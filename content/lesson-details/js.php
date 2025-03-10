<script>
// تحديث حالة الدرس
$(document).ready(function() {
    const $statusSelect = $('.status-selectx');
    
    // حفظ القيم الأولية
    $statusSelect.each(function() {
        const $select = $(this);
        $select.data('old-value', $select.val());
        $select.data('old-color', $select.css('background-color'));
        $select.data('old-text-color', $select.css('color'));
    });
    
    // معالجة تغيير الحالة
    $statusSelect.on('change', function() {
        const $select = $(this);
        const statusId = $select.val();
        const lessonId = $select.data('lesson-id');
        
        // التحقق من وجود معرف الدرس
        if (!lessonId) {
            console.error('Lesson ID not found:', $select.data());
            toastr.error('معرف الدرس غير موجود');
            return;
        }
        
        // حفظ القيم القديمة
        const oldValue = $select.data('old-value');
        const oldColor = $select.data('old-color');
        const oldTextColor = $select.data('old-text-color');
        
        // تعطيل العنصر أثناء التحديث
        $select.prop('disabled', true);
        
        // جلب معلومات الحالة المحددة
        const $selectedOption = $select.find('option:selected');
        const newColor = $selectedOption.data('color');
        const newTextColor = $selectedOption.data('text-color');
        
        // إرسال طلب التحديث
        $.ajax({
            url: '../api/update-lesson.php',
            type: 'POST',
            data: {
                lesson_id: lessonId,
                status_id: statusId
            },
            success: function(response) {
                console.log('Update response:', response);
                
                if (response.success) {
                    // تحديث الألوان
                    $select.css({
                        'background-color': newColor,
                        'color': newTextColor
                    });
                    
                    // تحديث القيم المحفوظة
                    $select.data('old-value', statusId);
                    $select.data('old-color', newColor);
                    $select.data('old-text-color', newTextColor);
                    
                    toastr.success(response.message);
                } else {
                    // استعادة القيم القديمة
                    $select.val(oldValue);
                    $select.css({
                        'background-color': oldColor,
                        'color': oldTextColor
                    });
                    toastr.error(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Update error:', error);
                
                // استعادة القيم القديمة
                $select.val(oldValue);
                $select.css({
                    'background-color': oldColor,
                    'color': oldTextColor
                });
                toastr.error('حدث خطأ في الاتصال بالخادم');
            },
            complete: function() {
                // إعادة تفعيل العنصر
                $select.prop('disabled', false);
            }
        });
    });
});

/**
 * معالجة تحديث الدرس
 * يتم تنفيذ هذا الكود عند تحميل الصفحة
 */
$(document).ready(function() {
    // تعريف المتغيرات الرئيسية
    const $form = $('#lessonUpdateForm');
    const $statusSelect = $('.status-select');
    const $submitBtn = $form.find('button[type="submit"]');
    const $isTheory = $('#isTheory');
    const $isImportant = $('#isImportant');
    
    // حفظ القيم الأولية للخيارات
    const initialCheckboxes = {
        isTheory: $isTheory.prop('checked'),
        isImportant: $isImportant.prop('checked')
    };
    
    /**
     * معالجة تغيير الحالة وتحديث الألوان
     */
    $statusSelect.each(function() {
        const $select = $(this);
        
        // حفظ القيم الأولية للحالة
        $select.data('initial-state', {
            value: $select.val(),
            color: $select.css('background-color'),
            textColor: $select.css('color')
        });
        
        // معالجة تغيير الحالة
        $select.on('change', function() {
            const $selectedOption = $(this).find('option:selected');
            const color = $selectedOption.data('color');
            const textColor = $selectedOption.data('text-color');
            
            if (color && textColor) {
                $(this).css({
                    'background-color': color,
                    'color': textColor
                });
            }
        });
    });
    
    /**
     * معالجة تقديم نموذج التحديث
     */
    $form.on('submit', function(e) {
        e.preventDefault();
        
        // تعطيل الزر وإظهار حالة التحميل
        $submitBtn.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-2"></i>جاري الحفظ...');
        
        // تجميع البيانات للإرسال
        const formData = {
            lesson_id: $form.find('input[name="lesson_id"]').val(),
            section_id: $form.find('select[name="section_id"]').val(),
            status_id: $form.find('select[name="status_id"]').val(),
            tags: $form.find('#lessonTags').val(),
            is_theory: $isTheory.prop('checked') ? 1 : 0,
            is_important: $isImportant.prop('checked') ? 1 : 0
        };
        
        // إرسال طلب التحديث
        $.ajax({
            url: '../api/update-lesson.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // تحديث واجهة المستخدم
                    updateLessonUI(response.lesson);
                    toastr.success(response.message);
                    
                    // تحديث القيم الأولية للخيارات
                    initialCheckboxes.isTheory = $isTheory.prop('checked');
                    initialCheckboxes.isImportant = $isImportant.prop('checked');
                } else {
                    // استعادة القيم الأولية
                    $isTheory.prop('checked', initialCheckboxes.isTheory);
                    $isImportant.prop('checked', initialCheckboxes.isImportant);
                    toastr.error(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Update error:', error);
                // استعادة القيم الأولية
                $isTheory.prop('checked', initialCheckboxes.isTheory);
                $isImportant.prop('checked', initialCheckboxes.isImportant);
                toastr.error('حدث خطأ في الاتصال بالخادم');
            },
            complete: function() {
                // إعادة تفعيل الزر
                $submitBtn.prop('disabled', false)
                    .html('<i class="fas fa-save me-2"></i>حفظ التغييرات');
            }
        });
    });
});

/**
 * دالة تحديث واجهة المستخدم
 * @param {Object} lesson - بيانات الدرس المحدثة
 */
function updateLessonUI(lesson) {
    // تحديث القسم
    if (lesson.section_id) {
        $('.section-select').val(lesson.section_id);
    }
    
    // تحديث البادجات
    updateBadges(lesson);
}

/**
 * دالة تحديث البادجات
 * @param {Object} lesson - بيانات الدرس
 */
function updateBadges(lesson) {
    const $badges = $('.lesson-badges');
    $badges.empty();
    
    if (lesson.is_theory == 1) {
        $badges.append(`
            <span class="badge bg-info">
                <i class="fas fa-book"></i>
                درس نظري
            </span>
        `);
    }
    
    if (lesson.is_important == 1) {
        $badges.append(`
            <span class="badge bg-warning">
                <i class="fas fa-star"></i>
                درس مهم
            </span>
        `);
    }
}

// معالجة تحديث الدرس
$(document).ready(function() {
    const $form = $('#lessonUpdateForm');
    const $statusSelect = $form.find('.status-select');
    const $submitBtn = $form.find('button[type="submit"]');
    const $isTheory = $('#isTheory');
    const $isImportant = $('#isImportant');
    
    // حفظ القيم الأولية للنموذج
    let initialFormState = {
        section_id: $form.find('select[name="section_id"]').val(),
        status_id: $statusSelect.val(),
        tags: $form.find('#lessonTags').val(),
        is_theory: $isTheory.is(':checked'),
        is_important: $isImportant.is(':checked'),
        status_color: $statusSelect.css('background-color'),
        status_text_color: $statusSelect.css('color')
    };
    
    // تحديث لون الحالة عند التغيير
    $statusSelect.on('change', function() {
        const $selectedOption = $(this).find('option:selected');
        const color = $selectedOption.data('color');
        const textColor = $selectedOption.data('text-color');
        
        if (color && textColor) {
            $(this).css({
                'background-color': color,
                'color': textColor
            });
        }
    });
    
    // معالجة تقديم النموذج
    $form.on('submit', function(e) {
        e.preventDefault();
        
        // تعطيل الزر وإظهار حالة التحميل
        $submitBtn.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-2"></i>جاري الحفظ...');
        
        // تجميع البيانات الحالية
        const currentFormState = {
            section_id: $form.find('select[name="section_id"]').val(),
            status_id: $statusSelect.val(),
            tags: $form.find('#lessonTags').val(),
            is_theory: $isTheory.is(':checked'),
            is_important: $isImportant.is(':checked')
        };
        
        // تجميع البيانات للإرسال
        const formData = {
            lesson_id: $form.find('input[name="lesson_id"]').val(),
            ...currentFormState
        };
        
        // تحويل القيم المنطقية إلى 1 أو 0
        formData.is_theory = formData.is_theory ? 1 : 0;
        formData.is_important = formData.is_important ? 1 : 0;
        
        console.log('Sending data:', formData);
        
        // إرسال طلب التحديث
        $.ajax({
            url: '../api/update-lesson.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Update response:', response);
                
                if (response.success) {
                    // تحديث القيم الأولية
                    initialFormState = {
                        ...currentFormState,
                        status_color: response.lesson.status_color,
                        status_text_color: response.lesson.status_text_color
                    };
                    
                    // تحديث واجهة المستخدم
                    updateLessonUI(response.lesson);
                    
                    // تحديث البادجات
                    updateBadges({
                        is_theory: formData.is_theory,
                        is_important: formData.is_important
                    });
                    
                    toastr.success(response.message);
                } else {
                    // استعادة القيم الأولية
                    restoreFormState();
                    toastr.error(response.message || 'حدث خطأ أثناء التحديث');
                }
            },
            error: function(xhr, status, error) {
                console.error('Update error:', error);
                restoreFormState();
                toastr.error('حدث خطأ في الاتصال بالخادم');
            },
            complete: function() {
                // إعادة تفعيل الزر
                $submitBtn.prop('disabled', false)
                    .html('<i class="fas fa-save me-2"></i>حفظ التغييرات');
            }
        });
    });
    
    // دالة لاستعادة حالة النموذج
    function restoreFormState() {
        $form.find('select[name="section_id"]').val(initialFormState.section_id);
        $statusSelect.val(initialFormState.status_id)
            .css({
                'background-color': initialFormState.status_color,
                'color': initialFormState.status_text_color
            });
        $form.find('#lessonTags').val(initialFormState.tags);
        $isTheory.prop('checked', initialFormState.is_theory);
        $isImportant.prop('checked', initialFormState.is_important);
    }
});

// استخراج معرف فيديو يوتيوب
function getYoutubeId(url) {
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
    const match = url.match(regExp);
    return (match && match[2].length === 11) ? match[2] : null;
}

// تعريف متغير عام للمحرر
let editor = null;

document.addEventListener('DOMContentLoaded', function() {
    // تهيئة التاجات
    if ($.fn.tagsinput) {
        $('#lessonTags').tagsinput({
            trimValue: true,
            confirmKeys: [13, 44], // Enter and comma
            maxTags: 10,
            maxChars: 20
        });
    }

    // تهيئة Select2
    if ($.fn.select2) {
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
    }

    // معالجة تغيير نوع الملاحظة
    document.getElementById('noteType').addEventListener('change', function() {
        const selectedType = this.value;
        const allFields = document.querySelectorAll('.note-fields');
        allFields.forEach(field => field.classList.add('d-none'));
        
        const selectedFields = document.querySelector(`.${selectedType}-fields`);
        if (selectedFields) {
            selectedFields.classList.remove('d-none');
        }
        
        if (selectedType === 'text') {
            // تدمير أي نسخة سابقة من المحرر
            if (tinymce.get('textContent')) {
                tinymce.remove('#textContent');
            }
            
            // تهيئة TinyMCE
            tinymce.init({
                selector: '#textContent',
                directionality: 'rtl',
                language: 'ar',
                height: 300,
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount'
                ],
                toolbar: 'undo redo | blocks | ' +
                        'bold italic forecolor | alignleft aligncenter ' +
                        'alignright alignjustify | bullist numlist outdent indent | ' +
                        'removeformat | help',
                content_style: `
                    body { 
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
                        font-size: 16px;
                        line-height: 1.6;
                        direction: rtl;
                    }
                `,
                setup: function(ed) {
                    editor = ed;
                    editor.on('change', function() {
                        editor.save();
                    });
                },
                init_instance_callback: function(ed) {
                    editor = ed;
                }
            });
        }
    });

    // تهيئة النموذج مع النوع الافتراضي (نص)
    document.getElementById('noteType').dispatchEvent(new Event('change'));

    // معالجة إرسال النموذج
    document.getElementById('addNoteForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // التحقق من الحقول المطلوبة حسب نوع الملاحظة
        const noteType = document.getElementById('noteType').value;
        let isValid = true;
        let errorMessage = '';

        // التحقق من العنوان (مطلوب دائماً)
        const title = this.querySelector('[name="title"]').value;
        if (!title) {
            isValid = false;
            errorMessage = 'العنوان مطلوب';
        }

        // التحقق حسب نوع الملاحظة
        switch (noteType) {
            case 'text':
                if (editor && !editor.getContent().trim()) {
                    isValid = false;
                    errorMessage = 'المحتوى مطلوب';
                }
                break;
                
            case 'code':
                const codeContent = this.querySelector('[name="code_content"]').value;
                const codeLanguage = this.querySelector('[name="code_language"]').value;
                if (!codeContent || !codeLanguage) {
                    isValid = false;
                    errorMessage = 'الكود ولغة البرمجة مطلوبة';
                }
                break;
                
            case 'link':
                const linkUrl = this.querySelector('[name="link_url"]').value;
                if (!linkUrl) {
                    isValid = false;
                    errorMessage = 'الرابط مطلوب';
                }
                break;
        }

        if (!isValid) {
            toastr.error(errorMessage);
            return;
        }

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        
        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري الإضافة...';
            
            // const response = await fetch('../api/add-note.php', {
            //     method: 'POST',
            //     body: formData
            // });
            
            // const data = await response.json();
            
            // if (data.success) {
            //     const notesList = document.getElementById('notesList');
            //     notesList.insertAdjacentHTML('afterbegin', createNoteHTML(data.note));
                
            //     // إعادة تعيين النموذج والمحرر
            //     this.reset();
            //     if (editor) {
            //         editor.setContent('');
            //     }
                
            //     toastr.success('تمت إضافة الملاحظة بنجاح');
            // } else {
            // //    toastr.error(data.message || 'حدث خطأ أثناء إضافة الملاحظة');
            // }
        } catch (error) {
            console.error('Error:', error);
            toastr.error('حدث خطأ في الاتصال بالخادم');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-plus me-2"></i>إضافة ملاحظة';
        }
    });
});

// تحديث قالب الملاحظة
function createNoteHTML(note) {
    let typeIcon, typeClass, contentHTML;
    
    // تحديد الأيقونة والصنف حسب نوع الملاحظة
    switch (note.type) {
        case 'text':
            typeIcon = 'fa-file-alt';
            typeClass = 'text-primary';
            contentHTML = `
                <div class="note-content formatted-content">
                    ${note.content}
                </div>
            `;
            break;
            
        case 'code':
            typeIcon = 'fa-code';
            typeClass = 'text-success';
            contentHTML = `
                <div class="code-wrapper">
                    <div class="code-header">
                        <span class="badge bg-secondary">
                            <i class="fas fa-code"></i> 
                            ${note.code_language}
                        </span>
                    </div>
                    <pre class="line-numbers"><code class="language-${note.code_language}">${note.content}</code></pre>
                </div>
            `;
            break;
            
        case 'link':
            typeIcon = 'fa-link';
            typeClass = 'text-info';
            contentHTML = `
                <div class="link-wrapper">
                    <a href="${note.link_url}" target="_blank" class="btn btn-link btn-lg d-block text-decoration-none">
                        <i class="fas fa-external-link-alt me-2"></i>
                        ${note.link_url}
                    </a>
                    ${note.link_description ? `
                        <div class="link-description mt-3">
                            <i class="fas fa-info-circle text-muted me-2"></i>
                            ${note.link_description}
                        </div>
                    ` : ''}
                </div>
            `;
            break;
    }
    
    return `
        <div class="note-card mb-4 ${note.type}-note">
            <div class="card">
                <div class="card-header ${typeClass}">
                    <div class="d-flex align-items-center">
                        <div class="note-icon me-3">
                            <i class="fas ${typeIcon} fa-lg"></i>
                        </div>
                        <div class="note-info flex-grow-1">
                            <h5 class="mb-0">${note.title}</h5>
                            <small class="text-muted">
                                <i class="far fa-clock me-1"></i>
                                ${new Date(note.created_at).toLocaleString()}
                            </small>
                        </div>
                        <div class="note-actions">
                            ${note.type === 'code' ? `
                                <button class="btn btn-sm btn-outline-secondary copy-code" title="نسخ الكود">
                                    <i class="fas fa-copy"></i>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    ${contentHTML}
                </div>
            </div>
        </div>
    `;
}

// تحديث CSS للمحتوى المنسق
const style = document.createElement('style');
style.textContent = `
    .tox-tinymce {
        direction: rtl;
    }
    .formatted-content {
        line-height: 1.6;
        direction: rtl;
    }
    .formatted-content * {
        max-width: 100%;
    }
    .formatted-content p {
        margin-bottom: 1rem;
    }
    .formatted-content h1,
    .formatted-content h2,
    .formatted-content h3 {
        margin: 1.5rem 0 1rem;
        font-weight: bold;
    }
    .formatted-content ul,
    .formatted-content ol {
        margin: 1rem 0;
        padding-right: 2rem;
    }
    .formatted-content blockquote {
        margin: 1rem 0;
        padding: 1rem;
        border-right: 4px solid #0d6efd;
        background: #f8f9fa;
    }
    .formatted-content table {
        width: 100%;
        margin: 1rem 0;
        border-collapse: collapse;
    }
    .formatted-content table th,
    .formatted-content table td {
        padding: 0.75rem;
        border: 1px solid #dee2e6;
    }
`;
document.head.appendChild(style);

// تحديث قسم الملاحظات
function updateNotesSection() {
    const notesContent = document.querySelector('.notes-content');
    const toggleButton = document.querySelector('.toggle-notes');
    
    if (!notesContent || !toggleButton) return; // التحقق من وجود العناصر قبل استخدامها

    // تحديث حالة القسم
    const isCollapsed = notesContent.classList.contains('collapsed');
    
    if (isCollapsed) {
        notesContent.classList.remove('collapsed');
        toggleButton.querySelector('i').classList.replace('fa-chevron-down', 'fa-chevron-up');
    } else {
        notesContent.classList.add('collapsed');
        toggleButton.querySelector('i').classList.replace('fa-chevron-up', 'fa-chevron-down');
    }

    // حفظ الحالة في localStorage
    localStorage.setItem('notesContentCollapsed', isCollapsed ? 'false' : 'true');
}

// تهيئة حالة قسم الملاحظات
function initializeNotesSection() {
    const notesContent = document.querySelector('.notes-content');
    const toggleButton = document.querySelector('.toggle-notes');
    
    if (!notesContent || !toggleButton) return; // التحقق من وجود العناصر

    // استرجاع الحالة المحفوظة
    const isCollapsed = localStorage.getItem('notesContentCollapsed') === 'true';
    
    if (isCollapsed) {
        notesContent.classList.add('collapsed');
        toggleButton.querySelector('i').classList.replace('fa-chevron-up', 'fa-chevron-down');
    }

    // إضافة مستمع الحدث لزر التبديل
    toggleButton.addEventListener('click', updateNotesSection);
}

// تهيئة نوع الملاحظة
function initializeNoteType() {
    const noteTypeSelect = document.getElementById('noteType');
    const codeOptions = document.querySelector('.code-options');
    
    if (!noteTypeSelect || !codeOptions) return; // التحقق من وجود العناصر

    noteTypeSelect.addEventListener('change', function() {
        if (this.value === 'code') {
            codeOptions.classList.remove('d-none');
        } else {
            codeOptions.classList.add('d-none');
        }
    });
}

// تهيئة محرر TinyMCE
function initializeTinyMCE() {
    const noteContent = document.getElementById('noteContent');
    
    if (!noteContent) return; // التحقق من وجود العنصر

    tinymce.init({
        selector: '#noteContent',
        directionality: 'rtl',
        height: 300,
        menubar: false,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | formatselect | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | help',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px }'
    });
}

// تهيئة عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    initializeNotesSection();
    initializeNoteType();
    initializeTinyMCE();
});

// معالجة إرسال النموذج
document.getElementById('addNoteForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // التحقق من وجود المحرر
    if (tinymce.get('noteContent')) {
        const content = tinymce.get('noteContent').getContent();
        // ... باقي كود معالجة النموذج
    }
});

// دوال النسخ
$(document).ready(function() {
    /**
     * دالة عامة للنسخ إلى الحافظة
     * @param {string} text - النص المراد نسخه
     * @param {string} successMessage - رسالة النجاح
     * @returns {boolean} - نجاح أو فشل عملية النسخ
     */
    function copyToClipboard(text, successMessage) {
        const tempTextArea = document.createElement('textarea');
        tempTextArea.value = text;
        document.body.appendChild(tempTextArea);
        
        try {
            tempTextArea.select();
            document.execCommand('copy');
            toastr.success(successMessage);
            return true;
        } catch (err) {
            console.error('Copy failed:', err);
            toastr.error('حدث خطأ أثناء النسخ');
            return false;
        } finally {
            document.body.removeChild(tempTextArea);
        }
    }
    
    /**
     * دالة تجميع نصوص الملاحظات
     * @returns {string[]} - مصفوفة تحتوي على نصوص الملاحظات
     */
    function collectTextNotes() {
        const notes = [];
        $('.note-card.text-note .note-content').each(function() {
            const noteText = $(this).text().trim();
            if (noteText) {
                notes.push(noteText);
            }
        });
        return notes;
    }
    
    /**
     * دالة تنسيق النص للنسخ
     * @param {Object} data - البيانات المراد تنسيقها
     * @returns {string} - النص المنسق
     */
    function formatCopyText(data) {
        const sections = [];
        
        // إضافة عنوان الدرس
        if (data.title) {
            sections.push(
                '=== عنوان الدرس ===',
                data.title,
                ''
            );
        }
        
        // إضافة التاجات
        if (data.tags) {
            sections.push(
                '=== التاجات ===',
                data.tags,
                ''
            );
        }
        
        // إضافة الملاحظات النصية
        if (data.notes && data.notes.length > 0) {
            sections.push(
                '=== الملاحظات النصية ===',
                ...data.notes
            );
        }
        
        return sections.join('\n');
    }
    
    // معالجة نسخ جميع المعلومات
    $('.copy-all').on('click', function() {
        const $btn = $(this);
        const title = $btn.data('title');
        const tags = $btn.data('tags');
        const notes = collectTextNotes();
        
        const formattedText = formatCopyText({
            title: title,
            tags: tags,
            notes: notes
        });
        
        if (copyToClipboard(formattedText, 'تم نسخ جميع المعلومات بنجاح')) {
            // تأثير بصري للزر
            $btn.addClass('btn-success').removeClass('btn-outline-primary');
            setTimeout(() => {
                $btn.removeClass('btn-success').addClass('btn-outline-primary');
            }, 1500);
        }
    });
    
    // تفعيل tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});

/**
 * معالجة نسخ معلومات الدرس
 */
$(document).ready(function() {
    /**
     * دالة لتنظيف النص من HTML
     * @param {string} html - النص المحتوي على HTML
     * @returns {string} - النص النظيف
     */
    function stripHtml(html) {
        const temp = document.createElement('div');
        temp.innerHTML = html;
        return temp.textContent || temp.innerText || '';
    }

    /**
     * دالة نسخ النص إلى الحافظة
     * @param {string} text - النص المراد نسخه
     * @returns {Promise} وعد يحل عند نجاح النسخ
     */
    async function copyTextToClipboard(text) {
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(text);
                return;
            }
            
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            
            const success = document.execCommand('copy');
            document.body.removeChild(textarea);
            
            if (!success) {
                throw new Error('فشل نسخ النص');
            }
        } catch (err) {
            throw new Error('فشل نسخ النص: ' + err.message);
        }
    }

    // زر نسخ جميع المعلومات
    $('.copy-lesson-info').on('click', async function() {
        const $btn = $(this);
        const lessonId = $btn.data('lesson-id');
        
        $btn.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin me-2"></i>جاري النسخ...');
        
        try {
            // جلب الملاحظات النصية
            const response = await $.ajax({
                url: '../api/get-notes.php',
                type: 'GET',
                data: {
                    lesson_id: lessonId,
                    type: 'text'
                }
            });
            
            if (!response.success) {
                throw new Error(response.message || 'فشل جلب الملاحظات');
            }
            
            // تجميع النص للنسخ
            const textParts = [];
            
            // إضافة عنوان الدرس
            textParts.push(
                '=== عنوان الدرس ===',
                $btn.data('title'),
                '\n'
            );
            
            // إضافة التاجات
            const tags = $btn.data('tags');
            if (tags) {
                textParts.push(
                    '=== التاجات ===',
                    tags,
                    '\n'
                );
            }
            
            // إضافة الملاحظات النصية
            if (response.notes && response.notes.length > 0) {
                textParts.push('=== الملاحظات النصية ===');
                response.notes.forEach(note => {
                    if (note.title && note.content) {
                        // تنظيف النص من HTML
                        const cleanContent = stripHtml(note.content).trim();
                        if (cleanContent) {
                            textParts.push(
                                `--- ${note.title} ---`,
                                cleanContent,
                                '\n'
                            );
                        }
                    }
                });
            }
            
            // نسخ النص المجمع
            const text = textParts.join('\n');
            await copyTextToClipboard(text);
            
            // تأثير بصري للنجاح
            $btn.removeClass('btn-primary').addClass('btn-success')
                .html('<i class="fas fa-check me-2"></i>تم النسخ');
            
            toastr.success('تم نسخ جميع المعلومات بنجاح');
            
        } catch (error) {
            console.error('Copy error:', error);
            
            $btn.removeClass('btn-primary').addClass('btn-danger')
                .html('<i class="fas fa-times me-2"></i>فشل النسخ');
            
            toastr.error('حدث خطأ أثناء نسخ المعلومات');
        } finally {
            setTimeout(() => {
                $btn.prop('disabled', false)
                    .removeClass('btn-success btn-danger')
                    .addClass('btn-primary')
                    .html('<i class="fas fa-copy me-2"></i>نسخ جميع المعلومات');
            }, 2000);
        }
    });
});

// تهيئة أزرار التحكم في الملاحظات
function initializeNoteControls() {
    // زر حذف الملاحظة
    document.querySelectorAll('.delete-note').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            const noteId = this.getAttribute('data-note-id');
            if (!noteId) {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'معرف الملاحظة غير موجود'
                });
                return;
            }

            const result = await Swal.fire({
                title: 'هل أنت متأكد؟',
                text: 'سيتم حذف هذه الملاحظة نهائياً',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'نعم، احذف',
                cancelButtonText: 'إلغاء'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('note_id', noteId);

                    const response = await fetch('../api/delete-note.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.closest('.note-card').remove();
                        Swal.fire({
                            icon: 'success',
                            title: 'تم الحذف',
                            text: 'تم حذف الملاحظة بنجاح',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        throw new Error(data.error || 'حدث خطأ أثناء حذف الملاحظة');
                    }
                } catch (error) {
                    console.error('Delete error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: error.message
                    });
                }
            }
        });
    });

    // زر تعديل الملاحظة
    document.querySelectorAll('.edit-note').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            const noteId = this.getAttribute('data-note-id');
            if (!noteId) {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'معرف الملاحظة غير موجود'
                });
                return;
            }

            try {
                // تغيير الطريقة إلى GET مع إضافة معرف الملاحظة في URL
                const response = await fetch(`../api/get-note.php?id=${noteId}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                if (data.success && data.note) {
                    const note = data.note;
                    
                    // تعبئة نموذج التعديل
                    const form = document.getElementById('addNoteForm');
                    
                    // إضافة معرف الدرس
                    let lessonIdInput = form.querySelector('input[name="lesson_id"]');
                    if (!lessonIdInput) {
                        lessonIdInput = document.createElement('input');
                        lessonIdInput.type = 'hidden';
                        lessonIdInput.name = 'lesson_id';
                        form.appendChild(lessonIdInput);
                    }
                    lessonIdInput.value = window.LESSON_ID;

                    // تعبئة باقي الحقول
                    form.querySelector('#noteTitle').value = note.title;
                    form.querySelector('#noteType').value = note.type;
                    
                    // معالجة حقول الكود
                    const codeOptions = document.querySelector('.code-options');
                    if (note.type === 'code') {
                        codeOptions.classList.remove('d-none');
                        const langSelect = form.querySelector('#codeLanguage');
                        if (langSelect) {
                            langSelect.value = note.code_language || 'javascript';
                        }
                    } else {
                        codeOptions.classList.add('d-none');
                    }

                    // تحديث المحتوى
                    const contentField = form.querySelector('#noteContent');
                    contentField.value = note.content;

                    // إضافة أو تحديث معرف الملاحظة
                    let hiddenInput = form.querySelector('input[name="note_id"]');
                    if (!hiddenInput) {
                        hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'note_id';
                        form.appendChild(hiddenInput);
                    }
                    hiddenInput.value = noteId;

                    // تحديث زر الحفظ
                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.innerHTML = '<i class="fas fa-save me-1"></i> حفظ التعديلات';

                    // التمرير إلى النموذج
                    form.scrollIntoView({ behavior: 'smooth' });

                    // إظهار رسالة نجاح
                    Swal.fire({
                        icon: 'success',
                        title: 'جاهز للتعديل',
                        text: 'يمكنك الآن تعديل الملاحظة',
                        timer: 1500,
                        showConfirmButton: false,
                        position: 'top-end',
                        toast: true
                    });
                } else {
                    throw new Error(data.error || 'حدث خطأ أثناء تحميل الملاحظة');
                }
            } catch (error) {
                console.error('Edit error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: error.message
                });
            }
        });
    });

    // زر نسخ الكود
    document.querySelectorAll('.copy-code').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            const codeElement = this.closest('.card-body').querySelector('code');
            
            if (!codeElement) {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'لم يتم العثور على الكود'
                });
                return;
            }

            try {
                // استخدام execCommand كحل بديل إذا لم يكن Clipboard API متوفراً
                const textArea = document.createElement('textarea');
                textArea.value = codeElement.textContent;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);

                Swal.fire({
                    icon: 'success',
                    title: 'تم النسخ',
                    text: 'تم نسخ الكود بنجاح',
                    timer: 1500,
                    showConfirmButton: false,
                    position: 'top-end',
                    toast: true
                });
            } catch (error) {
                console.error('Copy error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: 'حدث خطأ أثناء نسخ الكود'
                });
            }
        });
    });
}

// إضافة دالة للتحكم في وضع ملء الشاشة
function initializeFullscreenControls() {
    document.querySelectorAll('.fullscreen-toggle').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            const codeWrapper = this.closest('.code-wrapper');
            if (!codeWrapper) return;

            const icon = this.querySelector('i');
            const codeElement = codeWrapper.querySelector('code');
            
            try {
                if (!document.fullscreenElement) {
                    // الدخول في وضع ملء الشاشة
                    await codeWrapper.requestFullscreen();
                    
                    // تحديث حجم الخط والموضع
                    if (codeElement) {
                        codeElement.style.fontSize = '16px';
                        setTimeout(() => {
                            codeWrapper.scrollTop = 0;
                            codeElement.style.height = '100%';
                            codeElement.style.maxHeight = '100vh';
                        }, 100);
                    }
                    
                    if (icon) {
                        icon.classList.replace('fa-expand', 'fa-compress');
                    }
                } else {
                    // الخروج من وضع ملء الشاشة
                    await document.exitFullscreen();
                    
                    // إعادة تعيين الأنماط
                    if (codeElement) {
                        codeElement.style.fontSize = '14px';
                        codeElement.style.height = 'auto';
                        codeElement.style.maxHeight = 'none';
                    }
                    
                    if (icon) {
                        icon.classList.replace('fa-compress', 'fa-expand');
                    }
                }
            } catch (err) {
                console.error('خطأ في تبديل وضع ملء الشاشة:', err);
            }
        });
    });

    // مراقبة تغيير حالة ملء الشاشة
    document.addEventListener('fullscreenchange', handleFullscreenChange);
}

// معالجة تغيير حالة ملء الشاشة
function handleFullscreenChange() {
    const fullscreenElement = document.fullscreenElement || 
                             document.webkitFullscreenElement || 
                             document.mozFullScreenElement || 
                             document.msFullscreenElement;

    document.querySelectorAll('.fullscreen-toggle').forEach(button => {
        const icon = button.querySelector('i');
        if (icon) {
            if (fullscreenElement) {
                icon.classList.replace('fa-expand', 'fa-compress');
            } else {
                icon.classList.replace('fa-compress', 'fa-expand');
            }
        }
    });
}

// تهيئة موديول الكود
function initializeCodeModal() {
    const codeModal = document.getElementById('codeModal');
    if (!codeModal) return;

    const modal = new bootstrap.Modal(codeModal);
    
    // معالجة إغلاق الموديول
    codeModal.addEventListener('hidden.bs.modal', function () {
        const codeContent = this.querySelector('.code-content');
        if (codeContent) {
            codeContent.innerHTML = '';
        }
        
        // إعادة تعيين حجم الخط
        const fontSizeDisplay = this.querySelector('.font-size-display');
        if (fontSizeDisplay) {
            fontSizeDisplay.textContent = '14px';
        }
        
        // إخفاء الموديول وإزالة الخلفية السوداء
        document.body.classList.remove('modal-open');
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    });

    // معالجة فتح الموديول
    codeModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (!button) return;

        const codeWrapper = button.closest('.code-wrapper');
        if (!codeWrapper) return;

        const code = codeWrapper.querySelector('code');
        if (!code) return;

        const modalTitle = this.querySelector('.modal-title');
        const modalCode = this.querySelector('.code-content code');
        
        if (modalTitle) {
            const noteTitle = codeWrapper.closest('.note-card').querySelector('.card-title');
            modalTitle.textContent = noteTitle ? noteTitle.textContent : 'عرض الكود';
        }
        
        if (modalCode) {
            modalCode.className = code.className;
            modalCode.textContent = code.textContent;
            Prism.highlightElement(modalCode);
        }
    });

    // معالجة أزرار تغيير حجم الخط
    const decreaseBtn = codeModal.querySelector('.font-size-decrease');
    const increaseBtn = codeModal.querySelector('.font-size-increase');
    const fontSizeDisplay = codeModal.querySelector('.font-size-display');
    
    if (decreaseBtn && increaseBtn && fontSizeDisplay) {
        let fontSize = 14;
        
        decreaseBtn.addEventListener('click', () => {
            if (fontSize > 8) {
                fontSize -= 2;
                updateFontSize();
            }
        });
        
        increaseBtn.addEventListener('click', () => {
            if (fontSize < 24) {
                fontSize += 2;
                updateFontSize();
            }
        });
        
        function updateFontSize() {
            const codeElement = codeModal.querySelector('.code-content code');
            if (codeElement) {
                codeElement.style.fontSize = `${fontSize}px`;
                fontSizeDisplay.textContent = `${fontSize}px`;
            }
        }
    }
}

// تحديث تهيئة الصفحة لتشمل تهيئة أزرار ملء الشاشة
document.addEventListener('DOMContentLoaded', function() {
    initializeNotesSection();
    initializeNoteType();
    initializeTinyMCE();
    initializeNoteControls();
    initializeFullscreenControls(); // إضافة تهيئة أزرار ملء الشاشة
    initializeCodeModal(); // إضافة تهيئة الموديول
});
</script>