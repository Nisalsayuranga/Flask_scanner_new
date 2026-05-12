<?php
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    
    try {
        $pdo->beginTransaction();

        // 1. Update Customer
        $stmt = $pdo->prepare("
            UPDATE customers c
            JOIN pawn_records p ON p.customer_id = c.id
            SET c.full_name = ?, c.nic_number = ?, c.phone_number = ?, c.address = ?
            WHERE p.id = ?
        ");
        $stmt->execute([
            $_POST['full_name'],
            $_POST['nic_number'],
            $_POST['phone_number'],
            $_POST['address'],
            $id
        ]);

        // 2. Update Pawn Record
        $stmt = $pdo->prepare("
            UPDATE pawn_records SET 
            branch_location = ?, ir_no = ?, r_no = ?, receipt_no = ?, 
            article_description = ?, weight_g = ?, weight_mg = ?, 
            principal_amount = ?, agreed_amount = ?, total_amount_collected = ?,
            issue_date = ?, last_date = ?,
            verification_status = 'verified'
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['branch_location'],
            $_POST['ir_no'],
            $_POST['r_no'],
            $_POST['receipt_no'],
            $_POST['article_description'],
            $_POST['weight_g'],
            $_POST['weight_mg'],
            $_POST['principal_amount'],
            $_POST['agreed_amount'],
            $_POST['total_amount_collected'],
            $_POST['issue_date'],
            $_POST['last_date'],
            $id
        ]);

        // 3. Sync with separate Images Table
        $stmtImg = $pdo->prepare("
            UPDATE images SET customer_name = ?, nic_number = ?
            WHERE ir_no = ? AND (nic_number = ? OR nic_number IS NULL)
        ");
        $stmtImg->execute([
            $_POST['full_name'],
            $_POST['nic_number'],
            $_POST['ir_no'],
            $_POST['nic_number']
        ]);

        $pdo->commit();
        header("Location: index.php?success=1");
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Update failed: " . $e->getMessage());
    }
}
?>
