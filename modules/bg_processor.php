
<?php
/**
 * Background AI Processor
 * Processes a single pawn record using AI in the background.
 */

// Ignore user abort and remove time limits
ignore_user_abort(true);
set_time_limit(0);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/VisionProcessor.php';
require_once __DIR__ . '/../includes/prompts.php';

$recordId = $_GET['id'] ?? null;
if (!$recordId) exit;

try {
    // 1. Fetch record details
    $stmt = $pdo->prepare("SELECT * FROM pawn_records WHERE id = ? AND verification_status = 'processing'");
    $stmt->execute([$recordId]);
    $record = $stmt->fetch();

    if (!$record) exit;

    $vision = new VisionProcessor($pdo);
    $prompt = get_ocr_prompt();

    // 2. Call AI Vision API
    // Note: processImage can take a URL or a local path
    $result = $vision->processImage($record['file_path'], $prompt);

    if (!$result) {
        throw new Exception("AI failed to extract data");
    }

    // 3. Save results to Database
    $pdo->beginTransaction();

    // Find or create customer
    $stmt = $pdo->prepare("
        INSERT INTO customers (full_name, nic_number, phone_number, address) 
        VALUES (?, ?, ?, ?) 
        ON CONFLICT (nic_number) 
        DO UPDATE SET 
            address = EXCLUDED.address, 
            phone_number = EXCLUDED.phone_number
        RETURNING id
    ");
    $stmt->execute([
        $result['full_name'] ?? 'Unknown',
        $result['nic_number'] ?? '',
        $result['phone_number'] ?? '',
        $result['address'] ?? ''
    ]);
    $customerId = $stmt->fetchColumn();

    // Update pawn record with extracted data
    $stmt = $pdo->prepare("
        UPDATE pawn_records SET
            customer_id = ?,
            branch_location = ?,
            ir_no = ?,
            r_no = ?,
            receipt_no = ?,
            issue_date = ?,
            last_date = ?,
            article_description = ?,
            weight_g = ?,
            weight_mg = ?,
            principal_amount = ?,
            agreed_amount = ?,
            interest_paid = ?,
            total_amount_collected = ?,
            raw_ai_response = ?,
            verification_status = 'pending'
        WHERE id = ?
    ");

    $stmt->execute([
        $customerId,
        $result['branch_location'] ?? $record['branch_location'],
        $result['ir_no'] ?? null,
        $result['r_no'] ?? null,
        $result['receipt_no'] ?? null,
        $result['issue_date'] ?? null,
        $result['last_date'] ?? null,
        $result['article_description'] ?? null,
        floatval($result['weight_g'] ?? 0),
        floatval($result['weight_mg'] ?? 0),
        floatval(str_replace(',', '', $result['principal_amount'] ?? 0)),
        floatval(str_replace(',', '', $result['agreed_amount'] ?? 0)),
        floatval(str_replace(',', '', $result['interest_paid'] ?? 0)),
        floatval(str_replace(',', '', $result['total_amount_collected'] ?? 0)),
        json_encode($result),
        $recordId
    ]);

    $pdo->commit();

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    
    // Log error and update status to failed
    $stmt = $pdo->prepare("UPDATE pawn_records SET verification_status = 'failed', raw_ai_response = ? WHERE id = ?");
    $stmt->execute([json_encode(['error' => $e->getMessage()]), $recordId]);
    
    error_log("Background Processing Error (ID $recordId): " . $e->getMessage());
}
