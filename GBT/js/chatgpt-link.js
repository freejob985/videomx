/**
 * نظام توجيه الأسئلة إلى منصات الذكاء الاصطناعي
 * ============================
 * هذا النظام يسمح بإرسال الأسئلة مباشرة إلى منصات الذكاء الاصطناعي المختلفة وإدخالها تلقائياً
 * 
 * المميزات:
 * - إدخال السؤال يدوياً
 * - روابط مباشرة للأسئلة مع query string
 * - إرسال تلقائي للسؤال
 * - دعم لمنصات متعددة: ChatGPT, Claude, Bard
 * 
 * المدخلات:
 * - عنوان القسم (string)
 * - محتوى السؤال (string)
 * - المنصة المستهدفة (string)
 * 
 * المخرجات:
 * - فتح نافذة المنصة المطلوبة مع السؤال
 * - إدخال السؤال تلقائياً
 * - محاولة إرسال السؤال
 * 
 * التبعيات: لا يوجد (vanilla JavaScript)
 */

/**
 * المنصات المدعومة
 * @type {Object}
 */
const AI_PLATFORMS = {
    CHATGPT: {
        name: 'ChatGPT',
        url: 'https://chat.openai.com/',
        icon: 'fa-robot',
        color: '#10a37f',
        textSelector: 'textarea[placeholder="Send a message"]',
        insertFunction: insertChatGPTQuestion
    },
    CLAUDE: {
        name: 'Claude',
        url: 'https://claude.ai/chat',
        icon: 'fa-comment-dots',
        color: '#6b46fe',
        textSelector: 'div[contenteditable="true"]',
        insertFunction: insertClaudeQuestion
    },
    BARD: {
        name: 'Bard',
        url: 'https://bard.google.com/',
        icon: 'fa-comment',
        color: '#8e44ad',
        textSelector: 'textarea[placeholder="Enter a prompt here"]',
        insertFunction: insertBardQuestion
    }
};

/**
 * دالة التوجيه إلى منصة الذكاء الاصطناعي مع السؤال
 * @param {string} question - السؤال المراد إرساله
 * @param {string} platform - المنصة المستهدفة (CHATGPT, CLAUDE, BARD)
 * @returns {void}
 * 
 * مثال الاستخدام:
 * directAILink("شرح مفصل عن: Arrays في JavaScript", "CHATGPT");
 */
function directAILink(question, platform = 'CHATGPT') {
    // التحقق من وجود السؤال
    if (!question) {
        console.error('لم يتم تحديد سؤال');
        return;
    }
    
    // التحقق من وجود المنصة
    if (!AI_PLATFORMS[platform]) {
        console.error(`المنصة ${platform} غير مدعومة`);
        return;
    }
    
    const platformConfig = AI_PLATFORMS[platform];
    
    // تشفير السؤال للاستخدام في URL
    const encodedQuestion = encodeURIComponent(question);
    
    // إنشاء الرابط مع query string
    const aiUrl = `${platformConfig.url}?q=${encodedQuestion}`;
    
    // فتح المنصة في نافذة جديدة
    const aiWindow = window.open(aiUrl, '_blank');
    
    // عدد محاولات البحث عن مربع النص
    let attempts = 0;
    const maxAttempts = 50;
    
    /**
     * دالة لمراقبة تحميل الصفحة وإضافة السؤال
     * تحاول إضافة السؤال عدة مرات حتى تنجح أو تتجاوز الحد الأقصى للمحاولات
     */
    const waitForPageLoad = () => {
        const interval = setInterval(() => {
            attempts++;
            
            try {
                if (aiWindow.document.readyState === 'complete') {
                    if (platformConfig.insertFunction(aiWindow, encodedQuestion)) {
                        clearInterval(interval);
                        console.log(`تم إضافة السؤال بنجاح في ${platformConfig.name}`);
                        
                        // محاولات إضافية للتأكد من إدخال السؤال
                        for (let i = 1; i <= 3; i++) {
                            setTimeout(() => platformConfig.insertFunction(aiWindow, encodedQuestion), i * 500);
                        }
                    } else if (attempts >= maxAttempts) {
                        clearInterval(interval);
                        console.error(`فشل في إضافة السؤال بعد عدة محاولات في ${platformConfig.name}`);
                    }
                }
            } catch (error) {
                clearInterval(interval);
                console.error(`خطأ في الوصول للصفحة ${platformConfig.name}:`, error);
            }
        }, 300);
    };

    // إضافة مستمع لحدث تحميل الصفحة
    if (aiWindow) {
        aiWindow.addEventListener('load', waitForPageLoad);
    }
}

/**
 * إدخال السؤال في ChatGPT
 * @param {Window} window - نافذة ChatGPT
 * @param {string} encodedQuestion - السؤال المشفر
 * @returns {boolean} نجاح العملية
 */
function insertChatGPTQuestion(window, encodedQuestion) {
    try {
        // البحث عن مربع النص
        const textArea = window.document.querySelector('textarea[placeholder="Send a message"]') || 
                       window.document.querySelector('textarea[data-id="root"]') ||
                       window.document.querySelector('div[contenteditable="true"]');
        
        if (textArea) {
            // قراءة السؤال من query string
            const urlParams = new URLSearchParams(window.location.search);
            const urlQuestion = urlParams.get('q');
            
            if (!urlQuestion) {
                console.error('لم يتم العثور على السؤال في query string');
                return false;
            }

            // فك تشفير السؤال
            const decodedQuestion = decodeURIComponent(urlQuestion);
            
            // حذف محتوى placeholder وإضافة السؤال
            if (textArea.tagName.toLowerCase() === 'textarea') {
                textArea.value = decodedQuestion;
            } else {
                textArea.innerHTML = decodedQuestion;
            }
            
            // تحديث قيمة مربع النص
            textArea.dispatchEvent(new Event('input', { bubbles: true }));
            textArea.dispatchEvent(new Event('change', { bubbles: true }));
            
            // تفعيل مربع النص
            textArea.focus();
            
            // محاكاة كتابة النص
            const inputEvent = new InputEvent('input', {
                bubbles: true,
                cancelable: true,
                inputType: 'insertText',
                data: decodedQuestion
            });
            textArea.dispatchEvent(inputEvent);
            
            // محاكاة ضغط Enter
            const enterEvent = new KeyboardEvent('keydown', {
                key: 'Enter',
                code: 'Enter',
                keyCode: 13,
                which: 13,
                bubbles: true,
                cancelable: true,
                composed: true
            });
            textArea.dispatchEvent(enterEvent);
            
            return true;
        }
        return false;
    } catch (error) {
        console.error('خطأ في محاولة إضافة السؤال في ChatGPT:', error);
        return false;
    }
}

/**
 * إدخال السؤال في Claude
 * @param {Window} window - نافذة Claude
 * @param {string} encodedQuestion - السؤال المشفر
 * @returns {boolean} نجاح العملية
 */
function insertClaudeQuestion(window, encodedQuestion) {
    try {
        // البحث عن مربع النص
        const textArea = window.document.querySelector('div[contenteditable="true"]');
        
        if (textArea) {
            // قراءة السؤال من query string
            const urlParams = new URLSearchParams(window.location.search);
            const urlQuestion = urlParams.get('q');
            
            if (!urlQuestion) {
                console.error('لم يتم العثور على السؤال في query string');
                return false;
            }

            // فك تشفير السؤال
            const decodedQuestion = decodeURIComponent(urlQuestion);
            
            // إضافة السؤال
            textArea.innerHTML = decodedQuestion;
            
            // تحديث قيمة مربع النص
            textArea.dispatchEvent(new Event('input', { bubbles: true }));
            textArea.dispatchEvent(new Event('change', { bubbles: true }));
            
            // تفعيل مربع النص
            textArea.focus();
            
            // البحث عن زر الإرسال
            const sendButton = window.document.querySelector('button[aria-label="Send message"]');
            if (sendButton) {
                sendButton.click();
                return true;
            }
            
            return false;
        }
        return false;
    } catch (error) {
        console.error('خطأ في محاولة إضافة السؤال في Claude:', error);
        return false;
    }
}

/**
 * إدخال السؤال في Bard
 * @param {Window} window - نافذة Bard
 * @param {string} encodedQuestion - السؤال المشفر
 * @returns {boolean} نجاح العملية
 */
function insertBardQuestion(window, encodedQuestion) {
    try {
        // البحث عن مربع النص
        const textArea = window.document.querySelector('textarea[placeholder="Enter a prompt here"]');
        
        if (textArea) {
            // قراءة السؤال من query string
            const urlParams = new URLSearchParams(window.location.search);
            const urlQuestion = urlParams.get('q');
            
            if (!urlQuestion) {
                console.error('لم يتم العثور على السؤال في query string');
                return false;
            }

            // فك تشفير السؤال
            const decodedQuestion = decodeURIComponent(urlQuestion);
            
            // إضافة السؤال
            textArea.value = decodedQuestion;
            
            // تحديث قيمة مربع النص
            textArea.dispatchEvent(new Event('input', { bubbles: true }));
            textArea.dispatchEvent(new Event('change', { bubbles: true }));
            
            // تفعيل مربع النص
            textArea.focus();
            
            // البحث عن زر الإرسال
            const sendButton = window.document.querySelector('button[aria-label="Submit"]');
            if (sendButton) {
                sendButton.click();
                return true;
            }
            
            return false;
        }
        return false;
    } catch (error) {
        console.error('خطأ في محاولة إضافة السؤال في Bard:', error);
        return false;
    }
}

/**
 * تنسيق السؤال للقسم
 * @param {string} sectionName - اسم القسم
 * @param {string} languageName - اسم اللغة
 * @param {string} description - وصف القسم
 * @returns {string} السؤال المنسق
 */
function formatSectionQuestion(sectionName, languageName, description) {
    return `اريد شرح تفصيلي للقسم: ${sectionName} في لغة ${languageName}

الوصف الحالي للقسم:
${description || 'لا يوجد وصف متاح'}

المطلوب:
1. وصف مختصر للقسم في خمسة أسطر
2. شرح تفصيلي للمفاهيم الأساسية في هذا القسم
3. أمثلة عملية وتطبيقية
4. أفضل الممارسات والنصائح
5. المصادر المفيدة لتعلم هذا القسم

`;
}

// للتوافق مع الكود القديم
function directChatGPTLink(question) {
    directAILink(question, 'CHATGPT');
}

// تصدير الدوال للاستخدام العام
window.directAILink = directAILink;
window.directChatGPTLink = directChatGPTLink;
window.formatSectionQuestion = formatSectionQuestion;
window.AI_PLATFORMS = AI_PLATFORMS; 