<?php
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids'])) {
    $ids = $_POST['ids'];
    if (!empty($ids)) {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("DELETE FROM pawn_records WHERE id IN ($placeholders)");
        if ($stmt->execute($ids)) {
            header("Location: records.php?status=deleted");
            exit;
        }
    }
}

header("Location: records.php");
exit;
?>
