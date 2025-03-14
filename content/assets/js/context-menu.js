/**
 * قائمة السياق للدرس
 * يحتوي على الوظائف والأحداث الخاصة بالقائمة السياقية
 */

class LessonContextMenu {
    constructor() {
        this.menu = null;
        this.noteTypeMenu = null;
        this.isMouseDown = false;
        this.mouseOffset = { x: 0, y: 0 };
        this.init();
    }

    init() {
        this.createMenuElement();
        this.createNoteTypeMenu();
        this.registerEvents();
    }

    createMenuElement() {
        this.menu = document.createElement('div');
        this.menu.className = 'lesson-context-menu';
        
        const dragHandle = document.createElement('div');
        dragHandle.className = 'menu-drag-handle';
        
        const content = document.createElement('div');
        content.className = 'menu-content';
        content.innerHTML = `
            <ul>
                <li data-action="next" class="menu-item">
                    <i class="fas fa-arrow-left"></i>
                    <span>الدرس التالي</span>
                    <span class="shortcut">→</span>
                </li>
                <li data-action="prev" class="menu-item">
                    <i class="fas fa-arrow-right"></i>
                    <span>الدرس السابق</span>
                    <span class="shortcut">←</span>
                </li>
                <li class="divider"></li>
                <li data-action="complete" class="menu-item">
                    <i class="fas fa-check-circle"></i>
                    <span>إكمال الدرس</span>
                    <span class="shortcut">Alt + C</span>
                </li>
                <li data-action="review" class="menu-item">
                    <i class="fas fa-bookmark"></i>
                    <span>إضافة للمراجعة</span>
                    <span class="shortcut">Alt + R</span>
                </li>
                <li class="divider"></li>
                <li class="menu-item note-type-trigger">
                    <i class="fas fa-sticky-note"></i>
                    <span>نوع الملاحظة</span>
                    <i class="fas fa-chevron-left"></i>
                </li>
            </ul>
        `;

        this.menu.appendChild(dragHandle);
        this.menu.appendChild(content);
        document.body.appendChild(this.menu);

        this.registerDragEvents(dragHandle);
    }

    createNoteTypeMenu() {
        // إنشاء قائمة نوع الملاحظة المنفصلة
        this.noteTypeMenu = document.createElement('div');
        this.noteTypeMenu.className = 'note-type-menu';
        this.noteTypeMenu.innerHTML = `
            <div class="menu-header">
                <i class="fas fa-sticky-note"></i>
                <span>اختر نوع الملاحظة</span>
            </div>
            <div class="menu-items">
                <div class="menu-item" data-note-type="text">
                    <div class="item-icon">
                        <i class="fas fa-font"></i>
                    </div>
                    <div class="item-content">
                        <div class="item-title">نص عادي</div>
                        <div class="item-description">إضافة ملاحظة نصية</div>
                    </div>
                    <div class="item-shortcut">Alt + T</div>
                </div>
                <div class="menu-item" data-note-type="code">
                    <div class="item-icon">
                        <i class="fas fa-code"></i>
                    </div>
                    <div class="item-content">
                        <div class="item-title">كود برمجي</div>
                        <div class="item-description">إضافة مقطع برمجي</div>
                    </div>
                    <div class="item-shortcut">Alt + D</div>
                </div>
                <div class="menu-item" data-note-type="link">
                    <div class="item-icon">
                        <i class="fas fa-link"></i>
                    </div>
                    <div class="item-content">
                        <div class="item-title">رابط خارجي</div>
                        <div class="item-description">إضافة رابط مع وصف</div>
                    </div>
                    <div class="item-shortcut">Alt + L</div>
                </div>
            </div>
        `;
        document.body.appendChild(this.noteTypeMenu);
    }

    getMenuContent() {
        return `
            <li data-action="next" class="menu-item">
                <i class="fas fa-arrow-left"></i>
                <span>الدرس التالي</span>
                <span class="shortcut">→</span>
            </li>
            <li data-action="prev" class="menu-item">
                <i class="fas fa-arrow-right"></i>
                <span>الدرس السابق</span>
                <span class="shortcut">←</span>
            </li>
            <li class="divider"></li>
            <li data-action="complete" class="menu-item">
                <i class="fas fa-check-circle"></i>
                <span>إكمال الدرس</span>
                <span class="shortcut">Alt + C</span>
            </li>
            <li data-action="review" class="menu-item">
                <i class="fas fa-bookmark"></i>
                <span>إضافة للمراجعة</span>
                <span class="shortcut">Alt + R</span>
            </li>
            <li class="divider"></li>
            <li class="menu-item has-submenu">
                <i class="fas fa-sticky-note"></i>
                <span>نوع الملاحظة</span>
                <i class="fas fa-chevron-left submenu-arrow"></i>
                <ul class="submenu">
                    <li data-note-type="text" class="menu-item">
                        <i class="fas fa-font"></i>
                        <span>نص</span>
                        <span class="shortcut">Alt + T</span>
                    </li>
                    <li data-note-type="code" class="menu-item">
                        <i class="fas fa-code"></i>
                        <span>كود</span>
                        <span class="shortcut">Alt + D</span>
                    </li>
                    <li data-note-type="link" class="menu-item">
                        <i class="fas fa-link"></i>
                        <span>رابط</span>
                        <span class="shortcut">Alt + L</span>
                    </li>
                </ul>
            </li>
        `;
    }

    registerDragEvents(dragHandle) {
        dragHandle.addEventListener('mousedown', (e) => {
            this.isMouseDown = true;
            const menuRect = this.menu.getBoundingClientRect();
            
            this.mouseOffset = {
                x: e.clientX - menuRect.left,
                y: e.clientY - menuRect.top
            };
            
            this.menu.classList.add('dragging');
        });

        document.addEventListener('mousemove', (e) => {
            if (!this.isMouseDown) return;
            
            e.preventDefault();
            
            let newX = e.clientX - this.mouseOffset.x;
            let newY = e.clientY - this.mouseOffset.y;
            
            const menuRect = this.menu.getBoundingClientRect();
            const windowWidth = window.innerWidth;
            const windowHeight = window.innerHeight;
            
            newX = Math.max(0, Math.min(newX, windowWidth - menuRect.width));
            newY = Math.max(0, Math.min(newY, windowHeight - menuRect.height));
            
            this.menu.style.left = `${newX}px`;
            this.menu.style.top = `${newY}px`;
        });

        document.addEventListener('mouseup', () => {
            if (this.isMouseDown) {
                this.isMouseDown = false;
                this.menu.classList.remove('dragging');
            }
        });
    }

    registerEvents() {
        document.addEventListener('click', (e) => {
            if (!this.menu.contains(e.target)) {
                this.hideMenu();
            }
        });

        this.menu.addEventListener('click', (e) => {
            const menuItem = e.target.closest('[data-action], [data-note-type]');
            if (!menuItem) return;

            if (menuItem.dataset.action) {
                this.handleMenuAction(menuItem.dataset.action);
            } else if (menuItem.dataset.noteType) {
                this.changeNoteType(menuItem.dataset.noteType);
            }
        });

        document.addEventListener('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
        });

        document.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            this.showMenu(e.pageX, e.pageY);
        });

        this.menu.addEventListener('mouseenter', (e) => {
            const submenuTrigger = e.target.closest('.has-submenu');
            console.log(submenuTrigger);
            if (submenuTrigger) {
                const submenu = submenuTrigger.querySelector('.submenu');
                const rect = submenuTrigger.getBoundingClientRect();
                
                if (rect.right - submenu.offsetWidth < 0) {
                    submenu.style.right = 'auto';
                    submenu.style.left = '100%';
                }
            }
        });

        this.menu.addEventListener('click', (e) => {

            const noteTypeButton = e.target.closest('.note-type-trigger');
            if (noteTypeButton) {
                const rect = noteTypeButton.getBoundingClientRect();
                this.showNoteTypeMenu(rect.right, rect.top);
                e.stopPropagation();
            }
        });

        this.noteTypeMenu.addEventListener('click', (e) => {
            const menuItem = e.target.closest('[data-note-type]');
            if (menuItem) {
                const type = menuItem.dataset.noteType;
                // تحديث قيمة السليكت بناءً على النوع المختار
                const noteTypeSelect = document.querySelector('#noteType');
                if (noteTypeSelect) {
                    noteTypeSelect.value = type;
                    // تفعيل حدث التغيير لتحديث واجهة المستخدم
                    noteTypeSelect.dispatchEvent(new Event('change'));
                }
                this.changeNoteType(type);
                this.hideNoteTypeMenu();
                e.stopPropagation();
            }
        });

        document.addEventListener('click', (e) => {
            if (!this.menu.contains(e.target) && !this.noteTypeMenu.contains(e.target)) {
                this.hideMenu();
                this.hideNoteTypeMenu();
            }
        });
    }

    showMenu(x, y) {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

        const relativeX = x - scrollLeft;
        const relativeY = y - scrollTop;

        this.menu.style.display = 'block';

        const menuRect = this.menu.getBoundingClientRect();
        const windowWidth = window.innerWidth;
        const windowHeight = window.innerHeight;

        let finalX = relativeX;
        let finalY = relativeY;

        if (relativeX + menuRect.width > windowWidth) {
            finalX = windowWidth - menuRect.width;
        }

        if (finalX < 0) {
            finalX = 0;
        }

        if (relativeY + menuRect.height > windowHeight) {
            finalY = windowHeight - menuRect.height;
        }

        if (finalY < 0) {
            finalY = 0;
        }

        this.menu.style.left = `${finalX}px`;
        this.menu.style.top = `${finalY}px`;
    }

    hideMenu() {
        this.menu.style.display = 'none';
    }

    handleMenuAction(action) {
        switch (action) {
            case 'next':
                this.navigateToNextLesson();
                break;
            case 'prev':
                this.navigateToPrevLesson();
                break;
            case 'complete':
                this.toggleLessonCompletion();
                break;
            case 'review':
                this.toggleLessonReview();
                break;
            case 'note-type':
                break;
        }
        this.hideMenu();
    }

    handleKeyboardShortcuts(e) {
        if (e.altKey) {
            switch (e.key.toLowerCase()) {
                case 'c':
                    e.preventDefault();
                    this.toggleLessonCompletion();
                    break;
                case 'r':
                    e.preventDefault();
                    this.toggleLessonReview();
                    break;
                case 't':
                    e.preventDefault();
                    this.changeNoteType('text');
                    break;
                case 'd':
                    e.preventDefault();
                    this.changeNoteType('code');
                    break;
                case 'l':
                    e.preventDefault();
                    this.changeNoteType('link');
                    break;
            }
        } else {
            switch (e.key) {
                case 'ArrowRight':
                    e.preventDefault();
                    this.navigateToPrevLesson();
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    this.navigateToNextLesson();
                    break;
            }
        }
    }

    navigateToNextLesson() {
        const nextButton = document.querySelector('a[href*="lesson-details.php"][class*="btn-primary"]');
        if (nextButton) {
            window.location.href = nextButton.href;
        }
    }

    navigateToPrevLesson() {
        const prevButton = document.querySelector('a[href*="lesson-details.php"][class*="btn-outline-primary"]');
        if (prevButton) {
            window.location.href = prevButton.href;
        }
    }

    toggleLessonCompletion() {
        const toggleButton = document.getElementById('toggleCompletion');
        if (toggleButton) {
            toggleButton.click();
        }
    }

    toggleLessonReview() {
        const reviewButton = document.getElementById('toggleReview');
        if (reviewButton) {
            reviewButton.click();
        }
    }

    changeNoteType(type) {
        // البحث عن نموذج الملاحظات
        const noteForm = document.querySelector('#noteForm');
        if (!noteForm) return;

        // البحث عن السيلكت بالمعرف الصحيح
        const noteTypeSelect = noteForm.querySelector('#noteType');
        if (noteTypeSelect) {
            // تحديث قيمة السيلكت
            noteTypeSelect.value = type;
            
            // تشغيل حدث change لتحديث النموذج
            const changeEvent = new Event('change', { bubbles: true, cancelable: true });
            const isChanged = noteTypeSelect.dispatchEvent(changeEvent);

            if (isChanged) {
                // تحديث واجهة المستخدم
                this.updateNoteFormUI(type, noteForm);

                // التمرير إلى قسم الملاحظات
                const notesSection = document.querySelector('.notes-section');
                if (notesSection) {
                    notesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                // تركيز العنصر المناسب
                setTimeout(() => {
                    switch (type) {
                        case 'text':
                            const textContent = document.querySelector('#textContent');
                            if (textContent) {
                                textContent.focus();
                                textContent.select();
                            }
                            break;
                        
                        case 'code':
                            const codeContent = document.querySelector('#codeContent');
                            if (codeContent) {
                                codeContent.focus();
                                codeContent.select();
                            }
                            break;
                        
                        case 'link':
                            const linkUrl = document.querySelector('#linkUrl');
                            if (linkUrl) {
                                linkUrl.focus();
                                linkUrl.select();
                            }
                            break;
                    }
                }, 300);
            }
        }

        // إخفاء القوائم
        this.hideMenu();
        this.hideNoteTypeMenu();
    }

    // دالة جديدة لتحديث واجهة المستخدم
    updateNoteFormUI(type, noteForm) {
        // تحديث عنوان النموذج
        const formTitle = noteForm.querySelector('.note-form-title');
        if (formTitle) {
            const titles = {
                text: 'إضافة ملاحظة نصية',
                code: 'إضافة كود برمجي',
                link: 'إضافة رابط خارجي'
            };
            formTitle.textContent = titles[type] || 'إضافة ملاحظة';
        }

        // تحديث حالة الأزرار
        const noteTypeButtons = noteForm.querySelectorAll('.note-type-btn');
        noteTypeButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.type === type);
        });

        // تركيز العنصر المناسب
        setTimeout(() => {
            switch (type) {
                case 'text':
                    if (window.tinymce) {
                        const editor = window.tinymce.get('note_content');
                        if (editor) {
                            editor.focus();
                        }
                    }
                    break;
                
                case 'code':
                    const codeEditor = document.querySelector('.CodeMirror');
                    if (codeEditor && codeEditor.CodeMirror) {
                        codeEditor.CodeMirror.focus();
                        codeEditor.CodeMirror.refresh();
                    }
                    break;
                
                case 'link':
                    const linkInput = noteForm.querySelector('input[name="link_url"]');
                    if (linkInput) {
                        linkInput.focus();
                    }
                    break;
            }
        }, 300);
    }

    showNoteTypeMenu(x, y) {
        // إخفاء القائمة الرئيسية
        this.hideMenu();

        // عرض قائمة نوع الملاحظة
        this.noteTypeMenu.style.display = 'block';

        // حساب الموقع المناسب
        const menuRect = this.noteTypeMenu.getBoundingClientRect();
        const windowWidth = window.innerWidth;
        const windowHeight = window.innerHeight;

        let finalX = x;
        let finalY = y;

        if (x + menuRect.width > windowWidth) {
            finalX = windowWidth - menuRect.width;
        }
        if (y + menuRect.height > windowHeight) {
            finalY = windowHeight - menuRect.height;
        }

        this.noteTypeMenu.style.left = `${finalX}px`;
        this.noteTypeMenu.style.top = `${finalY}px`;
    }

    hideNoteTypeMenu() {
        if (this.noteTypeMenu) {
            this.noteTypeMenu.style.display = 'none';
        }
    }
}

export default LessonContextMenu; 