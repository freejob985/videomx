<?php
/**
 * دالة لتحديد أيقونة اللغة المناسبة
 */
function getLanguageIcon($languageName) {
    $icons = [
        'PHP' => 'fab fa-php',
        'JavaScript' => 'fab fa-js',
        'Python' => 'fab fa-python',
        'Java' => 'fab fa-java',
        'HTML' => 'fab fa-html5',
        'CSS' => 'fab fa-css3-alt',
        'React' => 'fab fa-react',
        'Angular' => 'fab fa-angular',
        'Vue.js' => 'fab fa-vuejs',
        'Node.js' => 'fab fa-node-js',
    ];
    
    return $icons[$languageName] ?? 'fas fa-code';
}

/**
 * الحصول على المسار الأساسي للموقع
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    return rtrim($protocol . $host . $path, '/') . '/';
}
?> 