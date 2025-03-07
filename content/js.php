<script>
// دالة تشغيل الفيديو
function playVideo(url, title) {
    const modal = document.getElementById('videoModal');
    const videoContainer = modal.querySelector('.video-container');
    modal.querySelector('.modal-title').textContent = title;

    // تحويل رابط اليوتيوب إلى رابط التضمين
    if (url.includes('youtube.com') || url.includes('youtu.be')) {
        const videoId = url.includes('youtu.be') 
            ? url.split('/').pop()
            : new URLSearchParams(new URL(url).search).get('v');
        
        const embedUrl = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
        videoContainer.innerHTML = `<iframe src="${embedUrl}" allowfullscreen allow="autoplay"></iframe>`;
    } else {
        videoContainer.innerHTML = `<iframe src="${url}" allowfullscreen></iframe>`;
    }

    new bootstrap.Modal(modal).show();
}

// دالة عرض النص
function showTranscript(lessonId) {
    const modal = document.getElementById('transcriptModal');
    fetch(`api/transcript.php?lesson_id=${lessonId}`)
        .then(response => response.text())
        .then(html => {
            modal.querySelector('.modal-body').innerHTML = html;
            new bootstrap.Modal(modal).show();
        });
}

// إغلاق الفيديو عند إغلاق النافذة
document.getElementById('videoModal').addEventListener('hidden.bs.modal', function () {
    this.querySelector('iframe').src = '';
});

// تحديث القسم
document.querySelectorAll('.section-select').forEach(select => {
    select.addEventListener('change', async function() {
        const lessonId = this.dataset.lessonId;
        const sectionId = this.value;
        
        // عرض مؤشر التحميل
        const loader = document.querySelector('.loader');
        if (loader) loader.style.display = 'flex';

        try {
            const response = await fetch('api/update-lesson-section.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    lesson_id: lessonId,
                    section_id: sectionId
                })
            });

            const data = await response.json();
            
            if (data.success) {
                toastr.success(data.message);
                // تحديث واجهة المستخدم إذا لزم الأمر
                updateStatusStats();
            } else {
                throw new Error(data.message || 'حدث خطأ أثناء تحديث القسم');
            }
        } catch (error) {
            console.error('Error:', error);
            toastr.error(error.message || 'حدث خطأ غير متوقع');
            
            // إعادة تحديد القيمة السابقة في حالة الفشل
            if (this.dataset.previousValue) {
                this.value = this.dataset.previousValue;
            }
        } finally {
            // إخفاء مؤشر التحميل
            if (loader) loader.style.display = 'none';
        }
    });

    // حفظ القيمة السابقة قبل التغيير
    select.addEventListener('focus', function() {
        this.dataset.previousValue = this.value;
    });
});

// تحديث حالة الدرس وألوانه
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', async function() {
        const lessonId = this.dataset.lessonId;
        const statusId = this.value;
        const selectedOption = this.options[this.selectedIndex];
        const row = this.closest('tr');
        
        // تحديث لون السيليكت
        if (selectedOption.dataset.color) {
            this.style.backgroundColor = selectedOption.dataset.color;
            this.style.color = selectedOption.dataset.textColor;
        } else {
            this.style.backgroundColor = '';
            this.style.color = '';
        }

        // عرض مؤشر التحميل
        const loader = document.querySelector('.loader');
        if (loader) loader.style.display = 'flex';
        
        try {
            const response = await fetch('api/update-lesson-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    lesson_id: lessonId,
                    status_id: statusId
                })
            });

            const data = await response.json();
            
            if (data.success) {
                toastr.success(data.message);
                
                // تحديث حالة completed في الواجهة إذا كان موجوداً
                if (row && typeof data.completed !== 'undefined') {
                    row.classList.toggle('completed', data.completed === 1);
                    
                    // تحديث أي عناصر أخرى تعتمد على حالة completed
                    const completedBadge = row.querySelector('.completed-badge');
                    if (completedBadge) {
                        completedBadge.style.display = data.completed === 1 ? '' : 'none';
                    }
                }
                
                updateStatusStats();
                updateProgressBar();
            } else {
                throw new Error(data.message || 'حدث خطأ أثناء تحديث حالة الدرس');
            }
        } catch (error) {
            console.error('Error:', error);
            toastr.error(error.message || 'حدث خطأ غير متوقع');
            
            // إعادة تحديد القيمة السابقة في حالة الفشل
            if (this.dataset.previousValue) {
                this.value = this.dataset.previousValue;
                const prevOption = this.options[this.selectedIndex];
                if (prevOption.dataset.color) {
                    this.style.backgroundColor = prevOption.dataset.color;
                    this.style.color = prevOption.dataset.textColor;
                }
            }
        } finally {
            if (loader) loader.style.display = 'none';
        }
    });

    // حفظ القيمة السابقة قبل التغيير
    select.addEventListener('focus', function() {
        this.dataset.previousValue = this.value;
    });
});

// تحسين تجربة المستخدم عند التفاعل مع Select
document.querySelectorAll('.custom-select').forEach(select => {
    // إضافة تأثير عند التركيز
    select.addEventListener('focus', function() {
        this.parentElement.classList.add('focused');
    });
    
    // إزالة التأثير عند فقد التركيز
    select.addEventListener('blur', function() {
        this.parentElement.classList.remove('focused');
    });
});

/**
 * دالة نسخ التفاصيل
 * تقوم بنسخ تفاصيل الكورس والدروس والتاجات والملاحظات
 * 
 * @param {string} type - نوع النسخ (all, details, titles)
 * @returns {Promise<void>}
 * 
 * المخرجات المتوقعة:
 * - نجاح: نسخ النص إلى الحافظة وعرض رسالة نجاح
 * - فشل: عرض رسالة خطأ
 */
async function copyDetails(type = 'all') {
    try {
        // عرض مؤشر التحميل
        const loader = document.querySelector('.loader');
        if (loader) loader.style.display = 'flex';

        // جلب التفاصيل من الخادم
        const response = await fetch(`api/course-details.php?course_id=<?php echo $course_id; ?>&type=${type}`);
        const data = await response.json();
        
        if (data.success) {
            // نسخ النص إلى الحافظة باستخدام Clipboard API
            try {
                await navigator.clipboard.writeText(data.text);
            } catch (clipboardError) {
                // استخدام الطريقة التقليدية كخطة بديلة
                const copyArea = document.getElementById('copyArea');
                copyArea.value = data.text;
                copyArea.style.display = 'block';
                copyArea.select();
                document.execCommand('copy');
                copyArea.style.display = 'none';
            }
            
            // إظهار رسالة نجاح مناسبة
            let message = '';
            switch (type) {
                case 'details':
                    message = 'تم نسخ تفاصيل الكورس بنجاح';
                    break;
                case 'titles':
                    message = 'تم نسخ أسماء الدروس والتاجات والملاحظات بنجاح';
                    break;
                default:
                    message = 'تم نسخ جميع المعلومات بنجاح';
            }
            toastr.success(message);
        } else {
            throw new Error(data.message || 'حدث خطأ أثناء جلب التفاصيل');
        }
    } catch (error) {
        console.error('Error:', error);
        toastr.error('حدث خطأ غير متوقع');
    } finally {
        // إخفاء مؤشر التحميل
        const loader = document.querySelector('.loader');
        if (loader) loader.style.display = 'none';
    }
}

// إضافة عنصر textarea للنسخ إذا لم يكن موجوداً
document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('copyArea')) {
        const copyArea = document.createElement('textarea');
        copyArea.id = 'copyArea';
        copyArea.style.cssText = 'position: absolute; left: -9999px; top: -9999px; opacity: 0;';
        document.body.appendChild(copyArea);
    }
});

// تفعيل tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// تحديث إحصائيات الحالات
function updateStatusStats() {
    const stats = {};
    const totalLessons = document.querySelectorAll('.status-select').length;
    
    // حساب عدد الدروس لكل حالة
    document.querySelectorAll('.status-select').forEach(select => {
        const statusId = select.value;
        if (statusId) {
            stats[statusId] = (stats[statusId] || 0) + 1;
        }
    });
    
    // تحديث العرض
    document.querySelectorAll('.status-card').forEach(card => {
        const statusId = card.dataset.statusId;
        const count = stats[statusId] || 0;
        const percentage = (count / totalLessons) * 100;
        
        card.querySelector('.status-count').textContent = count;
        card.querySelector('.progress-bar').style.width = `${percentage}%`;
    });
}

// تحديث مستمعي الأحداث
document.addEventListener('DOMContentLoaded', function() {
    loadStatusColors();
    updateStatusStats();
    updateProgressBar();
    
    // مستمعي تغيير الحالة
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            const statusId = this.value;
            const colors = JSON.parse(localStorage.getItem('statusColors') || '{"bg":{},"text":{}}');
            
            if (colors.bg[statusId]) {
                this.style.backgroundColor = colors.bg[statusId];
                this.style.color = colors.text[statusId] || '#000000';
            }
            
            setTimeout(() => {
                updateStatusStats();
                updateProgressBar();
            }, 500);
        });
    });
});

// تحديث دالة حفظ الألوان
async function saveStatusColors() {
    const colors = {};
    const textColors = {};
    const promises = [];
    
    document.querySelectorAll('.status-item').forEach(item => {
        const statusId = item.querySelector('.status-color-picker').dataset.statusId;
        const color = item.querySelector('.status-color-picker').value;
        const textColor = item.querySelector('.status-text-color-picker').value;
        
        colors[statusId] = color;
        textColors[statusId] = textColor;
        
        promises.push(
            fetch('api/update-status-color.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    status_id: statusId,
                    color: color,
                    text_color: textColor
                })
            })
        );
    });
    
    try {
        await Promise.all(promises);
        localStorage.setItem('statusColors', JSON.stringify({ bg: colors, text: textColors }));
        toastr.success('تم حفظ جميع الألوان بنجاح');
        updateAllStatusStyles();
        updateStatusStats();
        updateProgressBar();
    } catch (error) {
        console.error('Error:', error);
        toastr.error('حدث خطأ أثناء حفظ الألوان');
    }
}

// تحديث دالة تحديث البروجرس بار
async function updateProgressBar() {
    try {
        const response = await fetch(`api/progress-stats.php?course_id=<?php echo $course_id; ?>`);
        const data = await response.json();
        
        if (data.success) {
            const progressBar = document.querySelector('.course-progress .progress-bar');
            const percentage = Math.round(data.stats.completion_percentage);
            progressBar.style.width = `${percentage}%`;
            progressBar.setAttribute('aria-valuenow', percentage);
            progressBar.textContent = `${percentage}%`;
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// تحديث معاينة الحالة
function updateStatusPreview(statusId) {
    const item = document.querySelector(`.status-item [data-status-id="${statusId}"]`).closest('.status-item');
    const bgColor = item.querySelector('.status-color-picker').value;
    const textColor = item.querySelector('.status-text-color-picker').value;
    const preview = item.querySelector('.status-preview');
    
    preview.style.backgroundColor = bgColor;
    preview.style.color = textColor;
}

// دالة عرض نافذة إدارة الحالات
function showStatusesModal() {
    new bootstrap.Modal(document.getElementById('statusesModal')).show();
}

// تهيئة الأحداث عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة tooltips
    initializeTooltips();
});

/**
 * تهيئة أحداث الأزرار
 * يقوم بربط جميع الأحداث للأزرار في الصفحة
 */
function initializeButtonEvents() {
    // ربط حدث تبديل الدروس المهمة
    document.querySelectorAll('.toggle-importance').forEach(button => {
        button.addEventListener('click', function() {
            const lessonId = this.dataset.lessonId;
            const isImportant = this.dataset.isImportant === '1';
            toggleImportance(lessonId, isImportant);
        });
    });

    // ربط حدث تبديل الدروس النظرية
    document.querySelectorAll('.toggle-theory').forEach(button => {
        button.addEventListener('click', function() {
            const lessonId = this.dataset.lessonId;
            const isTheory = this.dataset.isTheory === '1';
            toggleTheory(lessonId, isTheory);
        });
    });

    // ربط حدث حذف الدرس
    document.querySelectorAll('.delete-lesson').forEach(button => {
        button.addEventListener('click', function() {
            const lessonId = this.dataset.lessonId;
            deleteLesson(lessonId);
        });
    });
}

/**
 * عرض رسالة Toast مخصصة
 * @param {string} message - نص الرسالة
 * @param {string} type - نوع الرسالة (success, warning, info)
 */
function showToast(message, type = 'success') {
    // إنشاء عنصر Toast
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-header">
            <i class="fas ${type === 'success' ? 'fa-check-circle text-success' : 
                          type === 'warning' ? 'fa-exclamation-circle text-warning' : 
                          'fa-info-circle text-info'} me-2"></i>
            <strong class="me-auto">تنبيه</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            ${message}
        </div>
    `;

    // إضافة Toast للصفحة
    const container = document.querySelector('.toast-container') || 
                     (() => {
                         const cont = document.createElement('div');
                         cont.className = 'toast-container';
                         document.body.appendChild(cont);
                         return cont;
                     })();
    
    container.appendChild(toast);

    // تفعيل Toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 3000
    });
    bsToast.show();

    // إزالة Toast بعد الإخفاء
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

/**
 * تحديث شارة الدرس المهم
 * @param {number} lessonId - معرف الدرس
 * @param {boolean} isImportant - حالة الأهمية
 */
function updateImportanceBadge(lessonId, isImportant) {
    const row = document.querySelector(`tr[data-lesson-id="${lessonId}"]`);
    const badgesContainer = row.querySelector('.lesson-badges');
    const existingBadge = badgesContainer.querySelector('.badge-important');
    
    if (isImportant && !existingBadge) {
        const badge = document.createElement('span');
        badge.className = 'badge badge-important badge-appear';
        badge.innerHTML = '<i class="fas fa-star me-1"></i>مهم';
        
        // إضافة الشارة مع تأثير حركي
        requestAnimationFrame(() => {
            badgesContainer.appendChild(badge);
            // تفعيل التأثير الحركي
            setTimeout(() => badge.classList.add('show'), 50);
        });
    } else if (!isImportant && existingBadge) {
        // إزالة الشارة مع تأثير حركي
        existingBadge.classList.remove('show');
        existingBadge.addEventListener('transitionend', () => {
            existingBadge.remove();
        });
    }
}

/**
 * تحديث شارة الدرس النظري
 * @param {number} lessonId - معرف الدرس
 * @param {boolean} isTheory - حالة النظري
 */
function updateTheoryBadge(lessonId, isTheory) {
    const row = document.querySelector(`tr[data-lesson-id="${lessonId}"]`);
    const badgesContainer = row.querySelector('.lesson-badges');
    const existingBadge = badgesContainer.querySelector('.badge-theory');
    
    if (isTheory && !existingBadge) {
        const badge = document.createElement('span');
        badge.className = 'badge badge-theory badge-appear';
        badge.innerHTML = '<i class="fas fa-book me-1"></i>نظري';
        
        // إضافة الشارة مع تأثير حركي
        requestAnimationFrame(() => {
            badgesContainer.appendChild(badge);
            // تفعيل التأثير الحركي
            setTimeout(() => badge.classList.add('show'), 50);
        });
    } else if (!isTheory && existingBadge) {
        // إزالة الشارة مع تأثير حركي
        existingBadge.classList.remove('show');
        existingBadge.addEventListener('transitionend', () => {
            existingBadge.remove();
        });
    }
}

/**
 * تحديث حالة الدرس المهم مع Ajax
 */
async function toggleImportance(lessonId, currentState) {
    try {
        const response = await fetch('api/toggle-importance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ lesson_id: lessonId })
        });
        
        const data = await response.json();
        if (data.success) {
            // تحديث الشارة
            updateImportanceBadge(lessonId, !currentState);
            
            // عرض رسالة نجاح
            showToast(data.message, 'success');
            
            // تحديث حالة الزر
            updateButtonState(lessonId, !currentState, 'important');
            
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast(error.message, 'warning');
    }
}

/**
 * تحديث حالة الدرس النظري مع Ajax
 */
async function toggleTheory(lessonId, currentState) {
    try {
        const response = await fetch('api/toggle-theory.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ lesson_id: lessonId })
        });
        
        const data = await response.json();
        if (data.success) {
            // تحديث الشارة
            updateTheoryBadge(lessonId, !currentState);
            
            // عرض رسالة نجاح
            showToast(data.message, 'info');
            
            // تحديث حالة الزر
            updateButtonState(lessonId, !currentState, 'theory');
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast(error.message, 'warning');
    }
}

/**
 * تحديث حالة الزر
 */
function updateButtonState(lessonId, newState, type) {
    const button = document.querySelector(
        `button[onclick*="toggle${type === 'important' ? 'Importance' : 'Theory'}(${lessonId}"]`
    );
    if (button) {
        const icon = button.querySelector('i');
        const text = button.querySelector('span');
        
        if (type === 'important') {
            icon.classList.toggle('text-warning', newState);
            text.textContent = newState ? 'إلغاء تحديد كدرس مهم' : 'تحديد كدرس مهم';
        } else {
            icon.classList.toggle('text-info', newState);
            text.textContent = newState ? 'إلغاء تحديد كدرس نظري' : 'تحديد كدرس نظري';
        }
    }
}

// تهيئة tooltips بعد تحميل Bootstrap
function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * حذف درس
 * @param {number} lessonId - معرف الدرس
 */
function deleteLesson(lessonId) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: 'سيتم حذف الدرس نهائياً',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، احذف',
        cancelButtonText: 'إلغاء'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await fetch('api/delete-lesson.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ lesson_id: lessonId })
                });
                
                const data = await response.json();
                if (data.success) {
                    // حذف الصف من الجدول
                    const row = document.querySelector(`tr[data-lesson-id="${lessonId}"]`);
                    row.remove();
                    
                    // تحديث الإحصائيات
                    updateStatusStats();
                    updateProgressBar();
                    
                    toastr.success(data.message);
                } else {
                    throw new Error(data.message || 'حدث خطأ أثناء حذف الدرس');
                }
            } catch (error) {
                console.error('Error:', error);
                toastr.error(error.message || 'حدث خطأ غير متوقع');
            }
        }
    });
}

/**
 * تأكيد تحديد الدرس كمهم
 * @param {number} lessonId - معرف الدرس
 * @param {boolean} currentState - الحالة الحالية
 */
function confirmToggleImportance(lessonId, currentState) {
    const message = currentState ? 
        'هل تريد إلغاء تحديد هذا الدرس كدرس مهم؟' : 
        'هل تريد تحديد هذا الدرس كدرس مهم؟';
    
    Swal.fire({
        title: 'تأكيد',
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'نعم',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            toggleImportance(lessonId, currentState);
        }
    });
}

/**
 * تأكيد تحديد الدرس كنظري
 * @param {number} lessonId - معرف الدرس
 * @param {boolean} currentState - الحالة الحالية
 */
function confirmToggleTheory(lessonId, currentState) {
    const message = currentState ? 
        'هل تريد إلغاء تحديد هذا الدرس كدرس نظري؟' : 
        'هل تريد تحديد هذا الدرس كدرس نظري؟';
    
    Swal.fire({
        title: 'تأكيد',
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#17a2b8',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'نعم',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            toggleTheory(lessonId, currentState);
        }
    });
}

// دالة تحديث حالة اكتمال الدرس
async function toggleLessonCompleted(lessonId, completed) {
    const loader = document.querySelector('.loader');
    if (loader) loader.style.display = 'flex';

    try {
        const response = await fetch('api/toggle-lesson-completed.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                lesson_id: lessonId,
                completed: completed
            })
        });

        const data = await response.json();
        
        if (data.success) {
            // تحديث مظهر عنوان الدرس
            const titleElement = document.querySelector(`.lesson-title[data-lesson-id="${lessonId}"]`);
            if (titleElement) {
                titleElement.classList.toggle('completed', data.completed === 1);
            }

            // تحديث أيقونة القائمة المنسدلة
            const dropdownButton = document.querySelector(`button[onclick*="toggleLessonCompleted(${lessonId},"]`);
            if (dropdownButton) {
                const icon = dropdownButton.querySelector('i');
                icon.className = data.completed === 1 ? 
                    'fas fa-check-circle text-success me-2' : 
                    'far fa-circle text-muted me-2';
                
                // تحديث نص الزر
                dropdownButton.innerHTML = dropdownButton.innerHTML.replace(
                    data.completed === 1 ? 'تحديد كمكتمل' : 'إلغاء تحديد كمكتمل',
                    data.completed === 1 ? 'إلغاء تحديد كمكتمل' : 'تحديد كمكتمل'
                );
                
                // تحديث حالة active للزر
                dropdownButton.classList.toggle('active', data.completed === 1);
            }

            toastr.success(data.message);
            updateStatusStats();
            updateProgressBar();
        } else {
            throw new Error(data.message || 'حدث خطأ أثناء تحديث حالة الدرس');
        }
    } catch (error) {
        console.error('Error:', error);
        toastr.error(error.message || 'حدث خطأ غير متوقع');
    } finally {
        if (loader) loader.style.display = 'none';
    }
}

// تحديث إحصائيات كامل الكورس
function updateFullCourseStats() {
    $.ajax({
        url: 'api/get-full-course-stats.php',
        method: 'GET',
        data: { course_id: courseId },
        success: function(response) {
            if (response.success) {
                const stats = response.stats;
                
                // تحديث القيم مع التحقق من صحتها
                Object.keys(stats).forEach(key => {
                    const value = parseInt(stats[key]) || 0;
                    const element = $(`#full-${key.replace('_', '-')}`);
                    
                    if (element.length) {
                        if (key.includes('duration')) {
                            element.text(formatDuration(value));
                        } else {
                            element.text(value);
                        }
                        
                        // إضافة تلميح إضافي للدروس بدون حالة
                        if (key === 'no_status_count' && stats.available_statuses > 0) {
                            element.attr('title', 
                                `هناك ${stats.available_statuses} حالة متاحة للغة الحالية`);
                        }
                    }
                });
                
                // تحديث شريط التقدم
                const percentage = stats.total_count > 0 ? 
                    Math.round((stats.completed_count / stats.total_count) * 100) : 0;
                $('#full-completion-percentage').text(percentage + '%');
                $('#full-completion-progress').css('width', percentage + '%');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error updating stats:', error);
            toastr.error('حدث خطأ أثناء تحديث الإحصائيات');
        }
    });
}

// إضافة بعد التعريفات الموجودة
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة حالة العرض من localStorage
    initializeVisibilityState('fullStatsContent', 'fullStatsVisibility');
    initializeVisibilityState('courseInfoContent', 'courseInfoVisibility');
    
    // إضافة مستمعي الأحداث لأزرار التبديل
    setupToggleButton('toggleFullStatsBtn', 'fullStatsContent', 'fullStatsVisibility');
    setupToggleButton('toggleCourseInfoBtn', 'courseInfoContent', 'courseInfoVisibility');
});

/**
 * تهيئة حالة العرض للعنصر
 * @param {string} contentId - معرف العنصر
 * @param {string} storageKey - مفتاح التخزين
 */
function initializeVisibilityState(contentId, storageKey) {
    const content = document.getElementById(contentId);
    const isHidden = localStorage.getItem(storageKey) === 'hidden';
    
    if (content) {
        if (isHidden) {
            content.style.display = 'none';
            // تحديث أيقونة الزر المرتبط
            const button = document.querySelector(`button[data-content="${contentId}"]`);
            if (button) {
                const icon = button.querySelector('i');
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    }
}

/**
 * إعداد زر التبديل
 * @param {string} buttonId - معرف الزر
 * @param {string} contentId - معرف المحتوى
 * @param {string} storageKey - مفتاح التخزين
 */
function setupToggleButton(buttonId, contentId, storageKey) {
    const button = document.getElementById(buttonId);
    const content = document.getElementById(contentId);
    
    if (button && content) {
        // إضافة معرف المحتوى للزر
        button.setAttribute('data-content', contentId);
        
        button.addEventListener('click', function() {
            // تبديل حالة العرض
            const isVisible = content.style.display !== 'none';
            
            // تحريك المحتوى
            $(content).slideToggle({
                duration: 300,
                complete: function() {
                    // تحديث الأيقونة
                    const icon = button.querySelector('i');
                    icon.classList.toggle('fa-eye-slash', !isVisible);
                    icon.classList.toggle('fa-eye', isVisible);
                    
                    // حفظ الحالة
                    localStorage.setItem(storageKey, isVisible ? 'hidden' : 'visible');
                }
            });
        });
    }
}

// تحديث الدالة الموجودة
$(document).ready(function() {
    // الأكواد الموجودة...
    
    // تحديث أزرار التبديل
    $('#toggleFullStatsBtn, #toggleCourseInfoBtn').each(function() {
        const contentId = $(this).data('content');
        const content = $('#' + contentId);
        
        if (content.length) {
            const isHidden = localStorage.getItem(contentId + 'Visibility') === 'hidden';
            if (isHidden) {
                content.hide();
                $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
            }
        }
    });
});

/**
 * تهيئة أزرار الإخفاء/الإظهار
 * يتم استدعاؤها عند تحميل الصفحة
 */
document.addEventListener('DOMContentLoaded', function() {
    // حذف الوظائف المكررة السابقة
    const sections = [
        {
            buttonId: 'toggleFullStatsBtn',
            contentId: 'fullStatsContent',
            storageKey: 'fullStatsVisibility'
        },
        {
            buttonId: 'toggleCourseInfoBtn',
            contentId: 'courseInfoContent',
            storageKey: 'courseInfoVisibility'
        },
        {
            buttonId: 'toggleStatsBtn',
            contentId: 'statsContent',
            storageKey: 'statsVisibility'
        }
    ];

    // تهيئة كل قسم
    sections.forEach(section => {
        const button = document.getElementById(section.buttonId);
        const content = document.getElementById(section.contentId);
        
        if (button && content) {
            // استرجاع الحالة المحفوظة
            const isHidden = localStorage.getItem(section.storageKey) === 'hidden';
            
            // تطبيق الحالة المحفوظة عند التحميل
            if (isHidden) {
                content.style.display = 'none';
                const icon = button.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }

            // إضافة مستمع الحدث للنقر
            button.addEventListener('click', function() {
                const isVisible = content.style.display !== 'none';
                
                // تحريك المحتوى مع تأثير حركي
                $(content).slideToggle({
                    duration: 300,
                    start: function() {
                        // تحديث الأيقونة عند بدء الحركة
                        const icon = button.querySelector('i');
                        if (icon) {
                            icon.classList.remove('fa-eye', 'fa-eye-slash');
                            icon.classList.add(isVisible ? 'fa-eye' : 'fa-eye-slash');
                        }
                    },
                    complete: function() {
                        // حفظ الحالة في localStorage
                        localStorage.setItem(section.storageKey, isVisible ? 'hidden' : 'visible');
                        
                        // تحديث الإحصائيات إذا كان القسم هو إحصائيات الكورس
                        if (section.contentId === 'fullStatsContent') {
                            updateFullCourseStats();
                        }
                    }
                });
            });
        }
    });
});

// إضافة CSS للأزرار
const toggleButtonStyles = document.createElement('style');
toggleButtonStyles.textContent = `
    .btn-toggle {
        cursor: pointer;
        padding: 8px !important;
        border-radius: 50%;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        background: transparent;
        border: none;
        outline: none;
    }
    
    .btn-toggle:hover {
        background-color: rgba(255, 255, 255, 0.1);
        transform: scale(1.1);
    }
    
    .btn-toggle:active {
        transform: scale(0.95);
    }
    
    .btn-toggle i {
        transition: all 0.3s ease;
        font-size: 1.2rem;
    }
    
    .content-section {
        transition: all 0.3s ease-in-out;
    }
`;
document.head.appendChild(toggleButtonStyles);

// تحديث الإحصائيات عند تغيير حالة الدرس
document.addEventListener('lessonStatusChanged', function() {
    updateFullCourseStats();
    updatePageStats();
});

// تحديث إحصائيات الصفحة
function updatePageStats() {
    $.ajax({
        url: 'api/get-page-stats.php',
        method: 'GET',
        data: { 
            course_id: courseId,
            page: currentPage,
            per_page: perPage
        },
        success: function(response) {
            if (response.success) {
                const stats = response.stats;
                
                // تحديث القيم مع التحقق من صحتها
                Object.keys(stats).forEach(key => {
                    const value = parseInt(stats[key]) || 0;
                    const element = $(`#page-${key.replace('_', '-')}`);
                    
                    if (element.length) {
                        if (key.includes('duration')) {
                            element.text(formatDuration(value));
                        } else {
                            element.text(value);
                        }
                        
                        // إضافة تلميح إضافي للدروس بدون حالة
                        if (key === 'no_status_count' && stats.available_statuses > 0) {
                            element.attr('title', 
                                `هناك ${stats.available_statuses} حالة متاحة للغة الحالية`);
                        }
                    }
                });
                
                // تحديث شريط التقدم
                const percentage = stats.total_count > 0 ? 
                    Math.round((stats.completed_count / stats.total_count) * 100) : 0;
                $('#page-completion-percentage').text(percentage + '%');
                $('#page-completion-progress').css('width', percentage + '%');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error updating page stats:', error);
            toastr.error('حدث خطأ أثناء تحديث إحصائيات الصفحة');
        }
    });
}

// تعريف الكلاس في بداية الملف
class TagsManager {
    constructor(wrapper, input, suggestions) {
        this.wrapper = wrapper;
        this.input = input;
        this.suggestions = suggestions;
        this.tags = new Set();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // إضافة تاج عند الضغط على Enter
        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.addTag(this.input.value);
            }
        });

        // حذف آخر تاج عند الضغط على Backspace
        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !this.input.value) {
                e.preventDefault();
                this.removeLastTag();
            }
        });

        // تحديث الاقتراحات عند الكتابة
        this.input.addEventListener('input', () => {
            this.updateSuggestions();
        });
    }

    addTag(tagText) {
        tagText = tagText.trim();
        if (!tagText || this.tags.has(tagText)) return;

        this.tags.add(tagText);
        
        const tagElement = document.createElement('span');
        tagElement.className = 'tag-item';
        tagElement.innerHTML = `
            ${tagText}
            <span class="remove-tag" onclick="tagsManager.removeTag('${tagText}')">&times;</span>
        `;
        
        this.input.parentNode.insertBefore(tagElement, this.input);
        this.input.value = '';
        this.updateHiddenInput();
    }

    removeTag(tagText) {
        this.tags.delete(tagText);
        this.updateView();
        this.updateHiddenInput();
    }

    removeLastTag() {
        const lastTag = Array.from(this.tags).pop();
        if (lastTag) {
            this.removeTag(lastTag);
        }
    }

    updateView() {
        // حذف جميع التاجات الحالية
        const existingTags = this.wrapper.querySelectorAll('.tag-item');
        existingTags.forEach(tag => tag.remove());

        // إعادة إنشاء التاجات
        this.tags.forEach(tag => this.addTag(tag));
    }

    updateHiddenInput() {
        const hiddenInput = document.getElementById('lessonTags');
        hiddenInput.value = Array.from(this.tags).join(',');
    }

    setTags(tagsString) {
        this.tags.clear();
        if (tagsString) {
            tagsString.split(',')
                .map(tag => tag.trim())
                .filter(tag => tag)
                .forEach(tag => this.addTag(tag));
        }
    }

    updateSuggestions() {
        const value = this.input.value.toLowerCase();
        if (!value) {
            this.suggestions.style.display = 'none';
            return;
        }

        // هنا يمكنك إضافة منطق لجلب الاقتراحات من قاعدة البيانات
        const commonTags = [
            'JavaScript', 'PHP', 'Python', 'HTML', 'CSS',
            'Frontend', 'Backend', 'Database', 'API'
        ];

        const matches = commonTags.filter(tag => 
            tag.toLowerCase().includes(value) && !this.tags.has(tag)
        );

        if (matches.length) {
            this.suggestions.innerHTML = matches
                .map(tag => `<div class="suggestion-item" onclick="tagsManager.addTag('${tag}')">${tag}</div>`)
                .join('');
            this.suggestions.style.display = 'block';
        } else {
            this.suggestions.style.display = 'none';
        }
    }
}

// تعريف متغير عام للتاجات
window.tagsManager = null;

/**
 * فتح نافذة تحرير التاجات
 */
function editLessonTags(lessonId, currentTags) {
    document.getElementById('lessonIdForTags').value = lessonId;
    
    // تهيئة حقل التاجات
    const tagsInput = $('#lessonTags');
    tagsInput.tagsinput('removeAll');
    
    // إضافة التاجات الحالية
    if (currentTags) {
        currentTags.split(',')
            .map(tag => tag.trim())
            .filter(tag => tag)
            .forEach(tag => tagsInput.tagsinput('add', tag));
    }
    
    // فتح النافذة
    new bootstrap.Modal(document.getElementById('editTagsModal')).show();
}

/**
 * حفظ تغييرات التاجات
 */
function saveTagsChanges() {
    const form = document.getElementById('editTagsForm');
    const formData = new FormData(form);
    const loader = document.querySelector('.loader');
    
    if (loader) loader.style.display = 'block';

    fetch('api/update-lesson-tags.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateLessonTags(data.lessonId, data.tags);
            bootstrap.Modal.getInstance(document.getElementById('editTagsModal')).hide();
            toastr.success(data.message || 'تم تحديث التاجات بنجاح');
        } else {
            throw new Error(data.error || 'حدث خطأ أثناء تحديث التاجات');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error(error.message);
    })
    .finally(() => {
        if (loader) loader.style.display = 'none';
    });
}

/**
 * تحديث عرض التاجات في الصفحة
 */
function updateLessonTags(lessonId, tags) {
    const lessonRow = document.querySelector(`tr[data-lesson-id="${lessonId}"]`);
    if (!lessonRow) return;

    const badgesContainer = lessonRow.querySelector('.lesson-badges');
    if (!badgesContainer) return;

    // حذف التاجات القديمة
    const oldTags = badgesContainer.querySelectorAll('.lesson-tag');
    oldTags.forEach(tag => {
        tag.classList.remove('show');
        setTimeout(() => tag.remove(), 300);
    });

    // إضافة التاجات الجديدة
    if (tags) {
        const tagsList = tags.split(',').map(tag => tag.trim()).filter(tag => tag);
        tagsList.forEach((tag, index) => {
            setTimeout(() => {
                const tagElement = document.createElement('span');
                const tagClass = tag.toLowerCase().replace(/[^a-z0-9-]/g, '');
                tagElement.className = `lesson-tag ${tagClass} badge-appear`;
                tagElement.title = tag;
                tagElement.innerHTML = `<i class="fas fa-tag"></i> ${tag}`;
                badgesContainer.appendChild(tagElement);
                
                requestAnimationFrame(() => tagElement.classList.add('show'));
            }, index * 100);
        });
    }
}

/**
 * فتح نافذة إضافة ملاحظة
 * @param {number} lessonId - معرف الدرس
 */
function addLessonNote(lessonId) {
    // تنظيف النموذج
    const form = document.getElementById('addNoteForm');
    form.reset();
    
    // تعيين معرف الدرس
    document.getElementById('lessonIdForNote').value = lessonId;
    
    // فتح النافذة
    new bootstrap.Modal(document.getElementById('addNoteModal')).show();
}

/**
 * حفظ الملاحظة الجديدة
 */
function saveNote() {
    const form = document.getElementById('addNoteForm');
    
    // التحقق من صحة النموذج
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    const loader = document.querySelector('.loader');
    
    // عرض مؤشر التحميل
    if (loader) loader.style.display = 'block';

    fetch('api/add-lesson-note.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // إغلاق النافذة
            bootstrap.Modal.getInstance(document.getElementById('addNoteModal')).hide();
            
            // عرض رسالة نجاح
            toastr.success(data.message || 'تمت إضافة الملاحظة بنجاح');
            
            // تحديث واجهة المستخدم إذا كان هناك قائمة ملاحظات
            if (typeof updateNotesList === 'function') {
                updateNotesList(data.note);
            }
        } else {
            throw new Error(data.error || 'حدث خطأ أثناء إضافة الملاحظة');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error(error.message);
    })
    .finally(() => {
        if (loader) loader.style.display = 'none';
    });
}

// إضافة كود التبديل لإحصائيات كامل الكورس
$(function() {
    const toggleBtn = $('#toggleFullCourseStatsBtn');
    const content = $('#fullCourseStatsContent');
    
    // تحقق من وجود العناصر
    if (!toggleBtn.length || !content.length) {
        console.error('عناصر التبديل غير موجودة');
        return;
    }

    // استرجاع الحالة المحفوظة
    const isHidden = localStorage.getItem('fullCourseStatsHidden') === 'true';
    
    // تطبيق الحالة المحفوظة عند التحميل
    if (isHidden) {
        content.hide();
        toggleBtn.find('i').removeClass('fa-eye-slash').addClass('fa-eye');
    }

    // معالج حدث النقر
    toggleBtn.on('click', function(e) {
        e.preventDefault();
        
        const isCurrentlyHidden = content.is(':hidden');
        const icon = toggleBtn.find('i');

        // تبديل العرض مع تأثير حركي
        content.slideToggle({
            duration: 300,
            start: function() {
                // تحديث الأيقونة
                icon.toggleClass('fa-eye-slash fa-eye');
            },
            complete: function() {
                // حفظ الحالة
                localStorage.setItem('fullCourseStatsHidden', !isCurrentlyHidden);
            }
        });
    });
});

// إضافة تأثير حركي للمحتوى
$('#fullCourseStatsContent').css({
    transition: 'all 0.3s ease-in-out'
});
</script>
