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

/* تنسيق الأزرار */
.btn-group .btn {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    margin: 0 2px;
}

.btn-play {
    background: linear-gradient(135deg, #11998e, #38ef7d);
    border: none;
    color: white;
}

.btn-edit {
    background: linear-gradient(135deg, #8e2de2, #4a00e0);
    border: none;
    color: white;
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
</style>

<!-- معلومات القسم -->
<div class="container py-5">
    <div class="section-info">
        <h3 class="text-primary mb-3">
            <i class="fas fa-folder-open me-2"></i>
            <?php echo htmlspecialchars($section['name']); ?>
        </h3>
        <div class="section-description mb-3">
            <?php echo $section['description'] ?? ''; ?>
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
                            <tr ondblclick="window.location.href='/content/views/lesson-details.php?id=<?php echo $lesson['id']; ?>'">
                                <td class="text-center"><?php echo $index + 1; ?></td>
                                <td class="lesson-title">
                                    <?php echo htmlspecialchars($lesson['title']); ?>
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
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-play" 
                                                onclick="playVideo('<?php echo htmlspecialchars($lesson['video_url']); ?>', '<?php echo htmlspecialchars($lesson['title']); ?>')"
                                                title="تشغيل الدرس">
                                            <i class="fas fa-play-circle"></i>
                                        </button>
                                        <button class="btn btn-sm btn-info" 
                                                onclick="editLessonSection(<?php echo $lesson['id']; ?>, <?php echo $section_id; ?>)"
                                                title="تغيير القسم">
                                            <i class="fas fa-folder-tree"></i>
                                        </button>
                                        <a href="/sections/edit_lesson.php?lesson_id=<?php echo $lesson['id']; ?>" 
                                           class="btn btn-sm btn-edit"
                                           title="تعديل الدرس">
                                            <i class="fas fa-pen-fancy"></i>
                                        </a>
                                        <button class="btn btn-sm <?php echo $lesson['completed'] ? 'btn-success' : 'btn-outline-success'; ?>"
                                                onclick="toggleLessonCompletion(<?php echo $lesson['id']; ?>, this)"
                                                title="<?php echo $lesson['completed'] ? 'تم الإكمال' : 'تحديد كمكتمل'; ?>">
                                            <i class="fas <?php echo $lesson['completed'] ? 'fa-check-circle' : 'fa-circle'; ?>"></i>
                                        </button>
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

<script>
let currentLessonId = null;

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

// إضافة المتغيرات في بداية الملف
const STORAGE_KEY = 'lesson_progress_<?php echo $section_id; ?>';
let completedLessons = new Set(JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'));

function toggleLessonCompletion(lessonId, button) {
    const isCompleted = button.classList.contains('btn-success');
    const formData = new FormData();
    formData.append('lesson_id', lessonId);
    formData.append('completed', (!isCompleted).toString());

    // تحديث واجهة المستخدم
    button.disabled = true;
    
    fetch('/sections/ajax/toggle_lesson_completion.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // تحديث الزر
            button.classList.toggle('btn-success');
            button.classList.toggle('btn-outline-success');
            
            const icon = button.querySelector('i');
            icon.classList.toggle('fa-check-circle');
            icon.classList.toggle('fa-circle');
            
            // تحديث التخزين المحلي
            if (!isCompleted) {
                completedLessons.add(lessonId);
            } else {
                completedLessons.delete(lessonId);
            }
            localStorage.setItem(STORAGE_KEY, JSON.stringify([...completedLessons]));
            
            // تحديث شريط التقدم
            updateProgressBar();
            
            // إظهار رسالة نجاح
            Swal.fire({
                icon: 'success',
                title: data.message,
                timer: 1500,
                showConfirmButton: false
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
    })
    .finally(() => {
        button.disabled = false;
    });
}

function updateProgressBar() {
    const totalLessons = <?php echo $section['lessons_count']; ?>;
    const completedCount = completedLessons.size;
    const percentage = (completedCount / totalLessons) * 100;
    
    const progressBar = document.querySelector('.progress-bar');
    progressBar.style.width = percentage + '%';
    progressBar.textContent = Math.round(percentage) + '%';
    progressBar.setAttribute('aria-valuenow', completedCount);
}

// تحديث شريط التقدم عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', updateProgressBar);
</script>

<?php require_once '../includes/footer.php'; ?> 