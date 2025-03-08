<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lesson-functions.php';
require_once __DIR__ . '/../lesson-details/image-section.php';

// التحقق من معرف الدرس
$lesson_id = $_GET['id'] ?? null;
if (!$lesson_id) {
    $_SESSION['error'] = 'معرف الدرس مطلوب';
    header('Location: lessons.php');
    exit;
}

// جلب تفاصيل الدرس
$lesson = getLessonDetails($lesson_id);
if (!$lesson) {
    $_SESSION['error'] = 'الدرس غير موجود';
    header('Location: lessons.php');
    exit;
}

// جلب معلومات الكورس
$course = getCourseInfo($lesson['course_id']);
if (!$course) {
    $_SESSION['error'] = 'الكورس غير موجود';
    header('Location: lessons.php');
    exit;
}

// جلب جميع دروس الكورس
$lessonsData = getLessonsByCourse($course['id']);
$lessons = $lessonsData['lessons'];

// جلب الدرس التالي والسابق
$nextLesson = getNextLesson($lesson['course_id'], $lesson_id);
$prevLesson = getPrevLesson($lesson['course_id'], $lesson_id);

// جلب قائمة الحالات
$statuses = getStatuses();

$pageTitle = $lesson['title'];

// استخدام الهيدر الخاص
require_once 'includes/lessons-header.php';
include'../lesson-details/css.php'; 
?>

<!-- إضافة بعد سطر include'../lesson-details/css.php'; -->
<link rel="stylesheet" href="/content/lesson-details/css/notes.css">
<link rel="stylesheet" href="/content/lesson-details/css/images.css">

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
                    <?php if (isset($lesson['language_id'])): ?>
                    <a href="/content/courses.php?language_id=<?php echo (int)$lesson['language_id']; ?>" 
                       class="btn btn-outline-primary">
                        <i class="fas fa-arrow-right me-1"></i>
                        العودة للغة
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- عنوان الدرس في المنتصف -->
            <div class="col text-center">
                <h4 class="mb-0 text-primary">
                    <?php echo htmlspecialchars($lesson['title']); ?>
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

<!-- بعد قسم التنقل العلوي -->
<div class="lesson-title-header">
    <div class="container">
        <h2><?php echo htmlspecialchars($lesson['title']); ?></h2>
    </div>
</div>

<div class="lesson-content">
    <div class="container py-4">
        <!-- شريط التنقل -->
        <div class="lesson-navigation mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <?php if ($prevLesson): ?>
                        <a href="lesson-details.php?id=<?php echo $prevLesson['id']; ?>" 
                           class="btn btn-outline-primary">
                            <i class="fas fa-chevron-right me-2"></i>
                            الدرس السابق
                        </a>
                    <?php endif; ?>
                </div>
                <div class="col-auto">
                    <div class="lesson-status">
                        <select class="form-select status-select" 
                                id="lessonStatus"
                                name="status_id"
                                data-lesson-id="<?php echo (int)$lesson['id']; ?>"
                                style="background-color: <?php echo htmlspecialchars($lesson['status_color'] ?? ''); ?>">
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?php echo (int)$status['id']; ?>"
                                        data-color="<?php echo htmlspecialchars($status['color']); ?>"
                                        data-text-color="<?php echo htmlspecialchars($status['text_color']); ?>"
                                        <?php echo ($lesson['status_id'] == $status['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($status['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col text-end">
                    <?php if ($nextLesson): ?>
                        <a href="lesson-details.php?id=<?php echo $nextLesson['id']; ?>" 
                           class="btn btn-primary">
                            الدرس التالي
                            <i class="fas fa-chevron-left ms-2"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Add after the navigation bar section -->
        <div class="lesson-actions mb-4">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-2">
                        <!-- Completion Toggle Button -->
                        <button id="toggleCompletion" 
                                class="btn <?php echo $lesson['completed'] ? 'btn-success' : 'btn-outline-secondary'; ?>"
                                data-lesson-id="<?php echo $lesson['id']; ?>"
                                data-bs-toggle="tooltip"
                                title="<?php echo $lesson['completed'] ? 'تحديد كغير مكتمل' : 'تحديد كمكتمل'; ?>">
                            <i class="fas <?php echo $lesson['completed'] ? 'fa-check-circle' : 'fa-circle'; ?>"></i>
                            <span class="button-text d-none d-md-inline ms-1">
                                <?php echo $lesson['completed'] ? 'مكتمل' : 'غير مكتمل'; ?>
                            </span>
                        </button>

                        <!-- Review Toggle Button -->
                        <button id="toggleReview" 
                                class="btn <?php echo $lesson['is_reviewed'] ? 'btn-info' : 'btn-outline-secondary'; ?>"
                                data-lesson-id="<?php echo $lesson['id']; ?>"
                                data-bs-toggle="tooltip"
                                title="<?php echo $lesson['is_reviewed'] ? 'إزالة من المراجعة' : 'إضافة للمراجعة'; ?>">
                            <i class="fas <?php echo $lesson['is_reviewed'] ? 'fa-bookmark' : 'fa-bookmark-o'; ?>"></i>
                            <span class="button-text d-none d-md-inline ms-1">
                                <?php echo $lesson['is_reviewed'] ? 'في المراجعة' : 'إضافة للمراجعة'; ?>
                            </span>
                        </button>

                        <!-- ChatGPT Button -->
                        <button class="btn btn-outline-primary chatgpt-link"
                                data-title="<?php echo htmlspecialchars($lesson['title']); ?>"
                                data-bs-toggle="tooltip"
                                title="فتح في ChatGPT">
                            <i class="fas fa-robot"></i>
                            <span class="d-none d-md-inline ms-1">ChatGPT</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- محتوى الدرس -->
        <div class="lesson-main">
            <div class="row">
                <div class="col-lg-8">
                    <!-- الفيديو -->
                    <?php if ($lesson['video_url']): ?>
                        <div class="video-wrapper mb-4">
                            <div class="ratio ratio-16x9">
                                <iframe 
                                    src="https://www.youtube.com/embed/<?php echo getYoutubeId($lesson['video_url']); ?>?rel=0" 
                                    title="<?php echo htmlspecialchars($lesson['title']); ?>"
                                    allowfullscreen>
                                </iframe>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- معلومات الدرس الأساسية -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                معلومات الدرس
                            </h5>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary copy-all" 
                                        data-bs-toggle="tooltip" 
                                        title="نسخ جميع المعلومات"
                                        data-title="<?php echo htmlspecialchars($lesson['title']); ?>"
                                        data-tags="<?php echo htmlspecialchars($lesson['tags']); ?>">
                                    <i class="fas fa-copy me-1"></i>
                                    نسخ الكل
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <h4 class="lesson-title mb-3">
                                <?php echo htmlspecialchars($lesson['title']); ?>
                            </h4>
                 
                        </div>
                    </div>

                    <!-- زر نسخ جميع المعلومات -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-copy me-2"></i>
                                نسخ المعلومات
                            </h5>
                            <button class="btn btn-primary copy-lesson-info" 
                                    data-lesson-id="<?php echo (int)$lesson['id']; ?>"
                                    data-title="<?php echo htmlspecialchars($lesson['title']); ?>"
                                    data-tags="<?php echo htmlspecialchars($lesson['tags']); ?>">
                                <i class="fas fa-copy me-2"></i>
                                نسخ جميع المعلومات
                            </button>
                        </div>
                    </div>

                    <!-- تفاصيل الدرس -->
               
                </div>

                <div class="col-lg-4">
                    <!-- معلومات إضافية -->
                    <div class="lesson-sidebar">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">معلومات الدرس</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-clock text-muted me-2"></i>
                                        <?php echo formatDuration($lesson['duration']); ?>
                                    </li>
                                    <?php if ($lesson['is_theory']): ?>
                                        <li class="mb-2">
                                            <i class="fas fa-book text-info me-2"></i>
                                            درس نظري
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($lesson['is_important']): ?>
                                        <li class="mb-2">
                                            <i class="fas fa-star text-warning me-2"></i>
                                            درس مهم
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- إضافة نموذج تحديث الدرس -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-edit me-2"></i>
                                    تحديث الدرس
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="lessonUpdateForm">
                                    <!-- معرف الدرس - حقل مخفي -->
                                    <input type="hidden" name="lesson_id" value="<?php echo (int)$lesson['id']; ?>">
                                    
                                    <!-- القسم -->
                                    <div class="mb-3">
                                        <label class="form-label">القسم</label>
                                        <select class="form-select section-select" name="section_id" required>
                                            <option value="">اختر القسم</option>
                                            <?php foreach (getSectionsByLanguage($course['language_id']) as $section): ?>
                                                <option value="<?php echo (int)$section['id']; ?>"
                                                        <?php echo ($lesson['section_id'] == $section['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($section['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- الحالة -->
                                    <div class="mb-3">
                                        <label class="form-label">الحالة</label>
                                        <select class="form-select status-select" 
                                                name="status_id" 
                                                required
                                                data-current-status="<?php echo (int)$lesson['status_id']; ?>"
                                                style="background-color: <?php echo htmlspecialchars($lesson['status_color'] ?? ''); ?>;
                                                       color: <?php echo htmlspecialchars($lesson['status_text_color'] ?? ''); ?>;">
                                            <option value="">اختر الحالة</option>
                                            <?php foreach (getStatusesByLanguage($course['language_id']) as $status): ?>
                                                <option value="<?php echo (int)$status['id']; ?>"
                                                        data-color="<?php echo htmlspecialchars($status['color']); ?>"
                                                        data-text-color="<?php echo htmlspecialchars($status['text_color']); ?>"
                                                        <?php echo ($lesson['status_id'] == $status['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($status['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- التاجات -->
                                    <div class="mb-3">
                                        <label class="form-label">التاجات</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="lessonTags" 
                                               name="tags" 
                                               value="<?php echo htmlspecialchars($lesson['tags'] ?? ''); ?>"
                                               data-role="tagsinput">
                                        <small class="text-muted">اضغط Enter أو Comma لإضافة تاج</small>
                                    </div>

                                    <!-- خيارات إضافية -->
                                    <div class="mb-3">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="is_theory" 
                                                   id="isTheory" 
                                                   <?php echo $lesson['is_theory'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="isTheory">
                                                درس نظري
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="is_important" 
                                                   id="isImportant" 
                                                   <?php echo $lesson['is_important'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="isImportant">
                                                درس مهم
                                            </label>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-save me-2"></i>
                                        حفظ التغييرات
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- قسم الدروس المرتبطة -->
        <?php if ($lesson['section_id']): ?>
            <?php 
            $relatedLessons = getRelatedLessonsBySection($lesson['section_id'], $lesson['id']); 
            if (!empty($relatedLessons)): 
            ?>
                <div class="related-lessons-section mt-5">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-link me-2 fa-fw"></i>
                                    <h3 class="mb-0">دروس مرتبطة من نفس القسم</h3>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-light text-primary">
                                        <?php echo count($relatedLessons); ?> دروس
                                    </span>
                                    <button type="button" class="toggle-related-lessons">
                                        <i class="fas fa-chevron-up"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="related-lessons-content">
                            <!-- عرض القسم الحالي -->
                            <div class="current-section mb-4">
                                <span class="badge bg-info">
                                    <i class="fas fa-folder me-1"></i>
                                    <?php echo htmlspecialchars($lesson['section_name'] ?? 'قسم غير محدد'); ?>
                                </span>
                            </div>

                            <div class="row g-4">
                                <?php foreach ($relatedLessons as $relatedLesson): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card h-100 related-lesson-card hover-shadow">
                                            <!-- شريط الحالة -->
                                            <div class="status-bar" style="background-color: <?php echo $relatedLesson['status_color']; ?>"></div>
                                            
                                            <?php if ($relatedLesson['thumbnail']): ?>
                                                <div class="card-img-wrapper position-relative">
                                                    <img src="<?php echo htmlspecialchars($relatedLesson['thumbnail']); ?>" 
                                                         class="card-img-top" 
                                                         alt="<?php echo htmlspecialchars($relatedLesson['title']); ?>">
                                                    <?php if ($relatedLesson['video_url']): ?>
                                                        <div class="play-icon">
                                                            <i class="fas fa-play-circle"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <!-- بادجات مميزة -->
                                                    <div class="lesson-badges position-absolute top-0 end-0 p-2">
                                                        <?php if ($relatedLesson['is_theory']): ?>
                                                            <span class="badge bg-info">
                                                                <i class="fas fa-book"></i>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($relatedLesson['is_important']): ?>
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-star"></i>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="card-body">
                                                <h5 class="card-title mb-3 fs-6">
                                                    <?php echo htmlspecialchars($relatedLesson['title']); ?>
                                                </h5>
                                                
                                                <div class="lesson-meta">
                                                    <!-- معلومات الكورس -->
                                                    <div class="course-info mb-2">
                                                        <small class="text-muted d-flex align-items-center">
                                                            <i class="fas fa-graduation-cap me-1"></i>
                                                            <?php echo htmlspecialchars($relatedLesson['course_title']); ?>
                                                        </small>
                                                    </div>
                                                    
                                                    <!-- المدة والحالة -->
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="text-muted small">
                                                            <i class="fas fa-clock me-1"></i>
                                                            <?php echo formatDuration($relatedLesson['duration']); ?>
                                                        </span>
                                                        <span class="badge" 
                                                              style="background-color: <?php echo $relatedLesson['status_color']; ?>; 
                                                                     color: <?php echo $relatedLesson['status_text_color']; ?>">
                                                            <?php echo htmlspecialchars($relatedLesson['status_name']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="card-footer bg-transparent border-top-0 p-3">
                                                <a href="lesson-details.php?id=<?php echo $relatedLesson['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm w-100">
                                                    <i class="fas fa-eye me-1"></i>
                                                    عرض الدرس
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- قسم الملاحظات -->
        <div class="notes-section mt-5">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-sticky-note me-2"></i>
                        <h3 class="mb-0">الملاحظات</h3>
                    </div>
                    <button type="button" 
                            class="btn btn-light btn-sm toggle-notes" 
                            data-bs-toggle="tooltip" 
                            title="إخفاء/إظهار الملاحظات">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                </div>
                <div class="card-body notes-content">
                    <!-- نموذج إضافة ملاحظة -->
                    <form id="addNoteForm" class="mb-4">
                        <input type="hidden" name="lesson_id" value="<?php echo $lesson['id']; ?>">
                        
                        <!-- نوع الملاحظة -->
                        <div class="mb-3">
                            <label class="form-label">نوع الملاحظة</label>
                            <select class="form-select" name="type" id="noteType">
                                <option value="text" selected>نص</option>
                                <option value="code">كود</option>
                                <option value="link">رابط</option>
                            </select>
                        </div>
                        
                        <!-- العنوان -->
                        <div class="mb-3">
                            <label class="form-label">العنوان</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        
                        <!-- حقول النص - تظهر افتراضياً -->
                        <div class="note-fields text-fields">
                            <div class="mb-3">
                                <label class="form-label">المحتوى</label>
                                <textarea class="form-control tinymce" name="content" id="textContent"></textarea>
                            </div>
                        </div>
                        
                        <!-- حقول الكود - مخفية -->
                        <div class="note-fields code-fields d-none">
                            <div class="mb-3">
                                <label class="form-label">لغة البرمجة</label>
                                <select class="form-select" name="code_language">
                                    <option value="javascript">JavaScript</option>
                                    <option value="php">PHP</option>
                                    <option value="html">HTML</option>
                                    <option value="css">CSS</option>
                                    <option value="sql">SQL</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الكود</label>
                                <textarea class="form-control code-editor" name="code_content" rows="10"></textarea>
                            </div>
                        </div>
                        
                        <!-- حقول الرابط - مخفية -->
                        <div class="note-fields link-fields d-none">
                            <div class="mb-3">
                                <label class="form-label">الرابط</label>
                                <input type="url" 
                                       class="form-control" 
                                       name="link_url" 
                                       data-required="true">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الوصف</label>
                                <textarea class="form-control" name="link_description" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            إضافة ملاحظة
                        </button>
                    </form>
                    
                    <!-- عرض الملاحظات -->
                    <div id="notesList" class="notes-list">
                        <?php 
                        $notes = getLessonNotes($lesson['id']);
                        foreach ($notes as $note):
                        ?>
                            <div class="note-card <?php echo $note['type']; ?>-note">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <?php echo htmlspecialchars($note['title']); ?>
                                        </h5>
                                        <div class="note-actions">
                                            <?php if ($note['type'] === 'code'): ?>
                                                <button class="btn btn-sm copy-code" title="نسخ الكود">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm edit-note" title="تعديل"
                                                    data-note-id="<?php echo $note['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm delete-note" title="حذف"
                                                    data-note-id="<?php echo $note['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($note['type'] === 'text'): ?>
                                            <div class="note-content formatted-content">
                                                <?php echo html_entity_decode($note['content']); ?>
                                            </div>
                                        <?php elseif ($note['type'] === 'code'): ?>
                                            <div class="code-wrapper">
                                                <div class="code-controls">
                                                    <button type="button" class="font-size-increase" title="تكبير الخط">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                    <button type="button" class="font-size-decrease" title="تصغير الخط">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <button type="button" class="fullscreen-toggle" title="عرض بملء الشاشة">
                                                        <i class="fas fa-expand"></i>
                                                    </button>
                                                </div>
                                                <pre><code class="language-<?php echo htmlspecialchars($note['code_language']); ?>"><?php echo htmlspecialchars($note['content']); ?></code></pre>
                                            </div>
                                        <?php else: ?>
                                            <div class="note-content">
                                                <a href="<?php echo htmlspecialchars($note['link_url']); ?>" 
                                                   target="_blank" 
                                                   rel="noopener noreferrer">
                                                    <i class="fas fa-external-link-alt me-1"></i>
                                                    <?php echo htmlspecialchars($note['link_url']); ?>
                                                </a>
                                                <?php if ($note['link_description']): ?>
                                                    <p class="link-description">
                                                        <?php echo nl2br(htmlspecialchars($note['link_description'])); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer text-muted">
                                        <small>
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo formatDate($note['created_at']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- بعد قسم الملاحظات مباشرة، نضيف قسم الصور -->
        <div class="images-section mt-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-images me-2"></i>
                        <h3 class="mb-0">صور الدرس</h3>
                    </div>
                    <button type="button" 
                            class="btn btn-light btn-sm toggle-images" 
                            data-section="images"
                            data-bs-toggle="tooltip" 
                            title="إخفاء/إظهار الصور">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                </div>
                <div class="card-body images-content" data-section-content="images">
                    <!-- منطقة السحب والإفلات -->
                    <div class="dropzone-wrapper mb-4">
                        <div id="imageDropzone" class="dropzone">
                            <div class="dz-message">
                                <i class="fas fa-cloud-upload-alt fa-3x"></i>
                                <h4>اسحب وأفلت الصور هنا</h4>
                                <p>أو انقر للاختيار من جهازك</p>
                                <p class="small text-muted">يمكنك أيضاً لصق الصور مباشرة (Ctrl+V)</p>
                            </div>
                        </div>
                    </div>

                    <!-- عرض الصور -->
                    <div id="lessonImages" class="row g-4">
                        <?php 
                        $images = getLessonImages($lesson['id']);
                        foreach ($images as $image): 
                        ?>
                            <div class="col-md-6 col-lg-4" data-image-id="<?php echo $image['id']; ?>">
                                <div class="card h-100">
                                    <div class="position-relative">
                                        <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                                             class="card-img-top lesson-image" 
                                             alt="<?php echo htmlspecialchars($image['title']); ?>">
                                        <div class="image-actions position-absolute top-0 end-0 p-2">
                                            <button class="btn btn-light btn-sm me-1 copy-image-url" 
                                                    data-url="<?php echo htmlspecialchars($image['image_url']); ?>"
                                                    title="نسخ رابط الصورة">
                                                <i class="fas fa-link"></i>
                                            </button>
                                            <button class="btn btn-light btn-sm me-1 edit-image" 
                                                    data-id="<?php echo $image['id']; ?>"
                                                    data-title="<?php echo htmlspecialchars($image['title']); ?>"
                                                    data-description="<?php echo htmlspecialchars($image['description'] ?? ''); ?>"
                                                    title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-light btn-sm delete-image" 
                                                    data-id="<?php echo $image['id']; ?>"
                                                    title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($image['title']); ?></h5>
                                        <?php if (!empty($image['description'])): ?>
                                            <p class="card-text"><?php echo nl2br(htmlspecialchars($image['description'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer text-muted">
                                        <small>
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo formatDate($image['created_at']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>

<!-- JavaScript Libraries (load only once) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.7.2/tinymce.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/plugins/toolbar/prism-toolbar.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/plugins/copy-to-clipboard/prism-copy-to-clipboard.min.js"></script>
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>

<!-- Global variables (define only once) -->
<script>
    const lessonId = <?php echo json_encode($lesson['id']); ?>;
    const baseUrl = '<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/\\'); ?>';
</script>

<!-- Custom JavaScript -->
<script src="/content/assets/js/code-controls.js"></script>
<script src="/content/assets/js/lesson-images.js"></script>
<?php include '../lesson-details/js.php'; ?>

<!-- في قسم head، نضيف -->
<link rel="stylesheet" href="/content/assets/css/lessons-header.css">

<!-- قبل نهاية body، نضيف -->
<script src="/content/assets/js/lessons-header.js"></script>

<!-- Add before closing body tag -->
<script src="/content/assets/js/lesson-actions.js"></script>
<script src="/content/assets/js/chatgpt.js"></script>

<!-- Add in the head section -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

</body>
</html>
