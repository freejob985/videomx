/**
 * Main JavaScript file for the application
 * Contains common functions and event handlers
 */

// Toast notification helper
function showToast(message, type = 'success') {
    const backgroundColor = type === 'success' ? '#10b981' : '#ef4444';
    
    Toastify({
        text: message,
        duration: 3000,
        gravity: "top",
        position: "center",
        style: {
            background: backgroundColor,
            fontFamily: 'Cairo'
        }
    }).showToast();
}

// Confirmation dialog helper
function showConfirmation(title, text) {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'نعم، متأكد',
        cancelButtonText: 'إلغاء',
        customClass: {
            popup: 'swal2-rtl'
        }
    });
}

// Form submission handler
function handleFormSubmit(formId, successCallback) {
    const form = document.getElementById(formId);
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const result = await showConfirmation('تأكيد', 'هل أنت متأكد من حفظ البيانات؟');
            
            if (result.isConfirmed) {
                if (typeof successCallback === 'function') {
                    successCallback();
                }
                showToast('تم الحفظ بنجاح');
            }
        });
    }
} 