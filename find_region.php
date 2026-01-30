<?php
$project_ref = 'urjquzycytreqwdytpok';
$password = 'chris-marketplac';
$regions = [
    'us-east-1',
    'us-east-2',
    'us-west-1',
    'us-west-2',
    'eu-central-1',
    'eu-west-1',
    'eu-west-2',
    'eu-west-3',
    'ap-southeast-1',
    'ap-southeast-2',
    'ap-northeast-1',
    'ap-northeast-2',
    'ap-northeast-3',
    'ap-south-1',
    'sa-east-1',
    'ca-central-1',
    'me-central-1',
    'af-south-1'
];
$port = '6543';
$db = 'postgres';
$user = "postgres.$project_ref";

foreach ($regions as $region) {
    $host = "aws-0-$region.pooler.supabase.com";
    try {
        $dsn = "pgsql:host=$host;port=$port;dbname=$db;connect_timeout=5";
        echo "Testing $region ($host)... ";
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        echo "SUCCESS!\n";
        exit(0);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Tenant or user not found') !== false) {
            echo "Region mismatch.\n";
        } else {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}
echo "No working region found.\n";
