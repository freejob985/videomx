<?php
require_once '../includes/functions.php';

$course_id = $_GET['course_id'] ?? null;
if (!$course_id) {
    $_SESSION['error'] = 'معرف الكورس مطلوب';
    header('Location: ../index.php');
    exit;
}

// جلب معلومات الكورس والدروس والحالات
$course = getCourseInfo($course_id);
if (!$course) {
    $_SESSION['error'] = 'الكورس غير موجود';
    header('Location: ../index.php');
    exit;
}

// جلب الدروس مع التحقق من البيانات
$lessonsData = getLessonsByCourse($course_id);
$lessons = $lessonsData['lessons'] ?? [];

// جلب الحالات المتاحة
$statuses = getStatusesByLanguage($course['language_id']);

$pageTitle = 'عرض الدروس - ' . htmlspecialchars($course['title']);

// استخدام الهيدر الجديد
require_once 'includes/lessons-header.php';

// إضافة قسم إحصائيات الدروس
try {
    $lessonsStats = getCourseLessonsStats($course_id);
} catch (Exception $e) {
    $lessonsStats = [
        'total_lessons' => 0,
        'completed_lessons' => 0,
        'remaining_lessons' => 0,
        'total_duration' => 0,
        'completed_duration' => 0,
        'remaining_duration' => 0
    ];
    error_log("Error getting lessons stats: " . $e->getMessage());
}
?>

<!-- تضمين الملفات CSS -->
<link rel="stylesheet" href="../assets/css/lessons.css">
<link rel="stylesheet" href="../assets/css/lesson-card.css?v=1.0">

<div class="container py-5">
    <!-- قسم الإحصائيات -->
    <div class="stats-section mb-4">
        <div class="stats-header d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">
                <i class="fas fa-chart-pie me-2"></i>
                إحصائيات الدروس
            </h5>
            <button class="btn btn-light btn-sm toggle-stats" title="إخفاء/إظهار الإحصائيات">
                <i class="fas fa-chevron-up"></i>
            </button>
        </div>
        
        <div class="stats-content">
            <!-- البروجرس بار -->
            <div class="course-progress mb-4">
                <h6 class="mb-2">تقدم الدروس</h6>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
            </div>

            <!-- إحصائيات الحالات -->
            <div class="status-stats mb-4">
                <div class="row g-3">
                    <?php foreach ($statuses as $status): ?>
                        <div class="col-md-3 col-sm-6">
                            <div class="card h-100" data-status-id="<?php echo $status['id']; ?>">
                                <div class="card-body">
                                    <h6 class="card-title d-flex justify-content-between">
                                        <span><?php echo htmlspecialchars($status['name']); ?></span>
                                        <span class="badge bg-secondary status-count">0</span>
                                    </h6>
                                    <div class="progress mt-2" style="height: 6px;">
                                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- إحصائيات الدروس -->
            <div class="course-stats">
                <!-- الصف الأول -->
                <div class="row g-3 mb-3">
                    <!-- إجمالي الدروس -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">إجمالي الدروس</h6>
                                <div class="d-flex align-items-center">
                                    <div class="icon-wrapper">
                                        <i class="fas fa-list text-primary"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3 class="stats-total-lessons mb-0">0 درس</h3>
                                        <small class="text-muted">إجمالي عدد الدروس</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- الدروس المكتملة -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">الدروس المكتملة</h6>
                                <div class="d-flex align-items-center">
                                    <div class="icon-wrapper">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3 class="stats-completed-lessons mb-0">0 درس</h3>
                                        <small class="text-muted">تم إكمالها</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- الدروس المتبقية -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">الدروس المتبقية</h6>
                                <div class="d-flex align-items-center">
                                    <div class="icon-wrapper">
                                        <i class="fas fa-hourglass-half text-warning"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3 class="stats-remaining-lessons mb-0">0 درس</h3>
                                        <small class="text-muted">متبقية للإكمال</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- الصف الثاني -->
                <div class="row g-3">
                    <!-- وقت الدروس المكتملة -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">وقت الدروس المكتملة</h6>
                                <div class="d-flex align-items-center">
                                    <div class="icon-wrapper">
                                        <i class="fas fa-clock text-success"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3 class="stats-completed-duration mb-0">0:00</h3>
                                        <small class="text-muted">الوقت المكتمل</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- وقت الدروس المتبقية -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">وقت الدروس المتبقية</h6>
                                <div class="d-flex align-items-center">
                                    <div class="icon-wrapper">
                                        <i class="fas fa-clock text-warning"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3 class="stats-remaining-duration mb-0">0:00</h3>
                                        <small class="text-muted">الوقت المتبقي</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- الدروس المهمة -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">الدروس المهمة</h6>
                                <div class="d-flex align-items-center">
                                    <div class="icon-wrapper">
                                        <i class="fas fa-star text-warning"></i>
                                    </div>
                                    <div class="stats-info">
                                        <h3 class="stats-important-lessons mb-0">0 درس</h3>
                                        <small class="stats-completed-important text-muted">(0 مكتمل)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- عرض الدروس بتصميم الكروت -->
    <div class="row g-4" id="lessons-container">
        <?php if (empty($lessons)): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    لا توجد دروس متاحة لهذا الكورس
                </div>
            </div>
        <?php else: ?>
            <?php 
            // ترتيب الدروس حسب الترتيب
            usort($lessons, function($a, $b) {
                return ($a['order_number'] ?? 0) - ($b['order_number'] ?? 0);
            });
            
            foreach ($lessons as $lesson): 
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card lesson-card h-100 <?php echo $lesson['is_theory'] ? 'theory' : ''; ?> <?php echo $lesson['is_important'] ? 'important' : ''; ?>"
                         data-lesson-id="<?php echo $lesson['id']; ?>"
                         data-video-url="<?php echo htmlspecialchars($lesson['video_url'] ?? ''); ?>">
                        <!-- صورة مصغرة للدرس -->
                        <?php if ($lesson['thumbnail']): ?>
                        <div class="card-img-wrapper position-relative">
                            <img src="<?php echo htmlspecialchars($lesson['thumbnail']); ?>" 
                                 class="card-img-top"
                                 alt="<?php echo htmlspecialchars($lesson['title']); ?>"
                                 loading="lazy"
                                 onerror="this.src='../assets/images/default-lesson.jpg'">
                            <?php if ($lesson['video_url']): ?>
                            <div class="play-icon">
                                <i class="fas fa-play-circle fa-3x"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- رأس الكارد -->
                        <div class="card-header bg-transparent border-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title mb-0">
                                    <?php echo htmlspecialchars($lesson['title'] ?? 'بدون عنوان'); ?>
                                </h5>
                                <?php if ($lesson['order_number']): ?>
                                <span class="badge bg-secondary">
                                    الدرس <?php echo $lesson['order_number']; ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- محتوى الكارد -->
                        <div class="card-body">
                            <div class="lesson-meta">
                                <!-- معلومات الدرس -->
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="badge bg-info">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo formatDuration($lesson['duration'] ?? 0); ?>
                                    </span>
                                    <?php if ($lesson['tags']): ?>
                                    <div class="tags-wrapper">
                                        <?php 
                                        $tags = explode(',', $lesson['tags']);
                                        $tagColors = ['primary', 'success', 'info', 'warning', 'danger'];
                                        foreach ($tags as $index => $tag): 
                                            $color = $tagColors[$index % count($tagColors)];
                                        ?>
                                            <span class="tag tag-<?php echo $color; ?>">
                                                <i class="fas fa-tag"></i>
                                                <?php echo htmlspecialchars(trim($tag)); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- حالة الدرس -->
                                <div class="status-wrapper mb-3">
                                    <select class="form-select status-select" 
                                            data-lesson-id="<?php echo $lesson['id']; ?>"
                                            style="background-color: <?php echo $lesson['status_color'] ?? ''; ?>; 
                                                   color: <?php echo $lesson['status_text_color'] ?? ''; ?>">
                                        <option value="">اختر الحالة</option>
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?php echo $status['id']; ?>"
                                                    <?php echo ($lesson['status_id'] == $status['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($status['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- شارات الدرس -->
                                <div class="badges-wrapper d-flex gap-2">
                                    <?php if ($lesson['is_theory']): ?>
                                        <span class="badge bg-secondary theory-badge">
                                            <i class="fas fa-book me-1"></i>
                                            درس نظري
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($lesson['is_important']): ?>
                                        <span class="badge bg-warning important-badge">
                                            <i class="fas fa-star me-1"></i>
                                            درس مهم
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- تذييل الكارد -->
                        <div class="card-footer bg-transparent">
                            <div class="d-flex gap-2 justify-content-between">
                                <div class="btn-group">
                                    <button class="btn btn-sm importance-btn <?php echo $lesson['is_important'] ? 'btn-warning' : 'btn-outline-warning'; ?>"
                                            title="تحديد كدرس مهم">
                                        <i class="fas fa-star"></i>
                                    </button>
                                    <button class="btn btn-sm theory-btn <?php echo $lesson['is_theory'] ? 'btn-info' : 'btn-outline-info'; ?>"
                                            title="تحديد كدرس نظري">
                                        <i class="fas fa-book"></i>
                                    </button>
                                    <button class="btn btn-sm completion-btn <?php echo $lesson['completed'] ? 'btn-success' : 'btn-outline-success'; ?>"
                                            data-lesson-id="<?php echo $lesson['id']; ?>"
                                            data-completed="<?php echo $lesson['completed']; ?>"
                                            title="تحديد كدرس مكتمل">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </div>
                                <a href="lesson-details.php?id=<?php echo $lesson['id']; ?>" 
                                   class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>
                                    التفاصيل
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// دالة تحديث الإحصائيات
async function updateCourseStats() {
    try {
        const courseId = <?php echo $course_id; ?>;
        const response = await fetch(`../api/get-course-stats.php?course_id=${courseId}`);
        const data = await response.json();
        
        if (data.success) {
            const stats = data.stats;
            
            // تحديث الأرقام الإحصائية
            document.querySelector('.stats-total-lessons').textContent = `${stats.total} درس`;
            document.querySelector('.stats-completed-lessons').textContent = `${stats.completed} درس`;
            document.querySelector('.stats-remaining-lessons').textContent = `${stats.remaining} درس`;
            document.querySelector('.stats-important-lessons').textContent = `${stats.important} درس`;
            
            // تحديث المدد الزمنية
            document.querySelector('.stats-completed-duration').textContent = formatDuration(stats.completed_duration);
            document.querySelector('.stats-remaining-duration').textContent = formatDuration(stats.remaining_duration);
            
            // تحديث شريط التقدم
            const progressBar = document.querySelector('.course-progress .progress-bar');
            progressBar.style.width = `${stats.completion_percentage}%`;
            progressBar.textContent = `${stats.completion_percentage}%`;
            
            // تحديث إحصائيات الحالات
            stats.status_counts.forEach(status => {
                const statusCard = document.querySelector(`.status-stats .card[data-status-id="${status.id}"]`);
                if (statusCard) {
                    statusCard.querySelector('.status-count').textContent = status.count;
                    const percentage = (status.count / stats.total) * 100;
                    statusCard.querySelector('.progress-bar').style.width = `${percentage}%`;
                }
            });
        }
    } catch (error) {
        console.error('Error updating stats:', error);
        toastr.error('حدث خطأ أثناء تحديث الإحصائيات');
    }
}

// دالة تنسيق المدة الزمنية
function formatDuration(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    return `${hours}:${minutes.toString().padStart(2, '0')}`;
}

// تحديث الإحصائيات عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    updateCourseStats();
    
    // تحديث الإحصائيات عند تغيير حالة الدرس
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            updateCourseStats();
        });
    });
    
    // تحديث الإحصائيات عند تغيير حالة الإكمال
    document.querySelectorAll('.completion-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            updateCourseStats();
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // استعادة حالة الإخفاء/الإظهار من localStorage
    const statsSection = document.querySelector('.stats-section');
    const isCollapsed = localStorage.getItem('statsCollapsed') === 'true';
    if (isCollapsed) {
        statsSection.classList.add('collapsed');
    }

    // إضافة مستمع لزر الإخفاء/الإظهار
    document.querySelector('.toggle-stats').addEventListener('click', function() {
        statsSection.classList.toggle('collapsed');
        // حفظ الحالة في localStorage
        localStorage.setItem('statsCollapsed', statsSection.classList.contains('collapsed'));
    });
});

// دالة تحديث عرض الدروس
function updateLessonsDisplay() {
    const container = document.getElementById('lessons-container');
    const cards = container.querySelectorAll('.lesson-card');
    
    if (cards.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-info">
                    لا توجد دروس متاحة لهذا الكورس
                </div>
            </div>
        `;
        return;
    }
    
    // تحديث الأرقام التسلسلية
    cards.forEach((card, index) => {
        const orderBadge = card.querySelector('.badge.bg-secondary');
        if (orderBadge) {
            orderBadge.textContent = `الدرس ${index + 1}`;
        }
    });
}

// تحديث العرض عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', updateLessonsDisplay);
</script>

<!-- مودال تشغيل الفيديو -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">مشاهدة الدرس</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="ratio ratio-16x9">
                    <iframe id="videoPlayer" 
                            src="" 
                            allowfullscreen 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
                    </iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- تضمين الملفات -->
<link rel="stylesheet" href="../assets/css/lesson-card.css?v=1.0">
<script src="../assets/js/lesson-card.js?v=1.0"></script>

<?php require_once '../includes/footer.php'; ?>

<style>
.stats-section {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 1.5rem;
}

.stats-header {
    border-bottom: 1px solid #eee;
    padding-bottom: 1rem;
}

.stats-header h5 {
    color: #333;
    font-weight: 600;
}

.toggle-stats {
    width: 35px;
    height: 35px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.toggle-stats:hover {
    background: #f8f9fa;
}

.toggle-stats i {
    transition: transform 0.3s ease;
}

.stats-section.collapsed .toggle-stats i {
    transform: rotate(180deg);
}

.stats-content {
    transition: all 0.3s ease;
    overflow: hidden;
}

.stats-section.collapsed .stats-content {
    height: 0;
    padding: 0;
    margin: 0;
}
</style>

