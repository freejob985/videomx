<?php
require_once '../includes/functions.php';

$lesson_id = $_GET['lesson_id'] ?? null;

if (!$lesson_id) {
    http_response_code(400);
    exit('معرف الدرس مطلوب');
}

try {
    $transcript = getLessonTranscript($lesson_id);
    
    if (!$transcript) {
        http_response_code(404);
        exit('لم يتم العثور على نص الدرس');
    }
    
    // تنسيق النص
    echo '<div class="transcript-content">';
    echo nl2br(htmlspecialchars($transcript));
    echo '</div>';
    
} catch (Exception $e) {
    http_response_code(500);
    exit('حدث خطأ أثناء جلب نص الدرس');
} 