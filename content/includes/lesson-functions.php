<?php
/**
 * Functions for lesson management
 * =============================
 * This file contains functions specific to lesson management including:
 * - Toggle completion status
 * - Toggle review status
 * - ChatGPT integration
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/database.php';

// Initialize database connection
function getLessonPDO() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $config = require __DIR__ . '/../config/database.php';
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}"
            ];
            $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    return $pdo;
}

/**
 * Toggle lesson completion status
 * @param int $lessonId Lesson ID
 * @return array Response with status and message
 */
function toggleLessonCompletion($lessonId) {
    try {
        $pdo = getLessonPDO();
        
        // Get current completion status
        $stmt = $pdo->prepare("SELECT completed FROM lessons WHERE id = ?");
        $stmt->execute([$lessonId]);
        $currentStatus = $stmt->fetchColumn();
        
        // Toggle status
        $newStatus = $currentStatus ? 0 : 1;
        
        // Update database
        $stmt = $pdo->prepare("UPDATE lessons SET completed = ? WHERE id = ?");
        $success = $stmt->execute([$newStatus, $lessonId]);
        
        if ($success) {
            return [
                'status' => 'success',
                'message' => $newStatus ? 'تم تحديد الدرس كمكتمل' : 'تم تحديد الدرس كغير مكتمل',
                'completed' => $newStatus
            ];
        }
        
        return [
            'status' => 'error',
            'message' => 'فشل تحديث حالة الدرس'
        ];
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return [
            'status' => 'error',
            'message' => 'حدث خطأ في قاعدة البيانات'
        ];
    }
}

/**
 * Toggle lesson review status
 * @param int $lessonId Lesson ID
 * @return array Response with status and message
 */
function toggleLessonReview($lessonId) {
    try {
        $pdo = getLessonPDO();
        
        // Get current review status
        $stmt = $pdo->prepare("SELECT is_reviewed FROM lessons WHERE id = ?");
        $stmt->execute([$lessonId]);
        $currentStatus = $stmt->fetchColumn();
        
        // Toggle status
        $newStatus = $currentStatus ? 0 : 1;
        
        // Update database
        $stmt = $pdo->prepare("UPDATE lessons SET is_reviewed = ? WHERE id = ?");
        $success = $stmt->execute([$newStatus, $lessonId]);
        
        if ($success) {
            return [
                'status' => 'success',
                'message' => $newStatus ? 'تمت إضافة الدرس للمراجعة' : 'تم إزالة الدرس من المراجعة',
                'is_reviewed' => $newStatus
            ];
        }
        
        return [
            'status' => 'error',
            'message' => 'فشل تحديث حالة المراجعة'
        ];
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return [
            'status' => 'error',
            'message' => 'حدث خطأ في قاعدة البيانات'
        ];
    }
}

/**
 * Format lesson title for ChatGPT query
 * @param string $title Lesson title
 * @return string Formatted query
 */
function formatChatGPTQuery($title) {
    return "اريد شرح نظري بالأمثلة والتفاصيل للدرس: $title\n\n" .
           "المطلوب:\n" .
           "1. شرح مفصل للمفاهيم النظرية\n" .
           "2. أمثلة عملية وتطبيقية\n" .
           "3. تفاصيل وملاحظات مهمة\n" .
           "4. شرح مختصر وموجز";
} 