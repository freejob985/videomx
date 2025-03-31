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
        
        // إضافة عناصر القائمة
        this.menu.innerHTML = `
            <a href="/content/index.php" class="context-menu-item">
                <i class="fas fa-home"></i>
                الرئيسية
            </a>
            <a href="/content/languages.php" class="context-menu-item">
                <i class="fas fa-globe"></i>
                اللغات
            </a>
            <div class="context-menu-divider"></div>
            <a href="/content/search/" class="context-menu-item">
                <i class="fas fa-search"></i>
                البحث
            </a>
            <a href="/review/" class="context-menu-item">
                <i class="fas fa-clock"></i>
                المراجعة
            </a>
            <div class="context-menu-divider"></div>
            <a href="/add/add.php" class="context-menu-item">
                <i class="fas fa-cog"></i>
                الإعدادات
            </a>
            <a href="http://videomx.com/GBT/" class="context-menu-item" target="_blank">
                <i class="fas fa-robot"></i>
                المساعد الذكي
            </a>
        `;

        // إضافة القائمة للصفحة
        document.body.appendChild(this.menu);

        // إضافة مستمعي الأحداث
        this.addEventListeners();
    }

    /**
     * إضافة مستمعي الأحداث
     */
    addEventListeners() {
        // تعديل معالجة النقر بالزر الأيمن
        document.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            
            // الحصول على إحداثيات النقر
            const x = e.clientX;
            const y = e.clientY;
            
            // إظهار القائمة في موقع النقر
            this.show(x, y);
        });

        // تعديل إخفاء القائمة
        document.addEventListener('click', (e) => {
            // التحقق من أن النقر ليس على القائمة نفسها
            if (!this.menu.contains(e.target)) {
                this.hide();
            }
        });

        // إضافة معالج مفتاح ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hide();
            }
        });

        // إخفاء القائمة عند التمرير
        document.addEventListener('scroll', () => {
            this.hide();
        });
    }

    /**
     * إظهار القائمة
     * @param {number} x - الموضع الأفقي
     * @param {number} y - الموضع الرأسي
     */
    show(x, y) {
        // إخفاء القائمة أولاً لإعادة حساب الأبعاد
        this.menu.style.display = 'block';
        this.menu.style.visibility = 'hidden';

        // حساب أبعاد القائمة والنافذة
        const menuWidth = this.menu.offsetWidth;
        const menuHeight = this.menu.offsetHeight;
        const windowWidth = window.innerWidth;
        const windowHeight = window.innerHeight;

        // تعديل الموقع إذا كانت القائمة ستتجاوز حدود النافذة
        let finalX = x;
        let finalY = y;

        if (x + menuWidth > windowWidth) {
            finalX = windowWidth - menuWidth;
        }

        if (y + menuHeight > windowHeight) {
            finalY = windowHeight - menuHeight;
        }

        // تعيين الموقع النهائي
        this.menu.style.left = `${finalX}px`;
        this.menu.style.top = `${finalY}px`;

        // إظهار القائمة
        this.menu.style.visibility = 'visible';
        this.menu.classList.add('show');
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