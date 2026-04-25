<?php
session_start();

if (isset($_SESSION['app_user_id'])) {
    header('Location: index.php');
    exit();
}

$errors = [];
$loginInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim($_POST['login'] ?? '');
    $passwordInput = $_POST['password'] ?? '';

    if (empty($loginInput) || empty($passwordInput)) {
        $errors[] = 'Заполните оба поля.';
    } else {
        // Подключение к БД
        function connectToDB() {
            static $db = null;
            if ($db === null) {
                $host = 'localhost';
                $user = 'u82316';
                $pass = '1579856';   
                $name = 'u82316';
                try {
                    $db = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass);
                    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch (PDOException $e) {
                    die("Ошибка БД: " . $e->getMessage());
                }
            }
            return $db;
        }

        $db = connectToDB();
        $stmt = $db->prepare("SELECT id, login, password_hash FROM application WHERE login = ?");
        $stmt->execute([$loginInput]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($passwordInput, $user['password_hash'])) {
            $_SESSION['app_user_id'] = $user['id'];
            $_SESSION['user_login'] = $user['login'];
            // Удаляем все куки формы, чтобы они не мешали
            $fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted', 'languages'];
            foreach ($fields as $f) {
                setcookie($f . '_err', '', 1);
                setcookie($f . '_val', '', 1);
            }
            setcookie('languages_val', '', 1);
            setcookie('contract_accepted_val', '', 1);
            setcookie('saved_ok', '', 1);
            header('Location: index.php');
            exit();
        } else {
            $errors[] = 'Неверный логин или пароль.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход – Лабораторная работа №5</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .error-message {
            background: #4a2e2e;
            color: #ff9999;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #8b3a3a;
            border-radius: 4px;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #a3bffa;
            text-decoration: none;
        }
        .back-link a:hover {
            color: #c4d0a8;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Вход в систему</h1>
    <p style="text-align:center; margin-bottom:20px;">Введите логин и пароль, которые были выданы при первой отправке формы</p>

    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $err): ?>
            <div class="error-message"><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label>Логин</label>
            <input type="text" name="login" value="<?= htmlspecialchars($loginInput) ?>" required>
        </div>
        <div class="form-group">
            <label>Пароль</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Войти</button>
    </form>

    <div class="back-link">
        <a href="index.php">← Вернуться к форме</a>
        <a href="v.php" style="margin-left:15px;">📊 Просмотреть анкеты</a>
    </div>

    <div class="auth-hint" style="text-align:center; margin-top:20px;">
        Нет аккаунта?<br>Заполните форму на главной странице — логин и пароль будут сгенерированы автоматически.
    </div>
</div>
</body>
</html>