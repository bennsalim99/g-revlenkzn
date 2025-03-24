<?php
require_once 'config.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username']);
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    $errors = [];
    
    // Validasyonlar
    if (empty($username)) {
        $errors[] = "Kullanıcı adı boş olamaz";
    }
    if (empty($email)) {
        $errors[] = "E-posta boş olamaz";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi girin";
    }
    if (empty($password)) {
        $errors[] = "Şifre boş olamaz";
    }
    if (strlen($password) < 6) {
        $errors[] = "Şifre en az 6 karakter olmalıdır";
    }
    if ($password !== $password_confirm) {
        $errors[] = "Şifreler eşleşmiyor";
    }
    
    // Kullanıcı adı ve e-posta kontrolü
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Bu kullanıcı adı zaten kullanılıyor";
        }

        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Bu e-posta adresi zaten kullanılıyor";
        }
    }
    
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashed_password]);
            
            $_SESSION['success_message'] = "Kayıt başarılı! Şimdi giriş yapabilirsiniz.";
            header('Location: login.php');
            exit();
        } catch (PDOException $e) {
            $errors[] = "Bir hata oluştu: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Görev Yap Kazan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #6366f1;
            --background-color: #f3f4f6;
            --text-color: #1f2937;
            --card-bg: #ffffff;
        }

        [data-theme="dark"] {
            --primary-color: #6366f1;
            --secondary-color: #4f46e5;
            --background-color: #1f2937;
            --text-color: #f3f4f6;
            --card-bg: #374151;
        }

        [data-theme="red"] {
            --primary-color: #dc2626;
            --secondary-color: #b91c1c;
            --background-color: #fee2e2;
            --text-color: #7f1d1d;
            --card-bg: #ffffff;
        }

        [data-theme="blue"] {
            --primary-color: #2563eb;
            --secondary-color: #1d4ed8;
            --background-color: #dbeafe;
            --text-color: #1e3a8a;
            --card-bg: #ffffff;
        }

        [data-theme="green"] {
            --primary-color: #059669;
            --secondary-color: #047857;
            --background-color: #d1fae5;
            --text-color: #064e3b;
            --card-bg: #ffffff;
        }

        [data-theme="yellow"] {
            --primary-color: #d97706;
            --secondary-color: #b45309;
            --background-color: #fef3c7;
            --text-color: #92400e;
            --card-bg: #ffffff;
        }

        body {
            background: linear-gradient(135deg, var(--background-color) 0%, var(--background-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-color);
        }

        .register-container {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            margin: 2rem;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2.5rem;
            transform: rotate(-5deg);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .form-floating {
            margin-bottom: 1rem;
        }

        .form-floating .form-control {
            background-color: var(--card-bg);
            border: 2px solid var(--primary-color);
            color: var(--text-color);
        }

        .form-floating .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(var(--primary-color), 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            padding: 1rem;
            font-weight: 600;
            width: 100%;
            margin-top: 1rem;
        }

        .theme-switcher {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
        }

        .theme-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 2px solid white;
            margin: 0 5px;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .theme-btn:hover {
            transform: scale(1.2);
        }
    </style>
</head>
<body>
    <div class="theme-switcher">
        <button class="theme-btn" style="background: #1f2937" onclick="setTheme('dark')" title="Koyu Tema"></button>
        <button class="theme-btn" style="background: #dc2626" onclick="setTheme('red')" title="Kırmızı Tema"></button>
        <button class="theme-btn" style="background: #2563eb" onclick="setTheme('blue')" title="Mavi Tema"></button>
        <button class="theme-btn" style="background: #059669" onclick="setTheme('green')" title="Yeşil Tema"></button>
        <button class="theme-btn" style="background: #d97706" onclick="setTheme('yellow')" title="Sarı Tema"></button>
        <button class="theme-btn" style="background: #f3f4f6" onclick="setTheme('light')" title="Açık Tema"></button>
    </div>

    <div class="register-container">
        <div class="register-header">
            <div class="icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1>Kayıt Ol</h1>
            <p>Görev Yap Kazan'a hoş geldiniz!</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="needs-validation" novalidate>
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" placeholder="Kullanıcı Adı" required>
                <label for="username">Kullanıcı Adı</label>
            </div>

            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="email" name="email" placeholder="E-posta" required>
                <label for="email">E-posta</label>
            </div>

            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="Şifre" required>
                <label for="password">Şifre</label>
            </div>

            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Şifre Tekrar" required>
                <label for="password_confirm">Şifre Tekrar</label>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-user-plus me-2"></i>Kayıt Ol
            </button>
        </form>

        <div class="text-center mt-4">
            <p>Zaten hesabınız var mı? <a href="login.php">Giriş Yap</a></p>
        </div>
    </div>

    <script>
        // Tema değiştirme fonksiyonu
        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
        }

        // Sayfa yüklendiğinde kaydedilmiş temayı uygula
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'light';
            setTheme(savedTheme);
        });

        // Form doğrulama
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html> 