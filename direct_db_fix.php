<?php
$host = '127.0.0.1';
$db   = 'wip_gla';
$user = 'kai';
$pass = 'ghimli2026';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     echo "Connected to database.\n";

     // Check if last_login_at exists
     $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login_at'");
     if ($result->rowCount() == 0) {
         $pdo->exec("ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL AFTER password");
         echo "Added 'last_login_at' column.\n";
     } else {
         echo "'last_login_at' column already exists.\n";
     }

     // Check if status exists
     $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
     if ($result->rowCount() == 0) {
         $pdo->exec("ALTER TABLE users ADD COLUMN status VARCHAR(255) DEFAULT 'active' AFTER last_login_at");
         echo "Added 'status' column.\n";
     } else {
         echo "'status' column already exists.\n";
     }

     echo "Database update completed.\n";

} catch (\PDOException $e) {
     echo "Error: " . $e->getMessage() . "\n";
}
