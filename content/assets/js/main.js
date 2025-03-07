// إعدادات Toast
toastr.options = {
    "closeButton": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "timeOut": "3000"
};

// دالة لعرض الإحصائيات في نافذة منبثقة
function showStats(languageId) {
    $.get(`api/stats.php?language_id=${languageId}`)
        .done(function(data) {
            $('#statsModal').find('.modal-body').html(data);
            $('#statsModal').modal('show');
        })
        .fail(function(jqXHR) {
            toastr.error(jqXHR.responseText || 'حدث خطأ أثناء جلب الإحصائيات');
        });
}

// دالة لتحميل الكورسات حسب اللغة
function loadCourses(languageId) {
    window.location.href = `courses.php?language_id=${languageId}`;
}

// دالة لتحميل الدروس حسب الكورس
function loadLessons(courseId) {
    window.location.href = `lessons.php?course_id=${courseId}`;
}

// تفعيل tooltips
$(function () {
    $('[data-bs-toggle="tooltip"]').tooltip();
}); 