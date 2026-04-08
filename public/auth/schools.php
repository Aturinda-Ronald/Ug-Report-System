<?php
declare(strict_types=1);

// Include configuration
require_once __DIR__ . '/../../config/config.php';

// Set JSON content type
header('Content-Type: application/json');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get search query
$query = sanitize_input($_GET['q'] ?? '');

// Validate query
if (strlen($query) < 2) {
    echo json_encode(['schools' => [], 'message' => 'Query too short']);
    exit;
}

try {
    $db = Database::getInstance();

    // Search for active schools matching the query
    $stmt = $db->prepare("
        SELECT id, name, district, emis_number
        FROM schools
        WHERE is_active = 1
        AND (name LIKE ? OR district LIKE ? OR emis_number LIKE ?)
        ORDER BY name ASC
        LIMIT 10
    ");

    $searchTerm = '%' . $query . '%';
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $schools = $stmt->fetchAll();

    // Format response
    $response = [
        'schools' => array_map(function($school) {
            return [
                'id' => (int)$school['id'],
                'name' => $school['name'],
                'district' => $school['district'],
                'emis' => $school['emis_number']
            ];
        }, $schools),
        'count' => count($schools)
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log('School search error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Search failed', 'schools' => []]);
}
