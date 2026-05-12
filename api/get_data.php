<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../includes/db.php';

// Check which data is requested
$type = $_GET['type'] ?? 'records';

try {
    if ($type === 'records') {
        $stmt = $pdo->query("
            SELECT p.*, 
                   c.full_name as customer_name, 
                   c.nic_number, 
                   c.phone_number, 
                   c.address 
            FROM pawn_records p 
            LEFT JOIN customers c ON p.customer_id = c.id 
            ORDER BY p.created_at DESC
        ");
        echo json_encode($stmt->fetchAll());
    } else if ($type === 'customers') {
        $stmt = $pdo->query("
            SELECT c.*, COUNT(p.id) as record_count 
            FROM customers c 
            LEFT JOIN pawn_records p ON c.id = p.customer_id 
            GROUP BY c.id 
            ORDER BY c.full_name ASC
        ");
        echo json_encode($stmt->fetchAll());
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
