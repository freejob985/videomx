<!-- إضافة CSS لمعالجة مشكلة عرض الفيديو -->
<style>
.video-container {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
}
.video-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 0;
}
</style>
<!-- إضافة CSS للمؤشر -->
<style>
.loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}
</style>

<!-- إضافة CSS للحالات -->
<style>
.status-card {
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.status-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.status-card .card-body {
    border-radius: 8px;
    padding: 1.25rem;
}

.status-card .card-title {
    font-size: 1rem;
    margin-bottom: 0.5rem;
}

.status-card .badge {
    font-size: 0.875rem;
    padding: 0.5em 0.75em;
}

.status-card .progress {
    background-color: rgba(255,255,255,0.3);
}

.status-card .progress-bar {
    transition: width 0.6s ease;
}

/* تنسيق Tags */
.tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.tags-container .badge {
    font-size: 0.75rem;
    padding: 0.35em 0.65em;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    transition: all 0.2s ease;
}

.tags-container .badge:hover {
    background-color: #e9ecef;
}

/* تنسيق أزرار الإجراءات */
.actions {
    display: flex;
    gap: 0.25rem;
}

.actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.actions .btn:hover {
    transform: translateY(-1px);
}
</style>

<!-- إضافة CSS للأزرار -->
<style>
/* تنسيق مجموعة الأزرار */
.btn-group-actions {
    display: flex;
    gap: 0.25rem;
}

/* تنسيق الأزرار */
.btn-group-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

/* تأثير التحويم */
.btn-group-actions .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* تنسيق الأيقونات */
.btn-group-actions .btn i {
    width: 1rem;
    text-align: center;
}

/* ألوان مخصصة للأزرار */
.btn-outline-warning:hover i {
    color: #fff !important;
}

.btn-outline-info:hover i {
    color: #fff !important;
}
</style>

<!-- إضافة CSS للشارات -->
<style>
/* تنسيق رأس الدرس */
.lesson-header {
    position: relative;
}

.lesson-title {
    font-weight: 500;
    color: #2c3e50;
}

/* تنسيق حاوية الشارات */
.lesson-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-top: 0.5rem;
}

/* تنسيق الشارات */
.lesson-badges .badge {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.65rem;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 6px;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
    gap: 0.4rem;
}

.lesson-badges .badge i {
    font-size: 0.8rem;
}

/* تأثير الظهور للشارات */
.badge-appear {
    animation: badgeAppear 0.3s ease forwards;
}

@keyframes badgeAppear {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* تنسيق الشارات المختلفة */
.badge-important {
    background: rgba(255, 193, 7, 0.15);
    color: #ff9800;
    border: 1px solid rgba(255, 193, 7, 0.3);
}

.badge-important:hover {
    background: rgba(255, 193, 7, 0.25);
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(255, 193, 7, 0.2);
}

.badge-theory {
    background: rgba(33, 150, 243, 0.15);
    color: #2196f3;
    border: 1px solid rgba(33, 150, 243, 0.3);
}

.badge-theory:hover {
    background: rgba(33, 150, 243, 0.25);
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(33, 150, 243, 0.2);
}

/* تنسيق التاجات */
.lesson-tag {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    margin: 0.125rem;
    border-radius: 0.25rem;
    background-color: #e9ecef;
    color: #495057;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    opacity: 0;
    transform: scale(0.9);
}

.lesson-tag.show {
    opacity: 1;
    transform: scale(1);
}

.lesson-tag i {
    margin-right: 0.25rem;
}

/* تنسيق عنوان الدرس */
.lesson-title-wrapper {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.lesson-title {
    font-weight: 500;
    color: #2d3436;
    text-decoration: none;
    transition: color 0.2s ease;
}

.lesson-title.completed {
    color: #5a9904;
    text-decoration: line-through;
    text-decoration-thickness: 2px;
    text-decoration-color: rgba(90, 153, 4, 0.4);
}

/* إخفاء قسم البحث */
.search-section {
    display: none !important;
}

/* تحديث تنسيق البطاقات الإحصائية */
.stat-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

/* إضافة بطاقة الوقت المتبقي */
.stat-icon.remaining {
    background: #e8eaf6;
    color: #3f51b5;
}

.stats-title {
    color: #333;
    font-size: 1.25rem;
    margin-bottom: 1.5rem;
    font-weight: 600;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 1.25rem;
    transition: all 0.3s ease;
    border: 1px solid #e0e0e0;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-icon.completed {
    background: #e8f5e9;
    color: #2e7d32;
}

.stat-icon.pending {
    background: #fff3e0;
    color: #ef6c00;
}

.stat-icon.total {
    background: #e3f2fd;
    color: #1565c0;
}

.stat-icon.duration {
    background: #f3e5f5;
    color: #7b1fa2;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #666;
    font-size: 0.875rem;
}

.progress-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #eee;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.progress-title {
    font-size: 1rem;
    color: #333;
    font-weight: 500;
}

.progress-percentage {
    font-size: 0.875rem;
    color: #666;
    font-weight: 500;
}

.progress-bar-container {
    height: 8px;
    background: #f5f5f5;
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(45deg, #2196f3, #1976d2);
    border-radius: 4px;
    transition: width 0.6s ease;
}
</style>

<!-- إضافة CSS للمسافات والتنسيق -->
<style>
/* تنسيق المسافات بين العناصر */
.lesson-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.lesson-title-wrapper {
    flex: 1;
}

.lesson-badges {
    display: flex;
    gap: 0.5rem;
    margin-right: 1rem;
}

/* تنسيق Toast */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

.toast {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    opacity: 0;
    transform: translateY(-20px);
    transition: all 0.3s ease;
}

.toast.show {
    opacity: 1;
    transform: translateY(0);
}

/* تنسيق أنواع Toast */
.toast-success {
    border-right: 4px solid #28a745;
}

.toast-warning {
    border-right: 4px solid #ffc107;
}

.toast-info {
    border-right: 4px solid #17a2b8;
}
</style>

<!-- تخصيص مظهر رسائل التوست -->
<style>
/* تنسيق عام لجميع رسائل التوست */
.toast-container {
    font-family: 'Cairo', sans-serif;
    direction: rtl;
}

/* تنسيق رسالة النجاح */
.toast-success {
    background-color: #28a745 !important;
    color: #ffffff !important;
    opacity: 1 !important;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15) !important;
}

/* تنسيق رسالة الخطأ */
.toast-error {
    background-color: #dc3545 !important;
    color: #ffffff !important;
    opacity: 1 !important;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.15) !important;
}

/* تنسيق رسالة التحذير */
.toast-warning {
    background-color: #ffc107 !important;
    color: #000000 !important;
    opacity: 1 !important;
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.15) !important;
}

/* تنسيق رسالة المعلومات */
.toast-info {
    background-color: #17a2b8 !important;
    color: #ffffff !important;
    opacity: 1 !important;
    box-shadow: 0 4px 12px rgba(23, 162, 184, 0.15) !important;
}

/* تنسيق عنوان التوست */
.toast-title {
    font-weight: bold;
    font-size: 1rem;
    margin-bottom: 5px;
}

/* تنسيق نص الرسالة */
.toast-message {
    font-size: 0.9rem;
    line-height: 1.4;
}

/* تنسيق زر الإغلاق */
.toast-close-button {
    color: inherit !important;
    opacity: 0.8 !important;
    text-shadow: none !important;
}

.toast-close-button:hover {
    color: inherit !important;
    opacity: 1 !important;
}

/* تحسين موضع التوست */
#toast-container {
    padding: 15px;
}

#toast-container > div {
    padding: 15px 15px 15px 50px;
    width: 300px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* تحسين الأيقونات */
.toast-success,
.toast-error,
.toast-warning,
.toast-info {
    background-image: none !important;
    padding: 15px !important;
}

.toast:before {
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    font-size: 24px;
    line-height: 24px;
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
}

.toast-success:before {
    content: '\f00c'; /* أيقونة النجاح */
}

.toast-error:before {
    content: '\f00d'; /* أيقونة الخطأ */
}

.toast-warning:before {
    content: '\f071'; /* أيقونة التحذير */
}

.toast-info:before {
    content: '\f129'; /* أيقونة المعلومات */
}
</style>
<!-- إضافة CSS للقائمة المنسدلة -->
<style>
.dropdown-menu {
    min-width: 200px;
    padding: 0.5rem 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.dropdown-item {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.dropdown-item i {
    width: 1.25rem;
    text-align: center;
}

.dropdown-divider {
    margin: 0.5rem 0;
}

/* تحسين مظهر زر القائمة */
.btn-light {
    border-color: #dee2e6;
    padding: 0.25rem 0.5rem;
}

.btn-light:hover {
    background-color: #e9ecef;
}

/* تأثيرات حركية */
.dropdown-menu {
    transform-origin: top right;
    animation: dropdownFade 0.2s ease;
}

@keyframes dropdownFade {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>

<!-- إضافة CSS للدروس -->
<style>
/* تنسيق عناوين الدروس المكتملة */
.lesson-title.completed {
    color: #5a9904 !important;
    text-decoration: line-through;
}

/* تنسيق عناوين الدروس غير المكتملة */
.lesson-title:not(.completed) {
    text-decoration: none;
}
</style>

<!-- إضافة CSS لإحصائيات الدروس -->
<style>
/* تنسيق إحصائيات الدروس */
.course-stats {
    margin: 2rem 0;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 16px;
    box-shadow: 0 2px 20px rgba(0,0,0,0.08);
    padding: 1.5rem;
    border: 1px solid rgba(0,0,0,0.05);
}

/* تنسيق معلومات الكورس */
.course-info {
    background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);
    border-radius: 16px;
    padding: 1.5rem;
    color: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* تنسيق Tags الدروس */
.lesson-tags {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
    margin-top: 0.5rem;
}

.lesson-tag {
    font-size: 0.7rem;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
    background: rgba(var(--tag-rgb), 0.1);
    color: rgb(var(--tag-rgb));
    border: 1px solid rgba(var(--tag-rgb), 0.2);
}

.lesson-tag i {
    font-size: 0.65rem;
    opacity: 0.7;
}

.lesson-tag:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(var(--tag-rgb), 0.15);
    background: rgba(var(--tag-rgb), 0.15);
}

/* تعريف الألوان للتاجات المختلفة */
.lesson-tag.html {
    --tag-rgb: 235, 87, 87;
}

.lesson-tag.css {
    --tag-rgb: 78, 205, 196;
}

.lesson-tag.javascript {
    --tag-rgb: 255, 217, 61;
}

.lesson-tag.php {
    --tag-rgb: 108, 92, 231;
}

.lesson-tag.mysql {
    --tag-rgb: 46, 134, 222;
}

.lesson-tag.api {
    --tag-rgb: 46, 213, 115;
}

.lesson-tag.git {
    --tag-rgb: 214, 48, 49;
}

.lesson-tag.linux {
    --tag-rgb: 55, 66, 250;
}

.lesson-tag.wordpress {
    --tag-rgb: 18, 137, 167;
}

/* تنسيق عنوان الدرس */
.lesson-title-wrapper {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.lesson-title {
    font-weight: 500;
    color: #2d3436;
    text-decoration: none;
    transition: color 0.2s ease;
}

.lesson-title.completed {
    color: #5a9904;
    text-decoration: line-through;
    text-decoration-thickness: 2px;
    text-decoration-color: rgba(90, 153, 4, 0.4);
}

/* إخفاء قسم البحث */
.search-section {
    display: none;
}

/* تحديث تنسيق البطاقات الإحصائية */
.stat-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

/* إضافة بطاقة الوقت المتبقي */
.stat-icon.remaining {
    background: #e8eaf6;
    color: #3f51b5;
}

.stats-title {
    color: #333;
    font-size: 1.25rem;
    margin-bottom: 1.5rem;
    font-weight: 600;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 1.25rem;
    transition: all 0.3s ease;
    border: 1px solid #e0e0e0;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-icon.completed {
    background: #e8f5e9;
    color: #2e7d32;
}

.stat-icon.pending {
    background: #fff3e0;
    color: #ef6c00;
}

.stat-icon.total {
    background: #e3f2fd;
    color: #1565c0;
}

.stat-icon.duration {
    background: #f3e5f5;
    color: #7b1fa2;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #666;
    font-size: 0.875rem;
}

.progress-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #eee;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.progress-title {
    font-size: 1rem;
    color: #333;
    font-weight: 500;
}

.progress-percentage {
    font-size: 0.875rem;
    color: #666;
    font-weight: 500;
}

.progress-bar-container {
    height: 8px;
    background: #f5f5f5;
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(45deg, #2196f3, #1976d2);
    border-radius: 4px;
    transition: width 0.6s ease;
}


/* إخفاء قسم البحث والفلترة */
.filters-section,
.search-section,
.course-progress {
    display: none !important;
}

/* تنسيق جديد للتاجات */
.lesson-tag {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
    font-weight: 500;
    border-radius: 50px;
    transition: all 0.3s ease;
    margin: 0.25rem;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-decoration: none;
    gap: 0.5rem;
}

/* تنسيق أيقونة التاج */
.lesson-tag i {
    font-size: 0.9rem;
    transition: transform 0.3s ease;
}

.lesson-tag:hover i {
    transform: rotate(15deg);
}

/* تنسيقات مخصصة لكل نوع تاج */
.lesson-tag.html {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5253 100%);
    color: white;
    border: none;
}

.lesson-tag.css {
    background: linear-gradient(135deg, #4834d4 0%, #686de0 100%);
    color: white;
    border: none;
}

.lesson-tag.javascript {
    background: linear-gradient(135deg, #ffd32a 0%, #ffb142 100%);
    color: #2d3436;
    border: none;
}

.lesson-tag.php {
    background: linear-gradient(135deg, #6c5ce7 0%, #a55eea 100%);
    color: white;
    border: none;
}

/* تأثير التحويم على التاجات */
.lesson-tag:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* تنسيق شارة "نظري" */
.badge-theory {
    background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.badge-theory i {
    font-size: 0.9rem;
}

.badge-theory:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* تنسيق شارة "مهم" */
.badge-important {
    background: linear-gradient(135deg, #ff9f43 0%, #ff6b6b 100%);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 2px 4px rgba(255,159,67,0.3);
    transition: all 0.3s ease;
    animation: pulse 2s infinite;
}

.badge-important i {
    font-size: 0.9rem;
}

.badge-important:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255,159,67,0.4);
}

/* تأثير النبض للشارة المهمة */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(255,159,67,0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(255,159,67,0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(255,159,67,0);
    }
}

/* تنسيق حاوية الشارات */
.lesson-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

/* تأثير ظهور الشارات */
.badge-appear {
    animation: badgeAppear 0.3s ease-out forwards;
}

@keyframes badgeAppear {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* تنسيق أيقونات الإحصائيات الجديدة */
.stat-icon.theory {
    background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
    color: white;
}

.stat-icon.completed-duration {
    background: linear-gradient(135deg, #6c5ce7 0%, #a55eea 100%);
    color: white;
}

.stat-icon.projects {
    background: linear-gradient(135deg, #6c5ce7 0%, #a55eea 100%);
    color: white;
}

.stat-icon.review {
    background: linear-gradient(135deg, #ff9f43 0%, #ff6b6b 100%);
    color: white;
}

.stat-icon.status {
    background: linear-gradient(135deg, #4834d4 0%, #686de0 100%);
    color: white;
}

/* تحسين تنسيق شبكة الإحصائيات */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

/* إضافة تنسيق أيقونة المراجعة */
.stat-icon.revision {
    background: linear-gradient(135deg, #20bf6b 0%, #0fb9b1 100%);
    color: white;
}

/* تحسين التجاوب */
@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 992px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

/* تحسين تأثيرات الحركة */
.stat-card {
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.stat-icon {
    transition: all 0.3s ease;
}

.stat-card:hover .stat-icon {
    transform: scale(1.1);
}

/* تنسيقات جديدة للإحصائيات */
.stat-icon.total {
    background-color: #6c757d;
}

.stat-icon.remaining-lessons {
    background-color: #ffc107;
}

.stat-icon.important {
    background: linear-gradient(135deg, #ff9f43 0%, #ff6b6b 100%);
    color: white;
    animation: pulse 2s infinite;
}

.stat-icon.no-status {
    background: linear-gradient(135deg, #ee5253 0%, #ff6b6b 100%);
    color: white;
}

/* تحديث تنسيقات الإحصائيات الحالية */
.stat-icon.review {
    background-color: #17a2b8;
}

.stat-icon.revision {
    background-color: #6610f2;
}

/* تحسين تنسيق البطاقات */
.stat-card {
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

/* تحسين عرض الأرقام */
.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
}

/* تحسين عرض التسميات */
.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
}

/* تحسين البحث عن النصوص العربية */
.stat-card {
    direction: rtl;
    text-align: right;
}

/* تحسين عرض الوقت */
.stat-value {
    font-family: 'Courier New', monospace;
    letter-spacing: 1px;
}

/* تأثير النبض للدروس المهمة */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(255,159,67,0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(255,159,67,0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(255,159,67,0);
    }
}

/* تنسيقات جديدة للإحصائيات */
.stat-icon.remaining-duration {
    background: linear-gradient(135deg, #ffd32a 0%, #ffb142 100%);
    color: #2d3436;
}


/* تنسيقات قسم التصفية والرسوم البيانية */
.stats-filter {
    background: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.chart-toggle .btn {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.chart-toggle .btn.active {
    background-color: #007bff;
    color: #fff;
}

.charts-section .card {
    height: 100%;
    transition: all 0.3s ease;
}

.charts-section .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* تحسين التجاوب للرسوم البيانية */
@media (max-width: 768px) {
    .charts-section .col-md-6 {
        margin-bottom: 1rem;
    }
}
</style>
<style>
/* إضافة تأثيرات انتقالية */
#courseInfoContent,
#courseSectionsContent,
#statsContent,
#chartsSection {
    transition: all 0.3s ease-in-out;
}

#toggleCourseInfoBtn,
#toggleStatsBtn,
#toggleChartsBtn {
    transition: all 0.2s ease-in-out;
}

#toggleCourseInfoBtn:hover,
#toggleStatsBtn:hover,
#toggleChartsBtn:hover {
    opacity: 0.8;
    transform: scale(1.1);
}

/* تنسيق أزرار التبديل */
.btn-link {
    text-decoration: none;
}

.btn-link:hover {
    text-decoration: none;
}

/* تنسيق أيقونات التبديل */
.fa-eye,
.fa-eye-slash {
    font-size: 1.2rem;
}

/* تنسيق قسم الحالات */
#statusesContent {
    transition: all 0.3s ease-in-out;
}

#toggleStatusesBtn {
    transition: all 0.2s ease-in-out;
}

#toggleStatusesBtn:hover {
    opacity: 0.8;
    transform: scale(1.1);
}

/* تنسيق بطاقات الحالات */
.status-card {
    transition: all 0.3s ease;
}

.status-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}


/* أنماط أزرار التبديل */
.btn-link i {
    transition: transform 0.3s ease;
}

.btn-link:hover i {
    transform: scale(1.1);
}

/* أنماط المحتوى القابل للطي */
#fullStatsContent,
#courseInfoContent {
    transition: height 0.3s ease-out;
    overflow: hidden;
}

/* أنماط الأيقونات */
.fa-eye,
.fa-eye-slash {
    font-size: 1.2rem;
}

/* تحسين مظهر الأزرار */
.btn-link {
    text-decoration: none;
    padding: 5px !important;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
}
</style>



<!-- تنسيقات التاجات -->
<style>
.tags-input-wrapper {
    position: relative;
    padding: 8px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    background: #fff;
    min-height: 46px;
}

.tags-input-wrapper:focus-within {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.tags-input {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    padding: 0;
    margin: 0;
}

.tag-item {
    display: inline-flex;
    align-items: center;
    background: #e9ecef;
    border-radius: 3px;
    padding: 2px 8px;
    margin: 2px;
    color: #495057;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.tag-item:hover {
    background: #dee2e6;
}

.tag-item .remove-tag {
    margin-left: 5px;
    cursor: pointer;
    color: #6c757d;
    font-size: 12px;
    padding: 2px;
}

.tag-item .remove-tag:hover {
    color: #dc3545;
}

.tags-input input {
    border: none;
    outline: none;
    padding: 5px;
    font-size: 0.875rem;
    flex: 1;
    min-width: 120px;
}

.tags-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #ced4da;
    border-radius: 4px;
    margin-top: 5px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: none;
}

.tags-suggestions.active {
    display: block;
}

.suggestion-item {
    padding: 8px 12px;
    cursor: pointer;
    transition: background 0.2s ease;
}

.suggestion-item:hover {
    background: #f8f9fa;
}

.suggestion-item.selected {
    background: #e9ecef;
}

/* تنسيقات النوافذ المنبثقة */
.modal-content {
    transform: scale(0.95);
    opacity: 0;
    transition: all 0.3s ease;
}

.modal.show .modal-content {
    transform: scale(1);
    opacity: 1;
}

/* تنسيقات النموذج */
.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}


/* تنسيقات لإحصائيات كامل الكورس */
#fullCourseStatsContent {
    opacity: 1;
    transform: translateY(0);
    transition: all 0.3s ease;
}

#fullCourseStatsContent.hidden {
    opacity: 0;
    transform: translateY(-10px);
}

#toggleFullCourseStatsBtn {
    cursor: pointer;
    transition: all 0.3s ease;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: transparent;
    border: none;
    padding: 0;
}

#toggleFullCourseStatsBtn:hover {
    transform: scale(1.1);
    background-color: rgba(0, 0, 0, 0.05);
}

#toggleFullCourseStatsBtn i {
    transition: all 0.3s ease;
    font-size: 1.2rem;
}

/* تحسين الحركة */
.slide-toggle {
    overflow: hidden;
    transition: height 0.3s ease-in-out;
}
</style>
<style>
/* تنسيقات زر التبديل وأيقونته */
#toggleFullCourseStatsBtn {
    cursor: pointer;
    transition: all 0.3s ease;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: transparent;
    border: none;
    padding: 0;
}

#toggleFullCourseStatsBtn:hover {
    transform: scale(1.1);
    background-color: rgba(0, 0, 0, 0.05);
}

#toggleFullCourseStatsBtn i {
    transition: all 0.3s ease;
    font-size: 1.2rem;
}

/* تنسيقات المحتوى */
#fullCourseStatsContent {
    transition: all 0.3s ease;
}

/* تأثيرات الحركة */
.slide-enter-active,
.slide-leave-active {
    transition: all 0.3s ease;
    max-height: 1000px;
    opacity: 1;
}

.slide-enter,
.slide-leave-to {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
}
</style>