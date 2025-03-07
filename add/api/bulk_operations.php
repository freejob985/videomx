<?php
require_once '../config.php';
require_once '../helper_functions.php';

header('Content-Type: application/json');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method !== 'POST') {
        throw new Exception('طريقة الطلب غير مدعومة');
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'delete_all_lessons':
            // حذف جميع الدروس
            $db->beginTransaction();
            
            try {
                // حذف الدروس فقط بدون تحديث الكورسات
                $db->exec('DELETE FROM lessons');
                
                $db->commit();
                echo jsonResponse(true, 'تم حذف جميع الدروس بنجاح');
            } catch (Exception $e) {
                $db->rollBack();
                throw new Exception('فشل في حذف الدروس: ' . $e->getMessage());
            }
            break;

        case 'add_default_data':
            $db->beginTransaction();
            
            try {
                // إضافة الحالات الافتراضية
                $defaultStatuses = [
                    'تطوير', 'بحث', 'مكتمل', 'مشاريع', 'نظري',
                    'عملي', 'مراجعة', 'تحديث', 'أساسيات', 'متقدم'
                ];

                $stmt = $db->prepare('INSERT INTO statuses (name, language_id) VALUES (?, ?)');
                
                // إضافة الحالات لكل لغة
                $languages = $db->query('SELECT id, name FROM languages')->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($languages as $language) {
                    foreach ($defaultStatuses as $status) {
                        try {
                            $stmt->execute([$status, $language['id']]);
                        } catch (PDOException $e) {
                            // تجاهل الحالات المكررة
                            if ($e->getCode() != 23000) throw $e;
                        }
                    }
                }

                // إضافة القسم العام
                $stmt = $db->prepare('INSERT INTO sections (name, language_id) VALUES ("عام", ?)');
                foreach ($languages as $language) {
                    try {
                        $stmt->execute([$language['id']]);
                    } catch (PDOException $e) {
                        if ($e->getCode() != 23000) throw $e;
                    }
                }

                $defaultLanguages = [
                    // لغات الويب الأمامية
                    'CSS', 'JavaScript', 'TypeScript', 'React JS', 'Vue.js', 'Angular',
                    'Next.js', 'Nuxt.js', 'Bootstrap',
                    
                    // لغات الويب الخلفية
                    'PHP', 'Python',  'Node.js', 'SQL', 'MongoDB',
                    'Laravel', 'Django',  
                    
                    // لغات الموبايل
                    'Python AI', 'Flutter', 'React Native', 'Python AI',
         
                    
                    // لغات الشبكات
                 'Laravel Livewire', 'C++', 'Go',
                    'Git', 'Docker', 'AWS',
                    
                    // لغات البرمجة السريعة
                    'FastAPI', 'CodeIgniter', 'Django', 'JQ'
              
                ];

                $stmt = $db->prepare('INSERT INTO languages (name) VALUES (?)');
                foreach ($defaultLanguages as $lang) {
                    try {
                        // التحقق من عدم وجود اللغة مسبقا
                        $stmt_check = $db->prepare('SELECT id FROM languages WHERE name = ?');
                        $stmt_check->execute([$lang]);
                        if (!$stmt_check->fetch()) {
                            $stmt->execute([$lang]);
                        }
                    } catch (PDOException $e) {
                        if ($e->getCode() != 23000) throw $e;
                    }
                }

                $db->commit();
                echo jsonResponse(true, 'تم إضافة البيانات الافتراضية بنجاح');
            } catch (Exception $e) {
                $db->rollBack();
                throw new Exception('فشل في إضافة البيانات الافتراضية: ' . $e->getMessage());
            }
            break;

        case 'add_default_statuses':
            try {
                $defaultStatuses = [
                    'تطوير', 'بحث', 'مكتمل', 'مشاريع', 'نظري',
                    'عملي', 'مراجعة', 'تحديث', 'أساسيات', 'متقدم'
                ];

                $stmt = $db->prepare('INSERT INTO statuses (name, language_id) VALUES (?, ?)');
                $languages = $db->query('SELECT id FROM languages')->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($languages as $langId) {
                    foreach ($defaultStatuses as $status) {
                        try {
                            $stmt->execute([$status, $langId]);
                        } catch (PDOException $e) {
                            if ($e->getCode() != 23000) throw $e;
                        }
                    }
                }

                echo jsonResponse(true, 'تم إضافة الحالات الافتراضية بنجاح');
            } catch (Exception $e) {
                throw new Exception('فشل في إضافة الحالات: ' . $e->getMessage());
            }
            break;

        case 'add_default_languages':
            try {
                $defaultLanguages = [
                    // لغات الويب الأمامية
                    'CSS', 'JavaScript', 'TypeScript', 'React JS', 'Vue.js', 'Angular',
                    'Next.js', 'Nuxt.js', 'Bootstrap',
                    
                    // لغات الويب الخلفية
                    'PHP', 'Python',  'Node.js', 'SQL', 'MongoDB',
                    'Laravel', 'Django',  
                    
                    // لغات الموبايل
                    'Python AI', 'Flutter', 'React Native', 'Python AI',
         
                    
                    // لغات الشبكات
                 'Laravel Livewire', 'C++', 'Go',
                    'Git', 'Docker', 'AWS',
                    
                    // لغات البرمجة السريعة
                    'FastAPI', 'CodeIgniter', 'Django', 'JQ'
              
                ];

                $stmt = $db->prepare('INSERT INTO languages (name) VALUES (?)');
                foreach ($defaultLanguages as $lang) {
                    try {
                        // التحقق من عدم وجود اللغة مسبقا
                        $stmt_check = $db->prepare('SELECT id FROM languages WHERE name = ?');
                        $stmt_check->execute([$lang]);
                        if (!$stmt_check->fetch()) {
                            $stmt->execute([$lang]);
                        }
                    } catch (PDOException $e) {
                        if ($e->getCode() != 23000) throw $e;
                    }
                }

                echo jsonResponse(true, 'تم إضافة لغات البرمجة بنجاح');
            } catch (Exception $e) {
                throw new Exception('فشل في إضافة اللغات: ' . $e->getMessage());
            }
            break;

        case 'truncate_all_tables':
            try {
                $db->beginTransaction();

                // تعطيل فحص المفاتيح الأجنبية مؤقتاً
                $db->exec('SET FOREIGN_KEY_CHECKS = 0');

                // حذف البيانات من جميع الجداول
                $tables = [
                    'lessons',
                    'course_sections',
                    'courses',
                    'sections',
                    'statuses',
                    'languages'
                ];

                foreach ($tables as $table) {
                    $db->exec("TRUNCATE TABLE $table");
                }

                // إعادة تفعيل فحص المفاتيح الأجنبية
                $db->exec('SET FOREIGN_KEY_CHECKS = 1');

                $db->commit();
                echo jsonResponse(true, 'تم حذف جميع البيانات بنجاح');
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                // إعادة تفعيل فحص المفاتيح الأجنبية في حالة الخطأ
                $db->exec('SET FOREIGN_KEY_CHECKS = 1');
                throw new Exception('فشل في حذف البيانات: ' . $e->getMessage());
            }
            break;

        default:
            throw new Exception('العملية غير معروفة');
    }
} catch (Exception $e) {
    echo jsonResponse(false, $e->getMessage());
} 