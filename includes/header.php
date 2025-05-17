<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام الإدارة</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/4.0.0/font/MaterialIcons-Regular.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    
    <!-- إضافة SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
    
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="min-h-screen">
    <!-- Header Navigation -->
    <header class="bg-gradient-to-r from-blue-600 to-blue-800 text-white shadow-lg">
        <nav class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="text-xl font-bold">نظام الإدارة</div>
                <ul class="flex space-x-6 space-x-reverse">
                    <!-- الرئيسية -->
                    <li><a href="/" class="hover:text-blue-200 flex items-center">
                        <i class="fas fa-home ml-1"></i>
                        الرئيسية
                    </a></li>
                    
                    <!-- قائمة الدروس -->
                    <li class="relative group">
                        <a href="#" class="hover:text-blue-200 flex items-center">
                            <i class="fas fa-book-open ml-1"></i>
                            الدروس
                            <i class="fas fa-chevron-down mr-1 text-sm"></i>
                        </a>
                        <ul class="absolute hidden group-hover:block bg-blue-800 rounded-md shadow-lg py-2 mt-1 min-w-[200px]">
                            <li>
                                <a href="/views/lessons-cards.php" class="block px-4 py-2 hover:bg-blue-700">
                                    <i class="fas fa-th-large ml-2"></i>
                                    عرض البطاقات
                                </a>
                            </li>
                            <li>
                                <a href="/lessons.php" class="block px-4 py-2 hover:bg-blue-700">
                                    <i class="fas fa-list ml-2"></i>
                                    عرض القائمة
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- قائمة الإدارة -->
                    <li class="relative group">
                        <a href="#" class="hover:text-blue-200 flex items-center">
                            <i class="fas fa-cog ml-1"></i>
                            الإدارة
                            <i class="fas fa-chevron-down mr-1 text-sm"></i>
                        </a>
                        <ul class="absolute hidden group-hover:block bg-blue-800 rounded-md shadow-lg py-2 mt-1 min-w-[200px]">
                            <!-- <li>
                                <a href="/modules/department/add.php" class="block px-4 py-2 hover:bg-blue-700">
                                    <i class="fas fa-plus-circle ml-2"></i>
                                    إضافة قسم
                                </a>
                            </li>
                            <li>
                                <a href="/modules/status/add.php" class="block px-4 py-2 hover:bg-blue-700">
                                    <i class="fas fa-tasks ml-2"></i>
                                    إضافة حالة
                                </a>
                            </li> -->
                            <li>
                                <a href="http://videomx.com/content/languages.php" class="block px-4 py-2 hover:bg-blue-700">
                                    <i class="fas fa-globe ml-2"></i>
                                    اللغات
                                </a>
                            </li>
                            <li>
                                <a href="http://videomx.com/content/index.php" class="block px-4 py-2 hover:bg-blue-700">
                                    <i class="fas fa-graduation-cap ml-2"></i>
                                    الدورات
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- المساعد الذكي -->
                    <li>
                        <a href="http://videomx.com/GBT/" class="hover:text-blue-200 flex items-center">
                            <i class="fas fa-robot ml-1"></i>
                            المساعد الذكي
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    <main class="container mx-auto px-4 py-8"> 