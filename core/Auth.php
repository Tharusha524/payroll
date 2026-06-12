<?php
// =============================================================
//  core/Auth.php - Multi-user Role-based Authentication System
//  Uses legacy MD5 hashes stored in `users.password` (per project preference).
// =============================================================

class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Optional: expire idle sessions
        if (defined('SESSION_TIMEOUT') && isset($_SESSION['last_active'])) {
            if ((time() - $_SESSION['last_active']) > SESSION_TIMEOUT) {
                self::logout();
                return;
            }
        }
        $_SESSION['last_active'] = time();
    }

    public static function login(string $username, string $password): bool
    {
        self::start();
        $db = Database::getInstance();
        $user = $db->fetchOne(
            'SELECT * FROM users WHERE username = ? AND is_active = 1',
            [$username]
        );

        if (!$user) {
            return false;
        }

        // Use legacy MD5 `password` column for authentication.
        if (!empty($user['password']) && md5($password) === $user['password']) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            if (class_exists('AuditLog')) {
                AuditLog::write('login', 'users', $user['id'], 'User logged in');
            }
            return true;
        }

        return false;
    }

    public static function logout(): void
    {
        if (self::isLoggedIn() && class_exists('AuditLog')) {
            AuditLog::write('logout', 'users', self::id(), 'User logged out');
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/modules/auth/login.php');
        exit;
    }

    public static function isLoggedIn(): bool
    {
        self::start();
        return !empty($_SESSION['user_id']);
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . (defined('APP_URL') ? APP_URL : '') . '/modules/auth/login.php');
            exit;
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        $currentRole = $_SESSION['role'] ?? '';
        foreach ($roles as $role) {
            if (strcasecmp($currentRole, $role) === 0) {
                return;
            }
        }
        http_response_code(403);
        include ROOT_PATH . '/templates/403.php';
        exit;
    }

    public static function id(): int    { self::start(); return (int)($_SESSION['user_id'] ?? 0); }
    public static function role(): string { self::start(); return $_SESSION['role'] ?? ''; }
    public static function name(): string { self::start(); return $_SESSION['full_name'] ?? ''; }
    public static function isAdmin(): bool { return self::role() === 'admin'; }
}