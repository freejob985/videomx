<?php
/**
 * فوتر الموقع
 * يحتوي على روابط التنقل الرئيسية وتذييل الصفحة
 * 
 * المتطلبات:
 * - اتصال قاعدة البيانات
 * - عرض اللغات المتاحة التي تحتوي على دروس
 */

// إعداد اتصال قاعدة البيانات
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'courses_db';

// إنشاء اتصال جديد
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// ضبط الترميز
$conn->set_charset("utf8");

// تحديث الاستعلام لجلب عدد الدروس لكل لغة
$languages_query = "SELECT 
                        l.*,
                        COUNT(DISTINCT les.id) as lessons_count
                    FROM languages l
                    INNER JOIN courses c ON c.language_id = l.id
                    INNER JOIN lessons les ON les.course_id = c.id
                    WHERE les.status_id = 1
                    GROUP BY l.id
                    ORDER BY l.name ASC";

// تنفيذ الاستعلام
$languages_result = $conn->query($languages_query);

// تحويل النتائج إلى مصفوفة
$available_languages = [];
if ($languages_result) {
    while ($row = $languages_result->fetch_assoc()) {
        $available_languages[] = $row;
    }
}

// إغلاق نتيجة الاستعلام
$languages_result->close();
?>

<!-- المكتبات JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.11.1/typeahead.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.min.js"></script>

<!-- تهيئة المكتبات -->
<script>
$(document).ready(function() {
    // إعدادات Toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-left",
        "timeOut": "3000"
    };
});
</script>

<!-- تحميل ملفات JavaScript الخاصة بالتطبيق -->
<script src="assets/js/main.js"></script>
<script src="assets/js/lessons.js"></script>

<!-- فوتر الموقع -->
<footer class="site-footer mt-5">
    <div class="footer-gradient">
        <div class="container">
            <div class="row py-5">
                <!-- القسم الأول: روابط الدروس -->
                <div class="col-md-4 mb-4">
                    <h5 class="text-white mb-4">عرض الدروس</h5>
                    <ul class="list-unstyled footer-links">
                        <?php if (isset($course_id)): ?>
                            <li class="mb-2">
                                <a href="<?php echo getCorrectPath("views/lessons-cards.php?course_id=" . $course_id); ?>">
                                    <i class="fas fa-th-large me-2"></i>
                                    عرض البطاقات
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo getCorrectPath("lessons.php?course_id=" . $course_id); ?>">
                                    <i class="fas fa-list me-2"></i>
                                    عرض الجدول
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <!-- القسم الثاني: روابط التنقل الرئيسية -->
                <div class="col-md-6 mb-4">
                    <h5 class="text-white mb-4">روابط رئيسية</h5>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2">
                            <a href="<?php echo buildUrl('languages.php'); ?>">
                                <i class="fas fa-globe me-2"></i>
                                اللغات
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo buildUrl('index.php'); ?>">
                                <i class="fas fa-home me-2"></i>
                                الرئيسية
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo buildUrl('courses.php'); ?>">
                                <i class="fas fa-graduation-cap me-2"></i>
                                الدورات
                            </a>
                        </li>
                        <li>
                            <a href="http://videomx.com/" class="portal-link">
                                <i class="fas fa-door-open me-2"></i>
                                البوابة
                            </a>
                        </li>
                        <li>
                            <a href="http://videomx.com/content/search/" class="search-link">
                                <i class="fas fa-search me-2"></i>
                                البحث
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- القسم الثالث: روابط إضافية -->
                <div class="col-md-6 mb-4">
                    <h5 class="text-white mb-4">روابط إضافية</h5>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2">
                            <a href="http://videomx.com/add/add.php">
                                <i class="fas fa-cog me-2"></i>
                                الإعدادات
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="http://videomx.com/review/" class="review-link">
                                <i class="fas fa-star me-2"></i>
                                المراجعة
                            </a>
                        </li>
                        <li>
                            <a href="http://videomx.com/GBT/" class="ai-link">
                                <i class="fas fa-robot me-2"></i>
                                المساعد الذكي
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- قسم اللغات المتاحة -->
            <div class="languages-section border-top border-light pt-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="text-white mb-0">اللغات المتاحة</h5>
                    <div class="scroll-controls">
                        <button class="btn btn-scroll" id="prevPage" title="الصفحة السابقة">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span class="text-white mx-2" id="paginationInfo"></span>
                        <button class="btn btn-scroll" id="nextPage" title="الصفحة التالية">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="languages-list-wrapper position-relative">
                    <ul class="list-unstyled footer-links languages-list" id="languagesList">
                        <!-- سيتم تحميل اللغات هنا -->
                    </ul>
                    <div class="loading-spinner d-none">
                        <div class="spinner-border text-light" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- حقوق النشر -->
            <div class="footer-bottom py-3 text-center border-top border-light">
                <p class="mb-0 text-white-50">
                    جميع الحقوق محفوظة &copy; <?php echo date('Y'); ?> 
                    <a href="<?php echo buildUrl(''); ?>" class="text-white">VideoMX</a>
                </p>
            </div>
        </div>
    </div>
</footer>

<style>
/* تنسيقات الفوتر */
.site-footer {
    background: #0d6efd;
    color: #fff;
    margin-top: auto;
}

.footer-gradient {
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
}

.footer-links a {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-block;
}

.footer-links a:hover {
    color: #fff;
    transform: translateX(-5px);
}

.footer-links .ai-link {
    color: #ffc107;
}

.footer-links .ai-link:hover {
    color: #ffcd39;
}

.footer-bottom {
    border-color: rgba(255,255,255,0.1) !important;
}

/* تأثيرات حركية للأيقونات */
.footer-links i {
    transition: transform 0.3s ease;
}

.footer-links a:hover i {
    transform: scale(1.2);
}

/* تحسينات للموبايل */
@media (max-width: 768px) {
    .footer-links {
        text-align: center;
    }
    
    .footer-links a {
        padding: 5px 0;
    }
}

/* تحسينات تنسيق قسم اللغات */
.languages-section {
    border-color: rgba(255,255,255,0.1) !important;
}

.languages-list-wrapper {
    overflow-x: hidden;
    padding-bottom: 10px;
}

.languages-list {
    display: flex;
    flex-wrap: nowrap;
    gap: 12px;
    padding-bottom: 5px;
    transition: transform 0.3s ease;
}

.languages-list li {
    flex: 0 0 auto;
    min-width: 200px;
    max-width: 250px;
}

.languages-list a {
    background: rgba(255, 255, 255, 0.1);
    padding: 12px 15px;
    border-radius: 8px;
    width: 100%;
    display: block;
    transition: all 0.3s ease;
    white-space: nowrap;
}

/* تخصيص شريط التمرير */
.languages-list-wrapper::-webkit-scrollbar {
    height: 6px;
}

.languages-list-wrapper::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.languages-list-wrapper::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 3px;
}

.languages-list-wrapper::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* تحسينات للموبايل */
@media (max-width: 768px) {
    .languages-list li {
        min-width: 180px;
    }
    
    .language-item {
        gap: 10px;
    }
}

/* تنسيقات أزرار التمرير */
.scroll-controls {
    display: flex;
    gap: 8px;
}

.btn-scroll {
    width: 32px;
    height: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    border: none;
    border-radius: 50%;
    color: rgba(255, 255, 255, 0.8);
    transition: all 0.3s ease;
}

.btn-scroll:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
    transform: scale(1.1);
}

.btn-scroll:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.btn-scroll i {
    font-size: 14px;
}

.loading-spinner {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.opacity-50 {
    opacity: 0.5;
}

#paginationInfo {
    font-size: 0.9em;
    min-width: 40px;
    text-align: center;
}
</style>

<!-- تضمين الملفات الأساسية -->
<script src="<?php echo buildUrl('assets/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo buildUrl('assets/js/fontawesome.min.js'); ?>"></script>

<!-- إضافة سكريبت التصفيح -->
<script>
$(document).ready(function() {
    // دالة للحصول على المسار الصحيح للـ API
    function getApiPath() {
        const baseUrl = 'http://videomx.com/content/';
        const currentPath = window.location.pathname;
        
        // التحقق من الصفحات الخاصة
        if (currentPath.includes('/content/views/')) {
            return baseUrl + 'api/get_languages.php';
        }
        
        // المسار الافتراضي
        return baseUrl + 'api/get_languages.php';
    }
    
    let currentPage = 1;
    const perPage = 6;
    let totalPages = 1;
    
    // دالة تحميل اللغات
    function loadLanguages(page) {
        const spinner = $('.loading-spinner');
        const list = $('#languagesList');
        
        spinner.removeClass('d-none');
        list.addClass('opacity-50');
        
        $.ajax({
            url: getApiPath(),
            data: { page: page, per_page: perPage },
            method: 'GET',
            success: function(response) {
                list.empty();
                
                response.languages.forEach(function(language) {
                    const baseUrl = 'http://videomx.com/content/';
                    const languageItem = `
                        <li>
                            <a href="${baseUrl}courses.php?language_id=${language.id}">
                                <div class="language-item">
                                    <div class="language-info">
                                        <i class="fas fa-code"></i>
                                        <span class="language-name">${language.name}</span>
                                    </div>
                                    <span class="lessons-count" title="عدد الدروس">
                                        ${language.lessons_count}
                                        <small>درس</small>
                                    </span>
                                </div>
                            </a>
                        </li>
                    `;
                    list.append(languageItem);
                });
                
                currentPage = response.current_page;
                totalPages = response.total_pages;
                
                updatePaginationInfo();
                updatePaginationButtons();
                
                spinner.addClass('d-none');
                list.removeClass('opacity-50');
            },
            error: function() {
                toastr.error('حدث خطأ أثناء تحميل اللغات');
                spinner.addClass('d-none');
                list.removeClass('opacity-50');
            }
        });
    }
    
    // تحديث معلومات التصفيح
    function updatePaginationInfo() {
        $('#paginationInfo').text(`${currentPage} / ${totalPages}`);
    }
    
    // تحديث حالة أزرار التصفيح
    function updatePaginationButtons() {
        $('#prevPage').prop('disabled', currentPage <= 1);
        $('#nextPage').prop('disabled', currentPage >= totalPages);
    }
    
    // معالجة النقر على زر الصفحة السابقة
    $('#prevPage').on('click', function() {
        if (currentPage > 1) {
            loadLanguages(currentPage - 1);
        }
    });
    
    // معالجة النقر على زر الصفحة التالية
    $('#nextPage').on('click', function() {
        if (currentPage < totalPages) {
            loadLanguages(currentPage + 1);
        }
    });
    
    // تحميل الصفحة الأولى عند تهيئة الصفحة
    loadLanguages(1);
});
</script>

<!-- تعديل روابط عرض الدروس -->
<?php
// دالة لتحديد المسار الصحيح
function getCorrectPath($path) {
    $current_url = $_SERVER['REQUEST_URI'];
    $base_url = "http://videomx.com/content/";
    
    // التحقق من نوع الصفحة الحالية
    if (strpos($current_url, '/content/views/') !== false) {
        // في حالة صفحات العرض
        if (strpos($path, 'views/') === 0) {
            // إذا كان المسار يبدأ بـ views/
            return $base_url . $path;
        } else {
            // للمسارات الأخرى
            return $base_url . $path;
        }
    } else if (strpos($current_url, '/content/') !== false) {
        // في حالة الصفحات داخل مجلد content
        return $base_url . $path;
    }
    
    // المسار الافتراضي
    return $base_url . $path;
}
?>

<?php
// إغلاق اتصال قاعدة البيانات
$conn->close();
?>
</body>
</html>