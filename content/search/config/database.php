<?php
/**
 * ملف الاتصال بقاعدة البيانات
 * يحتوي على معلومات الاتصال وإنشاء الاتصال
 */

// معلومات الاتصال بقاعدة البيانات
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'courses_db';

try {
    // إنشاء اتصال جديد
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    // التحقق من الاتصال
    if ($conn->connect_error) {
        throw new Exception("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
    }

    // ضبط الترميز
    $conn->set_charset("utf8");

} catch (Exception $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}
?> 