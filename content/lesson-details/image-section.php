<?php
// حذف تعريف الدالة getLessonImages من هنا لأنها معرفة في functions.php
require_once __DIR__ . '/../includes/functions.php';

// دالة لإضافة صورة جديدة
function addLessonImage($lessonId, $title, $description, $imageUrl) {
    try {
        $db = connectDB();
        $stmt = $db->prepare("
            INSERT INTO lesson_image_notes (lesson_id, title, description, image_url) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$lessonId, $title, $description, $imageUrl]);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

// دالة لتحديث صورة
function updateLessonImage($imageId, $title, $description) {
    try {
        $db = connectDB();
        $stmt = $db->prepare("
            UPDATE lesson_image_notes 
            SET title = ?, description = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$title, $description, $imageId]);
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

// دالة لحذف صورة
function deleteLessonImage($imageId) {
    try {
        $db = connectDB();
        
        // جلب معلومات الصورة قبل حذفها
        $stmt = $db->prepare("SELECT image_url FROM lesson_image_notes WHERE id = ?");
        $stmt->execute([$imageId]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($image) {
            // حذف الملف من السيرفر إذا كان محلياً
            if (strpos($image['image_url'], '/content/uploads/') !== false) {
                $filePath = __DIR__ . '/../../' . ltrim($image['image_url'], '/');
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            // حذف السجل من قاعدة البيانات
            $stmt = $db->prepare("DELETE FROM lesson_image_notes WHERE id = ?");
            return $stmt->execute([$imageId]);
        }
        return false;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}
?> 