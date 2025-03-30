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

const GROK_CONFIG = {
    url: 'https://grok.com/',
    textSelector: 'textarea[aria-label="Ask Grok anything"]',
    submitSelector: 'button[type="submit"]'
};

/**
 * توجيه السؤال إلى Grok
 * @param {string} question - السؤال المراد إرساله
 * @returns {void}
 */
function directGrokLink(question) {
    if (!question) {
        console.error('لم يتم تحديد سؤال');
        return;
    }
    
    const encodedQuestion = encodeURIComponent(question);
    const grokWindow = window.open(`${GROK_CONFIG.url}?q=${encodedQuestion}`, '_blank');
    
    let attempts = 0;
    const maxAttempts = 50;
    
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
        const textArea = window.document.querySelector(GROK_CONFIG.textSelector);
        
        if (textArea) {
            const urlParams = new URLSearchParams(window.location.search);
            const urlQuestion = urlParams.get('q');
            
            if (!urlQuestion) {
                console.error('لم يتم العثور على السؤال في query string');
                return false;
            }

            const decodedQuestion = decodeURIComponent(urlQuestion);
            textArea.value = decodedQuestion;
            
            textArea.dispatchEvent(new Event('input', { bubbles: true }));
            textArea.dispatchEvent(new Event('change', { bubbles: true }));
            textArea.focus();
            
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
1. شرح مفصل للمفاهيم النظرية
2. أمثلة عملية وتطبيقية
3. تفاصيل وملاحظات مهمة
4. شرح مختصر وموجز
5. أفضل الممارسات
6. مصادر إضافية للتعلم`;
}

// تصدير الدوال للاستخدام العام
window.directGrokLink = directGrokLink;
window.formatGrokQuestion = formatGrokQuestion;

// إضافة مستمع لأزرار Grok عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.grok-link').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const title = button.dataset.title || '';
            const question = formatGrokQuestion(title, '');
            directGrokLink(question);
        });
    });
}); 