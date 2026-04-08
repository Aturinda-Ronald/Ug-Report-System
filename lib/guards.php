<?php
@session_start();
/**
 * PATH: /lib/guards.php
 * Redirect unauthenticated users to /public/
 */
if (!function_exists('redirect')) {
    function redirect(string $to): void { header('Location: ' . $to); exit; }
}
if (!function_exists('require_login')) {
    function require_login(): void {
        $role = $_SESSION['role'] ?? '';
        if (!$role) redirect('/public/');
    }
}
if (!function_exists('require_admin')) {
    function require_admin(): void {
        $role = $_SESSION['role'] ?? '';
        if (!in_array($role, ['SCHOOL_ADMIN','SUPER_ADMIN','STAFF'], true)) redirect('/public/');
    }
}
if (!function_exists('require_student')) {
    function require_student(): void {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'STUDENT') redirect('/public/');
    }
}
