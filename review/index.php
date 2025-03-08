<?php
// التأكد من اتصال قاعدة البيانات
require_once 'config/database.php';

/**
 * جلب الكورسات التي تحتوي على دروس مراجعة
 * @return array الكورسات المراجعة
 */
function getReviewedCourses() {
    global $conn;
    
    $sql = "SELECT DISTINCT c.*, 
            (SELECT COUNT(*) FROM lessons WHERE course_id = c.id AND is_reviewed = 1) as reviewed_lessons_count,
            (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons_count
            FROM courses c
            INNER JOIN lessons l ON c.id = l.course_id
            WHERE l.is_reviewed = 1
            ORDER BY c.title";
            
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

$courses = getReviewedCourses();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الكورسات المراجعة</title>
    
    <!-- المكتبات المطلوبة -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">الكورسات المراجعة</h2>
        
        <div class="row">
            <?php foreach ($courses as $course): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <?php if ($course['thumbnail']): ?>
                            <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($course['title']); ?>">
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                            <p class="card-text">
                                <?php echo nl2br(htmlspecialchars(substr($course['description'], 0, 150)) . '...'); ?>
                            </p>
                            
                            <!-- بروجرس بار للدروس المراجعة -->
                            <div class="progress mb-3" style="height: 20px;">
                                <?php 
                                $percentage = ($course['reviewed_lessons_count'] / $course['total_lessons_count']) * 100;
                                ?>
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?php echo $percentage; ?>%"
                                     aria-valuenow="<?php echo $percentage; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    <?php echo round($percentage); ?>%
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-primary">
                                    <?php echo $course['reviewed_lessons_count']; ?> من <?php echo $course['total_lessons_count']; ?> درس
                                </span>
                                <a href="lessons.php?course_id=<?php echo $course['id']; ?>" 
                                   class="btn btn-primary">
                                    عرض الدروس
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- المكتبات JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.js"></script>
</body>
</html> 