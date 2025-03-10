// تهيئة المحررات
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة محرر النصوص المتقدم للمحتوى النصي
    initializeTextEditor();
    
    // تهيئة محرر الأكواد
    if (typeof CodeMirror !== 'undefined') {
        document.querySelectorAll('.code-editor').forEach(editor => {
            CodeMirror.fromTextArea(editor, {
                mode: 'javascript',
                theme: 'monokai',
                lineNumbers: true,
                direction: 'ltr'
            });
        });
    }
    
    // تهيئة Prism.js لتنسيق الأكواد
    if (typeof Prism !== 'undefined') {
        Prism.highlightAll();
    }
    
    // معالجة تغيير نوع الملاحظة
    const noteTypeSelect = document.getElementById('noteType');
    if (noteTypeSelect) {
        noteTypeSelect.addEventListener('change', function() {
            handleNoteTypeChange(this.value);
        });
    }
    
    // معالجة إضافة ملاحظة جديدة
    const addNoteForm = document.getElementById('addNoteForm');
    if (addNoteForm) {
       // addNoteForm.addEventListener('submit', handleNoteSubmit);
    }
    
    // معالجة نسخ الكود
    document.querySelectorAll('.copy-code').forEach(btn => {
        btn.addEventListener('click', function() {
            const codeBlock = this.closest('.note-card').querySelector('code');
            navigator.clipboard.writeText(codeBlock.textContent).then(() => {
                toastr.success('تم نسخ الكود بنجاح');
                
                // تأثير بصري
                this.classList.add('btn-success');
                setTimeout(() => {
                    this.classList.remove('btn-success');
                }, 1000);
            });
        });
    });
});

/**
 * تهيئة محرر النصوص المتقدم
 */
function initializeTextEditor() {
    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            selector: '#textContent',
            directionality: 'rtl',
            height: 300,
            plugins: 'link lists table',
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link table',
            setup: function(editor) {
                editor.on('change', function() {
                    editor.save(); // حفظ المحتوى في textarea
                });
            }
        });
    }
}

/**
 * معالجة تغيير نوع الملاحظة
 * @param {string} selectedType - النوع المختار (text/code/link)
 */
function handleNoteTypeChange(selectedType) {
    // إخفاء جميع الحقول
    document.querySelectorAll('.note-fields').forEach(field => {
        field.classList.add('d-none');
    });
    
    // إظهار الحقول المناسبة
    const selectedFields = document.querySelector(`.${selectedType}-fields`);
    if (selectedFields) {
        selectedFields.classList.remove('d-none');
        
        // تهيئة المحرر المناسب
        if (selectedType === 'text') {
            initializeTextEditor();
        } else if (selectedType === 'code') {
            initializeCodeEditor();
        }
    }
}

/**
 * تهيئة محرر الأكواد
 */
function initializeCodeEditor() {
    if (typeof CodeMirror !== 'undefined') {
        const codeEditor = document.querySelector('.code-editor');
        if (codeEditor && !codeEditor.nextSibling?.classList?.contains('CodeMirror')) {
            CodeMirror.fromTextArea(codeEditor, {
                mode: 'javascript',
                theme: 'monokai',
                lineNumbers: true,
                direction: 'ltr'
            });
        }
    }
}

/**
 * معالجة إرسال النموذج
 * @param {Event} e - حدث الإرسال
 */
async function handleNoteSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData(form);
        const noteType = formData.get('type');
        
        // تجهيز البيانات حسب النوع
        const data = {
            lesson_id: formData.get('lesson_id'),
            type: noteType,
            title: formData.get('title')
        };
        
        // إضافة البيانات حسب النوع
        switch (noteType) {
            case 'text':
                data.content = tinymce.get('textContent').getContent();
                break;
            case 'code':
                data.code_language = formData.get('code_language');
                const codeEditor = document.querySelector('.code-editor');
                if (codeEditor.nextSibling?.classList?.contains('CodeMirror')) {
                    data.content = codeEditor.nextSibling.CodeMirror.getValue();
                } else {
                    data.content = formData.get('code_content');
                }
                break;
            case 'link':
                data.link_url = formData.get('link_url');
                data.link_description = formData.get('link_description');
                break;
        }
        
        // إرسال البيانات
        const response = await fetch('../api/add-note.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // إضافة الملاحظة للقائمة
            document.getElementById('notesList').insertAdjacentHTML(
                'afterbegin', 
                createNoteCard(result.note)
            );
            
            // تنظيف النموذج
            form.reset();
            resetEditors(noteType);
            
            toastr.success('تمت إضافة الملاحظة بنجاح');
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        toastr.error(error.message || 'حدث خطأ أثناء إضافة الملاحظة');
    } finally {
        submitBtn.disabled = false;
    }
}

/**
 * إعادة تعيين المحررات
 * @param {string} noteType - نوع الملاحظة
 */
function resetEditors(noteType) {
    if (noteType === 'text') {
        tinymce.get('textContent').setContent('');
    } else if (noteType === 'code') {
        const codeEditor = document.querySelector('.code-editor');
        if (codeEditor.nextSibling?.classList?.contains('CodeMirror')) {
            codeEditor.nextSibling.CodeMirror.setValue('');
        }
    }
}

// دالة إنشاء كارد الملاحظة
function createNoteCard(note) {
    let content = '';
    
    switch (note.type) {
        case 'text':
            content = note.content;
            break;
        case 'code':
            content = `<pre><code class="language-${note.code_language}">${escapeHtml(note.content)}</code></pre>`;
            break;
        case 'link':
            content = `
                <a href="${escapeHtml(note.link_url)}" target="_blank" class="d-block mb-2">
                    ${escapeHtml(note.link_url)}
                </a>
                ${note.link_description ? `<p class="mb-0">${escapeHtml(note.link_description)}</p>` : ''}
            `;
            break;
    }
    
    return `
        <div class="note-card mb-3 ${note.type}-note">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">${escapeHtml(note.title)}</h5>
                    <div class="note-actions">
                        ${note.type === 'code' ? `
                            <button class="btn btn-sm btn-outline-primary copy-code">
                                <i class="fas fa-copy"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>
                <div class="card-body">
                    <div class="note-content">
                        ${content}
                    </div>
                </div>
            </div>
        </div>
    `;
}

// دالة تهريب النصوص HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * إدارة نموذج الملاحظات
 */
class NotesManager {
    constructor() {
        this.form = document.getElementById('addNoteForm');
        this.typeSelect = document.getElementById('noteType');
        this.codeEditor = null;
        this.textEditor = null;
        
        this.init();
    }
    
    /**
     * تهيئة النموذج والأحداث
     */
    init() {
        if (!this.form || !this.typeSelect) return;
        
        // تهيئة المحررات
        this.initEditors();
        
        // مراقبة تغيير النوع
        this.typeSelect.addEventListener('change', () => this.handleTypeChange());
        
        // مراقبة تقديم النموذج
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // تهيئة النوع الأولي
        this.handleTypeChange();
    }
    
    /**
     * تهيئة محررات النصوص والأكواد
     */
    initEditors() {
        // تهيئة محرر النصوص
        tinymce.init({
            selector: '#textContent',
            directionality: 'rtl',
            height: 300,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link | code'
        });
        
        // تهيئة محرر الأكواد
        const codeTextarea = document.getElementById('codeContent');
        if (codeTextarea) {
            this.codeEditor = CodeMirror.fromTextArea(codeTextarea, {
                mode: 'javascript',
                theme: 'monokai',
                lineNumbers: true,
                autoCloseBrackets: true,
                matchBrackets: true,
                indentUnit: 4,
                tabSize: 4,
                direction: 'ltr',
                rtlMoveVisually: false
            });
        }
    }
    
    /**
     * معالجة تغيير نوع الملاحظة
     */
    handleTypeChange() {
        const type = this.typeSelect.value;
        const wrapper = this.form;
        
        // إخفاء جميع الخيارات
        ['text-options', 'code-options', 'link-options'].forEach(className => {
            const element = wrapper.querySelector(`.${className}`);
            if (element) element.classList.add('d-none');
        });
        
        // إظهار الخيارات المناسبة
        const options = wrapper.querySelector(`.${type}-options`);
        if (options) {
            options.classList.remove('d-none');
            
            // تحديث المحرر المناسب
            if (type === 'code' && this.codeEditor) {
                setTimeout(() => this.codeEditor.refresh(), 10);
            }
        }
        
        // تحديث نوع النموذج
        wrapper.className = `note-form-wrapper ${type}-type`;
    }
    
    /**
     * معالجة إرسال النموذج
     */
    async handleSubmit(e) {
        e.preventDefault();
        
        try {
            // تجميع البيانات
            const data = {
                lesson_id: this.form.querySelector('[name="lesson_id"]').value,
                type: this.typeSelect.value,
                title: this.form.querySelector('[name="title"]').value
            };
            
            // إضافة البيانات حسب النوع
            switch (data.type) {
                case 'text':
                    data.content = tinymce.get('textContent').getContent();
                    break;
                    
                case 'code':
                    data.content = this.codeEditor.getValue();
                    data.code_language = this.form.querySelector('[name="code_language"]').value;
                    break;
                    
                case 'link':
                    data.content = this.form.querySelector('#linkContent').value;
                    data.link_url = this.form.querySelector('[name="link_url"]').value;
                    break;
            }
            
            // إرسال البيانات
            const response = await fetch('/content/api/add-note.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                toastr.success('تم إضافة الملاحظة بنجاح');
                this.resetForm();
                this.updateNotesList(result.note);
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('Error:', error);
            toastr.error(error.message || 'حدث خطأ أثناء إضافة الملاحظة');
        }
    }
    
    /**
     * إعادة تعيين النموذج
     */
    resetForm() {
        this.form.reset();
        
        // إعادة تعيين المحررات
        if (tinymce.get('textContent')) {
            tinymce.get('textContent').setContent('');
        }
        
        if (this.codeEditor) {
            this.codeEditor.setValue('');
        }
        
        this.handleTypeChange();
    }
    
    /**
     * تحديث قائمة الملاحظات
     */
    updateNotesList(newNote) {
        const notesList = document.getElementById('notesList');
        if (!notesList) return;
        
        const noteElement = this.createNoteElement(newNote);
        notesList.insertBefore(noteElement, notesList.firstChild);
    }
    
    createNoteElement(note) {
        const div = document.createElement('div');
        div.className = `note-card ${note.type}-note`;
        div.dataset.noteId = note.id;
        
        div.innerHTML = `
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">${note.title}</h5>
                    <div class="note-actions">
                        <button class="btn btn-sm btn-edit" title="تعديل">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-delete" title="حذف">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    ${this.formatNoteContent(note)}
                </div>
            </div>
        `;
        
        return div;
    }
    
    formatNoteContent(note) {
        switch (note.type) {
            case 'code':
                return `<pre><code class="language-${note.code_language}">${note.content}</code></pre>`;
            case 'link':
                return `<a href="${note.link_url}" target="_blank">${note.content}</a>`;
            default:
                return note.content;
        }
    }
}

// تهيئة مدير الملاحظات عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    new NotesManager();
}); 