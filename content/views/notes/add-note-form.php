<?php
/**
 * نموذج إضافة ملاحظة جديدة
 * يدعم الأنواع التالية: نص، كود، رابط، صورة
 */
?>
<div class="card mb-4 note-form-card">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">
            <i class="fas fa-plus-circle me-2"></i>
            إضافة ملاحظة جديدة
        </h5>
    </div>
    <div class="card-body">
        <form id="addNoteForm" class="note-form-wrapper text-type">
            <!-- حقل مخفي لمعرف الدرس -->
            <input type="hidden" name="lesson_id" value="<?php echo htmlspecialchars($lesson['id']); ?>">
            
            <!-- عنوان الملاحظة -->
            <div class="mb-3">
                <label for="noteTitle" class="form-label">عنوان الملاحظة</label>
                <input type="text" class="form-control" id="noteTitle" name="title"  value="<?php echo htmlspecialchars($lesson['title']); ?>" required>
            </div>
            
            <!-- نوع الملاحظة -->
            <div class="mb-3">
                <label for="noteType" class="form-label">نوع الملاحظة</label>
                <select class="form-select" id="noteType" name="type" required>
                    <option value="text">نص</option>
                    <option value="code">كود</option>
                    <option value="link">رابط</option>
                </select>
            </div>
            
            <!-- خيارات الكود -->
            <div class="code-options d-none">
                <div class="mb-3">
                    <label for="codeLanguage" class="form-label">لغة البرمجة</label>
                    <select class="form-select" id="codeLanguage" name="code_language">
                        <option value="javascript">JavaScript</option>
                        <option value="php">PHP</option>
                        <option value="html">HTML</option>
                        <option value="css">CSS</option>
                        <option value="sql">SQL</option>
                        <option value="python">Python</option>
                        <option value="java">Java</option>
                        <option value="csharp">C#</option>
                        <option value="cpp">C++</option>
                        <option value="typescript">TypeScript</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="codeContent" class="form-label">الكود</label>
                    <textarea class="form-control code-editor" 
                              id="codeContent" 
                              name="content" 
                              rows="10"
                              dir="ltr"></textarea>
                </div>
            </div>
            
            <!-- محتوى النص العادي -->
            <div class="text-options">
                <div class="mb-3">
                    <label for="textContent" class="form-label">المحتوى</label>
                    <textarea class="form-control" 
                              id="textContent" 
                              name="content" 
                              rows="5"></textarea>
                </div>
            </div>
            
            <!-- خيارات الرابط -->
            <div class="link-options d-none">
                <div class="mb-3">
                    <label for="linkUrl" class="form-label">الرابط</label>
                    <input type="url" class="form-control" id="linkUrl" name="link_url">
                </div>
                <div class="mb-3">
                    <label for="linkContent" class="form-label">وصف الرابط</label>
                    <textarea class="form-control" 
                              id="linkContent" 
                              name="content" 
                              rows="3"></textarea>
                </div>
            </div>
            
            <!-- أزرار التحكم -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>
                    حفظ الملاحظة
                </button>
                <button type="reset" class="btn btn-secondary ms-2">
                    <i class="fas fa-undo me-1"></i>
                    إعادة تعيين
                </button>
            </div>
        </form>
    </div>
</div> 