<?php
/**
 * ملف واجهة برمجة تطبيقات YouTube
 * يحتوي على الدوال الخاصة بالتعامل مع YouTube API
 */

// منع الوصول المباشر
if (!defined('INCLUDED')) {
    http_response_code(403);
    die('Access Forbidden');
}

require_once 'config.php';

/**
 * دالة للتحقق من صحة رابط YouTube
 * @param string $url الرابط المراد التحقق منه
 * @return bool صحة الرابط
 * @example isValidYoutubeUrl('https://www.youtube.com/playlist?list=123') // returns true
 */
function isValidYoutubeUrl($url) {
    return preg_match('/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.*/', $url);
}

/**
 * دالة لاستخراج معرف قائمة التشغيل من الرابط
 * @param string $url رابط قائمة التشغيل
 * @return string معرف قائمة التشغيل
 * @example extractPlaylistId('https://www.youtube.com/playlist?list=123') // returns '123'
 */
function extractPlaylistId($url) {
    if (preg_match('/list=([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return $matches[1];
    }
    return $url;
}

/**
 * دالة لاستخراج معرف الفيديو من الرابط
 * @param string $url رابط الفيديو
 * @return string معرف الفيديو
 * @example extractVideoId('https://www.youtube.com/watch?v=123') // returns '123'
 */
function extractVideoId($url) {
    if (preg_match('/(?:v=|\/v\/|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return $matches[1];
    }
    return $url;
}

/**
 * دالة لجلب تفاصيل قائمة التشغيل
 * @param string $playlistId معرف قائمة التشغيل
 * @param string $apiKey مفتاح API
 * @return array تفاصيل قائمة التشغيل
 * @throws Exception في حالة حدوث خطأ
 */
function getPlaylistDetails($playlistId, $apiKey) {
    // استخراج معرف قائمة التشغيل من الرابط إذا تم تقديم رابط كامل
    if (strpos($playlistId, 'list=') !== false) {
        $playlistId = extractPlaylistId($playlistId);
    }

    $parts = ['snippet', 'contentDetails', 'status'];
    
    $url = "https://www.googleapis.com/youtube/v3/playlists?" . http_build_query([
        'part' => implode(',', $parts),
        'id' => $playlistId,
        'key' => $apiKey,
        'maxResults' => 1
    ]);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("خطأ في CURL: " . $error);
    }

    $data = json_decode($response, true);
    
    if (!isset($data['items'][0])) {
        throw new Exception("قائمة التشغيل غير موجودة أو خاصة");
    }

    $item = $data['items'][0];
    return [
        'title' => $item['snippet']['title'] ?? '',
        'description' => $item['snippet']['description'] ?? '',
        'thumbnail' => $item['snippet']['thumbnails']['high']['url'] ?? '',
        'channel_title' => $item['snippet']['channelTitle'] ?? '',
        'video_count' => $item['contentDetails']['itemCount'] ?? 0,
        'privacy_status' => $item['status']['privacyStatus'] ?? 'private',
        'published_at' => $item['snippet']['publishedAt'] ?? '',
        'playlist_id' => $playlistId
    ];
}

/**
 * دالة لجلب عناصر قائمة التشغيل مع دعم الصفحات المتعددة
 * @param string $playlistId معرف قائمة التشغيل
 * @param string $apiKey مفتاح API
 * @return array عناصر قائمة التشغيل
 * @throws Exception في حالة حدوث خطأ
 */
function getPlaylistItems($playlistId, $apiKey) {
    $allItems = [];
    $nextPageToken = '';
    $maxAttempts = 10; // عدد المحاولات الأقصى
    $attempt = 0;
    
    do {
        try {
            $url = "https://www.googleapis.com/youtube/v3/playlistItems?" . http_build_query([
                'part' => 'snippet,contentDetails',
                'maxResults' => 50, // الحد الأقصى المسموح به
                'playlistId' => $playlistId,
                'key' => $apiKey,
                'pageToken' => $nextPageToken
            ]);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_ENCODING => ''
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception("CURL Error: " . $error);
            }

            if ($httpCode !== 200) {
                throw new Exception("HTTP Error: " . $httpCode);
            }

            $data = json_decode($response, true);
            
            if (!isset($data['items'])) {
                throw new Exception("Invalid response format");
            }

            // إضافة العناصر إلى المصفوفة الكلية
            foreach ($data['items'] as $item) {
                // التحقق من صحة العنصر
                if (isset($item['snippet']['resourceId']['videoId'])) {
                    $allItems[] = $item;
                }
            }
            
            // تحديث token للصفحة التالية
            $nextPageToken = $data['nextPageToken'] ?? '';
            
            // تأخير لتجنب تجاوز حد API
            usleep(500000); // 0.5 ثانية
            
            $attempt = 0; // إعادة تعيين عداد المحاولات عند النجاح
            
        } catch (Exception $e) {
            $attempt++;
            error_log("Error fetching playlist items (Attempt $attempt): " . $e->getMessage());
            
            if ($attempt >= $maxAttempts) {
                throw new Exception("Failed to fetch playlist items after $maxAttempts attempts");
            }
            
            // انتظار قبل المحاولة مرة أخرى
            sleep(2);
        }
    } while ($nextPageToken !== '' && count($allItems) < 1000); // حد أقصى للأمان

    return [
        'items' => $allItems,
        'totalItems' => count($allItems)
    ];
}

/**
 * دالة لجلب التفاصيل الأساسية للفيديو
 * @param string $videoId معرف الفيديو
 * @param string $apiKey مفتاح API
 * @return array تفاصيل الفيديو
 */
function getBasicVideoDetails($videoId, $apiKey) {
    $url = "https://www.googleapis.com/youtube/v3/videos?" . http_build_query([
        'part' => 'snippet,contentDetails',
        'id' => $videoId,
        'key' => $apiKey
    ]);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

/**
 * دالة لجلب تفاصيل الفيديو مع إعادة المحاولة
 * @param string $videoId معرف الفيديو
 * @param string $apiKey مفتاح API
 * @return array تفاصيل الفيديو
 * @throws Exception في حالة حدوث خطأ
 */
function getVideoDetails($videoId, $apiKey) {
    $maxAttempts = 5;
    $attempt = 0;
    
    do {
        try {
            $url = "https://www.googleapis.com/youtube/v3/videos?" . http_build_query([
                'part' => 'snippet,contentDetails,statistics',
                'id' => $videoId,
                'key' => $apiKey
            ]);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_ENCODING => ''
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception("HTTP Error: " . $httpCode);
            }

            $data = json_decode($response, true);
            
            if (!isset($data['items'][0])) {
                throw new Exception("Video not found or is private");
            }

            $video = $data['items'][0];
            
            return [
                'title' => $video['snippet']['title'],
                'description' => $video['snippet']['description'],
                'thumbnail' => $video['snippet']['thumbnails']['high']['url'] ?? '',
                'duration' => ISO8601ToSeconds($video['contentDetails']['duration']),
                'video_url' => "https://www.youtube.com/watch?v=" . $videoId,
                'tags' => $video['snippet']['tags'] ?? [],
                'view_count' => $video['statistics']['viewCount'] ?? 0,
                'published_at' => $video['snippet']['publishedAt']
            ];
            
        } catch (Exception $e) {
            $attempt++;
            error_log("Error fetching video details (Attempt $attempt): " . $e->getMessage());
            
            if ($attempt >= $maxAttempts) {
                throw new Exception("Failed to fetch video details after $maxAttempts attempts");
            }
            
            sleep(1);
        }
    } while ($attempt < $maxAttempts);
    
    throw new Exception("Failed to fetch video details");
}

/**
 * دالة لجلب النص المكتوب للفيديو
 * @param string $videoId معرف الفيديو
 * @param string $apiKey مفتاح API
 * @return string النص المكتوب أو نص فارغ
 */
function getVideoTranscript($videoId, $apiKey) {
    try {
        // جلب قائمة النصوص المكتوبة المتاحة
        $url = "https://www.googleapis.com/youtube/v3/captions?" . http_build_query([
            'part' => 'snippet',
            'videoId' => $videoId,
            'key' => $apiKey
        ]);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . YOUTUBE_OAUTH_TOKEN
            ]
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        
        if (!isset($data['items'][0])) {
            return '';
        }

        // جلب محتوى النص المكتوب
        $captionId = $data['items'][0]['id'];
        $downloadUrl = "https://www.googleapis.com/youtube/v3/captions/{$captionId}";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $downloadUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . YOUTUBE_OAUTH_TOKEN,
                'Accept: text/plain'
            ]
        ]);
        
        $transcript = curl_exec($ch);
        curl_close($ch);

        return cleanTranscript($transcript);
    } catch (Exception $e) {
        error_log("Error getting transcript for video {$videoId}: " . $e->getMessage());
        return '';
    }
} 