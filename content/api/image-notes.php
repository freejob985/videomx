<?php
// إضافة هذه الأسطر في بداية الملف
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// تصحيح المسارات باستخدام __DIR__
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../lesson-details/image-section.php';

// التأكد من وجود مجلد الرفع
$uploadDir = __DIR__ . '/../uploads/lesson-images/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $lessonId = $_POST['lesson_id'] ?? null;

        if (!$lessonId) {
            throw new Exception('معرف الدرس مطلوب');
        }

        switch ($action) {
            case 'upload':
                handleFileUpload($lessonId);
                break;
            
            case 'add_external':
                handleExternalImage($lessonId);
                break;

            default:
                throw new Exception('إجراء غير صالح');
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $lessonId = $_GET['lesson_id'] ?? null;
        if (!$lessonId) {
            throw new Exception('معرف الدرس مطلوب');
        }
        getImages($lessonId);
    }
} catch (Exception $e) {
    sendError($e->getMessage());
}

function handleFileUpload($lessonId) {
    global $uploadDir;

    if (!isset($_FILES['file'])) {
        throw new Exception('لم يتم تحديد ملف');
    }

    $file = $_FILES['file'];
    validateImage($file);

    $fileName = generateUniqueFileName($file['name']);
    $uploadFile = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
        $imageUrl = '/content/uploads/lesson-images/' . $fileName;
        $title = pathinfo($file['name'], PATHINFO_FILENAME);
        
        if (addLessonImage($lessonId, $title, '', $imageUrl)) {
            sendSuccess(['message' => 'تم رفع الصورة بنجاح', 'image_url' => $imageUrl]);
        } else {
            unlink($uploadFile);
            throw new Exception('فشل في حفظ معلومات الصورة');
        }
    } else {
        throw new Exception('فشل في رفع الصورة');
    }
}

function handleExternalImage($lessonId) {
    $url = $_POST['url'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new Exception('رابط الصورة غير صالح');
    }

    if (empty($title)) {
        throw new Exception('عنوان الصورة مطلوب');
    }

    if (addLessonImage($lessonId, $title, $description, $url)) {
        sendSuccess(['message' => 'تم إضافة الصورة بنجاح']);
    } else {
        throw new Exception('فشل في إضافة الصورة');
    }
}

function validateImage($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('نوع الملف غير مدعوم');
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('حجم الملف كبير جداً');
    }
}

function generateUniqueFileName($originalName) {
    return uniqid() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', basename($originalName));
}

function getImages($lessonId) {
    $images = getLessonImages($lessonId);
    sendSuccess(['images' => $images]);
}

function sendSuccess($data) {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function sendError($message) {
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit;
}

// إضافة دالة جديدة لمعالجة طلب قائمة الصور
function handleImagesList() {
    $lessonId = $_GET['lesson_id'] ?? null;
    
    if (!$lessonId) {
        http_response_code(400);
        echo json_encode(['error' => 'معرف الدرس مطلوب']);
        return;
    }

    $images = getLessonImages($lessonId);
    echo json_encode([
        'success' => true,
        'images' => $images
    ]);
}

// معالجة تحديث الصور
function handleImageUpdate() {
    $imageId = $_POST['image_id'] ?? null;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';

    if (updateLessonImage($imageId, $title, $description)) {
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث الصورة بنجاح'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'فشل في تحديث الصورة']);
    }
}

// معالجة حذف الصور
function handleImageDelete() {
    $imageId = $_POST['image_id'] ?? null;

    try {
        $db = connectDB();
        
        // جلب معلومات الصورة قبل حذفها
        $stmt = $db->prepare("SELECT image_url FROM lesson_image_notes WHERE id = ?");
        $stmt->execute([$imageId]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($image) {
            // حذف الملف من السيرفر إذا كان محلياً
            $imageUrl = $image['image_url'];
            if (strpos($imageUrl, '/content/uploads/') !== false) {
                $filePath = __DIR__ . '/../../' . ltrim($imageUrl, '/');
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // حذف السجل من قاعدة البيانات
            $stmt = $db->prepare("DELETE FROM lesson_image_notes WHERE id = ?");
            if ($stmt->execute([$imageId])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'تم حذف الصورة بنجاح'
                ]);
            } else {
                throw new Exception('فشل في حذف السجل من قاعدة البيانات');
            }
        } else {
            throw new Exception('الصورة غير موجودة');
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'فشل في حذف الصورة: ' . $e->getMessage()]);
    }
}
?> 