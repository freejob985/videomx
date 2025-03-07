<!-- إضافة JavaScript للتصفية والرسوم البيانية -->
<script>
// تهيئة المتغيرات العامة
let lessons = <?php echo json_encode($lessons); ?>;
let sections = <?php echo json_encode($sections); ?>;
let charts = {};

// دالة تحديث الإحصائيات حسب القسم
function updateStats(sectionId) {
    let filteredLessons = sectionId ? 
        lessons.filter(lesson => lesson.section_id == sectionId) : 
        lessons;

    // تحديث الإحصائيات
    updateStatCards(filteredLessons);
    updateCharts(filteredLessons);
}

// دالة تحديث بطاقات الإحصائيات
function updateStatCards(filteredLessons) {
    const stats = calculateStats(filteredLessons);
    
    // تحديث القيم في البطاقات
    document.querySelector('.stat-card .stat-value').textContent = filteredLessons.length;
    // ... تحديث باقي البطاقات
}

// دالة حساب الإحصائيات
function calculateStats(lessons) {
    return {
        total: lessons.length,
        completed: lessons.filter(l => l.completed).length,
        important: lessons.filter(l => l.is_important).length,
        theory: lessons.filter(l => l.is_theory).length,
        noStatus: lessons.filter(l => !l.status_id).length,
        completedDuration: lessons.reduce((sum, l) => sum + (l.completed ? (l.duration || 0) : 0), 0),
        remainingDuration: lessons.reduce((sum, l) => sum + (!l.completed ? (l.duration || 0) : 0), 0)
    };
}

// دالة تهيئة الرسوم البيانية
function initCharts() {
    // مخطط نسبة الإكمال
    charts.completion = new Chart(document.getElementById('completionChart'), {
        type: 'doughnut',
        data: {
            labels: ['مكتمل', 'متبقي'],
            datasets: [{
                data: [0, 0],
                backgroundColor: ['#4CAF50', '#FFC107']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'نسبة إكمال الدروس'
                }
            }
        }
    });

    // مخطط توزيع الوقت
    charts.duration = new Chart(document.getElementById('durationChart'), {
        type: 'bar',
        data: {
            labels: ['الوقت المكتمل', 'الوقت المتبقي'],
            datasets: [{
                data: [0, 0],
                backgroundColor: ['#2196F3', '#FF5722']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'توزيع وقت الدروس (بالدقائق)'
                }
            }
        }
    });
}

// دالة تحديث الرسوم البيانية
function updateCharts(filteredLessons) {
    const stats = calculateStats(filteredLessons);

    // تحديث مخطط نسبة الإكمال
    charts.completion.data.datasets[0].data = [
        stats.completed,
        filteredLessons.length - stats.completed
    ];
    charts.completion.update();

    // تحديث مخطط توزيع الوقت
    charts.duration.data.datasets[0].data = [
        Math.round(stats.completedDuration / 60),
        Math.round(stats.remainingDuration / 60)
    ];
    charts.duration.update();
}

// دالة تهيئة حالة إظهار/إخفاء الرسوم البيانية
function initChartsVisibility() {
    const chartsSection = document.getElementById('chartsSection');
    const toggleBtn = document.getElementById('toggleChartsBtn');
    const btnIcon = toggleBtn.querySelector('i');
    const btnText = toggleBtn.querySelector('span');
    
    // استرجاع الحالة المحفوظة
    const isHidden = localStorage.getItem('chartsHidden') === 'true';
    
    // تطبيق الحالة المحفوظة
    if (isHidden) {
        chartsSection.style.display = 'none';
        btnIcon.className = 'fas fa-eye';
        btnText.textContent = 'إظهار الرسوم البيانية';
    }
    
    // إضافة مستمع الحدث للزر
    toggleBtn.addEventListener('click', function() {
        const isCurrentlyHidden = chartsSection.style.display === 'none';
        
        // تبديل حالة العرض
        chartsSection.style.display = isCurrentlyHidden ? 'block' : 'none';
        
        // تحديث أيقونة ونص الزر
        btnIcon.className = isCurrentlyHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
        btnText.textContent = isCurrentlyHidden ? 'إخفاء الرسوم البيانية' : 'إظهار الرسوم البيانية';
        
        // حفظ الحالة في localStorage
        localStorage.setItem('chartsHidden', !isCurrentlyHidden);
    });
}

// دالة تهيئة حالة إظهار/إخفاء معلومات الكورس
function initCourseInfoVisibility() {
    const courseInfoContent = document.getElementById('courseInfoContent');
    const courseSectionsContent = document.getElementById('courseSectionsContent');
    const toggleBtn = document.getElementById('toggleCourseInfoBtn');
    const btnIcon = toggleBtn.querySelector('i');
    
    // استرجاع الحالة المحفوظة
    const isHidden = localStorage.getItem('courseInfoHidden') === 'true';
    
    // تطبيق الحالة المحفوظة
    if (isHidden) {
        courseInfoContent.style.display = 'none';
        courseSectionsContent.style.display = 'none';
        btnIcon.className = 'fas fa-eye';
    }
    
    // إضافة مستمع الحدث للزر
    toggleBtn.addEventListener('click', function() {
        const isCurrentlyHidden = courseInfoContent.style.display === 'none';
        
        // تبديل حالة العرض
        courseInfoContent.style.display = isCurrentlyHidden ? 'block' : 'none';
        courseSectionsContent.style.display = isCurrentlyHidden ? 'block' : 'none';
        
        // تحديث الأيقونة
        btnIcon.className = isCurrentlyHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
        
        // حفظ الحالة في localStorage
        localStorage.setItem('courseInfoHidden', !isCurrentlyHidden);
    });
}

// دالة تهيئة حالة إظهار/إخفاء الإحصائيات
function initStatsVisibility() {
    const statsContent = document.getElementById('statsContent');
    const toggleBtn = document.getElementById('toggleStatsBtn');
    const btnIcon = toggleBtn.querySelector('i');
    
    // استرجاع الحالة المحفوظة
    const isHidden = localStorage.getItem('statsHidden') === 'true';
    
    // تطبيق الحالة المحفوظة
    if (isHidden) {
        statsContent.style.display = 'none';
        btnIcon.className = 'fas fa-eye';
    }
    
    // إضافة مستمع الحدث للزر
    toggleBtn.addEventListener('click', function() {
        const isCurrentlyHidden = statsContent.style.display === 'none';
        
        // تبديل حالة العرض
        statsContent.style.display = isCurrentlyHidden ? 'block' : 'none';
        
        // تحديث الأيقونة
        btnIcon.className = isCurrentlyHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
        
        // حفظ الحالة في localStorage
        localStorage.setItem('statsHidden', !isCurrentlyHidden);
    });
}

// دالة تهيئة حالة إظهار/إخفاء حالات الدروس
function initStatusesVisibility() {
    const statusesContent = document.getElementById('statusesContent');
    const toggleBtn = document.getElementById('toggleStatusesBtn');
    const btnIcon = toggleBtn.querySelector('i');
    
    // استرجاع الحالة المحفوظة
    const isHidden = localStorage.getItem('statusesHidden') === 'true';
    
    // تطبيق الحالة المحفوظة
    if (isHidden) {
        statusesContent.style.display = 'none';
        btnIcon.className = 'fas fa-eye';
    }
    
    // إضافة مستمع الحدث للزر
    toggleBtn.addEventListener('click', function() {
        const isCurrentlyHidden = statusesContent.style.display === 'none';
        
        // تبديل حالة العرض
        statusesContent.style.display = isCurrentlyHidden ? 'block' : 'none';
        
        // تحديث الأيقونة
        btnIcon.className = isCurrentlyHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
        
        // حفظ الحالة في localStorage
        localStorage.setItem('statusesHidden', !isCurrentlyHidden);
    });
}

// Function to update lesson statistics
function updateLessonsStats() {
    // Get all lessons
    const lessons = document.querySelectorAll('tr[data-lesson-id]');
    const totalLessons = lessons.length;
    
    // Initialize status counts
    const statusCounts = {};
    
    // Count lessons for each status
    lessons.forEach(lesson => {
        const statusSelect = lesson.querySelector('.status-select');
        const statusId = statusSelect.value;
        
        if (statusId) {
            statusCounts[statusId] = (statusCounts[statusId] || 0) + 1;
        }
    });
    
    // Update status cards
    document.querySelectorAll('.status-card').forEach(card => {
        const statusId = card.dataset.statusId;
        const count = statusCounts[statusId] || 0;
        const countElement = card.querySelector('.status-count');
        const progressBar = card.querySelector('.progress-bar');
        
        // Update count
        countElement.textContent = count;
        
        // Update progress bar
        const percentage = totalLessons > 0 ? (count / totalLessons) * 100 : 0;
        progressBar.style.width = `${percentage}%`;
    });
}

// Function to handle status change
function handleStatusChange(lessonId, statusId) {
    // Show loading indicator
    showLoader();
    
    // Make AJAX request to update status
    fetch('ajax/update_lesson_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `lesson_id=${lessonId}&status_id=${statusId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update status select styling
            const statusSelect = document.querySelector(`tr[data-lesson-id="${lessonId}"] .status-select`);
            const selectedOption = statusSelect.options[statusSelect.selectedIndex];
            
            statusSelect.style.backgroundColor = selectedOption.dataset.color || '';
            statusSelect.style.color = selectedOption.dataset.textColor || '';
            
            // Update statistics
            updateLessonsStats();
            
            // Show success message
            toastr.success('تم تحديث حالة الدرس بنجاح');
        } else {
            throw new Error(data.message || 'حدث خطأ أثناء تحديث حالة الدرس');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toastr.error(error.message);
    })
    .finally(() => {
        hideLoader();
    });
}

// تحديث مستمعي الأحداث
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة الرسوم البيانية
    initCharts();
    
    // تهيئة حالة إظهار/إخفاء الرسوم البيانية
    initChartsVisibility();
    
    // تهيئة حالة إظهار/إخفاء الإحصائيات
    initStatsVisibility();
    
    // تهيئة حالة إظهار/إخفاء معلومات الكورس
    initCourseInfoVisibility();
    
    // تهيئة حالة إظهار/إخفاء حالات الدروس
    initStatusesVisibility();
    
    // تحديث البيانات الأولية
    updateStats('');

    // مستمع حدث تغيير القسم
    document.getElementById('sectionStatsFilter').addEventListener('change', function(e) {
        updateStats(e.target.value);
    });
    

    // Initial update of lesson statistics
    updateLessonsStats();
    
    // Add event listeners for status changes
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            const lessonId = this.dataset.lessonId;
            const statusId = this.value;
            handleStatusChange(lessonId, statusId);
        });
    });
});
</script>
