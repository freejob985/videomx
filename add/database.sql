-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS courses_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE courses_db;

-- جدول اللغات
CREATE TABLE languages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الحالات
CREATE TABLE statuses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    language_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_status_lang (name, language_id),
    FOREIGN KEY (language_id) REFERENCES languages(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الأقسام
CREATE TABLE sections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    language_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_section_lang (name, language_id),
    FOREIGN KEY (language_id) REFERENCES languages(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الكورسات
CREATE TABLE courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    playlist_url VARCHAR(255) NOT NULL UNIQUE,
    language_id INT UNSIGNED NOT NULL,
    thumbnail VARCHAR(255),
    description TEXT,
    processing_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (language_id) REFERENCES languages(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الدروس
CREATE TABLE lessons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    video_url VARCHAR(255) NOT NULL UNIQUE,
    duration INT UNSIGNED DEFAULT 0,
    course_id INT UNSIGNED NOT NULL,
    status_id INT UNSIGNED,
    thumbnail VARCHAR(255),
    tags JSON DEFAULT ('[]'),
    transcript TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (status_id) REFERENCES statuses(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول العلاقة بين الكورسات والأقسام
CREATE TABLE course_sections (
    course_id INT UNSIGNED NOT NULL,
    section_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (course_id, section_id),
    FOREIGN KEY (course_id) REFERENCES courses(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Triggers للتحقق من صحة البيانات
DELIMITER //

-- التحقق من صحة رابط قائمة التشغيل
CREATE TRIGGER before_course_insert 
BEFORE INSERT ON courses
FOR EACH ROW
BEGIN
    IF NEW.playlist_url NOT REGEXP '^https?://[^/]+/.*$' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid playlist URL format';
    END IF;
END//

-- التحقق من صحة رابط الفيديو
CREATE TRIGGER before_lesson_insert
BEFORE INSERT ON lessons
FOR EACH ROW
BEGIN
    IF NEW.video_url NOT REGEXP '^https?://[^/]+/.*$' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid video URL format';
    END IF;
END//

-- تحديث وقت التعديل تلقائياً
CREATE TRIGGER before_course_update
BEFORE UPDATE ON courses
FOR EACH ROW
SET NEW.updated_at = CURRENT_TIMESTAMP//

CREATE TRIGGER before_lesson_update
BEFORE UPDATE ON lessons
FOR EACH ROW
SET NEW.updated_at = CURRENT_TIMESTAMP//

DELIMITER ;

-- إضافة البيانات الافتراضية
INSERT INTO languages (name) VALUES 
-- لغات الويب الأمامية
('HTML'), ('CSS'), ('JavaScript'), ('TypeScript'), ('React'), ('Vue.js'), ('Angular'),
('Svelte'), ('Next.js'), ('Nuxt.js'), ('Bootstrap'), ('Tailwind CSS'),

-- لغات الويب الخلفية
('PHP'), ('Python'), ('Ruby'), ('Java'), ('Node.js'), ('SQL'), ('MongoDB'),
('Laravel'), ('Django'), ('Express.js'), ('Spring Boot'),

-- لغات الموبايل
('Swift'), ('Kotlin'), ('Flutter'), ('React Native'), ('Ionic'),
('Android Development'), ('iOS Development'),

-- لغات عامة وأدوات
('C++'), ('C#'), ('Go'), ('Rust'), ('Scala'), ('Git'), ('Docker'),
('AWS'), ('Azure'), ('Linux'), ('DevOps'), ('Cyber Security')

ON DUPLICATE KEY UPDATE name = VALUES(name);

-- إضافة الحالات الافتراضية
INSERT INTO statuses (name, language_id) 
SELECT 'جديد', id FROM languages
UNION ALL
SELECT 'قيد المعالجة', id FROM languages
UNION ALL
SELECT 'مكتمل', id FROM languages
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- إضافة القسم العام لكل لغة
INSERT INTO sections (name, language_id)
SELECT 'عام', id FROM languages
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- إنشاء مستخدم وصلاحيات
CREATE USER IF NOT EXISTS 'courses_user'@'localhost' IDENTIFIED BY 'your_password_here';
GRANT ALL PRIVILEGES ON courses_db.* TO 'courses_user'@'localhost';
FLUSH PRIVILEGES; 