<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // استلام المعايير
    $filters = [
        'course_id' => $_POST['course_id'] ?? null,
        'section' => $_POST['section'] ?? '',
        'status' => $_POST['status'] ?? '',
        'duration' => $_POST['duration'] ?? '',
        'search' => $_POST['search'] ?? '',
        'important' => $_POST['important'] ?? false,
        'theory' => $_POST['theory'] ?? false,
        'hideTheory' => $_POST['hideTheory'] ?? false,
        'page' => $_POST['page'] ?? 1,
        'perPage' => 12
    ];

    if (!$filters['course_id']) {
        throw new Exception('معرف الكورس مطلوب');
    }

    // جلب الدروس المفلترة
    $lessons = getFilteredLessons($filters);
    
    // حساب الصفحات
    $totalLessons = count($lessons);
    $totalPages = ceil($totalLessons / $filters['perPage']);
    
    // تقسيم النتائج حسب الصفحة
    $offset = ($filters['page'] - 1) * $filters['perPage'];
    $lessons = array_slice($lessons, $offset, $filters['perPage']);

    // إنشاء HTML للترقيم
    $pagination = generatePaginationHtml($totalPages, $filters['page']);

    echo json_encode([
        'success' => true,
        'lessons' => $lessons,
        'pagination' => $pagination,
        'total' => $totalLessons,
        'currentPage' => $filters['page'],
        'totalPages' => $totalPages
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * دالة إنشاء HTML للترقيم
 */
function generatePaginationHtml($totalPages, $currentPage) {
    if ($totalPages <= 1) {
        return '';
    }

    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // زر السابق
    $html .= '<li class="page-item ' . ($currentPage <= 1 ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="#" data-page="' . ($currentPage - 1) . '">السابق</a></li>';
    
    // الأرقام
    for ($i = 1; $i <= $totalPages; $i++) {
        $html .= '<li class="page-item ' . ($i == $currentPage ? 'active' : '') . '">';
        $html .= '<a class="page-link" href="#" data-page="' . $i . '">' . $i . '</a></li>';
    }
    
    // زر التالي
    $html .= '<li class="page-item ' . ($currentPage >= $totalPages ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="#" data-page="' . ($currentPage + 1) . '">التالي</a></li>';
    
    $html .= '</ul></nav>';
    
    return $html;
} 