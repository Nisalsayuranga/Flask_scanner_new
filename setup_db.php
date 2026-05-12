<?php
require_once 'includes/config.php';

// Simple PDO connection check and table creation
try {
    if (DB_TYPE === 'pgsql') {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    } else {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    echo "<h1>Database Setup</h1>";
    echo "Connected to " . DB_TYPE . " successfully!<br><br>";

    // 1. Create Customers Table
    $id_col = (DB_TYPE === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
    
    $sql1 = "CREATE TABLE IF NOT EXISTS customers (
      $id_col,
      full_name varchar(255) NOT NULL,
      nic_number varchar(20) NOT NULL UNIQUE,
      contact_number varchar(20) DEFAULT NULL,
      address text DEFAULT NULL,
      created_at " . (DB_TYPE === 'pgsql' ? "TIMESTAMP" : "datetime") . " DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql1);
    echo "✅ Customers table created/verified.<br>";

    // 2. Create Pawn Records Table
    $sql2 = "CREATE TABLE IF NOT EXISTS pawn_records (
      $id_col,
      customer_id int NOT NULL,
      branch_location varchar(100) DEFAULT NULL,
      branch_address text DEFAULT NULL,
      ir_no varchar(50) DEFAULT NULL,
      r_no varchar(50) DEFAULT NULL,
      receipt_no varchar(50) DEFAULT NULL,
      issue_date date DEFAULT NULL,
      payment_date date DEFAULT NULL,
      pawn_amount decimal(15,2) DEFAULT NULL,
      gross_weight decimal(10,3) DEFAULT NULL,
      net_weight decimal(10,3) DEFAULT NULL,
      article_details text DEFAULT NULL,
      interest_rate decimal(5,2) DEFAULT NULL,
      main_bill_url text DEFAULT NULL,
      receipt_url text DEFAULT NULL,
      created_at " . (DB_TYPE === 'pgsql' ? "TIMESTAMP" : "datetime") . " DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql2);
    echo "✅ Pawn Records table created/verified.<br>";

    echo "<br><b>Database setup complete!</b>";
    echo "<br><a href='index.php'>Go to Dashboard</a>";

} catch (PDOException $e) {
    echo "<h1>❌ Error</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
