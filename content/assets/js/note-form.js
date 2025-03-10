/**
 * التحكم في نموذج إضافة الملاحظات
 */
class NoteForm {
    constructor() {
        this.form = document.getElementById('addNoteForm');
        this.typeSelect = document.getElementById('noteType');
        this.contentEditor = null;
        
        this.init();
    }
    
    init() {
        if (!this.form) return;
        
        // تهيئة محرر النص المتقدم
        this.initTextEditor();
        
        // مراقبة تغيير نوع الملاحظة
        this.typeSelect.addEventListener('change', () => this.handleTypeChange());
        
        // مراقبة تقديم النموذج
    //    this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // تهيئة النوع الأولي
        this.handleTypeChange();
    }
    
    /**
     * تهيئة محرر النص المتقدم
     */
    initTextEditor() {
        tinymce.init({
            selector: '#noteContent',
            directionality: 'rtl',
            height: 300,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image | code',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial; font-size: 14px; }',
        });
    }
    
    /**
     * معالجة تغيير نوع الملاحظة
     */
    handleTypeChange() {
        const type = this.typeSelect.value;
        const wrapper = this.form;
        
        // إزالة الأصناف السابقة
        wrapper.classList.remove('text-type', 'code-type', 'link-type', 'image-type');
        wrapper.classList.add(`${type}-type`);
        
        // إخفاء جميع الخيارات
        document.querySelectorAll('.code-options, .link-options, .image-options').forEach(el => {
            el.classList.add('d-none');
        });
        
        // إظهار الخيارات المناسبة
        const options = document.querySelector(`.${type}-options`);
        if (options) {
            options.classList.remove('d-none');
        }
        
        // تحديث محرر المحتوى
        this.updateContentEditor(type);
    }
    
    /**
     * تحديث محرر المحتوى حسب النوع
     */
    updateContentEditor(type) {
        const contentWrapper = this.form.querySelector('.content-wrapper');
        
        if (type === 'text') {
            // تفعيل محرر النص المتقدم
            if (!this.contentEditor) {
                this.initTextEditor();
            }
        } else {
            // إزالة محرر النص المتقدم
            if (this.contentEditor) {
                tinymce.remove('#noteContent');
                this.contentEditor = null;
            }
        }
    }
    
    /**
     * معالجة تقديم النموذج
     */
    async handleSubmit(e) {
        e.preventDefault();
        
        try {
            const formData = new FormData(this.form);
            
            // التحقق من البيانات
            this.validateForm(formData);
            
            // إرسال البيانات
            const response = await fetch('/api/notes/add', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (response.ok) {
                toastr.success('تم إضافة الملاحظة بنجاح');
                this.resetForm();
                this.updateNotesList(data.note);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            toastr.error(error.message || 'حدث خطأ أثناء إضافة الملاحظة');
        }
    }
    
    /**
     * التحقق من صحة البيانات
     */
    validateForm(formData) {
        const type = formData.get('type');
        
        switch (type) {
            case 'code':
                if (!formData.get('code_language')) {
                    throw new Error('الرجاء اختيار لغة البرمجة');
                }
                break;
            case 'link':
                if (!formData.get('link_url')) {
                    throw new Error('الرجاء إدخال رابط صحيح');
                }
                break;
            case 'image':
                if (!formData.get('image')) {
                    throw new Error('الرجاء اختيار صورة');
                }
                break;
        }
    }
    
    /**
     * إعادة تعيين النموذج
     */
    resetForm() {
        this.form.reset();
        this.handleTypeChange();
    }
    
    /**
     * تحديث قائمة الملاحظات
     */
    updateNotesList(newNote) {
        // يتم تنفيذ هذه الدالة في ملف notes.js
        if (window.updateNotesList) {
            window.updateNotesList(newNote);
        }
    }
}

// تهيئة النموذج عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    new NoteForm();
}); 