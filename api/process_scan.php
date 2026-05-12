<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../modules/VisionProcessor.php';
require_once __DIR__ . '/../modules/CloudinaryProcessor.php';
require_once __DIR__ . '/../includes/prompts.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    $file = $_FILES['bill_image'] ?? null;
    $branch = $_POST['branch'] ?? 'Unknown';

    if (!$file) throw new Exception("No file uploaded");

    // Save locally first for processing
    $filename = time() . '_' . basename($file['name']);
    $localPath = UPLOAD_DIR . $filename;
    move_uploaded_file($file['tmp_name'], $localPath);

    $vision = new VisionProcessor($pdo);
    $cloudinary = new CloudinaryProcessor();
    
    // 1. Process with AI
    $prompt = get_ocr_prompt();
    $result = $vision->processImage($localPath, $prompt);

    if (!$result) throw new Exception("AI failed to extract data");

    // 2. Upload to Cloudinary for permanent storage
    $cloudUrl = $cloudinary->upload($localPath);

    // 3. Save to Database
    $pdo->beginTransaction();

    // Create or get customer
    $stmt = $pdo->prepare("INSERT INTO customers (full_name, nic_number, phone_number, address) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), address=VALUES(address), phone_number=VALUES(phone_number)");
    $stmt->execute([
        $result['full_name'] ?? 'Unknown',
        $result['nic_number'] ?? '',
        $result['phone_number'] ?? '',
        $result['address'] ?? ''
    ]);
    $customerId = $pdo->lastInsertId();

    // Insert pawn record
    $stmt = $pdo->prepare("
        INSERT INTO pawn_records (
            customer_id, branch_location, ir_no, r_no, receipt_no, 
            issue_date, last_date,
            article_description, weight_g, weight_mg, 
            principal_amount, agreed_amount, interest_paid, total_amount_collected,
            file_path, raw_ai_response, verification_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    $stmt->execute([
        $customerId,
        $result['branch_location'] ?? $branch,
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
        $cloudUrl,
        json_encode($result)
    ]);

    $newId = $pdo->lastInsertId();
    $pdo->commit();

    // Clean up local file after successful upload
    if (file_exists($localPath)) unlink($localPath);

    // Always return JSON for React
    echo json_encode([
        'success' => true,
        'data' => $result,
        'image_url' => $cloudUrl,
        'id' => $newId
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
