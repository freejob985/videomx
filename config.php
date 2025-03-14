<?php
// إعداد الاتصال بقاعدة البيانات
$host = 'localhost';
$dbname = 'courses_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// تعيين المنطقة الزمنية
date_default_timezone_set('Asia/Riyadh');

// تعيين ترميز الاتصال
ini_set('default_charset', 'UTF-8');
?> 