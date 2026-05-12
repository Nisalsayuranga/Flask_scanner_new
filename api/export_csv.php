<?php
require_once __DIR__ . '/../includes/db.php';

// Get filter values from GET
$search = $_GET['search'] ?? '';
$branch = $_GET['branch'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build Query (Same as records.php)
$sql = "
    SELECT p.*, c.full_name, c.nic_number, c.phone_number
    FROM pawn_records p 
    LEFT JOIN customers c ON p.customer_id = c.id 
    WHERE 1=1
";
$params = [];

if ($search) {
    $sql .= " AND (p.ir_no LIKE ? OR p.r_no LIKE ? OR p.receipt_no LIKE ? OR c.full_name LIKE ? OR c.nic_number LIKE ? OR c.phone_number LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}
if ($branch) {
    $sql .= " AND p.branch_location = ?";
    $params[] = $branch;
}
if ($date_from) {
    $sql .= " AND DATE(p.created_at) >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $sql .= " AND DATE(p.created_at) <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set Headers for CSV Download
$filename = "Pawn_Report_" . ($branch ?: "All") . "_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

// Add CSV BOM for Excel UTF-8 support
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header Row
fputcsv($output, [
    'Date', 'Branch', 'IR No', 'R No', 'Receipt No', 
    'Customer Name', 'NIC Number', 'Phone Number', 
    'Article Description', 'Weight (g)', 'Weight (mg)', 
    'Principal Amount', 'Agreed Amount', 'Interest Paid', 'Status'
]);

// Data Rows
foreach ($records as $row) {
    fputcsv($output, [
        date('Y-m-d H:i', strtotime($row['created_at'])),
        $row['branch_location'],
        $row['ir_no'],
        $row['r_no'],
        $row['receipt_no'],
        $row['full_name'],
        $row['nic_number'],
        $row['phone_number'],
        $row['article_description'],
        $row['weight_g'],
        $row['weight_mg'],
        $row['principal_amount'],
        $row['agreed_amount'],
        $row['interest_paid'],
        $row['verification_status']
    ]);
}

fclose($output);
exit;
?>
