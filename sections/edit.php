<?php
require_once '../includes/functions.php';
require_once '../includes/sections_functions.php';

$section_id = $_GET['section_id'] ?? null;

// التحقق من وجود القسم
if (!$section_id || !sectionExists($section_id)) {
    $_SESSION['error'] = 'القسم غير موجود';
    header('Location: /content/index.php');
    exit;
}

$section = getSectionInfo($section_id);
$language = getLanguageInfo($section['language_id']);
$pageTitle = 'تعديل قسم ' . $section['name'];

// معالجة تحديث القسم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (updateSection($section_id, $name, $description)) {
        $_SESSION['success'] = 'تم تحديث القسم بنجاح';
        header("Location: /sections/index.php?language_id={$section['language_id']}");
        exit;
    } else {
        $_SESSION['error'] = 'حدث خطأ أثناء تحديث القسم';
    }
}

require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">تعديل القسم</h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">اسم القسم</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   value="<?php echo htmlspecialchars($section['name']); ?>" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">وصف القسم</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="4"><?php echo htmlspecialchars($section['description']); ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                حفظ التغييرات
                            </button>
                            
                            <a href="/sections/index.php?language_id=<?php echo $section['language_id']; ?>" 
                               class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>
                                إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 