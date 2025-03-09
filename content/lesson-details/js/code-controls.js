document.addEventListener('DOMContentLoaded', function() {
    // تهيئة التحكم في حجم الخط لكل عنصر كود
    document.querySelectorAll('.code-wrapper').forEach(initializeCodeControls);
});

function initializeCodeControls(wrapper) {
    const codeElement = wrapper.querySelector('code');
    const fontSizeDisplay = wrapper.querySelector('.font-size-display');
    const decreaseBtn = wrapper.querySelector('.font-size-decrease');
    const increaseBtn = wrapper.querySelector('.font-size-increase');
    
    let currentSize = 14; // الحجم الافتراضي
    
    // تحديث حجم الخط
    function updateFontSize() {
        codeElement.style.fontSize = `${currentSize}px`;
        fontSizeDisplay.textContent = `${currentSize}px`;
    }
    
    // زر التصغير
    decreaseBtn.addEventListener('click', () => {
        if (currentSize > 8) {
            currentSize -= 2;
            updateFontSize();
        }
    });
    
    // زر التكبير
    increaseBtn.addEventListener('click', () => {
        if (currentSize < 24) {
            currentSize += 2;
            updateFontSize();
        }
    });
    
    // زر ملء الشاشة
    const fullscreenBtn = wrapper.querySelector('.fullscreen-toggle');
    fullscreenBtn.addEventListener('click', () => {
        const modal = document.getElementById('codeModal');
        const modalTitle = modal.querySelector('.modal-title');
        const modalCode = modal.querySelector('code');
        
        // نسخ محتوى الكود إلى النافذة المنبثقة
        modalCode.className = codeElement.className;
        modalCode.textContent = codeElement.textContent;
        modalTitle.textContent = wrapper.closest('.note-card').querySelector('.card-header h5').textContent;
        
        // تحديث syntax highlighting
        Prism.highlightElement(modalCode);
        
        // عرض النافذة المنبثقة
        new bootstrap.Modal(modal).show();
    });
} 