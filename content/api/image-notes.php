<?php
// إضافة هذه الأسطر في بداية الملف
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: X-Requested-With');

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

// التعامل مع الطلبات
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        handleImagesList();
        break;
    case 'upload':
        handleImageUpload();
        break;
    case 'update':
        handleImageUpdate();
        break;
    case 'delete':
        handleImageDelete();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'إجراء غير صالح']);
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

// معالجة رفع الصور
function handleImageUpload() {
    global $uploadDir;
    
    $lessonId = $_POST['lesson_id'] ?? null;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $externalUrl = $_POST['external_url'] ?? '';
    
    // التعامل مع الروابط الخارجية
    if ($externalUrl) {
        if (filter_var($externalUrl, FILTER_VALIDATE_URL)) {
            if (addLessonImage($lessonId, $title, $description, $externalUrl)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'تم إضافة الصورة الخارجية بنجاح',
                    'image_url' => $externalUrl
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'فشل في حفظ الصورة الخارجية']);
            }
            return;
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'رابط الصورة غير صالح']);
            return;
        }
    }

    // التعامل مع الملفات المرفوعة
    if (!isset($_FILES['image'])) {
        http_response_code(400);
        echo json_encode(['error' => 'لم يتم تحديد صورة']);
        return;
    }

    $file = $_FILES['image'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'نوع الملف غير مدعوم']);
        return;
    }

    // التحقق من حجم الملف (5MB كحد أقصى)
    if ($file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'حجم الملف كبير جداً']);
        return;
    }

    $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', basename($file['name']));
    $uploadFile = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
        $imageUrl = '/content/uploads/lesson-images/' . $fileName;
        
        if (addLessonImage($lessonId, $title, $description, $imageUrl)) {
            echo json_encode([
                'success' => true,
                'message' => 'تم رفع الصورة بنجاح',
                'image_url' => $imageUrl
            ]);
        } else {
            unlink($uploadFile); // حذف الملف إذا فشل الحفظ في قاعدة البيانات
            http_response_code(500);
            echo json_encode(['error' => 'فشل في حفظ معلومات الصورة']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'فشل في رفع الصورة']);
    }
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