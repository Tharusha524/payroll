/* =============================================================
   assets/js/app.js
   Panda Payroll – main JavaScript
   ============================================================= */

'use strict';

// =============================================================
//  Timecard – live production pay calculation
// =============================================================
const TimecardCalc = (() => {

    const SHIFT_PRESETS = {
        s1: { start: '08.00', end: '14.00' },
        s2: { start: '14.00', end: '22.00' },
    };

    function getShiftInputs(row) {
        const inputs = row.querySelectorAll('.tc-shift');
        return {
            start: inputs?.[0] || null,
            end: inputs?.[1] || null,
        };
    }

    function syncShiftPresetFromInputs(row) {
        const presetSelect = row.querySelector('.tc-shift-preset');
        if (!presetSelect) return;

        const { start, end } = getShiftInputs(row);
        const startVal = (start?.value || '').trim();
        const endVal = (end?.value || '').trim();

        const matched = Object.entries(SHIFT_PRESETS)
            .find(([, v]) => v.start === startVal && v.end === endVal);

        presetSelect.value = matched ? matched[0] : '';
    }

    function applyShiftPreset(row, presetKey) {
        const preset = SHIFT_PRESETS[presetKey];
        const { start, end } = getShiftInputs(row);
        if (!preset || !start || !end) return;

        start.value = preset.start;
        end.value = preset.end;

        // Trigger recalculation hooks (safe even if not used)
        start.dispatchEvent(new Event('input', { bubbles: true }));
        end.dispatchEvent(new Event('input', { bubbles: true }));
    }

    // Product config injected from PHP via window.PRODUCTS
    // Format: [{id, target_weekday, target_saturday, rate_above, rate_below}, …]

    function calcProductionPay(productId, qty, dateStr, status) {
        const products = window.PRODUCTS || [];
        const product  = products.find(p => p.id == productId);
        if (!product || qty <= 0) return 0;

        const dow = new Date(dateStr + 'T00:00:00').getDay(); // 0=Sun
        if (dow === 0 && status !== 'holiday') return 0; // Sunday – only payable if Holiday

        // Holiday: always use rate_above for all units
        if (status === 'holiday') {
            return Math.round(qty * parseFloat(product.rate_above));
        }

        const target = (dow === 6)
            ? parseInt(product.target_saturday)
            : parseInt(product.target_weekday);

        const above = Math.max(0, qty - target);
        const below = Math.min(qty, target);

        return Math.round(
            (above * parseFloat(product.rate_above)) +
            (below * parseFloat(product.rate_below))
        );
    }

    function calcRowProductionPay(row) {
        const dateStr = row.dataset.date;
        const status  = row.querySelector('.tc-status')?.value;
        if (!dateStr) return 0;

        const dow = new Date(dateStr + 'T00:00:00').getDay(); // 0=Sun
        if (dow === 0 && status !== 'holiday') return 0;

        const qtyInputs = Array.from(row.querySelectorAll('.tc-qty'));
        const qtyRows = qtyInputs
            .map(input => ({
                productId: input.dataset.productId,
                qty: parseInt(input.value) || 0
            }))
            .filter(x => x.qty > 0);

        if (qtyRows.length === 0) return 0;

        // Holiday: per-product rate_above only
        if (status === 'holiday') {
            let total = 0;
            qtyRows.forEach(x => { total += calcProductionPay(x.productId, x.qty, dateStr, 'holiday'); });
            return total;
        }

        if (status !== 'work') return 0;

        // Work day: if 2+ products, use highest-qty product's target/rates
        if (qtyRows.length >= 2) {
            const totalQty = qtyRows.reduce((sum, x) => sum + x.qty, 0);
            const top = qtyRows.reduce((best, x) => (x.qty > best.qty ? x : best), qtyRows[0]);

            const products = window.PRODUCTS || [];
            const product  = products.find(p => p.id == top.productId);
            if (!product) return 0;

            const target = (dow === 6)
                ? parseInt(product.target_saturday)
                : parseInt(product.target_weekday);

            const below = Math.min(totalQty, target);
            const above = Math.max(0, totalQty - target);

            return Math.round(
                (below * parseFloat(product.rate_below)) +
                (above * parseFloat(product.rate_above))
            );
        }

        // Work day: single product => existing per-product target logic
        return calcProductionPay(qtyRows[0].productId, qtyRows[0].qty, dateStr, 'work');
    }

    function recalcRow(row) {
        const dateStr = row.dataset.date;
        const status  = row.querySelector('.tc-status')?.value;
        const workable = (status === 'work' || status === 'holiday');

        if (!dateStr || !workable) {
            row.querySelector('.tc-prodpay').textContent = '-';
            row.querySelector('.tc-total').textContent   = '-';
            return;
        }

        const prodPay = calcRowProductionPay(row);

        const ot         = parseFloat(row.querySelector('.tc-ot')?.value)       || 0;
        const dayDuty    = parseFloat(row.querySelector('.tc-dayduty')?.value)   || 0;
        const travelling = parseFloat(row.querySelector('.tc-travel')?.value)    || 0;
        const other      = parseFloat(row.querySelector('.tc-other')?.value)     || 0;
        const gross      = prodPay + ot + dayDuty + travelling + other;

        row.querySelector('.tc-prodpay').textContent = prodPay > 0 ? fmt(prodPay) : '-';
        row.querySelector('.tc-total').textContent   = gross > 0   ? fmt(gross)   : '-';
    }

    function recalcFooter() {
        const rows = document.querySelectorAll('#tcBody tr');
        let totProd = 0, totOt = 0, totDayDuty = 0, totTravel = 0, totOther = 0, totGross = 0;

        rows.forEach(row => {
            const status = row.querySelector('.tc-status')?.value;
            if (status !== 'work' && status !== 'holiday') return;

            totProd += calcRowProductionPay(row);
            totOt       += parseFloat(row.querySelector('.tc-ot')?.value)     || 0;
            totDayDuty  += parseFloat(row.querySelector('.tc-dayduty')?.value) || 0;
            totTravel   += parseFloat(row.querySelector('.tc-travel')?.value)  || 0;
            totOther    += parseFloat(row.querySelector('.tc-other')?.value)   || 0;
        });

        totGross = totProd + totOt + totDayDuty + totTravel + totOther;

        setText('#foot-prod',    fmt(totProd));
        setText('#foot-ot',      fmt(totOt));
        setText('#foot-dayduty', fmt(totDayDuty));
        setText('#foot-travel',  fmt(totTravel));
        setText('#foot-other',   fmt(totOther));
        setText('#foot-gross',   fmt(totGross));
    }

    function handleStatusChange(row) {
        const status = row.querySelector('.tc-status')?.value;
        const workable = (status === 'work' || status === 'holiday');

        // Money inputs
        const moneyInputs = row.querySelectorAll('.tc-ot, .tc-dayduty, .tc-travel, .tc-other');
        moneyInputs.forEach(el => {
            el.disabled = (status === 'off');
            if (status === 'off') el.value = '';
        });

        // Shift inputs + preset should only be enabled on workable days
        const shiftEls = row.querySelectorAll('.tc-shift, .tc-shift-preset');
        shiftEls.forEach(el => {
            el.disabled = !workable;
            if (!workable) el.value = '';
        });

        row.querySelectorAll('.tc-qty').forEach(el => {
            el.disabled = !workable;
            if (!workable) el.value = 0;
        });
        recalcRow(row);
        recalcFooter();
    }

    function init() {
        const tbody = document.getElementById('tcBody');
        if (!tbody) return;

        tbody.addEventListener('input', e => {
            const row = e.target.closest('tr');
            if (!row) return;

            if (e.target.classList.contains('tc-shift')) {
                syncShiftPresetFromInputs(row);
            }

            recalcRow(row);
            recalcFooter();
        });

        tbody.addEventListener('change', e => {
            if (e.target.classList.contains('tc-status')) {
                const row = e.target.closest('tr');
                handleStatusChange(row);
                return;
            }

            if (e.target.classList.contains('tc-shift-preset')) {
                const row = e.target.closest('tr');
                const status = row?.querySelector('.tc-status')?.value;
                const workable = (status === 'work' || status === 'holiday');
                if (!row || !workable) return;
                if (e.target.value) {
                    applyShiftPreset(row, e.target.value);
                }
                return;
            }
        });

        // Initial calculation
        tbody.querySelectorAll('tr').forEach(row => {
            syncShiftPresetFromInputs(row);
            recalcRow(row);
        });
        recalcFooter();
    }

    return { init };
})();

// =============================================================
//  Utilities
// =============================================================
function fmt(amount) {
    return 'Rs. ' + Number(amount).toLocaleString('en-LK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function setText(selector, text) {
    const el = document.querySelector(selector);
    if (el) el.textContent = text;
}

// Confirm before delete actions
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this record?');
}

// Photo preview on file select
function initPhotoUpload() {
    const fileInput = document.getElementById('photo_file');
    const preview   = document.getElementById('photo_preview');
    if (!fileInput || !preview) return;

    fileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        if (!['image/jpeg','image/png','image/webp'].includes(file.type)) {
            alert('Only JPEG, PNG, or WebP images are allowed.');
            this.value = '';
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('Photo must be under 2 MB.');
            this.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    });
}

// Month/year picker shortcut: sync two selects
function syncMonthYear(yearId, monthId, callback) {
    [yearId, monthId].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => {
            if (typeof callback === 'function') callback();
        });
    });
}

// =============================================================
//  Bootstrap on DOM ready
// =============================================================
document.addEventListener('DOMContentLoaded', () => {
    TimecardCalc.init();
    initPhotoUpload();

    // Auto-dismiss alerts after 4 s
    setTimeout(() => {
        document.querySelectorAll('.alert-dismissible').forEach(el => {
            const bsAlert = bootstrap?.Alert?.getOrCreateInstance?.(el);
            bsAlert?.close();
        });
    }, 4000);

    // Tooltip init
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
        .forEach(el => new bootstrap.Tooltip(el));
});
