/**
 * التحكم في عرض الفيديو
 * يدير خيارات عرض الفيديو وحفظ التفضيلات
 */

class VideoControls {
    constructor() {
        this.videoWrapper = document.querySelector('.video-wrapper');
        this.lessonContent = document.querySelector('.lesson-content');
        this.lessonSidebar = document.querySelector('#lesson-sidebar');
        this.storageKey = 'videoPreferences';
        this.init();
    }

    init() {
        if (!this.videoWrapper) return;
        this.createControls();
        this.loadPreferences();
        this.registerEvents();
        this.focusSidebar();
    }

    createControls() {
        const controls = document.createElement('div');
        controls.className = 'video-view-controls';
        controls.innerHTML = `
            <button class="btn btn-sm btn-outline-secondary view-toggle" data-view="normal">
                <i class="fas fa-compress"></i>
                عرض عادي
            </button>
            <button class="btn btn-sm btn-outline-secondary view-toggle" data-view="wide">
                <i class="fas fa-expand"></i>
                عرض موسع
            </button>
            <button class="btn btn-sm btn-outline-secondary view-toggle" data-view="fullscreen">
                <i class="fas fa-expand-arrows-alt"></i>
                ملء الشاشة
            </button>
        `;
        
        if (this.videoWrapper) {
            this.videoWrapper.insertBefore(controls, this.videoWrapper.firstChild);
        }
    }

    loadPreferences() {
        try {
            const preferences = JSON.parse(localStorage.getItem(this.storageKey)) || {};
            this.setVideoView(preferences.view || 'normal');
        } catch (e) {
            console.error('Error loading video preferences:', e);
            this.setVideoView('normal');
        }
    }

    registerEvents() {
        document.querySelectorAll('.view-toggle').forEach(button => {
            button.addEventListener('click', (e) => {
                const view = e.currentTarget.dataset.view;
                this.setVideoView(view);
                this.savePreferences(view);
            });
        });

        // استمع لتغيير حجم النافذة
        window.addEventListener('resize', () => {
            const currentView = this.getCurrentView();
            if (currentView === 'wide' || currentView === 'fullscreen') {
                this.adjustVideoSize(currentView);
            }
        });
    }

    getCurrentView() {
        const preferences = JSON.parse(localStorage.getItem(this.storageKey)) || {};
        return preferences.view || 'normal';
    }

    setVideoView(view) {
        if (!this.videoWrapper || !this.lessonContent) return;

        // إزالة جميع الأنماط السابقة
        this.videoWrapper.classList.remove('video-normal', 'video-wide', 'video-fullscreen');
        document.body.classList.remove('video-fullscreen-mode');
        
        // إضافة النمط الجديد
        this.videoWrapper.classList.add(`video-${view}`);

        // تحديث حالة الأزرار
        document.querySelectorAll('.view-toggle').forEach(button => {
            button.classList.remove('active');
            if (button.dataset.view === view) {
                button.classList.add('active');
            }
        });

        // تطبيق التغييرات حسب نوع العرض
        switch (view) {
            case 'normal':
                this.resetLayout();
                break;
            
            case 'wide':
                this.adjustVideoSize('wide');
                break;
            
            case 'fullscreen':
                this.adjustVideoSize('fullscreen');
                document.body.classList.add('video-fullscreen-mode');
                break;
        }
    }

    adjustVideoSize(view) {
        if (!this.videoWrapper || !this.lessonContent) return;

        if (view === 'wide') {
            // إعادة تعيين الأنماط السابقة
            this.resetLayout();
            
            // تطبيق العرض الموسع
            this.videoWrapper.classList.add('video-wide-active');
            
            // تحريك معلومات الدرس وتحديث الدرس للأسفل
            const lessonInfo = document.querySelector('.lesson-info');
            const lessonUpdate = document.querySelector('.lesson-update');
            
            if (lessonInfo) {
                lessonInfo.style.order = '1';
                lessonInfo.style.marginTop = '2rem';
                lessonInfo.style.width = '100%';
            }
            
            if (lessonUpdate) {
                lessonUpdate.style.order = '2';
                lessonUpdate.style.marginTop = '2rem';
                lessonUpdate.style.width = '100%';
            }

            // تعديل نسبة الفيديو
            const ratio = this.videoWrapper.querySelector('.ratio');
            if (ratio) {
                ratio.style.width = '100%';
                ratio.style.margin = '0 auto';
            }
            
            // حفظ التفضيلات
            this.savePreferences('wide');
        } else if (view === 'fullscreen') {
            // تطبيق عرض كامل الشاشة
            this.videoWrapper.style.position = 'fixed';
            this.videoWrapper.style.top = '0';
            this.videoWrapper.style.left = '0';
            this.videoWrapper.style.width = '100vw';
            this.videoWrapper.style.height = '100vh';
            this.videoWrapper.style.zIndex = '9999';
            this.videoWrapper.style.backgroundColor = '#000';
            
            // تعديل نسبة الفيديو
            const ratio = this.videoWrapper.querySelector('.ratio');
            if (ratio) {
                ratio.style.height = '100vh';
            }
        }
    }

    resetLayout() {
        if (!this.videoWrapper) return;
        
        // إعادة تعيين خصائص الفيديو
        this.videoWrapper.style.width = '';
        this.videoWrapper.style.maxWidth = '';
        this.videoWrapper.style.height = '';
        this.videoWrapper.style.marginLeft = '';
        this.videoWrapper.style.marginRight = '';
        this.videoWrapper.style.position = '';
        this.videoWrapper.style.top = '';
        this.videoWrapper.style.left = '';
        this.videoWrapper.style.zIndex = '';
        this.videoWrapper.style.backgroundColor = '';
        
        // إعادة تعيين نسبة الفيديو
        const ratio = this.videoWrapper.querySelector('.ratio');
        if (ratio) {
            ratio.style.height = '';
            ratio.style.maxHeight = '';
        }
        
        // إعادة تعيين معلومات الدرس وتحديث الدرس
        const lessonInfo = document.querySelector('.lesson-info');
        const lessonUpdate = document.querySelector('.lesson-update');
        
        if (lessonInfo) {
            lessonInfo.style.order = '';
            lessonInfo.style.marginTop = '';
            lessonInfo.style.width = '';
        }
        
        if (lessonUpdate) {
            lessonUpdate.style.order = '';
            lessonUpdate.style.marginTop = '';
            lessonUpdate.style.width = '';
        }
        
        // إزالة الفئة النشطة
        this.videoWrapper.classList.remove('video-wide-active');
    }

    savePreferences(view) {
        try {
            const preferences = {
                view: view,
                timestamp: new Date().toISOString()
            };
            localStorage.setItem(this.storageKey, JSON.stringify(preferences));
        } catch (e) {
            console.error('Error saving video preferences:', e);
        }
    }

    focusSidebar() {
        if (this.lessonSidebar) {
            setTimeout(() => {
                this.lessonSidebar.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start'
                });
                
                this.lessonSidebar.classList.add('sidebar-focus');
                
                setTimeout(() => {
                    this.lessonSidebar.classList.remove('sidebar-focus');
                }, 2000);
            }, 500);
        }
    }
}

export default VideoControls; 