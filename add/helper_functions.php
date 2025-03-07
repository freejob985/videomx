<?php
/**
 * ملف الدوال المساعدة
 * يحتوي على الدوال العامة المستخدمة في النظام
 */

// منع الوصول المباشر
if (!defined('INCLUDED')) {
    http_response_code(403);
    die('Access Forbidden');
}

require_once 'config.php';

/**
 * دالة لتنظيف وتأمين المدخلات
 * @param string $input النص المراد تنظيفه
 * @return string النص بعد التنظيف
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * دالة لإنشاء استجابة JSON
 * @param bool $success حالة النجاح
 * @param string $message رسالة الاستجابة
 * @param mixed $data البيانات الإضافية (اختياري)
 * @return string استجابة JSON
 */
function jsonResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    return json_encode($response, JSON_UNESCAPED_UNICODE);
}

/**
 * دالة لتنسيق المدة الزمنية
 * @param int $seconds المدة بالثواني
 * @return string المدة منسقة (HH:MM:SS)
 */
function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}

/**
 * دالة لتحويل مدة ISO 8601 إلى ثواني
 * @param string $duration المدة بتنسيق ISO 8601
 * @return int المدة بالثواني
 */
function ISO8601ToSeconds($duration) {
    preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $matches);
    $hours = !empty($matches[1]) ? $matches[1] : 0;
    $minutes = !empty($matches[2]) ? $matches[2] : 0;
    $seconds = !empty($matches[3]) ? $matches[3] : 0;
    
    return $hours * 3600 + $minutes * 60 + $seconds;
}

/**
 * دالة التحقق من الصلاحيات
 * @param string $permission الصلاحية المطلوبة
 * @return bool نتيجة التحقق
 */
function checkPermission($permission) {
    // TODO: تنفيذ التحقق من الصلاحيات
    return true;
}

// الدوال التالية تم نقلها إلى config.php:
// - cleanTranscript()
// - updateProgress()
// - getPlaylistDetails()
// - getPlaylistItems()
// - getVideoDetails() 