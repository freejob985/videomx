<?php
/**
 * ملف تحديث حالة الدرس
 * يتعامل مع طلبات AJAX لتحديث بيانات الدرس
 * 
 * المدخلات المتوقعة:
 * - lesson_id: معرف الدرس
 * - status_id: معرف الحالة
 * - section_id: معرف القسم
 * - is_theory: هل الدرس نظري (0/1)
 * - is_important: هل الدرس مهم (0/1)
 * - completed: هل الدرس مكتمل (0/1)
 * - is_reviewed: هل تمت مراجعة الدرس (0/1)
 * 
 * المخرجات:
 * JSON object يحتوي على:
 * - success: نجاح العملية (true/false)
 * - message: رسالة توضيحية
 * - data: بيانات الدرس المحدثة
 */

require_once 'config/database.php';

/**
 * تحديث حالة الدرس وجميع الحقول القابلة للتحديث
 * @param int $lessonId معرف الدرس
 * @param array $data البيانات المراد تحديثها
 * @return bool نجاح العملية
 */
function updateLessonStatus($lessonId, $data) {
    global $conn;
    
    // التحقق من وجود الدرس
    $checkSql = "SELECT id FROM lessons WHERE id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $lessonId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }

    // تحديث جميع الحقول القابلة للتحديث
    $sql = "UPDATE lessons SET 
            status_id = ?,
            section_id = NULLIF(?, 0),
            is_theory = ?,
            is_important = ?,
            completed = ?,
            is_reviewed = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";
            
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "iiiiiii",
            $data['status_id'],
            $data['section_id'],
            $data['is_theory'],
            $data['is_important'],
            $data['completed'],
            $data['is_reviewed'],
            $lessonId
        );
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error updating lesson status: " . $e->getMessage());
        return false;
    }
}

/**
 * تسجيل تحديثات الدرس في سجل التغييرات
 * @param int $lessonId معرف الدرس
 * @param array $data البيانات المحدثة
 */
function logLessonUpdate($lessonId, $data) {
    global $conn;
    
    $sql = "INSERT INTO lesson_updates (
                lesson_id,
                status_id,
                section_id,
                is_theory,
                is_important,
                completed,
                is_reviewed,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
            
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "iiiiiii",
            $lessonId,
            $data['status_id'],
            $data['section_id'],
            $data['is_theory'],
            $data['is_important'],
            $data['completed'],
            $data['is_reviewed']
        );
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Error logging lesson update: " . $e->getMessage());
    }
}

// التأكد من أن الطلب POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'طريقة طلب غير صحيحة');
}

// التحقق من وجود البيانات المطلوبة
if (!isset($_POST['lesson_id'])) {
    sendResponse(false, 'معرف الدرس مطلوب');
}

// تجميع بيانات التحديث
$updateData = [
    'lesson_id' => (int)$_POST['lesson_id'],
    'status_id' => isset($_POST['status_id']) ? (int)$_POST['status_id'] : null,
    'section_id' => !empty($_POST['section_id']) ? (int)$_POST['section_id'] : null,
    'is_theory' => isset($_POST['is_theory']) ? (int)$_POST['is_theory'] : 0,
    'is_important' => isset($_POST['is_important']) ? (int)$_POST['is_important'] : 0,
    'completed' => isset($_POST['completed']) ? (int)$_POST['completed'] : 0,
    'is_reviewed' => isset($_POST['is_reviewed']) ? (int)$_POST['is_reviewed'] : 0
];

// التحقق من صحة البيانات
if (!validateUpdateData($updateData)) {
    sendResponse(false, 'بيانات غير صالحة');
}

try {
    // التحقق من وجود الدرس أولاً
    $checkSql = "SELECT id FROM lessons WHERE id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $updateData['lesson_id']);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(false, 'الدرس غير موجود');
    }
    
    // تحديث الدرس
    $sql = "UPDATE lessons SET 
            status_id = ?,
            section_id = NULLIF(?, 0),
            is_theory = ?,
            is_important = ?,
            completed = ?,
            is_reviewed = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        sendResponse(false, 'خطأ في إعداد الاستعلام: ' . $conn->error);
    }
    
    $stmt->bind_param(
        "iiiiiii",
        $updateData['status_id'],
        $updateData['section_id'],
        $updateData['is_theory'],
        $updateData['is_important'],
        $updateData['completed'],
        $updateData['is_reviewed'],
        $updateData['lesson_id']
    );
    
    if ($stmt->execute()) {
        // تسجيل التحديث
        logLessonUpdate($updateData['lesson_id'], $updateData);
        
        // جلب البيانات المحدثة
        $updatedLesson = getLessonDetails($updateData['lesson_id']);
        if (!$updatedLesson) {
            sendResponse(false, 'تم التحديث ولكن فشل جلب البيانات المحدثة');
        }
        sendResponse(true, 'تم تحديث حالة الدرس بنجاح', $updatedLesson);
    } else {
        sendResponse(false, 'فشل تحديث حالة الدرس: ' . $stmt->error);
    }
} catch (Exception $e) {
    error_log("Error updating lesson: " . $e->getMessage());
    sendResponse(false, 'حدث خطأ أثناء تحديث الدرس: ' . $e->getMessage());
}

/**
 * التحقق من صحة بيانات التحديث
 * @param array $data البيانات المراد التحقق منها
 * @return bool نتيجة التحقق
 */
function validateUpdateData($data) {
    if (!isset($data['status_id']) || !is_numeric($data['status_id']) || $data['status_id'] <= 0) {
        error_log("Invalid status_id: " . print_r($data['status_id'], true));
        return false;
    }
    
    // السماح بقيمة فارغة للقسم
    if (isset($data['section_id']) && $data['section_id'] !== null) {
        if (!is_numeric($data['section_id']) || $data['section_id'] < 0) {
            error_log("Invalid section_id: " . print_r($data['section_id'], true));
            return false;
        }
    }
    
    foreach (['is_theory', 'is_important', 'completed', 'is_reviewed'] as $field) {
        if (!isset($data[$field]) || !in_array($data[$field], [0, 1])) {
            error_log("Invalid {$field}: " . print_r($data[$field], true));
            return false;
        }
    }
    
    return true;
}

/**
 * جلب تفاصيل الدرس المحدثة
 * @param int $lessonId معرف الدرس
 * @return array|null بيانات الدرس
 */
function getLessonDetails($lessonId) {
    global $conn;
    
    $sql = "SELECT 
            l.*,
            COALESCE(s.name, 'غير محدد') as section_name,
            COALESCE(st.name, 'غير محدد') as status_name,
            COALESCE(st.color, '#gray') as status_color,
            COALESCE(st.text_color, '#000') as status_text_color
            FROM lessons l
            LEFT JOIN sections s ON l.section_id = s.id
            LEFT JOIN statuses st ON l.status_id = st.id
            WHERE l.id = ?";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing getLessonDetails query: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $lessonId);
    
    if (!$stmt->execute()) {
        error_log("Error executing getLessonDetails query: " . $stmt->error);
        return null;
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        error_log("Error getting result in getLessonDetails: " . $stmt->error);
        return null;
    }
    
    return $result->fetch_assoc();
}

/**
 * إرسال استجابة JSON
 * @param bool $success نجاح العملية
 * @param string $message رسالة توضيحية
 * @param array|null $data بيانات إضافية
 */
function sendResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
} 