/**
 * courses.js
 * ملف لعرض الكورسات وأقسامها مع إمكانية نسخ الدروس وعرض الدروس حسب القسم
 * وفلترة الكورسات حسب اللغة وعرض الأقسام حسب اللغة
 * 
 * المتطلبات:
 * - SweetAlert2: لعرض الإشعارات والنوافذ المنبثقة
 * - Font Awesome: للأيقونات
 * - Bootstrap: للتنسيق
 * 
 * الوظائف الرئيسية:
 * - عرض الكورسات وأقسامها
 * - نسخ قائمة الدروس
 * - عرض الدروس حسب القسم
 * - فلترة الكورسات حسب اللغة
 * - عرض الأقسام حسب اللغة
 */

class CoursesManager {
    /**
     * إنشاء مدير الكورسات
     * يقوم بتهيئة العناصر الأساسية وتخزين بيانات الكورسات
     */
    constructor() {
        this.coursesContainer = document.getElementById('coursesContainer');
        this.loadingElement = document.getElementById('loading');
        this.errorElement = document.getElementById('error');
        this.languageFiltersContainer = document.getElementById('languageFilters');
        this.languageSectionsContainer = document.createElement('div');
        this.languageSectionsContainer.className = 'language-sections-container';
        this.coursesContainer.parentNode.insertBefore(this.languageSectionsContainer, this.coursesContainer.nextSibling);
        
        this.coursesData = []; // تخزين بيانات الكورسات للاستخدام اللاحق
        this.languagesData = []; // تخزين بيانات اللغات والأقسام
        this.languages = []; // تخزين اللغات المتاحة
        this.activeLanguage = 'all'; // اللغة النشطة حالياً (الكل افتراضياً)
    }

    /**
     * تحميل الكورسات من الخادم
     * @returns {Promise<void>}
     */
    async loadCourses() {
        try {
            this.showLoading();
            const response = await fetch('get_courses.php');
            const data = await response.json();

            if (data.status === 'success') {
                this.coursesData = data.data; // تخزين بيانات الكورسات
                this.languagesData = data.languages; // تخزين بيانات اللغات والأقسام
                this.extractLanguages(); // استخراج اللغات المتاحة
                this.renderLanguageFilters(); // عرض فلاتر اللغات
                this.displayCourses(this.filterCoursesByLanguage(this.activeLanguage));
                this.displayLanguageSections(this.activeLanguage);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            this.showError(error.message);
            console.error('Error loading courses:', error);
        } finally {
            this.hideLoading();
        }
    }

    /**
     * استخراج اللغات المتاحة من بيانات الكورسات
     */
    extractLanguages() {
        // استخراج اللغات الفريدة
        const uniqueLanguages = [...new Set(this.coursesData.map(course => course.language))];
        this.languages = uniqueLanguages.filter(lang => lang); // إزالة القيم الفارغة
    }

    /**
     * عرض فلاتر اللغات
     */
    renderLanguageFilters() {
        if (!this.languages.length) {
            this.languageFiltersContainer.innerHTML = '<p>لا توجد لغات متاحة</p>';
            return;
        }

        const filtersHTML = `
            <div class="language-filter active" data-language="all">
                <i class="fas fa-globe"></i>
                جميع اللغات
            </div>
            ${this.languages.map(language => `
                <div class="language-filter" data-language="${language}">
                    <i class="fas fa-language"></i>
                    ${language}
                </div>
            `).join('')}
        `;

        this.languageFiltersContainer.innerHTML = filtersHTML;
        this.attachLanguageFilterHandlers();
    }

    /**
     * إضافة معالجات الأحداث لفلاتر اللغات
     */
    attachLanguageFilterHandlers() {
        document.querySelectorAll('.language-filter').forEach(filter => {
            filter.addEventListener('click', (e) => {
                const language = e.currentTarget.dataset.language;
                this.filterCourses(language);
                
                // تحديث الفلتر النشط
                document.querySelectorAll('.language-filter').forEach(f => {
                    f.classList.remove('active');
                });
                e.currentTarget.classList.add('active');
            });
        });
    }

    /**
     * فلترة الكورسات حسب اللغة
     * @param {string} language - اللغة المطلوبة ('all' لجميع اللغات)
     */
    filterCourses(language) {
        this.activeLanguage = language;
        const filteredCourses = this.filterCoursesByLanguage(language);
        this.displayCourses(filteredCourses);
        this.displayLanguageSections(language);
    }

    /**
     * فلترة الكورسات حسب اللغة
     * @param {string} language - اللغة المطلوبة ('all' لجميع اللغات)
     * @returns {Array} الكورسات المفلترة
     */
    filterCoursesByLanguage(language) {
        if (language === 'all') {
            return this.coursesData;
        }
        return this.coursesData.filter(course => course.language === language);
    }

    /**
     * عرض الكورسات في الصفحة
     * @param {Array} courses - مصفوفة الكورسات
     */
    displayCourses(courses) {
        if (!courses.length) {
            this.coursesContainer.innerHTML = '<div class="alert alert-info">لا توجد كورسات متاحة</div>';
            return;
        }

        const coursesHTML = courses.map(course => `
            <div class="course-card">
                <div class="course-header">
                    <img src="${course.thumbnail || 'images/default-course.jpg'}" 
                         alt="${course.title}" 
                         class="course-thumbnail">
                    <h3 class="course-title">${course.title}</h3>
                </div>
                <div class="course-info">
                    <span class="course-language">
                        <i class="fas fa-language"></i>
                        ${course.language}
                    </span>
                    <div class="course-actions">
                        <a href="http://videomx.com/content/lessons.php?course_id=${course.id}" 
                           target="_blank" 
                           class="btn btn-sm btn-success course-lessons-link">
                            <i class="fas fa-list-ul"></i>
                            قائمة الدروس
                        </a>
                        ${course.playlist_url ? `
                      
                        ` : ''}
                        <button class="btn btn-sm btn-primary copy-lessons" data-course-id="${course.id}">
                            <i class="fas fa-copy"></i>
                            نسخ الدروس
                        </button>
                    </div>
                </div>
                <div class="course-description">
                    ${course.description}
                </div>
                <div class="course-sections">
                    <h4>الأقسام</h4>
                    ${this.generateSectionsList(course.sections, course.id)}
                </div>
                <div class="lessons-container" id="lessons-container-${course.id}"></div>
                <div class="d-none" id="lessons-data-${course.id}">
                    ${this.formatLessonsForCopy(course.lessons)}
                </div>
            </div>
        `).join('');

        this.coursesContainer.innerHTML = coursesHTML;
        this.attachEventHandlers();
    }

    /**
     * عرض الأقسام حسب اللغة
     * @param {string} language - اللغة المطلوبة ('all' لجميع اللغات)
     */
    displayLanguageSections(language) {
        // إذا كانت اللغة "الكل"، نعرض أقسام جميع اللغات
        if (language === 'all') {
            const allSectionsHTML = this.languagesData.map(lang => this.generateLanguageSectionsHTML(lang)).join('');
            
            if (!allSectionsHTML) {
                this.languageSectionsContainer.innerHTML = '';
                return;
            }
            
            this.languageSectionsContainer.innerHTML = `
                <div class="language-sections-wrapper">
                    <h2 class="language-sections-title">أقسام جميع اللغات</h2>
                    ${allSectionsHTML}
                </div>
            `;
        } else {
            // نبحث عن اللغة المطلوبة في البيانات
            const languageData = this.languagesData.find(lang => lang.name === language);
            
            if (!languageData || !languageData.sections.length) {
                this.languageSectionsContainer.innerHTML = '';
                return;
            }
            
            this.languageSectionsContainer.innerHTML = `
                <div class="language-sections-wrapper">
                    <h2 class="language-sections-title">أقسام لغة ${language}</h2>
                    ${this.generateLanguageSectionsHTML(languageData)}
                </div>
            `;
        }

        // بعد إضافة HTML إلى الصفحة، نضيف معالجات الأحداث
        this.attachSectionEditHandlers();
    }

    /**
     * إضافة معالجات الأحداث لأزرار تحرير الأقسام
     */
    attachSectionEditHandlers() {
        document.querySelectorAll('.edit-section-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const sectionId = e.currentTarget.dataset.sectionId;
                const sectionName = e.currentTarget.dataset.sectionName;
                const sectionDescription = e.currentTarget.dataset.sectionDescription;
                
                this.showSectionEditModal(sectionId, sectionName, sectionDescription);
            });
        });
    }

    /**
     * عرض نافذة تحرير وصف القسم
     * @param {number} sectionId - معرف القسم
     * @param {string} sectionName - اسم القسم
     * @param {string} sectionDescription - وصف القسم الحالي
     */
    showSectionEditModal(sectionId, sectionName, sectionDescription) {
        Swal.fire({
            title: `تحرير وصف قسم "${sectionName}"`,
            html: `
                <textarea id="section-description-input" 
                          class="form-control" 
                          rows="5" 
                          placeholder="أدخل وصف القسم هنا..."
                          dir="rtl">${sectionDescription}</textarea>
            `,
            showCancelButton: true,
            confirmButtonText: 'حفظ',
            cancelButtonText: 'إلغاء',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const newDescription = document.getElementById('section-description-input').value;
                return this.updateSectionDescription(sectionId, newDescription);
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'تم الحفظ!',
                    text: 'تم تحديث وصف القسم بنجاح',
                    icon: 'success'
                });
            }
        });
    }

    /**
     * تحديث وصف القسم في قاعدة البيانات
     * @param {number} sectionId - معرف القسم
     * @param {string} description - الوصف الجديد
     * @returns {Promise<boolean>} نجاح العملية
     */
    async updateSectionDescription(sectionId, description) {
        try {
            const formData = new FormData();
            formData.append('section_id', sectionId);
            formData.append('description', description);
            
            const response = await fetch('update_section.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                // تحديث الوصف في الواجهة
                const descriptionElement = document.getElementById(`section-description-${sectionId}`);
                if (descriptionElement) {
                    descriptionElement.textContent = description || 'لا يوجد وصف متاح';
                }
                
                // تحديث البيانات المخزنة
                this.updateStoredSectionDescription(sectionId, description);
                
                return true;
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error updating section description:', error);
            Swal.showValidationMessage(`فشل التحديث: ${error.message}`);
            return false;
        }
    }

    /**
     * تحديث وصف القسم في البيانات المخزنة
     * @param {number} sectionId - معرف القسم
     * @param {string} description - الوصف الجديد
     */
    updateStoredSectionDescription(sectionId, description) {
        // تحديث في بيانات اللغات
        this.languagesData.forEach(language => {
            language.sections.forEach(section => {
                if (section.id == sectionId) {
                    section.description = description;
                }
            });
        });
        
        // تحديث في بيانات الكورسات
        this.coursesData.forEach(course => {
            course.sections.forEach(section => {
                if (section.id == sectionId) {
                    section.description = description;
                }
            });
        });
    }

    /**
     * إنشاء HTML لأقسام لغة معينة
     * @param {Object} languageData - بيانات اللغة
     * @returns {string} HTML الأقسام
     */
    generateLanguageSectionsHTML(languageData) {
        if (!languageData.sections.length) {
            return '';
        }
        
        return `
            <div class="language-section-group">
                <h3 class="language-name">${languageData.name}</h3>
                <div class="language-sections">
                    ${languageData.sections.map(section => `
                        <div class="language-section-card">
                            <div class="section-header">
                                <h4 class="section-title">${section.name}</h4>
                                <button class="btn btn-sm btn-outline-primary edit-section-btn" 
                                        data-section-id="${section.id}" 
                                        data-section-name="${section.name}"
                                        data-section-description="${section.description || ''}">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                            <p class="section-description" id="section-description-${section.id}">
                                ${section.description || 'لا يوجد وصف متاح'}
                            </p>
                            <div class="section-courses">
                                ${this.getCoursesForSection(section.id, languageData.id).map(course => `
                                    <a href="http://videomx.com/content/lessons.php?course_id=${course.id}" 
                                       class="section-course-link" 
                                       target="_blank">
                                        <i class="fas fa-graduation-cap"></i>
                                        ${course.title}
                                    </a>
                                `).join('')}
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    /**
     * الحصول على الكورسات التي تحتوي على قسم معين
     * @param {number} sectionId - معرف القسم
     * @param {number} languageId - معرف اللغة
     * @returns {Array} الكورسات المرتبطة بالقسم
     */
    getCoursesForSection(sectionId, languageId) {
        return this.coursesData.filter(course => 
            course.language_id === languageId && 
            course.sections.some(section => section.id === sectionId)
        );
    }

    /**
     * إنشاء قائمة الأقسام
     * @param {Array} sections - مصفوفة الأقسام
     * @param {number} courseId - معرف الكورس
     * @returns {string} HTML قائمة الأقسام
     */
    generateSectionsList(sections, courseId) {
        if (!sections.length) {
            return '<p class="no-sections">لا توجد أقسام متاحة</p>';
        }

        return `
            <div class="sections-grid">
                ${sections.map(section => `
                    <button class="section-button" 
                            data-course-id="${courseId}" 
                            data-section-id="${section.id}"
                            data-section-name="${section.name}">
                        <i class="fas fa-book-open"></i>
                        ${section.name}
                    </button>
                `).join('')}
            </div>
        `;
    }

    /**
     * تنسيق الدروس للنسخ
     * @param {Array} lessons - مصفوفة الدروس
     * @returns {string} نص الدروس المنسق
     */
    formatLessonsForCopy(lessons) {
        if (!lessons.length) {
            return 'لا توجد دروس متاحة';
        }

        return lessons.map(lesson => 
            `اسم الدرس: ${lesson.title || 'بدون عنوان'}\n` +
            `القسم: ${lesson.section || 'بدون قسم'}\n` +
            (lesson.note ? `الملاحظات: ${lesson.note}\n` : '') +
            `=============\n`
        ).join('\n');
    }

    /**
     * إضافة معالجات الأحداث
     */
    attachEventHandlers() {
        // معالج أحداث أزرار نسخ الدروس
        document.querySelectorAll('.copy-lessons').forEach(button => {
            button.addEventListener('click', (e) => {
                const courseId = e.currentTarget.dataset.courseId;
                this.copyLessons(courseId);
            });
        });

        // معالج أحداث أزرار الأقسام
        document.querySelectorAll('.section-button').forEach(button => {
            button.addEventListener('click', (e) => {
                const courseId = e.currentTarget.dataset.courseId;
                const sectionId = e.currentTarget.dataset.sectionId;
                const sectionName = e.currentTarget.dataset.sectionName;
                this.showLessonsBySection(courseId, sectionId, sectionName);
            });
        });
    }

    /**
     * نسخ الدروس إلى الحافظة
     * @param {number} courseId - معرف الكورس
     */
    async copyLessons(courseId) {
        try {
            const lessonsText = document.getElementById(`lessons-data-${courseId}`).textContent;
            
            if (!lessonsText || lessonsText === 'لا توجد دروس متاحة') {
                this.showToast('لا توجد دروس متاحة للنسخ', 'warning');
                return;
            }
            
            // استخدام واجهة Clipboard API
            if (navigator.clipboard) {
                await navigator.clipboard.writeText(lessonsText);
                this.showToast('تم نسخ الدروس بنجاح!', 'success');
            } else {
                // طريقة بديلة للمتصفحات القديمة
                const textArea = document.createElement('textarea');
                textArea.value = lessonsText;
                document.body.appendChild(textArea);
                textArea.select();
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                
                if (successful) {
                    this.showToast('تم نسخ الدروس بنجاح!', 'success');
                } else {
                    throw new Error('فشل النسخ');
                }
            }
        } catch (err) {
            console.error('Error copying lessons:', err);
            this.showToast('حدث خطأ أثناء النسخ', 'error');
        }
    }

    /**
     * عرض الدروس حسب القسم
     * @param {number} courseId - معرف الكورس
     * @param {number} sectionId - معرف القسم
     * @param {string} sectionName - اسم القسم
     */
    showLessonsBySection(courseId, sectionId, sectionName) {
        // البحث عن الكورس في البيانات المخزنة
        const course = this.coursesData.find(c => c.id == courseId);
        if (!course) {
            this.showToast('لم يتم العثور على الكورس', 'error');
            return;
        }

        // تصفية الدروس حسب القسم
        const sectionLessons = course.lessons.filter(lesson => 
            lesson.section === sectionName
        );

        // الحاوية التي سيتم عرض الدروس فيها
        const lessonsContainer = document.getElementById(`lessons-container-${courseId}`);
        
        if (!sectionLessons.length) {
            lessonsContainer.innerHTML = `
                <div class="section-lessons">
                    <div class="section-lessons-header">
                        <h5>${sectionName}</h5>
                    </div>
                    <div class="alert alert-info">لا توجد دروس في هذا القسم</div>
                </div>
            `;
            return;
        }

        // إنشاء قائمة الدروس
        const lessonsHTML = `
            <div class="section-lessons">
                <div class="section-lessons-header">
                    <h5>${sectionName}</h5>
                    <button class="btn btn-sm btn-outline-primary copy-section-lessons" 
                            data-course-id="${courseId}" 
                            data-section-id="${sectionId}">
                        <i class="fas fa-copy"></i> نسخ دروس القسم
                    </button>
                </div>
                <ol class="lessons-list">
                    ${sectionLessons.map((lesson, index) => `
                        <li class="lesson-item">
                            <span class="lesson-title">${lesson.title || 'بدون عنوان'}</span>
                            ${lesson.note ? `<span class="lesson-note">ملاحظات متاحة</span>` : ''}
                        </li>
                    `).join('')}
                </ol>
            </div>
        `;

        lessonsContainer.innerHTML = lessonsHTML;

        // إضافة معالج حدث لزر نسخ دروس القسم
        const copyButton = lessonsContainer.querySelector('.copy-section-lessons');
        if (copyButton) {
            copyButton.addEventListener('click', () => {
                this.copySectionLessons(courseId, sectionName);
            });
        }

        // التمرير إلى قسم الدروس
        lessonsContainer.scrollIntoView({ behavior: 'smooth' });
    }

    /**
     * نسخ دروس قسم معين
     * @param {number} courseId - معرف الكورس
     * @param {string} sectionName - اسم القسم
     */
    async copySectionLessons(courseId, sectionName) {
        try {
            const course = this.coursesData.find(c => c.id == courseId);
            if (!course) {
                throw new Error('لم يتم العثور على الكورس');
            }

            // البحث عن وصف القسم
            let sectionDescription = '';
            const section = course.sections.find(s => s.name === sectionName);
            if (section) {
                sectionDescription = section.description || 'لا يوجد وصف متاح';
            }

            const sectionLessons = course.lessons.filter(lesson => 
                lesson.section === sectionName
            );

            if (!sectionLessons.length) {
                this.showToast('لا توجد دروس في هذا القسم', 'warning');
                return;
            }

            // إضافة اسم القسم والوصف في بداية النص
            const lessonsText = 
                `القسم: ${sectionName}\n` +
                `الوصف: ${sectionDescription}\n` +
                `=============\n\n` +
                sectionLessons.map(lesson => 
                    `اسم الدرس: ${lesson.title || 'بدون عنوان'}\n` +
                    `القسم: ${lesson.section || 'بدون قسم'}\n` +
                    (lesson.note ? `الملاحظات: ${lesson.note}\n` : '') +
                    `=============\n`
                ).join('\n');

            if (navigator.clipboard) {
                await navigator.clipboard.writeText(lessonsText);
                this.showToast('تم نسخ دروس القسم بنجاح!', 'success');
            } else {
                const textArea = document.createElement('textarea');
                textArea.value = lessonsText;
                document.body.appendChild(textArea);
                textArea.select();
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                
                if (successful) {
                    this.showToast('تم نسخ دروس القسم بنجاح!', 'success');
                } else {
                    throw new Error('فشل النسخ');
                }
            }
        } catch (err) {
            console.error('Error copying section lessons:', err);
            this.showToast('حدث خطأ أثناء نسخ دروس القسم', 'error');
        }
    }

    /**
     * عرض إشعار
     * @param {string} message - نص الإشعار
     * @param {string} type - نوع الإشعار (success, error, warning, info)
     */
    showToast(message, type = 'info') {
        const toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });

        toast.fire({
            icon: type,
            title: message
        });
    }

    /**
     * إظهار مؤشر التحميل
     */
    showLoading() {
        this.loadingElement.classList.remove('d-none');
        this.errorElement.classList.add('d-none');
    }

    /**
     * إخفاء مؤشر التحميل
     */
    hideLoading() {
        this.loadingElement.classList.add('d-none');
    }

    /**
     * عرض رسالة خطأ
     * @param {string} message - نص رسالة الخطأ
     */
    showError(message) {
        this.errorElement.textContent = message;
        this.errorElement.classList.remove('d-none');
    }
}

// تهيئة مدير الكورسات عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    const coursesManager = new CoursesManager();
    coursesManager.loadCourses();
}); 