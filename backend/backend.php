<?php
session_start();
require __DIR__ . '/db.php';
header('Content-Type: application/json');

// 輸出 CORS 允許（開發時可用，正式環境調整為特定域名）
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit(0);
}
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

try {
    $db = getPDO();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => '資料庫連接失敗']);
    exit;
}

// 登入 API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'login') {
    $input = json_decode(file_get_contents("php://input"), true);
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';

    error_log('Login attempt for email: ' . $email);  // 除錯用
    
    // 從 users 表取得該用戶資料
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log('User data: ' . print_r($user, true));  // 除錯用
    error_log('Password verify result: ' . (password_verify($password, $user['password_hash']) ? 'true' : 'false'));  // 除錯用

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        echo json_encode([
            'success' => true, 
            'msg' => '登入成功',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'msg' => '帳號或密碼錯誤']);
    }
    exit;
}

// 登出 API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'msg' => '已登出']);
    exit;
}

// API 路由
// GET 請求
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 公開的 API 端點（無需登入）
    if ($_GET['action'] === 'list') {
        // 獲取案例列表，按上傳日期降序排序
        $rs = $db->query("SELECT * FROM case_stories ORDER BY upload_date DESC");
        echo json_encode([
            'success' => true,
            'data' => $rs->fetchAll(PDO::FETCH_ASSOC)
        ]);
        exit;
    }
}

// 檢查是否已登入（其他路由需要登入）
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'msg' => '未授權']);
    exit;
}

// 需要登入的 API 路由
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($_GET['action'] === 'list') {
        // 獲取案例列表，按上傳日期降序排序
        $rs = $db->query("SELECT * FROM case_stories ORDER BY upload_date DESC");
        echo json_encode([
            'success' => true,
            'data' => $rs->fetchAll(PDO::FETCH_ASSOC)
        ]);
    } elseif ($_GET['action'] === 'get' && isset($_GET['id'])) {
        // 獲取單個案例詳情
        $stmt = $db->prepare("SELECT * FROM case_stories WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $case = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => true,
            'data' => $case
        ]);
    }
    exit;
}

// POST 請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    // 檢查必填欄位
    $required_fields = ['title', 'lawyer', 'uploader', 'upload_date', 'description'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'msg' => "缺少必填欄位：{$field}"]);
            exit;
        }
    }

    try {
        if (!empty($data['id'])) {
            // 更新案例
            $stmt = $db->prepare("
                UPDATE case_stories 
                SET title = ?, 
                    lawyer = ?, 
                    uploader = ?, 
                    upload_date = ?, 
                    description = ?,
                    client_name = ?
                WHERE id = ?");
            
            $res = $stmt->execute([
                $data['title'],
                $data['lawyer'],
                $data['uploader'],
                $data['upload_date'],
                $data['description'],
                $data['client_name'] ?? null,
                $data['id']
            ]);
        } else {
            // 新增案例
            $stmt = $db->prepare("
                INSERT INTO case_stories 
                (title, lawyer, uploader, upload_date, description, client_name) 
                VALUES (?, ?, ?, ?, ?, ?)");
            
            $res = $stmt->execute([
                $data['title'],
                $data['lawyer'],
                $data['uploader'],
                $data['upload_date'],
                $data['description'],
                $data['client_name'] ?? null
            ]);
            if ($res) {
                $data['id'] = $db->lastInsertId();
            }
        }

        if ($res) {
            echo json_encode([
                'success' => true,
                'msg' => !empty($data['id']) ? '更新成功' : '新增成功',
                'data' => $data
            ]);
        } else {
            throw new Exception('資料庫操作失敗');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'msg' => '操作失敗：' . $e->getMessage()
        ]);
    }
    exit;
}

// DELETE 請求
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'msg' => '缺少案例 ID']);
        exit;
    }

    try {
        $stmt = $db->prepare("DELETE FROM case_stories WHERE id = ?");
        $res = $stmt->execute([$data['id']]);
        
        if ($res) {
            echo json_encode(['success' => true, 'msg' => '刪除成功']);
        } else {
            throw new Exception('刪除失敗');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'msg' => '刪除失敗：' . $e->getMessage()
        ]);
    }
    exit;
}
?>
