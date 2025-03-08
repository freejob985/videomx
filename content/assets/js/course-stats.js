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
        this.initializeSidebarState();
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

    initializeSidebarState() {
        // قراءة الحالة المخزنة أو استخدام الحالة الافتراضية (مخفية)
        const isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') !== 'false';
        if (isSidebarCollapsed) {
            document.body.classList.add('sidebar-collapsed');
        } else {
            document.body.classList.remove('sidebar-collapsed');
        }
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
                // تخزين الحالة الجديدة
                localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed'));
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
            const total = Math.floor(parseFloat(stats.total_duration) || 0);
            totalDuration.textContent = this.formatDuration(total);
        }
        if (remainingDuration) {
            const total = Math.floor(parseFloat(stats.total_duration) || 0);
            const completed = Math.floor(parseFloat(stats.completed_duration) || 0);
            const remaining = Math.max(0, total - completed);
            remainingDuration.textContent = this.formatDuration(remaining);
        }
    }

    formatDuration(minutes) {
        if (!minutes || isNaN(minutes)) return '00:00:00';
        
        // تحويل الدقائق إلى ساعات ودقائق
        const totalMinutes = Math.floor(minutes);
        const hours = Math.floor(totalMinutes / 60);
        const mins = totalMinutes % 60;
        
        // تنسيق الوقت بشكل أفضل
        const formattedHours = String(hours).padStart(2, '0');
        const formattedMins = String(mins).padStart(2, '0');
        
        return `${formattedHours}:${formattedMins}:00`;
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