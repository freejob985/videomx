/**
 * عرض إشعار Toast
 * @param {string} type - نوع الإشعار (success, error, warning, info)
 * @param {string} message - نص الرسالة
 */
export function showToast(type, message) {
    const toast = document.getElementById('toast');
    const toastBody = toast.querySelector('.toast-body');
    
    // تعيين لون الإشعار حسب النوع
    const headerColors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };
    
    // تعيين أيقونة الإشعار
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };
    
    // تحديث محتوى الإشعار
    toast.querySelector('.toast-header').style.background = headerColors[type];
    toastBody.innerHTML = `
        <i class="${icons[type]} me-2"></i>
        ${message}
    `;
    
    // إظهار الإشعار
    const bsToast = new bootstrap.Toast(toast, {
        animation: true,
        autohide: true,
        delay: 3000
    });
    bsToast.show();
} 