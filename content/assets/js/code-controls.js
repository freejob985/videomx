/**
 * إدارة التحكم في عرض الكود
 * يتضمن وظائف تكبير وتصغير الخط وعرض الكود في وضع ملء الشاشة
 */
class CodeControls {
    constructor() {
        this.modal = new bootstrap.Modal(document.getElementById('codeModal'));
        this.currentFontSize = 14;
        this.initializeControls();
        this.setupRelatedLessonsToggle();
        this.setupModalClose();
    }

    initializeControls() {
        // إضافة أزرار التحكم لكل كتلة كود
        document.querySelectorAll('.code-wrapper').forEach(wrapper => {
            if (!wrapper.querySelector('.code-controls')) {
                const controls = this.createCodeControls();
                wrapper.appendChild(controls);
            }
        });

        // تفعيل وظائف الأزرار
        this.initializeFullscreenButtons();
        this.initializeCopyButtons();
        this.initializeFontSizeControls();
    }

    createCodeControls() {
        const controls = document.createElement('div');
        controls.className = 'code-controls';
        
        // زر النسخ
        const copyBtn = document.createElement('button');
        copyBtn.className = 'copy-code';
        copyBtn.innerHTML = '<i class="fas fa-copy"></i>';
        copyBtn.title = 'نسخ الكود';
        
        // زر ملء الشاشة
        const fullscreenBtn = document.createElement('button');
        fullscreenBtn.className = 'fullscreen-toggle';
        fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i>';
        fullscreenBtn.title = 'ملء الشاشة';
        
        controls.appendChild(copyBtn);
        controls.appendChild(fullscreenBtn);
        
        return controls;
    }

    initializeFullscreenButtons() {
        document.querySelectorAll('.fullscreen-toggle').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const wrapper = e.currentTarget.closest('.code-wrapper');
                if (wrapper) {
                    this.openCodeInModal(wrapper);
                }
            });
        });

        // إضافة مستمع لمفتاح ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const fullscreenWrapper = document.querySelector('.code-wrapper.fullscreen');
                if (fullscreenWrapper) {
                    const button = fullscreenWrapper.querySelector('.fullscreen-toggle');
                    this.toggleFullscreen(fullscreenWrapper, button);
                }
            }
        });
    }

    toggleFullscreen(wrapper, button) {
        const isFullscreen = wrapper.classList.contains('fullscreen');
        
        if (!isFullscreen) {
            // تفعيل وضع ملء الشاشة
            wrapper.classList.add('fullscreen');
            document.body.classList.add('fullscreen-active');
            button.innerHTML = '<i class="fas fa-compress"></i>';
            button.title = 'إغلاق ملء الشاشة';
        } else {
            // إلغاء وضع ملء الشاشة
            wrapper.classList.remove('fullscreen');
            document.body.classList.remove('fullscreen-active');
            button.innerHTML = '<i class="fas fa-expand"></i>';
            button.title = 'ملء الشاشة';
        }
    }

    initializeCopyButtons() {
        document.querySelectorAll('.copy-code').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const wrapper = e.currentTarget.closest('.code-wrapper');
                if (wrapper) {
                    const code = wrapper.querySelector('code').textContent;
                    this.copyToClipboard(code, wrapper);
                }
            });
        });
    }

    copyToClipboard(text, wrapper) {
        navigator.clipboard.writeText(text).then(() => {
            this.showCopyFeedback(wrapper);
        }).catch(err => {
            console.error('فشل نسخ النص:', err);
        });
    }

    showCopyFeedback(wrapper) {
        // إزالة أي رسائل تأكيد سابقة
        const existingFeedback = wrapper.querySelector('.copy-feedback');
        if (existingFeedback) {
            existingFeedback.remove();
        }

        // إنشاء رسالة تأكيد جديدة
        const feedback = document.createElement('div');
        feedback.className = 'copy-feedback';
        feedback.textContent = 'تم النسخ!';
        wrapper.appendChild(feedback);

        // إظهار الرسالة
        setTimeout(() => {
            feedback.classList.add('show');
        }, 50);

        // إخفاء الرسالة بعد ثانيتين
        setTimeout(() => {
            feedback.classList.remove('show');
            setTimeout(() => {
                feedback.remove();
            }, 200);
        }, 2000);
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

    openCodeInModal(wrapper) {
        const modal = document.getElementById('codeModal');
        const codeElement = wrapper.querySelector('code');
        const noteTitle = wrapper.closest('.note-card').querySelector('.card-header h5').textContent;

        // تحديث عنوان الموديول
        modal.querySelector('.modal-title').textContent = noteTitle;

        // نسخ محتوى الكود ولغة البرمجة
        const modalCode = modal.querySelector('code');
        modalCode.className = codeElement.className;
        modalCode.textContent = codeElement.textContent;

        // إعادة تطبيق تنسيق Prism
        Prism.highlightElement(modalCode);

        // فتح الموديول
        this.modal.show();
    }

    initializeFontSizeControls() {
        const modal = document.getElementById('codeModal');
        
        // زر تكبير الخط
        modal.querySelector('.font-size-increase').addEventListener('click', () => {
            if (this.currentFontSize < 24) {
                this.currentFontSize += 2;
                this.updateFontSize();
            }
        });

        // زر تصغير الخط
        modal.querySelector('.font-size-decrease').addEventListener('click', () => {
            if (this.currentFontSize > 12) {
                this.currentFontSize -= 2;
                this.updateFontSize();
            }
        });
    }

    updateFontSize() {
        const modal = document.getElementById('codeModal');
        const codeElement = modal.querySelector('code');
        const fontSizeDisplay = modal.querySelector('.font-size-display');
        
        codeElement.style.fontSize = `${this.currentFontSize}px`;
        fontSizeDisplay.textContent = `${this.currentFontSize}px`;
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