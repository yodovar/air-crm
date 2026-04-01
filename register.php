<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'courier';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Заполните все поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } elseif (!in_array($role, ['admin', 'courier'])) {
        $error = 'Некорректная роль';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email уже зарегистрирован';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $email, $hashedPassword, $role]);
                $success = 'Регистрация успешна. Войдите в систему.';
            }
        } catch (PDOException $e) {
            $error = 'Ошибка регистрации';
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
    <title>Регистрация — CRM</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
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
        h1 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
            color: #333;
        }
        .message {
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .message.error {
            background: #fee;
            color: #c00;
        }
        .message.success {
            background: #efe;
            color: #060;
        }
        label {
            display: block;
            margin-bottom: 0.25rem;
            font-weight: 500;
            color: #444;
        }
        input, select {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #333;
        }
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
        button:hover {
            background: #555;
        }
        .link {
            text-align: center;
            margin-top: 1.5rem;
        }
        .link a {
            color: #333;
            text-decoration: none;
        }
        .link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="form-card">
        <h1>Регистрация</h1>
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="message success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="name">Имя</label>
            <input type="text" id="name" name="name" required
                value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" required minlength="6">

            <label for="role">Роль</label>
            <select id="role" name="role">
                <option value="courier" <?= ($_POST['role'] ?? '') === 'courier' ? 'selected' : '' ?>>Курьер</option>

            </select>

            <button type="submit">Зарегистрироваться</button>
        </form>
        <div class="link">
            <a href="<?= url('login.php') ?>">Уже есть аккаунт? Войти</a>
        </div>
    </div>
</body>
</html>
