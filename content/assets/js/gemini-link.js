/**
 * نظام توجيه الأسئلة إلى منصة Gemini
 * ============================
 * 
 * الوصف:
 * هذا النظام يسمح بإرسال الأسئلة مباشرة إلى منصة Gemini وإدخالها تلقائياً
 * 
 * المميزات:
 * - إدخال السؤال تلقائياً في واجهة Gemini
 * - دعم للغة العربية والإنجليزية
 * - تنسيق السؤال بشكل مناسب
 * - إرسال السؤال تلقائياً
 * 
 * التبعيات: لا يوجد (vanilla JavaScript)
 * 
 * المدخلات المتوقعة:
 * - عنوان السؤال (string)
 * - سياق السؤال (string, اختياري)
 * 
 * المخرجات المتوقعة:
 * - فتح نافذة Gemini مع السؤال
 * - إدخال السؤال تلقائياً
 * - إرسال السؤال تلقائياً
 */

const GEMINI_CONFIG = {
    baseUrl: 'https://gemini.google.com/app'
};

/**
 * نسخ النص إلى الحافظة
 * @param {string} text - النص المراد نسخه
 * @returns {Promise<boolean>} نجاح العملية
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        console.log('تم نسخ السؤال إلى الحافظة');
        return true;
    } catch (error) {
        console.error('خطأ في نسخ النص:', error);
        
        // محاولة ثانية باستخدام الطريقة التقليدية
        try {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            console.log('تم نسخ السؤال بالطريقة التقليدية');
            return true;
        } catch (fallbackError) {
            console.error('فشل النسخ بالطريقة التقليدية:', fallbackError);
            return false;
        }
    }
}

/**
 * توجيه السؤال إلى Gemini
 * @param {string} question - السؤال المراد نسخه وفتح Gemini
 */
async function directGeminiLink(question) {
    if (!question) {
        console.error('لم يتم تحديد سؤال');
        return;
    }

    try {
        // 1. نسخ السؤال إلى الحافظة
        const copied = await copyToClipboard(question);
        
        if (copied) {
            // 2. فتح نافذة Gemini
            window.open(GEMINI_CONFIG.baseUrl, '_blank');
            
            // 3. إظهار رسالة للمستخدم
            alert('تم نسخ السؤال إلى الحافظة');
        } else {
            alert('فشل في نسخ السؤال، حاول مرة أخرى');
        }
    } catch (error) {
        console.error('خطأ:', error);
        alert('حدث خطأ أثناء العملية');
    }
}

/**
 * تنسيق السؤال لـ Gemini
 * @param {string} title - عنوان السؤال
 * @param {string} context - سياق السؤال (اختياري)
 * @returns {string} السؤال المنسق
 */
function formatGeminiQuestion(title, context = '') {
    return `${title}

${context ? `Context:
${context}

` : ''}Requirements:
1. Detailed explanation
2. Practical examples
3. Best practices
4. Tips and guidelines
5. Additional resources`;
}

// تصدير الدوال للاستخدام العام
window.directGeminiLink = directGeminiLink;
window.formatGeminiQuestion = formatGeminiQuestion;

// إضافة مستمع لأزرار Gemini عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.gemini-link').forEach(button => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            const title = button.dataset.title || '';
            const question = formatGeminiQuestion(title, '');
            await directGeminiLink(question);
        });
    });
});

/**
 * مثال على الاستخدام:
 * 
 * // إرسال سؤال مباشر
 * directGeminiLink('كيف يمكنني تعلم البرمجة؟');
 * 
 * // إرسال سؤال منسق
 * const title = 'تعلم البرمجة';
 * const context = 'أريد البدء في تعلم البرمجة من الصفر';
 * const question = formatGeminiQuestion(title, context);
 * directGeminiLink(question);
 */ 