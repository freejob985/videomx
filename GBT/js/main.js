// ===== استيراد المكتبات والوحدات =====
import { showToast } from './toast.js';
import { showConfirmation } from './swal-config.js';

// ===== المتغيرات العامة =====
const chatMessages = document.getElementById('chatMessages');
const questionForm = document.getElementById('questionForm');
const questionInput = document.getElementById('question');
const clearHistoryBtn = document.getElementById('clearHistoryBtn');
const showHistoryBtn = document.getElementById('showHistoryBtn');

// ===== الأحداث الرئيسية =====
document.addEventListener('DOMContentLoaded', () => {
    loadChatHistory();
    initializeEventListeners();
});

// ===== تهيئة مستمعي الأحداث =====
function initializeEventListeners() {
    questionForm.addEventListener('submit', handleQuestionSubmit);
    clearHistoryBtn.addEventListener('click', handleClearHistory);
    showHistoryBtn.addEventListener('click', handleShowHistory);
}

// ===== معالجة إرسال السؤال =====
async function handleQuestionSubmit(e) {
    e.preventDefault();
    const question = questionInput.value.trim();
    
    if (!question) {
        showToast('warning', 'الرجاء كتابة سؤال');
        return;
    }

    try {
        // إضافة السؤال إلى المحادثة
        addMessageToChat('user', question);
        questionInput.value = '';

        // محاكاة استجابة المساعد
        const response = await simulateAssistantResponse(question);
        addMessageToChat('assistant', response);
        
        // حفظ المحادثة
        saveChatHistory();
        
        showToast('success', 'تم إرسال السؤال بنجاح');
    } catch (error) {
        showToast('error', 'حدث خطأ أثناء معالجة السؤال');
        console.error('Error:', error);
    }
}

// ===== معالجة مسح المحادثات =====
async function handleClearHistory() {
    const result = await showConfirmation({
        title: 'تأكيد المسح',
        text: 'هل أنت متأكد من رغبتك في مسح جميع المحادثات؟',
        icon: 'warning'
    });

    if (result.isConfirmed) {
        localStorage.removeItem('chatHistory');
        chatMessages.innerHTML = '';
        showToast('success', 'تم مسح المحادثات بنجاح');
    }
}

// ===== معالجة عرض المحادثات =====
function handleShowHistory() {
    const history = loadChatHistory();
    if (history && history.length > 0) {
        showToast('info', 'تم تحميل المحادثات السابقة');
    } else {
        showToast('info', 'لا توجد محادثات سابقة');
    }
}

// ===== إضافة رسالة إلى المحادثة =====
function addMessageToChat(sender, content) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${sender}-message`;
    messageDiv.innerHTML = `
        <div class="message-content">
            <div class="message-text">${content}</div>
            <div class="message-time">${new Date().toLocaleTimeString()}</div>
        </div>
    `;
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// ===== محاكاة استجابة المساعد =====
function simulateAssistantResponse(question) {
    return new Promise(resolve => {
        setTimeout(() => {
            resolve(`هذه استجابة تجريبية للسؤال: "${question}"`);
        }, 1000);
    });
}

// ===== حفظ وتحميل المحادثات =====
function saveChatHistory() {
    const history = chatMessages.innerHTML;
    localStorage.setItem('chatHistory', history);
}

function loadChatHistory() {
    const history = localStorage.getItem('chatHistory');
    if (history) {
        chatMessages.innerHTML = history;
    }
    return history;
} 