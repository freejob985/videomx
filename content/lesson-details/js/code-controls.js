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

        setupCodeWrapper(wrapper, codeBlock);
    });
}

function setupCodeWrapper(wrapper, codeBlock) {
    // إضافة أزرار التحكم
    const controls = createCodeControls();
    wrapper.insertBefore(controls, wrapper.firstChild);

    // تهيئة حجم الخط الأولي
    updateFontSize(wrapper, 14);

    // إضافة زر فتح الموديول
    const modalBtn = createModalButton();
    controls.appendChild(modalBtn);

    // تهيئة أحداث الأزرار
    setupControlEvents(wrapper, controls);
}

function createCodeControls() {
    const controls = document.createElement('div');
    controls.className = 'code-controls';
    
    const buttons = [
        createButton('فتح في نافذة منفصلة', 'fa-external-link-alt', 'modal-open-btn'),
        createButton('تصغير الخط', 'fa-minus', 'font-size-decrease'),
        createButton('تكبير الخط', 'fa-plus', 'font-size-increase'),
        createButton('ملء الشاشة', 'fa-expand', 'fullscreen-btn')
    ];

    buttons.forEach(button => controls.appendChild(button));
    
    return controls;
}

function createButton(title, icon, className) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `btn btn-sm ${className}`;
    button.title = title;
    button.innerHTML = `<i class="fas ${icon}"></i>`;
    return button;
}

function createModalButton() {
    const button = createButton('فتح في نافذة منفصلة', 'fa-external-link-alt', 'modal-open-btn');
    button.classList.add('modal-open-btn');
    return button;
}

function setupControlEvents(wrapper, controls) {
    const codeBlock = wrapper.querySelector('pre code');
    const fontDisplay = controls.querySelector('.font-size-display');
    const [decreaseBtn, increaseBtn] = controls.querySelectorAll('.font-size-controls button');
    const fullscreenBtn = controls.querySelector('button:nth-last-child(2)');
    const modalBtn = controls.querySelector('.modal-open-btn');

    decreaseBtn.addEventListener('click', () => changeFontSize(wrapper, -1));
    increaseBtn.addEventListener('click', () => changeFontSize(wrapper, 1));
    fullscreenBtn.addEventListener('click', () => toggleFullscreen(wrapper));
    modalBtn.addEventListener('click', () => openInModal(wrapper));
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
    const controls = createCodeControls();
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
    const formWrapper = document.querySelector('.note-form-wrapper');
    const codeOptions = document.querySelector('.code-options');
    const linkOptions = document.querySelector('.link-options');
    
    if (!noteForm || !noteType || !formWrapper) return;

    // تهيئة النوع الأولي
    updateFormType(noteType.value);

    // مراقبة تغيير النوع
    noteType.addEventListener('change', function() {
        const selectedType = this.value;
        
        // إخفاء جميع الخيارات
        codeOptions.style.display = 'none';
        linkOptions.style.display = 'none';
        
        // إزالة الأصناف السابقة
        formWrapper.classList.remove('text-type', 'code-type', 'link-type');
        
        // إضافة الصنف الجديد
        formWrapper.classList.add(`${selectedType}-type`);
        
        // إظهار الخيارات المناسبة
        if (selectedType === 'code') {
            codeOptions.style.display = 'block';
        } else if (selectedType === 'link') {
            linkOptions.style.display = 'block';
        }
    });

    // معالجة تقديم النموذج
    noteForm.addEventListener('submit', handleNoteSubmit);
}

// دالة تحديث نوع النموذج
function updateFormType(type) {
    // إزالة جميع الأصناف السابقة
    const formWrapper = document.querySelector('.note-form-wrapper');
    formWrapper.classList.remove('text-type', 'code-type', 'link-type');
    
    // إخفاء جميع الخيارات الإضافية
    const codeOptions = formWrapper.querySelector('.code-options');
    const linkOptions = formWrapper.querySelector('.link-options');
    
    if (codeOptions) codeOptions.style.display = 'none';
    if (linkOptions) linkOptions.style.display = 'none';

    // إضافة الصنف الجديد وإظهار الخيارات المناسبة
    formWrapper.classList.add(`${type}-type`);
    
    if (type === 'code') {
        codeOptions.style.display = 'block';
        initializeCodeEditor();
    } else if (type === 'link') {
        linkOptions.style.display = 'block';
    }
}

// دالة معالجة تقديم النموذج
async function handleNoteSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const noteType = formData.get('type');
    
    try {
        // إضافة معالجة خاصة حسب النوع
        switch(noteType) {
            case 'code':
                // معالجة خاصة للكود
                formData.append('language', formData.get('code_language'));
                break;
            case 'link':
                // معالجة خاصة للروابط
                if (!formData.get('link_url')) {
                    throw new Error('الرجاء إدخال رابط صحيح');
                }
                break;
        }

        const response = await fetch('/api/notes/add', {
            method: 'POST',
            body: formData
        });

        if (response.ok) {
            location.reload();
        } else {
            throw new Error('فشل في إضافة الملاحظة');
        }
    } catch (error) {
        console.error('Error:', error);
        alert(error.message || 'حدث خطأ أثناء إضافة الملاحظة');
    }
} 