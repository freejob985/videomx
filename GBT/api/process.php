<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // تكوين الاتصال بقاعدة البيانات
    $db = new PDO(
        'mysql:host=localhost;dbname=courses_db;charset=utf8mb4',
        'root',
        '',
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        )
    );

    // دالة لحفظ السؤال والإجابة
    function saveQuestion($question, $notes, $answer, $model) {
        global $db;
        
        $stmt = $db->prepare("INSERT INTO questions (question, notes, answer, model) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$question, $notes, $answer, $model]);
    }

    // دالة للحصول على جميع الأسئلة
    function getQuestions() {
        global $db;
        
        $stmt = $db->query("SELECT * FROM questions ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // معالجة الطلب
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['question'])) {
            $result = saveQuestion(
                $data['question'],
                $data['notes'] ?? '',
                $data['answer'] ?? '',
                $data['model'] ?? 'gemini'
            );
            
            echo json_encode([
                'success' => $result,
                'message' => 'تم حفظ السؤال بنجاح'
            ]);
        } else {
            echo json_encode([
                'error' => true,
                'message' => 'بيانات غير صالحة'
            ]);
        }
    }
    // للحصول على جميع الأسئلة
    else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'getHistory':
                    $stmt = $db->query("SELECT * FROM questions ORDER BY created_at DESC");
                    echo json_encode([
                        'success' => true,
                        'history' => $stmt->fetchAll(PDO::FETCH_ASSOC)
                    ]);
                    break;
                    
                case 'getConversation':
                    if (isset($_GET['id'])) {
                        $stmt = $db->prepare("SELECT * FROM questions WHERE id = ?");
                        $stmt->execute([$_GET['id']]);
                        echo json_encode([
                            'success' => true,
                            'conversation' => $stmt->fetch(PDO::FETCH_ASSOC)
                        ]);
                    }
                    break;
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $questions = getQuestions();
            echo json_encode([
                'success' => true,
                'data' => $questions
            ]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
        if ($_GET['action'] === 'clearHistory') {
            $db->exec("TRUNCATE TABLE questions");
            echo json_encode(['success' => true]);
        }
    }

} catch (PDOException $e) {
    echo json_encode([
        'error' => true,
        'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
    ]);
}
?> 