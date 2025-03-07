<?php
/**
 * ملف الإعدادات الرئيسي
 * يحتوي على الثوابت والإعدادات الأساسية للنظام
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('INCLUDED', true);

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'courses_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// مفتاح YouTube API
define('YOUTUBE_API_KEY', 'AIzaSyDGPD8_t3EAlU4f_pMOGjECkVQr-p3oRvY');

// إضافة token المصادقة لـ YouTube API
define('YOUTUBE_OAUTH_TOKEN', 'YOUR_OAUTH_TOKEN_HERE');

// إعدادات النظام
define('SYSTEM_NAME', 'نظام إدارة الكورسات');
define('SYSTEM_VERSION', '1.0.0');
define('SYSTEM_LANG', 'ar');
define('SYSTEM_TIMEZONE', 'Asia/Riyadh');

// ضبط المنطقة الزمنية
date_default_timezone_set(SYSTEM_TIMEZONE);

// مسار المجلد المؤقت
define('TEMP_DIR', __DIR__ . '/temp');

// التأكد من وجود المجلد المؤقت
if (!file_exists(TEMP_DIR)) {
    mkdir(TEMP_DIR, 0777, true);
}

// إنشاء اتصال قاعدة البيانات
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// تضمين الملفات المطلوبة
require_once __DIR__ . '/helper_functions.php';
require_once __DIR__ . '/youtube_api.php';

/**
 * دالة تحديث تقدم العملية
 * @param int $current العنصر الحالي
 * @param int $total العدد الكلي
 * @param string $title عنوان العنصر
 */
function updateProgress($current, $total, $title) {
    $progress = round(($current / $total) * 100);
    echo json_encode([
        'success' => true,
        'progress' => $progress,
        'current' => $current,
        'total' => $total,
        'title' => $title
    ]);
}

/**
 * دالة إضافة درس جديد
 * @param int $courseId معرف الكورس
 * @param array $videoDetails تفاصيل الفيديو
 * @param int $languageId معرف اللغة
 * @param int $current رقم الدرس الحالي
 * @param int $total إجمالي عدد الدروس
 */
function addLesson($courseId, $videoDetails, $languageId, $current, $total) {
    global $db;
    
    // جلب الحالة الافتراضية (جديد) للغة المحددة
    $stmt = $db->prepare('
        SELECT id FROM statuses 
        WHERE name = ? AND language_id = ? 
        LIMIT 1
    ');
    $stmt->execute(['جديد', $languageId]);
    $defaultStatusId = $stmt->fetchColumn();
    
    if (!$defaultStatusId) {
        // إذا لم يتم العثور على الحالة، قم بإنشائها
        $stmt = $db->prepare('
            INSERT INTO statuses (name, language_id) 
            VALUES (?, ?)
        ');
        $stmt->execute(['جديد', $languageId]);
        $defaultStatusId = $db->lastInsertId();
    }

    // إضافة الدرس مع الحالة الافتراضية
    $stmt = $db->prepare('
        INSERT INTO lessons (
            title, video_url, duration, course_id,
            status_id, thumbnail, tags, transcript
        ) VALUES (
            :title, :video_url, :duration, :course_id,
            :status_id, :thumbnail, :tags, :transcript
        )
    ');

    $stmt->execute([
        ':title' => $videoDetails['title'],
        ':video_url' => $videoDetails['video_url'],
        ':duration' => $videoDetails['duration'] ?? 0,
        ':course_id' => $courseId,
        ':status_id' => $defaultStatusId, // استخدام الحالة الافتراضية
        ':thumbnail' => $videoDetails['thumbnail'] ?? '',
        ':tags' => $videoDetails['tags'] ?? null,
        ':transcript' => $videoDetails['transcript'] ?? ''
    ]);

    // تحديث التقدم
    updateProgress($current, $total, $videoDetails['title']);
}

/**
 * دالة جلب تفاصيل الفيديو مع النص المكتوب
 * @param string $videoId معرف الفيديو
 * @param string $apiKey مفتاح API
 * @return array تفاصيل الفيديو والنص المكتوب
//  */
// function getVideoDetails($videoId, $apiKey) {
//     // جلب تفاصيل الفيديو
//     $videoUrl = "https://www.googleapis.com/youtube/v3/videos?part=contentDetails,snippet&id={$videoId}&key={$apiKey}";
    
//     $ch = curl_init();
//     curl_setopt($ch, CURLOPT_URL, $videoUrl);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//     $response = curl_exec($ch);
//     curl_close($ch);

//     $videoData = json_decode($response, true);

//     // جلب النص المكتوب
//     $transcriptUrl = "https://www.googleapis.com/youtube/v3/captions?part=snippet&videoId={$videoId}&key={$apiKey}";
    
//     $ch = curl_init();
//     curl_setopt($ch, CURLOPT_URL, $transcriptUrl);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//     $response = curl_exec($ch);
//     curl_close($ch);

//     $transcriptData = json_decode($response, true);
    
//     // دمج البيانات
//     $videoData['transcript'] = '';
//     if (isset($transcriptData['items']) && count($transcriptData['items']) > 0) {
//         $captionId = $transcriptData['items'][0]['id'];
        
//         // جلب محتوى النص المكتوب
//         $transcriptContentUrl = "https://www.googleapis.com/youtube/v3/captions/{$captionId}?key={$apiKey}";
        
//         $ch = curl_init();
//         curl_setopt($ch, CURLOPT_URL, $transcriptContentUrl);
//         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//         $response = curl_exec($ch);
//         curl_close($ch);

//         if ($response) {
//             $videoData['transcript'] = base64_decode($response);
//         }
//     }

//     return $videoData;
// }

/**
 * دالة معالجة النص المكتوب وتنظيفه
 * @param string $transcript النص المكتوب الخام
 * @return string النص المكتوب المنظف
 */
function cleanTranscript($transcript) {
    // إزالة علامات HTML
    $transcript = strip_tags($transcript);
    
    // إزالة الأوقات
    $transcript = preg_replace('/\[\d{2}:\d{2}:\d{2}\.\d{3}\]/', '', $transcript);
    
    // تنظيف الأسطر الفارغة المتكررة
    $transcript = preg_replace('/\n\s*\n/', "\n", $transcript);
    
    return trim($transcript);
}

/**
 * دالة جلب تفاصيل قائمة التشغيل
 * @param string $playlistId معرف قائمة التشغيل
 * @param string $apiKey مفتاح API
 * @return array تفاصيل قائمة التشغيل
 */

/**
 * معالجة إضافة كورس جديد
 * @param string $courseLink رابط قائمة التشغيل
 * @param int $courseLanguage معرف اللغة
 * @return array نتيجة العملية
 */
function addNewCourse($courseLink, $courseLanguage) {
    global $db;
    
    try {
        // استخراج معرف قائمة التشغيل
        if (!preg_match('/list=([^&]+)/', $courseLink, $matches)) {
            throw new Exception('رابط قائمة التشغيل غير صالح');
        }
        
        $playlistId = $matches[1];
        $apiKey = YOUTUBE_API_KEY;

        // جلب تفاصيل قائمة التشغيل
        $playlistInfo = getPlaylistDetails($playlistId, $apiKey);
        
        // التحقق من خصوصية قائمة التشغيل
        if ($playlistInfo['privacy_status'] !== 'public') {
            throw new Exception('قائمة التشغيل يجب أن تكون عامة');
        }

        // التحقق من وجود الكورس
        $stmt = $db->prepare('SELECT id FROM courses WHERE title = :title AND language_id = :language_id');
        $stmt->execute([
            ':title' => $playlistInfo['title'],
            ':language_id' => $courseLanguage
        ]);
        
        if ($stmt->fetch()) {
            throw new Exception('هذا الكورس موجود بالفعل');
        }

        // إضافة الكورس
        $stmt = $db->prepare('
            INSERT INTO courses (
                title, description, thumbnail, language_id,
                channel_title, video_count, playlist_url,
                created_at
            ) VALUES (
                :title, :description, :thumbnail, :language_id,
                :channel_title, :video_count, :playlist_url,
                CURRENT_TIMESTAMP
            )
        ');

        $stmt->execute([
            ':title' => $playlistInfo['title'],
            ':description' => $playlistInfo['description'],
            ':thumbnail' => $playlistInfo['thumbnail'],
            ':language_id' => $courseLanguage,
            ':channel_title' => $playlistInfo['channel_title'],
            ':video_count' => $playlistInfo['video_count'],
            ':playlist_url' => $courseLink
        ]);

        $courseId = $db->lastInsertId();

        // جلب وإضافة الدروس
        $playlistItems = getPlaylistItems($playlistId, $apiKey);
        $totalLessons = count($playlistItems['items']);

        foreach ($playlistItems['items'] as $index => $item) {
            $videoId = $item['snippet']['resourceId']['videoId'];
            $videoDetails = getVideoDetails($videoId, $apiKey);
            
            // إضافة الدرس
            addLesson($courseId, $videoDetails, $courseLanguage, $index + 1, $totalLessons);
            
            // تأخير لتجنب تجاوز حد API
            usleep(500000);
        }

        return [
            'success' => true,
            'message' => 'تم إضافة الكورس بنجاح',
            'course_id' => $courseId
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
} 