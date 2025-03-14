// تحديث الإحصائيات عند تحميل الصفحة وعند تغيير اللغة
document.addEventListener('DOMContentLoaded', function() {
    updateStatistics();
    loadLocalStorageSettings();

    // إضافة مستمع لتغيير اللغة
    document.querySelectorAll('.language-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            setTimeout(updateStatistics, 100); // تحديث بعد تغيير اللغة
        });
    });
});

// تحديث الإحصائيات
function updateStatistics() {
    // الحصول على معرف اللغة المحددة من URL
    const urlParams = new URLSearchParams(window.location.search);
    const languageId = urlParams.get('language_id');
    
    // إظهار حالة التحميل
    showLoadingState();
    
    // إرسال طلب AJAX للحصول على الإحصائيات
    fetch(`get_statistics.php${languageId ? `?language_id=${languageId}` : ''}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Statistics data:', data); // للتشخيص
            
            // تحديث العناصر في الصفحة
            updateElement('completedLessons', data.completed_lessons || 0);
            updateElement('totalLessons', data.total_lessons || 0);
            updateElement('remainingLessons', data.remaining_lessons || 0);
            updateElement('completedDuration', formatDuration(data.completed_duration));
            updateElement('totalDuration', formatDuration(data.total_duration));
            updateElement('remainingDuration', formatDuration(data.remaining_duration));
            updateElement('reviewLessons', data.review_lessons || 0);
            updateElement('theoryLessons', data.theory_lessons || 0);
            updateElement('importantLessons', data.important_lessons || 0);
            
            hideLoadingState();
        })
        .catch(error => {
            console.error('Error fetching statistics:', error);
            showError('حدث خطأ في تحميل الإحصائيات');
            hideLoadingState();
        });
}

// تحديث عنصر بأمان
function updateElement(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
    }
}

// إظهار حالة التحميل
function showLoadingState() {
    document.querySelectorAll('.stat-numbers').forEach(el => {
        el.classList.add('loading');
    });
}

// إخفاء حالة التحميل
function hideLoadingState() {
    document.querySelectorAll('.stat-numbers').forEach(el => {
        el.classList.remove('loading');
    });
}

// إظهار رسالة خطأ
function showError(message) {
    showToast(message, 'error');
}

// تنسيق الوقت
function formatDuration(seconds) {
    if (!seconds) return '00:00:00';
    seconds = parseInt(seconds);
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remainingSeconds = seconds % 60;
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
}

// حفظ إعدادات المستخدم في localStorage
function saveUserSettings(key, value) {
    localStorage.setItem(`userSettings_${key}`, value);
}

// تحميل إعدادات المستخدم من localStorage
function loadLocalStorageSettings() {
    // تحميل حالة المفضلة
    document.querySelectorAll('.btn-favorite').forEach(button => {
        const courseId = button.getAttribute('data-course-id');
        const isFavorite = localStorage.getItem(`userSettings_favorite_${courseId}`);
        if (isFavorite === 'true') {
            const icon = button.querySelector('i');
            icon.classList.remove('text-secondary');
            icon.classList.add('text-danger');
            button.dataset.favorite = '1';
        }
    });
}

// تحديث حالة المفضلة في localStorage
function updateFavoriteStatus(courseId, isFavorite) {
    saveUserSettings(`favorite_${courseId}`, isFavorite);
}

// تعديل دالة toggleFavorite لتحديث localStorage
function toggleFavorite(courseId, button) {
    fetch('toggle_favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ course_id: courseId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const icon = button.querySelector('i');
            if (data.is_favorite) {
                icon.classList.remove('text-secondary');
                icon.classList.add('text-danger');
                showToast('تمت إضافة الكورس إلى المفضلة');
                updateFavoriteStatus(courseId, true);
            } else {
                icon.classList.remove('text-danger');
                icon.classList.add('text-secondary');
                showToast('تمت إزالة الكورس من المفضلة');
                updateFavoriteStatus(courseId, false);
            }
            button.dataset.favorite = data.is_favorite ? '1' : '0';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('حدث خطأ أثناء تحديث المفضلة');
    });
} 