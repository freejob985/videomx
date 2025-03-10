document.addEventListener('DOMContentLoaded', function() {
    initializeCodeControls();
    initializeModals();
    initializeNoteForm();
});

function initializeCodeControls() {
    const codeWrappers = document.querySelectorAll('.code-wrapper');
    
    codeWrappers.forEach(wrapper => {
        const codeBlock = wrapper.querySelector('pre code');
        if (!codeBlock) return;

        // إضافة أزرار التحكم
        const controls = createCodeControls(wrapper);
        wrapper.insertBefore(controls, wrapper.firstChild);

        // تهيئة حجم الخط الأولي
        updateFontSize(wrapper, 14);
    });
}

function createCodeControls(wrapper) {
    const controls = document.createElement('div');
    controls.className = 'code-controls';
    
    const buttons = [
        {
            title: 'نسخ الكود',
            icon: 'fa-copy',
            action: () => copyCode(wrapper)
        },
        {
            title: 'فتح في نافذة جديدة',
            icon: 'fa-external-link-alt',
            action: () => openInNewWindow(wrapper)
        },
        {
            title: 'تصغير الخط',
            icon: 'fa-minus',
            action: () => changeFontSize(wrapper, -1)
        },
        {
            title: 'تكبير الخط',
            icon: 'fa-plus',
            action: () => changeFontSize(wrapper, 1)
        },
        {
            title: 'ملء الشاشة',
            icon: 'fa-expand',
            action: () => toggleFullscreen(wrapper)
        }
    ];

    buttons.forEach(btn => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'code-btn';
        button.title = btn.title;
        button.innerHTML = `<i class="fas ${btn.icon}"></i>`;
        button.addEventListener('click', btn.action);
        controls.appendChild(button);
    });
    
    return controls;
}

function changeFontSize(wrapper, delta) {
    const codeBlock = wrapper.querySelector('pre code');
    const display = wrapper.querySelector('.font-size-display');
    
    const currentSize = parseInt(window.getComputedStyle(codeBlock).fontSize);
    const newSize = Math.min(Math.max(currentSize + delta, 10), 24);
    
    updateFontSize(wrapper, newSize);
}

function updateFontSize(wrapper, size) {
    const codeBlock = wrapper.querySelector('pre code');
    const display = wrapper.querySelector('.font-size-display');
    
    codeBlock.style.fontSize = `${size}px`;
    display.textContent = `${size}px`;
}

function toggleFullscreen(wrapper) {
    const isFullscreen = wrapper.classList.toggle('fullscreen');
    const button = wrapper.querySelector('button:nth-last-child(2) i');
    
    button.classList.toggle('fa-expand', !isFullscreen);
    button.classList.toggle('fa-compress', isFullscreen);
    document.body.style.overflow = isFullscreen ? 'hidden' : '';
}

function openInModal(wrapper) {
    const modal = document.getElementById('codeModal');
    if (!modal) return;

    const modalContent = modal.querySelector('.modal-content');
    const modalBody = modal.querySelector('.modal-body');
    const modalTitle = modal.querySelector('.modal-title');

    // نسخ محتوى الكود
    const codeContent = wrapper.cloneNode(true);
    
    // إزالة أزرار التحكم القديمة
    const oldControls = codeContent.querySelector('.code-controls');
    if (oldControls) oldControls.remove();

    // إضافة أزرار تحكم جديدة
    const controls = createCodeControls(wrapper);
    codeContent.insertBefore(controls, codeContent.firstChild);

    // تحديث العنوان
    const noteTitle = wrapper.closest('.note-card')?.querySelector('.card-header h5')?.textContent;
    modalTitle.textContent = noteTitle || 'عرض الكود';

    // تحديث المحتوى
    modalBody.innerHTML = '';
    modalBody.appendChild(codeContent);

    // تطبيق تنسيق Prism
    if (window.Prism) {
        Prism.highlightAllUnder(modalBody);
    }

    // فتح الموديول
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
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

// إضافة دالة نسخ الكود
function copyCode(wrapper) {
    const code = wrapper.querySelector('pre code').textContent;
    navigator.clipboard.writeText(code).then(() => {
        toastr.success('تم نسخ الكود بنجاح');
    }).catch(() => {
        toastr.error('فشل نسخ الكود');
    });
}

// إضافة دالة فتح في نافذة جديدة
function openInNewWindow(wrapper) {
    const code = wrapper.querySelector('pre code').textContent;
    const language = wrapper.querySelector('pre code').className.split('-')[1] || 'plaintext';
    const title = wrapper.closest('.note-card')?.querySelector('.card-header h5')?.textContent || 'Code';
    
    // إنشاء محتوى HTML للنافذة الجديدة
    const html = `
        <!DOCTYPE html>
        <html dir="ltr">
        <head>
            <title>${title}</title>
            <meta charset="UTF-8">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fira-code@6.2.0/distr/fira_code.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism-tomorrow.min.css">
            <style>
                body {
                    margin: 0;
                    padding: 20px;
                    background: #1e1e1e;
                    color: #d4d4d4;
                    font-family: 'Fira Code', monospace;
                }
                pre {
                    margin: 0;
                    padding: 20px;
                    border-radius: 6px;
                    background: #2d2d2d !important;
                }
                code {
                    font-family: 'Fira Code', monospace !important;
                    font-size: 14px;
                    line-height: 1.5;
                }
                .header {
                    margin-bottom: 20px;
                    padding: 10px;
                    background: #2d2d2d;
                    border-radius: 6px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .title {
                    margin: 0;
                    color: #d4d4d4;
                    font-size: 16px;
                }
                .language-badge {
                    padding: 4px 8px;
                    background: #3d3d3d;
                    border-radius: 4px;
                    color: #d4d4d4;
                    font-size: 12px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1 class="title">${title}</h1>
                <span class="language-badge">${language}</span>
            </div>
            <pre><code class="language-${language}">${code}</code></pre>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/prism.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-${language}.min.js"></script>
            <script>
                Prism.highlightAll();
            </script>
        </body>
        </html>
    `;

    // فتح نافذة جديدة وكتابة المحتوى
    const newWindow = window.open('', '_blank', 'width=800,height=600');
    newWindow.document.write(html);
    newWindow.document.close();
} 