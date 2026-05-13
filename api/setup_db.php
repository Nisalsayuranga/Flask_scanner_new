<?php
require_once __DIR__ . '/../includes/config.php';

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

    // Optional: Force drop tables
    if (isset($_GET['force']) && $_GET['force'] === 'true') {
        $pdo->exec("DROP TABLE IF EXISTS pawn_records, customers, api_usage CASCADE");
        echo "⚠️ Tables dropped successfully. Re-creating...<br>";
    }

    // 1. Create Customers Table
    $id_col = (DB_TYPE === 'pgsql') ? "id SERIAL PRIMARY KEY" : "id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
    
    $sql1 = "CREATE TABLE IF NOT EXISTS customers (
      $id_col,
      full_name varchar(255) NOT NULL,
      nic_number varchar(20) NOT NULL UNIQUE,
      phone_number varchar(20) DEFAULT NULL,
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
      last_date date DEFAULT NULL,
      article_description text DEFAULT NULL,
      weight_g decimal(10,3) DEFAULT NULL,
      weight_mg decimal(10,3) DEFAULT NULL,
      principal_amount decimal(15,2) DEFAULT NULL,
      agreed_amount decimal(15,2) DEFAULT NULL,
      interest_paid decimal(15,2) DEFAULT NULL,
      total_amount_collected decimal(15,2) DEFAULT NULL,
      file_path text DEFAULT NULL,
      raw_ai_response text DEFAULT NULL,
      verification_status varchar(20) DEFAULT 'pending',
      created_at " . (DB_TYPE === 'pgsql' ? "TIMESTAMP" : "datetime") . " DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql2);
    echo "✅ Pawn Records table created/verified.<br>";

    // 3. Create API Usage Table
    $sql3 = "CREATE TABLE IF NOT EXISTS api_usage (
      $id_col,
      api_key varchar(255) NOT NULL,
      request_count int DEFAULT 0,
      last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql3);
    echo "✅ API Usage table created/verified.<br>";

    // 4. Ensure article_description column exists (Safety for existing tables)
    if (DB_TYPE === 'pgsql') {
        $pdo->exec("ALTER TABLE pawn_records ADD COLUMN IF NOT EXISTS article_description text DEFAULT NULL");
    } else {
        // MySQL check
        $cols = $pdo->query("SHOW COLUMNS FROM pawn_records LIKE 'article_description'")->fetch();
        if (!$cols) {
            $pdo->exec("ALTER TABLE pawn_records ADD COLUMN article_description text DEFAULT NULL AFTER last_date");
        }
    }
    echo "✅ Schema consistency verified.<br>";

    // Add some dummy data if empty
    $count = $pdo->query("SELECT COUNT(*) FROM api_usage")->fetchColumn();
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO api_usage (api_key) VALUES (?)");
        foreach (API_KEYS as $key) {
            $stmt->execute([$key]);
        }
        echo "✅ API Keys initialized in database.<br>";
    }

    echo "<br><b>Database setup complete!</b>";
    echo "<br><a href='index.php'>Go to Dashboard</a>";

} catch (PDOException $e) {
    echo "<h1>❌ Error</h1>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
