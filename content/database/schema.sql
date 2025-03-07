-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS courses_db CHARACTER SET utf8 COLLATE utf8_general_ci;
USE courses_db;

-- جدول اللغات
CREATE TABLE IF NOT EXISTS languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- جدول الحالات
CREATE TABLE IF NOT EXISTS statuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    language_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    color VARCHAR(7) DEFAULT '#ffd700',
    text_color VARCHAR(7) DEFAULT '#000000',
    FOREIGN KEY (language_id) REFERENCES languages(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- جدول الكورسات
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    playlist_url TEXT,
    language_id INT,
    thumbnail VARCHAR(255),
    description TEXT,
    processing_status TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (language_id) REFERENCES languages(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- جدول الدروس
CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    video_url TEXT,
    duration INT DEFAULT 0,
    course_id INT,
    status_id INT,
    thumbnail VARCHAR(255),
    tags TEXT,
    transcript TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id),
    FOREIGN KEY (status_id) REFERENCES statuses(id),
    is_important BOOLEAN DEFAULT FALSE,
    is_theory BOOLEAN DEFAULT FALSE,
    completed TINYINT(1) DEFAULT 0,
    section_id INT DEFAULT NULL,
    FOREIGN KEY (section_id) REFERENCES sections(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- جدول الملاحظات
CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    type ENUM('text', 'code', 'link') NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    code_language VARCHAR(50),
    link_url TEXT,
    link_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- التأكد من وجود جدول الأقسام
CREATE TABLE IF NOT EXISTS sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    language_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- إضافة بعض البيانات للاختبار إذا كان الجدول فارغاً
INSERT INTO languages (name) 
SELECT * FROM (
    SELECT 'PHP' AS name UNION ALL
    SELECT 'JavaScript' UNION ALL
    SELECT 'Python' UNION ALL
    SELECT 'Java'
) AS temp
WHERE NOT EXISTS (
    SELECT 1 FROM languages
) LIMIT 1; 