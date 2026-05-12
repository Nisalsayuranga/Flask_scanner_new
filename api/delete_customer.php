<?php
require_once 'includes/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Delete the customer
        // Note: pawn_records will have customer_id set to NULL due to ON DELETE SET NULL constraint
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        
        header("Location: customers.php?deleted=1");
    } catch (PDOException $e) {
        die("Error deleting customer: " . $e->getMessage());
    }
} else {
    header("Location: customers.php");
}
?>
