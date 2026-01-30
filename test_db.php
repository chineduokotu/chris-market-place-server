<?php
$project_ref = 'urjquzycytreqwdytpok';
$password = 'chris-marketplac';
$pooler_host = 'aws-0-us-east-1.pooler.supabase.com'; // Trying us-east-1
$port = '6543'; // Supabase Pooler Port
$db = 'postgres';
$user = "postgres.$project_ref";

$main_host = 'urjquzycytreqwdytpok.supabase.co';
$ports = ['5432', '6543'];

foreach ($ports as $port) {
    try {
        $dsn = "pgsql:host=$main_host;port=$port;dbname=$db;connect_timeout=5";
        echo "Testing Main Host $main_host Port $port...\n";
        $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "SUCCESS on Port $port!\n";
    } catch (PDOException $e) {
        echo "Failed on Port $port: " . $e->getMessage() . "\n";
    }
}

try {
    $dsn = "pgsql:host=$pooler_host;port=$port;dbname=$db";
    echo "Connecting to Pooler $dsn...\n";
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Connected successfully to Pooler!\n";
} catch (PDOException $e) {
    echo "Pooler connection failed: " . $e->getMessage() . "\n";

    // Try eu-central-1 just in case
    $pooler_host = 'aws-0-eu-central-1.pooler.supabase.com';
    try {
        $dsn = "pgsql:host=$pooler_host;port=$port;dbname=$db";
        echo "Trying eu-central-1: $dsn...\n";
        $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "Connected successfully to eu-central-1 Pooler!\n";
    } catch (PDOException $e2) {
        echo "eu-central-1 Pooler failed: " . $e2->getMessage() . "\n";
    }
}
