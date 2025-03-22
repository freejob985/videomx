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

<!-- إضافة CSS مخصص للتصميم المادي -->
<style>
/* إضافة أنماط جديدة لتثبيت الفوتر */
html, body {
    height: 100%;
    margin: 0;
}

body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.main-content {
    flex: 1 0 auto;
}

footer {
    flex-shrink: 0;
}

.material-gradient {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    color: white;
}

.section-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: none;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.section-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.navigation-bar {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.btn-material {
    border-radius: 8px;
    text-transform: uppercase;
    font-weight: 500;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}

.btn-material:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.section-title {
    color: #1e293b;
    font-weight: 600;
    margin-bottom: 1rem;
}

.section-description {
    color: #64748b;
    line-height: 1.6;
}

.badge-material {
    padding: 8px 12px;
    border-radius: 6px;
    font-weight: 500;
}

.card-actions {
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
}
</style>

<!-- شريط التنقل العلوي مع تصميم مادي -->
<div class="main-content">
    <div class="navigation-bar py-4 mb-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="d-flex gap-3">
                        <a href="/content/index.php" class="btn btn-material btn-light">
                            <i class="fas fa-globe me-2"></i>
                            اللغات
                        </a>
                        <a href="/content/courses.php?language_id=<?php echo (int)$language_id; ?>" 
                           class="btn btn-material btn-light">
                            <i class="fas fa-arrow-right me-2"></i>
                            <?php echo htmlspecialchars($language['name']); ?>
                        </a>
                    </div>
                </div>

                <div class="col text-center">
                    <h4 class="mb-0 section-title">
                        <i class="fas fa-layer-group me-2"></i>
                        أقسام <?php echo htmlspecialchars($language['name']); ?>
                    </h4>
                </div>

                <div class="col-auto">
                    <a href="/sections/add.php?language_id=<?php echo (int)$language_id; ?>" 
                       class="btn btn-material btn-success">
                        <i class="fas fa-plus me-2"></i>
                        إضافة قسم جديد
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <div class="row g-4">
            <?php foreach ($sections as $section): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card section-card h-100">
                        <div class="card-body">
                            <h5 class="card-title section-title">
                                <i class="fas fa-book-open me-2"></i>
                                <?php echo htmlspecialchars($section['name']); ?>
                            </h5>
                            
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
                                        <a href="#" class="toggle-desc text-primary text-decoration-none" data-show-more="true">
                                            <small>المزيد...</small>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="card-actions">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge badge-material bg-primary">
                                        <i class="fas fa-book me-1"></i>
                                        <?php echo $section['lessons_count']; ?> درس
                                    </span>
                                    <span class="badge badge-material bg-info">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo formatDuration($section['total_duration']); ?>
                                    </span>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <a href="/sections/lessons.php?section_id=<?php echo $section['id']; ?>" 
                                       class="btn btn-material btn-primary flex-grow-1">
                                        <i class="fas fa-play me-2"></i>
                                        عرض الدروس
                                    </a>
                                    <a href="/sections/edit.php?section_id=<?php echo $section['id']; ?>" 
                                       class="btn btn-material btn-outline-secondary">
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