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
        // تهيئة أزرار التحكم في حجم الخط لكل كتلة كود
        document.querySelectorAll('.code-wrapper').forEach(wrapper => {
            this.initializeSectionFontControls(wrapper);
        });

        // تهيئة أزرار التحكم في الموديول
        const modal = document.getElementById('codeModal');
        if (modal) {
            this.initializeModalFontControls(modal);
        }

        // تفعيل وظائف الأزرار الأخرى
        this.initializeFullscreenButtons();
        this.initializeCopyButtons();
    }

    initializeSectionFontControls(wrapper) {
        const increaseBtn = wrapper.querySelector('.font-size-increase');
        const decreaseBtn = wrapper.querySelector('.font-size-decrease');
        const codeElement = wrapper.querySelector('pre code');

        if (increaseBtn && codeElement) {
            increaseBtn.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (this.currentFontSize < 24) {
                    this.currentFontSize += 2;
                    this.updateAllCodeFontSizes();
                }
            };
        }

        if (decreaseBtn && codeElement) {
            decreaseBtn.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (this.currentFontSize > 12) {
                    this.currentFontSize -= 2;
                    this.updateAllCodeFontSizes();
                }
            };
        }

        // تعيين الحجم الأولي
        if (codeElement) {
            codeElement.style.fontSize = `${this.currentFontSize}px`;
        }
    }

    initializeModalFontControls(modal) {
        const increaseBtn = modal.querySelector('.font-size-increase');
        const decreaseBtn = modal.querySelector('.font-size-decrease');
        const fontSizeDisplay = modal.querySelector('.font-size-display');
        const codeElement = modal.querySelector('pre code');

        if (increaseBtn) {
            increaseBtn.onclick = (e) => {
                e.preventDefault();
                if (this.currentFontSize < 24) {
                    this.currentFontSize += 2;
                    this.updateAllCodeFontSizes();
                }
            };
        }

        if (decreaseBtn) {
            decreaseBtn.onclick = (e) => {
                e.preventDefault();
                if (this.currentFontSize > 12) {
                    this.currentFontSize -= 2;
                    this.updateAllCodeFontSizes();
                }
            };
        }

        // تحديث العرض الأولي
        this.updateFontSize(codeElement, fontSizeDisplay);
    }

    updateAllCodeFontSizes() {
        // تحديث حجم الخط في جميع أقسام الكود
        document.querySelectorAll('.code-wrapper pre code').forEach(codeElement => {
            this.updateFontSize(codeElement);
        });

        // تحديث حجم الخط في الموديول إذا كان مفتوحاً
        const modal = document.getElementById('codeModal');
        if (modal) {
            const modalCode = modal.querySelector('pre code');
            const fontSizeDisplay = modal.querySelector('.font-size-display');
            this.updateFontSize(modalCode, fontSizeDisplay);
        }
    }

    updateFontSize(codeElement, fontSizeDisplay = null) {
        if (codeElement) {
            codeElement.style.fontSize = `${this.currentFontSize}px`;
        }
        if (fontSizeDisplay) {
            fontSizeDisplay.textContent = `${this.currentFontSize}px`;
        }
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
        const noteTitle = wrapper.closest('.note-card')?.querySelector('.card-header h5')?.textContent || 'عرض الكود';

        // تحديث عنوان الموديول
        modal.querySelector('.modal-title').textContent = noteTitle;

        // نسخ محتوى الكود ولغة البرمجة
        const modalCode = modal.querySelector('code');
        modalCode.className = codeElement.className;
        modalCode.innerHTML = codeElement.innerHTML;
        modalCode.style.fontSize = `${this.currentFontSize}px`;

        // إعادة تطبيق تنسيق Prism
        Prism.highlightElement(modalCode);

        // فتح الموديول
        this.modal.show();
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