<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <!-- إضافة ملف المتغيرات -->
    <link rel="stylesheet" href="../assets/css/variables.css">
</head>
<body>
    <!-- شريط التنقل العلوي -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <!-- زر العودة للغات -->
            <a href="/content/index.php" class="btn btn-outline-light me-2">
                <i class="fas fa-globe me-1"></i>
                اللغات
            </a>

            <!-- زر العودة للغة الحالية -->
            <?php if (isset($lesson['language_id'])): ?>
            <a href="/content/courses.php?language_id=<?php echo (int)$lesson['language_id']; ?>" 
               class="btn btn-outline-light me-2">
                <i class="fas fa-arrow-right me-1"></i>
                العودة للغة
            </a>
            <?php endif; ?>

            <!-- أزرار تبديل طريقة العرض -->
            <div class="btn-group ms-auto me-2">
                <!-- زر عرض البطاقات -->
                <?php 
                $currentPage = basename($_SERVER['PHP_SELF']);
                $isCardsView = $currentPage === 'lessons-cards.php';
                ?>
                
                <a href="<?php echo buildUrl('views/lessons-cards.php?course_id=' . $course_id); ?>" 
                   class="btn btn-outline-light <?php echo $isCardsView ? 'active disabled' : ''; ?>">
                    <i class="fas fa-th-large me-1"></i>
                    عرض البطاقات
                </a>
                
                <!-- زر عرض القائمة -->
                <a href="<?php echo buildUrl('lessons.php?course_id=' . $course_id); ?>" 
                   class="btn btn-outline-light <?php echo !$isCardsView ? 'active disabled' : ''; ?>">
                    <i class="fas fa-list me-1"></i>
                    عرض القائمة
                </a>
            </div>

            <!-- زر الإعدادات -->
            <a href="/add/add.php" class="btn btn-outline-light">
                <i class="fas fa-cog me-1"></i>
                الإعدادات والإضافات
            </a>
        </div>
    </nav>

    <!-- باقي محتوى الصفحة -->
</body>
</html>

<style>
/* تنسيق أزرار تبديل طريقة العرض */
.btn-group .btn {
    border-color: rgba(255,255,255,0.5);
}

.btn-group .btn.active {
    background-color: rgba(255,255,255,0.2);
    border-color: #fff;
    cursor: default;
}

.btn-group .btn.disabled {
    opacity: 0.8;
    pointer-events: none;
}

.btn-group .btn:not(.active):hover {
    background-color: rgba(255,255,255,0.1);
    border-color: #fff;
}
</style> 