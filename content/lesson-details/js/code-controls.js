document.addEventListener('DOMContentLoaded', function() {
    initializeCodeControls();
    initializeModals();
    initializeNoteForm();
});

function initializeCodeControls() {
    document.querySelectorAll('.code-wrapper').forEach(wrapper => {
        const controls = wrapper.querySelector('.code-controls');
        if (!controls) return;

        // تهيئة الأحداث
        initializeControlEvents(wrapper);
        
        // تهيئة حجم الخط الأولي
        updateFontSize(wrapper, 14);
    });
}

function initializeControlEvents(wrapper) {
    // أزرار تغيير حجم الخط
    wrapper.querySelector('.font-size-decrease').addEventListener('click', () => changeFontSize(wrapper, -1));
    wrapper.querySelector('.font-size-increase').addEventListener('click', () => changeFontSize(wrapper, 1));
    
    // زر النسخ
    wrapper.querySelector('.copy-code').addEventListener('click', () => copyCode(wrapper));
    
    // زر فتح النافذة المنفصلة
    wrapper.querySelector('.open-popup').addEventListener('click', () => openInNewWindow(wrapper));
    
    // زر ملء الشاشة
    wrapper.querySelector('.fullscreen-toggle').addEventListener('click', () => toggleFullscreen(wrapper));
}

function changeFontSize(wrapper, delta) {
    const codeBlock = wrapper.querySelector('pre code');
    const display = wrapper.querySelector('.font-size-display');
    
    const currentSize = parseInt(window.getComputedStyle(codeBlock).fontSize);
    const newSize = Math.min(Math.max(currentSize + delta, 10), 24);
    
    codeBlock.style.fontSize = `${newSize}px`;
    display.textContent = `${newSize}px`;
}

function copyCode(wrapper) {
    const code = wrapper.querySelector('pre code').textContent;
    navigator.clipboard.writeText(code).then(() => {
        toastr.success('تم نسخ الكود بنجاح');
    });
}

function openInNewWindow(wrapper) {
    const code = wrapper.querySelector('pre code').textContent;
    const language = wrapper.querySelector('pre code').className.split('-')[1] || 'plaintext';
    
    const newWindow = window.open('', '_blank', 'width=800,height=600');
    newWindow.document.write(`
        <!DOCTYPE html>
        <html dir="ltr">
        <head>
            <title>Code View</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism-tomorrow.min.css">
            <style>
                body { margin: 0; padding: 20px; background: #1e1e1e; }
                pre { margin: 0; }
                code { font-family: 'Fira Code', monospace; font-size: 14px; }
            </style>
        </head>
        <body>
            <pre><code class="language-${language}">${code}</code></pre>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/prism.min.js"></script>
            <script>Prism.highlightAll();</script>
        </body>
        </html>
    `);
}

function toggleFullscreen(wrapper) {
    if (!document.fullscreenElement) {
        wrapper.requestFullscreen();
        wrapper.querySelector('.fullscreen-toggle i').classList.replace('fa-expand', 'fa-compress');
    } else {
        document.exitFullscreen();
        wrapper.querySelector('.fullscreen-toggle i').classList.replace('fa-compress', 'fa-expand');
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
        if (linkOptions) linkOptions.style.display = 'none';
        
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