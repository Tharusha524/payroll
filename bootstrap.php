<?php
// =============================================================
//  bootstrap.php
//  Single entry point for all configuration & core classes.
//  Every PHP page starts with: require_once __DIR__ . '/bootstrap.php';
//  (adjust path depth with dirname(__DIR__) as needed)
// =============================================================

// --- Constants -----------------------------------------------
require_once __DIR__ . '/config/app.php';

// --- Core classes (order matters) ----------------------------
require_once ROOT_PATH . '/core/Database.php';
require_once ROOT_PATH . '/core/helpers.php';
require_once ROOT_PATH . '/core/Auth.php';
require_once ROOT_PATH . '/core/PayrollEngine.php';

// --- Start session -------------------------------------------
Auth::start();
