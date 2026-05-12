<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow React to access
require_once __DIR__ . '/../includes/db.php';

try {
    // 1. Total Revenue (Principal Amount Sum)
    $stmt = $pdo->query("SELECT SUM(principal_amount) as total FROM pawn_records");
    $revenue = $stmt->fetch()['total'] ?? 0;

    // 2. New Customers (Total Count)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM customers");
    $customers = $stmt->fetch()['total'] ?? 0;

    // 3. Total Bills
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pawn_records");
    $bills = $stmt->fetch()['total'] ?? 0;

    // 4. Pending Verifications
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pawn_records WHERE verification_status = 'pending'");
    $pending = $stmt->fetch()['total'] ?? 0;

    // 5. Recent Transactions
    $stmt = $pdo->query("
        SELECT p.ir_no, p.principal_amount, p.verification_status, c.full_name 
        FROM pawn_records p 
        LEFT JOIN customers c ON p.customer_id = c.id 
        ORDER BY p.created_at DESC LIMIT 5
    ");
    $recent = $stmt->fetchAll();

    echo json_encode([
        'stats' => [
            ['title' => 'Total Revenue', 'value' => 'Rs. ' . number_format($revenue), 'icon' => 'TrendingUp', 'trend' => 'up', 'trendValue' => '+0%', 'color' => 'blue'],
            ['title' => 'New Customers', 'value' => $customers, 'icon' => 'Users', 'trend' => 'up', 'trendValue' => '+0%', 'color' => 'indigo'],
            ['title' => 'Total Bills', 'value' => $bills, 'icon' => 'FileText', 'trend' => 'none', 'trendValue' => '0', 'color' => 'purple'],
            ['title' => 'Pending Verifications', 'value' => $pending, 'icon' => 'Clock', 'trend' => 'up', 'trendValue' => $pending, 'color' => 'amber'],
        ],
        'recent' => $recent
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
