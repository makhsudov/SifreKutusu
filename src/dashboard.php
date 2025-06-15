<?php
require_once 'config.php';

// Kullanici giris kontrolu
if (!isset($_SESSION['user_id'])) {
   header('Location: index.php');
   exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Sifre ekleme islemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_password'])) {
   $site_name = trim($_POST['site_name']);
   $site_url = trim($_POST['site_url']);
   $site_username = trim($_POST['site_username']);
   $site_password = $_POST['site_password'];
   $tag = trim($_POST['tag']);
   
   if (!empty($site_name) && !empty($site_username) && !empty($site_password)) {
       // Kullanici anahtari uret
       $user_key = generate_user_key($user_id);
       // Sifreyi sifrele
       $encrypted_password = encrypt_password($site_password, $user_key);
       
       // Veritabanina kaydet
       $stmt = $pdo->prepare("INSERT INTO passwords (user_id, site_name, site_url, username, encrypted_password, tag) VALUES (?, ?, ?, ?, ?, ?)");
       $stmt->execute([$user_id, $site_name, $site_url, $site_username, $encrypted_password, $tag]);
   }
}

// Sifre silme islemi
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
   $password_id = $_GET['delete'];
   // Sadece kullanicinin kendi kaydini sil
   $stmt = $pdo->prepare("DELETE FROM passwords WHERE id = ? AND user_id = ?");
   $stmt->execute([$password_id, $user_id]);
   // Sayfayi yenile
   header('Location: dashboard.php');
   exit;
}

// Filtreleme
$tag_filter = isset($_GET['tag']) ? $_GET['tag'] : '';
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';

// Kullanicinin sifrelerini getir
$sql = "SELECT * FROM passwords WHERE user_id = ?";
$params = [$user_id];

// Tag filtresini ekle
if (!empty($tag_filter)) {
   $sql .= " AND tag = ?";
   $params[] = $tag_filter;
}

// Arama filtresini ekle
if (!empty($search_filter)) {
   $sql .= " AND (site_name LIKE ? OR username LIKE ?)";
   $params[] = "%$search_filter%";
   $params[] = "%$search_filter%";
}

$sql .= " ORDER BY created_at DESC"; // Yeniden eskiye sirala

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$passwords = $stmt->fetchAll();

// Tum taglari getir
$all_tags = get_all_tags($pdo, $user_id);

$current_theme = $_COOKIE['theme'] ?? 'light';
?>

<!DOCTYPE html>
<html lang="tr" class="<?php echo $current_theme; ?>">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Panel - ŞifreKutusu</title>
   <script src="https://cdn.tailwindcss.com"></script>
   <script>
       tailwind.config = {
           darkMode: 'class'
       }
   </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen transition-colors duration-200">
   <!-- Navbar -->
   <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
       <div class="container mx-auto px-4">
           <div class="flex items-center justify-between h-16">
               <div class="flex items-center">
                   <div class="rounded-lg w-10 h-10 flex items-center justify-center mr-3">
                       <!-- Animasyonlu ikon -->
                       <svg id="rotating-icon" class="w-6 h-6 text-gray-900 dark:text-white transition-all duration-300 ease-in-out" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z"></path>
                       </svg>
                   </div>
                   <h1 class="text-xl font-bold text-gray-900 dark:text-white font-mono">ŞifreKutusu</h1>
               </div>

               <div class="flex items-center space-x-2">
                   <!-- Theme Toggle -->
                   <div class="relative">
                       <button onclick="toggleTheme()" class="p-2 rounded-lg bg-transparent hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200 group">
                           <svg class="w-5 h-5 text-yellow-500 hidden dark:block transition-all duration-200" fill="currentColor" viewBox="0 0 24 24">
                               <path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z"></path>
                           </svg>
                           <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-indigo-600 block dark:hidden transition-all duration-200">
                               <path fill-rule="evenodd" d="M9.528 1.718a.75.75 0 0 1 .162.819A8.97 8.97 0 0 0 9 6a9 9 0 0 0 9 9 8.97 8.97 0 0 0 3.463-.69.75.75 0 0 1 .981.98 10.503 10.503 0 0 1-9.694 6.46c-5.799 0-10.5-4.7-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 0 1 .818.162Z" clip-rule="evenodd" />
                           </svg>
                       </button>
                   </div>

                   <div class="h-8 w-px bg-gray-300 dark:bg-gray-600"></div>
                   <!-- Logout Button -->
                   <a href="logout.php" class="flex items-center space-x-2 text-gray-700 dark:text-gray-300 hover:text-red-600 dark:hover:text-red-400 px-3 py-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-all duration-200 group">
                       <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                       </svg>
                       <span class="text-sm font-medium hidden sm:block">Çıkış</span>
                   </a>
               </div>
           </div>
       </div>
   </nav>

   <div class="container mx-auto px-4 py-8">
       <div class="max-w-4xl mx-auto">
           <!-- Baslik ve Yeni Sifre Butonu -->
           <span class="text-gray-600 dark:text-gray-400">Hoş geldiniz, <?php echo htmlspecialchars($username); ?></span>
           <div class="flex items-center justify-between mb-6">
               <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Kayıtlı Şifreler</h2>
               <button onclick="openAddModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center">
                   <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                   </svg>
                   Yeni Şifre
               </button>
           </div>

           <!-- Arama ve Filtreler -->
           <!-- Arama -->
           <div class="relative mb-4">
               <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                   <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                   </svg>
               </div>
               <input type="text" id="searchInput" placeholder="Site adı veya kullanıcı adı ara..." 
                      value="<?php echo htmlspecialchars($search_filter); ?>"
                      class="w-full pl-10 pr-4 py-3 border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
           </div>

           <!-- Tag Filtreleri -->
           <?php if (!empty($all_tags)): ?>
           <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-b-0 rounded-t-xl overflow-hidden mb-0">
               <div class="relative">
                   <!-- Sol ok -->
                   <button id="scrollLeft" onclick="scrollTabs('left')" 
                           class="absolute left-0 top-0 bottom-0 z-10 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 px-2 flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors hidden">
                       <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                       </svg>
                   </button>
                   
                   <!-- Sag ok -->
                   <button id="scrollRight" onclick="scrollTabs('right')" 
                           class="absolute right-0 top-0 bottom-0 z-10 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 px-2 flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors hidden">
                       <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                       </svg>
                   </button>
                   
                   <!-- Tab container -->
                   <div id="tabContainer" class="overflow-x-hidden scrollbar-hide">
                       <div id="tabScroller" class="flex transition-transform duration-300 ease-in-out">
                           <!-- Tumu butonu -->
                           <button onclick="filterByTag('')" 
                                   class="flex-shrink-0 px-6 py-3.5 text-sm font-medium border-b-2 transition-all duration-200 <?php echo empty($tag_filter) ? 'border-blue-500 text-blue-600 dark:text-blue-400 bg-gray-50 dark:bg-gray-700' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'; ?>">
                               Tümü
                           </button>
                           
                           <!-- Tag butonlari -->
                           <?php foreach ($all_tags as $tag): ?>
                               <button onclick="filterByTag('<?php echo htmlspecialchars($tag); ?>')" 
                                       class="flex-shrink-0 px-6 py-3.5 text-sm font-medium border-b-2 transition-all duration-200 whitespace-nowrap <?php echo $tag_filter === $tag ? 'border-blue-500 text-blue-600 dark:text-blue-400 bg-gray-50 dark:bg-gray-700' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'; ?>">
                                   <?php echo htmlspecialchars($tag); ?>
                               </button>
                           <?php endforeach; ?>
                       </div>
                   </div>
               </div>
           </div>
           <?php endif; ?>

           <!-- Sifre Tablosu -->
           <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden <?php echo !empty($all_tags) ? 'rounded-t-none border-t-0' : ''; ?>">
               <?php if (empty($passwords)): ?>
                   <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                       <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600"  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                           <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                       </svg>
                       <p class="text-lg">Henüz kayıtlı şifre bulunmuyor</p>
                       <p class="text-sm">Yeni şifre eklemek için yukarıdaki butonu kullanın</p>
                   </div>
               <?php else: ?>
                   <div class="overflow-x-auto">
                       <table class="w-full">
                           <thead class="bg-gray-50 dark:bg-gray-700">
                               <tr>
                                   <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Site</th>
                                   <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kullanıcı</th>
                                   <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Şifre</th>
                                   <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tag</th>
                                   <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">İşlemler</th>
                               </tr>
                           </thead>
                           <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                               <?php foreach ($passwords as $password): ?>
                                   <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                       <td class="px-6 py-4 whitespace-nowrap">
                                           <div class="flex items-center">
                                               <div>
                                                   <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                       <?php echo htmlspecialchars($password['site_name']); ?>
                                                   </div>
                                                   <?php if ($password['site_url']): ?>
                                                       <div class="text-sm text-gray-500 dark:text-gray-400">
                                                           <a href="<?php echo htmlspecialchars($password['site_url']); ?>" target="_blank" class="hover:text-blue-500">
                                                               <?php echo htmlspecialchars(parse_url($password['site_url'], PHP_URL_HOST)); ?>
                                                           </a>
                                                       </div>
                                                   <?php endif; ?>
                                               </div>
                                           </div>
                                       </td>
                                       <td class="px-6 py-4 whitespace-nowrap">
                                           <div class="text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($password['username']); ?></div>
                                       </td>
                                       <td class="px-6 py-4 whitespace-nowrap">
                                           <div class="flex items-center">
                                               <input type="password" 
                                                      value="<?php echo htmlspecialchars(decrypt_password($password['encrypted_password'], generate_user_key($user_id))); ?>" 
                                                      id="password_<?php echo $password['id']; ?>" 
                                                      class="bg-transparent border-none text-sm font-mono text-gray-900 dark:text-white" readonly>
                                               <button onclick="togglePassword('password_<?php echo $password['id']; ?>')" 
                                                       class="ml-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                                   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                   </svg>
                                               </button>
                                               <button onclick="copyPassword('password_<?php echo $password['id']; ?>')" 
                                                       class="ml-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Kopyala">
                                                   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                   </svg>
                                               </button>
                                           </div>
                                       </td>
                                       <td class="px-6 py-4 whitespace-nowrap">
                                           <?php if ($password['tag']): ?>
                                               <span class="inline-flex py-1 px-3 text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-lg">
                                                   <?php echo htmlspecialchars($password['tag']); ?>
                                               </span>
                                           <?php endif; ?>
                                       </td>
                                       <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                           <div class="flex space-x-2">
                                               <button onclick="editPassword(<?php echo $password['id']; ?>)" 
                                                       class="text-blue-500 hover:text-blue-700" title="Düzenle">
                                                   <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                   </svg>
                                               </button>
                                               <a href="?delete=<?php echo $password['id']; ?>" 
                                                  onclick="return confirm('Bu şifreyi silmek istediğinizden emin misiniz?')"
                                                  class="text-red-500 hover:text-red-700" title="Sil">
                                                   <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                   </svg>
                                               </a>
                                           </div>
                                       </td>
                                   </tr>
                               <?php endforeach; ?>
                           </tbody>
                       </table>
                   </div>
               <?php endif; ?>
           </div>
       </div>
   </div>

   <!-- Sifre Ekleme Modal -->
   <div id="addModal" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50 py-8 px-4">
       <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 py-4 p-6 w-full max-w-md max-h-full overflow-y-auto">
           <!-- Baslik ve Kapatma Butonu -->
           <div class="flex items-center justify-between mb-6">
               <h3 class="text-lg font-bold text-gray-900 dark:text-white">Yeni Şifre Ekle</h3>
               <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                   <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                   </svg>
               </button>
           </div>
           
           <form method="POST">
               <div class="mb-4">
                   <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2">Site Adı *</label>
                   <input class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       name="site_name" type="text" required placeholder="Örn: Google">
               </div>
               <div class="mb-4">
                   <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2">Site URL</label>
                   <input class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       name="site_url" type="url" placeholder="https://www.google.com">
               </div>
               <div class="mb-4">
                   <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2">Kullanıcı Adı *</label>
                   <input class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       name="site_username" type="text" required placeholder="kullanici@email.com">
               </div>
               <div class="mb-4">
                   <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2">Şifre *</label>
                   <div class="relative">
                       <input class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-10" 
                           name="site_password" type="password" id="newPassword" required>
                       <button type="button" onclick="togglePassword('newPassword')" 
                           class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                           <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                           </svg>
                       </button>
                   </div>
               </div> 
               <div class="mb-6">
                   <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2">Tag</label>
                   <input class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       name="tag" type="text" placeholder="Örn: sosyal medya, e-posta, iş">
               </div>
               
               <button class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200" 
                       type="submit" name="add_password">
                   Şifre Ekle
               </button>
           </form>
       </div>
   </div>

   <!-- Duzenleme Modal -->
   <div id="editModal" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50 py-8 px-4">
       <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 py-4 p-6 w-full max-w-md max-h-full overflow-y-auto">
           <!-- Baslik ve Kapatma Butonu -->
           <div class="flex items-center justify-between mb-6">
               <h3 class="text-lg font-bold text-gray-900 dark:text-white">Şifre Düzenle</h3>
               <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                   <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                   </svg>
               </button>
           </div>
           
           <form id="editForm" method="POST" action="edit_password.php">
               <input type="hidden" id="edit_id" name="password_id">
               <div class="mb-4">
                   <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2">Site Adı</label>
                   <input class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       id="edit_site_name" name="site_name" type="text" required>
               </div>
               <div class="mb-4">
                   <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2">Site URL</label>
                   <input class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       id="edit_site_url" name="site_url" type="url">
               </div>
               <div class="mb-4">
                   <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2">Kullanıcı Adı</label>
                   <input class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       id="edit_username" name="site_username" type="text" required>
               </div>
               <div class="mb-4">
                   <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2">Şifre</label>
                   <div class="relative">
                       <input class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-10" 
                           id="edit_password" name="site_password" type="password" required>
                       <button type="button" onclick="togglePassword('edit_password')" 
                               class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                           <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                           </svg>
                       </button>
                   </div>
               </div>
               <div class="mb-6">
                   <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2">Tag</label>
                   <input class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       id="edit_tag" name="tag" type="text">
               </div> 
               
               <button type="submit" name="update_password" 
                       class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                   Güncelle
               </button>
           </form>
       </div>
   </div>


   <script>
       document.addEventListener('DOMContentLoaded', function() {
           const iconElement = document.getElementById('rotating-icon');
           const icons = [
               // Anahtar (key) - baslangic anahtari
               { path: "M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" },
               
               // Kutu (archive-box)
               { path: "m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" },
               
               // Kilit (lock-closed)
               { path: "M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" }
           ];
           
           let currentIndex = 0;
           
           function switchIcon() {
               // Kaybolma efekti ekle
               iconElement.style.opacity = '0';
               iconElement.style.transform = 'scale(0.8)';
               
               setTimeout(() => {
                   // Ikonu degistir
                   currentIndex = (currentIndex + 1) % icons.length;
                   iconElement.querySelector('path').setAttribute('d', icons[currentIndex].path);
                   
                   // Gorunurlugu geri getir
                   iconElement.style.opacity = '1';
                   iconElement.style.transform = 'scale(1)';
               }, 150);
           }
           
           // Her 3 saniyede degisimi baslat
           setInterval(switchIcon, 3000);
       });
   </script>

   <script>
       function toggleTheme() {
           const html = document.documentElement;
           const newTheme = html.classList.contains('dark') ? 'light' : 'dark';
           
           html.classList.toggle('dark');
           
           fetch('theme_handler.php', {
               method: 'POST',
               headers: {
                   'Content-Type': 'application/x-www-form-urlencoded',
               },
               body: 'theme=' + newTheme
           });
       }

       function togglePassword(inputId) {
           // Sifre goster/gizle
           const input = document.getElementById(inputId);
           input.type = input.type === 'password' ? 'text' : 'password';
       }

       function copyPassword(inputId) {
           // Sifreyi panoya kopyala
           const input = document.getElementById(inputId);
           const button = event.target.closest('button');
           const originalSvg = button.innerHTML;
           
           // Gecici input olustur ve kopyala
           const tempInput = document.createElement('input');
           tempInput.value = input.value;
           document.body.appendChild(tempInput);
           tempInput.select();
           document.execCommand('copy');
           document.body.removeChild(tempInput);
           
           // Butonu tick isareti ile degistir
           button.innerHTML = `
               <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
               </svg>
           `;
           
           // 2 saniye sonra orijinal ikona geri don
           setTimeout(() => {
               button.innerHTML = originalSvg;
           }, 2000);
       }

       function openAddModal() {
           document.getElementById('addModal').classList.remove('hidden');
           document.getElementById('addModal').classList.add('flex');
       }

       function closeAddModal() {
           document.getElementById('addModal').classList.add('hidden');
           document.getElementById('addModal').classList.remove('flex');
       }

       function editPassword(id) {
           fetch(`get_password.php?id=${id}`)
               .then(response => response.json())
               .then(data => {
                   document.getElementById('edit_id').value = data.id;
                   document.getElementById('edit_site_name').value = data.site_name;
                   document.getElementById('edit_site_url').value = data.site_url || '';
                   document.getElementById('edit_username').value = data.username;
                   document.getElementById('edit_password').value = data.password;
                   document.getElementById('edit_tag').value = data.tag || '';
                   document.getElementById('editModal').classList.remove('hidden');
                   document.getElementById('editModal').classList.add('flex');
               });
       }

       function closeEditModal() {
           document.getElementById('editModal').classList.add('hidden');
           document.getElementById('editModal').classList.remove('flex');
       }

       function filterByTag(tag) {
           const url = new URL(window.location);
           if (tag) {
               url.searchParams.set('tag', tag);
           } else {
               url.searchParams.delete('tag');
           }
           window.location = url;
       }

       // Arama islevi
       document.getElementById('searchInput').addEventListener('keypress', function(e) {
           if (e.key === 'Enter') {
               const url = new URL(window.location);
               if (this.value.trim()) {
                   url.searchParams.set('search', this.value.trim());
               } else {
                   url.searchParams.delete('search');
               }
               window.location = url;
           }
       });

       let currentScroll = 0;
       const scrollStep = 200;

       function scrollTabs(direction) {
           const container = document.getElementById('tabScroller');
           const containerWidth = document.getElementById('tabContainer').clientWidth;
           const scrollWidth = container.scrollWidth;
           const maxScroll = scrollWidth - containerWidth;
           
           if (direction === 'left') {
               currentScroll = Math.max(0, currentScroll - scrollStep);
           } else {
               currentScroll = Math.min(maxScroll, currentScroll + scrollStep);
           }
           
           container.style.transform = `translateX(-${currentScroll}px)`;
           updateScrollButtons();
       }

       function updateScrollButtons() {
           const container = document.getElementById('tabContainer');
           const scroller = document.getElementById('tabScroller');
           const leftBtn = document.getElementById('scrollLeft');
           const rightBtn = document.getElementById('scrollRight');
           
           if (!container || !scroller) return;
           
           const containerWidth = container.clientWidth;
           const scrollWidth = scroller.scrollWidth;
           const maxScroll = scrollWidth - containerWidth;
           
           // Kaydirma butonlarini goster/gizle
           if (maxScroll > 0) {
               leftBtn.classList.toggle('hidden', currentScroll <= 0);
               rightBtn.classList.toggle('hidden', currentScroll >= maxScroll);
           } else {
               leftBtn.classList.add('hidden');
               rightBtn.classList.add('hidden');
           }
       }

       // Yuklenme ve boyut degisikliginde buton ihtiyacini kontrol et
       document.addEventListener('DOMContentLoaded', function() {
           updateScrollButtons();
       });

       window.addEventListener('resize', function() {
           // Boyut degisikliginde kaydirmayi sifirla
           currentScroll = 0;
           const container = document.getElementById('tabScroller');
           if (container) {
               container.style.transform = 'translateX(0px)';
           }
           updateScrollButtons();
       });

       // Fare tekerlegini destekle
       document.getElementById('tabContainer')?.addEventListener('wheel', function(e) {
           if (e.deltaX !== 0) {
               e.preventDefault();
               scrollTabs(e.deltaX > 0 ? 'right' : 'left');
           }
       });
   </script>
</body>
</html>