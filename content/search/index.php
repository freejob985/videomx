<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة الدروس</title>
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism-okaidia.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/plugins/line-numbers/prism-line-numbers.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.css" rel="stylesheet">

    <!-- Add favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,
        %3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E
            %3Cdefs%3E
                %3ClinearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E
                    %3Cstop offset='0%25' style='stop-color:%230d6efd;stop-opacity:1' /%3E
                    %3Cstop offset='100%25' style='stop-color:%230a58ca;stop-opacity:1' /%3E
                %3C/linearGradient%3E
            %3C/defs%3E
            %3Crect width='512' height='512' rx='50' fill='url(%23grad)'/%3E
            %3Cpath d='M200 150v212c0 5.523 4.477 10 10 10h92c5.523 0 10-4.477 10-10V150c0-5.523-4.477-10-10-10h-92c-5.523 0-10 4.477-10 10z' fill='white' fill-opacity='0.9'/%3E
            %3Cpath d='M246 180c-8.837 0-16 7.163-16 16v120c0 8.837 7.163 16 16 16s16-7.163 16-16V196c0-8.837-7.163-16-16-16z' fill='white' fill-opacity='0.9'/%3E
        %3C/svg%3E">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
        .card {
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        /* Custom styles for bootstrap-tagsinput */
        .bootstrap-tagsinput {
            width: 100%;
            min-height: 45px;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            border-radius: 0.375rem;
        }
        
        .bootstrap-tagsinput .tag {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            margin: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            line-height: 1.5;
            color: #fff !important;
            background-color: #0d6efd;
            border-radius: 0.25rem;
        }
        
        .bootstrap-tagsinput input {
            min-width: 150px;
        }
        
        .bootstrap-tagsinput .tag [data-role="remove"] {
            margin-left: 8px;
            cursor: pointer;
        }
        
        .bootstrap-tagsinput .tag [data-role="remove"]:after {
            content: "×";
            padding: 0 2px;
        }

        /* Material Design Card Styles */
        .lesson-card {
            transition: all 0.3s cubic-bezier(.25,.8,.25,1);
            position: relative;
            overflow: hidden;
            border: none !important;
        }
        
        .lesson-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 17px rgba(0,0,0,0.1), 0 6px 6px rgba(0,0,0,0.07) !important;
        }
        
        .lesson-card .card-img-wrapper {
            position: relative;
            overflow: hidden;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
        }
        
        .lesson-card .card-img-top {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .lesson-card:hover .card-img-top {
            transform: scale(1.1);
        }
        
        .lesson-card .play-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .lesson-card:hover .play-overlay {
            opacity: 1;
        }
        
        .lesson-card .play-button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 6px rgba(0,0,0,0.16);
            transform: scale(0.8);
            transition: all 0.3s ease;
        }
        
        .lesson-card:hover .play-button {
            transform: scale(1);
        }
        
        .lesson-card .play-button i {
            color: #dc3545;
            font-size: 24px;
            margin-left: 4px;
        }
        
        /* Card Header Gradients */
        .lesson-card .card-header {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: #fff;
            padding: 1rem;
            border: none;
        }
        
        .lesson-card.theory .card-header {
            background: linear-gradient(135deg, #0dcaf0, #0b96b4);
        }
        
        .lesson-card.important .card-header {
            background: linear-gradient(135deg, #dc3545, #b02a37);
        }
        
        .lesson-card.completed .card-header {
            background: linear-gradient(135deg, #198754, #146c43);
        }
        
        .lesson-card .card-header h5 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        /* Tags Styling */
        .lesson-card .tags-container {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        
        .lesson-card .tag {
            color: #fff !important;
            background: linear-gradient(135deg, #6c757d, #495057);
            border: none;
            margin: 0.25rem;
            padding: 0.35rem 0.75rem;
            border-radius: 50rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .lesson-card .tag i {
            margin-right: 0.5rem;
            font-size: 0.75rem;
            opacity: 0.9;
        }
        
        .lesson-card .tag:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
        }
        
        /* Status Badges */
        .lesson-card .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50rem;
            font-size: 0.875rem;
            margin: 0.5rem;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            color: #fff !important;
        }
        
        .lesson-card .status-badge i {
            margin-right: 0.5rem;
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        .lesson-card .status-badge.important {
            background: linear-gradient(135deg, #dc3545, #b02a37);
            border: none;
        }
        
        .lesson-card .status-badge.theory {
            background: linear-gradient(135deg, #0dcaf0, #0b96b4);
            border: none;
        }
        
        .lesson-card .status-badge.completed {
            background: linear-gradient(135deg, #198754, #146c43);
            border: none;
        }
        
        /* Footer Styling */
        .lesson-card .card-footer {
            background: transparent;
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 1rem;
        }
        
        .lesson-card .card-footer .btn {
            padding: 0.5rem 1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .lesson-card .card-footer .btn i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }
        
        .lesson-card .meta-info {
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .lesson-card .meta-info i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
            opacity: 0.7;
        }
        
        .lesson-card .meta-info span {
            margin-right: 1rem;
            display: inline-flex;
            align-items: center;
        }

        /* Add to existing styles */
        .table th {
            font-weight: 600;
            text-align: center;
        }
        
        .table td {
            text-align: center;
            vertical-align: middle;
        }
        
        .table .lesson-thumbnail {
            width: 60px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .table .lesson-title {
            text-align: right;
            font-weight: 500;
            color: #0d6efd;
        }
        
        .table .lesson-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            justify-content: center;
        }
        
        .table .lesson-tags .tag {
            padding: 2px 8px;
            font-size: 0.75rem;
        }
        
        .table .lesson-status {
            display: flex;
            flex-direction: column;
            gap: 4px;
            align-items: center;
        }
        
        .table .lesson-status .badge {
            width: 100%;
            white-space: nowrap;
        }
        
        .table .actions-cell {
            display: flex;
            gap: 4px;
            justify-content: center;
        }
        
        .table .btn-action {
            padding: 4px 8px;
            font-size: 0.875rem;
        }
        
        /* View toggle buttons */
        .btn-group .btn.active {
            background-color: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        /* Add to existing styles */
        .dataTables_wrapper {
            padding: 1rem;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 0.5rem;
        }
        
        .table {
            margin-bottom: 0 !important;
        }
        
        .table thead th {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
            transform: scale(1.01);
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .dataTables_info,
        .dataTables_length,
        .dataTables_filter {
            padding: 1rem;
            color: #6c757d;
        }
        
        .page-item.active .page-link {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            border-color: #0a58ca;
        }

        /* Custom Table Styles */
        .lessons-table {
            background: linear-gradient(135deg, #fff, #f8f9fa);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }

        .lessons-table thead {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            position: relative;
            width: 100%;
            display: table-header-group;
        }

        .lessons-table thead::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: -5px;
            height: 5px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.1), transparent);
        }

        .lessons-table thead th {
            color: #fff !important;
            font-weight: 600;
            text-transform: uppercase;
            padding: 1.2rem 1rem;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            border: none !important;
            text-align: center;
            white-space: nowrap;
            position: relative;
            background: transparent !important;
        }

        .lessons-table thead th:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 20%;
            height: 60%;
            width: 1px;
            background: rgba(255,255,255,0.2);
        }

        .lessons-table tbody tr {
            transition: all 0.3s ease;
            background: #fff;
        }

        .lessons-table tbody tr:hover {
            background: rgba(13, 110, 253, 0.03);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .lessons-table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .lessons-table .lesson-title {
            font-weight: 600;
            color: #2c3e50;
            text-decoration: none;
            display: block;
            transition: color 0.3s ease;
        }

        .lessons-table .lesson-title:hover {
            color: #0d6efd;
        }

        .lessons-table .lesson-thumbnail {
            width: 80px;
            height: 45px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .lessons-table .lesson-thumbnail:hover {
            transform: scale(1.1);
        }

        .lessons-table .lesson-meta {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .lessons-table .lesson-meta i {
            width: 20px;
            text-align: center;
            margin-right: 5px;
            opacity: 0.7;
        }

        .lessons-table .lesson-status {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            align-items: center;
        }

        .lessons-table .lesson-status .badge {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            font-weight: 500;
            border-radius: 50rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .lessons-table .lesson-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
            justify-content: center;
        }

        .lessons-table .lesson-tags .tag {
            padding: 0.3rem 0.8rem;
            font-size: 0.8rem;
            border-radius: 50rem;
            background: linear-gradient(135deg, #6c757d, #495057);
            color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .lessons-table .lesson-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        .lessons-table .btn-action {
            width: 35px;
            height: 35px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .lessons-table .btn-action:hover {
            transform: translateY(-2px);
        }

        /* Table Search and Length */
        .table-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .table-search {
            position: relative;
            max-width: 300px;
        }

        .table-search input {
            padding-right: 2.5rem;
            border-radius: 50rem;
        }

        .table-search i {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        /* Add to existing styles */
        .site-footer {
            background: #0d6efd;
            color: #fff;
            margin-top: auto;
        }

        .footer-gradient {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
        }

        .footer-links a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .footer-links a:hover {
            color: #fff;
            transform: translateX(-5px);
        }

        /* Languages list styles */
        .languages-list-wrapper {
            position: relative;
            overflow: hidden;
        }

        .languages-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .languages-list li {
            margin-bottom: 10px;
        }

        .languages-list a {
            background: rgba(255,255,255,0.1);
            padding: 12px 15px;
            border-radius: 8px;
            width: 100%;
            display: block;
            transition: all 0.3s ease;
        }

        .languages-list a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(-5px);
        }

        .languages-list .badge {
            font-size: 0.85rem;
            padding: 0.5em 0.8em;
            background: rgba(255,255,255,0.2) !important;
            color: #fff !important;
        }

        .languages-list i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
            color: rgba(255,255,255,0.7);
        }

        .languages-list .language-name {
            font-size: 1rem;
            font-weight: 500;
            color: #fff;
        }

        .btn-scroll {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.1);
            border: none;
            border-radius: 50%;
            color: #fff;
            transition: all 0.3s ease;
        }

        .btn-scroll:not(:disabled):hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.1);
        }

        .btn-scroll:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* تحديث أنماط الترقيم */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .pagination .page-item {
            margin: 0;
        }

        .pagination .page-link {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 15px;
            border-radius: 20px !important;
            font-weight: 500;
            border: none;
            background: #f8f9fa;
            color: #0d6efd;
            transition: all 0.3s ease;
        }

        .pagination .page-item:not(.disabled) .page-link:hover {
            background: #0d6efd;
            color: #fff;
            transform: translateY(-2px);
        }

        .pagination .page-item.disabled .page-link {
            background: #e9ecef;
            color: #6c757d;
        }

        .pagination .page-item.active .page-link {
            background: #0d6efd;
            color: #fff;
        }

        /* تنسيق أيقونات السابق والتالي */
        .pagination .fas {
            font-size: 0.8rem;
        }

        /* تنسيق قسم اللغات في الفوتر */
        .languages-grid {
            position: relative;
        }

        .languages-grid .language-card {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .languages-grid .language-card:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-5px);
        }

        .languages-grid .language-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .languages-grid .language-name {
            color: #fff;
            font-size: 1.1rem;
            font-weight: 500;
            margin: 0;
        }

        .languages-grid .language-icon {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
        }

        .languages-grid .language-icon i {
            font-size: 1.2rem;
            color: #fff;
        }

        .languages-grid .language-stats {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .languages-grid .stats-item {
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
        }

        .languages-grid .badge {
            background: rgba(255,255,255,0.2);
            color: #fff;
            border-radius: 20px;
            padding: 5px 12px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">نظام إدارة الدروس</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Filters Section -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="filterForm">
                    <div class="row">
                        <div class="col-md-3">
                            <select class="form-select" id="languageFilter">
                                <option value="">اختر اللغة</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="statusFilter">
                                <option value="">اختر الحالة</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="sectionFilter">
                                <option value="">اختر القسم</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="courseFilter">
                                <option value="">اختر الكورس</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="searchInput" placeholder="ابحث عن درس...">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">بحث</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add after filters section -->
        <div id="statsContainer" class="mb-4">
            <!-- Statistics will be loaded here -->
        </div>

        <!-- Update view toggle section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="btn-group" role="group" aria-label="طريقة العرض">
                <button type="button" class="btn btn-outline-primary active" data-view="grid">
                    <i class="fas fa-th-large"></i> بطاقات
                </button>
                <button type="button" class="btn btn-outline-primary" data-view="table">
                    <i class="fas fa-table"></i> جدول
                </button>
            </div>
            <div class="text-muted">
                الصفحة <span id="currentPageNum">1</span> من <span id="totalPagesNum">1</span>
            </div>
        </div>

        <!-- Add table view container -->
        <div id="tableViewContainer" class="d-none">
            <div class="table-controls">
                <div class="table-search">
                    <input type="text" class="form-control" id="tableSearch" placeholder="بحث في الجدول...">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            <div class="lessons-table">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الصورة</th>
                                <th>العنوان</th>
                                <th>القسم</th>
                                <th>المدة</th>
                                <th>الحالة</th>
                                <th>الوسوم</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="lessonsTable">
                            <!-- Table content will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add grid view container -->
        <div id="gridViewContainer">
            <div class="row" id="lessonsGrid">
                <!-- Grid content will be loaded here -->
            </div>
        </div>

        <!-- Update pagination section -->
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center" id="pagination">
                <!-- Pagination will be loaded here -->
            </ul>
        </nav>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="assets/js/main.js"></script>

    <!-- Video Modal -->
    <div class="modal fade" id="videoModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="videoModalLabel"></h5>
                    <div class="ms-auto d-flex align-items-center">
                        <!-- Navigation Buttons -->
                        <div class="btn-group me-3" role="group">
                            <button type="button" class="btn btn-outline-primary" id="prevLessonBtn" disabled>
                                <i class="fas fa-chevron-right"></i> السابق
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="nextLessonBtn" disabled>
                                التالي <i class="fas fa-chevron-left"></i>
                            </button>
                        </div>
                        <!-- Other Buttons -->
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-success btn-sm" id="toggleCompletedBtn">
                                <i class="fas fa-check"></i> تغيير حالة الإكمال
                            </button>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Video Section -->
                        <div class="col-12 mb-4">
                            <div class="ratio ratio-16x9">
                                <iframe id="videoFrame" src="" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
                            </div>
                        </div>
                        
                        <!-- Lesson Details -->
                        <div class="col-12">
                            <ul class="nav nav-tabs" id="lessonTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="notes-tab" data-bs-toggle="tab" 
                                        data-bs-target="#notes" type="button" role="tab">
                                        <i class="fas fa-sticky-note"></i> الملاحظات
                                        <button class="btn btn-sm btn-success ms-2" id="addNoteBtn">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tags-tab" data-bs-toggle="tab" 
                                        data-bs-target="#tags" type="button" role="tab">
                                        <i class="fas fa-tags"></i> الوسوم
                                        <button class="btn btn-sm btn-success ms-2" id="editTagsBtn">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="transcript-tab" data-bs-toggle="tab" 
                                        data-bs-target="#transcript" type="button" role="tab">
                                        <i class="fas fa-file-alt"></i> النص
                                    </button>
                                </li>
                            </ul>
                            <div class="tab-content p-3 border border-top-0" id="lessonTabContent">
                                <!-- Notes Tab -->
                                <div class="tab-pane fade show active" id="notes" role="tabpanel">
                                    <div id="notesList"></div>
                                </div>
                                <!-- Tags Tab -->
                                <div class="tab-pane fade" id="tags" role="tabpanel">
                                    <div id="tagsList" class="d-flex flex-wrap gap-2"></div>
                                </div>
                                <!-- Transcript Tab -->
                                <div class="tab-pane fade" id="transcript" role="tabpanel">
                                    <div id="transcriptContent"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Note Modal -->
    <div class="modal fade" id="noteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة/تعديل ملاحظة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="noteForm">
                        <input type="hidden" id="noteId">
                        <div class="mb-3">
                            <label class="form-label">العنوان</label>
                            <input type="text" class="form-control" id="noteTitle" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">النوع</label>
                            <select class="form-select" id="noteType">
                                <option value="text">نص</option>
                                <option value="code">كود</option>
                                <option value="link">رابط</option>
                            </select>
                        </div>
                        <div class="mb-3" id="contentGroup">
                            <label class="form-label">المحتوى</label>
                            <textarea class="form-control" id="noteContent" rows="4" required></textarea>
                        </div>
                        <div class="mb-3 d-none" id="codeLangGroup">
                            <label class="form-label">لغة البرمجة</label>
                            <select class="form-select" id="codeLanguage">
                                <option value="php">PHP</option>
                                <option value="javascript">JavaScript</option>
                                <option value="css">CSS</option>
                                <option value="sql">SQL</option>
                            </select>
                        </div>
                        <div class="mb-3 d-none" id="linkGroup">
                            <label class="form-label">الرابط</label>
                            <input type="url" class="form-control" id="linkUrl">
                            <label class="form-label mt-2">وصف الرابط</label>
                            <input type="text" class="form-control" id="linkDescription">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" id="saveNoteBtn">حفظ</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Tags Modal -->
    <div class="modal fade" id="tagsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تعديل الوسوم</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الوسوم</label>
                        <input type="text" class="form-control" id="tagsInput" 
                               data-role="tagsinput" 
                               placeholder="اضغط Enter لإضافة وسم">
                        <small class="text-muted">اضغط Enter بعد كل وسم</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" id="saveTagsBtn">حفظ</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="site-footer mt-5">
        <div class="footer-gradient">
            <div class="container">
                <div class="row py-5">
                    <!-- القسم الأول: روابط إضافية -->
                    <div class="col-md-4 mb-4">
                        <h5 class="text-white mb-4">روابط إضافية</h5>
                        <ul class="list-unstyled footer-links">
                            <li class="mb-2">
                                <a href="http://videomx.com/content/languages.php">
                                    <i class="fas fa-globe me-2"></i>
                                    قائمة اللغات
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="http://videomx.com/content/index.php">
                                    <i class="fas fa-graduation-cap me-2"></i>
                                    الدورات
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="http://videomx.com/add/add.php" target="_blank">
                                    <i class="fas fa-cog me-2"></i>
                                    الإعدادات
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="http://videomx.com/GBT/" target="_blank" class="ai-link">
                                    <i class="fas fa-robot me-2"></i>
                                    المساعد الذكي
                                </a>
                            </li>
                            <li>
                                <a href="http://videomx.com/" target="_blank">
                                    <i class="fas fa-home me-2"></i>
                                    البوابة
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- القسم الثاني: اللغات المتاحة -->
                    <div class="col-md-8">
                        <h5 class="text-white mb-4">اللغات المتاحة</h5>
                        <div class="languages-grid">
                            <div class="row" id="languagesList">
                                <!-- Languages will be loaded here -->
                            </div>
                            <!-- Pagination -->
                            <nav aria-label="Languages pagination" class="mt-4">
                                <ul class="pagination justify-content-center" id="languagesPagination">
                                    <!-- Pagination will be loaded here -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- حقوق النشر -->
                <div class="footer-bottom py-3 text-center border-top border-light">
                    <p class="mb-0 text-white-50">
                        جميع الحقوق محفوظة &copy; <?php echo date('Y'); ?> 
                        <a href="index.php" class="text-white">VideoMX</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Add before closing body -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/plugins/line-numbers/prism-line-numbers.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-sql.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.min.js"></script>
</body>
</html> 