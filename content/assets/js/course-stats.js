/**
 * Course Stats JavaScript
 * =====================
 * التعامل مع إحصائيات الكورس في واجهة المستخدم
 */

class CourseStats {
    constructor(courseId) {
        this.courseId = courseId;
        this.showCompleted = true;
        this.initializeElements();
        this.initializeEvents();
        this.loadInitialData();
    }

    initializeElements() {
        // عناصر الإحصائيات
        this.statsContainer = document.getElementById('courseStats');
        this.progressBar = document.getElementById('courseProgress');
        this.lessonCount = document.getElementById('lessonCount');
        this.completedCount = document.getElementById('completedCount');
        this.remainingCount = document.getElementById('remainingCount');
        
        // القائمة المنسدلة
        this.lessonsDropdown = document.getElementById('lessonsDropdown');
        
        // زر إخفاء/إظهار الدروس المكتملة
        this.toggleCompletedBtn = document.getElementById('toggleCompleted');
    }

    initializeEvents() {
        // تحديث الإحصائيات عند تغيير حالة الدرس
        document.addEventListener('lessonStatusChanged', () => this.loadStats());
        
        // التنقل بين الدروس
        if (this.lessonsDropdown) {
            this.lessonsDropdown.addEventListener('change', (e) => {
                const lessonId = e.target.value;
                if (lessonId) {
                    window.location.href = `http://videomx.com/content/views/lesson-details.php?id=${lessonId}`;
                }
            });
        }
        
        // تبديل عرض القائمة الجانبية
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                document.body.classList.toggle('sidebar-collapsed');
            });
        }
        
        // تبديل عرض الدروس المكتملة
        if (this.toggleCompletedBtn) {
            this.toggleCompletedBtn.addEventListener('click', async () => {
                this.showCompleted = !this.showCompleted;
                await this.loadLessons();
                
                // تحديث نص الزر
                this.toggleCompletedBtn.innerHTML = `
                    <i class="fas fa-${this.showCompleted ? 'eye-slash' : 'eye'}"></i>
                    <span class="ms-1">${this.showCompleted ? 'إخفاء المكتمل' : 'إظهار الكل'}</span>
                `;
                
                // إظهار رسالة للمستخدم
                toastr.success(this.showCompleted ? 'تم إظهار جميع الدروس' : 'تم إخفاء الدروس المكتملة');
            });
        }
    }

    async loadInitialData() {
        await this.loadStats();
        await this.loadLessons();
    }

    async loadStats() {
        try {
            const response = await fetch('/content/api/course-stats.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_stats&course_id=${this.courseId}`
            });
            
            const stats = await response.json();
            this.updateStatsDisplay(stats);
            
        } catch (error) {
            console.error('Error loading stats:', error);
            toastr.error('حدث خطأ أثناء تحميل الإحصائيات');
        }
    }

    updateStatsDisplay(stats) {
        // تحديث شريط التقدم
        if (this.progressBar) {
            this.progressBar.style.width = `${stats.completion_percentage}%`;
            this.progressBar.setAttribute('aria-valuenow', stats.completion_percentage);
            this.progressBar.textContent = `${stats.completion_percentage}%`;
        }
        
        // تحديث العدادات
        if (this.lessonCount) {
            this.lessonCount.textContent = stats.total_lessons;
        }
        if (this.completedCount) {
            this.completedCount.textContent = stats.completed_lessons;
        }
        if (this.remainingCount) {
            this.remainingCount.textContent = stats.total_lessons - stats.completed_lessons;
        }
        
        // تحديث إحصائيات الوقت
        const totalDuration = document.getElementById('totalDuration');
        const remainingDuration = document.getElementById('remainingDuration');
        
        if (totalDuration) {
            totalDuration.textContent = this.formatDuration(stats.total_duration);
        }
        if (remainingDuration) {
            const remainingTime = Math.max(0, stats.total_duration - stats.completed_duration);
            remainingDuration.textContent = this.formatDuration(remainingTime);
        }
    }

    formatDuration(minutes) {
        if (!minutes) return '0:00:00';
        
        const hours = Math.floor(minutes / 60);
        const mins = Math.floor(minutes % 60);
        const secs = Math.round((minutes % 1) * 60);
        
        return `${hours}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    async loadLessons() {
        try {
            const response = await fetch('/content/api/course-stats.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_lessons&course_id=${this.courseId}&show_completed=${this.showCompleted}`
            });
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            const lessons = await response.json();
            this.updateDropdown(lessons);
            
        } catch (error) {
            console.error('Error loading lessons:', error);
            toastr.error('حدث خطأ أثناء تحميل قائمة الدروس');
        }
    }

    updateDropdown(lessons) {
        const lessonsList = document.getElementById('lessonsList');
        if (!lessonsList) return;
        
        // تصفية الدروس حسب حالة العرض
        const filteredLessons = this.showCompleted ? 
            lessons : 
            lessons.filter(lesson => !lesson.completed);
        
        lessonsList.innerHTML = filteredLessons.map(lesson => `
            <div class="lesson-item ${lesson.completed ? 'completed' : ''} 
                                   ${lesson.is_reviewed ? 'reviewed' : ''} 
                                   ${lesson.id === window.LESSON_ID ? 'active' : ''}"
                 onclick="window.location.href='http://videomx.com/content/views/lesson-details.php?id=${lesson.id}'">
                <div class="lesson-status-icon">
                    ${lesson.completed ? '<i class="fas fa-check-circle"></i>' : 
                      lesson.is_reviewed ? '<i class="fas fa-bookmark"></i>' : 
                      '<i class="far fa-circle"></i>'}
                </div>
                <div class="lesson-title">${lesson.title}</div>
                <div class="lesson-duration">
                    <i class="far fa-clock"></i> ${this.formatDuration(lesson.duration)}
                </div>
            </div>
        `).join('');
    }
}

// تهيئة الإحصائيات عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    const courseId = window.COURSE_ID;
    if (courseId) {
        window.courseStats = new CourseStats(courseId);
    } else {
        console.error('Course ID not found');
    }
}); 