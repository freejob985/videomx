<?php
/**
 * ملف يحتوي على الدوال الخاصة بالأقسام والدروس
 * 
 * يوفر هذا الملف الدوال اللازمة للتعامل مع:
 * - الأقسام (sections)
 * - الدروس (lessons)
 * - اللغات (languages)
 */

// إضافة اتصال قاعدة البيانات
if (!isset($db)) {
    require_once __DIR__ . '/db.php';
}

/**
 * التحقق من وجود لغة معينة
 * @param int $language_id معرف اللغة
 * @return bool
 * مثال الاستخدام: if (languageExists(1)) { ... }
 */
function languageExists($language_id) {
    global $db;
    $stmt = $db->prepare("SELECT COUNT(*) FROM languages WHERE id = ?");
    $stmt->execute([$language_id]);
    return $stmt->fetchColumn() > 0;
}

/**
 * جلب معلومات لغة معينة
 * @param int $language_id معرف اللغة
 * @return array|null مصفوفة تحتوي على معلومات اللغة أو null إذا لم تكن موجودة
 * مثال الاستخدام: $language = getLanguageInfo(1);
 */
function getLanguageInfo($language_id) {
    global $db;
    $stmt = $db->prepare("
        SELECT id, name, created_at, updated_at 
        FROM languages 
        WHERE id = ?
    ");
    $stmt->execute([$language_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * التحقق من وجود قسم معين
 * @param int $section_id معرف القسم
 * @return bool
 * مثال الاستخدام: if (sectionExists(1)) { ... }
 */
function sectionExists($section_id) {
    global $db;
    $stmt = $db->prepare("SELECT COUNT(*) FROM sections WHERE id = ?");
    $stmt->execute([$section_id]);
    return $stmt->fetchColumn() > 0;
}

/**
 * جلب معلومات قسم معين
 * @param int $section_id معرف القسم
 * @return array|null مصفوفة تحتوي على معلومات القسم أو null إذا لم يكن موجوداً
 * مثال الاستخدام: $section = getSectionInfo(1);
 */
function getSectionInfo($section_id) {
    global $db;
    $stmt = $db->prepare("
        SELECT 
            s.*,
            l.name as language_name,
            COUNT(DISTINCT les.id) as lessons_count,
            COALESCE(SUM(les.duration), 0) as total_duration,
            COUNT(DISTINCT CASE WHEN les.completed = 1 THEN les.id END) as completed_lessons
        FROM sections s
        LEFT JOIN languages l ON l.id = s.language_id
        LEFT JOIN lessons les ON les.section_id = s.id
        WHERE s.id = ?
        GROUP BY s.id
    ");
    $stmt->execute([$section_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * جلب الأقسام الخاصة بلغة معينة
 * @param int $language_id معرف اللغة
 * @return array مصفوفة تحتوي على الأقسام وإحصائياتها
 * مثال الاستخدام: $sections = getSectionsByLanguage(1);
 */
function getSectionsByLanguage($language_id) {
    global $db;
    $stmt = $db->prepare("
        SELECT 
            s.*,
            COUNT(DISTINCT l.id) as lessons_count,
            COALESCE(SUM(l.duration), 0) as total_duration,
            COUNT(DISTINCT CASE WHEN l.completed = 1 THEN l.id END) as completed_lessons,
            COUNT(DISTINCT CASE WHEN l.is_important = 1 THEN l.id END) as important_lessons,
            COUNT(DISTINCT CASE WHEN l.is_theory = 1 THEN l.id END) as theory_lessons
        FROM sections s
        LEFT JOIN lessons l ON l.section_id = s.id
        WHERE s.language_id = ?
        GROUP BY s.id
        ORDER BY s.id ASC
    ");
    $stmt->execute([$language_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * جلب الدروس الخاصة بقسم معين
 * @param int $section_id معرف القسم
 * @return array مصفوفة تحتوي على الدروس وحالاتها
 * مثال الاستخدام: $lessons = getLessonsBySection(1);
 */
function getLessonsBySection($section_id) {
    global $db;
    $stmt = $db->prepare("
        SELECT 
            l.*,
            CASE 
                WHEN l.completed = 1 THEN 'مكتمل'
                WHEN l.is_important = 1 THEN 'مهم'
                WHEN l.is_theory = 1 THEN 'نظري'
                ELSE 'قيد التنفيذ'
            END as status_name,
            CASE 
                WHEN l.completed = 1 THEN '#28a745'
                WHEN l.is_important = 1 THEN '#dc3545'
                WHEN l.is_theory = 1 THEN '#17a2b8'
                ELSE '#6c757d'
            END as status_color,
            c.title as course_title
        FROM lessons l
        LEFT JOIN courses c ON c.id = l.course_id
        WHERE l.section_id = ?
        ORDER BY l.order_number ASC, l.id ASC
    ");
    $stmt->execute([$section_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * تنسيق المدة الزمنية
 * @param int $duration المدة بالثواني
 * @return string المدة منسقة (مثال: "01:30:45")
 * مثال الاستخدام: echo formatDuration(5445);
 */
function formatDuration($duration) {
    $hours = floor($duration / 3600);
    $minutes = floor(($duration % 3600) / 60);
    $seconds = $duration % 60;
    
    if ($hours > 0) {
        return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
    }
    return sprintf("%02d:%02d", $minutes, $seconds);
}

/**
 * جلب إحصائيات القسم
 * @param int $section_id معرف القسم
 * @return array مصفوفة تحتوي على إحصائيات القسم
 * مثال الاستخدام: $stats = getSectionStats(1);
 */
function getSectionStats($section_id) {
    global $db;
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT l.id) as total_lessons,
            COUNT(DISTINCT CASE WHEN l.completed = 1 THEN l.id END) as completed_lessons,
            COUNT(DISTINCT CASE WHEN l.is_important = 1 THEN l.id END) as important_lessons,
            COUNT(DISTINCT CASE WHEN l.is_theory = 1 THEN l.id END) as theory_lessons,
            COALESCE(SUM(l.duration), 0) as total_duration,
            COALESCE(SUM(CASE WHEN l.completed = 1 THEN l.duration ELSE 0 END), 0) as completed_duration
        FROM sections s
        LEFT JOIN lessons l ON l.section_id = s.id
        WHERE s.id = ?
        GROUP BY s.id
    ");
    $stmt->execute([$section_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * تحديث معلومات قسم
 * @param int $section_id معرف القسم
 * @param string $name اسم القسم
 * @param string $description وصف القسم
 * @return bool
 */
function updateSection($section_id, $name, $description) {
    global $db;
    try {
        $stmt = $db->prepare("
            UPDATE sections 
            SET name = ?, description = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        return $stmt->execute([$name, $description, $section_id]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * تحديث قسم الدرس
 * @param int $lesson_id معرف الدرس
 * @param int $section_id معرف القسم الجديد
 * @return bool
 */
function updateLessonSection($lesson_id, $section_id) {
    global $db;
    try {
        $stmt = $db->prepare("
            UPDATE lessons 
            SET section_id = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        return $stmt->execute([$section_id, $lesson_id]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        throw new Exception('حدث خطأ أثناء تحديث القسم');
    }
}

/**
 * تحديث حالة إكمال الدرس
 * @param int $lesson_id معرف الدرس
 * @param bool $completed حالة الإكمال
 * @return bool نجاح العملية
 */
function updateLessonCompletion($lesson_id, $completed) {
    global $db;
    try {
        $stmt = $db->prepare("
            UPDATE lessons 
            SET completed = ?, 
                completion_date = ?
            WHERE id = ?
        ");
        
        $completion_date = $completed ? date('Y-m-d H:i:s') : null;
        return $stmt->execute([$completed, $completion_date, $lesson_id]);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * جلب ملاحظات الدرس
 * @param int $lesson_id معرف الدرس
 * @return array ملاحظات الدرس
 */
function getLessonNotes($lesson_id) {
    global $db;
    try {
        $stmt = $db->prepare("
            SELECT * FROM lesson_notes 
            WHERE lesson_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$lesson_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * إضافة ملاحظة جديدة للدرس
 * @param int $lesson_id معرف الدرس
 * @param string $content محتوى الملاحظة
 * @return bool نجاح العملية
 */
function addLessonNote($lesson_id, $content) {
    global $db;
    try {
        $stmt = $db->prepare("
            INSERT INTO lesson_notes (lesson_id, content, created_at)
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        return $stmt->execute([$lesson_id, $content]);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

/**
 * تحديث وصف القسم
 * @param int $section_id معرف القسم
 * @param string $description الوصف الجديد
 * @return bool نجاح العملية
 * @throws Exception في حالة حدوث خطأ
 */
function updateSectionDescription($section_id, $description) {
    try {
        global $db;
        
        // تنظيف وتحضير البيانات
        $section_id = filter_var($section_id, FILTER_SANITIZE_NUMBER_INT);
        $description = trim($description);
        
        // التحقق من وجود القسم
        $stmt = $db->prepare("SELECT id FROM sections WHERE id = ?");
        $stmt->execute([$section_id]);
        if (!$stmt->fetch()) {
            throw new Exception("القسم غير موجود");
        }
        
        // تحديث الوصف
        $stmt = $db->prepare("UPDATE sections SET description = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$description, $section_id]);
        
        if (!$result) {
            throw new Exception("فشل تحديث وصف القسم");
        }
        
        // تسجيل العملية في سجل النظام
        logSystemActivity("تم تحديث وصف القسم رقم: " . $section_id);
        
        return true;
    } catch (PDOException $e) {
        // تسجيل الخطأ
        logError("خطأ في تحديث وصف القسم: " . $e->getMessage());
        throw new Exception("حدث خطأ أثناء تحديث وصف القسم");
    }
}

/**
 * تسجيل نشاط النظام
 * @param string $activity وصف النشاط
 */
function logSystemActivity($activity) {
    try {
        global $db;
        
        $stmt = $db->prepare("INSERT INTO system_logs (activity, created_at) VALUES (?, NOW())");
        $stmt->execute([$activity]);
    } catch (PDOException $e) {
        // تجاهل أخطاء التسجيل
        error_log("خطأ في تسجيل النشاط: " . $e->getMessage());
    }
}

/**
 * تسجيل الأخطاء
 * @param string $error وصف الخطأ
 */
function logError($error) {
    try {
        global $db;
        
        $stmt = $db->prepare("INSERT INTO error_logs (error_message, created_at) VALUES (?, NOW())");
        $stmt->execute([$error]);
    } catch (PDOException $e) {
        // تسجيل في ملف السجل الافتراضي
        error_log($error);
    }
} 