<?php
// db_connect.php
// فقط اتصال به دیتابیس. هیچ فایل دیگری را اینکلود نکنید.

$database_url = getenv('DATABASE_URL');

if ($database_url) {
    $db_parts = parse_url($database_url);
    $dsn = "pgsql:host={$db_parts['host']};port=5432;dbname=" . ltrim($db_parts['path'], '/') . ";sslmode=require";
    
    try {
        $conn = new PDO($dsn, $db_parts['user'], $db_parts['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        die("Database Connection Error.");
    }
}
?>
