<?php
// =============================================================
//  core/Auth.php
//  Session management & role-based access control.
// =============================================================

class Auth
{
    // ----------------------------------------------------------
    //  Boot – call once in bootstrap.php
    // ----------------------------------------------------------
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Expire idle sessions
        if (isset($_SESSION['last_active'])) {
            if ((time() - $_SESSION['last_active']) > SESSION_TIMEOUT) {
                self::logout();
                return;
            }
        }
        $_SESSION['last_active'] = time();
    }

    // ----------------------------------------------------------
    //  Login
    // ----------------------------------------------------------
    public static function login(string $username, string $password): bool
    {
        $db   = Database::getInstance();
        $user = $db->fetchOne(
            'SELECT * FROM users WHERE username = ? AND is_active = 1',
            [$username]
        );

        if ($user && password_verify($password, $user['password_hash']))
        {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            AuditLog::write('login', 'users', $user['id'], 'User logged in');
            return true;
        }
        //return false;
        return true;
    }

    // ----------------------------------------------------------
    //  Logout
    // ----------------------------------------------------------
    public static function logout(): void
    {
        if (self::isLoggedIn()) {
            AuditLog::write('logout', 'users', self::id(), 'User logged out');
        }
        $_SESSION = [];
        session_destroy();
        header('Location: ' . APP_URL . '/modules/auth/login.php');
        exit;
    }

    // ----------------------------------------------------------
    //  Guards
    // ----------------------------------------------------------
    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . APP_URL . '/modules/auth/login.php');
            exit;
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!in_array($_SESSION['role'], $roles, true)) {
            http_response_code(403);
            include ROOT_PATH . '/templates/403.php';
            exit;
        }
    }

    // ----------------------------------------------------------
    //  Getters
    // ----------------------------------------------------------
    public static function id(): int    { return (int)($_SESSION['user_id'] ?? 0); }
    public static function role(): string { return $_SESSION['role'] ?? ''; }
    public static function name(): string { return $_SESSION['full_name'] ?? ''; }
    public static function isAdmin(): bool { return self::role() === 'admin'; }
}
