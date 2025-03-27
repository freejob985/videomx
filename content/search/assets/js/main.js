$(document).ready(function() {
    // Initialize toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "rtl": true
    };

    // Initialize view preference from localStorage
    const savedView = localStorage.getItem('preferredView') || 'grid';
    
    // Update buttons and containers based on saved view
    $('[data-view]').removeClass('active');
    $(`[data-view="${savedView}"]`).addClass('active');
    
    // Show correct view container before loading data
    if (savedView === 'table') {
        $('#gridViewContainer').addClass('d-none');
        $('#tableViewContainer').removeClass('d-none');
    } else {
        $('#tableViewContainer').addClass('d-none');
        $('#gridViewContainer').removeClass('d-none');
    }

    // Initialize Bootstrap Tags Input
    if ($.fn.tagsinput) {
        $('#tagsInput').tagsinput({
            trimValue: true,
            tagClass: function(item) {
                return 'badge bg-primary';
            }
        });
    }

    // Load initial data after setting up view
    loadFilters();
    loadLessons(1);

    // Handle form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        loadLessons(1);
    });

    // Handle filter changes
    $('.form-select').on('change', function() {
        loadLessons(1);
    });

    // Handle search input
    let searchTimeout;
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadLessons(1);
        }, 500);
    });

    // Add event handlers for lesson updates
    $('#toggleCompletedBtn').on('click', function() {
        const newStatus = !currentLesson.completed;
        updateLessonStatus({ completed: newStatus });
    });

    $('#editTagsBtn').on('click', function() {
        if (currentLesson && currentLesson.tags) {
            $('#tagsInput').tagsinput('removeAll');
            if (Array.isArray(currentLesson.tags)) {
                currentLesson.tags.forEach(tag => {
                    if (tag) $('#tagsInput').tagsinput('add', tag.trim());
                });
            } else if (typeof currentLesson.tags === 'string') {
                currentLesson.tags.split(',').forEach(tag => {
                    if (tag) $('#tagsInput').tagsinput('add', tag.trim());
                });
            }
        }
        const tagsModal = new bootstrap.Modal(document.getElementById('tagsModal'));
        tagsModal.show();
    });

    $('#saveTagsBtn').on('click', function() {
        const tags = $('#tagsInput').val();
        updateLessonStatus({ tags: tags });
        bootstrap.Modal.getInstance(document.getElementById('tagsModal')).hide();
    });

    // Note management event handlers
    $('#addNoteBtn').on('click', function() {
        $('#noteId').val('');
        $('#noteForm')[0].reset();
        $('#noteType').trigger('change');
        const noteModal = new bootstrap.Modal(document.getElementById('noteModal'));
        noteModal.show();
    });

    $('#noteType').on('change', function() {
        const type = $(this).val();
        $('#codeLangGroup').toggleClass('d-none', type !== 'code');
        $('#linkGroup').toggleClass('d-none', type !== 'link');
    });

    $('#saveNoteBtn').on('click', function() {
        const noteId = $('#noteId').val();
        const noteData = {
            lesson_id: currentLessonId,
            title: $('#noteTitle').val(),
            content: $('#noteContent').val(),
            type: $('#noteType').val()
        };

        if (noteData.type === 'code') {
            noteData.code_language = $('#codeLanguage').val();
        } else if (noteData.type === 'link') {
            noteData.link_url = $('#linkUrl').val();
            noteData.link_description = $('#linkDescription').val();
        }

        const action = noteId ? 'update' : 'add';
        if (action === 'update') {
            noteData.note_id = noteId;
        }

        $.ajax({
            url: 'api/manage_note.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: action,
                ...noteData
            }),
            success: function(response) {
                if (response.success) {
                    bootstrap.Modal.getInstance(document.getElementById('noteModal')).hide();
                    // Reload lesson details to get updated notes
                    openLessonModal(currentLessonId);
                    toastr.success('تم حفظ الملاحظة بنجاح');
                }
            },
            error: function() {
                toastr.error('حدث خطأ أثناء حفظ الملاحظة');
            }
        });
    });

    // Clean up when modal is closed
    $('#videoModal').on('hidden.bs.modal', function () {
        $('#videoFrame').attr('src', '');
    });

    // Per page select handler
    $('#perPageSelect').on('change', function() {
        loadLessons(1);
    });

    // View toggle handlers with localStorage
    $('[data-view]').on('click', function() {
        const view = $(this).data('view');
        localStorage.setItem('preferredView', view);
        $('[data-view]').removeClass('active');
        $(this).addClass('active');
        
        if (view === 'grid') {
            $('#gridViewContainer').removeClass('d-none');
            $('#tableViewContainer').addClass('d-none');
        } else {
            $('#gridViewContainer').addClass('d-none');
            $('#tableViewContainer').removeClass('d-none');
        }
        
        loadLessons(currentPage);
    });

    // Load languages on page load
    loadLanguages(1);

    // تهيئة السليكت بوكس الخاص باللغات
    initializeLanguageFilter();

    // مستمع حدث تغيير اللغة
    $('#languageFilter').on('change', function() {
        const languageId = $(this).val();
        updateFilters(languageId);
    });
});

// Global variables
let currentLessonId = null;
let currentLesson = null;
let currentPage = 1;
let currentLessonIndex = -1;
let lessonsList = [];
let currentLessonLanguageId = null;

function loadFilters() {
    // تهيئة فلتر اللغات
    initializeLanguageFilter();

    // إضافة مستمع حدث واحد لتغيير اللغة
    $('#languageFilter').on('change', function() {
        const languageId = $(this).val();
        if (languageId) {
            // تحديث جميع الفلاتر المعتمدة على اللغة في وقت واحد
            Promise.all([
                updateStatusFilter(languageId),
                updateSections(languageId),
                updateCourses(languageId)
            ]).then(() => {
                // إعادة تحميل الدروس بعد تحديث جميع الفلاتر
                loadLessons(1);
            }).catch(error => {
                console.error('خطأ في تحديث الفلاتر:', error);
                toastr.error('حدث خطأ أثناء تحديث الفلاتر');
            });
        } else {
            // إفراغ جميع الفلاتر عند عدم اختيار لغة
            resetFilters();
        }
    });
}

/**
 * إعادة تعيين جميع الفلاتر إلى الحالة الافتراضية
 */
function resetFilters() {
    $('#statusFilter').html('<option value="">اختر الحالة</option>');
    $('#sectionFilter').html('<option value="">اختر القسم</option>');
    $('#courseFilter').html('<option value="">اختر الكورس</option>');
    loadLessons(1);
}

/**
 * تحديث فلتر الحالات
 * @param {number} languageId - معرف اللغة
 * @returns {Promise} وعد بإكمال التحديث
 */
function updateStatusFilter(languageId) {
    return new Promise((resolve, reject) => {
        const statusSelect = $('#statusFilter');
        statusSelect.prop('disabled', true);

        $.ajax({
            url: 'api/get_statuses.php',
            method: 'GET',
            data: { language_id: languageId },
            dataType: 'json',
            success: function(response) {
                if (response && response.status === 'success' && Array.isArray(response.data)) {
                    statusSelect.html('<option value="">اختر الحالة</option>');
                    response.data.forEach(status => {
                        statusSelect.append(`<option value="${status.id}">${status.name}</option>`);
                    });
                    resolve();
                } else {
                    reject('تنسيق الاستجابة غير صالح');
                }
            },
            error: function(xhr, status, error) {
                reject(error);
            },
            complete: function() {
                statusSelect.prop('disabled', false);
            }
        });
    });
}

/**
 * تحديث فلتر الأقسام
 * @param {number} languageId - معرف اللغة
 * @returns {Promise} وعد بإكمال التحديث
 */
function updateSections(languageId) {
    return new Promise((resolve, reject) => {
        const sectionSelect = $('#sectionFilter');
        sectionSelect.prop('disabled', true);

        $.ajax({
            url: 'api/get_sections_by_language.php',
            method: 'GET',
            data: { language_id: languageId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.sections) {
                    sectionSelect.html('<option value="">اختر القسم</option>');
                    response.sections.forEach(section => {
                        sectionSelect.append(
                            $('<option>', {
                                value: section.id,
                                text: section.name,
                                title: section.description || ''
                            })
                        );
                    });
                    resolve();
                } else {
                    reject(response.error || 'خطأ في جلب الأقسام');
                }
            },
            error: function(xhr, status, error) {
                reject(error);
            },
            complete: function() {
                sectionSelect.prop('disabled', false);
            }
        });
    });
}

/**
 * تحديث فلتر الكورسات
 * @param {number} languageId - معرف اللغة
 * @returns {Promise} وعد بإكمال التحديث
 */
function updateCourses(languageId) {
    return new Promise((resolve, reject) => {
        const courseSelect = $('#courseFilter');
        courseSelect.prop('disabled', true);

        $.ajax({
            url: 'api/get_courses_by_language.php',
            method: 'GET',
            data: { language_id: languageId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.courses) {
                    courseSelect.html('<option value="">اختر الكورس</option>');
                    response.courses.forEach(course => {
                        courseSelect.append(
                            $('<option>', {
                                value: course.id,
                                text: course.name
                            })
                        );
                    });
                    resolve();
                } else {
                    reject(response.error || 'خطأ في جلب الكورسات');
                }
            },
            error: function(xhr, status, error) {
                reject(error);
            },
            complete: function() {
                courseSelect.prop('disabled', false);
            }
        });
    });
}

// تحديث دالة initializeLanguageFilter لإزالة مستمع الحدث المكرر
function initializeLanguageFilter() {
    $.ajax({
        url: 'api/get_languages.php',
        method: 'GET',
        data: { 
            per_page: 100
        },
        dataType: 'json',
        success: function(response) {
            if (response && response.languages) {
                const languageSelect = $('#languageFilter');
                languageSelect.html('<option value="">اختر اللغة</option>');
                
                response.languages.forEach(language => {
                    languageSelect.append(`<option value="${language.id}">${language.name}</option>`);
                });
            } else {
                console.error('Invalid response format:', response);
                toastr.error('تنسيق الاستجابة غير صالح');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading languages:', error);
            toastr.error('حدث خطأ أثناء تحميل اللغات');
        }
    });
}

function loadSections() {
    const languageId = $('#languageFilter').val();
    const url = 'api/get_sections.php?language_id=' + (languageId ? languageId : '');
    
    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            const sectionFilter = $('#sectionFilter');
            sectionFilter.html('<option value="">اختر القسم</option>');
            
            if (data && data.sections && Array.isArray(data.sections)) {
                data.sections.forEach(section => {
                    sectionFilter.append(`<option value="${section.id}">${section.name}</option>`);
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading sections:', error);
            toastr.error('حدث خطأ أثناء تحميل الأقسام');
        }
    });
}

function loadCourses() {
    const languageId = $('#languageFilter').val();
    const url = 'api/get_filters.php?type=courses' + (languageId ? `&language_id=${languageId}` : '');
    
    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            const courseFilter = $('#courseFilter');
            courseFilter.html('<option value="">اختر الكورس</option>');
            
            if (Array.isArray(data)) {
                data.forEach(course => {
                    courseFilter.append(`<option value="${course.id}">${course.name}</option>`);
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading courses:', error);
            toastr.error('حدث خطأ أثناء تحميل الكورسات');
        }
    });
}

function loadLessons(page) {
    currentPage = page;
    const params = {
        page: page,
        per_page: 21,
        language_id: $('#languageFilter').val(),
        status_id: $('#statusFilter').val(),
        section_id: $('#sectionFilter').val(),
        course_id: $('#courseFilter').val(),
        search: $('#searchInput').val()
    };

    $.get('api/get_lessons.php', params, function(response) {
        lessonsList = response.lessons; // Store lessons globally
        const view = $('[data-view].active').data('view');
        
        if (view === 'table') {
            displayLessonsTable(response.lessons);
        } else {
            displayLessonsGrid(response.lessons);
        }
        
        displayPagination(response.pages, page);
        displayStats(response.stats);
    }).fail(function() {
        toastr.error('حدث خطأ أثناء تحميل الدروس');
    });
}

function displayLessonsGrid(lessons) {
    const grid = $('#lessonsGrid');
    grid.empty();

    lessons.forEach(lesson => {
        // Skip if required fields are empty
        if (!lesson.title || !lesson.thumbnail) return;

        // Format duration to remove leading zeros
        const duration = lesson.duration_formatted.replace(/^00:/, '');

        // Generate tags HTML only if tags exist
        const tags = lesson.tags ? lesson.tags.split(',').map(tag => tag.trim()).filter(Boolean) : [];
        const tagsHtml = tags.length > 0 ? 
            `<div class="tags-container">
                ${tags.map(tag => 
                    `<span class="tag" title="${tag}">
                        <i class="fas fa-tag"></i>
                        <span class="tag-text">${tag}</span>
                     </span>`
                ).join('')}
            </div>` : 
            `<div class="tags-container">
                <span class="no-tags">لا توجد وسوم</span>
            </div>`;

        // Determine card classes based on lesson status
        const cardClasses = [
            'lesson-card',
            lesson.is_theory ? 'theory' : '',
            lesson.is_important ? 'important' : '',
            lesson.completed ? 'completed' : ''
        ].filter(Boolean).join(' ');

        grid.append(`
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card shadow-sm ${cardClasses}">
                    <div class="card-header">
                        <h5 class="text-truncate">${lesson.title}</h5>
                    </div>
                    <div class="card-img-wrapper" onclick="openLessonModal(${lesson.id})">
                        <img src="${lesson.thumbnail}" class="card-img-top" alt="${lesson.title}">
                        <div class="play-overlay">
                            <div class="play-button">
                                <i class="fas fa-play"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="meta-info">
                            ${duration ? `<span><i class="fas fa-clock"></i>${duration}</span>` : ''}
                            ${lesson.section_name ? `<span><i class="fas fa-folder"></i>${lesson.section_name}</span>` : ''}
                        </div>
                        
                        ${lesson.course_title ? `
                            <div>
                                <span><i class="fas fa-graduation-cap"></i>${lesson.course_title}</span>
                            </div>
                        ` : ''}
                        
                        <div class="status-badges mt-3">
                            ${lesson.is_important ? '<span class="status-badge important"><i class="fas fa-star"></i>مهم</span>' : ''}
                            ${lesson.is_theory ? '<span class="status-badge theory"><i class="fas fa-book"></i>نظري</span>' : ''}
                            ${lesson.completed ? '<span class="status-badge completed"><i class="fas fa-check"></i>مكتمل</span>' : ''}
                        </div>
                        
                        ${tagsHtml}
                    </div>
                    <div class="card-footer">
                        <button onclick="openLessonModal(${lesson.id})" class="btn btn-primary w-100">
                            <i class="fas fa-play-circle"></i>مشاهدة الدرس
                        </button>
                    </div>
                </div>
            </div>
        `);
    });

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
}

function displayLessonsTable(lessons) {
    const table = $('#lessonsTable');
    table.empty();

    lessons.forEach((lesson, index) => {
        // Skip if required fields are empty
        if (!lesson.title || !lesson.thumbnail) return;

        // Format duration to remove leading zeros
        const duration = lesson.duration_formatted.replace(/^00:/, '');

        // Generate tags HTML only if tags exist
        const tags = lesson.tags ? lesson.tags.split(',').map(tag => tag.trim()).filter(Boolean) : [];
        const tagsBadges = tags.length > 0 ? tags.map(tag => 
            `<span class="tag">
                <i class="fas fa-tag me-1"></i>${tag}
            </span>`
        ).join('') : '';

        table.append(`
            <tr>
                <td class="text-center">${index + 1}</td>
                <td>
                    <img src="${lesson.thumbnail}" 
                         class="lesson-thumbnail" 
                         alt="${lesson.title}"
                         onclick="openLessonModal(${lesson.id})">
                </td>
                <td>
                    <a href="#" class="lesson-title" onclick="openLessonModal(${lesson.id}); return false;">
                        ${lesson.title}
                    </a>
                    ${lesson.course_title ? `
                        <div class="lesson-meta mt-2">
                            <span class="badge bg-secondary">
                                <i class="fas fa-graduation-cap me-1"></i>
                                ${lesson.course_title}
                            </span>
                        </div>
                    ` : ''}
                </td>
                ${lesson.section_name ? `
                    <td class="text-center">
                        <span class="lesson-meta">
                            <i class="fas fa-folder"></i> ${lesson.section_name}
                        </span>
                    </td>
                ` : '<td></td>'}
                ${duration ? `
                    <td class="text-center">
                        <span class="lesson-meta">
                            <i class="fas fa-clock"></i> ${duration}
                        </span>
                    </td>
                ` : '<td></td>'}
                <td>
                    <div class="lesson-status">
                        ${lesson.is_important ? '<span class="badge bg-danger"><i class="fas fa-star me-1"></i>مهم</span>' : ''}
                        ${lesson.is_theory ? '<span class="badge bg-info"><i class="fas fa-book me-1"></i>نظري</span>' : ''}
                        ${lesson.completed ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i>مكتمل</span>' : ''}
                    </div>
                </td>
                <td>
                    <div class="lesson-tags">
                        ${tagsBadges || '<small class="text-muted">لا توجد وسوم</small>'}
                    </div>
                </td>
                <td>
                    <div class="lesson-actions">
                        <button class="btn btn-primary btn-action" 
                                onclick="openLessonModal(${lesson.id})"
                                title="مشاهدة الدرس">
                            <i class="fas fa-play"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `);
    });

    // Initialize table search
    $('#tableSearch').on('input', function() {
        const searchText = $(this).val().toLowerCase();
        $('#lessonsTable tr').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchText));
        });
    });
}

function displayPagination(totalPages, currentPage) {
    const pagination = $('#pagination');
    pagination.empty();

    // Previous button
    pagination.append(`
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadLessons(${currentPage - 1})">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `);

    // Current/Total pages indicator
    pagination.append(`
        <li class="page-item disabled">
            <span class="page-link">
                ${currentPage} / ${totalPages}
            </span>
        </li>
    `);

    // Next button
    pagination.append(`
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadLessons(${currentPage + 1})">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `);
}

function displayStats(stats) {
    const statsHtml = `
        <div class="row">
            <!-- إجمالي الدروس -->
            <div class="col-md-3 mb-4">
                <div class="card border-primary h-100">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-graduation-cap fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">إجمالي الدروس</h5>
                        <h2 class="display-4">${stats.total_lessons}</h2>
                    </div>
                </div>
            </div>

            <!-- الدروس المكتملة -->
            <div class="col-md-3 mb-4">
                <div class="card border-success h-100">
                    <div class="card-body">
                        <div class="mb-3 text-center">
                            <i class="fas fa-check-circle fa-3x text-success"></i>
                        </div>
                        <h5 class="card-title">الدروس المكتملة</h5>
                        <div class="d-flex justify-content-between align-items-center">
                            <h3>${stats.completed_lessons}</h3>
                            <span class="badge bg-success">${stats.completed_percentage}%</span>
                        </div>
                        <div class="progress mt-2" style="height: 10px;">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" 
                                role="progressbar" 
                                style="width: ${stats.completed_percentage}%" 
                                aria-valuenow="${stats.completed_percentage}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- الدروس المهمة -->
            <div class="col-md-3 mb-4">
                <div class="card border-danger h-100">
                    <div class="card-body">
                        <div class="mb-3 text-center">
                            <i class="fas fa-exclamation-circle fa-3x text-danger"></i>
                        </div>
                        <h5 class="card-title">الدروس المهمة</h5>
                        <div class="d-flex justify-content-between align-items-center">
                            <h3>${stats.important_lessons}</h3>
                            <span class="badge bg-danger">${stats.important_percentage}%</span>
                        </div>
                        <div class="progress mt-2" style="height: 10px;">
                            <div class="progress-bar bg-danger progress-bar-striped progress-bar-animated" 
                                role="progressbar" 
                                style="width: ${stats.important_percentage}%" 
                                aria-valuenow="${stats.important_percentage}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- الدروس النظرية -->
            <div class="col-md-3 mb-4">
                <div class="card border-info h-100">
                    <div class="card-body">
                        <div class="mb-3 text-center">
                            <i class="fas fa-book fa-3x text-info"></i>
                        </div>
                        <h5 class="card-title">الدروس النظرية</h5>
                        <div class="d-flex justify-content-between align-items-center">
                            <h3>${stats.theory_lessons}</h3>
                            <span class="badge bg-info">${stats.theory_percentage}%</span>
                        </div>
                        <div class="progress mt-2" style="height: 10px;">
                            <div class="progress-bar bg-info progress-bar-striped progress-bar-animated" 
                                role="progressbar" 
                                style="width: ${stats.theory_percentage}%" 
                                aria-valuenow="${stats.theory_percentage}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Time Statistics -->
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="card border-dark">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-clock fa-2x text-dark me-2"></i>
                            <h5 class="card-title mb-0">إحصائيات الوقت</h5>
                        </div>
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-hourglass fa-2x text-primary mb-2"></i>
                                    <h6>الوقت الإجمالي</h6>
                                    <h4>${stats.total_duration_formatted}</h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-check-double fa-2x text-success mb-2"></i>
                                    <h6>الوقت المكتمل</h6>
                                    <h4 class="text-success">${stats.completed_duration_formatted}</h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-hourglass-half fa-2x text-danger mb-2"></i>
                                    <h6>الوقت المتبقي</h6>
                                    <h4 class="text-danger">${stats.remaining_duration_formatted}</h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-percentage fa-2x text-primary mb-2"></i>
                                    <h6>نسبة الإكمال</h6>
                                    <h4>${stats.duration_percentage}%</h4>
                                </div>
                            </div>
                        </div>
                        <div class="progress mt-3" style="height: 15px;">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" 
                                role="progressbar" 
                                style="width: ${stats.duration_percentage}%" 
                                aria-valuenow="${stats.duration_percentage}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                                ${stats.duration_percentage}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#statsContainer').html(statsHtml);
}

// Update the openLessonModal function
function openLessonModal(lessonId) {
    currentLessonId = lessonId;
    currentLessonIndex = lessonsList.findIndex(lesson => lesson.id === lessonId);
    
    // Show modal first
    const modal = new bootstrap.Modal(document.getElementById('videoModal'));
    modal.show();
    
    updateNavigationButtons();
    loadLessonDetails(lessonId);
}

// Add new functions for navigation
function updateNavigationButtons() {
    const hasPrev = currentLessonIndex > 0;
    const hasNext = currentLessonIndex < lessonsList.length - 1;
    
    $('#prevLessonBtn')
        .prop('disabled', !hasPrev)
        .off('click')
        .on('click', () => {
            if (hasPrev) {
                const prevLesson = lessonsList[currentLessonIndex - 1];
                loadLessonDetails(prevLesson.id);
            }
        });
    
    $('#nextLessonBtn')
        .prop('disabled', !hasNext)
        .off('click')
        .on('click', () => {
            if (hasNext) {
                const nextLesson = lessonsList[currentLessonIndex + 1];
                loadLessonDetails(nextLesson.id);
            }
        });
}

function loadLessonDetails(lessonId) {
    $.get(`api/get_lesson_details.php?lesson_id=${lessonId}`, function(response) {
        if (response.success) {
            currentLesson = response.lesson;
            currentLessonId = lessonId;
            currentLessonLanguageId = response.lesson.language_id;
            currentLessonIndex = lessonsList.findIndex(lesson => lesson.id === lessonId);
            
            // Update modal title
            $('#videoModalLabel').text(currentLesson.title);
            
            // Update video URL
            let videoUrl = currentLesson.video_url;
            if (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be')) {
                const videoId = extractYouTubeId(videoUrl);
                videoUrl = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
            }
            $('#videoFrame').attr('src', videoUrl);
            
            // إضافة زر تحديث القسم في شريط العنوان
            const modalHeader = $('#videoModal .modal-header .ms-auto');
            if (!modalHeader.find('#editSectionBtn').length) {
                const sectionEditBtn = $(`
                    <button id="editSectionBtn" class="btn btn-outline-primary btn-sm me-2">
                        <i class="fas fa-folder-open"></i> تعديل القسم
                    </button>
                `);
                sectionEditBtn.on('click', () => openSectionEditModal(currentLessonLanguageId));
                modalHeader.prepend(sectionEditBtn);
            }
            
            // Update other elements
            updateNavigationButtons();
            updateLessonStatusButtons();
            displayNotes(response.notes);
            displayTags(currentLesson.tags);
            displayTranscript(currentLesson.transcript);
        } else {
            toastr.error(response.error || 'حدث خطأ أثناء تحميل تفاصيل الدرس');
        }
    }).fail(function() {
        toastr.error('حدث خطأ أثناء تحميل تفاصيل الدرس');
    });
}

// Helper function to extract YouTube video ID
function extractYouTubeId(url) {
    let videoId = '';
    if (url.includes('youtube.com/watch?v=')) {
        videoId = url.split('watch?v=')[1].split('&')[0];
    } else if (url.includes('youtu.be/')) {
        videoId = url.split('youtu.be/')[1];
    }
    return videoId;
}

// Add function to update lesson status buttons
function updateLessonStatusButtons() {
    // Update completed status button
    $('#toggleCompletedBtn')
        .removeClass('btn-outline-success btn-success')
        .addClass(currentLesson.completed ? 'btn-success' : 'btn-outline-success')
        .html(`<i class="fas fa-check"></i> ${currentLesson.completed ? 'مكتمل' : 'غير مكتمل'}`);
        
    // Update important status in edit lesson modal
    $('#lessonImportantCheck').prop('checked', currentLesson.is_important);
    
    // Update theory status in edit lesson modal
    $('#lessonTheoryCheck').prop('checked', currentLesson.is_theory);
}

// Function to update lesson status
function updateLessonStatus(updates) {
    $.ajax({
        url: 'api/update_lesson.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            lesson_id: currentLessonId,
            ...updates
        }),
        success: function(response) {
            if (response.success) {
                // Update current lesson object
                Object.assign(currentLesson, updates);
                
                // Update UI
                if (updates.hasOwnProperty('completed')) {
                    currentLesson.completed = updates.completed;
                    updateLessonStatusButtons();
                }
                if (updates.hasOwnProperty('tags')) {
                    currentLesson.tags = updates.tags.split(',').map(tag => tag.trim());
                    displayTags(currentLesson.tags);
                }
                
                toastr.success('تم تحديث الدرس بنجاح');
                
                // Reload lessons to update the grid
                loadLessons(currentPage);
            }
        },
        error: function() {
            toastr.error('حدث خطأ أثناء تحديث الدرس');
        }
    });
}

// Note management functions
function editNote(noteId) {
    if (!currentLesson || !currentLesson.notes) {
        Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: 'لا يمكن تحميل الملاحظة'
        });
        return;
    }

    const note = currentLesson.notes.find(n => n.id === noteId);
    if (note) {
        $('#noteId').val(note.id);
        $('#noteTitle').val(note.title);
        $('#noteType').val(note.type).trigger('change');
        $('#noteContent').val(note.content);
        
        if (note.type === 'code') {
            $('#codeLanguage').val(note.code_language);
        } else if (note.type === 'link') {
            $('#linkUrl').val(note.link_url);
            $('#linkDescription').val(note.link_description);
        }
        
        const noteModal = new bootstrap.Modal(document.getElementById('noteModal'));
        noteModal.show();
    } else {
        Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: 'الملاحظة غير موجودة'
        });
    }
}

function deleteNote(noteId) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: 'لا يمكن التراجع عن هذا الإجراء!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، احذف!',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'api/manage_note.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    action: 'delete',
                    note_id: noteId
                }),
                success: function(response) {
                    if (response.success) {
                        openLessonModal(currentLessonId);
                        Swal.fire(
                            'تم الحذف!',
                            'تم حذف الملاحظة بنجاح.',
                            'success'
                        );
                    }
                },
                error: function() {
                    Swal.fire(
                        'خطأ!',
                        'حدث خطأ أثناء حذف الملاحظة.',
                        'error'
                    );
                }
            });
        }
    });
}

// Update displayNotes function to include edit/delete buttons
function displayNotes(notes) {
    const notesList = $('#notesList');
    notesList.empty();

    if (!notes || notes.length === 0) {
        notesList.html(`
            <div class="text-center py-5">
                <i class="fas fa-sticky-note fa-3x text-muted mb-3"></i>
                <p class="text-muted">لا توجد ملاحظات</p>
                <button class="btn btn-primary btn-sm" onclick="$('#addNoteBtn').click()">
                    <i class="fas fa-plus"></i> إضافة ملاحظة
                </button>
            </div>
        `);
        return;
    }

    notes.forEach(note => {
        let noteContent = '';
        let headerGradient = 'linear-gradient(135deg, #4CAF50, #45a049)';
        let headerIcon = 'fas fa-sticky-note';
        let headerClass = 'bg-success';
        
        switch(note.type) {
            case 'code':
                headerGradient = 'linear-gradient(135deg, #2196F3, #1976D2)';
                headerIcon = 'fas fa-code';
                headerClass = 'bg-primary';
                noteContent = `
                    <div class="code-block position-relative">
                        <div class="code-header d-flex justify-content-between align-items-center p-2 bg-light border-bottom">
                            <div>
                                <i class="fas fa-code text-primary me-2"></i>
                                <small class="text-muted">${note.code_language || 'text'}</small>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary copy-btn" onclick="copyCode(this)">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>
                        <pre class="line-numbers rounded-bottom mb-0"><code class="language-${note.code_language || 'text'}">${note.content}</code></pre>
                    </div>`;
                break;
            case 'link':
                headerGradient = 'linear-gradient(135deg, #FF9800, #F57C00)';
                headerIcon = 'fas fa-link';
                headerClass = 'bg-warning';
                noteContent = `
                    <div class="link-block p-3 bg-light rounded">
                        <a href="${note.link_url}" target="_blank" class="d-flex align-items-center text-decoration-none">
                            <i class="fas fa-external-link-alt text-warning me-3 fa-2x"></i>
                            <div>
                                <h6 class="mb-1">${note.link_description || 'رابط خارجي'}</h6>
                                <small class="text-muted">${note.link_url}</small>
                            </div>
                        </a>
                    </div>`;
                break;
            default:
                noteContent = `
                    <div class="text-block p-3">
                        <div class="note-text">${note.content}</div>
                    </div>`;
        }

        notesList.append(`
            <div class="note-item mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 ${headerClass} text-white py-3" 
                         style="background-image: ${headerGradient}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="${headerIcon} me-2"></i>
                                <h6 class="mb-0">${note.title}</h6>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-light btn-floating" 
                                        onclick="editNote(${note.id})" 
                                        title="تعديل">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-light btn-floating" 
                                        onclick="deleteNote(${note.id})" 
                                        title="حذف">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        ${noteContent}
                    </div>
                    <div class="card-footer bg-light border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="far fa-clock me-1"></i>
                                ${new Date(note.created_at).toLocaleString('ar-SA')}
                            </small>
                            <small class="text-muted">
                                <i class="far fa-user me-1"></i>
                                النظام
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `);
    });

    // Initialize Prism.js safely
    try {
        if (typeof Prism !== 'undefined') {
            Prism.highlightAll();
        }
    } catch (e) {
        console.warn('Prism.js initialization failed:', e);
    }
}

// Add copy code function
function copyCode(button) {
    const codeBlock = $(button).closest('.code-block').find('code');
    const text = codeBlock.text();
    
    navigator.clipboard.writeText(text).then(() => {
        $(button).html('<i class="fas fa-check"></i>');
        setTimeout(() => {
            $(button).html('<i class="far fa-copy"></i>');
        }, 2000);
    });
}

function displayTags(tags) {
    const tagsList = $('#tagsList');
    tagsList.empty();

    if (tags.length === 0) {
        tagsList.html('<p class="text-muted">لا توجد وسوم</p>');
        return;
    }

    tags.forEach(tag => {
        tagsList.append(`
            <span class="badge bg-secondary">
                <i class="fas fa-tag"></i> ${tag.trim()}
            </span>
        `);
    });
}

function displayTranscript(transcript) {
    const transcriptContent = $('#transcriptContent');
    transcriptContent.empty();

    if (!transcript) {
        transcriptContent.html('<p class="text-muted">لا يوجد نص</p>');
        return;
    }

    transcriptContent.html(`
        <div class="transcript-text">
            ${transcript}
        </div>
    `);
}

/**
 * Function to edit a lesson
 * @param {number} lessonId - The ID of the lesson to edit
 */
function editLesson(lessonId) {
    // For now, just open the lesson modal
    openLessonModal(lessonId);
    
    // You can implement the edit functionality later
 //   toastr.info('سيتم إضافة وظيفة التعديل قريباً');
}

// Add new functions for languages
function loadLanguages(page = 1) {
    const list = $('#languagesList');
    
    $.ajax({
        url: 'api/get_languages.php',
        data: { 
            page: page,
            per_page: 100 // زيادة عدد العناصر لعرض كل اللغات
        },
        method: 'GET',
        success: function(response) {
            list.empty();
            
            if (response.languages.length === 0) {
                list.html('<div class="col-12 text-center text-white-50">لا توجد لغات متاحة</div>');
                return;
            }
            
            response.languages.forEach(function(language) {
                const languageCard = `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <a href="http://videomx.com/content/courses.php?language_id=${language.id}" 
                           class="language-card d-block text-decoration-none">
                            <div class="language-info">
                                <div class="d-flex align-items-center">
                                    <div class="language-icon">
                                        <i class="${language.icon || 'fas fa-code'}"></i>
                                    </div>
                                    <h6 class="language-name mb-0">${language.name}</h6>
                                </div>
                                <span class="badge">
                                    ${language.lessons_count || 0}
                                </span>
                            </div>
                            <div class="language-stats">
                                <span class="stats-item">
                                    <i class="fas fa-book-open me-1"></i>
                                    ${language.courses_count || 0} دورة
                                </span>
                            </div>
                        </a>
                    </div>
                `;
                list.append(languageCard);
            });
            
            // تحديث الترقيم فقط إذا كان هناك صفحات متعددة
            if (response.total_pages > 1) {
                updateLanguagesPagination(response.current_page, response.total_pages);
            } else {
                $('#languagesPagination').empty(); // إخفاء الترقيم إذا كانت صفحة واحدة
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading languages:', error);
            toastr.error('حدث خطأ أثناء تحميل اللغات');
            list.html('<div class="col-12 text-center text-white-50">حدث خطأ أثناء تحميل اللغات</div>');
        }
    });
}

function updateLanguagesPagination(currentPage, totalPages) {
    const pagination = $('#languagesPagination');
    pagination.empty();

    if (totalPages <= 1) return;

    pagination.append(`
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadLanguages(${currentPage - 1})">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
        <li class="page-item disabled">
            <span class="page-link">${currentPage} / ${totalPages}</span>
        </li>
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadLanguages(${currentPage + 1})">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `);
}

// تحديث دالة فتح النافذة المنبثقة للفيديو لتشمل معالجة القسم
function openVideoModal(lessonData) {
    currentLessonId = lessonData.id;
    currentLessonLanguageId = lessonData.language_id;
    
    // ... existing video modal code ...
    
    // إضافة زر تعديل القسم
    const modalHeader = document.querySelector('#videoModal .modal-header .ms-auto');
    const sectionEditBtn = document.createElement('button');
    sectionEditBtn.className = 'btn btn-outline-primary btn-sm me-2';
    sectionEditBtn.innerHTML = '<i class="fas fa-folder-open"></i> تعديل القسم';
    sectionEditBtn.onclick = openSectionEditModal;
    modalHeader.insertBefore(sectionEditBtn, modalHeader.firstChild);
}

// تحديث دالة openSectionEditModal لتحديد القسم الحالي
async function openSectionEditModal(languageId) {
    try {
        if (!languageId) {
            throw new Error('معرف اللغة غير متوفر');
        }

        // جلب الأقسام الخاصة باللغة
        const response = await fetch(`api/get_sections.php?language_id=${languageId}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'حدث خطأ أثناء جلب الأقسام');
        }
        
        if (!data.sections || data.sections.length === 0) {
            toastr.warning('لا توجد أقسام متاحة لهذه اللغة');
            return;
        }
        
        // إنشاء محتوى النافذة المنبثقة
        const modalContent = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تعديل القسم</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">اختر القسم</label>
                            <select class="form-select" id="sectionSelect">
                                <option value="">اختر القسم...</option>
                                ${data.sections.map(section => 
                                    `<option value="${section.id}" ${currentLesson.section_id == section.id ? 'selected' : ''}>
                                        ${section.name}
                                    </option>`
                                ).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="button" class="btn btn-primary" onclick="updateSection()">حفظ</button>
                    </div>
                </div>
            </div>
        `;
        
        // إنشاء وعرض النافذة المنبثقة
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'sectionEditModal';
        modal.innerHTML = modalContent;
        document.body.appendChild(modal);
        
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // تنظيف النافذة المنبثقة عند إغلاقها
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
        
    } catch (error) {
        toastr.error(error.message || 'حدث خطأ أثناء جلب الأقسام');
        console.error(error);
    }
}

// تحديث دالة updateSection لتحديث واجهة المستخدم بعد التحديث
async function updateSection() {
    try {
        const sectionId = document.getElementById('sectionSelect').value;
        
        if (!sectionId) {
            toastr.error('الرجاء اختيار قسم');
            return;
        }
        
        const response = await fetch('api/update_section.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                lesson_id: currentLessonId,
                section_id: sectionId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            toastr.success('تم تحديث القسم بنجاح');
            bootstrap.Modal.getInstance(document.getElementById('sectionEditModal')).hide();
            
            // تحديث القسم في الدرس الحالي
            currentLesson.section_id = sectionId;
            
            // تحديث القائمة وإعادة تحميل الدروس
            loadLessons(currentPage);
        } else {
            throw new Error(data.error || 'حدث خطأ أثناء تحديث القسم');
        }
        
    } catch (error) {
        toastr.error(error.message || 'حدث خطأ أثناء تحديث القسم');
        console.error(error);
    }
}

/**
 * تحديث قائمة الأقسام عند تغيير اللغة
 * @param {number} languageId - معرف اللغة المحددة
 */
function updateSections(languageId) {
    // إفراغ قائمة الأقسام
    $('#sectionFilter').empty().append('<option value="">اختر القسم</option>');
    
    if (!languageId) return;

    // جلب الأقسام من الخادم
    $.ajax({
        url: 'api/get_sections_by_language.php',
        method: 'GET',
        data: { language_id: languageId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.sections) {
                // إضافة الأقسام إلى القائمة المنسدلة
                response.sections.forEach(function(section) {
                    $('#sectionFilter').append(
                        $('<option>', {
                            value: section.id,
                            text: section.name,
                            title: section.description || ''
                        })
                    );
                });
            } else {
                console.error('خطأ في جلب الأقسام:', response.error);
                toastr.error('حدث خطأ أثناء تحميل الأقسام');
            }
        },
        error: function(xhr, status, error) {
            console.error('خطأ في الاتصال:', error);
            toastr.error('حدث خطأ في الاتصال بالخادم');
        }
    });
}

/**
 * تحديث قائمة الأقسام والكورسات عند تغيير اللغة
 * @param {number} languageId - معرف اللغة المحددة
 */
function updateFilters(languageId) {
    // تحديث الأقسام
    updateSections(languageId);
    // تحديث الكورسات
    updateCourses(languageId);
}

/**
 * تحديث قائمة الكورسات حسب اللغة المحددة
 * @param {number} languageId - معرف اللغة المحددة
 */
function updateCourses(languageId) {
    // إفراغ قائمة الكورسات
    $('#courseFilter').empty().append('<option value="">اختر الكورس</option>');
    
    if (!languageId) return;

    // جلب الكورسات من الخادم
    $.ajax({
        url: 'api/get_courses_by_language.php',
        method: 'GET',
        data: { language_id: languageId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.courses) {
                // إضافة الكورسات إلى القائمة المنسدلة
                response.courses.forEach(function(course) {
                    $('#courseFilter').append(
                        $('<option>', {
                            value: course.id,
                            text: course.name
                        })
                    );
                });
            } else {
                console.error('خطأ في جلب الكورسات:', response.error);
                toastr.error('حدث خطأ أثناء تحميل الكورسات');
            }
        },
        error: function(xhr, status, error) {
            console.error('خطأ في الاتصال:', error);
            toastr.error('حدث خطأ في الاتصال بالخادم');
        }
    });
} 