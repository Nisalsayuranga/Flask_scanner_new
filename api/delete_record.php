<?php
require_once 'includes/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Optional: Delete associated images from disk
        $stmt = $pdo->prepare("SELECT receipt_bill_image, detail_bill_image FROM pawn_records WHERE id = ?");
        $stmt->execute([$id]);
        $files = $stmt->fetch();
        
        if ($files) {
            $splitDir = __DIR__ . '/uploads/splits/';
            if ($files['receipt_bill_image'] && file_exists($splitDir . $files['receipt_bill_image'])) {
                unlink($splitDir . $files['receipt_bill_image']);
            }
            if ($files['detail_bill_image'] && file_exists($splitDir . $files['detail_bill_image'])) {
                unlink($splitDir . $files['detail_bill_image']);
            }
        }

        // Delete the record
        $stmt = $pdo->prepare("DELETE FROM pawn_records WHERE id = ?");
        $stmt->execute([$id]);
        
        header("Location: records.php?deleted=1");
    } catch (PDOException $e) {
        die("Error deleting record: " . $e->getMessage());
    }
} else {
    header("Location: records.php");
}
?>
