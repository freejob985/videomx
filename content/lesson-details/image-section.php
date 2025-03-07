<?php
// دالة للحصول على صور الدرس
function getLessonImages($lessonId) {
    try {
        $db = connectDB();
        if (!$db) {
            throw new Exception("فشل الاتصال بقاعدة البيانات");
        }
        
        $sql = "SELECT * FROM lesson_image_notes 
                WHERE lesson_id = ? 
                ORDER BY created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$lessonId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error in getLessonImages: " . $e->getMessage());
        return [];
    }
}

// دالة لإضافة صورة جديدة
function addLessonImage($lessonId, $title, $description, $imageUrl) {
    try {
        $db = connectDB();
        if (!$db) {
            throw new Exception("فشل الاتصال بقاعدة البيانات");
        }
        
        $sql = "INSERT INTO lesson_image_notes 
                (lesson_id, title, description, image_url, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$lessonId, $title, $description, $imageUrl]);
    } catch (Exception $e) {
        error_log("Error in addLessonImage: " . $e->getMessage());
        return false;
    }
}

// دالة لتحديث صورة
function updateLessonImage($imageId, $title, $description) {
    try {
        $db = connectDB();
        if (!$db) {
            throw new Exception("فشل الاتصال بقاعدة البيانات");
        }
        
        $sql = "UPDATE lesson_image_notes 
                SET title = ?, description = ?, updated_at = NOW() 
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$title, $description, $imageId]);
    } catch (Exception $e) {
        error_log("Error in updateLessonImage: " . $e->getMessage());
        return false;
    }
}

// دالة لحذف صورة
function deleteLessonImage($imageId) {
    try {
        $db = connectDB();
        if (!$db) {
            throw new Exception("فشل الاتصال بقاعدة البيانات");
        }
        
        $sql = "DELETE FROM lesson_image_notes WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$imageId]);
    } catch (Exception $e) {
        error_log("Error in deleteLessonImage: " . $e->getMessage());
        return false;
    }
}
?> 