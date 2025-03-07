<?php
require_once '../config.php';
require_once '../helper_functions.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            // تعديل الاستعلام ليتناسب مع هيكل قاعدة البيانات
            $stmt = $db->query("
                SELECT 
                    s.id,
                    s.name,
                    s.language_id,
                    l.name as language_name,
                    (SELECT COUNT(*) FROM courses WHERE processing_status = s.name) as courses_count
                FROM statuses s
                LEFT JOIN languages l ON s.language_id = l.id
                GROUP BY s.id, s.name, s.language_id, l.name
                ORDER BY s.name
            ");
            
            $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo jsonResponse(true, 'تم جلب الحالات بنجاح', $statuses);
        } catch (Exception $e) {
            echo jsonResponse(false, 'خطأ في جلب الحالات: ' . $e->getMessage());
        }
        break;

    case 'POST':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name']) || !isset($data['language_id'])) {
                throw new Exception('جميع الحقول مطلوبة');
            }

            // التحقق من عدم وجود الحالة
            $stmt = $db->prepare('SELECT id FROM statuses WHERE name = ? AND language_id = ?');
            $stmt->execute([$data['name'], $data['language_id']]);
            if ($stmt->fetch()) {
                throw new Exception('هذه الحالة موجودة بالفعل لهذه اللغة');
            }

            // إضافة الحالة
            $stmt = $db->prepare('INSERT INTO statuses (name, language_id) VALUES (?, ?)');
            $stmt->execute([$data['name'], $data['language_id']]);
            
            $newId = $db->lastInsertId();
            
            echo jsonResponse(true, 'تم إضافة الحالة بنجاح', [
                'id' => $newId,
                'name' => $data['name'],
                'language_id' => $data['language_id']
            ]);
        } catch (Exception $e) {
            echo jsonResponse(false, 'خطأ في إضافة الحالة: ' . $e->getMessage());
        }
        break;

    case 'DELETE':
        try {
            $id = (int)$_GET['id'];
            
            // التحقق من عدم وجود كورسات مرتبطة
            $stmt = $db->prepare('
                SELECT s.name, COUNT(c.id) as course_count 
                FROM statuses s 
                LEFT JOIN courses c ON c.processing_status = s.name 
                WHERE s.id = ? 
                GROUP BY s.name
            ');
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if ($result && $result['course_count'] > 0) {
                throw new Exception('لا يمكن حذف الحالة لوجود كورسات مرتبطة بها');
            }

            // حذف الحالة
            $stmt = $db->prepare('DELETE FROM statuses WHERE id = ?');
            $stmt->execute([$id]);
            
            echo jsonResponse(true, 'تم حذف الحالة بنجاح');
        } catch (Exception $e) {
            echo jsonResponse(false, 'خطأ في حذف الحالة: ' . $e->getMessage());
        }
        break;

    default:
        echo jsonResponse(false, 'طريقة الطلب غير مدعومة');
        break;
} 