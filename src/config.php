<?php
session_start();

// Sitenin aydinlik/karanlik theme ayarlari
if (!isset($_COOKIE['theme'])) {
    setcookie('theme', 'light', time() + (365 * 24 * 60 * 60), '/');
    $_COOKIE['theme'] = 'light';
}

// Veritabani baglanti ayarlari
$host = 'localhost';
$dbname = 'password_manager';
$username = 'root';
$password = '';


try {
    // Once veritabani olmadan baglan
    $pdo_setup = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo_setup->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Veritabanini olustur (eger yoksa)
    $pdo_setup->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    
    // Simdi veritabanina baglan
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    
    // Tablolari olustur (eger yoklarsa)

    // Kullanicilar tablosu
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Sifreler tablosu
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS passwords (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            site_name VARCHAR(100) NOT NULL,
            site_url VARCHAR(255),
            username VARCHAR(100) NOT NULL,
            encrypted_password TEXT NOT NULL,
            tag VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    "); 
    
} catch(PDOException $e) {
    die("Veritabanı baglanti hatasi: " . $e->getMessage());
}

// Guvenlik fonksiyonlari
// Sifreyi sifrele
function encrypt_password($password, $key) {
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($password, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

// Sifreyi coz
function decrypt_password($encrypted_password, $key) {
    $data = base64_decode($encrypted_password);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}

// Kullanici anahtari uret
function generate_user_key($user_id) {
    return hash('sha256', 'secret_key_' . $user_id);
}

// Tum etiketleri getir
function get_all_tags($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT DISTINCT tag FROM passwords WHERE user_id = ? AND tag IS NOT NULL AND tag != '' ORDER BY tag");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Theme degistirme islemi
if (isset($_POST['change_theme'])) {
    $new_theme = $_POST['theme'] === 'dark' ? 'dark' : 'light';
    setcookie('theme', $new_theme, time() + (365 * 24 * 60 * 60), '/');
    $_COOKIE['theme'] = $new_theme;
}
?>
