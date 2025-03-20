<?php
/**
 * ملف خاص بوظائف فلترة الحروف للغات البرمجة
 * 
 * يحتوي هذا الملف على الوظائف المتعلقة بفلترة اللغات حسب الحروف الأبجدية
 * 
 * التبعيات:
 * - يتطلب اتصال بقاعدة البيانات ($conn)
 * - يتطلب ملف functions.php للوظائف المساعدة
 * 
 * @package LanguageFilter
 * @version 1.0
 */

/**
 * الحصول على الأحرف الأولى المتوفرة من أسماء اللغات
 * 
 * تقوم هذه الدالة بجلب الأحرف الأولى فقط من اللغات المتوفرة في قاعدة البيانات
 * وترتيبها أبجدياً
 * 
 * @return array مصفوفة تحتوي على الأحرف الأولى المتوفرة
 * 
 * مثال الاستخدام:
 * $letters = getAvailableLetters();
 * // ['A', 'C', 'J', 'P', 'R']
 */
function getAvailableLetters() {
    global $conn;
    
    if (!$conn) {
        error_log("Database connection error in getAvailableLetters");
        return [];
    }
    
    try {
        $query = "SELECT DISTINCT UPPER(LEFT(name, 1)) as letter 
                  FROM languages 
                  WHERE name REGEXP '^[A-Za-z]'
                  ORDER BY letter";
        
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            error_log("Query error in getAvailableLetters: " . mysqli_error($conn));
            return [];
        }
        
        $letters = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $letters[] = $row['letter'];
        }
        
        return $letters;
        
    } catch (Exception $e) {
        error_log("Error in getAvailableLetters: " . $e->getMessage());
        return [];
    }
}

/**
 * الحصول على اللغات التي تبدأ بحرف معين
 * 
 * تقوم هذه الدالة بجلب اللغات التي تبدأ بحرف محدد مع معلوماتها الإحصائية
 * 
 * @param string $letter الحرف المراد البحث عنه
 * @param int $page رقم الصفحة الحالية (افتراضياً 1)
 * @param int $perPage عدد العناصر في الصفحة (افتراضياً 12)
 * @return array مصفوفة تحتوي على:
 *               - results: نتائج اللغات
 *               - total: العدد الإجمالي للنتائج
 * 
 * مثال الاستخدام:
 * $result = getLanguagesByLetter('P', 1, 12);
 * // ['results' => [...], 'total' => 5]
 */
function getLanguagesByLetter($letter, $page = 1, $perPage = 12) {
    global $conn;
    
    if (!$conn) {
        error_log("Database connection error in getLanguagesByLetter");
        return ['results' => [], 'total' => 0];
    }
    
    try {
        $offset = ($page - 1) * $perPage;
        
        // الاستعلام الرئيسي للحصول على اللغات
        $query = "SELECT l.*, 
                         COUNT(DISTINCT c.id) as courses_count,
                         COUNT(DISTINCT ls.id) as lessons_count,
                         SUM(CASE WHEN ls.completed = 1 THEN 1 ELSE 0 END) as completed_lessons,
                         SUM(CASE WHEN ls.is_important = 1 THEN 1 ELSE 0 END) as important_lessons,
                         SUM(ls.duration) as total_duration,
                         MAX(ls.updated_at) as last_update
                  FROM languages l
                  LEFT JOIN courses c ON l.id = c.language_id
                  LEFT JOIN lessons ls ON c.id = ls.course_id
                  WHERE l.name LIKE ?
                  GROUP BY l.id
                  ORDER BY lessons_count DESC, l.name ASC
                  LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($query);
        $letterPattern = $letter . '%';
        $stmt->bind_param('sii', $letterPattern, $perPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $languages = [];
        while ($row = $result->fetch_assoc()) {
            $languages[] = $row;
        }
        
        // استعلام للحصول على إجمالي عدد النتائج
        $countQuery = "SELECT COUNT(*) as total FROM languages WHERE name LIKE ?";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->bind_param('s', $letterPattern);
        $countStmt->execute();
        $total = $countStmt->get_result()->fetch_assoc()['total'];
        
        return [
            'results' => $languages,
            'total' => $total
        ];
        
    } catch (Exception $e) {
        error_log("Error in getLanguagesByLetter: " . $e->getMessage());
        return ['results' => [], 'total' => 0];
    }
}

/**
 * التحقق من صحة الحرف المدخل
 * 
 * @param string $letter الحرف المراد التحقق منه
 * @return bool صحيح إذا كان الحرف صالحاً
 */
function isValidLetter($letter) {
    return preg_match('/^[A-Za-z]$/', $letter);
} 