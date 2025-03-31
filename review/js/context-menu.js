class ContextMenu {
    constructor() {
        this.init();
    }

    init() {
        // إنشاء عنصر القائمة
        this.createMenuElement();
        
        // إضافة مستمعي الأحداث
        this.addEventListeners();
    }

    createMenuElement() {
        // إنشاء عنصر القائمة
        this.menuElement = document.createElement('div');
        this.menuElement.className = 'context-menu';
        
        // إضافة عناصر القائمة
        this.menuElement.innerHTML = `
            <a href="/content/languages.php" class="context-menu-item">
                <i class="fas fa-language"></i>
                اللغات
            </a>
            <a href="/content/index.php" class="context-menu-item">
                <i class="fas fa-home"></i>
                الرئيسية
            </a>
            <a href="/content/index.php" class="context-menu-item">
                <i class="fas fa-graduation-cap"></i>
                الدورات
            </a>
            <div class="context-menu-divider"></div>
            <a href="/" class="context-menu-item">
                <i class="fas fa-door-open"></i>
                البوابة
            </a>
            <a href="/content/search/" class="context-menu-item">
                <i class="fas fa-search"></i>
                البحث
            </a>
            <a href="/add/add.php" class="context-menu-item">
                <i class="fas fa-cog"></i>
                الإعدادات
            </a>
            <div class="context-menu-divider"></div>
            <a href="/review/" class="context-menu-item">
                <i class="fas fa-tasks"></i>
                المراجعة
            </a>
            <a href="/GBT/" class="context-menu-item">
                <i class="fas fa-robot"></i>
                المساعد الذكي
            </a>
        `;
        
        // إضافة القائمة للصفحة
        document.body.appendChild(this.menuElement);
    }

    addEventListeners() {
        // مستمع حدث النقر بالزر الأيمن
        document.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            this.show(e.clientX, e.clientY);
        });

        // إخفاء القائمة عند النقر في أي مكان
        document.addEventListener('click', (e) => {
            // إذا كان النقر خارج القائمة
            if (!this.menuElement.contains(e.target)) {
                this.hide();
            }
        });

        // إخفاء القائمة عند التمرير
        document.addEventListener('scroll', () => {
            this.hide();
        });

        // إخفاء القائمة عند الضغط على ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hide();
            }
        });
    }

    updatePosition(x, y) {
        const menuWidth = this.menuElement.offsetWidth;
        const menuHeight = this.menuElement.offsetHeight;
        const windowWidth = window.innerWidth;
        const windowHeight = window.innerHeight;

        // التأكد من عدم تجاوز حدود النافذة
        if (x + menuWidth > windowWidth) {
            x = windowWidth - menuWidth - 5;
        }
        if (y + menuHeight > windowHeight) {
            y = windowHeight - menuHeight - 5;
        }

        // التأكد من عدم خروج القائمة عن الحدود اليسرى والعلوية
        x = Math.max(5, x);
        y = Math.max(5, y);

        this.menuElement.style.left = `${x}px`;
        this.menuElement.style.top = `${y}px`;
    }

    show(x, y) {
        this.menuElement.classList.add('show');
        this.updatePosition(x, y);
    }

    hide() {
        this.menuElement.classList.remove('show');
    }
} 