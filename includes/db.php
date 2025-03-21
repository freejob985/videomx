<?php
/**
 * ملف الاتصال بقاعدة البيانات
 */

try {
    $db = new PDO(
        "mysql:host=localhost;dbname=courses_db;charset=utf8mb4",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
} 