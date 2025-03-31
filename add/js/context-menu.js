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

        // إضافة محتوى القائمة
        this.menu.innerHTML = `
            <div class="context-menu-header">خيارات سريعة</div>
            <a href="http://videomx.com/content/index.php" class="context-menu-item">
                <i class="fas fa-home"></i>
                الرئيسية
            </a>
            <a href="http://videomx.com/content/languages.php" class="context-menu-item">
                <i class="fas fa-language"></i>
                اللغات
            </a>
            <div class="context-menu-divider"></div>
            <a href="http://videomx.com/content/search/" class="context-menu-item">
                <i class="fas fa-search"></i>
                البحث
            </a>
            <a href="http://videomx.com/review/" class="context-menu-item">
                <i class="fas fa-star"></i>
                المراجعة
            </a>
            <div class="context-menu-divider"></div>
            <a href="http://videomx.com/add/add.php" class="context-menu-item">
                <i class="fas fa-cog"></i>
                الإعدادات
            </a>
            <a href="http://videomx.com/GBT/" class="context-menu-item">
                <i class="fas fa-robot"></i>
                المساعد الذكي
            </a>
        `;

        // إضافة مستمعات الأحداث
        document.addEventListener('contextmenu', this.show.bind(this));
        document.addEventListener('click', this.hide.bind(this));
        document.addEventListener('scroll', this.hide.bind(this));
        window.addEventListener('resize', this.hide.bind(this));
    }

    show(e) {
        e.preventDefault();
        
        // تحديد موقع القائمة
        const x = e.clientX;
        const y = e.clientY;
        
        // التأكد من عدم خروج القائمة عن حدود الشاشة
        const menuWidth = this.menu.offsetWidth;
        const menuHeight = this.menu.offsetHeight;
        const windowWidth = window.innerWidth;
        const windowHeight = window.innerHeight;
        
        // تعديل الموقع إذا كانت القائمة ستخرج عن حدود الشاشة
        const adjustedX = x + menuWidth > windowWidth ? windowWidth - menuWidth : x;
        const adjustedY = y + menuHeight > windowHeight ? windowHeight - menuHeight : y;
        
        // تحديث موقع القائمة
        this.menu.style.left = `${adjustedX}px`;
        this.menu.style.top = `${adjustedY}px`;
        
        // إظهار القائمة
        this.menu.classList.add('show');
    }

    hide() {
        this.menu.classList.remove('show');
    }
} 