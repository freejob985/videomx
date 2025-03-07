document.addEventListener('DOMContentLoaded', function() {
    // منع الاكتشاف التلقائي لـ Dropzone
    if (typeof Dropzone !== 'undefined') {
        Dropzone.autoDiscover = false;
        
        const dropzoneElement = document.getElementById('imageDropzone');
        if (dropzoneElement && !dropzoneElement.dropzone) {
            const dropzoneOptions = {
                url: "/content/api/image-notes.php",
                paramName: "image",
                maxFilesize: 5,
                acceptedFiles: "image/*",
                addRemoveLinks: true,
                dictDefaultMessage: "اسحب وأفلت الصور هنا أو انقر للاختيار",
                dictRemoveFile: "حذف",
                dictCancelUpload: "إلغاء",
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                init: function() {
                    this.on("sending", function(file, xhr, formData) {
                        formData.append("action", "upload");
                        formData.append("lesson_id", lessonId);
                        formData.append("title", file.name);
                    });

                    this.on("success", function(file, response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                refreshImagesList();
                                this.removeFile(file);
                                showToast('تم رفع الصورة بنجاح');
                            } else {
                                showToast(data.error || 'حدث خطأ أثناء رفع الصورة', 'error');
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            showToast('حدث خطأ غير متوقع', 'error');
                        }
                    });

                    this.on("error", function(file, message) {
                        showToast(message, 'error');
                        this.removeFile(file);
                    });
                }
            };

            try {
                new Dropzone(dropzoneElement, dropzoneOptions);
            } catch (e) {
                console.error('Error initializing Dropzone:', e);
            }
        }
    }

    // تحسين معالجة لصق الصور
    let isProcessingPaste = false;
    let lastPasteTime = 0;
    const PASTE_COOLDOWN = 1000;

    document.addEventListener('paste', function(event) {
        const currentTime = Date.now();
        
        if (isProcessingPaste || (currentTime - lastPasteTime) < PASTE_COOLDOWN) {
            event.preventDefault();
            return;
        }

        const items = (event.clipboardData || event.originalEvent.clipboardData).items;
        let imageFile = null;
        
        for (let item of items) {
            if (item.type.indexOf('image') === 0) {
                imageFile = item.getAsFile();
                break;
            }
        }

        if (imageFile) {
            // عرض معاينة الصورة قبل الرفع
            const reader = new FileReader();
            reader.onload = function(e) {
                Swal.fire({
                    title: 'معاينة الصورة',
                    html: `
                        <div class="preview-container">
                            <img src="${e.target.result}" style="max-height: 300px; max-width: 100%;">
                            <input type="text" id="imageTitle" class="swal2-input" placeholder="عنوان الصورة" value="صورة ملصقة">
                            <textarea id="imageDescription" class="swal2-textarea" placeholder="وصف الصورة (اختياري)"></textarea>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'رفع',
                    cancelButtonText: 'إلغاء',
                    preConfirm: () => {
                        return {
                            title: document.getElementById('imageTitle').value || 'صورة ملصقة',
                            description: document.getElementById('imageDescription').value
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        isProcessingPaste = true;
                        lastPasteTime = currentTime;

                        const formData = new FormData();
                        formData.append('action', 'upload');
                        formData.append('lesson_id', lessonId);
                        formData.append('image', imageFile);
                        formData.append('title', result.value.title);
                        formData.append('description', result.value.description);

                        fetch('/content/api/image-notes.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                refreshImagesList();
                                showToast('تم رفع الصورة بنجاح');
                            } else {
                                showToast(data.error || 'حدث خطأ أثناء رفع الصورة', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('حدث خطأ غير متوقع', 'error');
                        })
                        .finally(() => {
                            isProcessingPaste = false;
                        });
                    }
                });
            };
            reader.readAsDataURL(imageFile);
        }
    });

    // زر إخفاء/إظهار الصور
    const toggleImagesBtn = document.querySelector('.toggle-images');
    const imagesContent = document.querySelector('.images-content');
    
    if (toggleImagesBtn && imagesContent) {
        // استخدام مفتاح عام لحالة قسم الصور لجميع الدروس
        const storageKey = 'images_section_collapsed';
        const isCollapsed = localStorage.getItem(storageKey) === 'true';
        
        // تطبيق الحالة المحفوظة
        if (isCollapsed) {
            imagesContent.classList.add('collapsed');
            toggleImagesBtn.querySelector('i').classList.replace('fa-chevron-up', 'fa-chevron-down');
        }

        toggleImagesBtn.addEventListener('click', function() {
            imagesContent.classList.toggle('collapsed');
            const icon = this.querySelector('i');
            const isNowCollapsed = imagesContent.classList.contains('collapsed');
            
            // تحديث الأيقونة
            icon.classList.toggle('fa-chevron-up', !isNowCollapsed);
            icon.classList.toggle('fa-chevron-down', isNowCollapsed);

            // حفظ الحالة في localStorage لجميع الدروس
            localStorage.setItem(storageKey, isNowCollapsed);
        });
    }

    // تحديث قائمة الصور
    function refreshImagesList() {
        fetch(`/content/api/image-notes.php?action=list&lesson_id=${lessonId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && Array.isArray(data.images)) {
                    const imagesContainer = document.getElementById('lessonImages');
                    if (imagesContainer) {
                        imagesContainer.innerHTML = data.images.map(image => createImageCard(image)).join('');
                        initializeImageActions();
                    }
                } else {
                    console.error('Invalid response format:', data);
                    showToast(data.error || 'حدث خطأ أثناء تحديث الصور', 'error');
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
        return new Date(dateString).toLocaleDateString('ar-SA', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
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

    // إضافة دالة لإضافة صورة خارجية
    function showExternalImageModal() {
        Swal.fire({
            title: 'إضافة صورة خارجية',
            html: `
                <input type="url" id="externalUrl" class="swal2-input" placeholder="رابط الصورة">
                <input type="text" id="imageTitle" class="swal2-input" placeholder="عنوان الصورة">
                <textarea id="imageDescription" class="swal2-textarea" placeholder="وصف الصورة"></textarea>
            `,
            showCancelButton: true,
            confirmButtonText: 'إضافة',
            cancelButtonText: 'إلغاء',
            preConfirm: () => {
                const url = document.getElementById('externalUrl').value;
                const title = document.getElementById('imageTitle').value;
                const description = document.getElementById('imageDescription').value;
                
                if (!url) {
                    Swal.showValidationMessage('الرجاء إدخال رابط الصورة');
                    return false;
                }
                
                return { url, title, description };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                addExternalImage(result.value);
            }
        });
    }

    // دالة إضافة الصورة الخارجية
    function addExternalImage(data) {
        const formData = new FormData();
        formData.append('action', 'upload');
        formData.append('lesson_id', lessonId);
        formData.append('external_url', data.url);
        formData.append('title', data.title);
        formData.append('description', data.description);

        fetch('/content/api/image-notes.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('تم إضافة الصورة بنجاح');
                refreshImagesList();
            } else {
                showToast(data.error || 'حدث خطأ أثناء إضافة الصورة', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('حدث خطأ غير متوقع', 'error');
        });
    }

    // إضافة معالج النقر على زر إضافة صورة خارجية
    document.getElementById('addExternalImageBtn')?.addEventListener('click', showExternalImageModal);

    // تهيئة الأحداث عند تحميل الصفحة
    initializeImageActions();
    initializeGallery();
}); 