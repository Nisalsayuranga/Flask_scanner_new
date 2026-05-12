<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$ids = $data['ids'] ?? [];
$type = $data['type'] ?? ''; // 'records' or 'customers'

if (empty($ids) || !is_array($ids)) {
    echo json_encode(['success' => false, 'error' => 'No IDs provided']);
    exit;
}

try {
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    
    if ($type === 'records') {
        $stmt = $pdo->prepare("DELETE FROM pawn_records WHERE id IN ($placeholders)");
        $stmt->execute($ids);
    } else if ($type === 'customers') {
        // When deleting customers, we should decide if we want to delete their records too
        // For safety, let's just delete the customers if they have no records, 
        // or the DB should have ON DELETE CASCADE if configured.
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id IN ($placeholders)");
        $stmt->execute($ids);
    } else {
        throw new Exception("Invalid deletion type");
    }

    echo json_encode(['success' => true, 'message' => count($ids) . ' items deleted successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
