<?php
require_once 'config.php';

// Yetkilendirme kontrolleri
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    http_response_code(403);
    exit;
}

$user_id = $_SESSION['user_id'];
$password_id = $_GET['id'];

// Kullanicinin sadece kendi kayitlarina erisim sagla
$stmt = $pdo->prepare("SELECT * FROM passwords WHERE id = ? AND user_id = ?");
$stmt->execute([$password_id, $user_id]);
$password_data = $stmt->fetch();

if ($password_data) {
    // Sifreyi coz ve JSON olarak dondur
    $user_key = generate_user_key($user_id);
    $decrypted_password = decrypt_password($password_data['encrypted_password'], $user_key);
    
    echo json_encode([
        'id' => $password_data['id'],
        'site_name' => $password_data['site_name'],
        'site_url' => $password_data['site_url'],
        'username' => $password_data['username'],
        'password' => $decrypted_password,
        'tag' => $password_data['tag'],
    ]);
} else {
    // Kayit bulunamadi
    http_response_code(404);
}
?>