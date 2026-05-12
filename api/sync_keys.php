<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

echo "Refreshing API Keys in Database...<br>";

// Clear old keys
$pdo->exec("TRUNCATE TABLE api_usage");
echo "Old keys cleared.<br>";

// Insert new keys
$stmt = $pdo->prepare("INSERT INTO api_usage (api_key) VALUES (?)");
foreach (API_KEYS as $key) {
    $stmt->execute([$key]);
    echo "Added key: " . substr($key, 0, 10) . "...<br>";
}

echo "<br><b>SUCCESS: All 13 keys are synced and system is ready!</b>";
?>
