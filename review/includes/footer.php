<?php
require_once __DIR__ . '/functions.php';

/**
 * فوتر الموقع
 * يحتوي على روابط التنقل الرئيسية وتذييل الصفحة
 * 
 * المتطلبات:
 * - اتصال قاعدة البيانات
 * - عرض اللغات المتاحة التي تحتوي على دروس
 */

// جلب اللغات المتاحة
$languages_query = "SELECT 
                    l.*,
                    COUNT(DISTINCT les.id) as lessons_count
                FROM languages l
                INNER JOIN courses c ON c.language_id = l.id
                INNER JOIN lessons les ON les.course_id = c.id
                WHERE les.status_id = 1
                GROUP BY l.id
                ORDER BY l.name ASC";

$languages_result = $conn->query($languages_query);
$available_languages = [];

if ($languages_result) {
    while ($row = $languages_result->fetch_assoc()) {
        $available_languages[] = $row;
    }
    $languages_result->close();
}
?>

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
                                <a href="views/lessons-cards.php?course_id=<?php echo $course_id; ?>">
                                    <i class="fas fa-th-large me-2"></i>
                                    عرض البطاقات
                                </a>
                            </li>
                            <li>
                                <a href="lessons.php?course_id=<?php echo $course_id; ?>">
                                    <i class="fas fa-list me-2"></i>
                                    عرض الجدول
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- القسم الثاني: روابط التنقل الرئيسية -->
                <div class="col-md-4 mb-4">
                    <h5 class="text-white mb-4">روابط رئيسية</h5>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2">
                            <a href="http://videomx.com/" target="_blank">
                                <i class="fas fa-home me-2"></i>
                                البوابة الرئيسية
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="http://videomx.com/content/languages.php">
                                <i class="fas fa-globe me-2"></i>
                                اللغات
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="http://videomx.com/content/index.php">
                                <i class="fas fa-graduation-cap me-2"></i>
                                الدورات
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="http://videomx.com/add/add.php" target="_blank">
                                <i class="fas fa-cog me-2"></i>
                                الإعدادات
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="courses.php" class="d-flex align-items-center">
                                <i class="fas fa-tasks me-2"></i>
                                الكورسات المراجعة
                                <?php if (isset($courses) && is_array($courses)): ?>
                                    <span class="ms-auto badge bg-light text-dark">
                                        <?php echo count($courses); ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="http://videomx.com/content/search/">
                                <i class="fas fa-search me-2"></i>
                                البحث
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- القسم الثالث: اللغات المتاحة -->
                <div class="col-md-4 mb-4">
                    <div class="section-header d-flex justify-content-between align-items-center mb-4">
                        <h5 class="text-white mb-0">
                            <i class="fas fa-globe me-2"></i>
                            اللغات المتاحة
                        </h5>
                        <button class="btn-toggle-languages" id="toggleLanguages" title="إخفاء/إظهار اللغات">
                            <i class="fas fa-chevron-up"></i>
                        </button>
                    </div>
                    <div class="languages-container" id="languagesContainer">
                        <div class="languages-grid">
                            <?php foreach ($available_languages as $language): ?>
                                <?php if ($language['lessons_count'] > 0): ?>
                                    <a href="http://videomx.com/content/courses.php?language_id=<?php echo $language['id']; ?>" 
                                       class="language-button">
                                        <div class="icon-wrapper">
                                            <i class="<?php echo getLanguageIcon($language['name']); ?>"></i>
                                        </div>
                                        <div class="language-details">
                                            <span class="language-name"><?php echo htmlspecialchars($language['name']); ?></span>
                                            <div class="language-meta">
                                                <span class="count"><?php echo $language['lessons_count']; ?> درس</span>
                                                <?php if (isset($language['courses_count'])): ?>
                                                    <span class="separator">•</span>
                                                    <span class="count"><?php echo $language['courses_count']; ?> كورس</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- حقوق النشر -->
            <div class="footer-bottom py-3 text-center border-top border-light">
                <p class="mb-0 text-white-50">
                    جميع الحقوق محفوظة &copy; <?php echo date('Y'); ?> VideoMX
                </p>
            </div>
        </div>
    </div>
</footer>

<style>
/* تنسيقات الفوتر */
.site-footer {
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    color: #fff;
    margin-top: auto;
}

.footer-gradient {
    background: rgba(0,0,0,0.1);
}

.footer-links a {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    padding: 10px 15px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    background: rgba(255,255,255,0.05);
    margin-bottom: 8px;
}

.footer-links a:hover {
    color: #fff;
    background: rgba(255,255,255,0.1);
    transform: translateX(-5px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.footer-links a i {
    width: 20px;
    text-align: center;
    transition: transform 0.3s ease;
}

.footer-links a:hover i {
    transform: scale(1.2);
}

.footer-links .badge {
    transition: all 0.3s ease;
}

.footer-links a:hover .badge {
    transform: scale(1.1);
    background: #fff !important;
}

.languages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
}

.language-button {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 15px;
    text-decoration: none;
    color: white;
    transition: all 0.3s ease;
    text-align: center;
    border: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    overflow: hidden;
}

.language-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(255,255,255,0.1), transparent);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.language-button:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    color: white;
}

.language-button:hover::before {
    opacity: 1;
}

.icon-wrapper {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    font-size: 1.5em;
    transition: all 0.3s ease;
}

.language-button:hover .icon-wrapper {
    transform: scale(1.1);
    background: rgba(255, 255, 255, 0.2);
}

.language-details {
    width: 100%;
}

.language-name {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    font-size: 0.9em;
}

.language-meta {
    font-size: 0.75em;
    opacity: 0.8;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 5px;
}

.separator {
    opacity: 0.5;
}

/* تخصيص الألوان حسب اللغة */
.language-button[href*="php"] .icon-wrapper { background: rgba(119, 123, 179, 0.3); }
.language-button[href*="javascript"] .icon-wrapper { background: rgba(247, 223, 30, 0.3); }
.language-button[href*="python"] .icon-wrapper { background: rgba(55, 118, 171, 0.3); }
.language-button[href*="java"] .icon-wrapper { background: rgba(248, 152, 32, 0.3); }
.language-button[href*="html"] .icon-wrapper { background: rgba(228, 77, 38, 0.3); }
.language-button[href*="css"] .icon-wrapper { background: rgba(33, 150, 243, 0.3); }
.language-button[href*="react"] .icon-wrapper { background: rgba(97, 218, 251, 0.3); }

/* تحسينات للموبايل */
@media (max-width: 768px) {
    .languages-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 10px;
    }

    .language-button {
        padding: 12px;
    }

    .icon-wrapper {
        width: 40px;
        height: 40px;
        font-size: 1.2em;
        margin-bottom: 8px;
    }

    .language-name {
        font-size: 0.85em;
    }

    .language-meta {
        font-size: 0.7em;
    }

    .footer-links a {
        padding: 8px 12px;
        font-size: 0.9em;
    }
}

/* تنسيقات زر الإخفاء/الإظهار */
.section-header {
    position: relative;
}

.btn-toggle-languages {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-toggle-languages:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: scale(1.1);
}

.btn-toggle-languages i {
    transition: transform 0.3s ease;
}

.btn-toggle-languages.collapsed i {
    transform: rotate(180deg);
}

/* تنسيق حاوية اللغات */
.languages-container {
    transition: all 0.3s ease;
    overflow: hidden;
    max-height: 1000px; /* قيمة كبيرة لضمان ظهور جميع العناصر */
}

.languages-container.collapsed {
    max-height: 0;
    opacity: 0;
    margin: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleLanguages');
    const container = document.getElementById('languagesContainer');
    
    // استرجاع الحالة المحفوظة
    const isCollapsed = localStorage.getItem('languagesSectionCollapsed') === 'true';
    
    // تطبيق الحالة المحفوظة
    if (isCollapsed) {
        container.classList.add('collapsed');
        toggleBtn.classList.add('collapsed');
    }
    
    // معالجة النقر على زر الإخفاء/الإظهار
    toggleBtn.addEventListener('click', function() {
        const willCollapse = !container.classList.contains('collapsed');
        
        // تحديث الواجهة
        container.classList.toggle('collapsed');
        toggleBtn.classList.toggle('collapsed');
        
        // حفظ الحالة
        localStorage.setItem('languagesSectionCollapsed', willCollapse);
        
        // إضافة تأثير حركي للزر
        toggleBtn.style.transform = 'scale(0.9)';
        setTimeout(() => {
            toggleBtn.style.transform = 'scale(1)';
        }, 150);
    });
    
    // إضافة تأثير حركي عند تحميل الصفحة
    container.style.opacity = '0';
    setTimeout(() => {
        container.style.opacity = '1';
    }, 100);
});
</script>