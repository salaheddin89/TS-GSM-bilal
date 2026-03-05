<?php
session_start();
// --- إعدادات قاعدة البيانات ---
$host = "localhost";
$user = "اسم_المستخدم"; 
$pass = "كلمة_المرور";
$db   = "اسم_القاعدة";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8");

// --- منطق تسجيل الدخول ---
if (isset($_POST['login'])) {
    $u = $_POST['username'];
    $p = $_POST['password'];
    $res = $conn->query("SELECT * FROM users WHERE username='$u'")->fetch_assoc();
    if ($res && password_verify($p, $res['password'])) {
        $_SESSION['user'] = $res;
    } else { $error = "بيانات خاطئة!"; }
}

if (isset($_GET['logout'])) { session_destroy(); header("Location: index.php"); }

// --- حفظ جهاز جديد ---
if (isset($_POST['add_repair'])) {
    $stmt = $conn->prepare("INSERT INTO repairs (customer_name, customer_phone, device_brand, device_model, imei_number, issue, total_cost) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssd", $_POST['name'], $_POST['phone'], $_POST['brand'], $_POST['model'], $_POST['imei'], $_POST['issue'], $_POST['cost']);
    $stmt->execute();
    header("Location: index.php?view=list");
}

// حماية الصفحة
if (!isset($_SESSION['user']) && !isset($_POST['login'])):
?>
<!-- شاشة تسجيل الدخول -->
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>دخول النظام</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#1a1a2e] flex items-center justify-center h-screen">
    <div class="bg-[#16213e] p-8 rounded-xl shadow-2xl w-96 border-r-4 border-pink-600 text-white">
        <h2 class="text-2xl font-bold mb-6 text-center">نظام المحترف 📱</h2>
        <?php if(isset($error)) echo "<p class='text-red-500'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="اسم المستخدم" class="w-full p-3 mb-4 rounded bg-[#0f3460] border-none text-white" required>
            <input type="password" name="password" placeholder="كلمة السر" class="w-full p-3 mb-4 rounded bg-[#0f3460] border-none text-white" required>
            <button name="login" class="w-full bg-pink-600 p-3 rounded font-bold hover:bg-pink-700 transition">دخول</button>
        </form>
    </div>
</body>
</html>
<?php exit(); endif; ?>

<!-- الواجهة الرئيسية للبرنامج -->
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم - المحترف</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-[#1a1a2e] text-white">
    <div class="flex">
        <!-- القائمة الجانبية -->
        <div class="w-64 bg-[#16213e] min-h-screen p-6 shadow-xl border-l border-gray-800">
            <h1 class="text-xl font-bold text-pink-500 mb-8 underline">لوحة الإدارة</h1>
            <nav class="space-y-4">
                <a href="?view=list" class="block p-3 hover:bg-pink-600 rounded transition"><i class="fas fa-tools ml-2"></i> قائمة الصيانة</a>
                <a href="?view=add" class="block p-3 hover:bg-pink-600 rounded transition"><i class="fas fa-plus ml-2"></i> استلام جهاز</a>
                <a href="?view=search" class="block p-3 hover:bg-pink-600 rounded transition"><i class="fas fa-search ml-2"></i> بحث IMEI</a>
                <hr class="border-gray-700">
                <a href="?logout=1" class="block p-3 text-red-400 hover:bg-red-900 rounded transition"><i class="fas fa-sign-out-alt ml-2"></i> خروج</a>
            </nav>
        </div>

        <!-- المحتوى -->
        <div class="flex-1 p-10">
            <?php 
            $view = $_GET['view'] ?? 'list';
            
            if ($view == 'list'): 
                $repairs = $conn->query("SELECT * FROM repairs ORDER BY id DESC");
            ?>
                <h2 class="text-3xl font-bold mb-6">قائمة الأجهزة قيد الصيانة</h2>
                <div class="grid grid-cols-1 gap-4">
                    <?php while($row = $repairs->fetch_assoc()): 
                        $whatsapp_num = ltrim($row['customer_phone'], '0');
                        $msg = urlencode("السلام عليكم، جهازك {$row['device_model']} جاهز للتسليم.");
                    ?>
                        <div class="bg-[#16213e] p-6 rounded-lg border-r-4 border-pink-500 flex justify-between items-center shadow-lg">
                            <div>
                                <h4 class="text-xl font-bold"><?php echo $row['device_brand'] . " " . $row['device_model']; ?></h4>
                                <p class="text-gray-400">IMEI: <?php echo $row['imei_number']; ?></p>
                                <p class="text-gray-300">الزبون: <?php echo $row['customer_name']; ?> | <span class="text-pink-400"><?php echo $row['status']; ?></span></p>
                            </div>
                            <div class="flex gap-2">
                                <a href="https://wa.me/213<?php echo $whatsapp_num; ?>?text=<?php echo $msg; ?>" target="_blank" class="bg-green-600 p-3 rounded hover:bg-green-700">
                                    <i class="fab fa-whatsapp"></i> واتساب
                                </a>
                                <a href="tel:<?php echo $row['customer_phone']; ?>" class="bg-blue-600 p-3 rounded hover:bg-blue-700">
                                    <i class="fas fa-phone"></i> إتصال
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

            <?php elseif ($view == 'add'): ?>
                <h2 class="text-3xl font-bold mb-6">استلام جهاز جديد</h2>
                <form method="POST" class="bg-[#16213e] p-8 rounded-xl max-w-2xl">
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" name="name" placeholder="اسم الزبون" class="bg-[#0f3460] p-3 rounded text-white" required>
                        <input type="text" name="phone" placeholder="رقم الهاتف (05...)" class="bg-[#0f3460] p-3 rounded text-white" required>
                        <input type="text" name="brand" placeholder="الماركة (iPhone, Samsung)" class="bg-[#0f3460] p-3 rounded text-white" required>
                        <input type="text" name="model" placeholder="الموديل (S23, 15 Pro)" class="bg-[#0f3460] p-3 rounded text-white" required>
                        <input type="text" name="imei" placeholder="رقم الـ IMEI" class="bg-[#0f3460] p-3 rounded text-white" required>
                        <input type="number" name="cost" placeholder="التكلفة التقديرية (دج)" class="bg-[#0f3460] p-3 rounded text-white" required>
                    </div>
                    <textarea name="issue" placeholder="وصف العطل" class="w-full bg-[#0f3460] p-3 rounded mt-4 h-32" required></textarea>
                    <button name="add_repair" class="mt-6 w-full bg-pink-600 p-4 rounded font-bold text-xl hover:bg-pink-700">حفظ وطباعة الوصل</button>
                </form>

            <?php elseif ($view == 'search'): ?>
                <h2 class="text-3xl font-bold mb-6">بحث IMEI الجهاز</h2>
                <form method="GET" class="flex gap-2 mb-8">
                    <input type="hidden" name="view" value="search">
                    <input type="text" name="q" placeholder="ادخل رقم IMEI للبحث في التاريخ..." class="flex-1 bg-[#16213e] p-4 rounded text-white border border-pink-500">
                    <button class="bg-pink-600 px-8 rounded font-bold">بحث</button>
                </form>
                <?php 
                if(isset($_GET['q'])): 
                    $q = $_GET['q'];
                    $results = $conn->query("SELECT * FROM repairs WHERE imei_number='$q'");
                    while($r = $results->fetch_assoc()):
                ?>
                    <div class="bg-[#16213e] p-4 rounded mb-2 border-r-2 border-green-500">
                        <p>تاريخ: <?php echo $r['created_at']; ?> | العطل: <?php echo $r['issue']; ?> | السعر: <?php echo $r['total_cost']; ?> دج</p>
                    </div>
                <?php endwhile; endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>