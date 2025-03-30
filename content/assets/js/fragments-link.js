/**
 * نظام توجيه الأسئلة إلى منصة Fragments
 * ============================
 * هذا النظام يسمح بإرسال الأسئلة مباشرة إلى منصة Fragments وإدخالها تلقائياً
 * 
 * المميزات:
 * - إدخال السؤال تلقائياً في واجهة Fragments
 * - دعم للغة العربية والإنجليزية
 * - تنسيق السؤال بشكل مناسب
 * 
 * التبعيات: لا يوجد (vanilla JavaScript)
 */

const FRAGMENTS_CONFIG = {
    url: 'https://fragments.e2b.dev/',
    textSelector: 'textarea[placeholder="Describe your app..."]',
    submitSelector: 'button[type="submit"]'
};

/**
 * توجيه السؤال إلى Fragments
 * @param {string} question - السؤال المراد إرساله
 * @returns {void}
 */
function directFragmentsLink(question) {
    if (!question) {
        console.error('لم يتم تحديد سؤال');
        return;
    }

    // تشفير السؤال وإضافته للرابط
    const encodedQuestion = encodeURIComponent(question);
    const fragmentsUrl = `${FRAGMENTS_CONFIG.url}?prompt=${encodedQuestion}`;
    const fragmentsWindow = window.open(fragmentsUrl, '_blank');
    
    let attempts = 0;
    const maxAttempts = 100;
    
    const waitForPageLoad = () => {
        const interval = setInterval(() => {
            attempts++;
            
            try {
                if (fragmentsWindow && fragmentsWindow.document) {
                    const textArea = fragmentsWindow.document.querySelector(FRAGMENTS_CONFIG.textSelector);
                    
                    if (textArea) {
                        clearInterval(interval);
                        
                        // تعيين القيمة من query string
                        const urlParams = new URLSearchParams(fragmentsWindow.location.search);
                        const promptValue = urlParams.get('prompt');
                        if (promptValue) {
                            const decodedQuestion = decodeURIComponent(promptValue);
                            textArea.value = decodedQuestion;
                        } else {
                            textArea.value = question;
                        }
                        
                        // تحديث الارتفاع
                        textArea.style.height = 'auto';
                        textArea.style.height = textArea.scrollHeight + 'px';
                        
                        // إطلاق أحداث
                        ['input', 'change'].forEach(eventType => {
                            textArea.dispatchEvent(new Event(eventType, { bubbles: true }));
                        });
                        
                        // التركيز على الحقل
                        textArea.focus();
                        
                        // محاكاة ضغط Enter
                        setTimeout(() => {
                            // 1. محاكاة keydown
                            textArea.dispatchEvent(new KeyboardEvent('keydown', {
                                key: 'Enter',
                                code: 'Enter',
                                keyCode: 13,
                                which: 13,
                                bubbles: true,
                                cancelable: true
                            }));

                            // 2. إضافة سطر جديد للنص
                            textArea.value += '\n';

                            // 3. محاكاة keyup
                            textArea.dispatchEvent(new KeyboardEvent('keyup', {
                                key: 'Enter',
                                code: 'Enter',
                                keyCode: 13,
                                which: 13,
                                bubbles: true,
                                cancelable: true
                            }));

                            // 4. محاولة النقر على زر Submit
                            const submitButton = fragmentsWindow.document.querySelector(FRAGMENTS_CONFIG.submitSelector);
                            if (submitButton) {
                                submitButton.click();
                            }

                            // 5. محاولة تقديم النموذج إذا وجد
                            const form = textArea.closest('form');
                            if (form) {
                                form.dispatchEvent(new Event('submit', { bubbles: true }));
                            }
                        }, 500);

                        console.log('تم إدخال السؤال بنجاح');
                    } else if (attempts >= maxAttempts) {
                        clearInterval(interval);
                        console.error('فشل في العثور على حقل الإدخال');
                    }
                }
            } catch (error) {
                if (attempts >= maxAttempts) {
                    clearInterval(interval);
                    console.error('خطأ في الوصول للصفحة:', error);
                }
            }
        }, 100);
    };

    // بدء المحاولة بعد تحميل الصفحة
    if (fragmentsWindow) {
        fragmentsWindow.addEventListener('load', () => {
            setTimeout(waitForPageLoad, 1000);
        });
    }
}

/**
 * تنسيق السؤال لـ Fragments
 * @param {string} title - عنوان السؤال
 * @param {string} context - سياق السؤال
 * @returns {string} السؤال المنسق
 */
function formatFragmentsQuestion(title, context) {
    return `Create an app for: ${title}

Description:
${context}

Requirements:
1. Core Features
2. Technical Stack
3. Implementation Steps
4. Best Practices
5. Security Considerations
6. Performance Optimization`;
}

// تصدير الدوال للاستخدام العام
window.directFragmentsLink = directFragmentsLink;
window.formatFragmentsQuestion = formatFragmentsQuestion;

// إضافة مستمع لأزرار Fragments عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.fragments-link').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const title = button.dataset.title || '';
            const question = formatFragmentsQuestion(title, '');
            directFragmentsLink(question);
        });
    });
}); 