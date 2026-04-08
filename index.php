<?php
@session_start();
/**
 * PATH: /index.php
 * Root router - handles both localhost and VirtualHost scenarios
 */

// Include config to access base_url function
require_once __DIR__ . '/config/config.php';

// Detect if we're in VirtualHost mode (port 8082)
$port = $_SERVER['SERVER_PORT'] ?? '80';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isVirtualHost = ($port == '8082' || strpos($host, ':8082') !== false);

if ($isVirtualHost) {
    // For VirtualHost: redirect to public/index.php (the actual app entry point)
    header('Location: public/index.php');
    exit;
}

// For normal localhost: handle role-based routing
$role = $_SESSION['role'] ?? '';
if (!$role) {
    header('Location: ' . base_url(''));
    exit;
}

if ($role === 'SUPER_ADMIN') {
    header('Location: ' . base_url('super/'));
    exit;
}
if ($role === 'SCHOOL_ADMIN') {
    header('Location: ' . base_url('admin/'));
    exit;
}
if ($role === 'STAFF') {
    header('Location: ' . base_url('admin/'));
    exit;
}
if ($role === 'STUDENT') {
    header('Location: ' . base_url('student/'));
    exit;
}
header('Location: ' . base_url(''));
exit;
