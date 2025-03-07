<?php
require_once '../config.php';
require_once '../helper_functions.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $stmt = $db->query("
                SELECT 
                    s.id,
                    s.name,
                    s.language_id,
                    l.name as language_name,
                    COUNT(DISTINCT cs.course_id) as courses_count
                FROM sections s
                LEFT JOIN languages l ON s.language_id = l.id
                LEFT JOIN course_sections cs ON s.id = cs.section_id
                GROUP BY s.id, s.name, s.language_id, l.name
                ORDER BY s.name
            ");
            
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo jsonResponse(true, 'تم جلب الأقسام بنجاح', $sections);
        } catch (Exception $e) {
            echo jsonResponse(false, 'خطأ في جلب الأقسام: ' . $e->getMessage());
        }
        break;

    case 'POST':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name']) || !isset($data['language_id'])) {
                throw new Exception('جميع الحقول مطلوبة');
            }

            // التحقق من عدم وجود القسم
            $stmt = $db->prepare('SELECT id FROM sections WHERE name = ? AND language_id = ?');
            $stmt->execute([$data['name'], $data['language_id']]);
            if ($stmt->fetch()) {
                throw new Exception('هذا القسم موجود بالفعل لهذه اللغة');
            }

            // إضافة القسم
            $stmt = $db->prepare('INSERT INTO sections (name, language_id) VALUES (?, ?)');
            $stmt->execute([$data['name'], $data['language_id']]);
            
            $newId = $db->lastInsertId();
            
            echo jsonResponse(true, 'تم إضافة القسم بنجاح', [
                'id' => $newId,
                'name' => $data['name'],
                'language_id' => $data['language_id']
            ]);
        } catch (Exception $e) {
            echo jsonResponse(false, 'خطأ في إضافة القسم: ' . $e->getMessage());
        }
        break;

    case 'DELETE':
        try {
            $id = (int)$_GET['id'];
            
            // التحقق من عدم وجود كورسات مرتبطة
            $stmt = $db->prepare('
                SELECT COUNT(*) FROM course_sections WHERE section_id = ?
            ');
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('لا يمكن حذف القسم لوجود كورسات مرتبطة به');
            }

            // حذف القسم
            $stmt = $db->prepare('DELETE FROM sections WHERE id = ?');
            $stmt->execute([$id]);
            
            echo jsonResponse(true, 'تم حذف القسم بنجاح');
        } catch (Exception $e) {
            echo jsonResponse(false, 'خطأ في حذف القسم: ' . $e->getMessage());
        }
        break;

    default:
        echo jsonResponse(false, 'طريقة الطلب غير مدعومة');
        break;
} 