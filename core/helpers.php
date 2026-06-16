<?php
// =============================================================
//  core/helpers.php
//  Global utility functions – keep small & focused.
// =============================================================

// ----------------------------------------------------------
//  Flash messages
// ----------------------------------------------------------
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Render flash HTML (call inside template)
function renderFlash(): void
{
    $flash = getFlash();
    if (!$flash) return;
    $type = htmlspecialchars($flash['type']);
    $msg  = htmlspecialchars($flash['message']);
    echo "<div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">
              {$msg}
              <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
          </div>";
}

// ----------------------------------------------------------
//  Input sanitisation
// ----------------------------------------------------------
function sanitize(mixed $value): string
{
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

function postStr(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function postInt(string $key, int $default = 0): int
{
    return isset($_POST[$key]) ? (int)$_POST[$key] : $default;
}

function postFloat(string $key, float $default = 0.0): float
{
    return isset($_POST[$key]) ? (float)$_POST[$key] : $default;
}

function getStr(string $key, string $default = ''): string
{
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

function getInt(string $key, int $default = 0): int
{
    return isset($_GET[$key]) ? (int)$_GET[$key] : $default;
}

// ----------------------------------------------------------
//  CSRF
// ----------------------------------------------------------
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('CSRF token mismatch. Please go back and try again.');
    }
}

// ----------------------------------------------------------
//  Date helpers
// ----------------------------------------------------------
function monthName(int $month): string
{
    return date('F', mktime(0, 0, 0, $month, 1));
}

function daysInMonth(int $year, int $month): int
{
    return (int) date('t', mktime(0, 0, 0, $month, 1, $year));
}

function formatDate(string $date, string $format = 'd M Y'): string
{
    return date($format, strtotime($date));
}

// ----------------------------------------------------------
//  Money formatting
// ----------------------------------------------------------
function formatRs(float $amount): string
{
    return 'Rs. ' . number_format($amount, 2);
}

// ----------------------------------------------------------
//  Redirect helper
// ----------------------------------------------------------
function redirect(string $path): never
{
    header('Location: ' . APP_URL . $path);
    exit;
}

// ----------------------------------------------------------
//  Generate next employee code
// ----------------------------------------------------------
function nextEmpCode(): string
{
    $db  = Database::getInstance();
    $row = $db->fetchOne('SELECT MAX(CAST(SUBSTRING(emp_code, 5) AS UNSIGNED)) AS max_num FROM employees');
    $num = (int)($row['max_num'] ?? 0) + 1;
    return 'EMP-' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

// =============================================================
//  AuditLog – static helper
// =============================================================
class AuditLog
{
    public static function write(
        string $action,
        string $tableName,
        ?int   $recordId   = null,
        ?string $description = null
    ): void {
        try {
            $db = Database::getInstance();
            $db->insert(
                'INSERT INTO audit_log (user_id, action, table_name, record_id, description, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    Auth::id(),
                    $action,
                    $tableName,
                    $recordId,
                    $description,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                ]
            );
        } catch (Throwable) {
            // Audit failure must never break the main flow
        }
    }
}
