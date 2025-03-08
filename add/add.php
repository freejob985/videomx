<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الكورسات</title>
    
    <!-- المكتبات الخارجية -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/4.0.0/font/MaterialIcons-Regular.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <!-- إضافة Select2 للقوائم المنسدلة -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #475569;
            --accent-color: #3b82f6;
            --gradient-start: #2563eb;
            --gradient-end: #1d4ed8;
        }
        
        body {
            font-family: 'Cairo', 'Tajawal', sans-serif;
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* تصميم الهيدر */
        .custom-header {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            padding: 1rem 0;
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .custom-card {
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            background-color: white;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        /* تصميم الفوتر */
        .custom-footer {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            padding: 2rem 0;
            margin-top: auto;
        }

        .social-links a {
            color: white;
            margin: 0 10px;
            font-size: 1.5rem;
            transition: transform 0.3s ease;
        }

        .social-links a:hover {
            transform: translateY(-3px);
        }

        /* تحسينات إضافية للنموذج */
        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }

        /* أنماط إضافية للنموذج */
        .modal-header {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
        }
        
        .select2-container--bootstrap-5 .select2-selection {
            border-color: #dee2e6;
        }
        
        .select2-container--bootstrap-5 .select2-selection:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }
        
        .action-button {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .sortable {
            cursor: pointer;
            position: relative;
        }
        
        .sortable:after {
            content: '↕';
            position: absolute;
            right: 8px;
            color: #999;
        }
        
        .sortable.asc:after {
            content: '↑';
        }
        
        .sortable.desc:after {
            content: '↓';
        }
        
        .table-search {
            padding-right: 30px;
        }
        
        .table-search-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .swal2-popup {
            font-family: 'Cairo', sans-serif;
        }

        .swal2-popup .select2-container {
            width: 100% !important;
            margin-top: 5px;
        }

        .swal2-popup .form-label {
            text-align: right;
            display: block;
            margin-bottom: 0.5rem;
        }

        .swal2-validation-message {
            background-color: #fff3cd !important;
            color: #856404 !important;
            border-color: #ffeeba !important;
            padding: 0.75rem !important;
            margin-top: 1rem !important;
            border-radius: 0.25rem !important;
            text-align: right !important;
        }

        /* تنسيق الجداول */
        .table-custom {
            width: 100%;
            margin-bottom: 1rem;
            background-color: transparent;
            border-collapse: collapse;
        }

        .table-custom thead {
            background: linear-gradient(135deg, #1a237e, #0d47a1);
        }
        
        .table-custom thead tr {
            background: none !important;
            border: none;
        }
        
        .table-custom thead th {
            color: white;
            padding: 1.2rem 1rem;
            font-weight: 600;
            border: none !important;
            text-align: right;
            white-space: nowrap;
            position: relative;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
            background: none !important;
        }

        /* تأثير التحويم على رؤوس الجداول */
        .table-custom thead th:hover {
            background: linear-gradient(135deg, #0d47a1, #1565c0) !important;
        }

        /* إزالة الحدود والخلفيات الافتراضية */
        .table-custom thead th,
        .table-custom tbody td {
            border: none;
            background-clip: padding-box;
        }

        /* تنسيق الهيدر والفوتر */
        .table-header {
            background: linear-gradient(135deg, #1a237e, #0d47a1);
            color: white;
            padding: 1.2rem;
            border-radius: 0.5rem 0.5rem 0 0;
            margin-bottom: 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: none;
        }

        .table-footer {
            background: linear-gradient(135deg, #1a237e, #0d47a1);
            color: white;
            padding: 1.2rem;
            border-radius: 0 0 0.5rem 0.5rem;
            margin-top: 0;
            box-shadow: 0 -2px 4px rgba(0,0,0,0.1);
            border: none;
        }

        /* تحسين تباين الصفوف */
        .table-custom tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .table-custom tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .table-custom td {
            padding: 1rem;
            vertical-align: middle;
            border: none;
        }

        /* إلغاء تأثيرات Bootstrap الافتراضية */
        .table-custom.table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.05) !important;
        }

        .table > :not(caption) > * > * {
            background-color: transparent !important;
            box-shadow: none !important;
        }

        /* تحسين تنسيق الأزرار في الجدول */
        .table-custom .btn {
            margin: 0 0.2rem;
            padding: 0.4rem 0.8rem;
        }

        /* تحسين عرض الأيقونات */
        .table-custom .fas {
            font-size: 0.9rem;
        }

        .section-content {
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .section-content.collapsed {
            height: 0;
            padding: 0;
            margin: 0;
        }

        .btn-link {
            color: #666;
            text-decoration: none;
            padding: 0.5rem;
        }

        .btn-link i {
            transition: transform 0.3s ease;
        }

        .btn-link.collapsed i {
            transform: rotate(-90deg);
        }

        .pagination-controls {
            display: flex;
            gap: 0.5rem;
        }

        .pages-info {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- الهيدر -->
    <header class="custom-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h4 mb-0">نظام الإدارة</h1>
                <nav>
                    <a href="http://videomx.com/content/index.php" class="text-white text-decoration-none ms-3">
                        <i class="fas fa-home"></i> الرئيسية
                    </a>
                    <a href="http://videomx.com/content/languages.php" class="text-white text-decoration-none ms-3">
                        <i class="fas fa-language"></i> اللغات
                    </a>
                    <a href="http://videomx.com/add/add.php" class="text-white text-decoration-none ms-3">
                        <i class="fas fa-cog"></i> الإعدادات
                    </a>
                    <a href="http://videomx.com/GBT/" class="text-white text-decoration-none">
                        <i class="fas fa-robot"></i> المساعد الذكي
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- المحتوى الرئيسي -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- نموذج إضافة كورس -->
                <div class="card custom-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">إضافة كورس جديد</h5>
                    </div>
                    <div class="card-body">
                        <form id="courseForm">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="youtubePlaylist" class="form-label">رابط قائمة التشغيل</label>
                                    <input type="url" class="form-control" id="youtubePlaylist" 
                                           placeholder="https://www.youtube.com/playlist?list=..." required>
                                    <div class="form-text">أدخل رابط قائمة التشغيل من YouTube</div>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label for="courseLanguage" class="form-label">اللغة</label>
                                    <select class="form-select" id="courseLanguage" required>
                                        <option value="">اختر اللغة</option>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" id="submitCourse">
                                <i class="fas fa-plus"></i> إضافة الكورس
                            </button>
                        </form>
                    </div>
                </div>

                <!-- جدول الكورسات -->
                <div class="card custom-card">
                    <div class="table-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">قائمة الكورسات</h5>
                            <button class="btn btn-light btn-sm" id="refreshCoursesBtn">
                                <i class="fas fa-sync"></i> تحديث
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom mb-0" id="coursesTable">
                            <thead>
                                <tr>
                                    <th class="sortable" width="5%">#</th>
                                    <th class="sortable" width="50%">الكورس</th>
                                    <th class="sortable" width="20%">اللغة</th>
                                    <th class="sortable" width="15%">عدد الدروس</th>
                                    <th width="10%">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center">جاري التحميل...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="table-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>إجمالي الكورسات: <span id="totalCourses">0</span></div>
                            <div>إجمالي الدروس: <span id="totalLessons">0</span></div>
                        </div>
                    </div>
                </div>

                <!-- نموذج إدارة اللغات -->
                <div class="custom-card p-4 mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800 mb-0">
                            <i class="fas fa-language ms-1"></i>
                            إدارة اللغات
                        </h2>
                        <button class="btn btn-link" onclick="toggleSection('languageFormSection')">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div id="languageFormSection" class="section-content">
                        <form id="languageForm" class="space-y-4">
                            <div class="mb-3">
                                <label for="languageTags" class="form-label fw-bold">اللغات</label>
                                <select class="form-control" id="languageTags" multiple>
                                    <!-- سيتم إضافة التاجات هنا -->
                                </select>
                                <small class="form-text text-muted">
                                    اكتب اسم اللغة واضغط Enter لإضافتها
                                </small>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-plus me-1"></i>
                                    إضافة اللغات
                                </button>
                            </div>
                        </form>

                        <!-- جدول اللغات مع الترقيم -->
                        <div class="table-responsive mt-4">
                            <table class="table table-custom" id="languagesTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>اسم اللغة</th>
                                        <th>عدد الكورسات</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- سيتم ملؤه عبر JavaScript -->
                                </tbody>
                            </table>
                            <!-- أزرار التنقل بين الصفحات -->
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="pages-info">
                                    عرض <span id="languagesCurrentPage">1</span> من <span id="languagesTotalPages">1</span>
                                </div>
                                <div class="pagination-controls">
                                    <button class="btn btn-sm btn-secondary me-2" onclick="changePage('languages', 'prev')">السابق</button>
                                    <button class="btn btn-sm btn-secondary" onclick="changePage('languages', 'next')">التالي</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- نموذج إدارة الحالات -->
                <div class="custom-card p-4 mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800 mb-0">
                            <i class="fas fa-tasks ms-1"></i>
                            إدارة الحالات
                        </h2>
                        <button class="btn btn-link" onclick="toggleSection('statusFormSection')">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div id="statusFormSection" class="section-content">
                        <form id="statusForm" class="space-y-4">
                            <div class="mb-3">
                                <label for="statusLanguage" class="form-label fw-bold">اللغة</label>
                                <select class="form-select" id="statusLanguage" required>
                                    <option value="">اختر اللغة...</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="statusTags" class="form-label fw-bold">الحالات</label>
                                <select class="form-control" id="statusTags" multiple>
                                    <!-- سيتم إضافة التاجات هنا -->
                                </select>
                                <small class="form-text text-muted">
                                    اكتب اسم الحالة واضغط Enter لإضافتها
                                </small>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-plus me-1"></i>
                                    إضافة الحالات
                                </button>
                            </div>
                        </form>

                        <!-- جدول الحالات -->
                        <div class="table-responsive mt-4">
                            <table class="table table-custom" id="statusesTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>اللغة</th>
                                        <th>اسم الحالة</th>
                                        <th>عدد الكورسات</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="5" class="text-center">جاري التحميل...</td>
                                    </tr>
                                </tbody>
                            </table>
                            <!-- أزرار التنقل بين الصفحات -->
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="pages-info">
                                    عرض <span id="statusesCurrentPage">1</span> من <span id="statusesTotalPages">1</span>
                                </div>
                                <div class="pagination-controls">
                                    <button class="btn btn-sm btn-secondary me-2" onclick="changePage('statuses', 'prev')">السابق</button>
                                    <button class="btn btn-sm btn-secondary" onclick="changePage('statuses', 'next')">التالي</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- نموذج إدارة الأقسام -->
                <div class="custom-card p-4 mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800 mb-0">
                            <i class="fas fa-folder ms-1"></i>
                            إدارة الأقسام
                        </h2>
                        <button class="btn btn-link" onclick="toggleSection('sectionFormSection')">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div id="sectionFormSection" class="section-content">
                        <form id="sectionForm" class="space-y-4">
                            <div class="mb-3">
                                <label for="sectionLanguage" class="form-label fw-bold">اللغة</label>
                                <select class="form-select" id="sectionLanguage" required>
                                    <option value="">اختر اللغة...</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="sectionTags" class="form-label fw-bold">الأقسام</label>
                                <select class="form-control" id="sectionTags" multiple>
                                    <!-- سيتم إضافة التاجات هنا -->
                                </select>
                                <small class="form-text text-muted">
                                    اكتب اسم القسم واضغط Enter لإضافته
                                </small>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-plus me-1"></i>
                                    إضافة الأقسام
                                </button>
                            </div>
                        </form>

                        <!-- جدول الأقسام -->
                        <div class="table-responsive mt-4">
                            <table class="table table-custom" id="sectionsTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>اللغة</th>
                                        <th>اسم القسم</th>
                                        <th>عدد الكورسات</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="5" class="text-center">جاري التحميل...</td>
                                    </tr>
                                </tbody>
                            </table>
                            <!-- أزرار التنقل بين الصفحات -->
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="pages-info">
                                    عرض <span id="sectionsCurrentPage">1</span> من <span id="sectionsTotalPages">1</span>
                                </div>
                                <div class="pagination-controls">
                                    <button class="btn btn-sm btn-secondary me-2" onclick="changePage('sections', 'prev')">السابق</button>
                                    <button class="btn btn-sm btn-secondary" onclick="changePage('sections', 'next')">التالي</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- أزرار العمليات الجماعية -->
                <div class="custom-card p-4 mt-5">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-cogs ms-1"></i>
                        العمليات الجماعية
                    </h2>
                    <div class="d-flex gap-3 flex-wrap">
                        <button class="btn btn-danger" onclick="deleteAllLessons()">
                            <i class="fas fa-trash me-1"></i>
                            حذف جميع الدروس
                        </button>
                        <button class="btn btn-danger" onclick="truncateAllTables()">
                            <i class="fas fa-trash-alt me-1"></i>
                            حذف جميع البيانات
                        </button>
                        <button class="btn btn-primary" onclick="addDefaultData()">
                            <i class="fas fa-database me-1"></i>
                            إضافة البيانات الافتراضية
                        </button>
                        <button class="btn btn-success" onclick="addDefaultStatuses()">
                            <i class="fas fa-tasks me-1"></i>
                            إضافة الحالات الافتراضية
                        </button>
                        <button class="btn btn-info" onclick="addDefaultLanguages()">
                            <i class="fas fa-code me-1"></i>
                            إضافة لغات البرمجة
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- الفوتر -->
    <footer class="custom-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="mb-3">روابط سريعة</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="http://videomx.com/content/views/lessons-cards.php?course_id=1" class="text-white text-decoration-none">
                                <i class="fas fa-id-card me-2"></i>عرض البطاقات
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="http://videomx.com/content/lessons.php?course_id=1" class="text-white text-decoration-none">
                                <i class="fas fa-book-open me-2"></i>عرض الدروس
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="http://videomx.com/content/languages.php" class="text-white text-decoration-none">
                                <i class="fas fa-language me-2"></i>اللغات
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="http://videomx.com/content/index.php" class="text-white text-decoration-none">
                                <i class="fas fa-graduation-cap me-2"></i>الدورات
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="http://videomx.com/review/" class="text-white text-decoration-none">
                                <i class="fas fa-star me-2"></i>المراجعة
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="col-md-4 text-center mb-4 mb-md-0">
                    <h5 class="mb-3">تواصل معنا</h5>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>

                <div class="col-md-4 text-md-end">
                    <h5 class="mb-3">روابط مهمة</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="http://videomx.com/add/add.php" class="text-white text-decoration-none">
                                <i class="fas fa-cog me-2"></i>الإعدادات
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="http://videomx.com/GBT/" class="text-white text-decoration-none">
                                <i class="fas fa-robot me-2"></i>المساعد الذكي
                            </a>
                        </li>
                    </ul>
                    <p class="mt-3 mb-0">جميع الحقوق محفوظة © 2024</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- إضافة jQuery قبل باقي المكتبات JavaScript -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- المكتبات JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<?php
include('js.php');
?>

    <!-- مودال عرض التقدم -->
    <div class="modal fade" id="progressModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">جاري إضافة الدروس</h5>
                    <div class="ms-auto">
                        <button type="button" class="btn btn-sm btn-outline-primary pause-resume-btn">
                            <i class="fas fa-pause"></i> إيقاف مؤقت
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    <!-- شريط التقدم -->
                    <div class="progress mb-3" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%" 
                             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    
                    <!-- معلومات التقدم -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="progress-text">0 من 0</span>
                        <span class="progress-percentage badge bg-primary">0%</span>
                    </div>
                    
                    <!-- الوقت المتبقي والسرعة -->
                    <div class="time-stats mb-3">
                        <div class="d-flex justify-content-between text-muted">
                            <span class="estimated-time">الوقت المتبقي: جاري الحساب...</span>
                            <span class="processing-speed">السرعة: 0 درس/دقيقة</span>
                        </div>
                    </div>

                    <!-- الدروس المتبقية -->
                    <div class="remaining-lessons text-muted mb-3">
                        جاري البدء...
                    </div>
                    
                    <!-- آخر درس -->
                    <div class="latest-lesson border-top pt-3">
                        <div class="text-center text-muted">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            جاري التحميل...
                        </div>
                    </div>

                    <!-- الإحصائيات -->
                    <div class="stats border-top mt-3 pt-3">
                        <h6 class="mb-3">إحصائيات المعالجة:</h6>
                        <div class="row">
                            <div class="col-6 mb-2">
                                <div class="text-muted">الدروس المضافة</div>
                                <div class="processed-count fw-bold">0</div>
                            </div>
                            <div class="col-6 mb-2">
                                <div class="text-muted">الدروس المتبقية</div>
                                <div class="remaining-count fw-bold">0</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">الوقت المنقضي</div>
                                <div class="elapsed-time fw-bold">00:00:00</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">حالة المعالجة</div>
                                <div class="processing-status fw-bold">جاري التحميل</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>