<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'courses_db';

$conn = new mysqli($host, $username, $password, $database);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
} 