<?php
require_once 'includes/functions.php';

// جلب رقم الصفحة الحالية
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;

// جلب إجمالي عدد اللغات للترقيم
$totalLanguages = getLanguagesTableCount();
$totalPages = ceil($totalLanguages / $perPage);

// جلب اللغات مع الإحصائيات
$languages = getLanguagesWithStats($page, $perPage);

$pageTitle = 'قائمة اللغات';
require_once 'includes/header.php';
?>

<!-- إضافة ملف CSS -->
<link rel="stylesheet" href="assets/css/languages.css">

<!-- شريط التنقل العلوي -->
<div class="navigation-bar bg-light py-3 mb-4 border-bottom">
    <div class="container">
        <div class="row align-items-center">
            <!-- الأزرار على اليمين -->
            <div class="col-auto">
                <div class="d-flex gap-2">
                    <a href="/content/index.php" class="btn btn-outline-primary">
                        <i class="fas fa-home me-1"></i>
                        الرئيسية
                    </a>
                </div>
            </div>

            <!-- عنوان الصفحة في المنتصف -->
            <div class="col text-center">
                <h4 class="mb-0 text-primary">
                    <i class="fas fa-language me-2"></i>
                    قائمة اللغات
                </h4>
            </div>

            <!-- زر الإضافة على اليسار -->
            <div class="col-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLanguageModal">
                    <i class="fas fa-plus me-1"></i>
                    إضافة لغة جديدة
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <!-- شبكة اللغات -->
    <div class="language-grid">
        <?php foreach ($languages as $language): ?>
            <div class="language-card card" data-language-id="<?php echo $language['id']; ?>">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="fas fa-code"></i>
                        <?php echo htmlspecialchars($language['name']); ?>
                    </h5>
                </div>
                
                <div class="card-body">
                    <!-- الإحصائيات -->
                    <div class="stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $language['sections_count']; ?></div>
                            <div class="stat-label">الأقسام</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $language['courses_count']; ?></div>
                            <div class="stat-label">الدورات</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $language['lessons_count']; ?></div>
                            <div class="stat-label">الدروس</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo formatDuration($language['total_duration']); ?></div>
                            <div class="stat-label">المدة الكلية</div>
                        </div>
                    </div>

                    <!-- إضافة قسم جديد -->
                    <div class="sections-input">
                        <textarea class="form-control add-section-input" 
                                  placeholder="اكتب أسماء الأقسام - كل قسم في سطر جديد"
                                  data-language-id="<?php echo $language['id']; ?>"
                                  rows="3"></textarea>
                        <button class="btn btn-primary btn-sm mt-2 add-sections-btn">
                            <i class="fas fa-plus me-1"></i>
                            إضافة الأقسام
                        </button>
                    </div>

                    <!-- قائمة الأقسام -->
                    <div class="sections-list">
                        <?php foreach ($language['sections'] as $section): ?>
                            <div class="section-item">
                                <span class="section-name">
                                    <?php echo htmlspecialchars($section['name']); ?>
                                </span>
                                <i class="fas fa-times delete-section" 
                                   data-section-id="<?php echo $section['id']; ?>"
                                   title="حذف القسم"></i>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="far fa-clock me-1"></i>
                            <?php echo formatDate($language['created_at']); ?>
                        </small>
                        <div class="btn-group">
                            <a href="courses.php?language_id=<?php echo $language['id']; ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-book me-1"></i>
                                الدورات
                            </a>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-danger delete-language"
                                    data-language-id="<?php echo $language['id']; ?>"
                                    data-language-name="<?php echo htmlspecialchars($language['name']); ?>">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- الترقيم -->
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">السابق</a>
                </li>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">التالي</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- مودال إضافة لغة جديدة -->
<div class="modal fade" id="addLanguageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة لغة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addLanguageForm">
                    <div class="mb-3">
                        <label for="languageName" class="form-label">اسم اللغة</label>
                        <input type="text" class="form-control" id="languageName" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="saveLanguage">حفظ</button>
            </div>
        </div>
    </div>
</div>

<!-- مودال تأكيد الحذف -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تأكيد الحذف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من حذف اللغة <strong id="languageNameToDelete"></strong>؟</p>
                <p class="text-danger">سيتم حذف جميع الكورسات والدروس والأقسام المرتبطة بها</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">تأكيد الحذف</button>
            </div>
        </div>
    </div>
</div>

<script>
// تهيئة المتغيرات
let currentLanguageId = null;

// إضافة قسم جديد
document.querySelectorAll('.add-section-input').forEach(input => {
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const languageId = this.dataset.languageId;
            const sectionName = this.value.trim();
            
            if (sectionName) {
                addSection(languageId, sectionName, this);
            }
        }
    });
});

// دالة إضافة قسم
async function addSection(languageId, name, input) {
    try {
        const response = await fetch('api/add-section.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ language_id: languageId, name: name })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // إضافة القسم للقائمة
            const sectionsList = input.closest('.card-body').querySelector('.sections-list');
            const sectionHtml = `
                <div class="section-item">
                    <span class="section-name">${name}</span>
                    <i class="fas fa-times delete-section" 
                       data-section-id="${data.section_id}"
                       title="حذف القسم"></i>
                </div>
            `;
            sectionsList.insertAdjacentHTML('beforeend', sectionHtml);
            
            // تحديث العداد
            const statsElement = input.closest('.card-body').querySelector('.stat-value');
            statsElement.textContent = parseInt(statsElement.textContent) + 1;
            
            // مسح الإدخال
            input.value = '';
            
            toastr.success('تم إضافة القسم بنجاح');
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        toastr.error(error.message || 'حدث خطأ أثناء إضافة القسم');
    }
}

// إضافة الأقسام المتعددة
document.querySelectorAll('.add-sections-btn').forEach(button => {
    button.addEventListener('click', function() {
        const textarea = this.parentElement.querySelector('.add-section-input');
        if (!textarea) {
            console.error('Could not find textarea element');
            return;
        }

        const languageId = textarea.dataset.languageId;
        const sectionsText = textarea.value.trim();
        
        if (sectionsText) {
            // تقسيم النص إلى مصفوفة من الأسماء
            const sectionNames = sectionsText.split('\n')
                .map(name => name.trim())
                .filter(name => name.length > 0);
            
            if (sectionNames.length > 0) {
                addMultipleSections(languageId, sectionNames, textarea);
            }
        }
    });
});

// دالة إضافة أقسام متعددة
async function addMultipleSections(languageId, names, textarea) {
    try {
        const response = await fetch('api/add-multiple-sections.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                language_id: languageId, 
                names: names 
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // إضافة الأقسام للقائمة
            const card = textarea.closest('.language-card');
            if (!card) {
                throw new Error('Could not find language card element');
            }

            const sectionsList = card.querySelector('.sections-list');
            if (!sectionsList) {
                throw new Error('Could not find sections list element');
            }
            
            data.sections.forEach(section => {
                const sectionHtml = `
                    <div class="section-item">
                        <span class="section-name">${section.name}</span>
                        <i class="fas fa-times delete-section" 
                           data-section-id="${section.id}"
                           title="حذف القسم"></i>
                    </div>
                `;
                sectionsList.insertAdjacentHTML('beforeend', sectionHtml);
            });
            
            // تحديث العداد
            const statsElement = card.querySelector('.stat-value');
            if (statsElement) {
                statsElement.textContent = parseInt(statsElement.textContent) + names.length;
            }
            
            // مسح النص
            textarea.value = '';
            
            toastr.success(`تم إضافة ${names.length} قسم بنجاح`);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        toastr.error(error.message || 'حدث خطأ أثناء إضافة الأقسام');
    }
}

// حذف قسم
document.addEventListener('click', function(e) {
    if (e.target.matches('.delete-section')) {
        const sectionId = e.target.dataset.sectionId;
        const sectionItem = e.target.closest('.section-item');
        if (!sectionItem) {
            console.error('Could not find section item element');
            return;
        }
        deleteSection(sectionId, sectionItem);
    }
});

// دالة حذف قسم
async function deleteSection(sectionId, element) {
    try {
        const response = await fetch('api/delete-section.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ section_id: sectionId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // حذف العنصر من DOM
            const card = element.closest('.language-card');
            if (!card) {
                throw new Error('Could not find language card element');
            }

            element.remove();
            
            // تحديث العداد
            const statsElement = card.querySelector('.stat-value');
            if (statsElement) {
                statsElement.textContent = parseInt(statsElement.textContent) - 1;
            }
            
            toastr.success('تم حذف القسم بنجاح');
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        toastr.error(error.message || 'حدث خطأ أثناء حذف القسم');
    }
}

// حذف لغة
document.querySelectorAll('.delete-language').forEach(button => {
    button.addEventListener('click', function() {
        const languageId = this.dataset.languageId;
        const languageName = this.dataset.languageName;
        const card = document.querySelector(`[data-language-id="${languageId}"]`);
        
        if (card) {
            document.getElementById('languageNameToDelete').textContent = languageName;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
            
            document.getElementById('confirmDelete').onclick = async function() {
                try {
                    const response = await fetch('api/delete-language.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ language_id: languageId })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        card.remove();
                        modal.hide();
                        toastr.success('تم حذف اللغة وجميع العناصر المرتبطة بها بنجاح');
                    } else {
                        throw new Error(data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    toastr.error(error.message || 'حدث خطأ أثناء حذف اللغة');
                }
            };
        }
    });
});

// إضافة لغة جديدة
document.getElementById('saveLanguage').addEventListener('click', async function() {
    const nameInput = document.getElementById('languageName');
    const name = nameInput.value.trim();
    
    if (!name) {
        toastr.error('يرجى إدخال اسم اللغة');
        return;
    }
    
    try {
        const response = await fetch('api/add-language.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ name: name })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // إعادة تحميل الصفحة لتحديث القائمة
            location.reload();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        toastr.error(error.message || 'حدث خطأ أثناء إضافة اللغة');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 