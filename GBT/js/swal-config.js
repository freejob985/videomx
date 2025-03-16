/**
 * عرض نافذة تأكيد باستخدام SweetAlert2
 * @param {Object} options - خيارات النافذة
 * @returns {Promise} نتيجة التأكيد
 */
export function showConfirmation(options) {
    return Swal.fire({
        title: options.title || 'تأكيد',
        text: options.text || 'هل أنت متأكد؟',
        icon: options.icon || 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'نعم',
        cancelButtonText: 'إلغاء',
        customClass: {
            popup: 'swal-rtl',
            title: 'swal-title-rtl',
            content: 'swal-content-rtl'
        }
    });
} 