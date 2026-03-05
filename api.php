<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$host = "localhost";
$user = "اسم_المستخدم"; 
$pass = "كلمة_المرور";
$db   = "اسم_القاعدة";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8");

$action = $_GET['action'] ?? '';

// --- 1. تسجيل الدخول ---
if ($action == 'login') {
    $data = json_decode(file_get_contents("php://input"), true);
    $u = $data['username']; $p = $data['password'];
    $res = $conn->query("SELECT * FROM users WHERE username='$u'")->fetch_assoc();
    if ($res && password_verify($p, $res['password'])) {
        echo json_encode(["status" => "success", "user" => $res]);
    } else {
        echo json_encode(["status" => "error"]);
    }
}

// --- 2. جلب إعدادات المحل ---
if ($action == 'get_settings') {
    $res = $conn->query("SELECT * FROM settings");
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}

// --- 3. إدارة الصيانة (جلب/إضافة) ---
if ($action == 'get_repairs') {
    $res = $conn->query("SELECT * FROM repairs ORDER BY id DESC");
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}

if ($action == 'add_repair' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $d = json_decode(file_get_contents("php://input"), true);
    $sql = "INSERT INTO repairs (customer_name, customer_phone, device_brand, device_model, imei_number, issue, total_cost) 
            VALUES ('{$d['name']}', '{$d['phone']}', '{$d['brand']}', '{$d['model']}', '{$d['imei']}', '{$d['issue']}', '{$d['cost']}')";
    if ($conn->query($sql)) echo json_encode(["status" => "success", "id" => $conn->insert_id]);
}

// --- 4. البحث بـ IMEI ---
if ($action == 'search_imei') {
    $imei = $_GET['imei'];
    $res = $conn->query("SELECT * FROM repairs WHERE imei_number = '$imei' ORDER BY id DESC");
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
}

$conn->close();
?>