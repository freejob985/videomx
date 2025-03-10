/**
 * نظام توجيه الأسئلة إلى ChatGPT
 * ============================
 * هذا النظام يسمح بإرسال الأسئلة مباشرة إلى ChatGPT وإدخالها تلقائياً
 * 
 * المميزات:
 * - إدخال السؤال يدوياً
 * - روابط مباشرة للأسئلة مع query string
 * - إرسال تلقائي للسؤال
 * - دعم لموقع chat.openai.com
 * 
 * المدخلات:
 * - عنوان الدرس (string)
 * - محتوى السؤال (string)
 * 
 * المخرجات:
 * - فتح نافذة ChatGPT مع السؤال
 * - إدخال السؤال تلقائياً
 * - محاولة إرسال السؤال
 * 
 * التبعيات: لا يوجد (vanilla JavaScript)
 */

/**
 * دالة التوجيه إلى ChatGPT مع السؤال
 * @param {string} question - السؤال المراد إرساله
 * @returns {void}
 * 
 * مثال الاستخدام:
 * directChatGPTLink("شرح مفصل عن: Arrays في JavaScript");
 */
function directChatGPTLink(question) {
    // التحقق من وجود السؤال
    if (!question) {
        console.error('لم يتم تحديد سؤال');
        return;
    }

    // تشفير السؤال للاستخدام في URL
    const encodedQuestion = encodeURIComponent(question);
    
    // إنشاء الرابط مع query string
    const chatGPTUrl = `https://chat.openai.com/?q=${encodedQuestion}`;
    
    // فتح ChatGPT في نافذة جديدة
    const chatGPTWindow = window.open(chatGPTUrl, '_blank');
    
    // عدد محاولات البحث عن مربع النص
    let attempts = 0;
    const maxAttempts = 50;
    
    /**
     * دالة لمحاولة إضافة السؤال في الصفحة
     * @returns {boolean} نجاح أو فشل العملية
     * 
     * السيلكتورات المدعومة:
     * - textarea[placeholder="Send a message"]
     * - textarea[data-id="root"]
     * - div[contenteditable="true"]
     */
    const tryToInsertQuestion = () => {
        try {
            // البحث عن مربع النص باستخدام السيلكتورات المختلفة
            const textArea = chatGPTWindow.document.querySelector('textarea[placeholder="Send a message"]') || 
                           chatGPTWindow.document.querySelector('textarea[data-id="root"]') ||
                           chatGPTWindow.document.querySelector('div[contenteditable="true"]');
            
            if (textArea) {
                // قراءة السؤال من query string
                const urlParams = new URLSearchParams(chatGPTWindow.location.search);
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
            console.error('خطأ في محاولة إضافة السؤال:', error);
            return false;
        }
    };

    /**
     * دالة لمراقبة تحميل الصفحة وإضافة السؤال
     * تحاول إضافة السؤال عدة مرات حتى تنجح أو تتجاوز الحد الأقصى للمحاولات
     */
    const waitForPageLoad = () => {
        const interval = setInterval(() => {
            attempts++;
            
            try {
                if (chatGPTWindow.document.readyState === 'complete') {
                    if (tryToInsertQuestion()) {
                        clearInterval(interval);
                        console.log('تم إضافة السؤال بنجاح');
                        
                        // محاولات إضافية للتأكد من إدخال السؤال
                        for (let i = 1; i <= 3; i++) {
                            setTimeout(tryToInsertQuestion, i * 500);
                        }
                    } else if (attempts >= maxAttempts) {
                        clearInterval(interval);
                        console.error('فشل في إضافة السؤال بعد عدة محاولات');
                    }
                }
            } catch (error) {
                clearInterval(interval);
                console.error('خطأ في الوصول للصفحة:', error);
            }
        }, 300);
    };

    // إضافة مستمع لحدث تحميل الصفحة
    if (chatGPTWindow) {
        chatGPTWindow.addEventListener('load', waitForPageLoad);
    }
}

/**
 * تنسيق السؤال للدرس
 * @param {string} title - عنوان الدرس
 * @returns {string} السؤال المنسق
 */
function formatLessonQuestion(title) {
    return `اريد شرح نظري بالأمثلة والتفاصيل للدرس: ${title}

المطلوب:
1. شرح مفصل للمفاهيم النظرية
2. أمثلة عملية وتطبيقية
3. تفاصيل وملاحظات مهمة
4. شرح مختصر وموجز

`;
}

// إضافة مستمعي الأحداث عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // إضافة مستمعي الأحداث للروابط الديناميكية
    document.querySelectorAll('.chatgpt-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const title = this.getAttribute('data-title');
            if (title) {
                const question = formatLessonQuestion(title);
                directChatGPTLink(question);
            }
        });
    });
});

// تصدير الدوال للاستخدام العام
window.directChatGPTLink = directChatGPTLink;
window.formatLessonQuestion = formatLessonQuestion; 