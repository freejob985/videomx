/**
 * نظام توجيه الأسئلة إلى منصة Grok
 * ============================
 * هذا النظام يسمح بإرسال الأسئلة مباشرة إلى منصة Grok وإدخالها تلقائياً
 * 
 * المميزات:
 * - إدخال السؤال تلقائياً في واجهة Grok
 * - دعم للغة العربية والإنجليزية
 * - تنسيق السؤال بشكل مناسب
 * 
 * التبعيات: لا يوجد (vanilla JavaScript)
 */

/**
 * تكوين منصة Grok
 * @type {Object}
 */
const GROK_CONFIG = {
    url: 'https://grok.com/',
    textSelector: 'textarea[aria-label="Ask Grok anything"]',
    submitSelector: 'button[type="submit"]'
};

/**
 * توجيه السؤال إلى Grok
 * @param {string} question - السؤال المراد إرساله
 * @returns {void}
 * 
 * مثال الاستخدام:
 * directGrokLink("شرح مفصل عن: Arrays في JavaScript");
 */
function directGrokLink(question) {
    if (!question) {
        console.error('لم يتم تحديد سؤال');
        return;
    }
    
    // تشفير السؤال للاستخدام في URL
    const encodedQuestion = encodeURIComponent(question);
    
    // فتح Grok في نافذة جديدة
    const grokWindow = window.open(`${GROK_CONFIG.url}?q=${encodedQuestion}`, '_blank');
    
    // عدد محاولات البحث عن مربع النص
    let attempts = 0;
    const maxAttempts = 50;
    
    /**
     * دالة لمراقبة تحميل الصفحة وإضافة السؤال
     */
    const waitForPageLoad = () => {
        const interval = setInterval(() => {
            attempts++;
            
            try {
                if (grokWindow.document.readyState === 'complete') {
                    if (insertGrokQuestion(grokWindow, encodedQuestion)) {
                        clearInterval(interval);
                        console.log('تم إضافة السؤال بنجاح في Grok');
                    } else if (attempts >= maxAttempts) {
                        clearInterval(interval);
                        console.error('فشل في إضافة السؤال بعد عدة محاولات');
                    }
                }
            } catch (error) {
                clearInterval(interval);
                console.error('خطأ في الوصول لصفحة Grok:', error);
            }
        }, 300);
    };

    // إضافة مستمع لحدث تحميل الصفحة
    if (grokWindow) {
        grokWindow.addEventListener('load', waitForPageLoad);
    }
}

/**
 * إدخال السؤال في واجهة Grok
 * @param {Window} window - نافذة Grok
 * @param {string} encodedQuestion - السؤال المشفر
 * @returns {boolean} نجاح العملية
 */
function insertGrokQuestion(window, encodedQuestion) {
    try {
        // البحث عن مربع النص
        const textArea = window.document.querySelector(GROK_CONFIG.textSelector);
        
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
            const submitButton = window.document.querySelector(GROK_CONFIG.submitSelector);
            if (submitButton) {
                submitButton.click();
                return true;
            }
            
            return false;
        }
        return false;
    } catch (error) {
        console.error('خطأ في محاولة إضافة السؤال في Grok:', error);
        return false;
    }
}

/**
 * تنسيق السؤال لـ Grok
 * @param {string} title - عنوان السؤال
 * @param {string} context - سياق السؤال
 * @returns {string} السؤال المنسق
 */
function formatGrokQuestion(title, context) {
    return `${title}

السياق:
${context}

المطلوب:
1. شرح مفصل للموضوع
2. أمثلة عملية
3. أفضل الممارسات
4. مصادر إضافية للتعلم`;
}

// تصدير الدوال للاستخدام العام
window.directGrokLink = directGrokLink;
window.formatGrokQuestion = formatGrokQuestion; 