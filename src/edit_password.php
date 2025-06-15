<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php'); // Giris sayfasina yonlendir
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $user_id = $_SESSION['user_id'];
    $password_id = $_POST['password_id'];
    $site_name = trim($_POST['site_name']);
    $site_url = trim($_POST['site_url']);
    $site_username = trim($_POST['site_username']);
    $site_password = $_POST['site_password'];
    $tag = trim($_POST['tag']); 
    
    // Zorunlu alanlar
    if (!empty($site_name) && !empty($site_username) && !empty($site_password)) {
        $user_key = generate_user_key($user_id);
        $encrypted_password = encrypt_password($site_password, $user_key);
        
        // Parol kaydini guncelle
        $stmt = $pdo->prepare("UPDATE passwords SET site_name = ?, site_url = ?, username = ?, encrypted_password = ?, tag = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
        // Parametreleri bagla ve sorguyu calistir
        $stmt->execute([$site_name, $site_url, $site_username, $encrypted_password, $tag, $password_id, $user_id]);
    }
}

header('Location: dashboard.php');
exit;
?>