<?php
require_once 'includes/functions.php';
require_once 'includes/statistics.php';

// معالجة طلب الحذف
if (isset($_POST['delete_language'])) {
    $language_id = (int)$_POST['language_id'];
    if (deleteLanguage($language_id)) {
        $_SESSION['success'] = 'تم حذف اللغة بنجاح';
    } else {
        $_SESSION['error'] = 'حدث خطأ أثناء حذف اللغة';
    }
    header('Location: index.php');
    exit;
}

// الحصول على رقم الصفحة الحالية
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;

// جلب إحصائيات المنصة
$platformStats = getPlatformStats();
$totalLanguages = getTotalLanguagesCount();
$totalPages = ceil($totalLanguages / $perPage);

// جلب اللغات للصفحة الحالية
$languages = getLanguagesPaginated($page, $perPage);

// الحصول على إحصائيات الدروس
$statistics = get_lessons_statistics();

$pageTitle = 'الرئيسية';
require_once 'includes/header.php';
?>

<!-- دمج شريطي التنقل في navbar واحد -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <!-- العلامة التجارية والزر للشاشات الصغيرة -->
        <a class="navbar-brand" href="/content/index.php">
            <i class="fas fa-home me-2"></i>
            الرئيسية
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- محتوى القائمة -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- القائمة الرئيسية على اليمين -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/content/languages.php">
                        <i class="fas fa-globe me-1"></i>
                        اللغات
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/add/add.php">
                        <i class="fas fa-cog me-1"></i>
                        الإعدادات والإضافات
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="http://videomx.com/GBT/" target="_blank">
                        <i class="fas fa-external-link-alt me-1"></i>
                        GBT
                    </a>
                </li>
            </ul>

            <!-- عنوان الصفحة في المنتصف -->
            <div class="navbar-text mx-auto">
                <h4 class="mb-0 text-white">
                    <i class="fas fa-code me-2"></i>
                    لغات البرمجة
                </h4>
            </div>

            <!-- أزرار إضافية على اليسار -->
            <div class="d-flex gap-2">
                <a href="/content/index.php" class="btn btn-outline-light">
                    <i class="fas fa-globe me-1"></i>
                    اللغات
                </a>
                <a href="http://videomx.com/GBT/" class="btn btn-outline-light" target="_blank">
                    <i class="fas fa-external-link-alt me-1"></i>
                    GBT
                </a>
                <a href="/add/add.php" class="btn btn-outline-light">
                    <i class="fas fa-cog me-1"></i>
                    الإعدادات
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- إضافة CSS للتنسيق -->
<style>
/* تنسيق Navbar */
.navbar {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 0.8rem 0;
}

.navbar-brand {
    font-weight: 600;
}

.navbar .btn-outline-light {
    border-width: 2px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.navbar .btn-outline-light:hover {
    background-color: rgba(255,255,255,0.1);
    transform: translateY(-2px);
}

.navbar-text h4 {
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* تحسينات للشاشات الصغيرة */
@media (max-width: 991.98px) {
    .navbar-text {
        display: none;
    }
    
    .navbar .d-flex {
        margin-top: 1rem;
        width: 100%;
        justify-content: center;
    }
    
    .navbar .btn-outline-light {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
    }
}

/* تأثير الخلفية المتحركة */
.animated-bg {
    background: linear-gradient(-45deg, #1e3c72, #2a5298, #2196f3, #00bcd4);
    background-size: 400% 400%;
    animation: gradient 15s ease infinite;
    height: 100%;
    width: 100%;
    opacity: 0.3;
}

@keyframes gradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* طبقة التراكب */
.bg-overlay {
    background: rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(5px);
}

/* تأثير الزجاج */
.glass-effect {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
}

/* تأثيرات النص */
.text-gradient {
    background: linear-gradient(120deg, #ffffff 0%, #f0f0f0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.gradient-text {
    background: linear-gradient(120deg, #ffd700 0%, #ffa500 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* تأثيرات الأزرار */
.custom-btn-primary {
    background: linear-gradient(120deg, #4CAF50 0%, #45a049 100%);
    border: none;
    color: white;
    transition: all 0.3s ease;
}

.custom-btn-outline {
    background: transparent;
    border: 2px solid rgba(255, 255, 255, 0.8);
    color: white;
    transition: all 0.3s ease;
}

.btn-hover-effect:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

/* تأثيرات البطاقات */
.hover-lift {
    transition: all 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
}

/* ألوان وتأثيرات إضافية */
.text-primary-light {
    color: #90caf9;
}

.text-white-80 {
    color: rgba(255, 255, 255, 0.8);
}

/* تحسينات التجاوب */
@media (max-width: 768px) {
    header {
        padding: 3rem 0;
    }
    
    .display-4 {
        font-size: 2rem;
    }
    
    .display-6 {
        font-size: 1.5rem;
    }
    
    .stat-card {
        padding: 1.5rem !important;
    }
}

header.position-relative.overflow-hidden.py-16 {
    padding: 31px;
    margin-top: -2px;
}

.statistics-container {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.statistics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.stat-box {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: transform 0.2s;
}

.stat-box:hover {
    transform: translateY(-5px);
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #2196F3;
    margin: 10px 0;
}

.stat-duration {
    color: #666;
    font-size: 14px;
}

.progress {
    height: 10px;
    border-radius: 5px;
}

.badge {
    padding: 5px 10px;
}
</style>

<div class="container py-5">
    <!-- شريط البحث -->
    <div class="row mb-4">
        <div class="col-md-6 mx-auto">
            <form class="search-form" method="GET">
                <div class="input-group">
                    <input type="text" 
                           class="form-control" 
                           name="search" 
                           placeholder="ابحث عن لغة..." 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                           autocomplete="off">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php
    // معالجة البحث
    $search = $_GET['search'] ?? '';
    $searchResults = searchLanguages($search, $page, $perPage);
    $languages = $searchResults['results'];
    $totalLanguages = $searchResults['total'];
    $totalPages = ceil($totalLanguages / $perPage);
    
    if (empty($languages) && !empty($search)): ?>
        <div class="alert alert-info text-center">
            لا توجد نتائج للبحث عن "<?php echo htmlspecialchars($search); ?>"
        </div>
    <?php endif; ?>

    <!-- بطاقات اللغات -->
    <div class="row g-4">
        <?php foreach ($languages as $language): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card language-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-code me-2"></i>
                            <?php echo htmlspecialchars($language['name']); ?>
                        </h5>
                        <div class="dropdown">
                            <button class="btn btn-link text-white" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item" onclick="showStats(<?php echo $language['id']; ?>)">
                                        <i class="fas fa-chart-bar me-2"></i>
                                        الإحصائيات
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item" onclick="loadCourses(<?php echo $language['id']; ?>)">
                                        <i class="fas fa-book me-2"></i>
                                        عرض الدورات
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه اللغة وجميع الدورات والدروس المرتبطة بها؟');">
                                        <input type="hidden" name="language_id" value="<?php echo $language['id']; ?>">
                                        <button type="submit" name="delete_language" class="dropdown-item text-danger">
                                            <i class="fas fa-trash-alt me-2"></i>
                                            حذف اللغة
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="stats">
                            <div class="d-flex justify-content-between mb-2">
                                <span>الدورات:</span>
                                <span class="badge bg-primary"><?php echo number_format($language['courses_count']); ?></span>
                            </div>
                            <div class="progress mb-3">
                                <div class="progress-bar bg-primary" style="width: <?php echo min(100, ($language['courses_count'] / 10) * 100); ?>%"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>الدروس:</span>
                                <span class="badge bg-success"><?php echo number_format($language['lessons_count']); ?></span>
                            </div>
                            <div class="progress mb-3">
                                <div class="progress-bar bg-success" style="width: <?php echo min(100, ($language['lessons_count'] / 50) * 100); ?>%"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <span>المدة:</span>
                                <span class="badge bg-info">
                                    <?php 
                                    require_once 'includes/time_formatter.php';
                                    echo format_time(intval($language['total_duration'])); 
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ترقيم الصفحات -->
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">السابق</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">التالي</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- نافذة الإحصائيات -->
<div class="modal fade" id="statsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إحصائيات اللغة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- سيتم تحميل المحتوى عبر AJAX -->
            </div>
        </div>
    </div>
</div>

<div class="statistics-container">
    <h2>إحصائيات الدروس</h2>
    <div class="statistics-grid">
        <!-- إحصائيات الدروس المكتملة -->
        <div class="stat-box">
            <h3>الدروس المكتملة</h3>
            <p class="stat-number"><?php echo $statistics['completed_count']; ?></p>
            <p class="stat-duration"><?php echo format_time($statistics['completed_duration']); ?></p>
            <div class="progress mt-2">
                <div class="progress-bar bg-success" 
                     style="width: <?php echo $statistics['completion_percentage']; ?>%">
                    <?php echo $statistics['completion_percentage']; ?>%
                </div>
            </div>
        </div>
        
        <!-- إحصائيات الدروس المتبقية -->
        <div class="stat-box">
            <h3>الدروس المتبقية</h3>
            <p class="stat-number"><?php echo $statistics['remaining_count']; ?></p>
            <p class="stat-duration"><?php echo format_time($statistics['remaining_duration']); ?></p>
        </div>
        
        <!-- إجمالي الدروس -->
        <div class="stat-box">
            <h3>إجمالي الدروس</h3>
            <p class="stat-number"><?php echo $statistics['total_count']; ?></p>
            <p class="stat-duration"><?php echo format_time($statistics['total_duration']); ?></p>
        </div>
        
        <!-- إحصائيات إضافية -->
        <div class="stat-box">
            <h3>إحصائيات إضافية</h3>
            <div class="d-flex justify-content-between mb-2">
                <span>الدروس المهمة:</span>
                <span class="badge bg-warning"><?php echo $statistics['important_count']; ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span>الدروس النظرية:</span>
                <span class="badge bg-info"><?php echo $statistics['theory_count']; ?></span>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<!-- في نهاية الملف قبل إغلاق body -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// دالة لتأكيد الحذف باستخدام SweetAlert2
function confirmDelete(languageId) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: 'سيتم حذف اللغة وجميع الدورات والدروس المرتبطة بها',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، احذف',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteForm' + languageId).submit();
        }
    });
    return false;
}

// تحديث نموذج الحذف
document.querySelectorAll('.delete-language-form').forEach(form => {
    form.onsubmit = function(e) {
        e.preventDefault();
        confirmDelete(this.querySelector('[name="language_id"]').value);
    };
});

// معالجة رسائل النجاح والخطأ
<?php if (isset($_SESSION['success'])): ?>
    Swal.fire({
        title: 'تم بنجاح',
        text: '<?php echo $_SESSION['success']; ?>',
        icon: 'success',
        timer: 3000,
        showConfirmButton: false
    });
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    Swal.fire({
        title: 'خطأ',
        text: '<?php echo $_SESSION['error']; ?>',
        icon: 'error'
    });
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

// تحسين البحث
const searchInput = document.querySelector('input[name="search"]');
let searchTimeout;

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        this.closest('form').submit();
    }, 500);
});
</script>
