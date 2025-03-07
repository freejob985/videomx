// تهيئة TagsInput
$(document).ready(function() {
    // تهيئة التاجات باستخدام Bootstrap Tags Input
    $('#lessonTags').tagsinput({
        trimValue: true,
        confirmKeys: [13, 44], // Enter and comma
        tagClass: 'badge bg-primary',
        maxTags: 10, // الحد الأقصى للتاجات
        maxChars: 20 // الحد الأقصى لحروف كل تاج
    });

    // تهيئة Select2 للقوائم المنسدلة
    $('.section-select, .status-select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dir: 'rtl',
        language: {
            noResults: function() {
                return "لا توجد نتائج";
            }
        }
    });

    // معالجة تحديث الدرس
    $('#lessonUpdateForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>جاري الحفظ...');
        
        const formData = new FormData(this);
        formData.append('is_theory', $('#isTheory').is(':checked') ? 1 : 0);
        formData.append('is_important', $('#isImportant').is(':checked') ? 1 : 0);

        $.ajax({
            url: '../api/update-lesson.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success('تم تحديث الدرس بنجاح');
                    updateLessonUI(response.lesson);
                } else {
                    toastr.error(response.message || 'حدث خطأ أثناء التحديث');
                }
            },
            error: function() {
                toastr.error('حدث خطأ في الاتصال بالخادم');
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>حفظ التغييرات');
            }
        });
    });

    // مراقبة تغيير الحالة لتحديث حالة الاكتمال
    $('.status-select').on('change', function() {
        const statusId = $(this).val();
        const lessonId = $(this).data('lesson-id');
        
        if (statusId) {
            updateLessonStatus(lessonId, statusId);
        }
    });
});

// تحديث واجهة الدرس
function updateLessonUI(lesson) {
    // تحديث الحالة
    if (lesson.status_color) {
        $('.status-select').css('background-color', lesson.status_color);
    }

    // تحديث البادجات
    const badgesContainer = $('.lesson-badges');
    badgesContainer.empty();
    
    if (lesson.is_theory) {
        badgesContainer.append(`
            <span class="badge bg-secondary theory-badge">
                <i class="fas fa-book me-1"></i>
                درس نظري
            </span>
        `);
    }
    
    if (lesson.is_important) {
        badgesContainer.append(`
            <span class="badge bg-warning important-badge">
                <i class="fas fa-star me-1"></i>
                درس مهم
            </span>
        `);
    }
}

// تحديث حالة الدرس
async function updateLessonStatus(lessonId, statusId) {
    try {
        const response = await fetch('../api/update-lesson-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ lesson_id: lessonId, status_id: statusId })
        });
        
        const data = await response.json();
        if (data.success) {
            // تحديث لون السيلكت
            const select = $(`.status-select[data-lesson-id="${lessonId}"]`);
            select.css({
                'background-color': data.status.color,
                'color': data.status.text_color
            });
            
            // تحديث البادج إذا كان الدرس مكتمل
            updateCompletionBadge(data.lesson.completed);
            
            toastr.success('تم تحديث حالة الدرس بنجاح');
        }
    } catch (error) {
        console.error('Error:', error);
        toastr.error('حدث خطأ أثناء تحديث الحالة');
    }
}

// تحديث بادج الاكتمال
function updateCompletionBadge(isCompleted) {
    const badge = $('.completion-badge');
    if (isCompleted) {
        badge.removeClass('bg-secondary').addClass('bg-success')
            .html('<i class="fas fa-check me-1"></i>مكتمل');
    } else {
        badge.removeClass('bg-success').addClass('bg-secondary')
            .html('<i class="fas fa-clock me-1"></i>قيد التقدم');
    }
} 