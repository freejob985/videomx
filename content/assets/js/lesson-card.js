/**
 * ملف التعامل مع وظائف كروت الدروس
 */

class LessonCard {
    constructor(lessonId) {
        this.lessonId = lessonId;
        this.card = document.querySelector(`[data-lesson-id="${lessonId}"]`).closest('.lesson-card');
        this.initializeEvents();
    }

    /**
     * تهيئة الأحداث للكارد
     */
    initializeEvents() {
        // حدث تغيير الحالة
        this.card.querySelector('.status-select')?.addEventListener('change', e => 
            this.updateStatus(e.target.value));

        // حدث تغيير القسم
        this.card.querySelector('.section-select')?.addEventListener('change', e => 
            this.updateSection(e.target.value));

        // أزرار التبديل
        this.card.querySelector('.importance-btn')?.addEventListener('click', () => 
            this.toggleImportance());
        this.card.querySelector('.theory-btn')?.addEventListener('click', () => 
            this.toggleTheory());

        // زر تشغيل الفيديو
        this.card.querySelector('.play-icon')?.addEventListener('click', () => 
            this.openVideoModal());
    }

    /**
     * تحديث حالة الدرس
     */
    async updateStatus(statusId) {
        try {
            const response = await this.sendRequest('../api/update-lesson-status.php', {
                lesson_id: this.lessonId,
                status_id: statusId
            });

            if (response.success) {
                const select = this.card.querySelector('.status-select');
                select.style.backgroundColor = response.status.color;
                select.style.color = response.status.text_color;
                
                updateStatusStats();
                updateProgressBar();
                toastr.success('تم تحديث حالة الدرس بنجاح');
            }
        } catch (error) {
            toastr.error('حدث خطأ أثناء تحديث الحالة');
        }
    }

    /**
     * تحديث قسم الدرس
     */
    async updateSection(sectionId) {
        try {
            const response = await this.sendRequest('../api/update-lesson-section.php', {
                lesson_id: this.lessonId,
                section_id: sectionId
            });

            if (response.success) {
                toastr.success('تم تحديث قسم الدرس بنجاح');
            }
        } catch (error) {
            toastr.error('حدث خطأ أثناء تحديث القسم');
        }
    }

    /**
     * تبديل حالة الأهمية
     */
    async toggleImportance() {
        try {
            const response = await this.sendRequest('../api/toggle-lesson-importance.php', {
                lesson_id: this.lessonId
            });

            if (response.success) {
                this.card.classList.toggle('important');
                const btn = this.card.querySelector('.importance-btn');
                btn.classList.toggle('btn-warning');
                btn.classList.toggle('btn-outline-warning');
                toastr.success('تم تحديث حالة الدرس المهم');
            }
        } catch (error) {
            toastr.error('حدث خطأ أثناء تحديث حالة الدرس المهم');
        }
    }

    /**
     * تبديل حالة الدرس النظري
     */
    async toggleTheory() {
        try {
            const response = await this.sendRequest('../api/toggle-lesson-theory.php', {
                lesson_id: this.lessonId
            });

            if (response.success) {
                this.card.classList.toggle('theory');
                const btn = this.card.querySelector('.theory-btn');
                btn.classList.toggle('btn-info');
                btn.classList.toggle('btn-outline-info');
                toastr.success('تم تحديث حالة الدرس النظري');
            }
        } catch (error) {
            toastr.error('حدث خطأ أثناء تحديث حالة الدرس النظري');
        }
    }

    /**
     * فتح الفيديو في نافذة منبثقة
     */
    openVideoModal() {
        const videoUrl = this.card.dataset.videoUrl;
        if (!videoUrl) return;

        const videoId = getYoutubeId(videoUrl);
        if (!videoId) return;

        const modal = new bootstrap.Modal(document.getElementById('videoModal'));
        const videoPlayer = document.getElementById('videoPlayer');
        // استخدام رابط التضمين المباشر
        videoPlayer.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
        modal.show();
    }

    /**
     * إرسال طلب للخادم
     */
    async sendRequest(url, data) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }
}

/**
 * تحميل ألوان الحالات
 */
function loadStatusColors() {
    document.querySelectorAll('.status-select').forEach(select => {
        const selectedOption = select.options[select.selectedIndex];
        if (selectedOption && selectedOption.value) {
            const status = statuses.find(s => s.id === parseInt(selectedOption.value));
            if (status) {
                select.style.backgroundColor = status.color;
                select.style.color = status.text_color;
            }
        }
    });
}

/**
 * تحديث شريط التقدم
 */
function updateProgressBar() {
    const totalLessons = document.querySelectorAll('.status-select').length;
    const completedLessons = document.querySelectorAll('.status-select option:checked:not([value=""])').length;
    const percentage = (completedLessons / totalLessons) * 100;
    
    const progressBar = document.querySelector('.course-progress .progress-bar');
    if (progressBar) {
        progressBar.style.width = `${percentage}%`;
        progressBar.textContent = `${Math.round(percentage)}%`;
        progressBar.setAttribute('aria-valuenow', percentage);
    }
}

/**
 * استخراج معرف فيديو يوتيوب من الرابط
 */
function getYoutubeId(url) {
    const pattern = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i;
    const matches = url.match(pattern);
    return matches ? matches[1] : null;
}

// تهيئة الأحداث عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة كروت الدروس
    document.querySelectorAll('.lesson-card').forEach(card => {
        const lessonId = card.querySelector('[data-lesson-id]').dataset.lessonId;
        const lessonCard = new LessonCard(lessonId);
    });

    // تهيئة مكتبة toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "3000"
    };

    // تحديث حالة الدرس
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', async function() {
            const lessonId = this.dataset.lessonId;
            const statusId = this.value;

            try {
                const response = await fetch('../api/update-lesson-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        lesson_id: lessonId,
                        status_id: statusId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    toastr.success(data.message);
                    // تحديث لون الـ select
                    if (data.status) {
                        this.style.backgroundColor = data.status.color;
                        this.style.color = data.status.text_color;
                    }
                    // تحديث الإحصائيات
                    if (typeof updateCourseStats === 'function') {
                        updateCourseStats();
                    }
                } else {
                    toastr.error(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                toastr.error('حدث خطأ أثناء تحديث حالة الدرس');
            }
        });
    });

    // تحديث حالة اكتمال الدرس
    document.querySelectorAll('.completion-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const lessonId = this.dataset.lessonId;
            const currentCompleted = this.dataset.completed === '1';
            const newCompleted = !currentCompleted;

            try {
                const response = await fetch('../api/update-lesson-completion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        lesson_id: lessonId,
                        completed: newCompleted
                    })
                });

                const data = await response.json();

                if (data.success) {
                    toastr.success(data.message);
                    // تحديث حالة الزر
                    this.dataset.completed = data.completed ? '1' : '0';
                    this.classList.toggle('btn-success', data.completed);
                    this.classList.toggle('btn-outline-success', !data.completed);
                    // تحديث الإحصائيات
                    if (typeof updateCourseStats === 'function') {
                        updateCourseStats();
                    }
                } else {
                    toastr.error(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                toastr.error('حدث خطأ أثناء تحديث حالة اكتمال الدرس');
            }
        });
    });

    // تفعيل الصفحات
    const itemsPerPage = 9;
    const lessonsContainer = document.getElementById('lessons-container');
    const lessonCards = lessonsContainer.querySelectorAll('.col-md-6');
    
    function showPage(pageNumber) {
        const start = (pageNumber - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        
        lessonCards.forEach((card, index) => {
            card.style.display = (index >= start && index < end) ? '' : 'none';
        });
    }
    
    // إنشاء أزرار التنقل بين الصفحات
    if (lessonCards.length > itemsPerPage) {
        const totalPages = Math.ceil(lessonCards.length / itemsPerPage);
        const paginationContainer = document.createElement('div');
        paginationContainer.className = 'pagination-container mt-4 d-flex justify-content-center';
        
        const pagination = document.createElement('ul');
        pagination.className = 'pagination';
        
        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement('li');
            li.className = 'page-item';
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = i;
            a.onclick = (e) => {
                e.preventDefault();
                showPage(i);
                // إزالة الكلاس active من جميع الأزرار
                pagination.querySelectorAll('.page-item').forEach(item => item.classList.remove('active'));
                // إضافة الكلاس active للزر الحالي
                li.classList.add('active');
            };
            li.appendChild(a);
            pagination.appendChild(li);
        }
        
        paginationContainer.appendChild(pagination);
        lessonsContainer.parentNode.insertBefore(paginationContainer, lessonsContainer.nextSibling);
        
        // عرض الصفحة الأولى وتنشيط زرها
        showPage(1);
        pagination.querySelector('.page-item').classList.add('active');
    }
});

// إضافة في نهاية الملف
document.getElementById('videoModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('videoPlayer').src = '';
}); 