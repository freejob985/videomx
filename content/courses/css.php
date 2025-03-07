/* تنسيق شريط التنقل العلوي */
.navigation-bar {
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.navigation-bar .btn {
    min-width: 120px;
    transition: all 0.3s ease;
}

.navigation-bar .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* تنسيق عنوان الصفحة */
.navigation-bar h4 {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 600px;
    margin: 0 auto;
}

/* تحسين المظهر على الشاشات الصغيرة */
@media (max-width: 768px) {
    .navigation-bar .row {
        flex-direction: column;
        gap: 1rem;
    }
    
    .navigation-bar .col-auto {
        width: 100%;
    }
    
    .navigation-bar .d-flex {
        justify-content: center;
    }
    
    .navigation-bar h4 {
        font-size: 1.1rem;
        margin: 10px 0;
    }
} 