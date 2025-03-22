<?php
require_once '../includes/functions.php';
require_once '../includes/sections_functions.php';

$language_id = $_GET['language_id'] ?? null;

// التحقق من وجود اللغة
if (!$language_id || !languageExists($language_id)) {
    $_SESSION['error'] = 'اللغة غير موجودة';
    header('Location: /content/index.php');
    exit;
}

// جلب معلومات اللغة والأقسام
$language = getLanguageInfo($language_id);
$sections = getSectionsByLanguage($language_id);
$pageTitle = 'أقسام ' . $language['name'];

require_once '../includes/header.php';
?>

<!-- شريط التنقل العلوي -->
<div class="navigation-bar bg-light py-3 mb-4 border-bottom">
    <div class="container">
        <div class="row align-items-center">
            <!-- الأزرار على اليمين -->
            <div class="col-auto">
                <div class="d-flex gap-2">
                    <a href="/content/index.php" class="btn btn-outline-primary">
                        <i class="fas fa-globe me-1"></i>
                        اللغات
                    </a>
                    <a href="/content/courses.php?language_id=<?php echo (int)$language_id; ?>" 
                       class="btn btn-outline-primary">
                        <i class="fas fa-arrow-right me-1"></i>
                        <?php echo htmlspecialchars($language['name']); ?>
                    </a>
                </div>
            </div>

            <!-- عنوان الصفحة في المنتصف -->
            <div class="col text-center">
                <h4 class="mb-0 text-primary">
                    أقسام <?php echo htmlspecialchars($language['name']); ?>
                </h4>
            </div>

            <!-- زر إضافة قسم جديد -->
            <div class="col-auto">
                <a href="/sections/add.php?language_id=<?php echo (int)$language_id; ?>" 
                   class="btn btn-outline-success">
                    <i class="fas fa-plus me-1"></i>
                    إضافة قسم جديد
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <!-- قائمة الأقسام -->
    <div class="row g-4">
        <?php foreach ($sections as $section): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($section['name']); ?></h5>
                        
                        <div class="section-description">
                            <?php 
                            $description = $section['description'];
                            $shortDesc = strip_tags(mb_substr($description, 0, 100));
                            $hasMore = mb_strlen(strip_tags($description)) > 100;
                            ?>
                            <div class="description-content">
                                <div class="short-desc"><?php echo $shortDesc; ?></div>
                                <?php if ($hasMore): ?>
                                    <div class="full-desc d-none"><?php echo $description; ?></div>
                                    <a href="#" class="toggle-desc text-primary" data-show-more="true">المزيد...</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary"><?php echo $section['lessons_count']; ?> درس</span>
                                <span class="badge bg-info"><?php echo formatDuration($section['total_duration']); ?></span>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <a href="/sections/lessons.php?section_id=<?php echo $section['id']; ?>" 
                                   class="btn btn-primary flex-grow-1">
                                    <i class="fas fa-play me-1"></i>
                                    عرض الدروس
                                </a>
                                <a href="/sections/edit.php?section_id=<?php echo $section['id']; ?>" 
                                   class="btn btn-outline-secondary">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// التبديل بين الوصف المختصر والكامل
document.querySelectorAll('.toggle-desc').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const card = this.closest('.section-description');
        const shortDesc = card.querySelector('.short-desc');
        const fullDesc = card.querySelector('.full-desc');
        const showMore = this.getAttribute('data-show-more') === 'true';
        
        if (showMore) {
            shortDesc.classList.add('d-none');
            fullDesc.classList.remove('d-none');
            this.textContent = 'أقل...';
            this.setAttribute('data-show-more', 'false');
        } else {
            shortDesc.classList.remove('d-none');
            fullDesc.classList.add('d-none');
            this.textContent = 'المزيد...';
            this.setAttribute('data-show-more', 'true');
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 