document.addEventListener('DOMContentLoaded', function() {
    // تهيئة حالة هيدر الدروس
    initializeSectionState('lessons-header');
});

// دالة تهيئة حالة القسم
function initializeSectionState(sectionName) {
    const toggleBtn = document.querySelector(`[data-section="${sectionName}"]`);
    const content = document.querySelector(`[data-section-content="${sectionName}"]`);
    
    if (toggleBtn && content) {
        // استرجاع الحالة المحفوظة
        const isCollapsed = localStorage.getItem(`section_${sectionName}_collapsed`) === 'true';
        
        // تطبيق الحالة المحفوظة
        if (isCollapsed) {
            content.classList.add('collapsed');
            toggleBtn.querySelector('i').classList.replace('fa-chevron-up', 'fa-chevron-down');
            toggleBtn.setAttribute('title', 'إظهار قائمة الدروس');
        }

        // إضافة معالج النقر
        toggleBtn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            content.classList.toggle('collapsed');
            
            // تحديث الأيقونة
            if (content.classList.contains('collapsed')) {
                icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                this.setAttribute('title', 'إظهار قائمة الدروس');
            } else {
                icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
                this.setAttribute('title', 'إخفاء قائمة الدروس');
            }
            
            // حفظ الحالة في localStorage
            localStorage.setItem(
                `section_${sectionName}_collapsed`, 
                content.classList.contains('collapsed')
            );
        });
    }
} 