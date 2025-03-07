<?php
/**
 * ملف الاتصال بقاعدة البيانات
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'courses_db');

// إنشاء اتصال بقاعدة البيانات
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// التحقق من نجاح الاتصال
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// تعيين ترميز الاتصال
mysqli_set_charset($conn, "utf8mb4");

try {
    $config = require __DIR__ . '/../config/database.php';
    
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}"
    ];
    
    $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
} catch (PDOException $e) {
    error_log("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
    die("فشل الاتصال بقاعدة البيانات");
} 