<?php
require_once '../includes/functions.php';
require_once '../includes/sections_functions.php';

$section_id = $_GET['section_id'] ?? null;

// التحقق من وجود القسم
if (!$section_id || !sectionExists($section_id)) {
    $_SESSION['error'] = 'القسم غير موجود';
    header('Location: /content/index.php');
    exit;
}

// جلب معلومات القسم والدروس
$section = getSectionInfo($section_id);
$lessons = getLessonsBySection($section_id);
$language = getLanguageInfo($section['language_id']);
$pageTitle = 'دروس ' . $section['name'];

// جلب قائمة الأقسام المتاحة للغة
$available_sections = getSectionsByLanguage($language['id']);

require_once '../includes/header.php';
?>

<!-- إضافة مكتبات SweetAlert2 -->
<link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<!-- إضافة TinyMCE -->
<script src="https://cdn.tiny.cloud/1/7e1mldkbut3yp4tyeob9lt5s57pb8wrb5fqbh11d6n782gm7/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<!-- شريط التنقل -->
<div class="navigation-bar bg-light py-3 mb-4 border-bottom">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="d-flex gap-2">
                    <a href="/content/index.php" class="btn btn-outline-primary">
                        <i class="fas fa-home me-1"></i>
                        الرئيسية
                    </a>
                    <a href="/sections/index.php?language_id=<?php echo $language['id']; ?>" 
                       class="btn btn-outline-primary">
                        <i class="fas fa-folder me-1"></i>
                        أقسام <?php echo htmlspecialchars($language['name']); ?>
                    </a>
                </div>
            </div>
            
            <div class="col text-center">
                <h4 class="mb-0 text-primary">
                    <?php echo htmlspecialchars($section['name']); ?>
                </h4>
            </div>
        </div>
    </div>
</div>

<!-- تنسيق الصفحة -->
<style>
/* تنسيق الجدول الرئيسي */
.lessons-table {
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    border-radius: 15px;
    overflow: hidden;
}

.lessons-table .card-header {
    background: linear-gradient(135deg, #2193b0, #6dd5ed);
    color: white;
    padding: 1.5rem;
    border-bottom: none;
}

.lessons-table .card-header h4 {
    margin: 0;
    font-weight: 600;
}

.lessons-table .card-body {
    padding: 0;
}

/* تنسيق رأس الجدول */
.lessons-table thead {
    background: linear-gradient(135deg, #2193b0, #6dd5ed);
    color: white;
}

.lessons-table th {
    padding: 15px;
    text-align: center;
    font-weight: 600;
    border: none;
}

/* تنسيق صفوف الجدول */
.lessons-table tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #e9ecef;
}

.lessons-table tbody tr:hover {
    background-color: rgba(109, 213, 237, 0.1);
    cursor: pointer;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* تنسيق عنوان الدرس */
.lesson-title {
    color: #2193b0;
    font-weight: 500;
    font-size: 1.1rem;
}

/* تنسيق شارة الكورس */
.course-badge {
    background: linear-gradient(135deg, #ff9966, #ff5e62);
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.85rem;
    display: inline-block;
    transition: all 0.3s ease;
}

.course-badge:hover {
    transform: scale(1.05);
}

/* تنسيق أزرار الإجراءات */
.action-buttons {
    display: flex;
    gap: 8px;
    align-items: center;
}

.action-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    background: white;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.action-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.action-btn:active {
    transform: translateY(-1px);
}

/* تنسيق زر التشغيل */
.btn-play {
    background: linear-gradient(45deg, #4CAF50, #8BC34A);
}

.btn-play i {
    color: white;
    font-size: 1.2rem;
}

.btn-play:hover {
    background: linear-gradient(45deg, #43A047, #7CB342);
}

/* تنسيق زر النقل */
.btn-move {
    background: linear-gradient(45deg, #2196F3, #03A9F4);
}

.btn-move i {
    color: white;
    font-size: 1.2rem;
}

.btn-move:hover {
    background: linear-gradient(45deg, #1E88E5, #039BE5);
}

/* تنسيق زر التعديل */
.btn-edit {
    background: linear-gradient(45deg, #FF9800, #FFC107);
}

.btn-edit i {
    color: white;
    font-size: 1.2rem;
}

.btn-edit:hover {
    background: linear-gradient(45deg, #F57C00, #FFB300);
}

/* تنسيق زر الإكمال */
.btn-complete {
    background: linear-gradient(45deg, #4CAF50, #8BC34A);
}

.btn-complete i {
    color: white;
    font-size: 1.2rem;
}

.btn-outline-success {
    border: 2px solid #4CAF50;
    background: white;
}

.btn-outline-success i {
    color: #4CAF50;
    font-size: 1.2rem;
}

.btn-outline-success:hover {
    background: linear-gradient(45deg, #4CAF50, #8BC34A);
}

.btn-outline-success:hover i {
    color: white;
}

/* تأثير الريبل عند الضغط */
.action-btn::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
    background-image: radial-gradient(circle, #fff 10%, transparent 10.01%);
    background-repeat: no-repeat;
    background-position: 50%;
    transform: scale(10, 10);
    opacity: 0;
    transition: transform .5s, opacity 1s;
}

.action-btn:active::after {
    transform: scale(0, 0);
    opacity: .3;
    transition: 0s;
}

/* تنسيق التولتيب */
[title] {
    position: relative;
}

[title]:hover::before {
    content: attr(title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    padding: 5px 10px;
    background: rgba(0,0,0,0.8);
    color: white;
    font-size: 12px;
    border-radius: 4px;
    white-space: nowrap;
    pointer-events: none;
    opacity: 0;
    animation: fadeIn 0.3s ease forwards;
}

@keyframes fadeIn {
    to {
        opacity: 1;
        transform: translateX(-50%) translateY(-5px);
    }
}

/* تنسيق النافذة المنبثقة */
.video-modal .modal-header {
    background: linear-gradient(135deg, #2193b0, #6dd5ed);
    color: white;
    border: none;
}

.video-modal .modal-content {
    border-radius: 15px;
    overflow: hidden;
}

/* تنسيق معلومات القسم */
.section-info {
    background: linear-gradient(135deg, #f6f9fc, #f1f4f8);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 30px;
}

.section-stats {
    display: flex;
    gap: 20px;
    margin-top: 15px;
}

.stat-item {
    background: white;
    padding: 10px 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

/* تنسيق وصف القسم */
.section-description {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.section-description h1,
.section-description h2,
.section-description h3,
.section-description h4,
.section-description h5,
.section-description h6 {
    color: #2193b0;
    margin-bottom: 1rem;
}

.section-description p {
    color: #4a5568;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.section-description ul,
.section-description ol {
    padding-right: 20px;
    margin-bottom: 1rem;
}

.section-description li {
    margin-bottom: 0.5rem;
    color: #4a5568;
}

.section-description a {
    color: #2193b0;
    text-decoration: none;
    transition: color 0.3s ease;
}

.section-description a:hover {
    color: #1a7083;
    text-decoration: underline;
}

.section-description code {
    background: #f1f4f8;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
    color: #e83e8c;
}

.section-description pre {
    background: #2d3748;
    color: #fff;
    padding: 15px;
    border-radius: 8px;
    overflow-x: auto;
    margin-bottom: 1rem;
}

.section-description blockquote {
    border-right: 4px solid #2193b0;
    padding: 10px 20px;
    margin: 0 0 1rem;
    background: #f8f9fa;
    font-style: italic;
}

.section-description img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 1rem 0;
}

.section-description table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
}

.section-description th,
.section-description td {
    padding: 8px;
    border: 1px solid #e2e8f0;
}

.section-description th {
    background: #f8f9fa;
    font-weight: 600;
}

/* تحسينات للتجاوب */
@media (max-width: 768px) {
    .section-description {
        padding: 15px;
    }
    
    .section-description pre {
        padding: 10px;
    }
}

/* تنسيق Modal تغيير القسم */
#changeSectionModal .modal-header {
    background: linear-gradient(135deg, #2193b0, #6dd5ed);
    color: white;
}

#changeSectionModal .modal-content {
    border-radius: 15px;
    overflow: hidden;
}

#sectionSelect {
    border-radius: 8px;
    padding: 10px;
    border-color: #e2e8f0;
}

#sectionSelect:focus {
    border-color: #2193b0;
    box-shadow: 0 0 0 0.2rem rgba(33, 147, 176, 0.25);
}

/* تحسين أزرار الإجراءات */
.btn-info {
    background: linear-gradient(135deg, #00b4db, #0083b0);
    border: none;
    color: white;
}

.btn-info:hover {
    background: linear-gradient(135deg, #0083b0, #00b4db);
    color: white;
}

/* تنسيق شريط التقدم */
.progress {
    background-color: #e9ecef;
    border-radius: 10px;
    box-shadow: inset 0 1px 3px rgba(0,0,0,.2);
}

.progress-bar {
    background: linear-gradient(135deg, #28a745, #20c997);
    border-radius: 10px;
    transition: width 0.5s ease;
    font-size: 0.9rem;
    font-weight: 600;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

/* تنسيق الأزرار */
.btn-group .btn i {
    font-size: 1.1rem;
}

.btn-play i { color: #fff; }
.btn-edit i { color: #fff; }
.btn-info i { color: #fff; }

/* تأثيرات حركية */
.btn-group .btn {
    transition: all 0.3s ease;
}

.btn-group .btn:hover {
    transform: translateY(-2px);
}

.btn-success .fa-check-circle {
    animation: pulse 1s ease;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* تحديث نمط CSS للدروس المكتملة */
.completed-lesson {
    position: relative;
}

.completed-lesson::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    border-top: 2px solid rgba(40, 167, 69, 0.3);
    pointer-events: none;
}

/* تنسيق قسم الملاحظات */
.lesson-notes {
    background: #f8f9fa;
    border-radius: 8px;
    margin-top: 10px;
    padding: 15px;
    box-shadow: inset 0 2px 5px rgba(0,0,0,0.05);
}

.note-item {
    position: relative;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.note-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.delete-note {
    position: absolute;
    top: 10px;
    left: 10px;
    color: #dc3545;
    cursor: pointer;
    opacity: 0;
    transition: all 0.3s ease;
    padding: 5px;
    border-radius: 50%;
    background-color: rgba(255,255,255,0.9);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.note-item:hover .delete-note {
    opacity: 1;
}

.delete-note:hover {
    transform: scale(1.1);
    background-color: #dc3545;
    color: white;
}

.note-content {
    white-space: pre-wrap;
    word-break: break-word;
    margin-right: 10px;
    margin-left: 30px;
    line-height: 1.5;
}

.note-meta {
    color: #6c757d;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
}

/* تأثير حذف الملاحظة */
.fade-out {
    animation: fadeOut 0.3s ease forwards;
}

@keyframes fadeOut {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-10px);
    }
}

/* تنسيق زر الذكاء الاصطناعي */
.btn-ai {
    background: linear-gradient(45deg, #10a37f, #1a7f64);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn-ai:hover {
    background: linear-gradient(45deg, #0d8c6a, #156751);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-ai i {
    font-size: 1.2rem;
}

/* إضافة تأثير نبض للأيقونة */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.btn-ai:hover i {
    animation: pulse 1s infinite;
}

/* تنسيق زر تحليل القسم */
.btn-primary {
    background: linear-gradient(135deg, #2193b0, #6dd5ed);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #1c7a94, #5bb8d0);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-primary:active {
    transform: translateY(-1px);
}

.btn-primary i {
    font-size: 1.1rem;
}

/* تأثير نبض للأيقونة عند التحليل */
@keyframes brainPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.analyzing .fa-brain {
    animation: brainPulse 1s infinite;
}

/* تنسيق زر Grok */
.btn-grok {
    background: linear-gradient(45deg, #6b21a8, #8b5cf6);
}

.btn-grok i {
    color: white;
    font-size: 1.2rem;
}

.btn-grok:hover {
    background: linear-gradient(45deg, #5b1e94, #7c4ddb);
}

/* تأثير نبض للأيقونة */
.btn-grok:hover i {
    animation: pulse 1s infinite;
}

/* تنسيق أزرار التحكم بالوصف */
.btn-outline-danger {
    border-color: #dc3545;
    color: #dc3545;
    transition: all 0.3s ease;
}

.btn-outline-danger:hover {
    background-color: #dc3545;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(220, 53, 69, 0.3);
}

.btn-outline-danger:active {
    transform: translateY(-1px);
}

/* تنسيق أيقونة المسح */
.btn-outline-danger i {
    font-size: 0.9rem;
}
</style>

<!-- معلومات القسم -->
<div class="container py-5">
    <div class="section-info">
        <h3 class="text-primary mb-3">
            <i class="fas fa-folder-open me-2"></i>
            <?php echo htmlspecialchars($section['name']); ?>
        </h3>
        
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="flex-grow-1">
                <?php if (!empty($section['description'])): ?>
                    <div class="section-description">
                        <?php echo $section['description']; ?>
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-outline-danger btn-sm" onclick="clearSectionDescription()">
                            <i class="fas fa-trash-alt me-1"></i>
                            مسح الوصف
                        </button>
                    </div>
                <?php else: ?>
                    <div class="text-muted">
                        لا يوجد وصف للقسم
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="editSectionDescription()">
                            <i class="fas fa-edit me-1"></i>
                            تحرير الوصف
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section-stats">
            <div class="stat-item">
                <i class="fas fa-book-open text-primary me-2"></i>
                <?php echo $section['lessons_count']; ?> درس
            </div>
            <div class="stat-item">
                <i class="fas fa-clock text-success me-2"></i>
                <?php echo formatDuration($section['total_duration']); ?>
            </div>
            <div class="stat-item">
                <i class="fas fa-check-circle text-info me-2"></i>
                <?php echo $section['completed_lessons']; ?> دروس مكتملة
            </div>
        </div>
        <div class="progress mt-4" style="height: 25px;">
            <div class="progress-bar bg-success" 
                 role="progressbar" 
                 style="width: <?php echo ($section['completed_lessons'] / $section['lessons_count']) * 100; ?>%"
                 aria-valuenow="<?php echo $section['completed_lessons']; ?>"
                 aria-valuemin="0" 
                 aria-valuemax="<?php echo $section['lessons_count']; ?>">
                <?php echo round(($section['completed_lessons'] / $section['lessons_count']) * 100); ?>%
            </div>
        </div>
    </div>

    <!-- جدول الدروس -->
    <div class="card lessons-table">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4>
                    <i class="fas fa-list-alt me-2"></i>
                    قائمة الدروس
                </h4>
                <div class="header-actions">
                    <!-- إضافة زر تحليل Grok -->
                    <button class="btn btn-grok me-2" onclick="analyzeSectionWithGrok()">
                        <i class="fas fa-brain me-1"></i>
                        تحليل القسم مع Grok
                    </button>
                    
                    <!-- الأزرار الموجودة مسبقاً -->
                    <button class="btn btn-primary me-2" onclick="analyzeSectionContent()">
                        <i class="fas fa-robot me-1"></i>
                        تحليل محتوى القسم
                    </button>
                    
                    <button class="btn btn-light" onclick="window.print()">
                        <i class="fas fa-print me-1"></i>
                        طباعة
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>عنوان الدرس</th>
                            <th>الكورس</th>
                            <th>المدة</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lessons as $index => $lesson): ?>
                            <tr data-lesson-id="<?php echo $lesson['id']; ?>">
                                <td class="text-center"><?php echo $index + 1; ?></td>
                                <td class="lesson-title">
                                    <span class="lesson-name" ondblclick="confirmLessonRedirect(<?php echo $lesson['id']; ?>, '<?php echo htmlspecialchars($lesson['title']); ?>')">
                                        <?php echo htmlspecialchars($lesson['title']); ?>
                                    </span>
                                    <?php if ($lesson['is_important']): ?>
                                        <i class="fas fa-star text-warning ms-1" title="درس مهم"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="course-badge">
                                        <?php echo htmlspecialchars($lesson['course_title']); ?>
                                    </span>
                                </td>
                                <td class="text-center"><?php echo formatDuration($lesson['duration']); ?></td>
                                <td class="text-center">
                                    <span class="badge" style="background-color: <?php echo $lesson['status_color']; ?>">
                                        <?php echo htmlspecialchars($lesson['status_name']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="action-buttons">
                                        <button class="action-btn btn-play" 
                                                onclick="playVideo('<?php echo htmlspecialchars($lesson['video_url']); ?>', '<?php echo htmlspecialchars($lesson['title']); ?>')"
                                                title="تشغيل الدرس">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        
                                        <button class="action-btn btn-move" 
                                                onclick="editLessonSection(<?php echo $lesson['id']; ?>, <?php echo $section_id; ?>)"
                                                title="نقل الدرس">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                            
                                        
                                        <button class="action-btn <?php echo $lesson['completed'] ? 'btn-complete' : 'btn-outline-success'; ?>"
                                                onclick="toggleLessonCompletion(<?php echo $lesson['id']; ?>, this)"
                                                title="<?php echo $lesson['completed'] ? 'تم الإكمال' : 'تحديد كمكتمل'; ?>">
                                            <i class="fas <?php echo $lesson['completed'] ? 'fa-check-circle' : 'fa-circle'; ?>"></i>
                                        </button>
                                        
                                        <!-- إضافة زر ChatGPT -->
                                        <button class="action-btn btn-ai" 
                                                onclick="askGPTAboutLesson('<?php echo htmlspecialchars($lesson['title']); ?>', '<?php echo htmlspecialchars($section['name']); ?>', '<?php echo htmlspecialchars($language['name']); ?>')"
                                                title="اسأل ChatGPT">
                                            <i class="fas fa-robot"></i>
                                        </button>
                                        
                                        <!-- إضافة زر Grok -->
                                        <button class="action-btn btn-grok" 
                                                onclick="askGrokAboutLesson('<?php echo htmlspecialchars($lesson['title']); ?>', '<?php echo htmlspecialchars($section['name']); ?>', '<?php echo htmlspecialchars($language['name']); ?>')"
                                                title="اسأل Grok">
                                            <i class="fas fa-brain"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="notes-row">
                                <td colspan="6">
                                    <div class="lesson-notes">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0">
                                                <i class="fas fa-sticky-note text-primary me-2"></i>
                                                ملاحظات الدرس
                                            </h6>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="showAddNoteModal(<?php echo $lesson['id']; ?>)">
                                                <i class="fas fa-plus me-1"></i>
                                                إضافة ملاحظة
                                            </button>
                                        </div>
                                        <div class="notes-container" id="notes-<?php echo $lesson['id']; ?>">
                                            <!-- سيتم تحديث الملاحظات هنا -->
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- مشغل الفيديو Modal -->
<div class="modal fade video-modal" id="videoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="ratio ratio-16x9">
                    <iframe id="videoPlayer" 
                            src="" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- إضافة Modal لتغيير القسم -->
<div class="modal fade" id="changeSectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تغيير القسم</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="sectionSelect" class="form-label">اختر القسم الجديد</label>
                    <select class="form-select" id="sectionSelect">
                        <?php foreach ($available_sections as $sect): ?>
                            <option value="<?php echo $sect['id']; ?>"
                                    <?php echo $sect['id'] == $section_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sect['name']); ?>
                                (<?php echo $sect['lessons_count']; ?> درس)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" onclick="updateSection()">حفظ التغييرات</button>
            </div>
        </div>
    </div>
</div>

<!-- موديول تحرير وصف القسم -->
<div class="modal fade" id="editDescriptionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تحرير وصف القسم</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="sectionDescription" class="form-label">وصف القسم</label>
                    <textarea id="sectionDescription" class="tinymce-editor"><?php echo $section['description'] ?? ''; ?></textarea>
                </div>
                <div class="d-flex justify-content-between">
                    <button class="btn btn-ai" onclick="getAIHelp()">
                        <i class="fas fa-robot me-1"></i>
                        مساعدة ChatGPT
                    </button>
                    <div>
                        <button class="btn btn-secondary me-2" data-bs-dismiss="modal">إلغاء</button>
                        <button class="btn btn-primary" onclick="saveSectionDescription()">حفظ التغييرات</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentLessonId = null;
let currentLessonForNote = null;

function playVideo(url, title) {
    // تحويل رابط يوتيوب العادي إلى رابط التضمين
    const videoId = url.match(/(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/watch\?.+&v=))([\w-]{11})/);
    if (videoId) {
        const embedUrl = 'https://www.youtube.com/embed/' + videoId[1];
        const videoPlayer = document.getElementById('videoPlayer');
        videoPlayer.src = embedUrl;
        
        // تحديث عنوان Modal
        document.querySelector('#videoModal .modal-title').textContent = title;
        
        const modal = new bootstrap.Modal(document.getElementById('videoModal'));
        modal.show();
    } else {
        alert('عذراً، الرابط غير صالح');
    }
}

// إيقاف الفيديو عند إغلاق Modal
document.getElementById('videoModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('videoPlayer').src = '';
});

// تفعيل tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function editLessonSection(lessonId, currentSectionId) {
    currentLessonId = lessonId;
    document.getElementById('sectionSelect').value = currentSectionId;
    new bootstrap.Modal(document.getElementById('changeSectionModal')).show();
}

function updateSection() {
    if (!currentLessonId) return;
    
    const sectionId = document.getElementById('sectionSelect').value;
    const formData = new FormData();
    formData.append('lesson_id', currentLessonId);
    formData.append('section_id', sectionId);

    // عرض مؤشر التحميل
    Swal.fire({
        title: 'جاري التحديث...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('/sections/ajax/update_lesson_section.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'تم التحديث بنجاح',
                text: data.message,
                timer: 1500
            }).then(() => {
                // إعادة تحميل الصفحة لتحديث البيانات
                window.location.reload();
            });
        } else {
            throw new Error(data.error || 'حدث خطأ أثناء التحديث');
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: error.message
        });
    });

    // إغلاق Modal
    bootstrap.Modal.getInstance(document.getElementById('changeSectionModal')).hide();
}

// تحديث دالة تبديل حالة إكمال الدرس
function toggleLessonCompletion(lessonId, button) {
    const row = button.closest('tr');
    const isCompleted = row.classList.contains('completed-lesson');
    
    // تحديث في LocalStorage
    localStorage.setItem(`lesson_${lessonId}_completed`, !isCompleted);
    
    // تحديث واجهة المستخدم
    if (!isCompleted) {
        row.classList.add('completed-lesson');
        button.classList.add('active', 'btn-complete');
        button.classList.remove('btn-outline-success');
        button.querySelector('i').classList.replace('fa-circle', 'fa-check-circle');
    } else {
        row.classList.remove('completed-lesson');
        button.classList.remove('active', 'btn-complete');
        button.classList.add('btn-outline-success');
        button.querySelector('i').classList.replace('fa-check-circle', 'fa-circle');
    }
    
    // تحديث شريط التقدم
    updateProgressBar();
    
    // إظهار رسالة نجاح
    Swal.fire({
        icon: 'success',
        title: !isCompleted ? 'تم إكمال الدرس' : 'تم إلغاء إكمال الدرس',
        timer: 1500,
        showConfirmButton: false
    });
}

/**
 * حذف ملاحظة من الدرس
 * @param {number} lessonId - معرف الدرس
 * @param {number} noteId - معرف الملاحظة
 * @param {HTMLElement} button - زر الحذف
 */
function deleteNote(lessonId, noteId, button) {
    const noteElement = button.closest('.note-item');
    
    Swal.fire({
        title: 'تأكيد الحذف',
        text: 'هل أنت متأكد من حذف هذه الملاحظة؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم، احذف',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#dc3545',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return new Promise((resolve) => {
                // حذف الملاحظة من LocalStorage
                const notes = JSON.parse(localStorage.getItem(`lesson_${lessonId}_notes`) || '[]');
                const updatedNotes = notes.filter(note => note.id !== noteId);
                localStorage.setItem(`lesson_${lessonId}_notes`, JSON.stringify(updatedNotes));
                resolve();
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // إضافة تأثير الحذف
            noteElement.classList.add('fade-out');
            
            // حذف العنصر بعد انتهاء التأثير
            setTimeout(() => {
                noteElement.remove();
                
                // عرض رسالة نجاح
                Swal.fire({
                    icon: 'success',
                    title: 'تم حذف الملاحظة',
                    timer: 1500,
                    showConfirmButton: false,
                    position: 'top-end',
                    toast: true
                });
            }, 300);
        }
    });
}

// تحديث دالة تهيئة الصفحة
document.addEventListener('DOMContentLoaded', () => {
    // تحميل حالة الدروس من LocalStorage
    document.querySelectorAll('tr[data-lesson-id]').forEach(row => {
        const lessonId = row.dataset.lessonId;
        
        // تحميل حالة الإكمال
        const isCompleted = localStorage.getItem(`lesson_${lessonId}_completed`) === 'true';
        if (isCompleted) {
            row.classList.add('completed-lesson');
            const completeButton = row.querySelector('.action-btn.btn-outline-success');
            if (completeButton) {
                completeButton.classList.add('active', 'btn-complete');
                completeButton.classList.remove('btn-outline-success');
                completeButton.querySelector('i').classList.replace('fa-circle', 'fa-check-circle');
            }
        }
        
        // تحميل الملاحظات
        const notes = JSON.parse(localStorage.getItem(`lesson_${lessonId}_notes`) || '[]');
        const notesContainer = document.getElementById(`notes-${lessonId}`);
        notes.forEach(note => {
            const noteElement = createNoteElement(note, lessonId);
            notesContainer.appendChild(noteElement);
        });
    });

    // تحديث شريط التقدم
    updateProgressBar();
});

// تحديث دالة إضافة ملاحظة
function showAddNoteModal(lessonId) {
    Swal.fire({
        title: 'إضافة ملاحظة جديدة',
        input: 'textarea',
        inputLabel: 'محتوى الملاحظة',
        inputPlaceholder: 'اكتب ملاحظتك هنا...',
        showCancelButton: true,
        confirmButtonText: 'حفظ',
        cancelButtonText: 'إلغاء',
        showLoaderOnConfirm: true,
        preConfirm: (content) => {
            if (!content.trim()) {
                Swal.showValidationMessage('الرجاء إدخال محتوى الملاحظة');
                return false;
            }
            
            const notes = JSON.parse(localStorage.getItem(`lesson_${lessonId}_notes`) || '[]');
            const newNote = {
                id: Date.now(), // إضافة معرف فريد للملاحظة
                content: content.trim(),
                created_at: new Date().toISOString()
            };
            notes.unshift(newNote);
            localStorage.setItem(`lesson_${lessonId}_notes`, JSON.stringify(notes));
            
            return newNote;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const notesContainer = document.getElementById(`notes-${lessonId}`);
            const noteElement = createNoteElement(result.value, lessonId);
            notesContainer.insertBefore(noteElement, notesContainer.firstChild);

            Swal.fire({
                icon: 'success',
                title: 'تم إضافة الملاحظة بنجاح',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
}

// دالة إنشاء عنصر الملاحظة
function createNoteElement(note, lessonId) {
    const noteElement = document.createElement('div');
    noteElement.className = 'note-item p-3 mb-2 bg-white rounded shadow-sm';
    noteElement.dataset.noteId = note.id;
    noteElement.innerHTML = `
        <div class="delete-note" onclick="deleteNote(${lessonId}, ${note.id}, this)">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </div>
        <div class="note-content">${note.content}</div>
        <div class="note-meta text-muted small mt-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline me-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            ${new Date(note.created_at).toLocaleString('ar-SA')}
        </div>
    `;
    return noteElement;
}

// إضافة دالة تحديث شريط التقدم
function updateProgressBar() {
    const totalLessons = document.querySelectorAll('tr[data-lesson-id]').length;
    const completedLessons = document.querySelectorAll('.completed-lesson').length;
    const percentage = totalLessons > 0 ? (completedLessons / totalLessons) * 100 : 0;
    
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        progressBar.style.width = `${percentage}%`;
        progressBar.textContent = `${Math.round(percentage)}%`;
        progressBar.setAttribute('aria-valuenow', completedLessons);
        progressBar.setAttribute('aria-valuemax', totalLessons);
    }
}

/**
 * فتح صفحة تفاصيل الدرس في تاب جديد
 * @param {number} lessonId - معرف الدرس
 * @param {string} lessonTitle - عنوان الدرس
 */
function confirmLessonRedirect(lessonId, lessonTitle) {
    Swal.fire({
        title: lessonTitle,
        text: 'هل تريد الانتقال إلى صفحة الدرس؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'نعم',
        cancelButtonText: 'لا',
        confirmButtonColor: '#28a745'
    }).then((result) => {
        if (result.isConfirmed) {
            // فتح الرابط في تاب جديد
            window.open(`/content/views/lesson-details.php?id=${lessonId}`, '_blank');
        }
    });
}

/**
 * توجيه سؤال حول الدرس إلى ChatGPT
 * @param {string} lessonTitle - عنوان الدرس
 * @param {string} sectionName - اسم القسم
 * @param {string} languageName - اسم اللغة
 */
function askGPTAboutLesson(lessonTitle, sectionName, languageName) {
    const question = formatLessonQuestion(lessonTitle, sectionName, languageName);
    directChatGPTLink(question);
}

/**
 * تنسيق السؤال للدرس
 * @param {string} lessonTitle - عنوان الدرس
 * @param {string} sectionName - اسم القسم
 * @param {string} languageName - اسم اللغة
 * @returns {string} السؤال المنسق
 */
function formatLessonQuestion(lessonTitle, sectionName, languageName) {
    return `اريد شرح تفصيلي للدرس: ${lessonTitle}
الموجود في قسم: ${sectionName}
في لغة: ${languageName}

المطلوب:
1. شرح مفصل للمفاهيم الأساسية في هذا الدرس
2. أمثلة عملية وأكواد توضيحية
3. تطبيقات وحالات استخدام
4. مشاريع صغيرة للتدريب
5. أفضل الممارسات والنصائح
6. مصادر إضافية للتعلم

الرجاء تقديم الشرح بأسلوب سهل وواضح مع التركيز على الجانب العملي.`;
}

/**
 * تحليل محتوى القسم بالكامل باستخدام ChatGPT
 * يقوم بإنشاء سؤال شامل عن القسم ودروسه
 */
function analyzeSectionContent() {
    // جمع معلومات القسم
    const sectionName = document.querySelector('.section-info h3').textContent.trim();
    const sectionDescription = document.querySelector('.section-description').textContent.trim();
    
    // جمع معلومات الدروس
    const lessons = [];
    document.querySelectorAll('tr[data-lesson-id]').forEach(row => {
        const title = row.querySelector('.lesson-name').textContent.trim();
        const course = row.querySelector('.course-badge').textContent.trim();
        lessons.push({ title, course });
    });
    
    // إنشاء السؤال الشامل
    const question = formatSectionAnalysisQuestion(sectionName, sectionDescription, lessons);
    
    // عرض مؤشر التحميل
    Swal.fire({
        title: 'جاري تحضير التحليل...',
        text: 'سيتم فتح ChatGPT مع تحليل شامل للقسم',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'متابعة',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            directChatGPTLink(question);
        }
    });
}

/**
 * تنسيق السؤال الشامل للقسم
 * @param {string} sectionName - اسم القسم
 * @param {string} sectionDescription - وصف القسم
 * @param {Array} lessons - مصفوفة الدروس
 * @returns {string} السؤال المنسق
 */
function formatSectionAnalysisQuestion(sectionName, sectionDescription, lessons) {
    const lessonsText = lessons.map((lesson, index) => 
        `${index + 1}. ${lesson.title} (${lesson.course})`
    ).join('\n');

    const examplesText = lessons.map((lesson, index) => 
        `**مثال ${index + 1}: ${lesson.title}**

${lesson.exampleCode}

**شرح:**
${lesson.exampleExplanation}
`).join('\n');

    const finalExamplesText = `**أمثلة ختامية:**

${examplesText}

**شرح:**
هذه الأمثلة الختامية تهدف إلى توضيح كيفية تطبيق المفاهيم الأساسية لكل درس في سياقات عملية. من خلال دراسة هذه الأمثلة، يمكنك الحصول على فهم أعمق لكيفية تطبيق ما تعلمته في القسم.`;

    return `أريد تحليلاً شاملاً للقسم التالي:

اسم القسم: ${sectionName}

وصف القسم:
${sectionDescription}

قائمة الدروس:
${lessonsText}

المطلوب:
1. تحليل شامل لمحتوى القسم وأهدافه التعليمية
2. شرح تفصيلي لكل درس مع:
   - المفاهيم الأساسية
   - أمثلة عملية وأكواد توضيحية
   - حالات استخدام واقعية
   - تمارين وتطبيقات عملية
3. مشروع متكامل يجمع مفاهيم جميع الدروس
4. خريطة تعلم مقترحة لدراسة القسم
5. مصادر إضافية للتعلم والتعمق
6. نصائح وأفضل الممارسات
7. اقتراحات لمشاريع إضافية للتطبيق العملي

${finalExamplesText}

الرجاء تقديم الإجابة بشكل منظم ومفصل مع التركيز على الجانب العملي والتطبيقي.`;
}

/**
 * توجيه سؤال حول الدرس إلى Grok
 * @param {string} lessonTitle - عنوان الدرس
 * @param {string} sectionName - اسم القسم
 * @param {string} languageName - اسم اللغة
 */
function askGrokAboutLesson(lessonTitle, sectionName, languageName) {
    const question = formatGrokQuestion(
        `شرح درس: ${lessonTitle}`,
        `هذا الدرس جزء من قسم ${sectionName} في لغة ${languageName}`
    );
    directGrokLink(question);
}

/**
 * تنسيق السؤال لـ Grok
 * @param {string} title - عنوان السؤال
 * @param {string} context - سياق السؤال
 * @returns {string} السؤال المنسق
 */
function formatGrokQuestion(title, context) {
    return `${title}

السياق:
${context}

المطلوب:
1. شرح مفصل للموضوع
2. أمثلة عملية
3. أفضل الممارسات
4. مصادر إضافية للتعلم`;
}

/**
 * تحليل القسم بالكامل باستخدام Grok
 */
function analyzeSectionWithGrok() {
    // جمع معلومات القسم
    const sectionName = document.querySelector('.section-info h3').textContent.trim();
    const sectionDescription = document.querySelector('.section-description').textContent.trim();
    
    // جمع معلومات الدروس
    const lessons = [];
    document.querySelectorAll('tr[data-lesson-id]').forEach(row => {
        const title = row.querySelector('.lesson-name').textContent.trim();
        const course = row.querySelector('.course-badge').textContent.trim();
        lessons.push({ title, course });
    });
    
    // إنشاء السؤال الشامل
    const question = formatGrokSectionAnalysis(sectionName, sectionDescription, lessons);
    
    // عرض مؤشر التحميل
    Swal.fire({
        title: 'جاري تحضير التحليل...',
        text: 'سيتم فتح Grok مع تحليل شامل للقسم',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'متابعة',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#6b21a8'
    }).then((result) => {
        if (result.isConfirmed) {
            directGrokLink(question);
        }
    });
}

/**
 * تنسيق تحليل القسم لـ Grok
 * @param {string} sectionName - اسم القسم
 * @param {string} sectionDescription - وصف القسم
 * @param {Array} lessons - مصفوفة الدروس
 * @returns {string} السؤال المنسق
 */
function formatGrokSectionAnalysis(sectionName, sectionDescription, lessons) {
    const lessonsText = lessons.map((lesson, index) => 
        `${index + 1}. ${lesson.title} (${lesson.course})`
    ).join('\n');

    return `تحليل شامل للقسم: ${sectionName}

وصف القسم:
${sectionDescription}

قائمة الدروس:
${lessonsText}

المطلوب:
1. تحليل شامل لمحتوى القسم وأهدافه التعليمية
2. شرح تفصيلي لكل درس مع:
   - المفاهيم الأساسية
   - أمثلة عملية وأكواد توضيحية
   - حالات استخدام واقعية
   - تمارين وتطبيقات عملية
3. مشروع متكامل يجمع مفاهيم جميع الدروس
4. خريطة تعلم مقترحة لدراسة القسم
5. مصادر إضافية للتعلم والتعمق
6. نصائح وأفضل الممارسات
7. اقتراحات لمشاريع إضافية للتطبيق العملي

الرجاء تقديم الإجابة بشكل منظم ومفصل مع التركيز على الجانب العملي والتطبيقي.`;
}

/**
 * دالة لإنشاء وصف للقسم باستخدام ChatGPT
 * @param {string} sectionName - اسم القسم
 * @param {string} languageName - اسم اللغة
 */
function generateSectionDescription(sectionName, languageName) {
    // تشفير معلومات القسم واللغة
    const encodedSection = encodeURIComponent(sectionName);
    const encodedLanguage = encodeURIComponent(languageName);
    const question = formatSectionQuestion(sectionName, languageName);
    
    // إنشاء رابط ChatGPT مع query string
    const chatGPTUrl = 'https://chat.openai.com/?' + new URLSearchParams({
        section: encodedSection,
        language: encodedLanguage,
        q: encodeURIComponent(question)
    }).toString();
    
    // فتح نافذة ChatGPT
    const chatWindow = window.open(chatGPTUrl, '_blank');
    
    // عدد محاولات البحث عن مربع النص
    let attempts = 0;
    const maxAttempts = 50;
    
    // دالة لمراقبة تحميل الصفحة وإضافة السؤال
    const waitForPageLoad = () => {
        const interval = setInterval(() => {
            attempts++;
            
            try {
                if (chatWindow.document.readyState === 'complete') {
                    // البحث عن مربع النص
                    const textArea = chatWindow.document.querySelector('textarea[placeholder="Send a message"]') || 
                                   chatWindow.document.querySelector('textarea[data-id="root"]');
                    
                    if (textArea) {
                        // فك تشفير السؤال
                        const urlParams = new URLSearchParams(chatWindow.location.search);
                        const urlQuestion = urlParams.get('q');
                        
                        if (urlQuestion) {
                            const decodedQuestion = decodeURIComponent(urlQuestion);
                            
                            // إضافة السؤال
                            textArea.value = decodedQuestion;
                            
                            // تحديث قيمة مربع النص
                            textArea.dispatchEvent(new Event('input', { bubbles: true }));
                            textArea.dispatchEvent(new Event('change', { bubbles: true }));
                            
                            // تفعيل مربع النص
                            textArea.focus();
                            
                            // محاكاة ضغط Enter
                            const enterEvent = new KeyboardEvent('keydown', {
                                key: 'Enter',
                                code: 'Enter',
                                keyCode: 13,
                                which: 13,
                                bubbles: true,
                                cancelable: true
                            });
                            textArea.dispatchEvent(enterEvent);
                        }
                        
                        clearInterval(interval);
                        console.log('تم إضافة السؤال بنجاح');
                    } else if (attempts >= maxAttempts) {
                        clearInterval(interval);
                        console.error('فشل في إضافة السؤال بعد عدة محاولات');
                    }
                }
            } catch (error) {
                clearInterval(interval);
                console.error('خطأ في الوصول للصفحة:', error);
            }
        }, 300);
    };

    // إضافة مستمع لحدث تحميل الصفحة
    if (chatWindow) {
        chatWindow.addEventListener('load', waitForPageLoad);
    }
}

/**
 * تنسيق السؤال لوصف القسم
 * @param {string} sectionName - اسم القسم
 * @param {string} languageName - اسم اللغة
 * @returns {string} السؤال المنسق
 */
function formatSectionQuestion(sectionName, languageName) {
    return `اريد وصف تفصيلي للقسم: ${sectionName} في لغة ${languageName}

المطلوب:
1. وصف مختصر للقسم في خمسة أسطر
2. شرح المفاهيم الأساسية في هذا القسم
3. أهداف تعليمية واضحة
4. المتطلبات السابقة للقسم
5. المخرجات المتوقعة بعد إكمال القسم
6. أمثلة عملية وتطبيقية
7. أفضل الممارسات والنصائح
8. المصادر المفيدة لتعلم هذا القسم

الرجاء تقديم الوصف بأسلوب سهل وواضح مع التركيز على الجانب العملي.`;
}

/**
 * فتح موديول تحرير وصف القسم
 */
function editSectionDescription() {
    const modal = new bootstrap.Modal(document.getElementById('editDescriptionModal'));
    modal.show();
}

/**
 * الحصول على مساعدة ChatGPT لكتابة الوصف
 */
function getAIHelp() {
    const sectionName = '<?php echo addslashes($section['name']); ?>';
    const languageName = '<?php echo addslashes($language['name']); ?>';
    const currentDescription = tinymce.get('sectionDescription').getContent();
    
    // إنشاء السؤال
    const question = `اريد تحسين وصف القسم التالي:

اسم القسم: ${sectionName}
اللغة: ${languageName}

الوصف الحالي:
${currentDescription || 'لا يوجد وصف حالي'}

المطلوب:
1. تحسين الوصف الحالي أو كتابة وصف جديد
2. التركيز على النقاط التالية:
    - المفاهيم الأساسية في القسم
    - الأهداف التعليمية
    - المتطلبات السابقة
    - المخرجات المتوقعة
3. جعل الوصف واضح ومفيد للمتعلمين

ملاحظة: الرجاء تقديم الوصف فقط بدون أي مقدمات أو تعليقات إضافية.`;

    // فتح ChatGPT في نافذة جديدة
    window.open(`https://chat.openai.com/?q=${encodeURIComponent(question)}`, '_blank');
}

/**
 * حفظ وصف القسم
 */
function saveSectionDescription() {
    const description = tinymce.get('sectionDescription').getContent();
    const sectionId = <?php echo $section_id; ?>;
    
    // عرض مؤشر التحميل
    Swal.fire({
        title: 'جاري الحفظ...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // إرسال الطلب لتحديث الوصف
    fetch('/sections/ajax/update_description.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            section_id: sectionId,
            description: description
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const container = document.querySelector('.flex-grow-1');
            if (description) {
                container.innerHTML = `
                    <div class="section-description">
                        ${description}
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-outline-danger btn-sm" onclick="clearSectionDescription()">
                            <i class="fas fa-trash-alt me-1"></i>
                            مسح الوصف
                        </button>
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <div class="text-muted">
                        لا يوجد وصف للقسم
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="editSectionDescription()">
                            <i class="fas fa-edit me-1"></i>
                            تحرير الوصف
                        </button>
                    </div>
                `;
            }

            // إغلاق الموديول
            bootstrap.Modal.getInstance(document.getElementById('editDescriptionModal')).hide();

            // عرض رسالة النجاح
            Swal.fire({
                icon: 'success',
                title: 'تم الحفظ بنجاح',
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            throw new Error(data.error || 'حدث خطأ أثناء الحفظ');
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: error.message
        });
    });
}

/**
 * مسح وصف القسم
 */
function clearSectionDescription() {
    Swal.fire({
        title: 'تأكيد المسح',
        text: 'هل أنت متأكد من مسح وصف القسم؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم، امسح الوصف',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            const sectionId = <?php echo $section_id; ?>;
            
            // عرض مؤشر التحميل
            Swal.fire({
                title: 'جاري المسح...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('/sections/ajax/update_description.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    section_id: sectionId,
                    description: null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const container = document.querySelector('.flex-grow-1');
                    container.innerHTML = `
                        <div class="text-muted">
                            لا يوجد وصف للقسم
                        </div>
                        <div class="mt-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="editSectionDescription()">
                                <i class="fas fa-edit me-1"></i>
                                تحرير الوصف
                            </button>
                        </div>
                    `;

                    Swal.fire({
                        icon: 'success',
                        title: 'تم مسح الوصف بنجاح',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    throw new Error(data.error || 'حدث خطأ أثناء مسح الوصف');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: error.message
                });
            });
        }
    });
}

// تهيئة TinyMCE
tinymce.init({
    selector: '.tinymce-editor',
    directionality: 'rtl',
    language: 'ar',
    height: 400,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount', 'directionality'
    ],
    toolbar: 'undo redo | formatselect | ' +
        'bold italic backcolor | alignleft aligncenter ' +
        'alignright alignjustify | bullist numlist outdent indent | ' +
        'removeformat | ltr rtl | help',
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial; font-size: 14px; }',
    setup: function(editor) {
        editor.on('change', function() {
            editor.save(); // حفظ المحتوى في التيكست اريا
        });
    }
});
</script>

<style>
@keyframes fadeOut {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-10px);
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-10px);
    }
}
</style>

<!-- إضافة سكربت ChatGPT في نهاية الملف قبل إغلاق body -->
<script src="/GBT/js/chatgpt-link.js"></script>

<!-- إضافة سكربت Grok -->
<script src="/GBT/js/grok-link.js"></script>

<!-- إضافة روابط CSS و JavaScript للقائمة السياقية -->
<link rel="stylesheet" href="/assets/css/context-menu.css">
<script src="/assets/js/context-menu.js"></script>

<!-- إضافة المتغيرات العامة للقائمة السياقية -->
<script>
    // المتغيرات العامة المطلوبة للقائمة السياقية
    const currentSectionId = <?php echo $section_id; ?>;
    const currentSectionName = '<?php echo addslashes($section['name']); ?>';
    const currentLanguageName = '<?php echo addslashes($language['name']); ?>';
</script>
