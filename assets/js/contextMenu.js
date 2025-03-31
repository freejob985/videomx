class ContextMenu {
    constructor() {
        this.contextMenu = null;
        this.init();
    }

    init() {
        // إنشاء عنصر القائمة
        this.contextMenu = document.createElement('div');
        this.contextMenu.className = 'custom-context-menu';
        document.body.appendChild(this.contextMenu);

        // إضافة العناصر للقائمة
        this.contextMenu.innerHTML = `
            <ul>
                <li data-action="languages">
                    <i class="fas fa-globe"></i>
                    <span>اللغات</span>
                </li>
                <li data-action="home">
                    <i class="fas fa-home"></i>
                    <span>الرئيسية</span>
                </li>
                <li data-action="courses">
                    <i class="fas fa-graduation-cap"></i>
                    <span>الدورات</span>
                </li>
                <li data-action="portal">
                    <i class="fas fa-door-open"></i>
                    <span>البوابة</span>
                </li>
                <li data-action="search">
                    <i class="fas fa-search"></i>
                    <span>البحث</span>
                </li>
                <li data-action="settings">
                    <i class="fas fa-cog"></i>
                    <span>الإعدادات</span>
                </li>
                <li data-action="review">
                    <i class="fas fa-check-double"></i>
                    <span>المراجعة</span>
                </li>
                <li data-action="assistant">
                    <i class="fas fa-robot"></i>
                    <span>المساعد الذكي</span>
                </li>
            </ul>
        `;

        // إضافة مستمعي الأحداث
        document.addEventListener('contextmenu', this.showMenu.bind(this));
        document.addEventListener('click', this.hideMenu.bind(this));
        window.addEventListener('scroll', this.hideMenu.bind(this));

        // إضافة مستمع النقر على عناصر القائمة
        this.contextMenu.querySelectorAll('li').forEach(item => {
            item.addEventListener('click', this.handleMenuClick.bind(this));
        });
    }

    showMenu(e) {
        e.preventDefault();
        this.contextMenu.style.display = 'block';
        
        // تحديد موقع القائمة
        const x = e.clientX;
        const y = e.clientY;
        const windowWidth = window.innerWidth;
        const windowHeight = window.innerHeight;
        const menuWidth = this.contextMenu.offsetWidth;
        const menuHeight = this.contextMenu.offsetHeight;

        // التأكد من عدم تجاوز حدود النافذة
        if (x + menuWidth > windowWidth) {
            this.contextMenu.style.left = (windowWidth - menuWidth) + 'px';
        } else {
            this.contextMenu.style.left = x + 'px';
        }

        if (y + menuHeight > windowHeight) {
            this.contextMenu.style.top = (windowHeight - menuHeight) + 'px';
        } else {
            this.contextMenu.style.top = y + 'px';
        }
    }

    hideMenu() {
        if (this.contextMenu) {
            this.contextMenu.style.display = 'none';
        }
    }

    handleMenuClick(e) {
        const action = e.currentTarget.dataset.action;
        const urls = {
            'languages': '/content/languages.php',
            'home': '/content/index.php',
            'courses': '/content/index.php',
            'portal': '/',
            'search': '/content/search/',
            'settings': '/add/add.php',
            'review': '/review/',
            'assistant': '/GBT/'
        };

        if (urls[action]) {
            window.location.href = urls[action];
        }
    }
}

// تهيئة القائمة عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    new ContextMenu();
}); 