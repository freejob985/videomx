<?php
/**
 * get_courses.php
 * الملف المسؤول عن جلب جميع الكورسات وأقسامها والدروس والملاحظات
 * 
 * المخرجات:
 * - JSON يحتوي على مصفوفة من الكورسات وأقسامها
 */

// تكوين قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'courses_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// إعداد headers لـ JSON
header('Content-Type: application/json; charset=utf-8');

// تمكين عرض الأخطاء للتصحيح
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // إنشاء اتصال PDO
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // استعلام أبسط لتجنب مشاكل GROUP BY
    $query = "
        SELECT 
            c.id as course_id,
            c.title as course_title,
            c.description as course_description,
            c.thumbnail as course_thumbnail,
            c.playlist_url,
            l.name as language_name,
            l.id as language_id
        FROM courses c
        LEFT JOIN languages l ON c.language_id = l.id
        ORDER BY c.id
    ";

    $stmt = $pdo->query($query);
    $courses = [];
    $languages = [];
    
    // جلب الكورسات
    while ($row = $stmt->fetch()) {
        $courses[$row['course_id']] = [
            'id' => $row['course_id'],
            'title' => $row['course_title'],
            'description' => substr($row['course_description'] ?? '', 0, 150) . '...',
            'thumbnail' => $row['course_thumbnail'],
            'playlist_url' => $row['playlist_url'],
            'language' => $row['language_name'] ?? 'غير محدد',
            'language_id' => $row['language_id'],
            'sections' => [],
            'lessons' => []
        ];
        
        // تجميع اللغات الفريدة
        if (!isset($languages[$row['language_id']]) && $row['language_id']) {
            $languages[$row['language_id']] = [
                'id' => $row['language_id'],
                'name' => $row['language_name'],
                'sections' => []
            ];
        }
    }
    
    if (count($courses) > 0) {
        // جلب الأقسام
        $courseIds = array_keys($courses);
        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        
        $sectionsQuery = "
            SELECT 
                s.id as section_id,
                s.name as section_name,
                s.description as section_description,
                c.id as course_id,
                c.language_id
            FROM sections s
            JOIN lessons l ON s.id = l.section_id
            JOIN courses c ON l.course_id = c.id
            WHERE c.id IN ($placeholders)
            GROUP BY s.id, c.id
            ORDER BY s.id
        ";
        
        $sectionsStmt = $pdo->prepare($sectionsQuery);
        $sectionsStmt->execute($courseIds);
        
        while ($section = $sectionsStmt->fetch()) {
            // إضافة القسم للكورس
            if (isset($courses[$section['course_id']])) {
                $courses[$section['course_id']]['sections'][] = [
                    'id' => $section['section_id'],
                    'name' => $section['section_name'],
                    'description' => $section['section_description']
                ];
            }
            
            // إضافة القسم للغة (إذا لم يكن موجوداً بالفعل)
            if (isset($languages[$section['language_id']])) {
                $sectionExists = false;
                foreach ($languages[$section['language_id']]['sections'] as $existingSection) {
                    if ($existingSection['id'] == $section['section_id']) {
                        $sectionExists = true;
                        break;
                    }
                }
                
                if (!$sectionExists) {
                    $languages[$section['language_id']]['sections'][] = [
                        'id' => $section['section_id'],
                        'name' => $section['section_name'],
                        'description' => $section['section_description']
                    ];
                }
            }
        }
        
        // جلب الدروس والملاحظات
        $lessonsQuery = "
            SELECT 
                les.id as lesson_id,
                les.title as lesson_title,
                les.course_id,
                s.name as section_name,
                n.content as note_content
            FROM lessons les
            LEFT JOIN sections s ON les.section_id = s.id
            LEFT JOIN notes n ON les.id = n.lesson_id AND n.type = 'text'
            WHERE les.course_id IN ($placeholders)
            ORDER BY les.course_id, les.order_number
        ";
        
        $lessonsStmt = $pdo->prepare($lessonsQuery);
        $lessonsStmt->execute($courseIds);
        
        while ($lesson = $lessonsStmt->fetch()) {
            if (isset($courses[$lesson['course_id']])) {
                $courses[$lesson['course_id']]['lessons'][] = [
                    'id' => $lesson['lesson_id'],
                    'title' => $lesson['lesson_title'] ?? 'بدون عنوان',
                    'section' => $lesson['section_name'] ?? 'بدون قسم',
                    'note' => $lesson['note_content']
                ];
            }
        }
    }

    // تحويل المصفوفة الترابطية إلى مصفوفة عادية
    $response = [
        'status' => 'success',
        'data' => array_values($courses),
        'languages' => array_values($languages)
    ];

} catch (PDOException $e) {
    $response = [
        'status' => 'error',
        'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
    ];
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => 'خطأ عام: ' . $e->getMessage()
    ];
}

// التأكد من أن الاستجابة صالحة قبل تحويلها إلى JSON
if (!isset($response)) {
    $response = [
        'status' => 'error',
        'message' => 'حدث خطأ غير معروف'
    ];
}

// إرجاع النتيجة
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR); 