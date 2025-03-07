/**
 * إدارة التحكم في عرض الكود
 * يتضمن وظائف تكبير وتصغير الخط وعرض الكود في وضع ملء الشاشة
 */
class CodeControls {
    constructor() {
        this.initializeControls();
        this.setupRelatedLessonsToggle();
    }

    initializeControls() {
        // إعداد أزرار التحكم في الكود
        document.querySelectorAll('.code-wrapper').forEach(wrapper => {
            const controls = wrapper.querySelector('.code-controls');
            if (controls) {
                const increaseBtn = controls.querySelector('.font-size-increase');
                const decreaseBtn = controls.querySelector('.font-size-decrease');
                const fullscreenBtn = controls.querySelector('.fullscreen-toggle');
                const codeElement = wrapper.querySelector('code');

                let fontSize = 14;

                increaseBtn.addEventListener('click', () => {
                    if (fontSize < 24) {
                        fontSize += 2;
                        codeElement.style.fontSize = `${fontSize}px`;
                    }
                });

                decreaseBtn.addEventListener('click', () => {
                    if (fontSize > 12) {
                        fontSize -= 2;
                        codeElement.style.fontSize = `${fontSize}px`;
                    }
                });

                fullscreenBtn.addEventListener('click', () => {
                    this.openFullscreen(wrapper);
                });
            }
        });
    }

    openFullscreen(wrapper) {
        const modal = document.createElement('div');
        modal.className = 'code-modal';
        modal.innerHTML = `
            <div class="code-modal-content">
                <div class="code-modal-header">
                    <h5 class="code-modal-title">عرض الكود</h5>
                    <button type="button" class="code-modal-close">&times;</button>
                </div>
                <div class="code-modal-body">
                    <pre><code class="${wrapper.querySelector('code').className}">${wrapper.querySelector('code').innerHTML}</code></pre>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        modal.querySelector('.code-modal-close').addEventListener('click', () => {
            modal.remove();
        });

        // تفعيل تنسيق الكود
        Prism.highlightElement(modal.querySelector('code'));
    }

    setupRelatedLessonsToggle() {
        const toggleBtn = document.querySelector('.toggle-related-lessons');
        const content = document.querySelector('.related-lessons-content');
        
        if (toggleBtn && content) {
            // استرجاع الحالة المحفوظة
            const isCollapsed = localStorage.getItem('relatedLessonsCollapsed') === 'true';
            
            // تطبيق الحالة المحفوظة
            if (isCollapsed) {
                content.classList.add('collapsed');
                toggleBtn.querySelector('i').classList.replace('fa-chevron-up', 'fa-chevron-down');
            }

            // إضافة مستمع الحدث
            toggleBtn.addEventListener('click', () => {
                content.classList.toggle('collapsed');
                const isNowCollapsed = content.classList.contains('collapsed');
                
                // تحديث الأيقونة
                const icon = toggleBtn.querySelector('i');
                icon.classList.replace(
                    isNowCollapsed ? 'fa-chevron-up' : 'fa-chevron-down',
                    isNowCollapsed ? 'fa-chevron-down' : 'fa-chevron-up'
                );
                
                // حفظ الحالة
                localStorage.setItem('relatedLessonsCollapsed', isNowCollapsed);
            });
        }
    }
}

// تهيئة عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    new CodeControls();
}); 