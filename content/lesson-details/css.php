<style>
/* تنسيقات الدروس المرتبطة */
.related-lessons-section {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
}

.related-lesson-card {
    position: relative;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.related-lesson-card:hover {
    transform: translateY(-5px);
}

.related-lesson-card .status-bar {
    height: 4px;
    width: 100%;
}

.related-lesson-card .card-img-wrapper {
    height: 140px;
    overflow: hidden;
}

.related-lesson-card .card-img-top {
    height: 100%;
    object-fit: cover;
}

.related-lesson-card .play-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #fff;
    font-size: 2rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.related-lesson-card .lesson-badges {
    z-index: 2;
}

.related-lesson-card .lesson-badges .badge {
    margin-left: 0.25rem;
}

.related-lesson-card .card-title {
    line-height: 1.4;
    height: 2.8em;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.hover-shadow:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
}

.current-section {
    border-bottom: 1px solid #eee;
    padding-bottom: 1rem;
}

/* تنسيقات زر الإخفاء/الإظهار */
.toggle-notes {
    transition: transform 0.3s ease;
}

.toggle-notes.collapsed {
    transform: rotate(180deg);
}

/* تأثير حركي لمحتوى الملاحظات */
.notes-content {
    transition: max-height 0.3s ease-out;
    overflow: hidden;
}

.notes-content.collapsed {
    max-height: 0 !important;
    padding: 0;
}

/* تنسيقات عامة للملاحظات */
.note-card {
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
}

.note-card .card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* تنسيق الهيدر لكل نوع */
.text-note .card-header {
    background: linear-gradient(135deg, #4CAF50, #2E7D32);
    color: white;
    border-bottom: none;
}

.code-note .card-header {
    background: linear-gradient(135deg, #2196F3, #1565C0);
    color: white;
    border-bottom: none;
}

.link-note .card-header {
    background: linear-gradient(135deg, #9C27B0, #6A1B9A);
    color: white;
    border-bottom: none;
}

/* تنسيق محتوى الملاحظة */
.note-card .card-body {
    padding: 1.25rem;
}

/* تنسيق خاص للنص */
.text-note .note-content {
    font-size: 1rem;
    line-height: 1.6;
    color: #333;
}

/* تنسيق خاص للكود */
.code-note pre {
    margin: 0;
    border-radius: 4px;
    background: #1E1E1E !important;
}

.code-note code {
    font-family: 'Fira Code', monospace;
    font-size: 0.9rem;
}

/* تنسيق خاص للروابط */
.link-note .note-content a {
    color: #1976D2;
    text-decoration: none;
    word-break: break-all;
    padding: 0.5rem;
    background: #E3F2FD;
    border-radius: 4px;
    display: inline-block;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}

.link-note .note-content a:hover {
    background: #BBDEFB;
    color: #0D47A1;
}

/* تنسيق أزرار التحكم */
.note-actions {
    display: flex;
    gap: 0.5rem;
}

.note-actions button {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 4px;
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    transition: all 0.3s ease;
}

.note-actions button:hover {
    background: rgba(255,255,255,0.3);
    border-color: rgba(255,255,255,0.4);
}

/* تأثيرات التحويم */
.note-card:hover {
    transform: translateY(-2px);
}

/* تنسيق وصف الرابط */
.link-note .note-content p {
    color: #666;
    font-size: 0.9rem;
    background: #F5F5F5;
    padding: 0.5rem;
    border-radius: 4px;
    margin-top: 0.5rem;
}

/* تنسيق أيقونات النوع */
.note-card .card-header::before {
    font-family: "Font Awesome 5 Free";
    margin-right: 0.5rem;
    font-weight: 900;
}

.text-note .card-header::before {
    content: "\f15c"; /* أيقونة المستند */
}

.code-note .card-header::before {
    content: "\f121"; /* أيقونة الكود */
}

.link-note .card-header::before {
    content: "\f0c1"; /* أيقونة الرابط */
}

/* تنسيقات الكود */
.code-wrapper {
    position: relative;
    background: #2d2d2d;
    border-radius: 6px;
    overflow: hidden;
}

.code-header {
    padding: 0.5rem 1rem;
    background: rgba(255,255,255,0.1);
}

.code-wrapper pre {
    margin: 0;
    padding: 1rem;
    max-height: 400px;
    overflow: auto;
}

.code-wrapper code {
    font-family: 'Fira Code', monospace;
    font-size: 0.9rem;
}

/* تنسيقات الروابط */
.link-wrapper {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 6px;
    text-align: center;
}

.link-wrapper .btn-link {
    color: #0dcaf0;
    font-weight: 500;
}

.link-description {
    color: #6c757d;
    font-size: 0.9rem;
}

/* تنسيقات أرقام الأسطر في الكود */
.line-numbers .line-numbers-rows {
    border-right: 2px solid rgba(255,255,255,0.2);
    padding-right: 0.5rem;
}

/* تنسيقات حالة التحديث */
.status-select:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

/* تأثيرات الانتقال */
.status-select {
    transition: all 0.3s ease;
}

/* تنسيق البادجات */
.lesson-badges {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.lesson-badges .badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
}

.lesson-badges .badge i {
    font-size: 0.875rem;
}

/* تنسيق شريط التنقل العلوي */
.navigation-bar {
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.navigation-bar .btn {
    min-width: 120px;
    transition: all 0.3s ease;
}

.navigation-bar .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* تنسيق عنوان الدرس */
.navigation-bar h4 {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 600px;
    margin: 0 auto;
}

/* تحسين المظهر على الشاشات الصغيرة */
@media (max-width: 768px) {
    .navigation-bar .row {
        flex-direction: column;
        gap: 1rem;
    }
    
    .navigation-bar .col-auto {
        width: 100%;
    }
    
    .navigation-bar .d-flex {
        justify-content: center;
    }
    
    .navigation-bar h4 {
        font-size: 1.1rem;
        margin: 10px 0;
    }
}
</style>
