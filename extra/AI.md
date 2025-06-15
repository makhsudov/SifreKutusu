### 1. Veritabanı Tasarımı
**Soru:** Şifre yöneticisi için veritabanı şeması nasıl olmalı?

**AI Yanıtı:**
```sql
-- users tablosu
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- passwords tablosu  
CREATE TABLE passwords (
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
);
```

### 2. Güvenlik İmplementasyonu
**Soru:** PHP'de şifre nasıl güvenli bir şekilde şifrelenir ve saklanır?

**AI Yanıtı:**
```php
// Kullanıcı şifresi için
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Kayıtlı şifreler için AES şifreleme
function encrypt_password($password, $key) {
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($password, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function decrypt_password($encrypted_password, $key) {
    $data = base64_decode($encrypted_password);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}
```

### 3. JavaScript İnteraktivitesi
**Soru:** Şifre göster/gizle ve panoya kopyalama nasıl yapılır?

**AI Yanıtı:**
```javascript
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
}

function copyPassword(inputId) {
    const input = document.getElementById(inputId);
    const tempInput = document.createElement('input');
    tempInput.value = input.value;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
}
```

### 4. Filtreleme ve Arama
**Soru:** PHP'de dinamik filtreleme ve arama nasıl implement edilir?

**AI Yanıtı:**
```php
$sql = "SELECT * FROM passwords WHERE user_id = ?";
$params = [$user_id];

if (!empty($tag_filter)) {
    $sql .= " AND tag = ?";
    $params[] = $tag_filter;
}

if (!empty($search_filter)) {
    $sql .= " AND (site_name LIKE ? OR username LIKE ?)";
    $params[] = "%$search_filter%";
    $params[] = "%$search_filter%";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
```

### 5. Tema Sistemi
**Soru:** Cookie tabanlı tema sistemi nasıl yapılır?

**AI Yanıtı:**
```php
// Tema ayarlama
$theme = $_POST['theme'] === 'dark' ? 'dark' : 'light';
setcookie('theme', $theme, time() + (365 * 24 * 60 * 60), '/');

// JavaScript ile tema değiştirme
function toggleTheme() {
    const html = document.documentElement;
    const newTheme = html.classList.contains('dark') ? 'light' : 'dark';
    html.classList.toggle('dark');
    
    fetch('theme_handler.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'theme=' + newTheme
    });
}
```

### 6. Animasyonlu SVG İkon Değişimi
**Soru:** Header'da sürekli değişen animasyonlu SVG ikonları nasıl yapılır?

**AI Yanıtı:**
```javascript
document.addEventListener('DOMContentLoaded', function() {
    const iconElement = document.getElementById('rotating-icon');
    const icons = [
        // Anahtar (key)
        { path: "M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" },
        
        // Kutu (archive-box)
        { path: "m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" },
        
        // Kilit (lock-closed)
        { path: "M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" }
    ];
    
    let currentIndex = 0;
    
    function switchIcon() {
        // Kaybolma efekti
        iconElement.style.opacity = '0';
        iconElement.style.transform = 'scale(0.8)';
        
        setTimeout(() => {
            // İkonu değiştir
            currentIndex = (currentIndex + 1) % icons.length;
            iconElement.querySelector('path').setAttribute('d', icons[currentIndex].path);
            
            // Görünürlüğü geri getir
            iconElement.style.opacity = '1';
            iconElement.style.transform = 'scale(1)';
        }, 150);
    }
    
    // Her 3 saniyede değişim
    setInterval(switchIcon, 3000);
});
```

```css
/* CSS transition */
#rotating-icon {
    transition: all 0.3s ease-in-out;
}
```


...ve diğer sorular.
