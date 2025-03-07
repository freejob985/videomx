// Disable Dropzone auto discover
Dropzone.autoDiscover = false;

document.addEventListener('DOMContentLoaded', function() {
    // إدارة حالة الأقسام
    initializeSectionState('images');
    
    // Initialize Dropzone
    const myDropzone = new Dropzone("#imageDropzone", {
        url: "/content/api/image-notes.php",
        paramName: "file",
        maxFilesize: 5, // MB
        acceptedFiles: "image/*",
        addRemoveLinks: true,
        uploadMultiple: false,
        createImageThumbnails: true,
        thumbnailWidth: 120,
        thumbnailHeight: 120,
        
        // تخصيص الرسائل بالعربية
        dictDefaultMessage: `
            <i class="fas fa-cloud-upload-alt fa-3x"></i>
            <h4>اسحب وأفلت الصور هنا</h4>
            <p>أو انقر للاختيار من جهازك</p>
            <p class="small text-muted">يمكنك أيضاً لصق الصور مباشرة (Ctrl+V)</p>
        `,
        dictFallbackMessage: "متصفحك لا يدعم السحب والإفلات للملفات.",
        dictFileTooBig: "حجم الملف كبير جداً ({{filesize}}MB). الحد الأقصى هو {{maxFilesize}}MB.",
        dictInvalidFileType: "لا يمكنك رفع هذا النوع من الملفات.",
        dictResponseError: "حدث خطأ في الخادم برمز {{statusCode}}.",
        dictCancelUpload: "إلغاء الرفع",
        dictUploadCanceled: "تم إلغاء الرفع.",
        dictCancelUploadConfirmation: "هل أنت متأكد من إلغاء الرفع؟",
        dictRemoveFile: "حذف الملف",
        dictMaxFilesExceeded: "لا يمكنك رفع المزيد من الملفات.",

        headers: {
            'X-Lesson-ID': lessonId
        },
        
        init: function() {
            this.on("sending", function(file, xhr, formData) {
                formData.append("action", "upload");
                formData.append("lesson_id", lessonId);
            });

            this.on("success", function(file, response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success) {
                        showToast('تم رفع الصورة بنجاح');
                        refreshImagesList();
                    } else {
                        showToast(data.error || 'حدث خطأ أثناء رفع الصورة', 'error');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    showToast('حدث خطأ غير متوقع', 'error');
                }
                this.removeFile(file);
            });

            this.on("error", function(file, errorMessage) {
                showToast(typeof errorMessage === 'string' ? errorMessage : 'حدث خطأ أثناء الرفع', 'error');
                this.removeFile(file);
            });
        }
    });

    // تحسين معالجة لصق الصور
    let isProcessingPaste = false;
    let lastPasteTime = 0;
    const PASTE_COOLDOWN = 1000;

    document.addEventListener('paste', async function(event) {
        try {
            const items = (event.clipboardData || event.originalEvent.clipboardData).items;
            for (let item of items) {
                if (item.type.indexOf('image') === 0) {
                    event.preventDefault();
                    const file = item.getAsFile();
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('action', 'upload');
                    formData.append('lesson_id', lessonId);

                    const response = await fetch('/content/api/image-notes.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        showToast('تم رفع الصورة بنجاح');
                        refreshImagesList();
                    } else {
                        showToast(data.error || 'حدث خطأ أثناء رفع الصورة', 'error');
                    }
                    break;
                }
            }
        } catch (error) {
            console.error('Error handling paste:', error);
            showToast('حدث خطأ أثناء معالجة الصورة الملصقة', 'error');
        }
    });

    // تحديث قائمة الصور
    function refreshImagesList() {
        fetch(`/content/api/image-notes.php?lesson_id=${lessonId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && Array.isArray(data.images)) {
                    const imagesContainer = document.getElementById('lessonImages');
                    if (imagesContainer) {
                        imagesContainer.innerHTML = data.images.map(image => `
                            <div class="col-md-6 col-lg-4" data-image-id="${image.id}">
                                <div class="card h-100">
                                    <div class="position-relative">
                                        <img src="${image.image_url}" 
                                             class="card-img-top lesson-image" 
                                             alt="${image.title}">
                                        <div class="image-actions position-absolute top-0 end-0 p-2">
                                            <button class="btn btn-light btn-sm me-1 copy-image-url" 
                                                    data-url="${image.image_url}"
                                                    title="نسخ رابط الصورة">
                                                <i class="fas fa-link"></i>
                                            </button>
                                            <button class="btn btn-light btn-sm me-1 edit-image" 
                                                    data-id="${image.id}"
                                                    data-title="${image.title}"
                                                    data-description="${image.description || ''}"
                                                    title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-light btn-sm delete-image" 
                                                    data-id="${image.id}"
                                                    title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title">${image.title}</h5>
                                        ${image.description ? `<p class="card-text">${image.description}</p>` : ''}
                                    </div>
                                    <div class="card-footer text-muted">
                                        <small>
                                            <i class="fas fa-clock me-1"></i>
                                            ${formatDate(image.created_at)}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                        
                        initializeImageActions();
                    }
                } else {
                    console.error('Invalid response format:', data);
                    showToast('حدث خطأ أثناء تحديث الصور', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('حدث خطأ غير متوقع', 'error');
            });
    }

    // إنشاء بطاقة الصورة
    function createImageCard(image) {
        return `
            <div class="col-md-6 col-lg-4" data-image-id="${image.id}">
                <div class="card h-100">
                    <div class="position-relative">
                        <img src="${escapeHtml(image.image_url)}" 
                             class="card-img-top lesson-image" 
                             alt="${escapeHtml(image.title)}">
                        <div class="image-actions position-absolute top-0 end-0 p-2">
                            <button class="btn btn-light btn-sm me-1 copy-image-url" 
                                    data-url="${escapeHtml(image.image_url)}"
                                    title="نسخ رابط الصورة">
                                <i class="fas fa-link"></i>
                            </button>
                            <button class="btn btn-light btn-sm me-1 edit-image" 
                                    title="تعديل">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-light btn-sm delete-image" 
                                    title="حذف">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">${escapeHtml(image.title)}</h5>
                        ${image.description ? `<p class="card-text">${escapeHtml(image.description)}</p>` : ''}
                    </div>
                    <div class="card-footer text-muted">
                        <small>
                            <i class="fas fa-clock me-1"></i>
                            ${formatDate(image.created_at)}
                        </small>
                    </div>
                </div>
            </div>
        `;
    }

    // إضافة دالة لتأمين النصوص
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // تهيئة أحداث الصور
    function initializeImageActions() {
        // نسخ رابط الصورة
        document.querySelectorAll('.copy-image-url').forEach(button => {
            button.addEventListener('click', function() {
                const url = this.dataset.url;
                navigator.clipboard.writeText(url).then(() => {
                    showToast('تم نسخ رابط الصورة بنجاح');
                });
            });
        });

        // تعديل الصورة
        document.querySelectorAll('.edit-image').forEach(button => {
            button.addEventListener('click', function() {
                const imageCard = this.closest('[data-image-id]');
                const imageId = imageCard.dataset.imageId;
                showEditImageModal(imageId);
            });
        });

        // حذف الصورة
        document.querySelectorAll('.delete-image').forEach(button => {
            button.addEventListener('click', function() {
                const imageCard = this.closest('[data-image-id]');
                const imageId = imageCard.dataset.imageId;
                confirmDeleteImage(imageId);
            });
        });
    }

    // عرض نافذة تعديل الصورة
    function showEditImageModal(imageId) {
        Swal.fire({
            title: 'تعديل معلومات الصورة',
            html: `
                <input type="text" id="imageTitle" class="swal2-input" placeholder="عنوان الصورة">
                <textarea id="imageDescription" class="swal2-textarea" placeholder="وصف الصورة"></textarea>
            `,
            showCancelButton: true,
            confirmButtonText: 'حفظ',
            cancelButtonText: 'إلغاء',
            preConfirm: () => {
                const title = document.getElementById('imageTitle').value;
                const description = document.getElementById('imageDescription').value;
                return { title, description };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                updateImage(imageId, result.value.title, result.value.description);
            }
        });
    }

    // تأكيد حذف الصورة
    function confirmDeleteImage(imageId) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: 'سيتم حذف الصورة نهائياً',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteImage(imageId);
            }
        });
    }

    // تحديث الصورة
    function updateImage(imageId, title, description) {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('image_id', imageId);
        formData.append('title', title);
        formData.append('description', description);

        fetch('/content/api/image-notes.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('تم تحديث الصورة بنجاح');
                refreshImagesList();
            } else {
                showToast('حدث خطأ أثناء تحديث الصورة', 'error');
            }
        });
    }

    // حذف الصورة
    function deleteImage(imageId) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('image_id', imageId);

        fetch('/content/api/image-notes.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('تم حذف الصورة بنجاح');
                refreshImagesList();
            } else {
                showToast('حدث خطأ أثناء حذف الصورة', 'error');
            }
        });
    }

    // عرض رسالة
    function showToast(message, icon = 'success') {
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            icon: icon,
            title: message
        });
    }

    // تنسيق التاريخ
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('ar-SA', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    // إضافة دالة لعرض معرض الصور
    function initializeGallery() {
        document.querySelectorAll('.lesson-image').forEach(img => {
            img.addEventListener('click', function() {
                const images = Array.from(document.querySelectorAll('.lesson-image'));
                const currentIndex = images.indexOf(this);
                
                Swal.fire({
                    html: `
                        <div class="gallery-container">
                            <img src="${this.src}" class="gallery-image" style="max-height: 80vh; max-width: 100%;">
                            <div class="image-counter mt-2">
                                صورة ${currentIndex + 1} من ${images.length}
                            </div>
                        </div>
                    `,
                    showCloseButton: true,
                    showConfirmButton: false,
                    width: '80%',
                    customClass: {
                        container: 'gallery-modal'
                    },
                    showClass: {
                        popup: 'animate__animated animate__fadeIn'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__fadeOut'
                    },
                    didOpen: (modal) => {
                        // إضافة أزرار التنقل
                        const container = modal.querySelector('.gallery-container');
                        if (images.length > 1) {
                            container.innerHTML += `
                                <button class="gallery-nav prev">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                                <button class="gallery-nav next">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                            `;

                            let currentImageIndex = currentIndex;
                            const galleryImage = modal.querySelector('.gallery-image');
                            const counter = modal.querySelector('.image-counter');

                            modal.querySelector('.prev').addEventListener('click', () => {
                                currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
                                galleryImage.src = images[currentImageIndex].src;
                                counter.textContent = `صورة ${currentImageIndex + 1} من ${images.length}`;
                            });

                            modal.querySelector('.next').addEventListener('click', () => {
                                currentImageIndex = (currentImageIndex + 1) % images.length;
                                galleryImage.src = images[currentImageIndex].src;
                                counter.textContent = `صورة ${currentImageIndex + 1} من ${images.length}`;
                            });

                            // إضافة دعم لوحة المفاتيح
                            document.addEventListener('keydown', function(e) {
                                if (e.key === 'ArrowLeft') {
                                    modal.querySelector('.next').click();
                                } else if (e.key === 'ArrowRight') {
                                    modal.querySelector('.prev').click();
                                }
                            });
                        }
                    }
                });
            });
        });
    }

    // إضافة صورة خارجية
    document.getElementById('addExternalImageBtn')?.addEventListener('click', function() {
        Swal.fire({
            title: 'إضافة صورة خارجية',
            html: `
                <input type="url" id="external-image-url" class="swal2-input" placeholder="رابط الصورة">
                <input type="text" id="external-image-title" class="swal2-input" placeholder="عنوان الصورة">
                <textarea id="external-image-description" class="swal2-textarea" placeholder="وصف الصورة (اختياري)"></textarea>
            `,
            showCancelButton: true,
            confirmButtonText: 'إضافة',
            cancelButtonText: 'إلغاء',
            preConfirm: () => {
                const url = document.getElementById('external-image-url').value;
                const title = document.getElementById('external-image-title').value;
                const description = document.getElementById('external-image-description').value;

                if (!url) {
                    Swal.showValidationMessage('الرجاء إدخال رابط الصورة');
                    return false;
                }
                if (!title) {
                    Swal.showValidationMessage('الرجاء إدخال عنوان الصورة');
                    return false;
                }

                return { url, title, description };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                addExternalImage(result.value);
            }
        });
    });

    // دالة إضافة صورة خارجية
    async function addExternalImage(data) {
        try {
            const formData = new FormData();
            formData.append('action', 'add_external');
            formData.append('lesson_id', lessonId);
            formData.append('url', data.url);
            formData.append('title', data.title);
            formData.append('description', data.description);

            const response = await fetch('/content/api/image-notes.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                showToast('تم إضافة الصورة بنجاح');
                refreshImagesList();
            } else {
                showToast(result.error || 'حدث خطأ أثناء إضافة الصورة', 'error');
            }
        } catch (error) {
            console.error('Error adding external image:', error);
            showToast('حدث خطأ غير متوقع', 'error');
        }
    }

    // تهيئة الأحداث عند تحميل الصفحة
    initializeImageActions();
    initializeGallery();
});

// دالة تهيئة حالة القسم
function initializeSectionState(sectionName) {
    const toggleBtn = document.querySelector(`[data-section="${sectionName}"]`);
    const content = document.querySelector(`[data-section-content="${sectionName}"]`);
    
    if (toggleBtn && content) {
        // استرجاع الحالة من localStorage
        const isCollapsed = localStorage.getItem(`section_${sectionName}_collapsed`) === 'true';
        
        // تطبيق الحالة المحفوظة
        if (isCollapsed) {
            content.classList.add('collapsed');
            toggleBtn.querySelector('i').classList.replace('fa-chevron-up', 'fa-chevron-down');
            toggleBtn.setAttribute('title', 'إظهار الصور');
        }

        // إضافة معالج النقر
        toggleBtn.addEventListener('click', function() {
            const icon = this.querySelector('i');
            content.classList.toggle('collapsed');
            
            // تحديث الأيقونة
            if (content.classList.contains('collapsed')) {
                icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                this.setAttribute('title', 'إظهار الصور');
            } else {
                icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
                this.setAttribute('title', 'إخفاء الصور');
            }
            
            // حفظ الحالة في localStorage
            localStorage.setItem(
                `section_${sectionName}_collapsed`, 
                content.classList.contains('collapsed')
            );
        });
    }
} 