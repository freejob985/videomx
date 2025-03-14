<?php
require_once 'includes/functions.php';

$course_id = $_GET['course_id'] ?? null;
if (!$course_id) {
    $_SESSION['error'] = 'معرف الكورس مطلوب';
    header('Location: index.php');
    exit;
}

// جلب رقم الصفحة الحالية
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;

// جلب معلومات الكورس والدروس
$course = getCourseInfo($course_id);
$allLessons = getAllLessonsByCourse($course_id); // جلب جميع الدروس
$totalLessons = count($allLessons);
$lessonsData = getLessonsByCourse($course_id, $page, $perPage);
$lessons = $lessonsData['lessons'];
$sections = getSectionsByLanguage($course['language_id']);
$statuses = getStatusesByLanguage($course['language_id']);
$pageTitle = 'دروس ' . $course['title'];

// جلب إحصائيات الدروس مباشرة من قاعدة البيانات
$lessonsStats = getCourseLessonsStats($course_id);

// جلب إحصائيات الدروس للصفحة الحالية
$currentPageStats = [
    'start' => ($page - 1) * $perPage + 1,
    'end' => min($page * $perPage, $totalLessons)
];

// جلب إحصائيات الدروس للصفحة الحالية
$pageStats = getPageLessonsStats($course_id, $currentPageStats['start'] - 1, $perPage);

// جلب إحصائيات كامل الكورس
$fullStats = getFullCourseStats($course_id);

require_once 'includes/header.php';

// إضافة jQuery أولاً
echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';

// ثم باقي المكتبات
echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">';
echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>';

// إضافة تنسيقات Toastr
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">';
echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>';

// إضافة مكتبة Chart.js
echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

// إضافة Bootstrap Tags Input
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.css">';
echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.min.js"></script>';

?>
<style>
.bootstrap-tagsinput {
    width: 100%;
    padding: 8px;
    border-radius: 4px;
    box-shadow: none;
    border: 1px solid #ced4da;
    min-height: 46px;
}

.bootstrap-tagsinput input {
    width: auto;
    max-width: 100%;
}

.bootstrap-tagsinput .tag {
    background: #e9ecef;
    color: #495057;
    border-radius: 3px;
    padding: 4px 8px;
    margin: 2px;
    font-size: 0.875rem;
}

.bootstrap-tagsinput .tag [data-role="remove"] {
    margin-left: 8px;
    cursor: pointer;
}

.bootstrap-tagsinput .tag [data-role="remove"]:after {
    content: "×";
    padding: 0px 2px;
}

.bootstrap-tagsinput .tag [data-role="remove"]:hover {
    color: #dc3545;
}

html {
    scroll-behavior: smooth;
}

.quick-nav {
    position: sticky;
    top: 20px;
    z-index: 1000;
}

.quick-nav .dropdown-menu {
    max-height: 400px;
    overflow-y: auto;
}

.quick-nav .dropdown-item {
    padding: 0.5rem 1rem;
    color: #495057;
    transition: all 0.2s ease;
}

.quick-nav .dropdown-item:hover {
    background-color: #f8f9fa;
    color: #0d6efd;
}

.quick-nav .dropdown-item i {
    width: 20px;
    text-align: center;
}

/* إضافة padding للأقسام لتجنب تداخل العناوين مع التنقل */
section[id] {
    scroll-margin-top: 80px;
}

.mouse-menu {
    position: fixed;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1000;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.menu-toggle {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0d6efd;
    color: white;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.menu-toggle:hover {
    background: #0b5ed7;
    transform: scale(1.1);
}

.menu-items {
    display: none;
    flex-direction: column;
    gap: 10px;
    background: white;
    padding: 10px;
    border-radius: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.menu-item {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    color: #495057;
    border-radius: 50%;
    text-decoration: none;
    transition: all 0.3s ease;
}

.menu-item:hover {
    background: #e9ecef;
    color: #0d6efd;
    transform: scale(1.1);
}

.menu-item.active {
    background: #0d6efd;
    color: white;
}

/* تحريك القائمة للجانب الأيمن في النسخة العربية */
.rtl .mouse-menu {
    left: auto;
    right: 20px;
}

/* تحسين تأثيرات الحركة */
.menu-items.show {
    display: flex;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* تحسين تلميحات الأزرار */
.menu-item::after {
    content: attr(title);
    position: absolute;
    right: 50px;
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    white-space: nowrap;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.rtl .menu-item::after {
    right: auto;
    left: 50px;
}

.menu-item:hover::after {
    opacity: 1;
    visibility: visible;
}

/* إضافة تأثير دوران للأيقونة عند التبديل */
.menu-toggle i {
    transition: transform 0.3s ease;
}

.menu-toggle i.fa-times {
    transform: rotate(180deg);
}
</style>
<?php require_once 'css.php'; ?> 


<!-- إضافة مؤشر التحميل -->
<div class="loader" style="display: none;">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">جاري التحميل...</span>
    </div>
</div>



<!-- إعدادات Toastr -->
<script>
// تهيئة إعدادات Toastr
toastr.options = {
    "closeButton": true,
    "debug": false,
    "newestOnTop": true,
    "progressBar": true,
    "positionClass": "toast-top-left", // تغيير الموضع إلى اليسار للغة العربية
    "preventDuplicates": false,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "5000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut",
    "rtl": true // تفعيل الاتجاه من اليمين لليسار
};
</script>

<div class="container py-5">

    <!-- إحصائيات الدروس -->
    <div class="course-stats" id="courseStats">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="stats-title mb-0">
                <i class="fas fa-chart-line"></i>
                إحصائيات الدروس (الصفحة <?php echo $page; ?>)
            </h3>
            <!-- إضافة زر الإخفاء/الإظهار -->
            <button type="button" 
                    class="btn btn-link text-primary p-0" 
                    id="toggleStatsBtn" 
                    title="إخفاء/إظهار إحصائيات الدروس">
                <i class="fas fa-eye-slash"></i>
            </button>
        </div>
        
        <!-- إضافة div جديد لتغليف المحتوى القابل للإخفاء -->
        <div id="statsContent">
            <div class="stats-grid">
                <!-- إجمالي الدروس في الصفحة الحالية -->
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="total-lessons">
                            <?php echo $currentPageStats['end'] - $currentPageStats['start'] + 1; ?>
                        </div>
                        <div class="stat-label">إجمالي الدروس في الصفحة</div>
                    </div>
                </div>

                <!-- الدروس المكتملة في الصفحة الحالية -->
                <div class="stat-card">
                    <div class="stat-icon completed">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="completed-lessons">
                            <?php echo $pageStats['completed_count']; ?>
                        </div>
                        <div class="stat-label">الدروس المكتملة</div>
                    </div>
                </div>

                <!-- الدروس المتبقية في الصفحة الحالية -->
                <div class="stat-card">
                    <div class="stat-icon remaining-lessons">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="remaining-lessons">
                            <?php echo $pageStats['remaining_count']; ?>
                        </div>
                        <div class="stat-label">الدروس المتبقية</div>
                    </div>
                </div>

                <!-- الدروس المهمة في الصفحة الحالية -->
                <div class="stat-card">
                    <div class="stat-icon important">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="important-lessons">
                            <?php echo $pageStats['important_count']; ?>
                        </div>
                        <div class="stat-label">الدروس المهمة</div>
                    </div>
                </div>

                <!-- الدروس النظرية في الصفحة الحالية -->
                <div class="stat-card">
                    <div class="stat-icon theory">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="theory-lessons">
                            <?php echo $pageStats['theory_count']; ?>
                        </div>
                        <div class="stat-label">الدروس النظرية</div>
                    </div>
                </div>

                <!-- وقت الدروس المكتملة في الصفحة الحالية -->
                <div class="stat-card">
                    <div class="stat-icon completed-duration">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="completed-duration">
                            <?php echo formatDuration($pageStats['completed_duration']); ?>
                        </div>
                        <div class="stat-label">وقت الدروس المكتملة</div>
                    </div>
                </div>

                <!-- وقت الدروس المتبقية في الصفحة الحالية -->
                <div class="stat-card">
                    <div class="stat-icon remaining-duration">
                        <i class="fas fa-hourglass-start"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="remaining-duration">
                            <?php echo formatDuration($pageStats['remaining_duration']); ?>
                        </div>
                        <div class="stat-label">وقت الدروس المتبقية</div>
                    </div>
                </div>

                <!-- الدروس بدون حالة في الصفحة الحالية -->
                <div class="stat-card">
                    <div class="stat-icon no-status">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="no-status-lessons">
                            <?php echo $pageStats['no_status_count']; ?>
                        </div>
                        <div class="stat-label">دروس بدون حالة</div>
                    </div>
                </div>
            </div>

            <!-- شريط التقدم للصفحة الحالية -->
            <div class="progress-section">
                <div class="progress-header">
                    <span class="progress-title">نسبة الإنجاز في الصفحة الحالية</span>
                    <span class="progress-percentage" id="completion-percentage">
                        <?php 
                        $pageCompletionPercentage = $pageStats['total_count'] > 0 ? 
                            round(($pageStats['completed_count'] / $pageStats['total_count']) * 100) : 0;
                        echo $pageCompletionPercentage . '%';
                        ?>
                    </span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" 
                         id="completion-progress" 
                         style="width: <?php echo $pageCompletionPercentage; ?>%">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- إحصائيات كامل الكورس -->
    <div class="course-stats mb-4" id="fullCourseStats">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="stats-title mb-0">
                <i class="fas fa-chart-line"></i>
                إحصائيات كامل الكورس
            </h3>
            <!-- إضافة زر الإخفاء/الإظهار -->
            <button type="button" 
                    class="btn btn-link text-primary p-0" 
                    id="toggleFullCourseStatsBtn" 
                    title="إخفاء/إظهار إحصائيات كامل الكورس">
                <i class="fas fa-eye-slash"></i>
            </button>
        </div>
        
        <div id="fullCourseStatsContent">
            <div class="stats-grid">
                <!-- إجمالي الدروس -->
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="full-total-lessons">
                            <?php echo $fullStats['total_count']; ?>
                        </div>
                        <div class="stat-label">إجمالي الدروس</div>
                    </div>
                </div>

                <!-- الدروس المكتملة -->
                <div class="stat-card">
                    <div class="stat-icon completed">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="full-completed-lessons">
                            <?php echo $fullStats['completed_count']; ?>
                        </div>
                        <div class="stat-label">الدروس المكتملة</div>
                    </div>
                </div>

                <!-- الدروس المتبقية -->
                <div class="stat-card">
                    <div class="stat-icon remaining-lessons">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="full-remaining-lessons">
                            <?php echo $fullStats['remaining_count']; ?>
                        </div>
                        <div class="stat-label">الدروس المتبقية</div>
                    </div>
                </div>

                <!-- الدروس المهمة -->
                <div class="stat-card">
                    <div class="stat-icon important">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="full-important-lessons">
                            <?php echo $fullStats['important_count']; ?>
                        </div>
                        <div class="stat-label">الدروس المهمة</div>
                    </div>
                </div>

                <!-- الدروس النظرية -->
                <div class="stat-card">
                    <div class="stat-icon theory">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="full-theory-lessons">
                            <?php echo $fullStats['theory_count']; ?>
                        </div>
                        <div class="stat-label">الدروس النظرية</div>
                    </div>
                </div>

                <!-- وقت الدروس المكتملة -->
                <div class="stat-card">
                    <div class="stat-icon completed-duration">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="full-completed-duration">
                            <?php echo formatDuration($fullStats['completed_duration']); ?>
                        </div>
                        <div class="stat-label">وقت الدروس المكتملة</div>
                    </div>
                </div>

                <!-- وقت الدروس المتبقية -->
                <div class="stat-card">
                    <div class="stat-icon remaining-duration">
                        <i class="fas fa-hourglass-start"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="full-remaining-duration">
                            <?php echo formatDuration($fullStats['remaining_duration']); ?>
                        </div>
                        <div class="stat-label">وقت الدروس المتبقية</div>
                    </div>
                </div>

                <!-- الدروس بدون حالة -->
                <div class="stat-card">
                    <div class="stat-icon no-status">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="full-no-status-lessons">
                            <?php echo $fullStats['no_status_count']; ?>
                        </div>
                        <div class="stat-label">دروس بدون حالة</div>
                    </div>
                </div>
            </div>

            <!-- شريط التقدم الكلي -->
            <div class="progress-section">
                <div class="progress-header">
                    <span class="progress-title">نسبة الإنجاز الكلية</span>
                    <span class="progress-percentage" id="full-completion-percentage">
                        <?php 
                        $fullCompletionPercentage = $fullStats['total_count'] > 0 ? 
                            round(($fullStats['completed_count'] / $fullStats['total_count']) * 100) : 0;
                        echo $fullCompletionPercentage . '%';
                        ?>
                    </span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" 
                         id="full-completion-progress" 
                         style="width: <?php echo $fullCompletionPercentage; ?>%">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- معلومات الكورس -->
    <div class="course-header bg-gradient-to-r from-blue-600 to-blue-800 text-white py-5 mb-5" style="background: linear-gradient(45deg, #1a237e, #0d47a1);" id="courseInfo">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-3">
                            <li class="breadcrumb-item">
                                <a href="index.php" class="text-white-50">
                                    <i class="fas fa-home"></i>
                                </a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="courses.php?language_id=<?php echo $course['language_id']; ?>" class="text-white-50">
                                    <?php echo htmlspecialchars(getLanguageInfo($course['language_id'])['name']); ?>
                                </a>
                            </li>
                            <li class="breadcrumb-item active text-white" aria-current="page">
                                <?php echo htmlspecialchars($course['title']); ?>
                            </li>
                        </ol>
                    </nav>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="display-5 mb-0">
                            <?php echo htmlspecialchars($course['title']); ?>
                        </h1>
                        <!-- إضافة زر الإخفاء/الإظهار -->
                        <button type="button" 
                                class="btn btn-link text-white p-0 btn-toggle" 
                                id="toggleCourseInfoBtn" 
                                title="إخفاء/إظهار معلومات الكورس">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    
                    <!-- إضافة div جديد لتغليف المحتوى القابل للإخفاء -->
                    <div id="courseInfoContent">
                        <p class="lead mb-4">
                            <?php echo htmlspecialchars($course['description']); ?>
                        </p>

                        <div class="course-meta">
                            <div class="row g-3">
                                <div class="col-auto">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-book-open me-2"></i>
                                        <span><?php echo count($lessons); ?> درس</span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-clock me-2"></i>
                                        <span>
                                            <?php 
                                            $totalDuration = array_sum(array_column($lessons, 'duration'));
                                            echo formatDuration($totalDuration);
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <?php if ($course['playlist_url']): ?>
                                    <div class="col-auto">
                                        <a href="<?php echo htmlspecialchars($course['playlist_url']); ?>" 
                                           class="btn btn-outline-light btn-sm" 
                                           target="_blank">
                                            <i class="fab fa-youtube me-1"></i>
                                            قائمة التشغيل
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <div class="col-auto">
                                    <div class="btn-group">
                                        <button class="btn btn-outline-light btn-sm" 
                                                onclick="copyDetails('all')" 
                                                data-bs-toggle="tooltip" 
                                                title="نسخ التفاصيل والدروس">
                                            <i class="fas fa-copy me-1"></i>
                                            نسخ الكل
                                        </button>
                                        <button type="button" 
                                                class="btn btn-outline-light btn-sm dropdown-toggle dropdown-toggle-split" 
                                                data-bs-toggle="dropdown">
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="copyDetails('details')">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    نسخ التفاصيل فقط
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="copyDetails('titles')">
                                                    <i class="fas fa-list me-2"></i>
                                                    نسخ أسماء الدروس فقط
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 mt-4 mt-lg-0" id="courseSectionsContent">
                    <div class="course-stats" style="
        background: linear-gradient(45deg, #1a237e, #0d47a1);
    "
>
                        <div class="row g-3">
                            <!-- الأقسام -->
                            <div class="col-12">
                                <div class="card bg-white bg-opacity-10 border-0">
                                    <div class="card-header bg-transparent border-0">
                                        <h5 class="card-title text-white mb-0">
                                            <i class="fas fa-folder-tree me-2"></i>
                                            الأقسام
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="list-group list-group-flush bg-transparent">
                                            <?php foreach ($sections as $section): ?>
                                                <div class="list-group-item bg-transparent border-0 text-white-50 py-2 px-0">
                                                    <i class="fas fa-folder me-2"></i>
                                                    <?php echo htmlspecialchars($section['name']); ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- إضافة قسم الفلترة بعد هيدر الصفحة -->
    <div class="filters-section" id="filtersSection">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">القسم</label>
                <select class="form-select" id="sectionFilter">
                    <option value="">الكل</option>
                    <?php foreach ($sections as $section): ?>
                        <option value="<?php echo $section['id']; ?>"
                                <?php echo (isset($_GET['section_id']) && $_GET['section_id'] == $section['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($section['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">الحالة</label>
                <select class="form-select" id="statusFilter">
                    <option value="">الكل</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?php echo $status['id']; ?>">
                            <?php echo htmlspecialchars($status['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">المدة</label>
                <select class="form-select" id="durationFilter">
                    <option value="">الكل</option>
                    <option value="short">أقل من 10 دقائق</option>
                    <option value="medium">10-30 دقيقة</option>
                    <option value="long">أكثر من 30 دقيقة</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">البحث</label>
                <input type="text" class="form-control" id="searchFilter" placeholder="ابحث عن درس...">
            </div>
            
            <div class="col-12">
                <div class="d-flex gap-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="importantFilter">
                        <label class="form-check-label">
                            الدروس المهمة فقط
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="theoryFilter">
                        <label class="form-check-label">
                            الدروس النظرية فقط
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="hideTheoryFilter">
                        <label class="form-check-label">
                            إخفاء الدروس النظرية
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- إضافة تصفية الإحصائيات -->
    <div class="stats-filter mb-4">
        <div class="row align-items-center">
            <div class="col-md-4">
                <label class="form-label">تصفية حسب القسم:</label>
                <select class="form-select" id="sectionStatsFilter">
                    <option value="">جميع الأقسام</option>
                    <?php foreach ($sections as $section): ?>
                        <option value="<?php echo $section['id']; ?>">
                            <?php echo htmlspecialchars($section['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-8 d-flex justify-content-between align-items-center">
                <div class="chart-toggle btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary active" data-chart="completion">
                        <i class="fas fa-chart-pie me-2"></i>نسبة الإكمال
                    </button>
                    <button type="button" class="btn btn-outline-primary" data-chart="duration">
                        <i class="fas fa-chart-bar me-2"></i>توزيع الوقت
                    </button>
                    <button type="button" class="btn btn-outline-primary" data-chart="types">
                        <i class="fas fa-chart-line me-2"></i>أنواع الدروس
                    </button>
                </div>
                <!-- إضافة زر الإخفاء/الإظهار -->
                <button type="button" class="btn btn-outline-secondary" id="toggleChartsBtn">
                    <i class="fas fa-eye-slash"></i>
                    <span>إخفاء الرسوم البيانية</span>
                </button>
            </div>
        </div>
    </div>

    <!-- إضافة قسم الرسوم البيانية -->
    <div class="charts-section mb-4" id="chartsSection">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <canvas id="completionChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <canvas id="durationChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول الدروس -->
    <div class="card shadow-sm" id="lessonsTable">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0">قائمة الدروس</h5>
                </div>
                <div class="col-auto">
                    <div class="btn-group">
                        <button class="btn btn-primary btn-sm" onclick="showStatusesModal()">
                            <i class="fas fa-cog me-1"></i>
                            إدارة الحالات
                        </button>
                        <a href="views/lessons-cards.php?course_id=<?php echo $course_id; ?>" 
                           class="btn btn-info btn-sm">
                            <i class="fas fa-th-large me-1"></i>
                            عرض البطاقات
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- جدول الدروس -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50">#</th> <!-- عمود واحد للترقيم والمراجعة -->
                        <th>العنوان</th>
                        <th width="120">المدة</th>
                        <th width="150">القسم</th>
                        <th width="120">الحالة</th>
                        <th width="100">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lessons as $index => $lesson): ?>
                        <tr data-lesson-id="<?php echo $lesson['id']; ?>">
                            <!-- دمج الترقيم مع المراجعة -->
                            <td>
                                <div class="review-number-wrapper">
                                    <?php if ($lesson['is_reviewed']): ?>
                                        <input type="checkbox" 
                                               class="form-check-input review-checkbox" 
                                               data-lesson-id="<?php echo $lesson['id']; ?>"
                                               checked
                                               onchange="toggleReview(this, <?php echo $lesson['id']; ?>)">
                                        <i class="fas fa-check number"></i>
                                    <?php else: ?>
                                        <input type="checkbox" 
                                               class="form-check-input review-checkbox" 
                                               data-lesson-id="<?php echo $lesson['id']; ?>"
                                               onchange="toggleReview(this, <?php echo $lesson['id']; ?>)">
                                        <span class="number"><?php echo $index + 1; ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="lesson-content">
                                    <div class="lesson-title-wrapper">
                                        <span class="lesson-title <?php echo $lesson['completed'] ? 'completed' : ''; ?>" 
                                              data-lesson-id="<?php echo $lesson['id']; ?>">
                                            <?php echo htmlspecialchars($lesson['title']); ?>
                                        </span>
                                        
                                        <div class="lesson-badges">
                                            <?php if ($lesson['is_important']): ?>
                                                <span class="badge badge-important badge-appear" title="درس مهم">
                                                    <i class="fas fa-star"></i>
                                                    مهم
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if ($lesson['is_theory']): ?>
                                                <span class="badge badge-theory badge-appear" title="درس نظري">
                                                    <i class="fas fa-book"></i>
                                                    نظري
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($lesson['tags'])): ?>
                                                <?php 
                                                $tags = array_map('trim', explode(',', $lesson['tags']));
                                                $tags = array_filter($tags);
                                                foreach ($tags as $tag): 
                                                    $tagClass = preg_replace('/[^a-z0-9-]/', '', strtolower(trim($tag)));
                                                ?>
                                                    <span class="lesson-tag <?php echo $tagClass; ?> badge-appear" 
                                                          title="<?php echo htmlspecialchars($tag); ?>">
                                                        <i class="fas fa-tag"></i>
                                                        <?php echo htmlspecialchars($tag); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="duration">
                                    <?php echo formatDuration($lesson['duration']); ?>
                                </span>
                            </td>
                            <td>
                                <select class="form-select section-select custom-select" 
                                        data-lesson-id="<?php echo $lesson['id']; ?>">
                                    <option value="">اختر القسم</option>
                                    <?php foreach ($sections as $section): ?>
                                        <option value="<?php echo $section['id']; ?>"
                                                <?php echo ($lesson['section_id'] == $section['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($section['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select class="form-select status-select custom-select" 
                                        data-lesson-id="<?php echo $lesson['id']; ?>"
                                        style="background-color: <?php echo $lesson['status_color'] ?? ''; ?>; 
                                               color: <?php echo $lesson['status_text_color'] ?? ''; ?>">
                                    <option value="">اختر الحالة</option>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?php echo $status['id']; ?>"
                                                <?php echo ($lesson['status_id'] == $status['id']) ? 'selected' : ''; ?>
                                                data-color="<?php echo $status['color']; ?>"
                                                data-text-color="<?php echo $status['text_color']; ?>">
                                            <?php echo htmlspecialchars($status['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light" 
                                            type="button" 
                                            data-bs-toggle="dropdown" 
                                            aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <!-- زر تفاصيل الدرس -->
                                        <li>
                                            <a class="dropdown-item" 
                                               href="http://videomx.com/content/views/lesson-details.php?id=<?php echo $lesson['id']; ?>" 
                                               target="_blank">
                                                <i class="fas fa-info-circle me-2 text-primary"></i>
                                                تفاصيل الدرس
                                            </a>
                                        </li>
                                        
                                        <!-- زر تشغيل الفيديو -->
                                        <?php if (!empty($lesson['video_url'])): ?>
                                            <li>
                                                <button type="button" 
                                                        class="dropdown-item" 
                                                        onclick="playVideo('<?php echo htmlspecialchars($lesson['video_url']); ?>', '<?php echo htmlspecialchars($lesson['title']); ?>')">
                                                    <i class="fas fa-play me-2 text-success"></i>
                                                    تشغيل الفيديو
                                                </button>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <li><hr class="dropdown-divider"></li>
                                        
                                        <!-- إضافة خيارات تحديد الدرس كمكتمل/غير مكتمل -->
                                        <li>
                                            <button type="button" 
                                                    class="dropdown-item <?php echo $lesson['completed'] ? 'active' : ''; ?>"
                                                    onclick="toggleLessonCompleted(<?php echo $lesson['id']; ?>, <?php echo $lesson['completed'] ? '0' : '1'; ?>)">
                                                <i class="<?php echo $lesson['completed'] ? 'fas fa-check-circle text-success' : 'far fa-circle text-muted'; ?> me-2"></i>
                                                <?php echo $lesson['completed'] ? 'إلغاء تحديد كمكتمل' : 'تحديد كمكتمل'; ?>
                                            </button>
                                        </li>
                                        
                                        <!-- زر تحديد كدرس مهم -->
                                        <li>
                                            <button type="button" 
                                                    class="dropdown-item" 
                                                    onclick="confirmToggleImportance(<?php echo $lesson['id']; ?>, <?php echo $lesson['is_important']; ?>)">
                                                <i class="fas fa-star me-2 <?php echo $lesson['is_important'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                <?php echo $lesson['is_important'] ? 'إلغاء تحديد كدرس مهم' : 'تحديد كدرس مهم'; ?>
                                            </button>
                                        </li>
                                        
                                        <!-- زر تحديد كدرس نظري -->
                                        <li>
                                            <button type="button" 
                                                    class="dropdown-item" 
                                                    onclick="confirmToggleTheory(<?php echo $lesson['id']; ?>, <?php echo $lesson['is_theory']; ?>)">
                                                <i class="fas fa-book me-2 <?php echo $lesson['is_theory'] ? 'text-info' : 'text-muted'; ?>"></i>
                                                <?php echo $lesson['is_theory'] ? 'إلغاء تحديد كدرس نظري' : 'تحديد كدرس نظري'; ?>
                                            </button>
                                        </li>
                                        
                                        <!-- زر تحرير التاجات -->
                                        <li>
                                            <button type="button" 
                                                    class="dropdown-item" 
                                                    onclick="editLessonTags(<?php echo $lesson['id']; ?>, '<?php echo htmlspecialchars($lesson['tags'] ?? ''); ?>')">
                                                <i class="fas fa-tags me-2 text-primary"></i>
                                                تحرير التاجات
                                            </button>
                                        </li>
                                        
                                        <!-- زر إضافة ملاحظة -->
                                        <li>
                                            <button type="button" 
                                                    class="dropdown-item" 
                                                    onclick="addLessonNote(<?php echo $lesson['id']; ?>)">
                                                <i class="fas fa-sticky-note me-2 text-success"></i>
                                                إضافة ملاحظة
                                            </button>
                                        </li>
                                        
                                        <li><hr class="dropdown-divider"></li>
                                        
                                        <!-- زر الحذف -->
                                        <li>
                                            <button type="button" 
                                                    class="dropdown-item text-danger" 
                                                    onclick="deleteLesson(<?php echo $lesson['id']; ?>)">
                                                <i class="fas fa-trash me-2"></i>
                                                حذف الدرس
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- إضافة ترقيم الصفحات -->
            <?php 
            // التأكد من وجود القيم قبل استخدامها
            $totalPages = $lessonsData['total_pages'] ?? 1;
            $totalLessons = $lessonsData['total_lessons'] ?? count($lessons);
            ?>

            <?php if ($totalPages > 1): ?>
                <nav aria-label="ترقيم الصفحات" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <!-- زر الصفحة السابقة -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" 
                               href="?course_id=<?php echo $course_id; ?>&page=<?php echo $page - 1; ?>"
                               aria-label="السابق">
                                <span aria-hidden="true">&laquo;</span>
                                <span class="sr-only">السابق</span>
                            </a>
                        </li>
                        
                        <!-- أرقام الصفحات -->
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" 
                                   href="?course_id=<?php echo $course_id; ?>&page=<?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- زر الصفحة التالية -->
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" 
                               href="?course_id=<?php echo $course_id; ?>&page=<?php echo $page + 1; ?>"
                               aria-label="التالي">
                                <span aria-hidden="true">&raquo;</span>
                                <span class="sr-only">التالي</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                
                <!-- عرض معلومات الصفحات -->
                <div class="text-center text-muted mt-2">
                    <small>
                        عرض الدروس <?php echo ($page - 1) * $perPage + 1; ?> إلى 
                        <?php echo min($page * $perPage, $totalLessons); ?> 
                        من إجمالي <?php echo $totalLessons; ?> درس
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- إضافة قسم الحالات بعد جدول الدروس -->
    <div class="mt-4 bg-white text-black p-4 rounded" id="statusesSection">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="h5 mb-0">
                <i class="fas fa-tags me-2"></i>
                حالات الدروس
            </h3>
            <!-- إضافة زر الإخفاء/الإظهار -->
            <button type="button" 
                    class="btn btn-link text-primary p-0" 
                    id="toggleStatusesBtn" 
                    title="إخفاء/إظهار حالات الدروس">
                <i class="fas fa-eye-slash"></i>
            </button>
        </div>

        <!-- إضافة div جديد لتغليف المحتوى القابل للإخفاء -->
        <div id="statusesContent">
            <div class="row g-3">
                <?php foreach ($statuses as $status): ?>
                    <div class="col-md-3 col-sm-6">
                        <div class="card h-100 status-card" data-status-id="<?php echo $status['id']; ?>">
                            <div class="card-body" style="background-color: <?php echo $status['color']; ?>; color: <?php echo $status['text_color']; ?>">
                                <h6 class="card-title d-flex justify-content-between align-items-center">
                                    <span><?php echo htmlspecialchars($status['name']); ?></span>
                                    <span class="badge bg-light text-dark status-count">0</span>
                                </h6>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar bg-light" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- نافذة عرض الفيديو -->
<div class="modal fade" id="videoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="video-container">
                    <!-- سيتم تحديث المصدر عبر JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- نافذة عرض النص -->
<div class="modal fade" id="transcriptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">نص الدرس</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- سيتم تحميل المحتوى عبر AJAX -->
            </div>
        </div>
    </div>
</div>


<!-- إضافة textarea مخفي للنسخ -->
<textarea id="copyArea" style="position: absolute; left: -9999px; display: none;"></textarea>

<!-- إضافة نافذة إدارة الحالات قبل نهاية body -->
<div class="modal fade" id="statusesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إدارة الحالات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="statuses-list">
                    <?php foreach ($statuses as $status): ?>
                        <div class="status-item mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="status-colors me-2">
                                    <div class="d-flex gap-2">
                                        <input type="color" 
                                               class="form-control form-control-color status-color-picker" 
                                               value="<?php echo $status['color'] ?? '#ffd700'; ?>"
                                               data-status-id="<?php echo $status['id']; ?>"
                                               title="لون الخلفية">
                                        <input type="color" 
                                               class="form-control form-control-color status-text-color-picker" 
                                               value="<?php echo $status['text_color'] ?? '#000000'; ?>"
                                               data-status-id="<?php echo $status['id']; ?>"
                                               title="لون النص">
                                    </div>
                                </div>
                                <div class="status-name flex-grow-1">
                                    <?php echo htmlspecialchars($status['name']); ?>
                                </div>
                                <div class="status-count badge bg-secondary ms-2">
                                    <?php 
                                    $count = array_count_values(array_column($lessons, 'status_id'))[$status['id']] ?? 0;
                                    echo $count;
                                    ?> درس
                                </div>
                            </div>
                            <div class="status-preview p-2 rounded" 
                                 style="background-color: <?php echo $status['color']; ?>; color: <?php echo $status['text_color']; ?>">
                                معاينة الحالة
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary" onclick="saveStatusColors()">
                    <i class="fas fa-save me-1"></i>
                    حفظ التغييرات
                </button>
            </div>
        </div>
    </div>
</div>

<!-- نافذة تحرير التاجات -->
<div class="modal fade" id="editTagsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تحرير تاجات الدرس</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editTagsForm">
                    <input type="hidden" id="lessonIdForTags" name="lesson_id">
                    <div class="mb-3">
                        <label for="lessonTags" class="form-label">التاجات</label>
                        <input type="text" 
                               class="form-control" 
                               id="lessonTags" 
                               name="tags" 
                               data-role="tagsinput"
                               placeholder="أضف تاجات">
                        <div class="form-text">اكتب التاج واضغط Enter للإضافة</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" onclick="saveTagsChanges()">
                    <i class="fas fa-save me-1"></i>
                    حفظ التغييرات
                </button>
            </div>
        </div>
    </div>
</div>

<!-- نافذة إضافة ملاحظة -->
<div class="modal fade" id="addNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة ملاحظة للدرس</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addNoteForm">
                    <input type="hidden" id="lessonIdForNote" name="lesson_id">
                    <input type="hidden" name="type" value="text">
                    
                    <div class="mb-3">
                        <label for="noteTitle" class="form-label">عنوان الملاحظة</label>
                        <input type="text" 
                               class="form-control" 
                               id="noteTitle" 
                               name="title" 
                               required 
                               placeholder="أدخل عنوان الملاحظة">
                    </div>
                    
                    <div class="mb-3">
                        <label for="noteContent" class="form-label">محتوى الملاحظة</label>
                        <textarea class="form-control" 
                                  id="noteContent" 
                                  name="content" 
                                  rows="5" 
                                  required 
                                  placeholder="أدخل محتوى الملاحظة"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" onclick="saveNote()">
                    <i class="fas fa-save me-1"></i>
                    حفظ الملاحظة
                </button>
            </div>
        </div>
    </div>
</div>

<!-- إضافة JavaScript لتحديث الإحصائيات -->
<script>
/**
 * تحديث إحصائيات الدروس
 * @param {Object} stats - كائن يحتوي على الإحصائيات الجديدة
 */
function updateStats(stats) {
    if (!stats) return;

    // تحديث الأعداد
    const elements = {
        'total-lessons': stats.total_lessons,
        'completed-lessons': stats.completed_lessons,
        'remaining-lessons': stats.remaining_lessons,
        'important-lessons': stats.important_lessons,
        'theory-lessons': stats.theory_lessons
    };

    // تحديث كل عنصر
    Object.entries(elements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    });

    // تحديث نسبة الإنجاز
    const percentage = stats.total_lessons > 0 
        ? Math.round((stats.completed_lessons / stats.total_lessons) * 100) 
        : 0;

    const percentageElement = document.getElementById('completion-percentage');
    const progressBar = document.getElementById('completion-progress');

    if (percentageElement) {
        percentageElement.textContent = percentage + '%';
    }
    if (progressBar) {
        progressBar.style.width = percentage + '%';
    }

    // تحديث إحصائيات الحالات
    if (stats.statusCounts) {
        updateStatusStats(stats.statusCounts, stats.total_lessons);
    }
}

/**
 * تحديث إحصائيات الحالات
 * @param {Object} statusCounts - كائن يحتوي على عدد الدروس لكل حالة
 * @param {number} total - إجمالي عدد الدروس
 */
function updateStatusStats(statusCounts, total) {
    document.querySelectorAll('.status-card').forEach(card => {
        const statusId = card.dataset.statusId;
        if (statusId && statusCounts[statusId] !== undefined) {
            const count = parseInt(statusCounts[statusId]);
            const countElement = card.querySelector('.status-count');
            const progressBar = card.querySelector('.progress-bar');

            if (countElement) {
                countElement.textContent = count;
            }
            if (progressBar && total > 0) {
                const percentage = Math.round((count / total) * 100);
                progressBar.style.width = percentage + '%';
            }
        }
    });
}

// تعريف معرف الكورس
const courseId = <?php echo json_encode($course_id); ?>;

// استدعاء تحديث الإحصائيات عند تغيير حالة الدرس
document.addEventListener('lessonStatusChanged', function(event) {
    const loader = document.querySelector('.loader');
    if (loader) loader.style.display = 'block';

    fetch(`api/get-course-stats.php?course_id=${courseId}`)
        .then(response => response.json())
        .then(stats => {
            if (stats.error) {
                throw new Error(stats.error);
            }
        //    updateStats(stats);
        })
        .catch(error => {
            console.error('Error updating stats:', error);
            toastr.error('حدث خطأ أثناء تحديث الإحصائيات');
        })
        .finally(() => {
            if (loader) loader.style.display = 'none';
        });
});

$(function() {
    const toggleBtn = $('#toggleFullCourseStatsBtn');
    const content = $('#fullCourseStatsContent');
    
    // تحقق من وجود العناصر
    if (!toggleBtn.length || !content.length) {
        console.error('عناصر التبديل غير موجودة');
        return;
    }

    // استرجاع الحالة المحفوظة
    const isHidden = localStorage.getItem('fullCourseStatsHidden') === 'true';
    
    // تطبيق الحالة المحفوظة عند التحميل
    if (isHidden) {
        content.hide();
        toggleBtn.find('i').removeClass('fa-eye-slash').addClass('fa-eye');
    }

    // معالج حدث النقر
    toggleBtn.on('click', function(e) {
        e.preventDefault();
        
        const isCurrentlyHidden = content.is(':hidden');
        const icon = toggleBtn.find('i');

        // تبديل العرض مع تأثير حركي
        content.slideToggle({
            duration: 300,
            start: function() {
                // تحديث الأيقونة
                icon.toggleClass('fa-eye-slash fa-eye');
            },
            complete: function() {
                // حفظ الحالة
                localStorage.setItem('fullCourseStatsHidden', !isCurrentlyHidden);
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // التنقل السلس عند النقر على روابط القائمة
    document.querySelectorAll('.quick-nav .dropdown-item').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                // إغلاق القائمة المنسدلة
                const dropdownMenu = this.closest('.dropdown-menu');
                if (dropdownMenu) {
                    const dropdown = bootstrap.Dropdown.getInstance(document.querySelector('#quickNavDropdown'));
                    if (dropdown) {
                        dropdown.hide();
                    }
                }
                
                // التمرير إلى القسم المستهدف
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // تحديث القائمة المنسدلة عند التمرير
    let lastKnownScrollPosition = 0;
    let ticking = false;
    
    function updateActiveSection() {
        const sections = document.querySelectorAll('div[id]');
        const scrollPosition = window.scrollY + 100; // إضافة offset للتعويض عن الهيدر
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.offsetHeight;
            
            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                // تحديث العنصر النشط في القائمة
                document.querySelectorAll('.quick-nav .dropdown-item').forEach(item => {
                    if (item.getAttribute('href') === '#' + section.id) {
                        item.classList.add('active');
                    } else {
                        item.classList.remove('active');
                    }
                });
            }
        });
    }
    
    document.addEventListener('scroll', function() {
        lastKnownScrollPosition = window.scrollY;
        
        if (!ticking) {
            window.requestAnimationFrame(function() {
                updateActiveSection();
                ticking = false;
            });
            
            ticking = true;
        }
    });
});

// إضافة JavaScript للتحكم في القائمة -->
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.menu-toggle');
    const menuItems = document.querySelector('.menu-items');
    const menuLinks = document.querySelectorAll('.menu-item');
    let isMenuOpen = false;
    
    // تبديل عرض/إخفاء القائمة
    menuToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        isMenuOpen = !isMenuOpen;
        menuItems.classList.toggle('show');
        menuToggle.querySelector('i').classList.toggle('fa-bars');
        menuToggle.querySelector('i').classList.toggle('fa-times');
    });
    
    // إخفاء القائمة عند النقر خارجها
    document.addEventListener('click', function(e) {
        if (isMenuOpen && !e.target.closest('.mouse-menu')) {
            isMenuOpen = false;
            menuItems.classList.remove('show');
            menuToggle.querySelector('i').classList.remove('fa-times');
            menuToggle.querySelector('i').classList.add('fa-bars');
        }
    });
    
    // التنقل السلس عند النقر
    menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                // إغلاق القائمة بعد النقر
                isMenuOpen = false;
                menuItems.classList.remove('show');
                menuToggle.querySelector('i').classList.remove('fa-times');
                menuToggle.querySelector('i').classList.add('fa-bars');
                
                // التمرير إلى القسم المستهدف
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // تحديث العنصر النشط عند التمرير
    let lastKnownScrollPosition = 0;
    let ticking = false;
    
    function updateActiveMenuItem() {
        const scrollPosition = window.scrollY + 100;
        
        document.querySelectorAll('div[id]').forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.offsetHeight;
            
            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                menuLinks.forEach(link => {
                    if (link.getAttribute('href') === '#' + section.id) {
                        link.classList.add('active');
                    } else {
                        link.classList.remove('active');
                    }
                });
            }
        });
    }
    
    window.addEventListener('scroll', function() {
        lastKnownScrollPosition = window.scrollY;
        
        if (!ticking) {
            window.requestAnimationFrame(function() {
                updateActiveMenuItem();
                ticking = false;
            });
            ticking = true;
        }
    });
});
</script>

<?php require_once 'jsx.php'; ?> 
<?php require_once 'js.php'; ?> 
<?php require_once 'includes/footer.php'; ?> 

<!-- إضافة زر قائمة الموس -->
<div class="mouse-menu-trigger">
    <button class="btn btn-primary rounded-circle" id="mouseMenuBtn">
        <i class="fas fa-bars"></i>
    </button>
</div>

<!-- قائمة الموس (مخفية افتراضياً) -->
<div class="mouse-menu" style="display: none;">
    <div class="menu-items">
        <a href="#courseStats" class="menu-item" title="إحصائيات الدروس">
            <i class="fas fa-chart-line"></i>
        </a>
        <a href="#fullCourseStats" class="menu-item" title="إحصائيات كامل الكورس">
            <i class="fas fa-chart-pie"></i>
        </a>
        <a href="#courseInfo" class="menu-item" title="معلومات الكورس">
            <i class="fas fa-info-circle"></i>
        </a>
        <a href="#filtersSection" class="menu-item" title="أدوات التصفية">
            <i class="fas fa-filter"></i>
        </a>
        <a href="#chartsSection" class="menu-item" title="الرسوم البيانية">
            <i class="fas fa-chart-bar"></i>
        </a>
        <a href="#lessonsTable" class="menu-item" title="جدول الدروس">
            <i class="fas fa-table"></i>
        </a>
        <a href="#statusesSection" class="menu-item" title="حالات الدروس">
            <i class="fas fa-tags"></i>
        </a>
    </div>
</div>

<style>
/* تنسيق زر القائمة */
.mouse-menu-trigger {
    position: fixed;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1000;
}

.mouse-menu-trigger button {
    width: 40px;
    height: 40px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.mouse-menu-trigger button:hover {
    transform: scale(1.1);
}

/* تنسيق القائمة */
.mouse-menu {
    position: fixed;
    left: 70px; /* زيادة المسافة من الزر */
    top: 50%;
    transform: translateY(-50%);
    z-index: 1000;
    background: white;
    padding: 10px;
    border-radius: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

/* تعديل موضع القائمة في النسخة العربية */
.rtl .mouse-menu-trigger {
    left: auto;
    right: 20px;
}

.rtl .mouse-menu {
    left: auto;
    right: 70px;
}

/* باقي التنسيقات كما هي */
.menu-items {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.menu-item {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    color: #495057;
    border-radius: 50%;
    text-decoration: none;
    transition: all 0.3s ease;
}

.menu-item:hover {
    background: #e9ecef;
    color: #0d6efd;
    transform: scale(1.1);
}

.menu-item.active {
    background: #0d6efd;
    color: white;
}

/* تحسين تلميحات الأزرار */
.menu-item::after {
    content: attr(title);
    position: absolute;
    right: 50px;
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    white-space: nowrap;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.rtl .menu-item::after {
    right: auto;
    left: 50px;
}

.menu-item:hover::after {
    opacity: 1;
    visibility: visible;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuBtn = document.getElementById('mouseMenuBtn');
    const mouseMenu = document.querySelector('.mouse-menu');
    const menuLinks = document.querySelectorAll('.menu-item');
    let isMenuOpen = false;
    
    // تبديل عرض/إخفاء القائمة
    menuBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        isMenuOpen = !isMenuOpen;
        mouseMenu.style.display = isMenuOpen ? 'block' : 'none';
        menuBtn.querySelector('i').classList.toggle('fa-bars');
        menuBtn.querySelector('i').classList.toggle('fa-times');
    });
    
    // إخفاء القائمة عند النقر خارجها
    document.addEventListener('click', function(e) {
        if (isMenuOpen && !e.target.closest('.mouse-menu') && !e.target.closest('.mouse-menu-trigger')) {
            isMenuOpen = false;
            mouseMenu.style.display = 'none';
            menuBtn.querySelector('i').classList.remove('fa-times');
            menuBtn.querySelector('i').classList.add('fa-bars');
        }
    });
    
    // التنقل السلس عند النقر
    menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                // إغلاق القائمة بعد النقر
                isMenuOpen = false;
                mouseMenu.style.display = 'none';
                menuBtn.querySelector('i').classList.remove('fa-times');
                menuBtn.querySelector('i').classList.add('fa-bars');
                
                // التمرير إلى القسم المستهدف
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // تحديث العنصر النشط
                menuLinks.forEach(item => item.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
    
    // تحديث العنصر النشط عند التمرير
    function updateActiveMenuItem() {
        const scrollPosition = window.scrollY + 100;
        
        document.querySelectorAll('div[id]').forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.offsetHeight;
            
            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                menuLinks.forEach(link => {
                    if (link.getAttribute('href') === '#' + section.id) {
                        link.classList.add('active');
                    } else {
                        link.classList.remove('active');
                    }
                });
            }
        });
    }
    
    // تحديث العنصر النشط عند التمرير
    let ticking = false;
    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                updateActiveMenuItem();
                ticking = false;
            });
            ticking = true;
        }
    });
});
</script> 

<!-- إضافة JavaScript للتعامل مع المراجعة -->
<script>
/**
 * تبديل حالة المراجعة للدرس
 * @param {HTMLElement} checkbox - عنصر checkbox المضغوط
 * @param {number} lessonId - معرف الدرس
 */
function toggleReview(checkbox, lessonId) {
    const isReviewed = checkbox.checked ? 1 : 0;
    const numberElement = checkbox.nextElementSibling;
    
    // عرض مؤشر التحميل
    const loader = document.querySelector('.loader');
    if (loader) loader.style.display = 'block';

    fetch('api/update-lesson-review.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            lesson_id: lessonId,
            is_reviewed: isReviewed
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success(isReviewed ? 'تمت إضافة الدرس للمراجعة' : 'تم إزالة الدرس من المراجعة');
            
            // تحديث الواجهة
            checkbox.checked = isReviewed;
            checkbox.style.backgroundColor = isReviewed ? '#28a745' : '#dc3545';
            
            // تحديث الرقم/الأيقونة
            if (isReviewed) {
                numberElement.className = 'fas fa-check number';
                numberElement.textContent = '';
            } else {
                numberElement.className = 'number';
                numberElement.textContent = numberElement.closest('tr').querySelector('td:first-child').textContent;
            }
        } else {
            throw new Error(data.error || 'حدث خطأ أثناء تحديث حالة المراجعة');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error(error.message);
        checkbox.checked = !isReviewed;
        checkbox.style.backgroundColor = !isReviewed ? '#28a745' : '#dc3545';
    })
    .finally(() => {
        if (loader) loader.style.display = 'none';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const reviewCheckboxes = document.querySelectorAll('.review-checkbox');
    
    reviewCheckboxes.forEach(checkbox => {
        new bootstrap.Tooltip(checkbox, {
            title: checkbox.checked ? 'تمت المراجعة' : 'لم تتم المراجعة',
            placement: 'top'
        });
        
        checkbox.addEventListener('change', function() {
            const tooltip = bootstrap.Tooltip.getInstance(this);
            if (tooltip) {
                tooltip.dispose();
            }
            new bootstrap.Tooltip(this, {
                title: this.checked ? 'تمت المراجعة' : 'لم تتم المراجعة',
                placement: 'top'
            });
        });
    });
});
</script>

<style>
/* تنسيقات checkbox المراجعة */
.review-checkbox {
    cursor: pointer;
    width: 20px;
    height: 20px;
}

.review-checkbox:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.review-checkbox:hover {
    border-color: #28a745;
}

/* تحسين مظهر tooltip */
.tooltip {
    font-size: 12px;
}

.tooltip .tooltip-inner {
    background-color: #333;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
}
</style> 

<style>
.review-number-wrapper {
    position: relative;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.review-checkbox {
    position: absolute;
    width: 30px;
    height: 30px;
    margin: 0;
    cursor: pointer;
    border-radius: 50%;
}

.review-checkbox:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.review-checkbox:not(:checked) {
    background-color: #dc3545;
    border-color: #dc3545;
}

.review-number-wrapper .number {
    position: absolute;
    color: white;
    font-size: 12px;
    z-index: 1;
    pointer-events: none;
}

.review-number-wrapper .fa-check {
    font-size: 14px;
}

/* تحسين مظهر tooltip */
.tooltip {
    font-size: 12px;
}

.tooltip .tooltip-inner {
    background-color: #333;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const reviewCheckboxes = document.querySelectorAll('.review-checkbox');
    
    reviewCheckboxes.forEach(checkbox => {
        // إضافة tooltip
        new bootstrap.Tooltip(checkbox, {
            title: checkbox.checked ? 'تمت المراجعة' : 'لم تتم المراجعة',
            placement: 'top'
        });
        
        // تحديث tooltip عند تغيير الحالة
        checkbox.addEventListener('change', function() {
            const tooltip = bootstrap.Tooltip.getInstance(this);
            if (tooltip) {
                tooltip.dispose();
            }
            new bootstrap.Tooltip(this, {
                title: this.checked ? 'تمت المراجعة' : 'لم تتم المراجعة',
                placement: 'top'
            });
        });
    });
});

function toggleReview(checkbox, lessonId) {
    const isReviewed = checkbox.checked ? 1 : 0;
    
    // عرض مؤشر التحميل
    const loader = document.querySelector('.loader');
    if (loader) loader.style.display = 'block';

    // إرسال الطلب لتحديث حالة المراجعة
    fetch('api/update-lesson-review.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            lesson_id: lessonId,
            is_reviewed: isReviewed
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // عرض رسالة نجاح
            toastr.success(isReviewed ? 'تمت إضافة الدرس للمراجعة' : 'تم إزالة الدرس من المراجعة');
            
            // تحديث الواجهة
            checkbox.checked = isReviewed;
            checkbox.style.backgroundColor = isReviewed ? '#28a745' : '#dc3545';
        } else {
            throw new Error(data.error || 'حدث خطأ أثناء تحديث حالة المراجعة');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error(error.message);
        // إعادة الـ checkbox لحالته السابقة
        checkbox.checked = !isReviewed;
        checkbox.style.backgroundColor = !isReviewed ? '#28a745' : '#dc3545';
    })
    .finally(() => {
        if (loader) loader.style.display = 'none';
    });
}
</script> 

<script>
// التركيز على جدول الدروس عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // التأكد من وجود العنصر
    const lessonsTable = document.getElementById('lessonsTable');
    if (lessonsTable) {
        // حساب موضع العنصر مع إضافة offset
        const offset = 800; // يمكن تعديل هذه القيمة حسب ارتفاع الهيدر
        const elementPosition = lessonsTable.getBoundingClientRect().top + window.pageYOffset;
        
        // التمرير إلى الموضع المحسوب
        window.scrollTo({
            top: elementPosition - offset,
            behavior: 'smooth'
        });
    }
});
</script>

</body>
</html>