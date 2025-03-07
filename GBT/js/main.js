// التحقق من تحميل مكتبة marked
if (typeof marked === 'undefined') {
    console.error('مكتبة marked غير محملة');
} else {
    // إعداد خيارات marked
    marked.use({
        gfm: true,
        breaks: true,
        pedantic: false,
        sanitize: false,
        smartLists: true,
        smartypants: true,
        langPrefix: 'language-'
    });
}

// تكوين المتغيرات العامة
const GEMINI_API_KEY = 'AIzaSyA_jhlapD2z6sKE3avt0RsKHrlB_Y-6zFk';
const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

// دالة لعرض التوست
function showToast(message, type = 'success') {
    const toast = new bootstrap.Toast(document.getElementById('toast'));
    const toastBody = document.querySelector('.toast-body');
    toastBody.textContent = message;
    toast.show();
}

// دالة لتحضير السؤال مع الملاحظات الإضافية
function prepareQuestion(question) {
    return `${question}

يرجى:
1. اكتب وصفًا مختصرًا مكونًا من 7 جمل يتناول هذا الموضوع، على أن يكون موجزًا وواضحًا.
2. استخدم تنسيق Markdown للإجابة (العناوين، القوائم، الجداول، إلخ).
3. أضف قائمة بالكلمات الدلالية ذات الصلة تحت عنوان "الكلمات الدلالية:".`;
}

// دالة للحصول على إجابة من Gemini
async function getGeminiResponse(question) {
    try {
        const response = await fetch(`${GEMINI_API_URL}?key=${GEMINI_API_KEY}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                contents: [{
                    parts: [{
                        text: question
                    }]
                }]
            })
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(`Gemini API error: ${errorData.error?.message || 'Unknown error'}`);
        }

        const data = await response.json();
        
        // التحقق من وجود البيانات المطلوبة
        if (!data.candidates?.[0]?.content?.parts?.[0]?.text) {
            throw new Error('Invalid response format from Gemini API');
        }

        return data.candidates[0].content.parts[0].text;
    } catch (error) {
        console.error('خطأ في الاتصال بـ Gemini:', error);
        throw new Error(`فشل في الحصول على إجابة من Gemini: ${error.message}`);
    }
}

// دالة لتحليل النص واستخراج الكلمات الدلالية
function extractKeywords(text) {
    const keywordsMatch = text.match(/الكلمات الدلالية:[\s\n]+((?:[^:\n]+(?:\n|$))+)/i);
    if (keywordsMatch && keywordsMatch[1]) {
        return keywordsMatch[1]
            .split(/[,،\n]/)
            .map(keyword => keyword.trim())
            .filter(keyword => keyword.length > 0 && keyword !== '-');
    }
    return [];
}

// دالة لإضافة رسالة للمحادثة
function addMessage(content, type = 'user') {
    const messagesContainer = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    
    const icon = type === 'user' ? userIcon : geminiIcon;
    
    // معالجة الكلمات الدلالية والمحتوى
    let keywords = [];
    let processedContent = content;
    
    if (type === 'ai') {
        keywords = extractKeywords(content);
        processedContent = content.replace(/الكلمات الدلالية:[\s\n]+((?:[^:\n]+(?:\n|$))+)/i, '');
        // التحقق من وجود marked قبل استخدامه
        if (typeof marked !== 'undefined') {
            try {
                // استخدام marked.parse بدلاً من marked مباشرة
                processedContent = marked.parse(processedContent);
            } catch (error) {
                console.error('خطأ في تحويل Markdown:', error);
                // في حالة الفشل، استخدم النص كما هو
                processedContent = `<p>${processedContent}</p>`;
            }
        }
    }
    
    messageDiv.innerHTML = `
        <div class="message-icon">
            ${icon}
        </div>
        <div class="message-content">
            <button class="copy-button" title="نسخ النص" onclick="copyText(this)">
                <i class="fas fa-copy"></i>
            </button>
            <div class="message-text">${processedContent}</div>
            ${keywords.length > 0 ? `
                <div class="keywords-container">
                    <div class="keywords-title">
                        <i class="fas fa-tags"></i>
                        <span>الكلمات الدلالية:</span>
                    </div>
                    <div class="keywords-list">
                        ${keywords.map((keyword, index) => `
                            <div class="keyword-tag" 
                                 onclick="copyKeyword(this)" 
                                 style="--tag-index: ${index}">
                                <span class="keyword-text">${keyword}</span>
                                <i class="fas fa-copy copy-icon"></i>
                            </div>
                        `).join('\n')}
                    </div>
                </div>
            ` : ''}
        </div>
    `;
    
    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// دالة لنسخ النص
function copyText(button) {
    const messageContent = button.closest('.message-content');
    const textToCopy = messageContent.querySelector('.message-text').innerText; // استخدام innerText بدلاً من textContent
    
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(textToCopy).then(() => {
            updateCopyButton(button, true);
        }).catch(() => {
            fallbackCopyText(textToCopy, button);
        });
    } else {
        fallbackCopyText(textToCopy, button);
    }
}

// دالة احتياطية لنسخ النص
function fallbackCopyText(text, button) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    document.body.appendChild(textArea);
    textArea.select();
    
    try {
        document.execCommand('copy');
        updateCopyButton(button, true);
    } catch (err) {
        console.error('فشل في نسخ النص:', err);
        showToast('حدث خطأ أثناء محاولة نسخ النص', 'error');
    } finally {
        document.body.removeChild(textArea);
    }
}

// دالة لتحديث زر النسخ
function updateCopyButton(button, success) {
    const icon = button.querySelector('i');
    if (success) {
        icon.className = 'fas fa-check';
        button.classList.add('copied');
        setTimeout(() => {
            icon.className = 'fas fa-copy';
            button.classList.remove('copied');
        }, 2000);
    }
}

// دالة لإظهار مؤشر الكتابة
function showTypingIndicator() {
    const messagesContainer = document.getElementById('chatMessages');
    const typingDiv = document.createElement('div');
    typingDiv.className = 'typing-indicator';
    typingDiv.innerHTML = `
        <div class="typing-dot"></div>
        <div class="typing-dot"></div>
        <div class="typing-dot"></div>
    `;
    messagesContainer.appendChild(typingDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    return typingDiv;
}

// تحديث معالج النموذج
$(document).ready(function() {
    $('#questionForm').on('submit', async function(e) {
        e.preventDefault();
        
        const question = $('#question').val();
        const notes = $('#notes').val();
        
        if (!question) {
            showToast('الرجاء إدخال السؤال');
            return;
        }

        // إضافة رسالة المستخدم
        addMessage(question, 'user', notes);
        
        // إظهار مؤشر الكتابة
        const typingIndicator = showTypingIndicator();

        try {
            const preparedQuestion = prepareQuestion(question);
            const answer = await getGeminiResponse(preparedQuestion);
            
            // إزالة مؤشر الكتابة
            typingIndicator.remove();
            
            // إضافة رد المساعد
            addMessage(answer, 'ai');
            
            // حفظ في قاعدة البيانات
            await saveToDatabase(question, notes, answer, 'gemini');
            
            // مسح حقول الإدخال
            $('#question').val('');
            $('#notes').val('');
            
        } catch (error) {
            console.error('خطأ:', error);
            typingIndicator.remove();
            showToast(error.message, 'error');
        }
    });
});

// دالة لحفظ البيانات في قاعدة البيانات
async function saveToDatabase(question, notes, answer, model) {
    try {
        const response = await fetch('api/process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                question: question,
                notes: notes,
                answer: answer,
                model: model
            })
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'خطأ في حفظ البيانات');
        }
    } catch (error) {
        console.error('خطأ في حفظ البيانات:', error);
        throw error;
    }
}

// دالة لنسخ الكلمة الدلالية
function copyKeyword(element) {
    const keywordText = element.querySelector('.keyword-text').textContent;
    
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(keywordText).then(() => {
            updateKeywordTag(element, true);
        }).catch(() => {
            fallbackCopyKeyword(keywordText, element);
        });
    } else {
        fallbackCopyKeyword(keywordText, element);
    }
}

// دالة احتياطية لنسخ الكلمة الدلالية
function fallbackCopyKeyword(text, element) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    document.body.appendChild(textArea);
    textArea.select();
    
    try {
        document.execCommand('copy');
        updateKeywordTag(element, true);
    } catch (err) {
        console.error('فشل في نسخ الكلمة الدلالية:', err);
        showToast('حدث خطأ أثناء محاولة نسخ الكلمة الدلالية', 'error');
    } finally {
        document.body.removeChild(textArea);
    }
}

// دالة لتحديث حالة الكلمة الدلالية
function updateKeywordTag(element, success) {
    if (success) {
        const icon = element.querySelector('.copy-icon');
        element.classList.add('copied');
        icon.className = 'fas fa-check copy-icon';
        showToast('تم نسخ الكلمة الدلالية');
        
        setTimeout(() => {
            element.classList.remove('copied');
            icon.className = 'fas fa-copy copy-icon';
        }, 2000);
    }
}

// SVG icons as constants
const userIcon = `<svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
    <circle cx="18" cy="18" r="17" stroke="#4285f4" stroke-width="2">
        <animate attributeName="stroke-dasharray" from="0 100" to="100 100" dur="1s" />
    </circle>
    <path d="M18 10C16.35 10 15 11.35 15 13C15 14.65 16.35 16 18 16C19.65 16 21 14.65 21 13C21 11.35 19.65 10 18 10ZM18 18C15.75 18 11 19.125 11 21.375V23H25V21.375C25 19.125 20.25 18 18 18Z" fill="#4285f4">
        <animate attributeName="fill-opacity" values="0;1" dur="0.5s" />
    </path>
</svg>`;

const geminiIcon = `<svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M18 3L3 11V25L18 33L33 25V11L18 3Z" stroke="#34A853" stroke-width="2">
        <animate attributeName="stroke-dasharray" from="0 100" to="100 100" dur="1s" />
    </path>
    <path d="M18 9L10 13V23L18 27L26 23V13L18 9Z" fill="#34A853">
        <animate attributeName="fill-opacity" values="0;1" dur="0.5s" />
    </path>
</svg>`;

// دالة لجلب المحادثات السابقة
async function fetchHistory() {
    try {
        const response = await fetch('api/process.php?action=getHistory');
        const data = await response.json();
        
        if (data.success) {
            return data.history;
        } else {
            throw new Error(data.message || 'فشل في جلب المحادثات');
        }
    } catch (error) {
        console.error('خطأ في جلب المحادثات:', error);
        showToast('حدث خطأ في جلب المحادثات', 'error');
        return [];
    }
}

// دالة لحذف جميع المحادثات
async function clearHistory() {
    try {
        const response = await fetch('api/process.php?action=clearHistory', {
            method: 'POST'
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('تم حذف جميع المحادثات بنجاح');
            hideHistoryModal();
        } else {
            throw new Error(data.message || 'فشل في حذف المحادثات');
        }
    } catch (error) {
        console.error('خطأ في حذف المحادثات:', error);
        showToast('حدث خطأ في حذف المحادثات', 'error');
    }
}

// دالة لعرض نافذة المحادثات
async function showHistoryModal() {
    const history = await fetchHistory();
    
    const modal = document.createElement('div');
    modal.className = 'history-modal';
    modal.innerHTML = `
        <div class="history-content">
            <div class="history-header">
                <h3>المحادثات السابقة</h3>
                <button class="history-close" onclick="hideHistoryModal()">&times;</button>
            </div>
            <div class="history-list">
                ${history.map(item => `
                    <div class="history-item" onclick="loadConversation('${item.id}')">
                        <div class="history-item-header">
                            <div class="history-item-question">${item.question.substring(0, 100)}...</div>
                            <div class="history-item-date">${new Date(item.created_at).toLocaleDateString('ar-SA')}</div>
                        </div>
                        <div class="history-item-content">${item.answer.substring(0, 150)}...</div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.style.display = 'block';
}

// دالة لإخفاء نافذة المحادثات
function hideHistoryModal() {
    const modal = document.querySelector('.history-modal');
    if (modal) {
        modal.remove();
    }
}

// دالة لتحميل محادثة معينة
async function loadConversation(id) {
    try {
        const response = await fetch(`api/process.php?action=getConversation&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            // إضافة المحادثة للشات
            addMessage(data.conversation.question, 'user');
            addMessage(data.conversation.answer, 'ai');
            hideHistoryModal();
        } else {
            throw new Error(data.message || 'فشل في تحميل المحادثة');
        }
    } catch (error) {
        console.error('خطأ في تحميل المحادثة:', error);
        showToast('حدث خطأ في تحميل المحادثة', 'error');
    }
}

// إضافة معالجات الأحداث
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('showHistoryBtn').addEventListener('click', showHistoryModal);
    document.getElementById('clearHistoryBtn').addEventListener('click', async function() {
        if (confirm('هل أنت متأكد من حذف جميع المحادثات؟')) {
            await clearHistory();
        }
    });
}); 