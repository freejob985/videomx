<?php
/**
 * دالة الاتصال بقاعدة البيانات
 * 
 * @return PDO كائن PDO للاتصال بقاعدة البيانات
 * @throws PDOException في حالة فشل الاتصال
 */
function connectDB() {
    static $db = null;
    
    if ($db === null) {
        try {
            $config = require __DIR__ . '/../config/database.php';
            if (!is_array($config)) {
                throw new Exception('خطأ في تحميل ملف الإعدادات');
            }
            
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                $config['host'],
                $config['dbname'],
                $config['charset']
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}"
            ];
            
            $db = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            error_log("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
            throw new Exception("فشل الاتصال بقاعدة البيانات");
        }
    }
    
    return $db;
}

/**
 * دالة للحصول على اتصال PDO
 * 
 * @return PDO كائن PDO للاتصال بقاعدة البيانات
 */
function getPDO() {
    return connectDB();
}

// دالة جلب إحصائيات اللغات
function getLanguagesStats() {
    $db = connectDB();
    $query = "
        SELECT 
            l.id,
            l.name,
            COUNT(DISTINCT c.id) as courses_count,
            COUNT(DISTINCT les.id) as lessons_count,
            SUM(les.duration) as total_duration
        FROM languages l
        LEFT JOIN courses c ON c.language_id = l.id
        LEFT JOIN lessons les ON les.course_id = c.id
        GROUP BY l.id, l.name
    ";
    return $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

// دالة جلب الكورسات حسب اللغة
function getCoursesByLanguage($language_id) {
    $db = connectDB();
    $stmt = $db->prepare("
        SELECT 
            c.*,
            COUNT(l.id) as lessons_count,
            SUM(l.duration) as total_duration
        FROM courses c
        LEFT JOIN lessons l ON l.course_id = c.id
        WHERE c.language_id = ?
        GROUP BY c.id
    ");
    $stmt->execute([$language_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * دالة جلب الدروس الخاصة بكورس معين مع دعم الترقيم
 * 
 * @param int $course_id معرف الكورس
 * @param int $page رقم الصفحة الحالية
 * @param int $perPage عدد العناصر في الصفحة
 * @return array مصفوفة تحتوي على الدروس والمعلومات المرتبطة
 */
function getLessonsByCourse($course_id, $page = 1, $perPage = 10) {
    try {
        $db = connectDB();
        
        // حساب إجمالي عدد الدروس
        $countQuery = "SELECT COUNT(*) FROM lessons WHERE course_id = :course_id";
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute(['course_id' => $course_id]);
        $totalLessons = $countStmt->fetchColumn();
        
        // حساب عدد الصفحات
        $totalPages = ceil($totalLessons / $perPage);
        
        // حساب الإزاحة للصفحة الحالية
        $offset = ($page - 1) * $perPage;
        
        // استعلام جلب الدروس مع معلوماتها الكاملة
        $query = "SELECT 
            l.*,
            s.name as status_name,
            s.color as status_color,
            s.text_color as status_text_color
        FROM lessons l
        LEFT JOIN statuses s ON l.status_id = s.id
        WHERE l.course_id = :course_id
        ORDER BY l.order_number ASC, l.id ASC
        LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // معالجة البيانات وتنسيقها
        foreach ($lessons as &$lesson) {
            // تنسيق المدة الزمنية
            $lesson['formatted_duration'] = formatDuration($lesson['duration']);
            
            // معالجة التاجات
            if (!empty($lesson['tags'])) {
                $lesson['tags_array'] = array_map('trim', explode(',', $lesson['tags']));
            } else {
                $lesson['tags_array'] = [];
            }
            
            // التحقق من وجود صورة مصغرة
            if (empty($lesson['thumbnail'])) {
                $lesson['thumbnail'] = '../assets/images/default-lesson.jpg';
            }
            
            // تحويل القيم المنطقية
            $lesson['is_important'] = (bool)$lesson['is_important'];
            $lesson['is_theory'] = (bool)$lesson['is_theory'];
            $lesson['completed'] = (bool)$lesson['completed'];
        }
        
        return [
            'success' => true,
            'lessons' => $lessons,
            'total_lessons' => $totalLessons,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'per_page' => $perPage
        ];
        
    } catch (Exception $e) {
        error_log("Error in getLessonsByCourse: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'حدث خطأ أثناء جلب الدروس',
            'lessons' => [],
            'total_lessons' => 0,
            'total_pages' => 1,
            'current_page' => 1,
            'per_page' => $perPage
        ];
    }
}

/**
 * دالة لجلب اللغات مع الترقيم
 * 
 * @param int $page رقم الصفحة الحالية
 * @param int $perPage عدد العناصر في كل صفحة
 * @return array مصفوفة تحتوي على اللغات مع إحصائياتها
 */
function getLanguagesPaginated($page = 1, $perPage = 12) {
    $db = connectDB();
    $offset = ($page - 1) * $perPage;
    
    $query = "
        SELECT 
            l.*,
            COUNT(DISTINCT c.id) as courses_count,
            COUNT(DISTINCT les.id) as lessons_count,
            SUM(COALESCE(les.duration, 0)) as total_duration
        FROM languages l
        LEFT JOIN courses c ON c.language_id = l.id
        LEFT JOIN lessons les ON les.course_id = c.id
        GROUP BY l.id, l.name
        ORDER BY l.id ASC
        LIMIT :limit
        OFFSET :offset
    ";
    
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * دالة لجلب عدد اللغات الكلي
 * 
 * @return int عدد اللغات الكلي
 */
function getTotalLanguagesCount() {
    $db = connectDB();
    $query = "SELECT COUNT(*) as total FROM languages";
    $result = $db->query($query)->fetch(PDO::FETCH_ASSOC);
    return (int) $result['total'];
}

/**
 * دالة للتحقق من وجود لغة معينة
 * 
 * @param int $language_id معرف اللغة
 * @return bool هل اللغة موجودة أم لا
 */
function languageExists($language_id) {
    $db = connectDB();
    $stmt = $db->prepare("SELECT 1 FROM languages WHERE id = ? LIMIT 1");
    $stmt->execute([$language_id]);
    return (bool) $stmt->fetch();
}

/**
 * دالة لجلب معلومات لغة معينة
 * 
 * @param int $language_id معرف اللغة
 * @return array|null معلومات اللغة أو null إذا لم تكن موجودة
 */
function getLanguageInfo($language_id) {
    $db = connectDB();
    $stmt = $db->prepare("SELECT * FROM languages WHERE id = ?");
    $stmt->execute([$language_id]);
    return $stmt->fetch();
}

/**
 * دالة لحذف لغة وجميع الكورسات والدروس المرتبطة بها
 * 
 * @param int $language_id معرف اللغة المراد حذفها
 * @return bool نجاح أو فشل العملية
 */
function deleteLanguage($language_id) {
    $db = connectDB();
    try {
        $db->beginTransaction();

        // حذف الدروس المرتبطة بالكورسات التابعة للغة
        $stmt = $db->prepare("
            DELETE lessons FROM lessons 
            INNER JOIN courses ON lessons.course_id = courses.id 
            WHERE courses.language_id = ?
        ");
        $stmt->execute([$language_id]);

        // حذف الكورسات المرتبطة باللغة
        $stmt = $db->prepare("DELETE FROM courses WHERE language_id = ?");
        $stmt->execute([$language_id]);

        // حذف اللغة نفسها
        $stmt = $db->prepare("DELETE FROM languages WHERE id = ?");
        $stmt->execute([$language_id]);

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log($e->getMessage());
        return false;
    }
}

/**
 * دالة لجلب إحصائيات عامة عن المنصة
 * 
 * @return array مصفوفة تحتوي على الإحصائيات العامة
 */
function getPlatformStats() {
    $db = connectDB();
    $query = "
        SELECT 
            COUNT(DISTINCT l.id) as total_languages,
            COUNT(DISTINCT c.id) as total_courses,
            COUNT(DISTINCT les.id) as total_lessons,
            SUM(COALESCE(les.duration, 0)) as total_duration
        FROM languages l
        LEFT JOIN courses c ON c.language_id = l.id
        LEFT JOIN lessons les ON les.course_id = c.id
    ";
    return $db->query($query)->fetch(PDO::FETCH_ASSOC);
}

/**
 * دالة للبحث في اللغات
 * 
 * @param string $search كلمة البحث
 * @param int $page رقم الصفحة
 * @param int $perPage عدد العناصر في الصفحة
 * @return array مصفوفة تحتوي على نتائج البحث والعدد الكلي
 */
function searchLanguages($search = '', $page = 1, $perPage = 12) {
    $db = connectDB();
    $offset = ($page - 1) * $perPage;
    $search = "%$search%";
    
    // جلب العدد الكلي للنتائج
    $countStmt = $db->prepare("
        SELECT COUNT(DISTINCT l.id) as total
        FROM languages l
        WHERE l.name LIKE :search
    ");
    $countStmt->execute(['search' => $search]);
    $total = $countStmt->fetch()['total'];
    
    // جلب النتائج مع الإحصائيات
    $stmt = $db->prepare("
        SELECT 
            l.*,
            COUNT(DISTINCT c.id) as courses_count,
            COUNT(DISTINCT les.id) as lessons_count,
            SUM(COALESCE(les.duration, 0)) as total_duration
        FROM languages l
        LEFT JOIN courses c ON c.language_id = l.id
        LEFT JOIN lessons les ON les.course_id = c.id
        WHERE l.name LIKE :search
        GROUP BY l.id, l.name
        ORDER BY l.name ASC
        LIMIT :limit
        OFFSET :offset
    ");
    
    $stmt->bindValue(':search', $search, PDO::PARAM_STR);
    $stmt->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return [
        'total' => $total,
        'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

/**
 * تنسيق الوقت من الثواني إلى صيغة مقروءة
 * 
 * المدخلات:
 * @param int $seconds عدد الثواني
 * @param string $format نوع التنسيق ('time' للوقت، 'text' للنص)
 * @param bool $showSeconds إظهار الثواني (اختياري، افتراضياً true)
 * 
 * المخرجات:
 * @return string المدة المنسقة
 * 
 * أمثلة:
 * formatDuration(3600, 'time') => "1:00:00"
 * formatDuration(3600, 'text') => "1 ساعة"
 * formatDuration(3660, 'time') => "1:01:00"
 * formatDuration(3660, 'text') => "1 ساعة و 1 دقيقة"
 */
function formatDuration($seconds, $format = 'time', $showSeconds = true) {
    if (!$seconds) return $format === 'time' ? '00:00:00' : '';
    
    // تحويل الثواني إلى ساعات ودقائق وثواني
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($format === 'time') {
        // تنسيق الوقت (HH:MM:SS أو MM:SS)
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        } else {
            return sprintf('%02d:%02d', $minutes, $secs);
        }
    } else {
        // تنسيق نصي
        $formatted = '';
        if ($hours > 0) {
            $formatted .= $hours . ' ساعة ';
        }
        if ($minutes > 0 || $hours === 0) {
            $formatted .= $minutes . ' دقيقة ';
        }
        if ($showSeconds && $secs > 0) {
            $formatted .= 'و ' . $secs . ' ثانية';
        }
        return trim($formatted);
    }
}

/**
 * تنسيق الوقت للعرض في الإحصائيات
 * 
 * المدخلات:
 * @param int $seconds عدد الثواني
 * 
 * المخرجات:
 * @return string الوقت المنسق مع النص (مثال: "ساعة و 30 دقيقة")
 */
function formatDurationStats($seconds) {
    if (!$seconds) return '0 دقيقة';
    
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    $formatted = '';
    if ($hours > 0) {
        $formatted .= $hours . ' ساعة ';
        if ($minutes > 0) {
            $formatted .= 'و ';
        }
    }
    if ($minutes > 0 || $hours === 0) {
        $formatted .= $minutes . ' دقيقة';
    }
    
    return trim($formatted);
}

/**
 * دالة لجلب إحصائيات الكورس
 * 
 * @param int $language_id معرف اللغة
 * @return array إحصائيات الكورس والحالات
 */
function getCourseStats($language_id) {
    $db = connectDB();
    
    // جلب إحصائيات الحالات
    $stmt = $db->prepare("
        SELECT 
            s.name as status_name,
            COUNT(l.id) as lessons_count,
            SUM(l.duration) as total_duration
        FROM statuses s
        LEFT JOIN lessons l ON l.status_id = s.id
        LEFT JOIN courses c ON l.course_id = c.id
        WHERE c.language_id = ?
        GROUP BY s.id, s.name
    ");
    $stmt->execute([$language_id]);
    $statusStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // جلب إحصائيات عامة
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT c.id) as total_courses,
            COUNT(l.id) as total_lessons,
            SUM(l.duration) as total_duration
        FROM courses c
        LEFT JOIN lessons l ON l.course_id = c.id
        WHERE c.language_id = ?
    ");
    $stmt->execute([$language_id]);
    $generalStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'status_stats' => $statusStats,
        'general_stats' => $generalStats
    ];
}

/**
 * دالة لجلب معلومات الكورس
 * 
 * @param int $course_id معرف الكورس
 * @return array|null معلومات الكورس أو null إذا لم يكن موجوداً
 */
function getCourseInfo($course_id) {
    $db = connectDB();
    $stmt = $db->prepare("
        SELECT 
            c.*,
            COUNT(l.id) as lessons_count,
            SUM(l.duration) as total_duration
        FROM courses c
        LEFT JOIN lessons l ON l.course_id = c.id
        WHERE c.id = ?
        GROUP BY c.id
    ");
    $stmt->execute([$course_id]);
    return $stmt->fetch();
}

/**
 * دالة لجلب اسم الحالة
 * 
 * @param int $status_id معرف الحالة
 * @return string اسم الحالة
 */
function getStatusName($status_id) {
    $db = connectDB();
    $stmt = $db->prepare("SELECT name FROM statuses WHERE id = ?");
    $stmt->execute([$status_id]);
    $result = $stmt->fetch();
    return $result ? $result['name'] : 'غير معروف';
}

/**
 * دالة لجلب نص الدرس
 * 
 * @param int $lesson_id معرف الدرس
 * @return string|null نص الدرس أو null إذا لم يكن موجوداً
 */
function getLessonTranscript($lesson_id) {
    $db = connectDB();
    $stmt = $db->prepare("SELECT transcript FROM lessons WHERE id = ?");
    $stmt->execute([$lesson_id]);
    $result = $stmt->fetch();
    return $result ? $result['transcript'] : null;
}

/**
 * جلب الحالات حسب اللغة
 * @param int $language_id معرف اللغة
 * @return array قائمة الحالات
 */
function getStatusesByLanguage($language_id) {
    $db = connectDB();
    try {
        $stmt = $db->prepare("
            SELECT s.* 
            FROM statuses s
            WHERE s.language_id = ?
            ORDER BY s.name ASC
        ");
        $stmt->execute([$language_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * جلب الأقسام حسب اللغة
 * @param int $language_id معرف اللغة
 * @return array قائمة الأقسام
 */
function getSectionsByLanguage($language_id) {
    $db = connectDB();
    try {
        $stmt = $db->prepare("
            SELECT * FROM sections 
            WHERE language_id = ? 
            ORDER BY name ASC
        ");
        $stmt->execute([$language_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * تحديث حالة الدرس
 * @param int $lesson_id معرف الدرس
 * @param int $status_id معرف الحالة الجديدة
 * @return array نتيجة التحديث
 */
function updateLessonStatus($lesson_id, $status_id) {
    try {
        $db = connectDB();
        
        // تحديث الحالة
        $stmt = $db->prepare("UPDATE lessons SET status_id = ? WHERE id = ?");
        $stmt->execute([$status_id, $lesson_id]);
        
        // جلب معلومات الحالة الجديدة
        $stmt = $db->prepare("SELECT * FROM statuses WHERE id = ?");
        $stmt->execute([$status_id]);
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'status' => $status
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ];
    }
}

/**
 * دالة لجلب تفاصيل الكورس والدروس بتنسيق منظم
 * 
 * @param int $course_id معرف الكورس
 * @param string $type نوع التنسيق (details, titles, all)
 * @return array مصفوفة تحتوي على النص المنسق والبيانات
 */
function getFormattedCourseDetails($course_id, $type = 'all') {
    $db = connectDB();
    
    // جلب معلومات الكورس
    $course = getCourseInfo($course_id);
    $lessons = getLessonsByCourse($course_id);
    $language = getLanguageInfo($course['language_id']);
    
    $text = '';
    
    // تنسيق التفاصيل
    if ($type === 'all' || $type === 'details') {
        $text .= "📚 {$course['title']}\n";
        $text .= "🔤 {$language['name']}\n";
        $text .= "📝 {$course['description']}\n\n";
        
        // إضافة الإحصائيات
        $totalDuration = array_sum(array_column($lessons['lessons'], 'duration'));
        $text .= "📊 إحصائيات الكورس:\n";
        $text .= "- عدد الدروس: " . count($lessons['lessons']) . "\n";
        $text .= "- المدة الإجمالية: " . formatDuration($totalDuration) . "\n";
        if ($course['playlist_url']) {
            $text .= "- قائمة التشغيل: {$course['playlist_url']}\n";
        }
    }
    
    // إضافة قائمة أسماء الدروس مع التاجات والملاحظات
    if ($type === 'all' || $type === 'titles') {
        if ($type === 'all') {
            $text .= "\n=============================\n\n";
        }

        foreach ($lessons['lessons'] as $index => $lesson) {
            $number = $index + 1;
            
            // خط فاصل في البداية
            $text .= str_repeat("-", 30) . "\n";
            
            // عنوان الدرس
            $text .= "الدرس\n";
            $text .= str_repeat("*", 15) . "\n";
            $text .= "{$number}. {$lesson['title']}\n\n";
            
            // التاجات
            $text .= "التاجات\n";
            $text .= str_repeat("*", 15) . "\n";
            if (!empty($lesson['tags_array'])) {
                $tags = array_map('trim', $lesson['tags_array']);
                $text .= implode(", ", $tags) . "\n\n";
            } else {
                $text .= "لا توجد تاجات\n\n";
            }
            
            // الملاحظات
            $text .= "الملاحظات\n";
            $text .= str_repeat("*", 15) . "\n";
            $notes = getTextNotes($lesson['id']);
            if (!empty($notes)) {
                foreach ($notes as $note) {
                    $text .= "- " . strip_tags($note['content']) . "\n";
                }
                $text .= "\n";
            } else {
                $text .= "لا توجد ملاحظات\n\n";
            }
            
            // خط فاصل في النهاية
            $text .= str_repeat("-", 30) . "\n";
        }
    }
    
    return [
        'text' => $text,
        'course' => $course,
        'lessons' => $lessons['lessons']
    ];
}

/**
 * دالة مساعدة لجلب الملاحظات النصية للدرس
 * 
 * @param int $lesson_id معرف الدرس
 * @return array مصفوفة تحتوي على الملاحظات النصية
 */
function getTextNotes($lesson_id) {
    $db = connectDB();
    try {
        $stmt = $db->prepare("
            SELECT title, content 
            FROM notes 
            WHERE lesson_id = ? 
            AND type = 'text'
            ORDER BY created_at ASC
        ");
        $stmt->execute([$lesson_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * دالة لتحديث لون الحالة
 * 
 * @param int $status_id معرف الحالة
 * @param string $color اللون بصيغة HEX
 * @return bool نجاح أو فشل العملية
 */
function updateStatusColor($status_id, $color) {
    $db = connectDB();
    try {
        $stmt = $db->prepare("
            UPDATE statuses 
            SET color = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$color, $status_id]);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * دالة لجلب إحصائيات الدروس المحدثة
 * 
 * @param int $course_id معرف الكورس
 * @return array إحصائيات الدروس
 */
function getUpdatedLessonsStats($course_id) {
    $db = connectDB();
    $stmt = $db->prepare("
        SELECT 
            s.id as status_id,
            s.name as status_name,
            s.color as status_color,
            COUNT(l.id) as lessons_count,
            SUM(l.duration) as total_duration
        FROM statuses s
        LEFT JOIN lessons l ON l.status_id = s.id
        WHERE l.course_id = ?
        GROUP BY s.id, s.name, s.color
    ");
    $stmt->execute([$course_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * دالة لتحديث لون النص للحالة
 * 
 * @param int $status_id معرف الحالة
 * @param string $text_color لون النص بصيغة HEX
 * @return bool نجاح أو فشل العملية
 */
function updateStatusTextColor($status_id, $text_color) {
    $db = connectDB();
    try {
        $stmt = $db->prepare("
            UPDATE statuses 
            SET text_color = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$text_color, $status_id]);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * دالة لتحديث إحصائيات البروجرس بار
 * 
 * @param int $course_id معرف الكورس
 * @return array إحصائيات البروجرس بار
 */
function getProgressStats($course_id) {
    $db = connectDB();
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_lessons,
            SUM(CASE WHEN status_id IS NOT NULL THEN 1 ELSE 0 END) as completed_lessons,
            (SUM(CASE WHEN status_id IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) as completion_percentage
        FROM lessons 
        WHERE course_id = ?
    ");
    $stmt->execute([$course_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * دالة لجلب تفاصيل درس معين
 * @param int $lesson_id معرف الدرس
 * @return array|false مصفوفة تحتوي على تفاصيل الدرس أو false في حالة عدم وجود الدرس
 */
function getLessonDetails($lesson_id) {
    $db = connectDB();
    try {
        $stmt = $db->prepare("
            SELECT 
                l.*,
                c.title as course_title,
                c.language_id,
                s.name as status_name,
                s.color as status_color,
                s.text_color as status_text_color,
                sec.name as section_name
            FROM lessons l
            LEFT JOIN courses c ON l.course_id = c.id
            LEFT JOIN statuses s ON l.status_id = s.id
            LEFT JOIN sections sec ON l.section_id = sec.id
            WHERE l.id = ?
        ");
        $stmt->execute([$lesson_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * تبديل حالة أهمية الدرس
 * @param int $lesson_id معرف الدرس
 * @return array نتيجة العملية
 */
function toggleLessonImportance($lesson_id) {
    try {
        $db = connectDB();
        
        // التحقق من وجود الدرس
        $stmt = $db->prepare("SELECT is_important FROM lessons WHERE id = ?");
        $stmt->execute([$lesson_id]);
        $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$lesson) {
            return [
                'success' => false,
                'error' => 'الدرس غير موجود'
            ];
        }
        
        // تبديل الحالة
        $stmt = $db->prepare("
            UPDATE lessons 
            SET is_important = NOT is_important,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $result = $stmt->execute([$lesson_id]);
        
        return [
            'success' => $result,
            'is_important' => !$lesson['is_important']
        ];
    } catch (PDOException $e) {
        error_log("Error toggling lesson importance: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'حدث خطأ أثناء تحديث حالة الدرس'
        ];
    }
}

/**
 * تبديل حالة نظرية الدرس
 * @param int $lesson_id معرف الدرس
 * @return array نتيجة العملية
 */
function toggleLessonTheory($lesson_id) {
    try {
        $db = connectDB();
        
        // التحقق من وجود الدرس
        $stmt = $db->prepare("SELECT is_theory FROM lessons WHERE id = ?");
        $stmt->execute([$lesson_id]);
        $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$lesson) {
            return [
                'success' => false,
                'error' => 'الدرس غير موجود'
            ];
        }
        
        // تبديل الحالة
        $stmt = $db->prepare("
            UPDATE lessons 
            SET is_theory = NOT is_theory,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $result = $stmt->execute([$lesson_id]);
        
        return [
            'success' => $result,
            'is_theory' => !$lesson['is_theory']
        ];
    } catch (PDOException $e) {
        error_log("Error toggling lesson theory: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'حدث خطأ أثناء تحديث حالة الدرس'
        ];
    }
}

/**
 * دالة لحذف درس
 * 
 * @param int $lesson_id معرف الدرس
 * @return bool نجاح أو فشل العملية
 */
function deleteLesson($lesson_id) {
    $db = connectDB();
    try {
        $stmt = $db->prepare("DELETE FROM lessons WHERE id = ?");
        return $stmt->execute([$lesson_id]);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * تحديث حالة اكتمال الدرس
 * @param int $lesson_id معرف الدرس
 * @param int $status_id معرف الحالة
 * @return bool نجاح أو فشل العملية
 */
function updateLessonCompletion($lesson_id, $status_id) {
    $db = connectDB();
    try {
        // التحقق من أن الحالة تشير إلى الاكتمال
        $stmt = $db->prepare("
            SELECT name 
            FROM statuses 
            WHERE id = ? AND (name LIKE '%مكتمل%' OR name LIKE '%منتهي%')
        ");
        $stmt->execute([$status_id]);
        $isCompletedStatus = (bool)$stmt->fetch();

        // تحديث حالة الدرس
        $stmt = $db->prepare("
            UPDATE lessons 
            SET status_id = ?,
                completed = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$status_id, $isCompletedStatus ? 1 : 0, $lesson_id]);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * دالة تحديث إحصائيات الكورس
 */
function updateCourseStats($lesson_id) {
    $db = connectDB();
    try {
        // جلب معرف الكورس
        $stmt = $db->prepare("SELECT course_id FROM lessons WHERE id = ?");
        $stmt->execute([$lesson_id]);
        $course_id = $stmt->fetchColumn();
        
        if ($course_id) {
            // تحديث الإحصائيات
            $stmt = $db->prepare("
                UPDATE courses 
                SET total_completed = (
                    SELECT COUNT(*) 
                    FROM lessons 
                    WHERE course_id = ? AND completed = 1
                )
                WHERE id = ?
            ");
            $stmt->execute([$course_id, $course_id]);
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}

/**
 * دالة جلب الدروس مع الفلترة
 * 
 * @param array $filters معايير الفلترة
 * @return array الدروس المفلترة
 */
function getFilteredLessons($filters) {
    $db = connectDB();
    $query = "
        SELECT 
            l.*,
            s.name as status_name,
            s.color as status_color,
            s.text_color as status_text_color
        FROM lessons l
        LEFT JOIN statuses s ON l.status_id = s.id
        WHERE l.course_id = :course_id
    ";
    
    $params = ['course_id' => $filters['course_id']];
    
    // إضافة شروط الفلترة
    if (!empty($filters['section'])) {
        $query .= " AND l.section_id = :section";
        $params['section'] = $filters['section'];
    }
    
    if (!empty($filters['status'])) {
        $query .= " AND l.status_id = :status";
        $params['status'] = $filters['status'];
    }
    
    if (!empty($filters['search'])) {
        $query .= " AND (l.title LIKE :search OR l.tags LIKE :search)";
        $params['search'] = "%{$filters['search']}%";
    }
    
    if (!empty($filters['important'])) {
        $query .= " AND l.is_important = 1";
    }
    
    if (!empty($filters['theory'])) {
        $query .= " AND l.is_theory = 1";
    } elseif (!empty($filters['hideTheory'])) {
        $query .= " AND l.is_theory = 0";
    }
    
    $query .= " ORDER BY l.id ASC";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * دالة جلب حالة الدرس
 */
function getLessonStatus($lesson_id) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("
        SELECT s.* 
        FROM lessons l
        JOIN statuses s ON s.id = l.status_id
        WHERE l.id = ?
    ");
    $stmt->execute([$lesson_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * دالة جلب معلومات الحالة
 */
function getStatusInfo($status_id) {
    $pdo = connectDB();
    $stmt = $pdo->prepare("
        SELECT id, name, color, text_color
        FROM statuses
        WHERE id = ?
    ");
    $stmt->execute([$status_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * دالة جلب إحصائيات الدرس
 */
function getLessonStats($lesson_id) {
    $pdo = connectDB();
    // يمكنك إضافة أي إحصائيات إضافية هنا
    $stmt = $pdo->prepare("
        SELECT 
            l.*,
            c.title as course_title,
            s.name as status_name,
            s.color as status_color,
            s.text_color as status_text_color
        FROM lessons l
        LEFT JOIN courses c ON l.course_id = c.id
        LEFT JOIN statuses s ON l.status_id = s.id
        WHERE l.id = ?
    ");
    $stmt->execute([$lesson_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * دالة تحديث ألوان الحالة في قاعدة البيانات
 * 
 * @param int $status_id معرف الحالة
 * @param string $color لون الخلفية
 * @param string $text_color لون النص
 * @return bool نجاح أو فشل العملية
 */
function updateStatusColors($status_id, $color, $text_color) {
    $db = connectDB();
    try {
        $stmt = $db->prepare("
            UPDATE statuses 
            SET color = ?, 
                text_color = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$color, $text_color, $status_id]);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * دالة جلب جميع الحالات المتاحة
 * 
 * @return array قائمة الحالات
 */
function getAllStatuses() {
    $db = connectDB();
    try {
        $stmt = $db->query("
            SELECT id, name, color, text_color 
            FROM statuses 
            ORDER BY id ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * جلب الدرس التالي في نفس الكورس
 * @param int $course_id معرف الكورس
 * @param int $current_lesson_id معرف الدرس الحالي
 * @return array|null معلومات الدرس التالي أو null إذا لم يوجد
 */
function getNextLesson($course_id, $current_lesson_id) {
    $db = connectDB();
    
    $sql = "SELECT * FROM lessons 
            WHERE course_id = ? 
            AND id > ? 
            ORDER BY id ASC 
            LIMIT 1";
            
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([$course_id, $current_lesson_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting next lesson: " . $e->getMessage());
        return null;
    }
}

/**
 * جلب الدرس السابق في نفس الكورس
 * @param int $course_id معرف الكورس
 * @param int $current_lesson_id معرف الدرس الحالي
 * @return array|null معلومات الدرس السابق أو null إذا لم يوجد
 */
function getPrevLesson($course_id, $current_lesson_id) {
    $db = connectDB();
    
    $sql = "SELECT * FROM lessons 
            WHERE course_id = ? 
            AND id < ? 
            ORDER BY id DESC 
            LIMIT 1";
            
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([$course_id, $current_lesson_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting previous lesson: " . $e->getMessage());
        return null;
    }
}

/**
 * جلب حالات الدروس المتاحة
 * @return array قائمة بجميع الحالات المتاحة
 */
function getStatuses() {
    $db = connectDB();
    
    try {
        $stmt = $db->query("SELECT * FROM statuses ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting statuses: " . $e->getMessage());
        return [];
    }
}

/**
 * استخراج معرف فيديو يوتيوب من الرابط
 * @param string $url رابط الفيديو
 * @return string|null معرف الفيديو أو null إذا لم يكن رابط صحيح
 */
function getYoutubeId($url) {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    return null;
}

/**
 * تحديث قسم الدرس
 * @param int $lesson_id معرف الدرس
 * @param int $section_id معرف القسم
 * @return array نتيجة العملية
 */
function updateLessonSection($lesson_id, $section_id) {
    try {
        $db = connectDB();
        
        // التحقق من وجود الدرس والقسم
        $stmt = $db->prepare("
            SELECT l.id as lesson_exists, s.id as section_exists
            FROM lessons l
            LEFT JOIN sections s ON s.id = ?
            WHERE l.id = ?
        ");
        $stmt->execute([$section_id, $lesson_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result['lesson_exists']) {
            return [
                'success' => false,
                'error' => 'الدرس غير موجود'
            ];
        }
        
        if (!$result['section_exists']) {
            return [
                'success' => false,
                'error' => 'القسم غير موجود'
            ];
        }
        
        // تحديث القسم
        $stmt = $db->prepare("
            UPDATE lessons 
            SET section_id = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $result = $stmt->execute([$section_id, $lesson_id]);
        
        return [
            'success' => $result
        ];
    } catch (PDOException $e) {
        error_log("Error updating lesson section: " . $e->getMessage());
        return [
            'success' => false,
            'error' => 'حدث خطأ أثناء تحديث قسم الدرس'
        ];
    }
}

/**
 * جلب الدروس المرتبطة بنفس القسم
 * @param int $section_id معرف القسم
 * @param int $current_lesson_id معرف الدرس الحالي (للاستثناء)
 * @return array مصفوفة تحتوي على الدروس المرتبطة
 */
function getRelatedLessonsBySection($section_id, $current_lesson_id) {
    try {
        $db = connectDB();
        $stmt = $db->prepare("
            SELECT 
                l.*,
                c.title as course_title,
                s.name as status_name,
                s.color as status_color,
                s.text_color as status_text_color,
                sec.name as section_name
            FROM lessons l
            LEFT JOIN courses c ON l.course_id = c.id
            LEFT JOIN statuses s ON l.status_id = s.id
            LEFT JOIN sections sec ON l.section_id = sec.id
            WHERE l.section_id = :section_id 
            AND l.id != :current_lesson_id
            ORDER BY l.order_number ASC, l.created_at DESC
            LIMIT 6
        ");
        
        $stmt->execute([
            ':section_id' => $section_id,
            ':current_lesson_id' => $current_lesson_id
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting related lessons: " . $e->getMessage());
        return [];
    }
}

/**
 * جلب ملاحظات الدرس
 * @param int $lesson_id معرف الدرس
 * @return array مصفوفة تحتوي على الملاحظات
 */
function getLessonNotes($lesson_id) {
    try {
        $db = connectDB();
        $stmt = $db->prepare("
            SELECT * FROM notes 
            WHERE lesson_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$lesson_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting lesson notes: " . $e->getMessage());
        return [];
    }
}

/**
 * إضافة ملاحظة جديدة
 * @param array $note بيانات الملاحظة
 * @return int|false معرف الملاحظة الجديدة أو false في حالة الفشل
 */
function addNote($note) {
    try {
        $db = connectDB();
        
        $sql = "INSERT INTO notes (lesson_id, type, title, content";
        $params = [
            ':lesson_id' => $note['lesson_id'],
            ':type' => $note['type'],
            ':title' => $note['title'],
            ':content' => $note['content']
        ];
        
        // إضافة الأعمدة الإضافية حسب النوع
        if ($note['type'] === 'code') {
            $sql .= ", code_language";
            $params[':code_language'] = $note['code_language'];
        } elseif ($note['type'] === 'link') {
            $sql .= ", link_url, link_description";
            $params[':link_url'] = $note['link_url'];
            $params[':link_description'] = $note['link_description'];
        }
        
        $sql .= ") VALUES (:lesson_id, :type, :title, :content";
        
        if ($note['type'] === 'code') {
            $sql .= ", :code_language";
        } elseif ($note['type'] === 'link') {
            $sql .= ", :link_url, :link_description";
        }
        
        $sql .= ")";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * جلب ملاحظة بواسطة المعرف
 * @param int $id معرف الملاحظة
 * @return array|false بيانات الملاحظة أو false في حالة عدم وجودها
 */
function getNoteById($id) {
    try {
        $db = connectDB();
        
        $stmt = $db->prepare("SELECT * FROM notes WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * تنسيق التاريخ بالشكل المطلوب
 * 
 * @param string $date التاريخ المراد تنسيقه
 * @return string التاريخ بعد التنسيق
 */
function formatDate($date) {
    if (!$date) return '';
    
    $timestamp = strtotime($date);
    return date('Y/m/d h:i A', $timestamp);
}

/**
 * جلب اللغات مع عدد الأقسام لكل لغة
 * 
 * @param int $page رقم الصفحة الحالية
 * @param int $perPage عدد العناصر في الصفحة
 * @return array مصفوفة تحتوي على اللغات وعدد الأقسام
 */
function getLanguagesWithSectionsTable($page = 1, $perPage = 10) {
    try {
        $db = connectDB();
        $offset = ($page - 1) * $perPage;
        
        // تعديل الاستعلام لاستخدام معرف اللغة في GROUP BY
        $query = "
            SELECT 
                l.id,
                l.name,
                l.created_at,
                l.updated_at,
                COUNT(DISTINCT s.id) as sections_count
            FROM languages l
            LEFT JOIN sections s ON l.id = s.language_id
            GROUP BY l.id, l.name, l.created_at, l.updated_at
            ORDER BY l.created_at DESC
            LIMIT ?, ?
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(1, $offset, PDO::PARAM_INT);
        $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * جلب إجمالي عدد اللغات للجدول
 * 
 * @return int عدد اللغات
 */
function getLanguagesTableCount() {
    try {
        $db = connectDB();
        $stmt = $db->query("SELECT COUNT(DISTINCT id) FROM languages");
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        error_log($e->getMessage());
        return 0;
    }
}

/**
 * التحقق من وجود بيانات في جدول اللغات
 * 
 * @return bool
 */
function hasLanguagesData() {
    try {
        $db = connectDB();
        $stmt = $db->query("SELECT EXISTS(SELECT 1 FROM languages LIMIT 1)");
        return (bool)$stmt->fetchColumn();
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * جلب اللغات مع الإحصائيات الكاملة
 * 
 * @param int $page رقم الصفحة
 * @param int $perPage عدد العناصر في الصفحة
 * @return array مصفوفة تحتوي على اللغات وإحصائياتها
 */
function getLanguagesWithStats($page = 1, $perPage = 12) {
    try {
        $db = connectDB();
        $offset = ($page - 1) * $perPage;
        
        // استعلام جلب اللغات مع الإحصائيات
        $query = "
            SELECT 
                l.*,
                COUNT(DISTINCT s.id) as sections_count,
                COUNT(DISTINCT c.id) as courses_count,
                COUNT(DISTINCT les.id) as lessons_count,
                SUM(COALESCE(les.duration, 0)) as total_duration
            FROM languages l
            LEFT JOIN sections s ON l.id = s.language_id
            LEFT JOIN courses c ON l.id = c.language_id
            LEFT JOIN lessons les ON c.id = les.course_id
            GROUP BY l.id, l.name, l.created_at, l.updated_at
            ORDER BY l.created_at DESC
            LIMIT ?, ?
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(1, $offset, PDO::PARAM_INT);
        $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // جلب الأقسام لكل لغة
        foreach ($languages as &$language) {
            $language['sections'] = getSectionsForLanguageTable($language['id']);
        }
        
        return $languages;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * جلب إحصائيات الدروس للكورس
 * @param int $course_id معرف الكورس
 * @return array مصفوفة تحتوي على الإحصائيات
 */
function getCourseLessonsStats($course_id) {
    try {
        $db = connectDB();
        
        // استعلام محسن لجلب جميع الإحصائيات في عملية واحدة
        $stmt = $db->prepare("
            SELECT 
                (SELECT COUNT(*) FROM lessons WHERE course_id = :course_id) as total_lessons,
                (SELECT COUNT(*) FROM lessons WHERE course_id = :course_id AND completed = 1) as completed_lessons,
                (SELECT COUNT(*) FROM lessons WHERE course_id = :course_id AND (completed = 0 OR completed IS NULL)) as remaining_lessons,
                (SELECT COUNT(*) FROM lessons WHERE course_id = :course_id AND is_important = 1) as important_lessons,
                (SELECT COUNT(*) FROM lessons WHERE course_id = :course_id AND is_theory = 1) as theory_lessons,
                (SELECT SUM(duration) FROM lessons WHERE course_id = :course_id) as total_duration,
                (SELECT SUM(duration) FROM lessons WHERE course_id = :course_id AND completed = 1) as completed_duration,
                (SELECT SUM(duration) FROM lessons WHERE course_id = :course_id AND (completed = 0 OR completed IS NULL)) as remaining_duration
        ");
        
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // التأكد من أن جميع القيم رقمية
        return array_map(function($value) {
            return $value === null ? 0 : (int)$value;
        }, $stats);
        
    } catch (PDOException $e) {
        error_log("Error getting course lessons stats: " . $e->getMessage());
        return [
            'total_lessons' => 0,
            'completed_lessons' => 0,
            'remaining_lessons' => 0,
            'important_lessons' => 0,
            'theory_lessons' => 0,
            'total_duration' => 0,
            'completed_duration' => 0,
            'remaining_duration' => 0
        ];
    }
}

/**
 * دالة لإنشاء الروابط الصحيحة
 * @param string $path المسار المطلوب
 * @return string الرابط الكامل
 */
function buildUrl($path) {
    $config = require __DIR__ . '/../config/app.php';
    return $config['base_url'] . $config['content_path'] . '/' . ltrim($path, '/');
}

/**
 * جلب معلومات لغة محددة بواسطة المعرف
 * 
 * @param int $id معرف اللغة
 * @return array|false مصفوفة تحتوي على معلومات اللغة أو false في حالة عدم وجودها
 */
function getLanguageById($id) {
    try {
        $db = connectDB();
        
        $stmt = $db->prepare("
            SELECT l.*, 
                   COUNT(DISTINCT s.id) as sections_count,
                   COUNT(DISTINCT c.id) as courses_count,
                   COUNT(DISTINCT les.id) as lessons_count,
                   COALESCE(SUM(les.duration), 0) as total_duration
            FROM languages l
            LEFT JOIN sections s ON l.id = s.language_id
            LEFT JOIN courses c ON l.id = c.language_id
            LEFT JOIN lessons les ON c.id = les.course_id
            WHERE l.id = ?
            GROUP BY l.id
        ");
        
        $stmt->execute([$id]);
        $language = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($language) {
            // جلب الأقسام المرتبطة باللغة
            $sectionsStmt = $db->prepare("
                SELECT id, name 
                FROM sections 
                WHERE language_id = ? 
                ORDER BY name ASC
            ");
            $sectionsStmt->execute([$id]);
            $language['sections'] = $sectionsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $language;
        }
        
        return false;
        
    } catch (PDOException $e) {
        error_log("Database error in getLanguageById: " . $e->getMessage());
        return false;
    }
}

/**
 * جلب أقسام لغة معينة للعرض في الجدول
 * 
 * @param int $language_id معرف اللغة
 * @return array مصفوفة تحتوي على الأقسام
 */
function getSectionsForLanguageTable($language_id) {
    try {
        $db = connectDB();
        
        $stmt = $db->prepare("
            SELECT id, name, created_at, updated_at
            FROM sections
            WHERE language_id = ?
            ORDER BY created_at DESC
        ");
        
        $stmt->execute([$language_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * إضافة قسم جديد للغة
 * 
 * @param int $languageId معرف اللغة
 * @param string $name اسم القسم
 * @return int|false معرف القسم الجديد أو false في حالة الفشل
 */
function addSection($languageId, $name) {
    try {
        $db = connectDB();
        
        // التحقق من عدم وجود قسم بنفس الاسم للغة
        $checkStmt = $db->prepare("
            SELECT COUNT(*) 
            FROM sections 
            WHERE language_id = ? AND name = ?
        ");
        $checkStmt->execute([$languageId, $name]);
        
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception("يوجد قسم بنفس الاسم بالفعل");
        }
        
        // إضافة القسم الجديد
        $stmt = $db->prepare("
            INSERT INTO sections (language_id, name, created_at) 
            VALUES (?, ?, NOW())
        ");
        
        if ($stmt->execute([$languageId, $name])) {
            return $db->lastInsertId();
        }
        
        return false;
        
    } catch (PDOException $e) {
        error_log("Database error in addSection: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Error in addSection: " . $e->getMessage());
        return false;
    }
}

/**
 * حذف قسم
 * 
 * @param int $section_id معرف القسم
 * @return bool نجاح أو فشل العملية
 */
function deleteSection($section_id) {
    try {
        $db = connectDB();
        
        // بدء المعاملة
        $db->beginTransaction();
        
        // تحديث الدروس المرتبطة بالقسم لجعل section_id = NULL
        $stmt = $db->prepare("
            UPDATE lessons 
            SET section_id = NULL,
                updated_at = NOW()
            WHERE section_id = ?
        ");
        $stmt->execute([$section_id]);
        
        // حذف القسم
        $stmt = $db->prepare("
            DELETE FROM sections 
            WHERE id = ?
        ");
        $result = $stmt->execute([$section_id]);
        
        // إتمام المعاملة
        $db->commit();
        
        return $result;
        
    } catch (Exception $e) {
        // التراجع عن التغييرات في حالة حدوث خطأ
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Error deleting section: " . $e->getMessage());
        return false;
    }
}

/**
 * الحصول على جميع دروس الكورس
 * @param int $course_id معرف الكورس
 * @return array مصفوفة تحتوي على جميع الدروس
 */
function getAllLessonsByCourse($course_id) {
    try {
        $db = connectDB();
        
        // استعلام محسن لجلب جميع الدروس
        $sql = "
            SELECT l.*, 
                   s.name as status_name,
                   s.color as status_color,
                   s.text_color as status_text_color
            FROM lessons l
            LEFT JOIN statuses s ON l.status_id = s.id
            WHERE l.course_id = :course_id 
            ORDER BY l.id ASC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error in getAllLessonsByCourse: " . $e->getMessage());
        return [];
    }
}

/**
 * جلب إحصائيات الدروس للصفحة الحالية
 * 
 * @param int $course_id معرف الكورس
 * @param int $offset بداية الصفحة
 * @param int $limit عدد العناصر في الصفحة
 * @return array مصفوفة تحتوي على إحصائيات الدروس
 */
function getPageLessonsStats($course_id, $offset, $limit) {
    try {
        $db = connectDB();
        
        // أولاً: جلب معلومات الكورس للحصول على language_id
        $courseQuery = "SELECT language_id FROM courses WHERE id = :course_id";
        $stmt = $db->prepare($courseQuery);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->execute();
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$course) {
            throw new Exception("Course not found");
        }

        // ثانياً: جلب جميع الحالات المتاحة للغة
        $statusesQuery = "SELECT id FROM statuses WHERE language_id = :language_id";
        $stmt = $db->prepare($statusesQuery);
        $stmt->bindValue(':language_id', $course['language_id'], PDO::PARAM_INT);
        $stmt->execute();
        $availableStatusIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // ثالثاً: جلب معرفات الدروس في الصفحة الحالية
        $pageIdsQuery = "SELECT id FROM lessons 
                        WHERE course_id = :course_id 
                        ORDER BY id ASC 
                        LIMIT :offset, :limit";
        
        $stmt = $db->prepare($pageIdsQuery);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $lessonIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $stats = [
            'total_count' => 0,
            'completed_count' => 0,
            'remaining_count' => 0,
            'important_count' => 0,
            'theory_count' => 0,
            'completed_duration' => 0,
            'remaining_duration' => 0,
            'no_status_count' => 0,
            'available_statuses' => count($availableStatusIds)
        ];
        
        if (!empty($lessonIds)) {
            // رابعاً: جلب عدد الدروس بدون حالة
            $noStatusQuery = "SELECT COUNT(*) as count 
                            FROM lessons 
                            WHERE id IN (" . implode(',', $lessonIds) . ")
                            AND (status_id IS NULL 
                                OR status_id = 0" .
                                (count($availableStatusIds) > 0 ? 
                                " OR status_id NOT IN (" . implode(',', $availableStatusIds) . ")" 
                                : "") . ")";
            
            $stmt = $db->query($noStatusQuery);
            $noStatusCount = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $stats['no_status_count'] = $noStatusCount;
            
            // خامساً: جلب باقي الإحصائيات
            $query = "SELECT 
                        COUNT(*) as total_count,
                        SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_count,
                        SUM(CASE WHEN completed = 0 OR completed IS NULL THEN 1 ELSE 0 END) as remaining_count,
                        SUM(CASE WHEN is_important = 1 THEN 1 ELSE 0 END) as important_count,
                        SUM(CASE WHEN is_theory = 1 THEN 1 ELSE 0 END) as theory_count,
                        SUM(CASE WHEN completed = 1 THEN duration ELSE 0 END) as completed_duration,
                        SUM(CASE WHEN completed = 0 OR completed IS NULL THEN duration ELSE 0 END) as remaining_duration
                      FROM lessons 
                      WHERE id IN (" . implode(',', $lessonIds) . ")";
            
            $stmt = $db->query($query);
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                foreach ($row as $key => $value) {
                    if (isset($stats[$key])) {
                        $stats[$key] = (int)$value;
                    }
                }
            }
        }
        
        // التأكد من صحة القيم
        foreach ($stats as $key => $value) {
            if ($key !== 'available_statuses') {
                $stats[$key] = max(0, $value);
            }
        }
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log("Error in getPageLessonsStats: " . $e->getMessage());
        return [
            'total_count' => 0,
            'completed_count' => 0,
            'remaining_count' => 0,
            'important_count' => 0,
            'theory_count' => 0,
            'completed_duration' => 0,
            'remaining_duration' => 0,
            'no_status_count' => 0,
            'available_statuses' => 0
        ];
    }
}

/**
 * جلب إحصائيات كامل الكورس مع التحقق من الحالات
 * 
 * @param int $course_id معرف الكورس
 * @return array مصفوفة تحتوي على إحصائيات كامل الكورس
 */
function getFullCourseStats($course_id) {
    try {
        $db = connectDB();
        
        // أولاً: جلب معلومات الكورس للحصول على language_id
        $courseQuery = "SELECT language_id FROM courses WHERE id = :course_id";
        $stmt = $db->prepare($courseQuery);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->execute();
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$course) {
            throw new Exception("Course not found");
        }

        // ثانياً: جلب جميع الحالات المتاحة للغة
        $statusesQuery = "SELECT id FROM statuses WHERE language_id = :language_id";
        $stmt = $db->prepare($statusesQuery);
        $stmt->bindValue(':language_id', $course['language_id'], PDO::PARAM_INT);
        $stmt->execute();
        $availableStatusIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // ثالثاً: جلب الدروس بدون حالة أو بحالة غير موجودة في الحالات المتاحة
        $noStatusQuery = "SELECT COUNT(*) as count 
                         FROM lessons 
                         WHERE course_id = :course_id 
                         AND (status_id IS NULL 
                             OR status_id = 0" .
                             (count($availableStatusIds) > 0 ? 
                             " OR status_id NOT IN (" . implode(',', $availableStatusIds) . ")" 
                             : "") . ")";
        
        $stmt = $db->prepare($noStatusQuery);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->execute();
        $noStatusCount = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // رابعاً: جلب باقي الإحصائيات
        $query = "SELECT 
                    COUNT(*) as total_count,
                    SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN completed = 0 OR completed IS NULL THEN 1 ELSE 0 END) as remaining_count,
                    SUM(CASE WHEN is_important = 1 THEN 1 ELSE 0 END) as important_count,
                    SUM(CASE WHEN is_theory = 1 THEN 1 ELSE 0 END) as theory_count,
                    SUM(CASE WHEN completed = 1 THEN duration ELSE 0 END) as completed_duration,
                    SUM(CASE WHEN completed = 0 OR completed IS NULL THEN duration ELSE 0 END) as remaining_duration
                  FROM lessons 
                  WHERE course_id = :course_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $stats = [
            'total_count' => 0,
            'completed_count' => 0,
            'remaining_count' => 0,
            'important_count' => 0,
            'theory_count' => 0,
            'completed_duration' => 0,
            'remaining_duration' => 0,
            'no_status_count' => $noStatusCount,
            'available_statuses' => count($availableStatusIds) // إضافة عدد الحالات المتاحة
        ];
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            foreach ($row as $key => $value) {
                if (isset($stats[$key])) {
                    $stats[$key] = (int)$value;
                }
            }
        }
        
        // التأكد من صحة القيم
        foreach ($stats as $key => $value) {
            if ($key !== 'available_statuses') {
                $stats[$key] = max(0, $value);
            }
        }
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log("Error in getFullCourseStats: " . $e->getMessage());
        return [
            'total_count' => 0,
            'completed_count' => 0,
            'remaining_count' => 0,
            'important_count' => 0,
            'theory_count' => 0,
            'completed_duration' => 0,
            'remaining_duration' => 0,
            'no_status_count' => 0,
            'available_statuses' => 0
        ];
    }
}

/**
 * تنظيف وتأمين المدخلات
 * @param string $input النص المراد تنظيفه
 * @return string النص بعد التنظيف
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * التحقق من صحة معرف الدرس
 * @param mixed $lessonId معرف الدرس
 * @return bool
 */
function isValidLessonId($lessonId) {
    return !empty($lessonId) && is_numeric($lessonId) && $lessonId > 0;
}

/**
 * التحقق من وجود الدرس
 * @param int $lessonId معرف الدرس
 * @return bool
 */
function lessonExists($lessonId) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE id = ?");
        $stmt->execute([$lessonId]);
        return (bool)$stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Error checking lesson existence: " . $e->getMessage());
        return false;
    }
}

/**
 * تنسيق التاريخ بالعربية
 * @param string $date التاريخ
 * @return string التاريخ المنسق
 */
function formatArabicDate($date) {
    $months = [
        'January' => 'يناير',
        'February' => 'فبراير',
        'March' => 'مارس',
        'April' => 'أبريل',
        'May' => 'مايو',
        'June' => 'يونيو',
        'July' => 'يوليو',
        'August' => 'أغسطس',
        'September' => 'سبتمبر',
        'October' => 'أكتوبر',
        'November' => 'نوفمبر',
        'December' => 'ديسمبر'
    ];
    
    $timestamp = strtotime($date);
    $monthName = date('F', $timestamp);
    return date('d', $timestamp) . ' ' . $months[$monthName] . ' ' . date('Y', $timestamp);
}

// التأكد من وجود متغير الاتصال بقاعدة البيانات
global $db;

// دالة التحقق من تسجيل الدخول إذا لم تكن موجودة
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// إضافة دالة للتحقق من صلاحية الجلسة
function validateSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isLoggedIn();
}
