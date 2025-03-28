<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام المساعد الذكي</title>
    
    <!-- الخطوط -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    
    <!-- المكتبات الخارجية -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/4.19.1/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- الستايل الخاص -->
    <link href="css/style.css" rel="stylesheet">

    <!-- إضافة مكتبة TinyMCE قبل نهاية head -->
    <script src="https://cdn.tiny.cloud/1/7e1mldkbut3yp4tyeob9lt5s57pb8wrb5fqbh11d6n782gm7/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    </head>
<body>
    <!-- الهيدر -->
    <header class="main-header">
        <div class="header-content">
            <div class="logo">
                <img src="images/logo.png" alt="شعار المساعد الذكي" class="logo-icon">
                <h1>المساعد الذكي</h1>
            </div>
            <div class="header-actions">
                <button id="showHistoryBtn" class="action-btn">
                    <i class="fas fa-history"></i>
                    السجل
                </button>
                <button id="clearHistoryBtn" class="action-btn">
                    <i class="fas fa-trash"></i>
                    مسح
                </button>
            </div>
        </div>
    </header>

    <!-- منطقة المحتوى الرئيسي -->
    <main class="chat-container">
        <!-- فلتر اللغات -->
        <div class="language-filter-container">
            <div class="filter-header">
                <h4>تصفية حسب اللغة</h4>
            </div>
            <div id="languageFilters" class="language-filters">
                <!-- سيتم إضافة فلاتر اللغات هنا ديناميكياً -->
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
            </div>
        </div>

        <!-- إضافة قسم الكورسات -->
        <div id="coursesContainer" class="courses-container"></div>
        
        <!-- عنصر التحميل -->
        <div id="loading" class="d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
        </div>
        
        <!-- عنصر الخطأ -->
        <div id="error" class="d-none"></div>
    </main>

    <!-- الفوتر -->
    <footer class="main-footer">
        <div class="footer-content">
            <div class="row py-4">
                <!-- قسم روابط الدروس -->
                <div class="col-md-4 mb-4">
                    <h5 class="text-white mb-3">عرض الدروس</h5>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2">
                            <a href="views/lessons-cards.php" class="footer-link">
                                <i class="fas fa-th-large me-2"></i>
                                عرض البطاقات
                            </a>
                        </li>
                        <li>
                            <a href="lessons.php" class="footer-link">
                                <i class="fas fa-list me-2"></i>
                                عرض القائمة
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- قسم الروابط الرئيسية -->
                <div class="col-md-4 mb-4">
                    <h5 class="text-white mb-3">روابط رئيسية</h5>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2">
                            <a href="http://videomx.com/content/languages.php" class="footer-link">
                                <i class="fas fa-globe me-2"></i>
                                اللغات
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="/" class="footer-link">
                                <i class="fas fa-home me-2"></i>
                                الرئيسية
                            </a>
                        </li>
                        <li>
                            <a href="http://videomx.com/content/index.php" class="footer-link">
                                <i class="fas fa-graduation-cap me-2"></i>
                                الدورات
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- قسم الروابط الإضافية -->
                <div class="col-md-4 mb-4">
                    <h5 class="text-white mb-3">روابط إضافية</h5>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2">
                            <a href="http://videomx.com/add/add.php" class="footer-link">
                                <i class="fas fa-cog me-2"></i>
                                الإعدادات
                            </a>
                        </li>
                        <li>
                            <a href="http://videomx.com/GBT/" class="footer-link">
                                <i class="fas fa-robot me-2"></i>
                                المساعد الذكي
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <hr class="my-3" style="border-color: rgba(255,255,255,0.1);">
            
            <div class="text-center mt-3">
                <div class="social-links mb-3">
                    <a href="#" class="social-link" title="فيسبوك"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="social-link" title="تويتر"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link" title="يوتيوب"><i class="fab fa-youtube"></i></a>
                    <a href="#" class="social-link" title="انستغرام"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link" title="لينكد إن"><i class="fab fa-linkedin"></i></a>
                    <a href="#" class="social-link" title="جيت هب"><i class="fab fa-github"></i></a>
                </div>
                <p class="mb-0">جميع الحقوق محفوظة © 2024 VideoMX</p>
            </div>
        </div>
    </footer>

    <!-- نافذة Toast -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">تنبيه</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <!-- سيتم إضافة محتوى التنبيه هنا ديناميكياً -->
            </div>
        </div>
    </div>

    <!-- المكتبات JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/4.19.1/mdb.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- الملفات JavaScript الخاصة -->
    <script type="module" src="js/main.js"></script>

    <!-- إضافة هذه الأسطر قبل نهاية body -->
    <link href="css/courses.css" rel="stylesheet">
    <script src="js/chatgpt-link.js"></script>
    <script src="js/courses.js"></script>
</body>
</html> 