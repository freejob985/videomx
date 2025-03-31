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

        // إضافة العناصر للقائمة
        this.menu.innerHTML = `
            <div class="context-menu-item" data-action="view">
                <i class="fas fa-play"></i>
                عرض الدروس
            </div>
            <div class="context-menu-item" data-action="edit">
                <i class="fas fa-edit"></i>
                تعديل القسم
            </div>
            <div class="context-menu-separator"></div>
            <div class="context-menu-item" data-action="share">
                <i class="fas fa-share-alt"></i>
                مشاركة
            </div>
            <div class="context-menu-item" data-action="report">
                <i class="fas fa-flag"></i>
                إبلاغ عن مشكلة
            </div>
        `;

        // إضافة مستمعات الأحداث
        document.addEventListener('contextmenu', this.handleContextMenu.bind(this));
        document.addEventListener('click', this.hideMenu.bind(this));
        window.addEventListener('resize', this.hideMenu.bind(this));
        window.addEventListener('scroll', this.hideMenu.bind(this));

        // إضافة مستمعات الأحداث لعناصر القائمة
        this.menu.querySelectorAll('.context-menu-item').forEach(item => {
            item.addEventListener('click', this.handleMenuItemClick.bind(this));
        });
    }

    handleContextMenu(e) {
        // التحقق مما إذا كان النقر على بطاقة القسم
        const sectionCard = e.target.closest('.section-card');
        if (!sectionCard) {
            this.hideMenu();
            return;
        }

        e.preventDefault();
        
        // تخزين معرف القسم
        this.menu.dataset.sectionId = sectionCard.dataset.sectionId;

        // عرض القائمة في موقع النقر
        this.showMenu(e.clientX, e.clientY);
    }

    showMenu(x, y) {
        this.menu.style.display = 'block';
        
        // تعديل موقع القائمة لتجنب تجاوز حدود النافذة
        const menuRect = this.menu.getBoundingClientRect();
        const windowWidth = window.innerWidth;
        const windowHeight = window.innerHeight;

        if (x + menuRect.width > windowWidth) {
            x = windowWidth - menuRect.width;
        }

        if (y + menuRect.height > windowHeight) {
            y = windowHeight - menuRect.height;
        }

        this.menu.style.left = x + 'px';
        this.menu.style.top = y + 'px';
    }

    hideMenu() {
        this.menu.style.display = 'none';
    }

    handleMenuItemClick(e) {
        const action = e.currentTarget.dataset.action;
        const sectionId = this.menu.dataset.sectionId;

        switch (action) {
            case 'view':
                window.location.href = `/sections/lessons.php?section_id=${sectionId}`;
                break;
            case 'edit':
                window.location.href = `/sections/edit.php?section_id=${sectionId}`;
                break;
            case 'share':
                // تنفيذ وظيفة المشاركة
                this.shareSection(sectionId);
                break;
            case 'report':
                // تنفيذ وظيفة الإبلاغ
                this.reportSection(sectionId);
                break;
        }

        this.hideMenu();
    }

    shareSection(sectionId) {
        // يمكن إضافة منطق المشاركة هنا
        alert('جاري تطوير خاصية المشاركة...');
    }

    reportSection(sectionId) {
        // يمكن إضافة منطق الإبلاغ هنا
        alert('جاري تطوير خاصية الإبلاغ...');
    }
} 