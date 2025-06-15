<?php 
require_once 'config.php';

// POST istegi ve tema parametresi kontrolu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['theme'])) {
   // Tema degerini guvenli sekilde ayarla (sadece dark veya light)
   $theme = $_POST['theme'] === 'dark' ? 'dark' : 'light';
   
   // Cookie'yi 1 yil sureyle kaydet
   setcookie('theme', $theme, time() + (365 * 24 * 60 * 60), '/');
   
   // JSON yaniti dondur
   header('Content-Type: application/json');
   echo json_encode(['success' => true, 'theme' => $theme]);
   exit;
}

// POST degilse, onceki sayfaya geri don
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
?>