/**
 * إدارة التحكم في عرض الكود
 * يتضمن وظائف تكبير وتصغير الخط وعرض الكود في وضع ملء الشاشة
 */
class CodeControls {
    constructor() {
        this.initializeModals();
        this.setupEventListeners();
        this.currentFontSize = 14;
    }

    /**
     * تهيئة النوافذ المنبثقة
     */
    initializeModals() {
        // إضافة modal للكود إلى الصفحة
        const modalHTML = `
            <div class="code-modal">
                <div class="code-modal-content">
                    <div class="code-modal-header">
                        <h5 class="code-modal-title"></h5>
                        <button type="button" class="code-modal-close">&times;</button>
                    </div>
                    <div class="code-modal-body">
                        <pre><code></code></pre>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    /**
     * إعداد مستمعي الأحداث
     */
    setupEventListeners() {
        // إضافة أزرار التحكم لكل كتلة كود
        document.querySelectorAll('.code-wrapper').forEach(wrapper => {
            if (!wrapper.querySelector('.code-controls')) {
                const controls = this.createControls();
                wrapper.insertBefore(controls, wrapper.firstChild);
                
                // إعداد مستمعي الأحداث للأزرار
                const btns = controls.querySelectorAll('button');
                btns[0].addEventListener('click', () => this.increaseFontSize(wrapper));
                btns[1].addEventListener('click', () => this.decreaseFontSize(wrapper));
                btns[2].addEventListener('click', () => this.openFullscreen(wrapper));
            }
        });

        // مستمع لإغلاق النافذة المنبثقة
        document.querySelector('.code-modal-close').addEventListener('click', () => {
            document.querySelector('.code-modal').classList.remove('active');
        });
    }

    /**
     * إنشاء أزرار التحكم
     */
    createControls() {
        const controls = document.createElement('div');
        controls.className = 'code-controls';
        controls.innerHTML = `
            <button type="button" title="تكبير الخط">
                <i class="fas fa-plus"></i>
            </button>
            <button type="button" title="تصغير الخط">
                <i class="fas fa-minus"></i>
            </button>
            <button type="button" title="عرض بملء الشاشة">
                <i class="fas fa-expand"></i>
            </button>
        `;
        return controls;
    }

    /**
     * زيادة حجم الخط
     */
    increaseFontSize(wrapper) {
        if (this.currentFontSize < 24) {
            this.currentFontSize += 2;
            wrapper.querySelector('code').style.fontSize = `${this.currentFontSize}px`;
        }
    }

    /**
     * تقليل حجم الخط
     */
    decreaseFontSize(wrapper) {
        if (this.currentFontSize > 12) {
            this.currentFontSize -= 2;
            wrapper.querySelector('code').style.fontSize = `${this.currentFontSize}px`;
        }
    }

    /**
     * فتح الكود في وضع ملء الشاشة
     */
    openFullscreen(wrapper) {
        const modal = document.querySelector('.code-modal');
        const modalTitle = modal.querySelector('.code-modal-title');
        const modalCode = modal.querySelector('code');
        
        // نسخ محتوى الكود والعنوان
        modalTitle.textContent = wrapper.closest('.note-card').querySelector('.card-header h5').textContent;
        modalCode.className = wrapper.querySelector('code').className;
        modalCode.textContent = wrapper.querySelector('code').textContent;
        
        // تفعيل تنسيق الكود
        Prism.highlightElement(modalCode);
        
        // عرض النافذة
        modal.classList.add('active');
    }
}

// تهيئة التحكم في الكود عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    new CodeControls();
}); 