/**
 * إدارة قائمة السياق
 * يتحكم في عرض وإخفاء القائمة وتفاعلاتها
 */

class ContextMenu {
    constructor() {
        // تهيئة المتغيرات
        this.contextMenu = null;
        this.isVisible = false;
        
        // ربط الدوال
        this.init = this.init.bind(this);
        this.show = this.show.bind(this);
        this.hide = this.hide.bind(this);
        this.handleClick = this.handleClick.bind(this);
        
        // تهيئة القائمة
        this.init();
    }

    /**
     * تهيئة قائمة السياق
     */
    init() {
        // إنشاء عنصر القائمة
        this.contextMenu = document.createElement('div');
        this.contextMenu.className = 'context-menu';
        
        // إضافة العناصر للقائمة
        this.contextMenu.innerHTML = `
            <a href="http://videomx.com/content/languages.php" class="context-menu-item">
                <i class="fas fa-globe"></i>
                قائمة اللغات
            </a>
            <a href="http://videomx.com/content/index.php" class="context-menu-item">
                <i class="fas fa-graduation-cap"></i>
                الدورات
            </a>
            <div class="context-menu-divider"></div>
            <a href="http://videomx.com/add/add.php" target="_blank" class="context-menu-item">
                <i class="fas fa-cog"></i>
                الإعدادات
            </a>
            <a href="http://videomx.com/GBT/" target="_blank" class="context-menu-item ai-link">
                <i class="fas fa-robot"></i>
                المساعد الذكي
            </a>
            <div class="context-menu-divider"></div>
            <a href="http://videomx.com/" target="_blank" class="context-menu-item">
                <i class="fas fa-home"></i>
                البوابة
            </a>
            <a href="http://videomx.com/review/" target="_blank" class="context-menu-item">
                <i class="fas fa-star"></i>
                المراجعة
            </a>
        `;
        
        // إضافة القائمة للصفحة
        document.body.appendChild(this.contextMenu);
        
        // إضافة مستمعي الأحداث
        document.addEventListener('contextmenu', this.show);
        document.addEventListener('click', this.hide);
        document.addEventListener('scroll', this.hide);
        
        // معالجة النقر على عناصر القائمة
        this.contextMenu.addEventListener('click', this.handleClick);
    }

    /**
     * عرض القائمة
     * @param {Event} e - حدث النقر بالزر الأيمن
     */
    show(e) {
        e.preventDefault();
        
        // تحديد موقع القائمة
        const x = e.clientX;
        const y = e.clientY;
        
        // التأكد من عدم تجاوز حدود الشاشة
        const menuWidth = this.contextMenu.offsetWidth;
        const menuHeight = this.contextMenu.offsetHeight;
        const windowWidth = window.innerWidth;
        const windowHeight = window.innerHeight;
        
        // تعديل الموقع إذا تجاوز حدود الشاشة
        const posX = x + menuWidth > windowWidth ? windowWidth - menuWidth : x;
        const posY = y + menuHeight > windowHeight ? windowHeight - menuHeight : y;
        
        // تحديث موقع القائمة
        this.contextMenu.style.left = `${posX}px`;
        this.contextMenu.style.top = `${posY}px`;
        
        // إظهار القائمة
        this.contextMenu.classList.add('show');
        this.isVisible = true;
    }

    /**
     * إخفاء القائمة
     */
    hide() {
        if (this.isVisible) {
            this.contextMenu.classList.remove('show');
            this.isVisible = false;
        }
    }

    /**
     * معالجة النقر على عناصر القائمة
     * @param {Event} e - حدث النقر
     */
    handleClick(e) {
        const target = e.target.closest('.context-menu-item');
        if (target) {
            // إخفاء القائمة بعد النقر
            this.hide();
        }
    }
}

// تهيئة القائمة عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    window.contextMenu = new ContextMenu();
}); 