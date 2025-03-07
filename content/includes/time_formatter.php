<?php
/**
 * ملف معالجة وتنسيق الوقت
 * يحتوي على الدوال المسؤولة عن تنسيق وعرض الوقت بالشكل المطلوب
 */

/**
 * دالة تحول الثواني إلى تنسيق ساعات:دقائق:ثواني
 * @param int $seconds عدد الثواني
 * @param bool $use_arabic_format استخدام التنسيق العربي (س، د)
 * @return string الوقت بالتنسيق المطلوب
 */
function format_time($seconds, $use_arabic_format = false) {
    if ($seconds <= 0) {
        return "00:00:00";
    }

    // تحويل الثواني إلى ساعات ودقائق وثواني
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;

    // تنسيق الوقت مع إضافة الأصفار في البداية
    return sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
}

/**
 * دالة تحول تنسيق الوقت HH:MM:SS إلى ثواني
 * @param string $time الوقت بتنسيق HH:MM:SS
 * @return int عدد الثواني
 */
function time_to_seconds($time) {
    $parts = array_reverse(explode(":", $time));
    $seconds = 0;
    
    if (isset($parts[0])) $seconds += intval($parts[0]); // ثواني
    if (isset($parts[1])) $seconds += intval($parts[1]) * 60; // دقائق
    if (isset($parts[2])) $seconds += intval($parts[2]) * 3600; // ساعات
    
    return $seconds;
}

/**
 * دالة تحول الدقائق إلى ثواني
 * 
 * @param int $minutes عدد الدقائق
 * @return int عدد الثواني
 */
function minutes_to_seconds($minutes) {
    return $minutes * 60;
}

/**
 * دالة تجمع مجموعة من الأوقات بتنسيق HH:MM:SS
 * 
 * @param array $times مصفوفة من الأوقات بتنسيق HH:MM:SS
 * @return string مجموع الأوقات بنفس التنسيق
 */
function sum_times($times) {
    $total_seconds = 0;
    foreach ($times as $time) {
        $parts = array_reverse(explode(":", $time));
        $seconds = 0;
        if (isset($parts[0])) $seconds += intval($parts[0]);
        if (isset($parts[1])) $seconds += intval($parts[1]) * 60;
        if (isset($parts[2])) $seconds += intval($parts[2]) * 3600;
        $total_seconds += $seconds;
    }
    return format_time($total_seconds);
}

/**
 * دالة تحول الدقائق إلى تنسيق ساعات ودقائق بالعربية
 * مثال: 101 دقيقة = "1س 41د"
 * 
 * @param int $minutes عدد الدقائق
 * @return string الوقت بتنسيق "XXس XXد"
 */
function format_duration_arabic($minutes) {
    if ($minutes <= 0) {
        return "0د";
    }

    $hours = floor($minutes / 60);
    $remaining_minutes = $minutes % 60;
    
    $parts = [];
    if ($hours > 0) {
        $parts[] = $hours . "س";
    }
    if ($remaining_minutes > 0 || count($parts) == 0) {
        $parts[] = $remaining_minutes . "د";
    }
    
    return implode(" ", $parts);
} 