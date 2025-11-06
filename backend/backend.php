<?php
session_start();
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
    $db = new PDO("mysql:host=localhost;dbname=lawyer;charset=utf8mb4", "your_db_user", "your_db_password");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'msg' => '資料庫連接失敗']);
    exit;
}

// 登入 API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'login') {
    $input = json_decode(file_get_contents("php://input"), true);
    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';

    // 從 users 表取得該用戶資料（密碼建議已經用 password_hash 加密）
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        echo json_encode(['success' => true, 'msg' => '登入成功']);
    } else {
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

// 檢查是否已登入（非登入路由拒絕存取）
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'msg' => '未授權']);
    exit;
}

// 文章管理 API
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($_GET['action'] === 'list') {
        $rs = $db->query("SELECT * FROM case_stories ORDER BY upload_date DESC");
        echo json_encode($rs->fetchAll(PDO::FETCH_ASSOC));
    } elseif ($_GET['action'] === 'get' && isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM case_stories WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    }
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 新增或更新案例
    if (!empty($data['id'])) {
        $stmt = $db->prepare("UPDATE case_stories SET uploader = ?, upload_date = ?, lawyer = ?, title = ?, description = ? WHERE id = ?");
        $res = $stmt->execute([$data['uploader'], $data['upload_date'], $data['lawyer'], $data['title'], $data['description'], $data['id']]);
    } else {
        $stmt = $db->prepare("INSERT INTO case_stories (uploader, upload_date, lawyer, title, description) VALUES (?, ?, ?, ?, ?)");
        $res = $stmt->execute([$data['uploader'], $data['upload_date'], $data['lawyer'], $data['title'], $data['description']]);
    }
    echo json_encode(['success' => $res]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // 刪除案例
    $stmt = $db->prepare("DELETE FROM case_stories WHERE id = ?");
    $res = $stmt->execute([$data['id']]);
    echo json_encode(['success' => $res]);
    exit;
}
?>
