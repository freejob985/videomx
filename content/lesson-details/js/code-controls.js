document.addEventListener('DOMContentLoaded', function() {
    initializeCodeControls();
    initializeModals();
    initializeNoteForm();
});

function initializeCodeControls() {
    document.querySelectorAll('.code-wrapper').forEach(wrapper => {
        const codeBlock = wrapper.querySelector('pre code');
        const fontDisplay = wrapper.querySelector('.font-size-display');
        const decreaseBtn = wrapper.querySelector('.font-size-decrease');
        const increaseBtn = wrapper.querySelector('.font-size-increase');
        const fullscreenBtn = wrapper.querySelector('.fullscreen-toggle');
        const copyBtn = wrapper.querySelector('.copy-code');

        if (!codeBlock || !fontDisplay) return;

        // تعيين الحجم الأولي
        const initialSize = parseInt(localStorage.getItem('codeFontSize')) || 14;
        updateFontSize(codeBlock, fontDisplay, initialSize);

        // زر تصغير الخط
        if (decreaseBtn) {
            decreaseBtn.addEventListener('click', () => {
                const currentSize = parseInt(getComputedStyle(codeBlock).fontSize);
                if (currentSize > 10) {
                    updateFontSize(codeBlock, fontDisplay, currentSize - 2);
                }
            });
        }

        // زر تكبير الخط
        if (increaseBtn) {
            increaseBtn.addEventListener('click', () => {
                const currentSize = parseInt(getComputedStyle(codeBlock).fontSize);
                if (currentSize < 24) {
                    updateFontSize(codeBlock, fontDisplay, currentSize + 2);
                }
            });
        }

        // زر النسخ
        if (copyBtn) {
            copyBtn.addEventListener('click', () => {
                const code = codeBlock.textContent;
                navigator.clipboard.writeText(code).then(() => {
                    toastr.success('تم نسخ الكود بنجاح');
                });
            });
        }

        // زر ملء الشاشة
        if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', () => {
                toggleFullscreen(wrapper);
            });
        }
    });
}

function updateFontSize(codeBlock, display, newSize) {
    // تطبيق الحجم الجديد على عنصر الكود
    codeBlock.style.fontSize = `${newSize}px`;
    
    // تحديث عرض الحجم في الواجهة
    display.textContent = `${newSize}px`;
    
    // حفظ الحجم في التخزين المحلي
    localStorage.setItem('codeFontSize', newSize);

    // تحديث حجم الخط في النافذة المنفصلة إذا كانت مفتوحة
    const popup = document.querySelector('.code-popup.active');
    if (popup) {
        const popupCode = popup.querySelector('pre code');
        if (popupCode) {
            popupCode.style.fontSize = `${newSize}px`;
        }
    }
}

function copyCode(wrapper) {
    const code = wrapper.querySelector('pre code').textContent;
    navigator.clipboard.writeText(code).then(() => {
        toastr.success('تم نسخ الكود بنجاح');
    });
}

function openInNewWindow(wrapper) {
    // إنشاء عنصر النافذة المنفصلة إذا لم يكن موجوداً
    let popup = document.querySelector('.code-popup');
    if (!popup) {
        popup = document.createElement('div');
        popup.className = 'code-popup';
        document.body.appendChild(popup);
    }

    // الحصول على معلومات الكود
    const code = wrapper.querySelector('pre code').textContent;
    const language = wrapper.querySelector('pre code').className.split('-')[1] || 'plaintext';
    const title = wrapper.closest('.note-card')?.querySelector('.card-header h5')?.textContent || 'Code View';

    // تحديث محتوى النافذة المنفصلة
    popup.innerHTML = `
        <div class="code-popup-header">
            <h3 class="code-popup-title">${title}</h3>
            <div class="code-popup-controls">
                <div class="control-group">
                    <button type="button" class="code-btn font-size-decrease" title="تصغير الخط">
                        <i class="fas fa-minus"></i>
                    </button>
                    <span class="font-size-display">18px</span>
                    <button type="button" class="code-btn font-size-increase" title="تكبير الخط">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div class="control-group">
                    <button type="button" class="code-btn copy-code" title="نسخ الكود">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button type="button" class="code-btn close-popup" title="إغلاق">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="code-popup-content">
            <pre><code class="language-${language}">${code}</code></pre>
        </div>
    `;

    // تفعيل تمييز الكود
    if (window.Prism) {
        Prism.highlightElement(popup.querySelector('code'));
    }

    // إضافة الأحداث
    const popupControls = {
        fontSize: 18,
        codeElement: popup.querySelector('code'),
        fontDisplay: popup.querySelector('.font-size-display'),
        
        updateFontSize(delta) {
            this.fontSize = Math.min(Math.max(this.fontSize + delta, 12), 32);
            this.codeElement.style.fontSize = `${this.fontSize}px`;
            this.fontDisplay.textContent = `${this.fontSize}px`;
        }
    };

    // أزرار تغيير حجم الخط
    popup.querySelector('.font-size-decrease').addEventListener('click', () => {
        popupControls.updateFontSize(-2);
    });

    popup.querySelector('.font-size-increase').addEventListener('click', () => {
        popupControls.updateFontSize(2);
    });

    // زر النسخ
    popup.querySelector('.copy-code').addEventListener('click', () => {
        navigator.clipboard.writeText(code).then(() => {
            toastr.success('تم نسخ الكود بنجاح');
        });
    });

    // زر الإغلاق
    popup.querySelector('.close-popup').addEventListener('click', () => {
        popup.classList.remove('active');
    });

    // مفتاح ESC للإغلاق
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && popup.classList.contains('active')) {
            popup.classList.remove('active');
        }
    });

    // عرض النافذة
    popup.classList.add('active');
    document.body.style.overflow = 'hidden'; // منع التمرير في الصفحة الرئيسية
}

function toggleFullscreen(wrapper) {
    if (!document.fullscreenElement) {
        wrapper.requestFullscreen().then(() => {
            wrapper.classList.add('fullscreen');
            wrapper.querySelector('.fullscreen-toggle i').classList.replace('fa-expand', 'fa-compress');
            
            // تعديل حجم الكود ليملأ الشاشة
            const codeContent = wrapper.querySelector('.code-content');
            if (codeContent) {
                codeContent.style.height = '100vh';
                codeContent.style.maxHeight = '100vh';
            }
        });
    } else {
        document.exitFullscreen().then(() => {
            wrapper.classList.remove('fullscreen');
            wrapper.querySelector('.fullscreen-toggle i').classList.replace('fa-compress', 'fa-expand');
            
            // إعادة الحجم الطبيعي
            const codeContent = wrapper.querySelector('.code-content');
            if (codeContent) {
                codeContent.style.height = '';
                codeContent.style.maxHeight = '';
            }
        });
    }
}

function initializeModals() {
    // إضافة مستمع لمفتاح ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const fullscreenWrapper = document.querySelector('.code-wrapper.fullscreen');
            if (fullscreenWrapper) {
                toggleFullscreen(fullscreenWrapper);
                return;
            }

            const modal = document.getElementById('codeModal');
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        }
    });
}

function handleNoteTypeChange() {
    const noteType = document.getElementById('noteType');
    const formWrapper = document.querySelector('.note-form-wrapper');
    const codeOptions = document.querySelector('.code-options');
    const linkOptions = document.querySelector('.link-options');
    
    noteType.addEventListener('change', function() {
        // إزالة جميع الأصناف السابقة
        formWrapper.classList.remove('text-type', 'code-type', 'link-type');
        
        // إخفاء جميع الخيارات الإضافية
        if (codeOptions) codeOptions.style.display = 'none';
        if (linkOptions) linkOptions.style.display.display = 'none';
        
        // إضافة الصنف الجديد وإظهار الخيارات المناسبة
        switch(this.value) {
            case 'code':
                formWrapper.classList.add('code-type');
                if (codeOptions) codeOptions.style.display = 'block';
                break;
            case 'link':
                formWrapper.classList.add('link-type');
                if (linkOptions) linkOptions.style.display = 'block';
                break;
            default:
                formWrapper.classList.add('text-type');
        }
    });
}

function initializeNoteForm() {
    const noteForm = document.getElementById('addNoteForm');
    const noteType = document.getElementById('noteType');
    
    if (!noteForm || !noteType) return;

    // تهيئة النوع الأولي
    updateNoteFormType(noteType.value);

    // مراقبة تغيير النوع
    noteType.addEventListener('change', function() {
        updateNoteFormType(this.value);
    });

    // معالجة تقديم النموذج
  //  noteForm.addEventListener('submit', handleNoteSubmit);
}

// تحديث نوع النموذج
function updateNoteFormType(type) {
    const formWrapper = document.querySelector('.note-form-wrapper');
    const codeOptions = document.querySelector('.code-options');
    const linkOptions = document.querySelector('.link-options');
    
    // إزالة جميع الأصناف
    formWrapper.classList.remove('text-type', 'code-type', 'link-type');
    formWrapper.classList.add(`${type}-type`);
    
    // إخفاء جميع الخيارات
    [codeOptions, linkOptions].forEach(el => {
        if (el) el.style.display = 'none';
    });
    
    // إظهار الخيارات المناسبة
    switch(type) {
        case 'code':
            codeOptions.style.display = 'block';
            break;
        case 'link':
            linkOptions.style.display = 'block';
            break;
    }
}

// معالجة إرسال النموذج
async function handleNoteSubmit(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(this);
        const noteType = formData.get('type');

        // التحقق من البيانات
        if (noteType === 'code' && !formData.get('code_language')) {
            throw new Error('الرجاء اختيار لغة البرمجة');
        }
        if (noteType === 'link' && !formData.get('link_url')) {
            throw new Error('الرجاء إدخال رابط صحيح');
        }

        // إرسال البيانات
        const response = await fetch('/api/notes/add', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (response.ok) {
            // عرض رسالة نجاح
            toastr.success('تم إضافة الملاحظة بنجاح');
            
            // تحديث قائمة الملاحظات
            updateNotesList(data.note);
            
            // إعادة تعيين النموذج
            this.reset();
            updateNoteFormType('text');
        } else {
            throw new Error(data.message || 'فشل في إضافة الملاحظة');
        }
    } catch (error) {
        console.error('Error:', error);
        toastr.error(error.message || 'حدث خطأ أثناء إضافة الملاحظة');
    }
}

// تحديث قائمة الملاحظات
function updateNotesList(newNote) {
    const notesList = document.getElementById('notesList');
    if (!notesList) return;

    // إنشاء عنصر الملاحظة الجديدة
    const noteElement = createNoteElement(newNote);
    
    // إضافة الملاحظة في بداية القائمة
    notesList.insertBefore(noteElement, notesList.firstChild);
    
    // تهيئة عناصر التحكم للملاحظة الجديدة
    if (newNote.type === 'code') {
        initializeCodeControls();
    }
}

class CodeControls {
    constructor(wrapper) {
        this.wrapper = wrapper;
        this.codeBlock = wrapper.querySelector('pre code');
        this.fontSize = 14;
        this.initControls();
        this.initPopupWindow();
    }

    initControls() {
        const controls = document.createElement('div');
        controls.className = 'code-controls';
        
        // مجموعة التحكم في حجم الخط
        const fontSizeGroup = document.createElement('div');
        fontSizeGroup.className = 'control-group';
        fontSizeGroup.innerHTML = `
            <button type="button" class="font-size-decrease" title="تصغير الخط">
                <i class="fas fa-minus"></i>
            </button>
            <span class="font-size-display">14px</span>
            <button type="button" class="font-size-increase" title="تكبير الخط">
                <i class="fas fa-plus"></i>
            </button>
        `;

        // مجموعة الأدوات
        const toolsGroup = document.createElement('div');
        toolsGroup.className = 'control-group';
        toolsGroup.innerHTML = `
            <button type="button" class="copy-code" title="نسخ الكود">
                <i class="fas fa-copy"></i>
            </button>
            <button type="button" class="open-popup" title="فتح في نافذة منفصلة">
                <i class="fas fa-external-link-alt"></i>
            </button>
            <button type="button" class="fullscreen-toggle" title="ملء الشاشة">
                <i class="fas fa-expand"></i>
            </button>
        `;

        controls.appendChild(fontSizeGroup);
        controls.appendChild(toolsGroup);
        this.wrapper.insertBefore(controls, this.wrapper.firstChild);

        this.bindEvents();
    }

    initPopupWindow() {
        this.popup = document.createElement('div');
        this.popup.className = 'code-popup-window';
        this.popup.innerHTML = `
            <div class="popup-header">
                <div class="control-group">
                    <button type="button" class="font-size-decrease">
                        <i class="fas fa-minus"></i>
                    </button>
                    <span class="font-size-display">14px</span>
                    <button type="button" class="font-size-increase">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div class="control-group">
                    <button type="button" class="copy-code">
                        <i class="fas fa-copy"></i>
                        نسخ
                    </button>
                    <button type="button" class="close-popup">
                        <i class="fas fa-times"></i>
                        إغلاق
                    </button>
                </div>
            </div>
            <div class="popup-content"></div>
        `;
        document.body.appendChild(this.popup);
    }

    bindEvents() {
        // التحكم في حجم الخط
        this.wrapper.querySelector('.font-size-decrease').addEventListener('click', () => this.changeFontSize(-1));
        this.wrapper.querySelector('.font-size-increase').addEventListener('click', () => this.changeFontSize(1));

        // نسخ الكود
        this.wrapper.querySelector('.copy-code').addEventListener('click', () => this.copyCode());

        // فتح في نافذة منفصلة
        this.wrapper.querySelector('.open-popup').addEventListener('click', () => this.openPopup());

        // ملء الشاشة
        this.wrapper.querySelector('.fullscreen-toggle').addEventListener('click', () => this.toggleFullscreen());

        // أحداث النافذة المنفصلة
        this.popup.querySelector('.close-popup').addEventListener('click', () => this.closePopup());
        this.popup.querySelector('.copy-code').addEventListener('click', () => this.copyCode(true));
        this.popup.querySelector('.font-size-decrease').addEventListener('click', () => this.changeFontSize(-1, true));
        this.popup.querySelector('.font-size-increase').addEventListener('click', () => this.changeFontSize(1, true));
    }

    changeFontSize(delta, inPopup = false) {
        this.fontSize = Math.max(10, Math.min(24, this.fontSize + delta));
        const target = inPopup ? this.popup : this.wrapper;
        
        target.querySelector('.font-size-display').textContent = `${this.fontSize}px`;
        target.querySelector('pre code').style.fontSize = `${this.fontSize}px`;
    }

    copyCode(inPopup = false) {
        const code = this.codeBlock.textContent;
        navigator.clipboard.writeText(code).then(() => {
            toastr.success('تم نسخ الكود بنجاح');
        });
    }

    openPopup() {
        const popupContent = this.popup.querySelector('.popup-content');
        popupContent.innerHTML = '';
        const clonedCode = this.wrapper.querySelector('pre').cloneNode(true);
        popupContent.appendChild(clonedCode);
        
        this.popup.classList.add('active');
        this.updatePopupSize();
    }

    closePopup() {
        this.popup.classList.remove('active');
    }

    toggleFullscreen() {
        if (!document.fullscreenElement) {
            this.wrapper.requestFullscreen();
            this.wrapper.querySelector('.fullscreen-toggle i').classList.replace('fa-expand', 'fa-compress');
        } else {
            document.exitFullscreen();
            this.wrapper.querySelector('.fullscreen-toggle i').classList.replace('fa-compress', 'fa-expand');
        }
    }

    updatePopupSize() {
        const popupContent = this.popup.querySelector('.popup-content pre code');
        if (popupContent) {
            popupContent.style.fontSize = `${this.fontSize}px`;
        }
    }
}

// تهيئة عناصر التحكم لكل بلوك كود
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.code-wrapper').forEach(wrapper => {
        new CodeControls(wrapper);
    });
});

/**
 * Font Size Control Module
 * Handles increasing and decreasing font size for code blocks
 */
const FontSizeControl = {
    // Default font size in pixels
    DEFAULT_SIZE: 14,
    // Minimum font size allowed
    MIN_SIZE: 10,
    // Maximum font size allowed
    MAX_SIZE: 24,
    // Step size for font size changes
    STEP_SIZE: 2,

    /**
     * Initialize font size controls for a code wrapper
     * @param {HTMLElement} wrapper - The code wrapper element
     */
    init(wrapper) {
        const decreaseBtn = wrapper.querySelector('.font-size-decrease');
        const increaseBtn = wrapper.querySelector('.font-size-increase');
        const display = wrapper.querySelector('.font-size-display');
        const codeBlock = wrapper.querySelector('.code-block');

        // Set initial font size
        if (codeBlock) {
            codeBlock.style.fontSize = `${this.DEFAULT_SIZE}px`;
            if (display) {
                display.textContent = `${this.DEFAULT_SIZE}px`;
            }
        }

        // Decrease font size handler
        if (decreaseBtn) {
            decreaseBtn.addEventListener('click', () => {
                this.changeFontSize(wrapper, 'decrease');
            });
        }

        // Increase font size handler
        if (increaseBtn) {
            increaseBtn.addEventListener('click', () => {
                this.changeFontSize(wrapper, 'increase');
            });
        }
    },

    /**
     * Change font size of code block
     * @param {HTMLElement} wrapper - The code wrapper element
     * @param {string} action - Either 'increase' or 'decrease'
     */
    changeFontSize(wrapper, action) {
        const codeBlock = wrapper.querySelector('.code-block');
        const display = wrapper.querySelector('.font-size-display');
        
        if (!codeBlock) return;

        // Get current font size
        const currentSize = parseInt(window.getComputedStyle(codeBlock).fontSize);
        let newSize = currentSize;

        // Calculate new size based on action
        if (action === 'increase' && currentSize < this.MAX_SIZE) {
            newSize = currentSize + this.STEP_SIZE;
        } else if (action === 'decrease' && currentSize > this.MIN_SIZE) {
            newSize = currentSize - this.STEP_SIZE;
        }

        // Apply new font size
        codeBlock.style.fontSize = `${newSize}px`;
        
        // Update display
        if (display) {
            display.textContent = `${newSize}px`;
        }

        // Save preference to localStorage
        this.saveFontSizePreference(newSize);
    },

    /**
     * Save font size preference to localStorage
     * @param {number} size - Font size in pixels
     */
    saveFontSizePreference(size) {
        try {
            localStorage.setItem('codeFontSize', size.toString());
        } catch (e) {
            console.warn('Could not save font size preference:', e);
        }
    },

    /**
     * Get saved font size preference
     * @returns {number} Font size in pixels
     */
    getSavedFontSize() {
        try {
            const saved = localStorage.getItem('codeFontSize');
            return saved ? parseInt(saved) : this.DEFAULT_SIZE;
        } catch (e) {
            console.warn('Could not get saved font size:', e);
            return this.DEFAULT_SIZE;
        }
    }
};

/**
 * Initialize all code controls when document is ready
 */
document.addEventListener('DOMContentLoaded', () => {
    // Initialize existing code wrappers
    document.querySelectorAll('.code-wrapper').forEach(wrapper => {
        FontSizeControl.init(wrapper);
    });

    // Initialize code controls for dynamically added code wrappers
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === 1 && node.classList.contains('code-wrapper')) {
                    FontSizeControl.init(node);
                }
            });
        });
    });

    // Start observing the document for added code wrappers
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}); 