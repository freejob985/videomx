<?php
require_once 'includes/functions.php';

$language_id = $_GET['language_id'] ?? null;

// التحقق من وجود اللغة
if (!$language_id || !languageExists($language_id)) {
    $_SESSION['error'] = 'اللغة غير موجودة';
    header('Location: index.php');
    exit;
}

// جلب معلومات اللغة والإحصائيات
$language = getLanguageInfo($language_id);
$courses = getCoursesByLanguage($language_id);
$stats = getCourseStats($language_id);
$pageTitle = 'دورات ' . $language['name'];

require_once 'includes/header.php';
?>

<!-- شريط التنقل العلوي -->
<div class="navigation-bar bg-light py-3 mb-4 border-bottom">
    <div class="container">
        <div class="row align-items-center">
            <!-- الأزرار على اليمين -->
            <div class="col-auto">
                <div class="d-flex gap-2">
                    <!-- زر العودة للغات -->
                    <a href="/content/index.php" class="btn btn-outline-primary">
                        <i class="fas fa-globe me-1"></i>
                        اللغات
                    </a>

                    <!-- زر العودة للغة الحالية -->
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
                    دورات <?php echo htmlspecialchars($language['name']); ?>
                </h4>
            </div>

            
            <!-- زر الإعدادات على اليسار -->
            <div class="col-auto">
                <a href="/add/add.php" class="btn btn-outline-primary">
                    <i class="fas fa-cog me-1"></i>
                    الإعدادات والإضافات
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <!-- إحصائيات اللغة -->
    <div class="stats-header mb-5">
        <div class="row">
            <div class="col-md-6">
                <h1 class="mb-4">
                    <i class="fas fa-book me-2"></i>
                    دورات <?php echo htmlspecialchars($language['name']); ?>
                </h1>
            </div>
            <div class="col-md-6">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h3><?php echo number_format($stats['general_stats']['total_courses']); ?></h3>
                                <p class="mb-0">الدورات</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3><?php echo number_format($stats['general_stats']['total_lessons']); ?></h3>
                                <p class="mb-0">الدروس</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائيات الحالات -->
        <div class="status-stats mt-4">
            <h5 class="mb-3">إحصائيات الدروس حسب الحالة</h5>
            <div class="row g-3">
                <?php foreach ($stats['status_stats'] as $status): ?>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo htmlspecialchars($status['status_name']); ?></h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-info"><?php echo number_format($status['lessons_count']); ?> درس</span>
                                    <span class="text-muted"><?php echo formatDuration($status['total_duration']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- قائمة الكورسات -->
    <div class="row g-4">
        <?php foreach ($courses as $course): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <?php if ($course['thumbnail']): ?>
                        <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($course['title']); ?>">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                        
                        <div class="course-description">
                            <?php 
                            $description = $course['description'];
                            $shortDesc = mb_substr($description, 0, 100);
                            $hasMore = mb_strlen($description) > 100;
                            ?>
                            <p class="card-text">
                                <span class="short-desc"><?php echo htmlspecialchars($shortDesc); ?></span>
                                <?php if ($hasMore): ?>
                                    <span class="full-desc d-none"><?php echo htmlspecialchars($description); ?></span>
                                    <a href="#" class="toggle-desc text-primary" data-show-more="true">المزيد...</a>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary"><?php echo $course['lessons_count']; ?> درس</span>
                                <span class="badge bg-info"><?php echo formatDuration($course['total_duration']); ?></span>
                            </div>
                            
                            <button class="btn btn-primary w-100" 
                                    onclick="loadLessons(<?php echo $course['id']; ?>)">
                                <i class="fas fa-play me-1"></i>
                                عرض الدروس
                            </button>
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
        const card = this.closest('.course-description');
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

<?php require_once 'includes/footer.php'; ?> 