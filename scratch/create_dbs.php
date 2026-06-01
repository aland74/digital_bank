<?php
// Script to automatically create the 4 required databases for the distributed regional branch architecture.

$host = '127.0.0.1';
$username = 'root';
$password = '';

$databases = [
    'distributed_bank_hq',
    'distributed_bank_erbil',
    'distributed_bank_sulaimaniyah',
    'distributed_bank_duhok'
];

try {
    // Connect to MySQL server without selecting a database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Successfully connected to MySQL server.\n";
    
    foreach ($databases as $db) {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        echo "Database `$db` created or already exists. ✅\n";
    }
    
    echo "\nAll databases initialized successfully! 🚀\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
