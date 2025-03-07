/**
 * ملف JavaScript الرئيسي للدروس
 * يحتوي على الدوال الأساسية لتحديث الإحصائيات وإدارة حالات الدروس
 */

// تعريف المتغيرات العامة
let courseId = null;
let currentFilters = {};
let currentPage = 1;
let isInitialized = false;

// دالة تحميل ألوان الحالات
function loadStatusColors() {
    // جلب جميع عناصر select الخاصة بالحالات
    $('.status-select').each(function() {
        const select = $(this);
        const statusId = select.val();
        
        if (statusId) {
            // جلب معلومات الحالة من السيرفر
            $.ajax({
                url: 'api/get-status-info.php',
                method: 'GET',
                data: { status_id: statusId },
                success: function(response) {
                    if (response.success) {
                        // تحديث لون السيلكت
                        updateSelectStyle(select, response.status);
                    }
                }
            });
        }
    });
}

// دالة التهيئة الرئيسية
function initializeLessons() {
    if (isInitialized) return;
    isInitialized = true;

    // تحديد معرف الكورس من URL
    const urlParams = new URLSearchParams(window.location.search);
    courseId = urlParams.get('course_id');
    currentPage = parseInt(urlParams.get('page')) || 1;
    
    if (!courseId) {
        console.error('Course ID is required');
        return;
    }
    
    // تهيئة Select2
    if (typeof $.fn.select2 !== 'undefined') {
        initializeSelect2();
    } else {
        console.error('Select2 not loaded');
    }
    
    // تهيئة مستمعي الأحداث
    initializeEventListeners();
    
    // تهيئة tooltips
    if (typeof bootstrap !== 'undefined') {
        initializeTooltips();
    }
    
    // التحديث الأولي
    updateFilters();
    
    // تحميل ألوان الحالات
    loadStatusColors();
}

// تحديث دالة تهيئة Select2
function initializeSelect2() {
    $('.form-select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        language: {
            noResults: function() {
                return "لا توجد نتائج";
            }
        },
        // إضافة خيارات جديدة لتحسين المظهر
        templateResult: formatOption,
        templateSelection: formatOption,
        dropdownCssClass: 'select2-dropdown-large', // إضافة كلاس للقائمة المنسدلة
        selectionCssClass: 'select2-selection-large' // إضافة كلاس لمربع الاختيار
    }).on('select2:select', function(e) {
        const selectId = $(this).attr('id');
        currentFilters[selectId] = e.params.data.id;
        
        // حفظ القيمة في localStorage
        if ($(this).hasClass('status-select')) {
            saveStatusesToLocalStorage();
        }
        
        // تحديث الفلترة
        updateFilters();
    });
}

// دالة تنسيق خيارات Select2
function formatOption(option) {
    if (!option.id) {
        return option.text;
    }

    // إضافة تنسيق خاص للخيارات
    return $(`<span class="select2-option">
        <i class="fas fa-folder me-2"></i>
        ${option.text}
    </span>`);
}

// دالة حفظ الفلاتر الحالية
function saveCurrentFilters() {
    $('.form-select').each(function() {
        const selectId = $(this).attr('id');
        currentFilters[selectId] = $(this).val();
    });
}

// دالة تهيئة مستمعي الأحداث
function initializeEventListeners() {
    // مستمعي تغيير الفلاتر
    $('#searchFilter').on('input', debounce(updateFilters, 300));
    $('.filters-section input[type="checkbox"]').on('change', updateFilters);
    
    // مستمع تغيير الصفحة
    $(document).on('click', '.pagination .page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page) {
            currentPage = page;
            updateFilters();
        }
    });
    
    // مستمع تغيير حالة الدرس
    $(document).on('change', '.status-select', function() {
        const lessonId = $(this).data('lesson-id');
        const statusId = $(this).val();
        updateLessonStatus(lessonId, statusId);
    });
}

// دالة تحديث حالة الدرس
function updateLessonStatus(lessonId, statusId) {
    showLoader();
    
    $.ajax({
        url: 'api/update-lesson-status.php',
        method: 'POST',
        data: {
            lesson_id: lessonId,
            status_id: statusId
        },
        success: function(response) {
            if (response.success) {
                toastr.success('تم تحديث حالة الدرس بنجاح');
                
                // تحديث القيمة في السيلكت
                const select = $(`select[data-lesson-id="${lessonId}"]`);
                select.val(statusId).trigger('change.select2');
                
                // تحديث لون الخلفية
                updateSelectStyle(select, response.status);
                
                // تحديث الإحصائيات
                updateStats(response.stats);
                
                // حفظ الحالة في localStorage
                saveStatusesToLocalStorage();
                
                // تحديث ألوان جميع السيلكت
                loadStatusColors();
            } else {
                toastr.error(response.message || 'حدث خطأ أثناء تحديث الحالة');
                // إعادة القيمة السابقة
                const select = $(`select[data-lesson-id="${lessonId}"]`);
                select.val(response.previous_status).trigger('change.select2');
            }
        },
        error: function() {
            toastr.error('حدث خطأ في الاتصال بالخادم');
        },
        complete: function() {
            hideLoader();
        }
    });
}

// دالة تحديث أسلوب السيلكت
function updateSelectStyle(select, status) {
    select.css({
        'background-color': status.color,
        'color': status.text_color
    });
}

// دوال مساعدة للتحميل
function showLoader() {
    $('.loader').show();
}

function hideLoader() {
    $('.loader').hide();
}

// دالة تحديث الفلترة
function updateFilters() {
    const filters = {
        course_id: courseId,
        section: $('#sectionFilter').val(),
        status: $('#statusFilter').val(),
        duration: $('#durationFilter').val(),
        search: $('#searchFilter').val(),
        important: $('#importantFilter').is(':checked'),
        theory: $('#theoryFilter').is(':checked'),
        hideTheory: $('#hideTheoryFilter').is(':checked'),
        page: currentPage
    };

    // حفظ الفلاتر الحالية
    Object.assign(currentFilters, filters);

    // إظهار مؤشر التحميل
    showLoader();

    // إرسال طلب AJAX
    $.ajax({
        url: 'api/filter-lessons.php',
        method: 'POST',
        data: filters,
        success: function(response) {
            if (response.success) {
                updateLessonsDisplay(response.lessons);
                updatePagination(response.pagination);
                updateStats(response.stats);
            } else {
                toastr.error(response.message || 'حدث خطأ أثناء تحديث النتائج');
            }
        },
        error: function() {
            toastr.error('حدث خطأ في الاتصال بالخادم');
        },
        complete: function() {
            hideLoader();
        }
    });
}

// دالة تحديث عرض الدروس
function updateLessonsDisplay(lessons) {
    const container = $('.lessons-container');
    container.empty();

    if (lessons.length === 0) {
        container.html('<div class="alert alert-info">لا توجد دروس تطابق معايير البحث</div>');
        return;
    }

    lessons.forEach(lesson => {
        // تجاهل الدروس النظرية إذا تم تفعيل خيار الإخفاء
        if ($('#hideTheoryFilter').is(':checked') && lesson.is_theory) {
            return;
        }

        const card = createLessonCard(lesson);
        container.append(card);
    });

    // استعادة قيم السيلكت
    restoreSelectValues();
}

// دالة إنشاء بطاقة الدرس
function createLessonCard(lesson) {
    return `
        <div class="col-md-6 col-lg-4">
            <div class="card lesson-card h-100 ${lesson.is_theory ? 'theory' : ''} ${lesson.is_important ? 'important' : ''}">
                <div class="card-body">
                    <h5 class="card-title">${lesson.title}</h5>
                    <div class="lesson-meta mt-3">
                        <!-- معلومات الدرس -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-info">
                                <i class="fas fa-clock me-1"></i>
                                ${formatDuration(lesson.duration)}
                            </span>
                            <select class="form-select status-select" 
                                    data-lesson-id="${lesson.id}"
                                    style="background-color: ${lesson.status_color || ''}; color: ${lesson.status_text_color || ''};">
                                <option value="">اختر الحالة</option>
                                ${generateStatusOptions(lesson.status_id)}
                            </select>
                        </div>
                        
                        <!-- أزرار التحكم -->
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm ${lesson.is_important ? 'btn-warning' : 'btn-outline-warning'}"
                                    onclick="toggleImportance(${lesson.id})"
                                    title="تحديد كدرس مهم">
                                <i class="fas fa-star"></i>
                            </button>
                            <button class="btn btn-sm ${lesson.is_theory ? 'btn-info' : 'btn-outline-info'}"
                                    onclick="toggleTheory(${lesson.id})"
                                    title="تحديد كدرس نظري">
                                <i class="fas fa-book"></i>
                            </button>
                            <button class="btn btn-sm btn-danger"
                                    onclick="deleteLesson(${lesson.id})"
                                    title="حذف الدرس">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// تحديث الترقيم
function updatePagination(pagination) {
    $('.pagination-container').html(pagination);
}

// دالة debounce لتحسين الأداء
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// دالة استعادة قيم السيلكت
function restoreSelectValues() {
    // استعادة قيم الفلاتر
    Object.keys(currentFilters).forEach(selectId => {
        const value = currentFilters[selectId];
        if (value) {
            $(`#${selectId}`).val(value).trigger('change');
        }
    });
    
    // استعادة حالات الدروس
    restoreStatusesFromLocalStorage();
}

// دالة تهيئة tooltips
function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// تحديث دالة saveStatusesToLocalStorage
function saveStatusesToLocalStorage() {
    const statuses = {};
    $('.status-select').each(function() {
        const lessonId = $(this).data('lesson-id');
        const statusId = $(this).val();
        if (statusId) {
            statuses[lessonId] = statusId;
        }
    });
    localStorage.setItem('lesson_statuses', JSON.stringify(statuses));
}

// تحديث دالة restoreStatusesFromLocalStorage
function restoreStatusesFromLocalStorage() {
    const statuses = JSON.parse(localStorage.getItem('lesson_statuses') || '{}');
    Object.entries(statuses).forEach(([lessonId, statusId]) => {
        const select = $(`.status-select[data-lesson-id="${lessonId}"]`);
        if (select.length) {
            select.val(statusId).trigger('change');
        }
    });
}

/**
 * دالة تبديل حالة الأهمية للدرس
 * @param {number} lessonId - معرف الدرس
 * @returns {Promise<void>}
 */
async function toggleImportance(lessonId) {
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
            // تحديث واجهة المستخدم
            const button = document.querySelector(`button[onclick*="toggleImportance(${lessonId})"]`);
            if (!button) {
                console.error('Button not found');
                return;
            }

            const row = button.closest('tr');
            if (!row) {
                console.error('Row not found');
                return;
            }

            // تبديل الأزرار
            button.classList.toggle('btn-warning');
            button.classList.toggle('btn-outline-warning');

            // تحديث البادج في عمود العنوان
            const titleCell = row.querySelector('td:nth-child(2)');
            if (titleCell) {
                const tagsContainer = titleCell.querySelector('.tags');
                if (tagsContainer) {
                    const existingBadge = tagsContainer.querySelector('.badge.bg-warning');
                    if (existingBadge) {
                        existingBadge.remove();
                    } else {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-warning me-1';
                        badge.innerHTML = '<i class="fas fa-star me-1"></i>مهم';
                        tagsContainer.appendChild(badge);
                    }
                }
            }
            
            toastr.success('تم تحديث حالة الدرس بنجاح');
        }
    } catch (error) {
        console.error('Error:', error);
        toastr.error('حدث خطأ أثناء تحديث الحالة');
    }
}

/**
 * دالة تبديل حالة النظري للدرس
 * @param {number} lessonId - معرف الدرس
 * @returns {Promise<void>}
 */
async function toggleTheory(lessonId) {
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
            // تحديث واجهة المستخدم
            const button = document.querySelector(`button[onclick*="toggleTheory(${lessonId})"]`);
            if (!button) {
                console.error('Button not found');
                return;
            }

            const row = button.closest('tr');
            if (!row) {
                console.error('Row not found');
                return;
            }

            // تبديل الأزرار
            button.classList.toggle('btn-info');
            button.classList.toggle('btn-outline-info');

            // تحديث البادج في عمود العنوان
            const titleCell = row.querySelector('td:nth-child(2)');
            if (titleCell) {
                const tagsContainer = titleCell.querySelector('.tags');
                if (tagsContainer) {
                    const existingBadge = tagsContainer.querySelector('.badge.bg-info');
                    if (existingBadge) {
                        existingBadge.remove();
                    } else {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-info me-1';
                        badge.innerHTML = '<i class="fas fa-book me-1"></i>نظري';
                        tagsContainer.appendChild(badge);
                    }
                }
            }
            
            toastr.success('تم تحديث حالة الدرس بنجاح');
        }
    } catch (error) {
        console.error('Error:', error);
        toastr.error('حدث خطأ أثناء تحديث الحالة');
    }
}

/**
 * دالة حذف الدرس
 * @param {number} lessonId - معرف الدرس المراد حذفه
 * @returns {Promise<void>}
 * @requires SweetAlert2
 * @requires jQuery
 * @requires Toastr
 */
async function deleteLesson(lessonId) {
    try {
        // تأكيد الحذف باستخدام SweetAlert2
        const result = await Swal.fire({
            title: 'هل أنت متأكد؟',
            text: 'سيتم حذف الدرس نهائياً',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء',
            // تحسين مظهر النافذة للغة العربية
            customClass: {
                popup: 'swal2-rtl',
                title: 'swal2-title-rtl',
                content: 'swal2-content-rtl'
            }
        });

        if (result.isConfirmed) {
            // عرض مؤشر التحميل
            Swal.showLoading();

            const response = await fetch('api/delete-lesson.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ lesson_id: lessonId })
            });

            const data = await response.json();
            
            if (data.success) {
                // إخفاء مؤشر التحميل
                Swal.hideLoading();
                
                // إظهار رسالة النجاح
                await Swal.fire({
                    title: 'تم الحذف!',
                    text: 'تم حذف الدرس بنجاح',
                    icon: 'success',
                    customClass: {
                        popup: 'swal2-rtl'
                    }
                });

                // إزالة الصف من الجدول
                const row = document.querySelector(`tr[data-lesson-id="${lessonId}"]`);
                if (row) {
                    row.remove();
                    
                    // تحديث الإحصائيات
                    updateStatusStats();
                    updateProgressBar();
                    
                    // تحديث أرقام الدروس
                    updateLessonNumbers();
                } else {
                    console.error('Row not found');
                }
            } else {
                throw new Error(data.message || 'حدث خطأ أثناء حذف الدرس');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        
        // إظهار رسالة الخطأ
        await Swal.fire({
            title: 'خطأ!',
            text: error.message || 'حدث خطأ غير متوقع',
            icon: 'error',
            customClass: {
                popup: 'swal2-rtl'
            }
        });
    }
}

/**
 * دالة تحديث أرقام الدروس في الجدول
 */
function updateLessonNumbers() {
    const rows = document.querySelectorAll('table tbody tr');
    rows.forEach((row, index) => {
        const numberCell = row.querySelector('td:first-child');
        if (numberCell) {
            numberCell.textContent = index + 1;
        }
    });
}

// إضافة CSS لدعم RTL في SweetAlert2
const style = document.createElement('style');
style.textContent = `
    .swal2-rtl {
        direction: rtl;
        text-align: right;
    }
    .swal2-rtl .swal2-title,
    .swal2-rtl .swal2-content {
        text-align: right;
    }
    .swal2-rtl .swal2-actions {
        justify-content: flex-start;
    }
`;
document.head.appendChild(style);

/**
 * دالة تحديث إحصائيات الحالات
 * @param {Object} stats - إحصائيات الحالات
 */
function updateStatusStats() {
    try {
        const stats = {};
        const totalLessons = document.querySelectorAll('.status-select').length;
        
        document.querySelectorAll('.status-select').forEach(select => {
            const statusId = select.value;
            if (statusId) {
                stats[statusId] = (stats[statusId] || 0) + 1;
            }
        });
        
        document.querySelectorAll('.status-stats .card').forEach(card => {
            const statusId = card.dataset.statusId;
            const count = stats[statusId] || 0;
            const percentage = (count / totalLessons) * 100;
            
            const countElement = card.querySelector('.status-count');
            const progressBar = card.querySelector('.progress-bar');
            
            if (countElement) countElement.textContent = count;
            if (progressBar) progressBar.style.width = `${percentage}%`;
        });
    } catch (error) {
        console.error('Error updating status stats:', error);
    }
}

// تحديث حالة اكتمال الدرس
async function updateLessonCompletion(btn) {
    try {
        const lessonId = btn.dataset.lessonId;
        const currentCompleted = btn.classList.contains('btn-success');
        const newCompleted = !currentCompleted;

        const response = await fetch('../api/update-lesson-completion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                lesson_id: lessonId,
                completed: newCompleted ? 1 : 0
            })
        });

        if (response.ok) {
            const data = await response.json();
            if (data.success) {
                btn.classList.toggle('btn-success');
                btn.classList.toggle('btn-outline-success');
                updateLessonsStats();
                updateStatusStats();
                toastr.success('تم تحديث حالة الدرس بنجاح');
            }
        }
    } catch (error) {
        console.error('Error updating lesson completion:', error);
        toastr.error('حدث خطأ أثناء تحديث حالة الدرس');
    }
}

// تهيئة الصفحة
document.addEventListener('DOMContentLoaded', function() {
    try {
        // تحديث الإحصائيات الأولية
        updateLessonsStats();
        updateStatusStats();

        // إضافة مستمعي الأحداث
        document.querySelectorAll('.completion-btn').forEach(btn => {
            btn.addEventListener('click', () => updateLessonCompletion(btn));
        });
    } catch (error) {
        console.error('Error in initialization:', error);
    }
}); 