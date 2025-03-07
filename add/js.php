    <script>
        // تهيئة المتغيرات العامة
        let isProcessing = false;

        // متغيرات عامة للصفحات
        const pageSize = 10; // عدد العناصر في كل صفحة
        const pageState = {
            languages: { currentPage: 1, totalPages: 1, data: [] },
            sections: { currentPage: 1, totalPages: 1, data: [] },
            statuses: { currentPage: 1, totalPages: 1, data: [] }
        };

        // عند تحميل الصفحة
        $(document).ready(function() {
            // تهيئة الأحداث
            initializeEvents();
            // تحميل البيانات الأولية
            loadInitialData();
            initializeLanguageManagement();
            loadStatuses();
            loadSections();
            initializeStatusManagement();
            
            // استعادة حالة الأقسام
            ['languageFormSection', 'statusFormSection', 'sectionFormSection'].forEach(sectionId => {
                const state = localStorage.getItem(sectionId + '_state');
                if (state === 'collapsed') {
                    toggleSection(sectionId);
                }
            });
        });

        /**
         * تهيئة الأحداث
         */
        function initializeEvents() {
            // تحديث الكورسات
            $('#refreshCoursesBtn').on('click', function() {
                refreshCourses();
            });

            // نموذج إضافة كورس
            $('#courseForm').on('submit', handleCourseSubmit);

            // البحث في الجداول
            $('.table-search').on('input', handleTableSearch);

            // الترتيب
            $('.sortable').on('click', handleTableSort);

            // تحديث جدول اللغات عند النقر على زر التحديث
            $('#refreshLanguagesBtn').on('click', function() {
                refreshLanguagesTable();
            });

            // البحث في جدول اللغات
            $('#languageSearch').on('input', function() {
                const searchValue = $(this).val().toLowerCase();
                $('#languagesTable tbody tr').each(function() {
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(text.includes(searchValue));
                });
            });
        }

        /**
         * تحميل البيانات الأولية
         */
        function loadInitialData() {
            refreshCourses();
            loadLanguages();
            loadSections();
            
            // تحديث تلقائي كل دقيقة
            setInterval(refreshCourses, 60000);
        }

        /**
         * تحديث جدول الكورسات
         */
        function refreshCourses() {
            const tableBody = $('#coursesTable tbody');
            showTableLoading(tableBody);
            
            $.get('api/courses.php')
                .done(function(response) {
                    if (response.success) {
                        updateCoursesTable(response.data);
                    } else {
                        showError('فشل في تحميل الكورسات');
                    }
                })
                .fail(function() {
                    showError('فشل في الاتصال بالخادم');
                });
        }

        /**
         * تحديث جدول الكورسات
         * @param {Array} courses بيانات الكورسات
         */
        function updateCoursesTable(courses) {
            const tableBody = $('#coursesTable tbody');
            
            if (!courses || courses.length === 0) {
                tableBody.html(`
                    <tr>
                        <td colspan="5" class="text-center">لا توجد كورسات مضافة</td>
                    </tr>
                `);
                return;
            }

            const rows = courses.map(course => `
                <tr data-id="${course.id}">
                    <td>${course.id}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="${course.thumbnail}" 
                                 alt="" 
                                 class="rounded me-2" 
                                 style="width: 40px; height: 40px; object-fit: cover;">
                            <div>
                                <div class="fw-bold">${course.title}</div>
                                <small class="text-muted">${course.lessons_count} درس</small>
                            </div>
                        </div>
                    </td>
                    <td>${course.language_name}</td>
                    <td>${course.lessons_count}</td>
                    <td>
                        <button class="btn btn-sm btn-danger delete-course" 
                                data-id="${course.id}" 
                                data-title="${course.title}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            
            tableBody.html(rows);
            
            // تهيئة أزرار الحذف
            $('.delete-course').on('click', function() {
                const id = $(this).data('id');
                const title = $(this).data('title');
                deleteCourse(id, title);
            });
        }

        /**
         * معالجة تقديم نموذج إضافة كورس
         * @param {Event} e حدث التقديم
         */
        function handleCourseSubmit(e) {
            e.preventDefault();
            
            const formData = {
                playlist_url: $('#youtubePlaylist').val().trim(),
                language_id: $('#courseLanguage').val()
            };

            // التحقق من البيانات
            if (!formData.playlist_url || !formData.language_id) {
                showError('جميع الحقول المطلوبة يجب ملؤها');
                return;
            }

            // التحقق من صحة رابط YouTube
            if (!formData.playlist_url.match(/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.*list=([^&]+)/)) {
                showError('رابط قائمة التشغيل غير صالح');
                return;
            }

            // تعطيل الزر وإظهار حالة التحميل
            const submitBtn = $('#submitCourse');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> جاري الإضافة...').prop('disabled', true);

            // إرسال الطلب
            $.ajax({
                url: 'api/courses.php',
                method: 'POST',
                data: JSON.stringify(formData),
                contentType: 'application/json'
            })
            .done(function(response) {
                if (response.success) {
                    showSuccess(response.message);
                    $('#courseForm')[0].reset();
                    
                    // بدء مراقبة التقدم
                    if (response.data && response.data.id) {
                        startProgressMonitoring(response.data.id);
                    }
                } else {
                    showError(response.message);
                }
            })
            .fail(function() {
                showError('فشل في إضافة الكورس');
            })
            .always(function() {
                submitBtn.html(originalText).prop('disabled', false);
            });
        }

        /**
         * معالجة البحث في الجداول
         * @param {Event} e حدث الإدخال
         */
        function handleTableSearch(e) {
            const searchValue = $(this).val().toLowerCase();
            const tableId = $(this).data('table');
            
            $(`#${tableId} tbody tr`).each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.includes(searchValue));
            });
        }

        /**
         * معالجة ترتيب الجداول
         * @param {Event} e حدث النقر
         */
        function handleTableSort(e) {
            const th = $(this);
            const table = th.closest('table');
            const rows = table.find('tbody tr').toArray();
            const column = th.index();
            const isAsc = th.hasClass('asc');
            
            // إزالة الأسهم من جميع العناوين
            table.find('th').removeClass('asc desc');
            
            // إضافة السهم المناسب
            th.addClass(isAsc ? 'desc' : 'asc');
            
            // ترتيب الصفوف
            rows.sort((a, b) => {
                const aText = $(a).find('td').eq(column).text();
                const bText = $(b).find('td').eq(column).text();
                
                if (!isNaN(aText) && !isNaN(bText)) {
                    return isAsc ? bText - aText : aText - bText;
                }
                
                return isAsc ? 
                    bText.localeCompare(aText, 'ar') : 
                    aText.localeCompare(bText, 'ar');
            });
            
            // تحديث الجدول
            table.find('tbody').empty().append(rows);
        }

        /**
         * إظهار حالة التحميل في الجدول
         * @param {jQuery} tableBody جسم الجدول
         */
        function showTableLoading(tableBody) {
            tableBody.html(`
                <tr>
                    <td colspan="7" class="text-center">
                        <i class="fas fa-spinner fa-spin me-2"></i> جاري التحميل...
                    </td>
                </tr>
            `);
        }

        /**
         * حذف كورس
         * @param {number} id معرف الكورس
         * @param {string} title عنوان الكورس
         */
        function deleteCourse(id, title) {
            Swal.fire({
                title: 'تأكيد الحذف',
                text: `هل أنت متأكد من حذف الكورس "${title}"؟`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، حذف',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `api/courses.php?id=${id}`,
                        method: 'DELETE'
                    })
                    .done(function(response) {
                        if (response.success) {
                            showSuccess('تم حذف الكورس بنجاح');
                            refreshCourses();
                        } else {
                            showError(response.message);
                        }
                    })
                    .fail(function() {
                        showError('فشل في حذف الكورس');
                    });
                }
            });
        }

        /**
         * عرض تفاصيل الكورس
         * @param {number} id معرف الكورس
         */
        function viewCourseDetails(id) {
            Swal.fire({
                title: 'تفاصيل الكورس',
                text: 'جاري التحميل...',
                showConfirmButton: false,
                allowOutsideClick: false
            });

            $.get(`api/courses.php?id=${id}`)
                .done(function(response) {
                    if (response.success) {
                        const course = response.data;
                        Swal.fire({
                            title: course.title,
                            html: `
                                <div class="text-start">
                                    <p><strong>اللغة:</strong> ${course.language_name}</p>
                                    <p><strong>عدد الدروس:</strong> ${course.lessons_count}</p>
                                    <p><strong>المدة الإجمالية:</strong> ${course.total_duration_formatted}</p>
                                    <p><strong>تاريخ الإضافة:</strong> ${formatDate(course.created_at)}</p>
                                    <p><strong>الحالة:</strong> ${getStatusText(course.processing_status)}</p>
                                </div>
                            `,
                            confirmButtonText: 'إغلاق'
                        });
                    } else {
                        showError('فشل في تحميل تفاصيل الكورس');
                    }
                })
                .fail(function() {
                    showError('فشل في الاتصال بالخادم');
                });
        }

        /**
         * تحميل اللغات
         */
        function loadLanguages() {
            $.get('api/languages.php')
                .done(function(response) {
                    if (response.success) {
                        pageState.languages.data = response.data;
                        pageState.languages.totalPages = Math.ceil(response.data.length / pageSize);
                        updateTable('languages');
                        updatePaginationInfo('languages');
                        
                        // تحديث قائمة اللغات في نموذج إضافة الكورس
                        const courseLanguageSelect = $('#courseLanguage');
                        courseLanguageSelect.empty().append('<option value="">اختر اللغة...</option>');
                        
                        // تحديث قائمة اللغات في نموذج إضافة الحالة
                        const statusLanguageSelect = $('#statusLanguage');
                        statusLanguageSelect.empty().append('<option value="">اختر اللغة...</option>');
                        
                        // تحديث قائمة اللغات في نموذج إضافة القسم
                        const sectionLanguageSelect = $('#sectionLanguage');
                        sectionLanguageSelect.empty().append('<option value="">اختر اللغة...</option>');
                        
                        response.data.forEach(function(language) {
                            const option = `<option value="${language.id}">${language.name}</option>`;
                            courseLanguageSelect.append(option);
                            statusLanguageSelect.append(option);
                            sectionLanguageSelect.append(option);
                        });
                    }
                })
                .fail(function() {
                    showError('فشل في تحميل اللغات');
                });
        }

        /**
         * تحميل الأقسام
         */
        function loadSections() {
            $.get('api/sections.php')
                .done(function(response) {
                    if (response.success) {
                        pageState.sections.data = response.data;
                        pageState.sections.totalPages = Math.ceil(response.data.length / pageSize);
                        updateTable('sections');
                        updatePaginationInfo('sections');
                    }
                })
                .fail(function() {
                    showError('فشل في تحميل الأقسام');
                });
        }

        /**
         * تحديث جدول الأقسام
         * @param {Array} sections بيانات الأقسام
         */
        function updateSectionsTable(sections) {
            const tableBody = $('#sectionsTable tbody');
            
            if (!sections || sections.length === 0) {
                tableBody.html('<tr><td colspan="5" class="text-center">لا توجد أقسام</td></tr>');
                return;
            }

            const rows = sections.map((section, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${section.name}</td>
                    <td>${section.language_name}</td>
                    <td>${section.courses_count || 0}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="deleteSection(${section.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            
            tableBody.html(rows);
        }

        /**
         * معالجة نموذج إضافة قسم
         */
        $('#sectionForm').on('submit', function(e) {
            e.preventDefault();
            
            const languageId = $('#sectionLanguage').val();
            const sections = $('#sectionTags').val();

            if (!languageId) {
                showError('يرجى اختيار اللغة');
                return;
            }

            if (!sections || sections.length === 0) {
                showError('يرجى إدخال قسم واحد على الأقل');
                return;
            }

            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> جاري الإضافة...').prop('disabled', true);

            // إضافة كل قسم على حدة
            const promises = sections.map(name => 
                $.ajax({
                    url: 'api/sections.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        name: name.trim(),
                        language_id: languageId
                    })
                })
            );

            Promise.all(promises)
                .then(responses => {
                    const successCount = responses.filter(r => r.success).length;
                    if (successCount > 0) {
                        showSuccess(`تم إضافة ${successCount} قسم بنجاح`);
                        $('#sectionTags').val(null).trigger('change');
                        loadSections();
                    }
                })
                .catch(() => {
                    showError('حدث خطأ أثناء إضافة الأقسام');
                })
                .finally(() => {
                    submitBtn.html(originalText).prop('disabled', false);
                });
        });

        /**
         * حذف قسم
         * @param {number} id معرف القسم
         */
        function deleteSection(id) {
            Swal.fire({
                title: 'تأكيد الحذف',
                text: 'هل أنت متأكد من حذف هذا القسم؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، حذف',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `api/sections.php?id=${id}`,
                        method: 'DELETE'
                    })
                    .done(function(response) {
                        if (response.success) {
                            showSuccess('تم حذف القسم بنجاح');
                            loadSections();
                        } else {
                            showError(response.message);
                        }
                    })
                    .fail(function() {
                        showError('فشل في حذف القسم');
                    });
                }
            });
        }

        /**
         * الحصول على لون الحالة
         * @param {string} status حالة المعالجة
         * @returns {string} لون الحالة
         */
        function getStatusBadgeClass(status) {
            const classes = {
                'pending': 'warning',
                'processing': 'info',
                'completed': 'success',
                'error': 'danger'
            };
            return classes[status] || 'secondary';
        }

        /**
         * الحصول على نص الحالة
         * @param {string} status حالة المعالجة
         * @returns {string} نص الحالة
         */
        function getStatusText(status) {
            const texts = {
                'pending': 'قيد الانتظار',
                'processing': 'جاري المعالجة',
                'completed': 'مكتمل',
                'error': 'خطأ'
            };
            return texts[status] || status;
        }

        /**
         * تنسيق التاريخ
         * @param {string} date التاريخ
         * @returns {string} التاريخ المنسق
         */
        function formatDate(date) {
            return new Date(date).toLocaleDateString('ar-SA', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        /**
         * عرض رسالة نجاح
         * @param {string} message نص الرسالة
         */
        function showSuccess(message) {
            Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "center",
                style: {
                    background: "linear-gradient(to right, #00b09b, #96c93d)",
                    fontFamily: 'Cairo'
                }
            }).showToast();
        }

        /**
         * عرض رسالة خطأ
         * @param {string} message نص الرسالة
         */
        function showError(message) {
            Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "center",
                style: {
                    background: "linear-gradient(to right, #ff5f6d, #ffc371)",
                    fontFamily: 'Cairo'
                }
            }).showToast();
        }

        /**
         * بدء مراقبة تقدم معالجة الكورس
         * @param {number} courseId معرف الكورس
         */
        function startProgressMonitoring(courseId) {
            // عرض مودال التقدم
            const progressModal = new bootstrap.Modal(document.getElementById('progressModal'));
            progressModal.show();

            let isPaused = false;
            let startTime = Date.now();
            
            // تهيئة أزرار المودال
            const pauseResumeBtn = document.querySelector('.pause-resume-btn');
            pauseResumeBtn.addEventListener('click', function() {
                isPaused = !isPaused;
                this.innerHTML = isPaused ? 
                    '<i class="fas fa-play"></i> استئناف' : 
                    '<i class="fas fa-pause"></i> إيقاف مؤقت';
            });

            // دالة تحديث التقدم
            function updateProgress() {
                if (isPaused) {
                    return setTimeout(updateProgress, 1000);
                }

                $.get(`api/progress.php?course_id=${courseId}`)
                    .done(function(response) {
                        if (!response.success) {
                            showError(response.message);
                            return;
                        }

                        const data = response.data;
                        
                        // تحديث شريط التقدم
                        const progressBar = document.querySelector('.progress-bar');
                        const progress = Math.round(data.progress);
                        progressBar.style.width = `${progress}%`;
                        progressBar.setAttribute('aria-valuenow', progress);
                        progressBar.textContent = `${progress}%`;

                        // تحديث النصوص
                        document.querySelector('.progress-text').textContent = 
                            `${data.current} من ${data.total}`;
                        document.querySelector('.progress-percentage').textContent = 
                            `${progress}%`;

                        // تحديث الإحصائيات
                        document.querySelector('.processed-count').textContent = data.current;
                        document.querySelector('.remaining-count').textContent = 
                            data.total - data.current;

                        // تحديث الوقت المنقضي
                        const elapsedTime = Math.floor((Date.now() - startTime) / 1000);
                        document.querySelector('.elapsed-time').textContent = 
                            formatDuration(elapsedTime);

                        // تحديث السرعة والوقت المتبقي
                        if (data.speed) {
                            document.querySelector('.processing-speed').textContent = 
                                `السرعة: ${data.speed.toFixed(1)} درس/دقيقة`;
                        }
                        
                        if (data.time_remaining) {
                            document.querySelector('.estimated-time').textContent = 
                                `الوقت المتبقي: ${formatDuration(data.time_remaining)}`;
                        }

                        // تحديث آخر درس
                        document.querySelector('.latest-lesson').innerHTML = `
                            <div class="text-center">
                                <i class="fas fa-file-video text-primary me-2"></i>
                                ${data.latest_lesson}
                            </div>
                        `;

                        // تحديث حالة المعالجة
                        document.querySelector('.processing-status').textContent = 
                            getStatusText(data.status);

                        // التحقق من اكتمال المعالجة
                        if (data.status === 'completed') {
                            showSuccess('تم إضافة الكورس وجميع الدروس بنجاح');
                            progressModal.hide();
                            refreshCourses();
                            return;
                        } else if (data.status === 'error') {
                            showError(data.message);
                            progressModal.hide();
                            return;
                        }

                        // استمرار المراقبة
                        setTimeout(updateProgress, 1000);
                    })
                    .fail(function() {
                        showError('فشل في تحديث حالة التقدم');
                        setTimeout(updateProgress, 2000);
                    });
            }

            // بدء المراقبة
            updateProgress();
        }

        /**
         * تنسيق المدة الزمنية
         * @param {number} seconds الثواني
         * @returns {string} المدة المنسقة
         */
        function formatDuration(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            
            return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        }

        /**
         * تهيئة إدارة اللغات
         */
        function initializeLanguageManagement() {
            // تحميل اللغات عند بدء التطبيق
            loadLanguages();
            refreshLanguagesTable();
            
            // تهيئة التاجات للغات
            $('#languageTags').select2({
                tags: true,
                tokenSeparators: [',', ' '],
                dir: 'rtl',
                placeholder: 'اكتب اسم اللغة واضغط Enter',
                language: {
                    noResults: function() {
                        return "اكتب اسم اللغة الجديدة";
                    }
                }
            });

            // معالجة إضافة اللغات
            $('#languageForm').on('submit', function(e) {
                e.preventDefault();
                
                const languages = $('#languageTags').val();
                if (!languages || languages.length === 0) {
                    showError('يرجى إدخال لغة واحدة على الأقل');
                    return;
                }

                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> جاري الإضافة...').prop('disabled', true);

                // إضافة كل لغة على حدة
                const promises = languages.map(name => 
                    $.ajax({
                        url: 'api/languages.php',
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({ name: name.trim() })
                    })
                );

                Promise.all(promises)
                    .then(responses => {
                        const successCount = responses.filter(r => r.success).length;
                        if (successCount > 0) {
                            showSuccess(`تم إضافة ${successCount} لغة بنجاح`);
                            $('#languageTags').val(null).trigger('change');
                            loadLanguages();
                            refreshLanguagesTable();
                        }
                    })
                    .catch(() => {
                        showError('حدث خطأ أثناء إضافة اللغات');
                    })
                    .finally(() => {
                        submitBtn.html(originalText).prop('disabled', false);
                    });
            });

            // البحث في جدول اللغات
            $('#languageSearch').on('input', function() {
                const searchValue = $(this).val().toLowerCase();
                $('#languagesTable tbody tr').each(function() {
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(text.includes(searchValue));
                });
            });
        }

        /**
         * تحديث جدول اللغات
         */
        function refreshLanguagesTable() {
            const tableBody = $('#languagesTable tbody');
            tableBody.html('<tr><td colspan="4" class="text-center">جاري التحميل...</td></tr>');

            $.get('api/languages.php')
                .done(function(response) {
                    if (response.success) {
                        const languages = response.data;
                        if (languages.length === 0) {
                            tableBody.html('<tr><td colspan="4" class="text-center">لا توجد لغات</td></tr>');
                            return;
                        }

                        const rows = languages.map((language, index) => `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${language.name}</td>
                                <td>${language.courses_count || 0}</td>
                                <td>
                                    <button class="btn btn-sm btn-danger" onclick="deleteLanguage(${language.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `).join('');

                        tableBody.html(rows);
                    }
                })
                .fail(function() {
                    tableBody.html('<tr><td colspan="4" class="text-center text-danger">فشل في تحميل البيانات</td></tr>');
                });
        }

        /**
         * حذف لغة
         * @param {number} id معرف اللغة
         */
        function deleteLanguage(id) {
            Swal.fire({
                title: 'تأكيد الحذف',
                text: 'هل أنت متأكد من حذف هذه اللغة؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، حذف',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `api/languages.php?id=${id}`,
                        method: 'DELETE'
                    })
                    .done(function(response) {
                        if (response.success) {
                            showSuccess('تم حذف اللغة بنجاح');
                            loadLanguages();
                            refreshLanguagesTable();
                        } else {
                            showError(response.message);
                        }
                    })
                    .fail(function() {
                        showError('فشل في حذف اللغة');
                    });
                }
            });
        }

        /**
         * تحميل الحالات
         */
        function loadStatuses() {
            $.get('api/statuses.php')
                .done(function(response) {
                    if (response.success) {
                        pageState.statuses.data = response.data;
                        pageState.statuses.totalPages = Math.ceil(response.data.length / pageSize);
                        updateTable('statuses');
                        updatePaginationInfo('statuses');
                    }
                })
                .fail(function() {
                    showError('فشل في تحميل الحالات');
                });
        }

        /**
         * تحديث جدول الحالات
         * @param {Array} statuses بيانات الحالات
         */
        function updateStatusesTable(statuses) {
            const tableBody = $('#statusesTable tbody');
            
            if (!statuses || statuses.length === 0) {
                tableBody.html('<tr><td colspan="5" class="text-center">لا توجد حالات</td></tr>');
                return;
            }

            const rows = statuses.map((status, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${status.language_name}</td>
                    <td>${status.name}</td>
                    <td>${status.courses_count || 0}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="deleteStatus(${status.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            
            tableBody.html(rows);
        }

        /**
         * تهيئة إدارة الحالات
         */
        function initializeStatusManagement() {
            // تهيئة التاجات للحالات
            $('#statusTags').select2({
                tags: true,
                tokenSeparators: [',', ' '],
                dir: 'rtl',
                placeholder: 'اكتب اسم الحالة واضغط Enter',
                width: '100%',
                language: {
                    noResults: function() {
                        return "اكتب اسم الحالة الجديدة";
                    }
                },
                createTag: function(params) {
                    return {
                        id: params.term,
                        text: params.term,
                        newOption: true
                    };
                }
            });

            // تحميل اللغات في قائمة الحالات
            loadLanguagesForStatuses();

            // معالجة إضافة الحالات
            $('#statusForm').on('submit', function(e) {
                e.preventDefault();
                
                const languageId = $('#statusLanguage').val();
                const statuses = $('#statusTags').val();

                if (!languageId) {
                    showError('يرجى اختيار اللغة');
                    return;
                }

                if (!statuses || statuses.length === 0) {
                    showError('يرجى إدخال حالة واحدة على الأقل');
                    return;
                }

                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> جاري الإضافة...').prop('disabled', true);

                // إضافة كل حالة على حدة
                const promises = statuses.map(name => 
                    $.ajax({
                        url: 'api/statuses.php',
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            name: name.trim(),
                            language_id: languageId
                        })
                    })
                );

                Promise.all(promises)
                    .then(responses => {
                        const successCount = responses.filter(r => r.success).length;
                        if (successCount > 0) {
                            showSuccess(`تم إضافة ${successCount} حالة بنجاح`);
                            $('#statusTags').val(null).trigger('change');
                            refreshStatusesTable();
                        }
                    })
                    .catch(() => {
                        showError('حدث خطأ أثناء إضافة الحالات');
                    })
                    .finally(() => {
                        submitBtn.html(originalText).prop('disabled', false);
                    });
            });
        }

        /**
         * تحميل اللغات في قائمة الحالات
         */
        function loadLanguagesForStatuses() {
            $.get('api/languages.php')
                .done(function(response) {
                    if (response.success) {
                        const languages = response.data;
                        const select = $('#statusLanguage');
                        select.empty().append('<option value="">اختر اللغة...</option>');
                        
                        languages.forEach(function(language) {
                            select.append(`<option value="${language.id}">${language.name}</option>`);
                        });
                    }
                })
                .fail(function() {
                    showError('فشل في تحميل اللغات');
                });
        }

        /**
         * تحديث جدول الحالات
         */
        function refreshStatusesTable() {
            const tableBody = $('#statusesTable tbody');
            tableBody.html('<tr><td colspan="5" class="text-center">جاري التحميل...</td></tr>');

            $.get('api/statuses.php')
                .done(function(response) {
                    if (response.success) {
                        const statuses = response.data;
                        if (statuses.length === 0) {
                            tableBody.html('<tr><td colspan="5" class="text-center">لا توجد حالات</td></tr>');
                            return;
                        }

                        const rows = statuses.map((status, index) => `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${status.language_name}</td>
                                <td>${status.name}</td>
                                <td>${status.courses_count || 0}</td>
                                <td>
                                    <button class="btn btn-sm btn-danger" onclick="deleteStatus(${status.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `).join('');

                        tableBody.html(rows);
                    }
                })
                .fail(function() {
                    tableBody.html('<tr><td colspan="5" class="text-center text-danger">فشل في تحميل البيانات</td></tr>');
                });
        }

        // تهيئة الصفحة عند التحميل
        $(document).ready(function() {
            initializeStatusManagement();
        });

        // تهيئة التاجات للأقسام
        $('#sectionTags').select2({
            tags: true,
            tokenSeparators: [',', ' '],
            dir: 'rtl',
            placeholder: 'اكتب اسم القسم واضغط Enter',
            language: {
                noResults: function() {
                    return "اكتب اسم القسم الجديد";
                }
            }
        });

        // تحديث CSS للمسافات
        $('.custom-card').css('margin-bottom', '2rem');
        $('.custom-card:last-child').css('margin-bottom', '0');

        // حذف أزرار التحديث والتصدير
        $('#refreshCoursesBtn, #exportBtn').remove();

        // تجاهل تحذيرات Tailwind
        if (window.console && console.warn) {
            const originalWarn = console.warn;
            console.warn = function(message) {
                if (message.indexOf('cdn.tailwindcss.com') === -1) {
                    originalWarn.apply(console, arguments);
                }
            };
        }

        /**
         * حذف جميع الدروس
         */
        function deleteAllLessons() {
            Swal.fire({
                title: 'تأكيد الحذف',
                text: 'هل أنت متأكد من حذف جميع الدروس؟ لا يمكن التراجع عن هذه العملية!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، حذف الكل',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/bulk_operations.php', { action: 'delete_all_lessons' })
                        .done(function(response) {
                            if (response.success) {
                                showSuccess(response.message);
                                refreshCoursesTable();
                            } else {
                                showError(response.message);
                            }
                        })
                        .fail(function() {
                            showError('فشل في تنفيذ العملية');
                        });
                }
            });
        }

        /**
         * إضافة البيانات الافتراضية
         */
        function addDefaultData() {
            Swal.fire({
                title: 'تأكيد الإضافة',
                text: 'هل تريد إضافة البيانات الافتراضية؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'نعم، إضافة',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/bulk_operations.php', { action: 'add_default_data' })
                        .done(function(response) {
                            if (response.success) {
                                showSuccess(response.message);
                                loadLanguages();
                                loadStatuses();
                                loadSections();
                            } else {
                                showError(response.message);
                            }
                        })
                        .fail(function() {
                            showError('فشل في تنفيذ العملية');
                        });
                }
            });
        }

        /**
         * إضافة الحالات الافتراضية
         */
        function addDefaultStatuses() {
            Swal.fire({
                title: 'تأكيد الإضافة',
                text: 'هل تريد إضافة الحالات الافتراضية؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'نعم، إضافة',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/bulk_operations.php', { action: 'add_default_statuses' })
                        .done(function(response) {
                            if (response.success) {
                                showSuccess(response.message);
                                loadStatuses();
                            } else {
                                showError(response.message);
                            }
                        })
                        .fail(function() {
                            showError('فشل في تنفيذ العملية');
                        });
                }
            });
        }

        /**
         * إضافة لغات البرمجة
         */
        function addDefaultLanguages() {
            Swal.fire({
                title: 'تأكيد الإضافة',
                text: 'هل تريد إضافة لغات البرمجة الافتراضية؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'نعم، إضافة',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/bulk_operations.php', { action: 'add_default_languages' })
                        .done(function(response) {
                            if (response.success) {
                                showSuccess(response.message);
                                loadLanguages();
                            } else {
                                showError(response.message);
                            }
                        })
                        .fail(function() {
                            showError('فشل في تنفيذ العملية');
                        });
                }
            });
        }

        /**
         * حذف جميع البيانات من جميع الجداول
         */
        function truncateAllTables() {
            Swal.fire({
                title: 'تأكيد حذف جميع البيانات',
                text: 'هل أنت متأكد من حذف جميع البيانات من جميع الجداول؟ لا يمكن التراجع عن هذه العملية!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، حذف الكل',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#dc3545',
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-secondary'
                },
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return new Promise((resolve) => {
                        Swal.fire({
                            title: 'تأكيد إضافي',
                            text: 'اكتب "حذف" لتأكيد عملية حذف جميع البيانات',
                            input: 'text',
                            inputAttributes: {
                                autocapitalize: 'off'
                            },
                            showCancelButton: true,
                            confirmButtonText: 'تأكيد الحذف',
                            cancelButtonText: 'إلغاء',
                            showLoaderOnConfirm: true,
                            preConfirm: (text) => {
                                if (text === 'حذف') {
                                    return $.post('api/bulk_operations.php', { action: 'truncate_all_tables' })
                                        .then(response => {
                                            if (!response.success) {
                                                throw new Error(response.message)
                                            }
                                            return response;
                                        })
                                        .catch(error => {
                                            Swal.showValidationMessage(
                                                `فشل في تنفيذ العملية: ${error.message}`
                                            )
                                        });
                                } else {
                                    Swal.showValidationMessage('كلمة التأكيد غير صحيحة');
                                }
                            },
                            allowOutsideClick: () => !Swal.isLoading()
                        }).then((result) => {
                            if (result.isConfirmed) {
                                resolve(result.value);
                            } else {
                                resolve(false);
                            }
                        });
                    });
                }
            }).then((result) => {
                if (result.value && result.value.success) {
                    showSuccess(result.value.message);
                    // تحديث جميع الجداول
                    loadLanguages();
                    loadStatuses();
                    loadSections();
                    refreshCoursesTable();
                }
            });
        }

        /**
         * تبديل ظهور/إخفاء القسم
         * @param {string} sectionId معرف القسم
         */
        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            const button = section.previousElementSibling.querySelector('.btn-link');
            
            if (section.classList.contains('collapsed')) {
                section.classList.remove('collapsed');
                button.classList.remove('collapsed');
                // حفظ الحالة في localStorage
                localStorage.setItem(sectionId + '_state', 'expanded');
            } else {
                section.classList.add('collapsed');
                button.classList.add('collapsed');
                localStorage.setItem(sectionId + '_state', 'collapsed');
            }
        }

        /**
         * تغيير الصفحة الحالية
         * @param {string} section اسم القسم
         * @param {string} direction الاتجاه (prev/next)
         */
        function changePage(section, direction) {
            const state = pageState[section];
            if (direction === 'prev' && state.currentPage > 1) {
                state.currentPage--;
            } else if (direction === 'next' && state.currentPage < state.totalPages) {
                state.currentPage++;
            }
            
            updateTable(section);
            updatePaginationInfo(section);
        }

        /**
         * تحديث معلومات الترقيم
         * @param {string} section اسم القسم
         */
        function updatePaginationInfo(section) {
            const state = pageState[section];
            $(`#${section}CurrentPage`).text(state.currentPage);
            $(`#${section}TotalPages`).text(state.totalPages);
        }

        /**
         * تحديث جدول العرض
         * @param {string} section اسم القسم
         */
        function updateTable(section) {
            const state = pageState[section];
            const start = (state.currentPage - 1) * pageSize;
            const end = start + pageSize;
            const pageData = state.data.slice(start, end);
            
            // تحديث الجدول حسب القسم
            switch(section) {
                case 'languages':
                    updateLanguagesTable(pageData);
                    break;
                case 'sections':
                    updateSectionsTable(pageData);
                    break;
                case 'statuses':
                    updateStatusesTable(pageData);
                    break;
            }
        }

        /**
         * تحديث جدول اللغات
         * @param {Array} languages بيانات اللغات
         */
        function updateLanguagesTable(languages) {
            const tableBody = $('#languagesTable tbody');
            
            if (!languages || languages.length === 0) {
                tableBody.html('<tr><td colspan="4" class="text-center">لا توجد لغات</td></tr>');
                return;
            }

            const rows = languages.map((language, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${language.name}</td>
                    <td>${language.courses_count || 0}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="deleteLanguage(${language.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            
            tableBody.html(rows);
        }

        /**
         * تحديث جدول الحالات
         * @param {Array} statuses بيانات الحالات
         */
        function updateStatusesTable(statuses) {
            const tableBody = $('#statusesTable tbody');
            
            if (!statuses || statuses.length === 0) {
                tableBody.html('<tr><td colspan="5" class="text-center">لا توجد حالات</td></tr>');
                return;
            }

            const rows = statuses.map((status, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${status.language_name}</td>
                    <td>${status.name}</td>
                    <td>${status.courses_count || 0}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="deleteStatus(${status.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            
            tableBody.html(rows);
        }

        /**
         * تحديث جدول الأقسام
         * @param {Array} sections بيانات الأقسام
         */
        function updateSectionsTable(sections) {
            const tableBody = $('#sectionsTable tbody');
            
            if (!sections || sections.length === 0) {
                tableBody.html('<tr><td colspan="5" class="text-center">لا توجد أقسام</td></tr>');
                return;
            }

            const rows = sections.map((section, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${section.name}</td>
                    <td>${section.language_name}</td>
                    <td>${section.courses_count || 0}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="deleteSection(${section.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            
            tableBody.html(rows);
        }
    </script>