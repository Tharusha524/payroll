<?php
// =============================================================
//  config/app.php
//  Application-wide constants.
// =============================================================

define('APP_NAME',    'Panda Payroll');
define('APP_VERSION', '1.0.0');
define('APP_URL',     'http://localhost/panda-payroll-1');   // no trailing slash

// Absolute path to the project root (auto-detected)
define('ROOT_PATH',   dirname(__DIR__));

// Upload directories
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('EMPLOYEE_PHOTO_PATH', UPLOAD_PATH . '/employees');
define('EMPLOYEE_PHOTO_URL',  APP_URL . '/uploads/employees');

// Allowed photo types
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('MAX_PHOTO_SIZE',       2 * 1024 * 1024); // 2 MB

// Session
define('SESSION_TIMEOUT', 3600); // seconds (1 hour)

// Pagination
define('RECORDS_PER_PAGE', 20);

// Date / time
define('APP_TIMEZONE', 'Asia/Colombo');
date_default_timezone_set(APP_TIMEZONE);

// Days of week that count as Saturday / Sunday for payroll logic
define('DAY_SUNDAY',   0);  // PHP date('w') values
define('DAY_SATURDAY', 6);
