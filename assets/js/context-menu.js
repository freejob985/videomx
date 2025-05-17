class ContextMenu {
    constructor() {
        this.menu = null;
        this.init();
    }

    init() {
        // إنشاء عنصر القائمة
        this.menu = document.createElement('div');
        this.menu.className = 'context-menu';
        document.body.appendChild(this.menu);

        // إضافة مستمعات الأحداث
        document.addEventListener('contextmenu', this.handleContextMenu.bind(this));
        document.addEventListener('click', this.hideMenu.bind(this));
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.hideMenu();
        });
        window.addEventListener('resize', this.hideMenu.bind(this));
        window.addEventListener('scroll', this.hideMenu.bind(this));
    }

    handleContextMenu(e) {
        // التحقق من أن النقر على صف في الجدول
        const row = e.target.closest('tr[data-lesson-id]');
        if (!row) {
            this.hideMenu();
            return;
        }

        e.preventDefault();
        
        const lessonId = row.dataset.lessonId;
        const lessonTitle = row.querySelector('.lesson-name').textContent.trim();
        const courseTitle = row.querySelector('.course-badge').textContent.trim();
        const videoUrl = row.querySelector('.btn-play').getAttribute('onclick')?.match(/'([^']+)'/)?.[1] || '';

        // تحديث محتوى القائمة
        this.showMenu(e.clientX, e.clientY, {
            lessonId,
            lessonTitle,
            courseTitle,
            videoUrl
        });
    }

    showMenu(x, y, data) {
        // تحديث محتوى القائمة
        this.menu.innerHTML = this.getMenuHTML(data);
        
        // إعادة تعيين الأنماط قبل إظهار القائمة
        this.menu.style.display = 'block';
        this.menu.style.opacity = '0';
        
        // تحديد موقع القائمة
        const menuRect = this.menu.getBoundingClientRect();
        const windowWidth = window.innerWidth;
        const windowHeight = window.innerHeight;

        // حساب الموقع مع مراعاة حدود النافذة
        let menuX = x;
        let menuY = y;

        if (x + menuRect.width > windowWidth) {
            menuX = windowWidth - menuRect.width - 10;
        }

        if (y + menuRect.height > windowHeight) {
            menuY = windowHeight - menuRect.height - 10;
        }

        // تعيين الموقع النهائي
        this.menu.style.left = `${menuX}px`;
        this.menu.style.top = `${menuY}px`;

        // إظهار القائمة مع التأثير الحركي
        requestAnimationFrame(() => {
            this.menu.classList.add('show');
            this.menu.style.opacity = '1';
        });

        // إضافة مستمعات الأحداث للعناصر
        this.addEventListeners(data);
    }

    hideMenu() {
        this.menu.classList.remove('show');
        this.menu.style.opacity = '0';
        setTimeout(() => {
            if (!this.menu.classList.contains('show')) {
                this.menu.style.display = 'none';
            }
        }, 200);
    }

    getMenuHTML(data) {
        return `
            <div class="context-menu-item" data-action="play">
                <i class="fas fa-play"></i>
                تشغيل الدرس
            </div>
            <div class="context-menu-item" data-action="edit">
                <i class="fas fa-edit"></i>
                تعديل الدرس
            </div>
            <div class="context-menu-divider"></div>
            <div class="context-menu-item" data-action="move">
                <i class="fas fa-exchange-alt"></i>
                نقل إلى قسم آخر
            </div>
            <div class="context-menu-item" data-action="complete">
                <i class="fas fa-check-circle"></i>
                تحديد كمكتمل
            </div>
            <div class="context-menu-divider"></div>
            <div class="context-menu-item" data-action="ai">
                <i class="fas fa-robot"></i>
                تحليل مع ChatGPT
            </div>
            <div class="context-menu-item" data-action="grok">
                <i class="fas fa-brain"></i>
                تحليل مع Grok
            </div>
            <div class="context-menu-divider"></div>
            <div class="context-menu-item" data-action="notes">
                <i class="fas fa-sticky-note"></i>
                إضافة ملاحظة
            </div>
        `;
    }

    addEventListeners(data) {
        this.menu.querySelectorAll('.context-menu-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const action = e.currentTarget.dataset.action;
                this.handleAction(action, data);
                this.hideMenu();
            });
        });
    }

    handleAction(action, data) {
        switch (action) {
            case 'play':
                // استخدام الدالة الموجودة لتشغيل الفيديو
                playVideo(data.videoUrl, data.lessonTitle);
                break;
            case 'edit':
                // فتح صفحة تعديل الدرس
                window.location.href = `/content/edit_lesson.php?id=${data.lessonId}`;
                break;
            case 'move':
                // استخدام الدالة الموجودة لتغيير القسم
                editLessonSection(data.lessonId, currentSectionId);
                break;
            case 'complete':
                // تحديث حالة إكمال الدرس
                const button = document.querySelector(`tr[data-lesson-id="${data.lessonId}"] .btn-complete`);
                if (button) {
                    toggleLessonCompletion(data.lessonId, button);
                }
                break;
            case 'ai':
                // تحليل الدرس مع ChatGPT
                askGPTAboutLesson(data.lessonTitle, currentSectionName, currentLanguageName);
                break;
            case 'grok':
                // تحليل الدرس مع Grok
                askGrokAboutLesson(data.lessonTitle, currentSectionName, currentLanguageName);
                break;
            case 'notes':
                // إضافة ملاحظة جديدة
                showAddNoteModal(data.lessonId);
                break;
        }
    }
}

// تهيئة القائمة السياقية عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    window.contextMenu = new ContextMenu();
}); 