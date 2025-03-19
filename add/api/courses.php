<?php
/**
 * ملف معالجة طلبات API الخاصة بالكورسات
 * يتعامل مع إضافة وتحديث وحذف الكورسات والدروس
 */

require_once '../config.php';
require_once '../helper_functions.php';
require_once '../youtube_api.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            // جلب الكورسات مع معلومات اللغة والأقسام
            $query = "
                SELECT 
                    c.*,
                    l.name as language_name,
                    GROUP_CONCAT(DISTINCT s.name) as sections,
                    COUNT(DISTINCT ls.id) as lessons_count,
                    SUM(ls.duration) as total_duration
                FROM courses c
                LEFT JOIN languages l ON c.language_id = l.id
                LEFT JOIN course_sections cs ON c.id = cs.course_id
                LEFT JOIN sections s ON cs.section_id = s.id
                LEFT JOIN lessons ls ON c.id = ls.course_id
                GROUP BY c.id
                ORDER BY c.created_at DESC
            ";
            
            $stmt = $db->query($query);
            $courses = $stmt->fetchAll();
            
            // تنسيق البيانات
            foreach ($courses as &$course) {
                $course['sections'] = $course['sections'] ? explode(',', $course['sections']) : [];
                $course['total_duration_formatted'] = gmdate("H:i:s", $course['total_duration']);
            }
            
            echo jsonResponse(true, 'تم جلب الكورسات بنجاح', $courses);
        } catch (PDOException $e) {
            echo jsonResponse(false, 'خطأ في جلب الكورسات: ' . $e->getMessage());
        }
        break;

    case 'POST':
        try {
            $db->beginTransaction();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // التحقق من البيانات المطلوبة
            if (!isset($data['playlist_url']) || !isset($data['language_id'])) {
                throw new Exception('جميع الحقول المطلوبة يجب ملؤها');
            }
            
            $playlistUrl = sanitizeInput($data['playlist_url']);
            $languageId = (int)$data['language_id'];

            // التحقق من صحة رابط YouTube
            if (!isValidYoutubeUrl($playlistUrl)) {
                throw new Exception('رابط قائمة التشغيل غير صالح');
            }

            // التحقق من وجود اللغة
            $stmt = $db->prepare('SELECT id FROM languages WHERE id = ?');
            $stmt->execute([$languageId]);
            if (!$stmt->fetch()) {
                throw new Exception('اللغة المحددة غير موجودة');
            }

            // جلب تفاصيل قائمة التشغيل
            $playlistDetails = getPlaylistDetails($playlistUrl, YOUTUBE_API_KEY);
            
            // التحقق من خصوصية قائمة التشغيل
            if ($playlistDetails['privacy_status'] !== 'public') {
                throw new Exception('قائمة التشغيل يجب أن تكون عامة');
            }

            // التحقق من عدم وجود الكورس
            $stmt = $db->prepare('
                SELECT id FROM courses 
                WHERE playlist_url = :playlist_url OR 
                      (title = :title AND language_id = :language_id)
            ');
            $stmt->execute([
                ':playlist_url' => $playlistUrl,
                ':title' => $playlistDetails['title'],
                ':language_id' => $languageId
            ]);
            
            $existingCourse = $stmt->fetch();
            $courseId = null;
            
            if ($existingCourse) {
                // إذا كان الكورس موجود، استخدم معرفه
                $courseId = $existingCourse['id'];
                
                // تحديث معلومات الكورس
                $stmt = $db->prepare('
                    UPDATE courses SET 
                    processing_status = :processing_status,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                ');
                $stmt->execute([
                    ':processing_status' => 'pending',
                    ':id' => $courseId
                ]);
            } else {
                // إضافة الكورس الجديد
                $stmt = $db->prepare('
                    INSERT INTO courses (
                        title, description, playlist_url, language_id,
                        thumbnail, processing_status, created_at, updated_at
                    ) VALUES (
                        :title, :description, :playlist_url, :language_id,
                        :thumbnail, :processing_status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    )
                ');

                $stmt->execute([
                    ':title' => $playlistDetails['title'],
                    ':description' => $playlistDetails['description'],
                    ':playlist_url' => $playlistUrl,
                    ':language_id' => $languageId,
                    ':thumbnail' => $playlistDetails['thumbnail'],
                    ':processing_status' => 'pending'
                ]);

                $courseId = $db->lastInsertId();
            }
            
            // إنشاء ملف التقدم
            saveProgress($courseId, [
                'status' => 'processing',
                'progress' => 0,
                'current' => 0,
                'total' => $playlistDetails['video_count'],
                'latest_lesson' => '',
                'message' => 'جاري بدء المعالجة',
                'start_time' => microtime(true)
            ]);

            $db->commit();

            // إرسال الاستجابة
            echo jsonResponse(true, 'تم إضافة الكورس بنجاح', [
                'id' => $courseId,
                'title' => $playlistDetails['title']
            ]);

            // بدء معالجة الدروس في الخلفية
            ignore_user_abort(true);
            set_time_limit(0);
            flush();
            
            // معالجة الدروس
            processCourseVideos($courseId, $playlistUrl);

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo jsonResponse(false, 'خطأ في إضافة الكورس: ' . $e->getMessage());
        }
        break;

    case 'PUT':
        try {
            $db->beginTransaction();
            
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)$data['id'];
            $title = sanitizeInput($data['title']);
            $languageId = (int)$data['language_id'];
            $sections = isset($data['sections']) ? array_map('intval', $data['sections']) : [];

            // تحديث معلومات الكورس
            $stmt = $db->prepare('
                UPDATE courses 
                SET title = :title, language_id = :language_id 
                WHERE id = :id
            ');
            $stmt->execute([
                'title' => $title,
                'language_id' => $languageId,
                'id' => $id
            ]);

            // تحديث الأقسام
            $db->prepare('DELETE FROM course_sections WHERE course_id = ?')->execute([$id]);
            
            if (!empty($sections)) {
                $insertSections = $db->prepare('
                    INSERT INTO course_sections (course_id, section_id) 
                    VALUES (:course_id, :section_id)
                ');
                foreach ($sections as $sectionId) {
                    $insertSections->execute([
                        'course_id' => $id,
                        'section_id' => $sectionId
                    ]);
                }
            }

            $db->commit();
            echo jsonResponse(true, 'تم تحديث الكورس بنجاح');
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo jsonResponse(false, 'خطأ في تحديث الكورس: ' . $e->getMessage());
        }
        break;

    case 'DELETE':
        try {
            $id = (int)$_GET['id'];
            
            // حذف الكورس (سيتم حذف الدروس والعلاقات تلقائياً بسبب CASCADE)
            $stmt = $db->prepare('DELETE FROM courses WHERE id = :id');
            $stmt->execute(['id' => $id]);

            echo jsonResponse(true, 'تم حذف الكورس بنجاح');
        } catch (PDOException $e) {
            echo jsonResponse(false, 'خطأ في حذف الكورس: ' . $e->getMessage());
        }
        break;

    default:
        echo jsonResponse(false, 'طريقة الطلب غير مدعومة');
        break;
}

/**
 * دالة معالجة إضافة الكورس والدروس
 * @param int $courseId معرف الكورس
 * @param string $playlistUrl رابط قائمة التشغيل
 */
function processCourseVideos($courseId, $playlistUrl) {
    global $db;
    
    try {
        $startTime = microtime(true);
        $processedVideos = 0;
        $totalVideos = 0;
        $addedVideoIds = []; // لتتبع الفيديوهات المضافة

        // التحقق من عدم وجود معالجة جارية
        $stmt = $db->prepare('SELECT processing_status FROM courses WHERE id = ?');
        $stmt->execute([$courseId]);
        $course = $stmt->fetch();
        
        if ($course && $course['processing_status'] === 'processing') {
            throw new Exception('يوجد معالجة جارية لهذا الكورس');
        }

        // تحديث حالة المعالجة
        $db->prepare('UPDATE courses SET processing_status = ? WHERE id = ?')
           ->execute(['processing', $courseId]);

        $playlistId = extractPlaylistId($playlistUrl);
        
        // جلب تفاصيل قائمة التشغيل أولاً
        $playlistDetails = getPlaylistDetails($playlistId, YOUTUBE_API_KEY);
        $expectedCount = $playlistDetails['video_count'];

        // تحديث حالة البدء
        saveProgress($courseId, [
            'status' => 'processing',
            'progress' => 0,
            'current' => 0,
            'total' => $expectedCount,
            'latest_lesson' => '',
            'message' => 'جاري جلب تفاصيل قائمة التشغيل...',
            'start_time' => $startTime
        ]);

        // جلب عناصر قائمة التشغيل
        $playlistItems = getPlaylistItems($playlistId, YOUTUBE_API_KEY);

        if (!isset($playlistItems['items']) || empty($playlistItems['items'])) {
            throw new Exception('لا يمكن الوصول إلى فيديوهات قائمة التشغيل');
        }

        $totalVideos = count($playlistItems['items']);

        // التحقق من تطابق العدد المتوقع
        if ($totalVideos < $expectedCount) {
            error_log("Warning: Expected {$expectedCount} videos but got {$totalVideos}");
        }

        // جلب الدروس الموجودة مسبقاً للكورس
        $existingLessons = [];
        $stmt = $db->prepare('SELECT video_url FROM lessons WHERE course_id = ?');
        $stmt->execute([$courseId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $videoId = getVideoIdFromUrl($row['video_url']);
            if ($videoId) {
                $existingLessons[$videoId] = true;
            }
        }

        // إضافة الدروس الجديدة
        $stmt = $db->prepare('
            INSERT INTO lessons (
                title, video_url, duration, course_id,
                thumbnail, status_id, tags, transcript,
                created_at
            ) VALUES (
                :title, :video_url, :duration, :course_id,
                :thumbnail, :status_id, :tags, :transcript,
                CURRENT_TIMESTAMP
            )
        ');

        $db->beginTransaction();

        foreach ($playlistItems['items'] as $index => $item) {
            try {
                $videoId = $item['snippet']['resourceId']['videoId'];
                
                // تخطي الفيديوهات المكررة في نفس المعالجة
                if (in_array($videoId, $addedVideoIds)) {
                    continue;
                }
                $addedVideoIds[] = $videoId;
                
                // تخطي الفيديوهات الموجودة مسبقاً في قاعدة البيانات
                if (isset($existingLessons[$videoId])) {
                    $processedVideos++;
                    continue;
                }

                // جلب تفاصيل الفيديو
                $videoDetails = getVideoDetails($videoId, YOUTUBE_API_KEY);
                
                // إضافة الدرس
                $stmt->execute([
                    ':title' => $videoDetails['title'],
                    ':video_url' => $videoDetails['video_url'],
                    ':duration' => $videoDetails['duration'],
                    ':course_id' => $courseId,
                    ':thumbnail' => $videoDetails['thumbnail'],
                    ':status_id' => 1, // الحالة الافتراضية
                    ':tags' => json_encode($videoDetails['tags']),
                    ':transcript' => '' // يمكن إضافة النص المكتوب لاحقاً
                ]);

                $processedVideos++;

                // تحديث التقدم
                saveProgress($courseId, [
                    'status' => 'processing',
                    'progress' => ($processedVideos / $totalVideos) * 100,
                    'current' => $processedVideos,
                    'total' => $totalVideos,
                    'latest_lesson' => $videoDetails['title'],
                    'message' => "جاري معالجة الدرس {$processedVideos} من {$totalVideos}",
                    'elapsed_time' => microtime(true) - $startTime
                ]);

                // تأخير لتجنب تجاوز حد API
                usleep(500000); // 0.5 ثانية

            } catch (Exception $e) {
                error_log("Error processing video {$videoId}: " . $e->getMessage());
                continue; // الاستمرار مع الفيديو التالي
            }
        }

        $db->commit();

        // تحديث حالة الاكتمال
        $db->prepare('UPDATE courses SET processing_status = ?, duration = ? WHERE id = ?')
           ->execute(['completed', array_sum(array_column($addedVideoIds, 'duration')), $courseId]);

        saveProgress($courseId, [
            'status' => 'completed',
            'progress' => 100,
            'current' => $processedVideos,
            'total' => $totalVideos,
            'latest_lesson' => 'تم الانتهاء',
            'message' => "تم إضافة {$processedVideos} درس بنجاح",
            'elapsed_time' => microtime(true) - $startTime
        ]);

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        
        error_log("Error processing course {$courseId}: " . $e->getMessage());
        
        $db->prepare('UPDATE courses SET processing_status = ? WHERE id = ?')
           ->execute(['error', $courseId]);

        saveProgress($courseId, [
            'status' => 'error',
            'progress' => 0,
            'current' => $processedVideos,
            'total' => $totalVideos,
            'latest_lesson' => '',
            'message' => 'حدث خطأ: ' . $e->getMessage(),
            'elapsed_time' => microtime(true) - $startTime
        ]);
    }
}

/**
 * دالة لحفظ حالة التقدم
 */
function saveProgress($courseId, $progress) {
    $progressFile = TEMP_DIR . '/course_' . $courseId . '_progress.json';
    return file_put_contents($progressFile, json_encode($progress));
}

// تحديث دالة قراءة التقدم
function getProgress($courseId) {
    $progressFile = TEMP_DIR . '/course_' . $courseId . '_progress.json';
    if (file_exists($progressFile)) {
        return json_decode(file_get_contents($progressFile), true);
    }
    return null;
}

// تحديث دالة حذف ملف التقدم
function deleteProgress($courseId) {
    $progressFile = TEMP_DIR . '/course_' . $courseId . '_progress.json';
    if (file_exists($progressFile)) {
        unlink($progressFile);
    }
}

/**
 * دالة لاستخراج معرف الفيديو من رابط YouTube
 * @param string $url رابط الفيديو
 * @return string|null معرف الفيديو أو null إذا لم يتم العثور عليه
 */
function getVideoIdFromUrl($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    return null;
} 