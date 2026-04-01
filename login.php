<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, name, password, role FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                header('Location: ' . url('dashboard.php'));
                exit;
            }
            $error = 'Неверный email или пароль';
        } catch (PDOException $e) {
            $error = 'Ошибка входа';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Вход — CRM</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            font-size: 16px;
            line-height: 1.5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: #f5f5f5;
        }
        .form-card {
            width: 100%;
            max-width: 360px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 2rem;
        }
        h1 { font-size: 1.5rem; margin-bottom: 1.5rem; text-align: center; color: #333; }
        .message { padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; }
        .message.error { background: #fee; color: #c00; }
        label { display: block; margin-bottom: 0.25rem; font-weight: 500; color: #444; }
        input {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        input:focus { outline: none; border-color: #333; }
        button {
            width: 100%;
            padding: 0.875rem;
            background: #333;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
        }
        button:hover { background: #555; }
        .link { text-align: center; margin-top: 1.5rem; }
        .link a { color: #333; text-decoration: none; }
        .link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="form-card">
        <h1>Вход</h1>
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Войти</button>
        </form>
        <div class="link">
            <a href="<?= url('register.php') ?>">Нет аккаунта? Зарегистрироваться</a>
        </div>
    </div>
</body>
</html>
