/**
 * إدارة قائمة السياق
 */
class ContextMenu {
    constructor() {
        this.menu = null;
        this.init();
    }

    /**
     * تهيئة القائمة
     */
    init() {
        // إنشاء عنصر القائمة
        this.menu = document.createElement('div');
        this.menu.className = 'context-menu';
        this.menu.setAttribute('dir', 'rtl'); // إضافة اتجاه RTL
        document.body.appendChild(this.menu);

        // إضافة عناصر القائمة
        this.menu.innerHTML = `
            <a href="/content/languages.php" class="context-menu-item">
                <i class="fas fa-language"></i>
                <span>اللغات</span>
            </a>
            <a href="/content/index.php" class="context-menu-item">
                <i class="fas fa-home"></i>
                <span>الرئيسية</span>
            </a>
            <a href="/content/index.php" class="context-menu-item">
                <i class="fas fa-graduation-cap"></i>
                <span>الدورات</span>
            </a>
            <a href="/" class="context-menu-item">
                <i class="fas fa-door-open"></i>
                <span>البوابة</span>
            </a>
            <div class="context-menu-divider"></div>
            <a href="/content/search/" class="context-menu-item">
                <i class="fas fa-search"></i>
                <span>البحث</span>
            </a>
            <a href="/add/add.php" class="context-menu-item">
                <i class="fas fa-cog"></i>
                <span>الإعدادات</span>
            </a>
            <a href="/review/" class="context-menu-item">
                <i class="fas fa-check-double"></i>
                <span>المراجعة</span>
            </a>
            <a href="/GBT/" class="context-menu-item">
                <i class="fas fa-robot"></i>
                <span>المساعد الذكي</span>
            </a>
        `;

        // إضافة مستمعي الأحداث
        this.addEventListeners();
    }

    /**
     * إضافة مستمعي الأحداث
     */
    addEventListeners() {
        // إظهار القائمة عند النقر بالزر الأيمن
        document.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            this.show(e.clientX, e.clientY);
        });

        // إخفاء القائمة عند النقر في أي مكان آخر
        document.addEventListener('click', (e) => {
            if (!this.menu.contains(e.target)) {
                this.hide();
            }
        });

        // إخفاء القائمة عند الضغط على ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hide();
            }
        });

        // إضافة تأثيرات حركية للعناصر
        const items = this.menu.querySelectorAll('.context-menu-item');
        items.forEach(item => {
            // إضافة تأثير عند التحويم
            item.addEventListener('mouseenter', () => {
                item.style.transform = 'translateX(-5px)';
            });
            
            item.addEventListener('mouseleave', () => {
                item.style.transform = 'translateX(0)';
            });

            // إضافة تأثير عند النقر
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const href = item.getAttribute('href');
                
                // إضافة تأثير النقر
                item.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    window.location.href = href;
                }, 150);
            });
        });
    }

    /**
     * إظهار القائمة
     * @param {number} x - الموضع الأفقي
     * @param {number} y - الموضع الرأسي
     */
    show(x, y) {
        // تحديد موضع القائمة
        const menuWidth = this.menu.offsetWidth;
        const menuHeight = this.menu.offsetHeight;
        const windowWidth = window.innerWidth;
        const windowHeight = window.innerHeight;

        // التأكد من عدم تجاوز حدود النافذة
        if (x + menuWidth > windowWidth) {
            x = windowWidth - menuWidth - 10;
        }
        if (y + menuHeight > windowHeight) {
            y = windowHeight - menuHeight - 10;
        }

        // تحديث موضع القائمة
        this.menu.style.left = `${x}px`;
        this.menu.style.top = `${y}px`;
        
        // إظهار القائمة مع تأثير حركي
        requestAnimationFrame(() => {
            this.menu.classList.add('show');
        });
    }

    /**
     * إخفاء القائمة
     */
    hide() {
        this.menu.classList.remove('show');
    }
}

// تهيئة القائمة عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    new ContextMenu();
}); 