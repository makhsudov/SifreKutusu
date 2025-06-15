<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        // Giris islemi
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            $error = 'Kullanıcı adı ve şifre boş olamaz.';
        } else {
            // Veritabaninda kullaniciyi ara
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // Sifre dogrulamasi
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Kullanıcı adı veya şifre hatalı.';
            }
        }
    }
    
    if (isset($_POST['register'])) {
        // Kayit islemi
        $username = trim($_POST['reg_username']);
        $email = trim($_POST['reg_email']);
        $password = $_POST['reg_password'];
        $confirm_password = $_POST['reg_confirm_password'];
        
        // Validasyon kontrolleri
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'Tüm alanlar doldurulmalıdır.';
        } elseif ($password !== $confirm_password) {
            $error = 'Şifreler eşleşmiyor.';
        } elseif (strlen($password) < 6) {
            $error = 'Şifre en az 6 karakter olmalıdır.';
        } else {
            // Kullanici adi (benzersizlik) kontrolu
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $error = 'Bu kullanıcı adı veya e-posta zaten kullanılıyor.';
            } else {
                // Yeni kullanici olustur
                $password_hash = password_hash($password, PASSWORD_DEFAULT); // Sifreyi hashle
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
                
                if ($stmt->execute([$username, $email, $password_hash])) {
                    $success = 'Kayıt başarılı! Şimdi giriş yapabilirsiniz.';
                } else {
                    $error = 'Kayıt sırasında bir hata oluştu.';
                }
            }
        }
    }
}

$current_theme = $_COOKIE['theme'] ?? 'light';
?>

<!DOCTYPE html>
<html lang="tr" class="<?php echo $current_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ŞifreKutusu  - Giriş</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen transition-colors duration-200 flex items-center justify-center">
    <!-- Theme Toggle -->
    <div class="absolute top-4 right-4">
        <button onclick="toggleTheme()" class="p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <svg id="theme-icon-light" class="w-5 h-5 text-gray-600 dark:text-gray-400 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            <svg id="theme-icon-dark" class="w-5 h-5 text-gray-600 dark:text-gray-400 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
        </button>
    </div>

    <div class="container mx-auto px-4 w-full max-w-md">
        <div class="max-w-md mx-auto">
            <!-- Baslik -->
            <div class="text-center mb-8"> 
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">ŞifreKutusu</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Şifrelerinizi güvenle saklayın</p>
            </div>

            <!-- Hata/Basari Mesajlari -->
            <?php if ($error): ?>
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-xl mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-xl mb-4">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Giris/Kayit Formlari -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <!-- Tab Basliklari -->
                <div class="flex border-b border-gray-200 dark:border-gray-700">
                    <button onclick="showLogin()" id="loginTab" class="flex-1 py-3 px-4 text-center font-medium rounded-tl-xl bg-blue-500 text-white">
                        Giriş Yap
                    </button>
                    <button onclick="showRegister()" id="registerTab" class="flex-1 py-3 px-4 text-center font-medium rounded-tr-xl bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                        Kayıt Ol
                    </button>
                </div>

                <!-- Giris Formu -->
                <div id="loginForm" class="p-6">
                    <form method="POST">
                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2" for="username">
                                Kullanıcı Adı
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                   id="username" name="username" type="text" required>
                        </div>
                        <div class="mb-6">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2" for="password">
                                Şifre
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                   id="password" name="password" type="password" required>
                        </div>
                        <button class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200" 
                                type="submit" name="login">
                            Giriş Yap
                        </button>
                    </form>
                </div>

                <!-- Kayit Formu -->
                <div id="registerForm" class="p-6 hidden">
                    <form method="POST">
                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2" for="reg_username">
                                Kullanıcı Adı
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                   id="reg_username" name="reg_username" type="text" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2" for="reg_email">
                                E-posta
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                   id="reg_email" name="reg_email" type="email" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2" for="reg_password">
                                Şifre
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                   id="reg_password" name="reg_password" type="password" required>
                        </div>
                        <div class="mb-6">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2" for="reg_confirm_password">
                                Şifre Tekrar
                            </label>
                            <input class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                   id="reg_confirm_password" name="reg_confirm_password" type="password" required>
                        </div>
                        <button class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200" 
                                type="submit" name="register">
                            Kayıt Ol
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showLogin() {
            // Giris formunu goster, kayit formunu gizle
            document.getElementById('loginForm').classList.remove('hidden');
            document.getElementById('registerForm').classList.add('hidden');
            document.getElementById('loginTab').classList.add('bg-blue-500', 'text-white');
            document.getElementById('loginTab').classList.remove('bg-gray-100', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
            document.getElementById('registerTab').classList.add('bg-gray-100', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
            document.getElementById('registerTab').classList.remove('bg-blue-500', 'text-white');
        }

        function showRegister() {
            // Kayit formunu goster, giris formunu gizle
            document.getElementById('registerForm').classList.remove('hidden');
            document.getElementById('loginForm').classList.add('hidden');
            document.getElementById('registerTab').classList.add('bg-blue-500', 'text-white');
            document.getElementById('registerTab').classList.remove('bg-gray-100', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
            document.getElementById('loginTab').classList.add('bg-gray-100', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
            document.getElementById('loginTab').classList.remove('bg-blue-500', 'text-white');
        }

        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.classList.contains('dark') ? 'dark' : 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.classList.toggle('dark'); // CSS class'ini degistir
            
            fetch('theme_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'theme=' + newTheme
            });
        }
    </script>
</body>
</html>