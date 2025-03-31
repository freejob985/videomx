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

// إضافة ملفات القائمة السياقية

?>
<link rel="stylesheet" href="/assets/css/contextMenu.css">
<script src="/assets/js/contextMenu.js" defer></script>

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

            <!-- إضافة زر الأقسام -->
            <div class="col-auto">
                <a href="/sections/index.php?language_id=<?php echo (int)$language_id; ?>" class="btn btn-outline-primary">
                    <i class="fas fa-list-alt me-1"></i>
                    الأقسام
                </a>
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
    <!-- قسم الإحصائيات الرئيسية -->
    <div class="statistics-section py-4 mb-5">
        <div class="container">
            <!-- العنوان الرئيسي -->
            <div class="section-header text-center mb-5">
                <h2 class="display-6 fw-bold text-primary">
                    <i class="fas fa-chart-line me-2"></i>
                    إحصائيات <?php echo htmlspecialchars($language['name']); ?>
                </h2>
                <div class="divider mx-auto my-3"></div>
            </div>

            <!-- الإحصائيات الرئيسية -->
            <div class="main-stats mb-5">
                <div class="row g-4">
                    <!-- إجمالي الدورات -->
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card primary-gradient h-100">
                            <div class="stat-icon">
                                <i class="fas fa-books fa-2x"></i>
                            </div>
                            <div class="stat-info">
                                <h3 class="stat-value"><?php echo number_format($stats['general_stats']['total_courses']); ?></h3>
                                <p class="stat-label">إجمالي الدورات</p>
                            </div>
                        </div>
                    </div>

                    <!-- إجمالي الدروس -->
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card success-gradient h-100">
                            <div class="stat-icon">
                                <i class="fas fa-graduation-cap fa-2x"></i>
                            </div>
                            <div class="stat-info">
                                <h3 class="stat-value"><?php echo number_format($stats['general_stats']['total_lessons']); ?></h3>
                                <p class="stat-label">إجمالي الدروس</p>
                            </div>
                        </div>
                    </div>

                    <!-- الدروس المكتملة -->
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card info-gradient h-100">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                            <div class="stat-info">
                                <h3 class="stat-value"><?php echo number_format($stats['general_stats']['completed_lessons']); ?></h3>
                                <p class="stat-label">الدروس المكتملة</p>
                            </div>
                        </div>
                    </div>

                    <!-- الدروس المتبقية -->
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card warning-gradient h-100">
                            <div class="stat-icon">
                                <i class="fas fa-hourglass-half fa-2x"></i>
                            </div>
                            <div class="stat-info">
                                <h3 class="stat-value"><?php echo number_format($stats['general_stats']['remaining_lessons']); ?></h3>
                                <p class="stat-label">الدروس المتبقية</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- إحصائيات الوقت -->
            <div class="time-stats mb-5">
                <h3 class="section-title mb-4">
                    <i class="fas fa-clock text-primary me-2"></i>
                    إحصائيات الوقت
                </h3>
                <div class="row g-4">
                    <!-- الوقت الكلي -->
                    <div class="col-lg-4">
                        <div class="time-card total-time h-100">
                            <div class="time-info">
                                <div class="time-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <h4 class="time-value"><?php echo formatDuration($stats['general_stats']['total_duration']); ?></h4>
                                <p class="time-label">الوقت الكلي</p>
                            </div>
                        </div>
                    </div>

                    <!-- الوقت المكتمل -->
                    <div class="col-lg-4">
                        <div class="time-card completed-time h-100">
                            <div class="time-info">
                                <div class="time-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h4 class="time-value"><?php echo formatDuration($stats['general_stats']['completed_duration']); ?></h4>
                                <p class="time-label">الوقت المكتمل</p>
                            </div>
                        </div>
                    </div>

                    <!-- الوقت المتبقي -->
                    <div class="col-lg-4">
                        <div class="time-card remaining-time h-100">
                            <div class="time-info">
                                <div class="time-icon">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <h4 class="time-value"><?php echo formatDuration($stats['general_stats']['remaining_duration']); ?></h4>
                                <p class="time-label">الوقت المتبقي</p>
                            </div>
                        </div>
                    </div>
                </div>
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

    <!-- إحصائيات الحالات -->
    <div class="status-stats mb-5">
        <div class="container">
            <h3 class="section-title mb-4">
                <i class="fas fa-tasks text-primary me-2"></i>
                إحصائيات الدروس حسب الحالة
            </h3>
            <div class="row g-4">
                <?php foreach ($stats['status_stats'] as $status): ?>
                    <div class="col-md-4">
                        <div class="status-card h-100">
                            <div class="status-header" style="background-color: <?php echo $status['color'] ?? '#6c757d'; ?>">
                                <h5 class="status-title">
                                    <?php echo htmlspecialchars($status['status_name']); ?>
                                </h5>
                            </div>
                            <div class="status-body">
                                <div class="status-item">
                                    <i class="fas fa-book-open"></i>
                                    <div class="status-details">
                                        <span class="status-value"><?php echo number_format($status['lessons_count']); ?></span>
                                        <span class="status-label">درس</span>
                                    </div>
                                </div>
                                <div class="status-item">
                                    <i class="fas fa-clock"></i>
                                    <div class="status-details">
                                        <span class="status-value"><?php echo formatDuration($status['total_duration']); ?></span>
                                        <span class="status-label">المدة الزمنية</span>
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

<!-- إضافة CSS مخصص -->
<style>
/* التنسيقات العامة */
.statistics-section {
    background-color: #f8f9fa;
    border-radius: 15px;
    padding: 2rem 0;
}

.divider {
    height: 4px;
    width: 60px;
    background: linear-gradient(90deg, #007bff, #6610f2);
    border-radius: 2px;
}

/* تنسيقات البطاقات الإحصائية */
.stat-card {
    padding: 1.5rem;
    border-radius: 15px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.stat-icon {
    position: absolute;
    top: 20px;
    right: 20px;
    opacity: 0.2;
    transition: all 0.3s ease;
}

.stat-card:hover .stat-icon {
    transform: scale(1.2);
    opacity: 0.3;
}

.stat-info {
    position: relative;
    z-index: 1;
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #fff;
}

.stat-label {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.9);
    margin: 0;
}

/* التدرجات اللونية */
.primary-gradient {
    background: linear-gradient(135deg, #2196F3, #1976D2);
}

.success-gradient {
    background: linear-gradient(135deg, #4CAF50, #388E3C);
}

.info-gradient {
    background: linear-gradient(135deg, #00BCD4, #0097A7);
}

.warning-gradient {
    background: linear-gradient(135deg, #FFC107, #FFA000);
}

/* تنسيقات بطاقات الوقت */
.time-card {
    padding: 2rem;
    border-radius: 15px;
    text-align: center;
    background: #fff;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.time-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.time-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.time-value {
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.time-label {
    color: #6c757d;
    margin: 0;
}

/* تنسيقات خاصة لكل نوع وقت */
.total-time .time-icon {
    color: #2196F3;
}

.completed-time .time-icon {
    color: #4CAF50;
}

.remaining-time .time-icon {
    color: #FFC107;
}

/* تحسينات للتجاوب */
@media (max-width: 768px) {
    .stat-value {
        font-size: 2rem;
    }
    
    .time-value {
        font-size: 1.5rem;
    }
    
    .section-title {
        font-size: 1.5rem;
    }
}

/* تنسيقات بطاقات الحالة */
.status-card {
    background: #fff;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.status-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.status-header {
    padding: 1.5rem;
    color: white;
    text-align: center;
}

.status-title {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
}

.status-body {
    padding: 1.5rem;
}

.status-item {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.status-item:last-child {
    margin-bottom: 0;
}

.status-item i {
    font-size: 1.5rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #f8f9fa;
    color: #6c757d;
    margin-left: 1rem;
}

.status-details {
    flex: 1;
}

.status-value {
    display: block;
    font-size: 1.2rem;
    font-weight: 600;
    color: #2c3e50;
}

.status-label {
    display: block;
    font-size: 0.9rem;
    color: #6c757d;
}

/* تحسين العنوان */
.section-title {
    position: relative;
    padding-bottom: 0.5rem;
    margin-bottom: 2rem;
}

.section-title:after {
    content: '';
    position: absolute;
    bottom: 0;
    right: 0;
    width: 50px;
    height: 3px;
    background: linear-gradient(90deg, #007bff, #6610f2);
    border-radius: 2px;
}

/* تحسينات للتجاوب */
@media (max-width: 768px) {
    .status-card {
        margin-bottom: 1rem;
    }
    
    .status-value {
        font-size: 1.1rem;
    }
    
    .status-item i {
        font-size: 1.2rem;
        width: 35px;
        height: 35px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 