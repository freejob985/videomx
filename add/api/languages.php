<?php
/**
 * API للتعامل مع اللغات
 */

require_once '../config.php';
require_once '../helper_functions.php';

// منع الوصول المباشر
if (!defined('INCLUDED')) {
    http_response_code(403);
    die('Access Forbidden');
}

header('Content-Type: application/json; charset=utf-8');

try {
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            // جلب قائمة اللغات
            $stmt = $db->prepare("
                SELECT 
                    l.id,
                    l.name,
                    l.created_at,
                    l.updated_at,
                    COUNT(DISTINCT c.id) as courses_count
                FROM languages l
                LEFT JOIN courses c ON l.id = c.language_id
                GROUP BY l.id, l.name, l.created_at, l.updated_at
                ORDER BY l.name ASC
            ");
            
            $stmt->execute();
            $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'تم جلب اللغات بنجاح',
                'data' => $languages
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'POST':
            // إضافة لغة جديدة
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name']) || empty(trim($data['name']))) {
                throw new Exception('اسم اللغة مطلوب');
            }

            $name = trim($data['name']);

            // التحقق من عدم وجود اللغة
            $stmt = $db->prepare('SELECT id FROM languages WHERE name = ?');
            $stmt->execute([$name]);
            if ($stmt->fetch()) {
                throw new Exception('هذه اللغة موجودة بالفعل');
            }

            // إضافة اللغة
            $stmt = $db->prepare('INSERT INTO languages (name) VALUES (?)');
            $stmt->execute([$name]);

            $newId = $db->lastInsertId();
            
            // جلب اللغة المضافة
            $stmt = $db->prepare('SELECT * FROM languages WHERE id = ?');
            $stmt->execute([$newId]);
            $language = $stmt->fetch();

            // إضافة الحالات الافتراضية لكل لغة مع إعادة
            $defaultStatuses = [
                'تطوير', 'بحث', 'مكتمل', 'مشاريع', 'نظري',
                'عملي', 'مراجعة', 'تحديث', 'أساسيات', 'متقدم', 'إعادة'
            ];

            $stmt = $db->prepare('INSERT INTO statuses (name, language_id) VALUES (:name, :language_id)');
            foreach ($defaultStatuses as $status) {
                $stmt->execute([':name' => $status, ':language_id' => $newId]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'تم إضافة اللغة بنجاح',
                'data' => $language
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'PUT':
            // تحديث لغة
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id']) || !isset($data['name'])) {
                throw new Exception('جميع البيانات مطلوبة');
            }

            $id = (int)$data['id'];
            $name = trim($data['name']);

            if (empty($name)) {
                throw new Exception('اسم اللغة مطلوب');
            }

            // التحقق من وجود اللغة
            $stmt = $db->prepare('SELECT id FROM languages WHERE id = ?');
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                throw new Exception('اللغة غير موجودة');
            }

            // التحقق من عدم تكرار الاسم
            $stmt = $db->prepare('SELECT id FROM languages WHERE name = ? AND id != ?');
            $stmt->execute([$name, $id]);
            if ($stmt->fetch()) {
                throw new Exception('يوجد لغة أخرى بنفس الاسم');
            }

            // تحديث اللغة
            $stmt = $db->prepare('UPDATE languages SET name = ? WHERE id = ?');
            $stmt->execute([$name, $id]);

            echo json_encode([
                'success' => true,
                'message' => 'تم تحديث اللغة بنجاح'
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'DELETE':
            try {
                $db->beginTransaction();
                
                $id = (int)$_GET['id'];

                // حذف الدروس المرتبطة بالكورسات التي تنتمي لهذه اللغة
                $stmt = $db->prepare('
                    DELETE lessons 
                    FROM lessons 
                    INNER JOIN courses ON lessons.course_id = courses.id 
                    WHERE courses.language_id = ?
                ');
                $stmt->execute([$id]);

                // حذف الكورسات المرتبطة باللغة
                $stmt = $db->prepare('DELETE FROM courses WHERE language_id = ?');
                $stmt->execute([$id]);

                // حذف اللغة
                $stmt = $db->prepare('DELETE FROM languages WHERE id = ?');
                $stmt->execute([$id]);

                $db->commit();
                
                echo jsonResponse(true, 'تم حذف اللغة والكورسات المرتبطة بها بنجاح');
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                echo jsonResponse(false, 'خطأ في حذف اللغة: ' . $e->getMessage());
            }
            break;

        default:
            throw new Exception('طريقة الطلب غير مدعومة');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات'
    ], JSON_UNESCAPED_UNICODE);
} 