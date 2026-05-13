<?php
require_once __DIR__ . '/../includes/db.php';

try {
    // Disable foreign key checks for a moment if needed (MySQL)
    // For Postgres, we use TRUNCATE CASCADE
    
    if (DB_TYPE === 'pgsql') {
        $pdo->exec("TRUNCATE TABLE pawn_records, customers RESTART IDENTITY CASCADE");
    } else {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("TRUNCATE TABLE pawn_records");
        $pdo->exec("TRUNCATE TABLE customers");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    echo "<h1>✅ Success!</h1>";
    echo "<p>All customer and pawn records have been cleared. Database is now clean.</p>";
    echo "<a href='index.php'>Go back to Dashboard</a>";

} catch (Exception $e) {
    echo "<h1>❌ Error!</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
