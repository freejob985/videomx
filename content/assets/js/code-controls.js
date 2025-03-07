/**
 * إدارة التحكم في عرض الكود
 * يتضمن وظائف تكبير وتصغير الخط وعرض الكود في وضع ملء الشاشة
 */
class CodeControls {
    constructor() {
        this.initializeControls();
        this.setupRelatedLessonsToggle();
        this.setupModalClose();
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

                increaseBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (fontSize < 24) {
                        fontSize += 2;
                        codeElement.style.fontSize = `${fontSize}px`;
                    }
                });

                decreaseBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (fontSize > 12) {
                        fontSize -= 2;
                        codeElement.style.fontSize = `${fontSize}px`;
                    }
                });

                fullscreenBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.openFullscreen(wrapper);
                });
            }
        });
    }

    setupModalClose() {
        // إغلاق الموديول عند النقر خارجه
        document.addEventListener('click', (e) => {
            const modal = document.querySelector('.code-modal');
            if (modal && e.target.classList.contains('code-modal')) {
                modal.remove();
            }
        });

        // إغلاق الموديول بضغط ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modal = document.querySelector('.code-modal');
                if (modal) modal.remove();
            }
        });
    }

    openFullscreen(wrapper) {
        // إزالة أي موديول موجود مسبقاً
        const existingModal = document.querySelector('.code-modal');
        if (existingModal) existingModal.remove();

        const codeElement = wrapper.querySelector('code');
        const noteTitle = wrapper.closest('.note-card').querySelector('.card-header h5').textContent;
        
        const modal = document.createElement('div');
        modal.className = 'code-modal';
        modal.innerHTML = `
            <div class="code-modal-content">
                <div class="code-modal-header">
                    <h5 class="code-modal-title">${noteTitle}</h5>
                    <div class="code-modal-controls">
                        <!-- البحث -->
                        <div class="search-wrapper">
                            <input type="text" class="search-input" placeholder="بحث في الكود...">
                            <span class="search-count"></span>
                        </div>
                        
                        <!-- التحكم في حجم الخط -->
                        <div class="code-controls-group">
                            <button type="button" class="font-size-decrease" title="تصغير الخط">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="font-size-display">14px</span>
                            <button type="button" class="font-size-increase" title="تكبير الخط">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        
                        <button type="button" class="code-modal-close" title="إغلاق">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="code-modal-body">
                    <pre><code class="${codeElement.className}">${codeElement.innerHTML}</code></pre>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        
        // تفعيل تنسيق الكود
        Prism.highlightElement(modal.querySelector('code'));

        // إعداد أزرار التحكم في الموديول
        this.setupModalControls(modal);

        // جعل الموديول مرئي
        requestAnimationFrame(() => {
            modal.classList.add('active');
        });
    }

    setupModalControls(modal) {
        const modalCode = modal.querySelector('code');
        let fontSize = 14;

        // إغلاق الموديول
        modal.querySelector('.code-modal-close').addEventListener('click', () => {
            modal.remove();
        });

        // تكبير الخط
        modal.querySelector('.font-size-increase').addEventListener('click', () => {
            if (fontSize < 24) {
                fontSize += 2;
                this.updateModalFontSize(modal, fontSize);
            }
        });

        // تصغير الخط
        modal.querySelector('.font-size-decrease').addEventListener('click', () => {
            if (fontSize > 12) {
                fontSize -= 2;
                this.updateModalFontSize(modal, fontSize);
            }
        });

        // البحث في الكود
        const searchInput = modal.querySelector('.search-input');
        searchInput.addEventListener('input', () => {
            this.handleModalSearch(modal, searchInput.value);
        });
    }

    updateModalFontSize(modal, size) {
        const codeElement = modal.querySelector('code');
        const fontSizeDisplay = modal.querySelector('.font-size-display');
        
        codeElement.style.fontSize = `${size}px`;
        fontSizeDisplay.textContent = `${size}px`;
    }

    handleModalSearch(modal, query) {
        const codeElement = modal.querySelector('code');
        const searchCount = modal.querySelector('.search-count');
        
        // إزالة التمييز السابق
        codeElement.innerHTML = codeElement.innerHTML.replace(
            /<mark class="search-highlight[^>]*>([^<]*)<\/mark>/g,
            '$1'
        );

        if (!query) {
            searchCount.textContent = '';
            return;
        }

        const text = codeElement.textContent;
        const regex = new RegExp(query, 'gi');
        const matches = text.match(regex);
        
        if (matches) {
            searchCount.textContent = `${matches.length} نتيجة`;
            codeElement.innerHTML = text.replace(
                regex,
                match => `<mark class="search-highlight">${match}</mark>`
            );
        } else {
            searchCount.textContent = 'لا توجد نتائج';
        }
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