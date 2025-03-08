<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'عرض الدروس'; ?></title>
    
    <!-- خطوط جوجل -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Changa:wght@200..800&display=swap" rel="stylesheet">
    
    <!-- المكتبات CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.css">
    
    <!-- ملفات CSS المخصصة -->
    <link href="../assets/css/lessons.css" rel="stylesheet">
    <link href="../assets/css/cards.css" rel="stylesheet">
    
    <!-- تحسين مظهر Tags Input -->
    <style>
        .bootstrap-tagsinput {
            width: 100%;
            padding: 0.5rem;
            border-radius: 8px;
            border: 1px solid #ced4da;
            background-color: #fff;
        }
        
        .bootstrap-tagsinput .tag {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            margin: 0.25rem;
            border-radius: 4px;
            background-color: #2196f3;
            color: #fff !important;
            font-size: 0.875rem;
        }
        
        .bootstrap-tagsinput input {
            border: none;
            box-shadow: none;
            outline: none;
            background-color: transparent;
            padding: 0;
            margin: 0;
            width: auto;
            max-width: inherit;
        }

        .lessons-header {
    transition: max-height 0.3s ease-out;
    overflow: hidden;
    max-height: 2000px;
    background: #22237c !important;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
    padding: 20px;
}
    </style>

    <!-- المكتبات JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- إضافة Bootstrap Tags Input بعد jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.11.1/typeahead.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.min.js"></script>
</head>
<body class="lessons-page">
    <!-- هيدر الصفحة -->
    <header class="lessons-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item">
                                <a href="../index.php">
                                    <i class="fas fa-home"></i>
                                </a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="../courses.php?language_id=<?php echo $course['language_id']; ?>">
                                    الدورات
                                </a>
                            </li>
                            <li class="breadcrumb-item active">
                                <?php echo htmlspecialchars($course['title']); ?>
                            </li>
                        </ol>
                    </nav>
                    <h1 class="course-title">
                        <?php echo htmlspecialchars($course['title']); ?>
                    </h1>
                    <?php if ($course['description']): ?>
                        <p class="course-description">
                            <?php echo htmlspecialchars($course['description']); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="col-lg-4">
                    <div class="course-meta">
                        <div class="meta-item">
                            <i class="fas fa-book"></i>
                            <span><?php echo count($lessons); ?> درس</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <span><?php echo formatDuration(array_sum(array_column($lessons, 'duration'))); ?></span>
                        </div>
                        <?php if ($course['playlist_url']): ?>
                            <a href="<?php echo htmlspecialchars($course['playlist_url']); ?>" 
                               class="btn btn-outline-light" 
                               target="_blank">
                                <i class="fab fa-youtube me-2"></i>
                                قائمة التشغيل
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <a href="<?php echo buildUrl('lessons.php?course_id=' . $course_id); ?>" class="btn btn-primary">
    عرض الدروس
</a> 
        </div>
    </header>
</body>
</html> 