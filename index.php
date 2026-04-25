<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

// Определяем авторизацию
$userLogged = isset($_SESSION['app_user_id']);
$currentUserId = $userLogged ? $_SESSION['app_user_id'] : null;

// Выход
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

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

// Генерация уникального логина
function makeUniqueLogin($db) {
    do {
        $login = 'user_' . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 8);
        $stmt = $db->prepare("SELECT id FROM application WHERE login = ?");
        $stmt->execute([$login]);
    } while ($stmt->fetch());
    return $login;
}

// Генерация случайного пароля
function makeRandomPassword($len = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($chars), 0, $len);
}

// Белые списки
$validLanguages = [
    'Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python',
    'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'
];
$validGenders = ['male', 'female'];

// ====================== GET ======================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $msgList = [];
    $fieldErrors = [];
    $fieldValues = [];

    $allFields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted', 'languages'];

    if (!$userLogged) {
        // Неавторизованный – читаем cookies
        foreach ($allFields as $f) {
            $fieldErrors[$f] = !empty($_COOKIE[$f . '_err']);
        }
        if ($fieldErrors['full_name']) $msgList[] = '<div class="error-message">ФИО должно содержать только буквы и пробелы (макс. 150 символов).</div>';
        if ($fieldErrors['phone']) $msgList[] = '<div class="error-message">Телефон должен содержать от 6 до 12 цифр, допускаются +, -, (, ), пробел.</div>';
        if ($fieldErrors['email']) $msgList[] = '<div class="error-message">Введите корректный email.</div>';
        if ($fieldErrors['birth_date']) $msgList[] = '<div class="error-message">Дата рождения: формат ГГГГ-ММ-ДД, не позже сегодня.</div>';
        if ($fieldErrors['gender']) $msgList[] = '<div class="error-message">Выберите пол.</div>';
        if ($fieldErrors['biography']) $msgList[] = '<div class="error-message">Биография не более 10000 символов.</div>';
        if ($fieldErrors['contract_accepted']) $msgList[] = '<div class="error-message">Необходимо подтвердить согласие.</div>';
        if ($fieldErrors['languages']) $msgList[] = '<div class="error-message">Выберите хотя бы один язык программирования.</div>';

        foreach ($allFields as $f) {
            $fieldValues[$f] = empty($_COOKIE[$f . '_val']) ? '' : $_COOKIE[$f . '_val'];
        }
        if (!empty($_COOKIE['languages_val'])) {
            $fieldValues['languages'] = explode(',', $_COOKIE['languages_val']);
        } else {
            $fieldValues['languages'] = [];
        }
        $fieldValues['contract_accepted'] = !empty($_COOKIE['contract_accepted_val']);

        // Сообщение об успешном сохранении новой анкеты
        if (!empty($_COOKIE['saved_ok'])) {
            setcookie('saved_ok', '', 1);
            $msgList[] = '<div class="success-message">Данные успешно сохранены!</div>';
        }
        // Показ сгенерированных логина/пароля
        if (!empty($_COOKIE['temp_login']) && !empty($_COOKIE['temp_pass'])) {
            $tmpLogin = $_COOKIE['temp_login'];
            $tmpPass = $_COOKIE['temp_pass'];
            setcookie('temp_login', '', 1);
            setcookie('temp_pass', '', 1);
            $msgList[] = '<div class="credentials">
                <strong>Форма успешно отправлена!</strong><br>
                Ваш логин: <strong>' . htmlspecialchars($tmpLogin) . '</strong><br>
                Ваш пароль: <strong>' . htmlspecialchars($tmpPass) . '</strong><br>
                <small>Сохраните их! Они больше никогда не будут показаны.</small>
            </div>';
        }
    } else {
        // Авторизованный – загружаем данные из БД
        $db = connectToDB();
        $stmt = $db->prepare("SELECT * FROM application WHERE id = ?");
        $stmt->execute([$currentUserId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($userData) {
            $fieldValues['full_name'] = $userData['full_name'];
            $fieldValues['phone'] = $userData['phone'];
            $fieldValues['email'] = $userData['email'];
            $fieldValues['birth_date'] = $userData['birth_date'];
            $fieldValues['gender'] = $userData['gender'];
            $fieldValues['biography'] = $userData['biography'];
            $fieldValues['contract_accepted'] = (bool)$userData['contract_accepted'];

            $langStmt = $db->prepare("
                SELECT l.name FROM application_language al 
                JOIN language l ON al.language_id = l.id 
                WHERE al.application_id = ?
            ");
            $langStmt->execute([$currentUserId]);
            $fieldValues['languages'] = $langStmt->fetchAll(PDO::FETCH_COLUMN);
            $msgList[] = '<div class="success-message">Вы вошли как ' . htmlspecialchars($_SESSION['user_login']) . '. Вы можете редактировать свои данные.</div>';
        } else {
            session_destroy();
            header('Location: login.php');
            exit();
        }
    }

    // Список языков для выпадающего списка
    $db = connectToDB();
    $languagesList = $db->query("SELECT name FROM language ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($languagesList)) $languagesList = $validLanguages;

    include 'f.php';
    exit();
}

// ====================== POST ======================
else {
    $hasError = false;

    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birthDate = trim($_POST['birth_date'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $biography = trim($_POST['biography'] ?? '');
    $contractAccepted = isset($_POST['contract_accepted']) ? 1 : 0;
    $languages = $_POST['languages'] ?? [];

    // === ВАЛИДАЦИЯ ===
    if (empty($fullName) || !preg_match('/^[а-яА-Яa-zA-Z\s]+$/u', $fullName) || strlen($fullName) > 150) {
        setcookie('full_name_err', '1', time() + 86400);
        $hasError = true;
    }
    setcookie('full_name_val', $fullName, time() + 2592000);

    if (empty($phone) || !preg_match('/^[\d\s\-\+\(\)]{6,12}$/', $phone)) {
        setcookie('phone_err', '1', time() + 86400);
        $hasError = true;
    }
    setcookie('phone_val', $phone, time() + 2592000);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setcookie('email_err', '1', time() + 86400);
        $hasError = true;
    }
    setcookie('email_val', $email, time() + 2592000);

    if (empty($birthDate)) {
        setcookie('birth_date_err', '1', time() + 86400);
        $hasError = true;
    } else {
        $dateObj = DateTime::createFromFormat('Y-m-d', $birthDate);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $birthDate || $dateObj > new DateTime('today')) {
            setcookie('birth_date_err', '1', time() + 86400);
            $hasError = true;
        }
    }
    setcookie('birth_date_val', $birthDate, time() + 2592000);

    if (empty($gender) || !in_array($gender, $validGenders)) {
        setcookie('gender_err', '1', time() + 86400);
        $hasError = true;
    }
    setcookie('gender_val', $gender, time() + 2592000);

    if (strlen($biography) > 10000) {
        setcookie('biography_err', '1', time() + 86400);
        $hasError = true;
    }
    setcookie('biography_val', $biography, time() + 2592000);

    if (!$contractAccepted) {
        setcookie('contract_accepted_err', '1', time() + 86400);
        $hasError = true;
    }
    setcookie('contract_accepted_val', $contractAccepted ? '1' : '0', time() + 2592000);

    if (empty($languages)) {
        setcookie('languages_err', '1', time() + 86400);
        $hasError = true;
    } else {
        foreach ($languages as $lang) {
            if (!in_array($lang, $validLanguages)) {
                setcookie('languages_err', '1', time() + 86400);
                $hasError = true;
                break;
            }
        }
    }
    setcookie('languages_val', implode(',', $languages), time() + 2592000);

    if ($hasError) {
        header('Location: index.php');
        exit();
    }

    // === СОХРАНЕНИЕ В БД ===
    try {
        $db = connectToDB();
        $db->beginTransaction();

        if ($userLogged) {
            // Обновление
            $stmt = $db->prepare("
                UPDATE application 
                SET full_name = :fn, phone = :ph, email = :em, birth_date = :bd,
                    gender = :gd, biography = :bio, contract_accepted = :ca
                WHERE id = :id
            ");
            $stmt->execute([
                ':fn' => $fullName, ':ph' => $phone, ':em' => $email, ':bd' => $birthDate,
                ':gd' => $gender, ':bio' => $biography, ':ca' => $contractAccepted, ':id' => $currentUserId
            ]);
            $appId = $currentUserId;
            $db->prepare("DELETE FROM application_language WHERE application_id = ?")->execute([$appId]);
            setcookie('updated_ok', '1', time() + 86400);
        } else {
            // Новая анкета
            $login = makeUniqueLogin($db);
            $plainPass = makeRandomPassword();
            $passHash = password_hash($plainPass, PASSWORD_DEFAULT);

            $stmt = $db->prepare("
                INSERT INTO application 
                (full_name, phone, email, birth_date, gender, biography, contract_accepted, login, password_hash)
                VALUES (:fn, :ph, :em, :bd, :gd, :bio, :ca, :lg, :phash)
            ");
            $stmt->execute([
                ':fn' => $fullName, ':ph' => $phone, ':em' => $email, ':bd' => $birthDate,
                ':gd' => $gender, ':bio' => $biography, ':ca' => $contractAccepted,
                ':lg' => $login, ':phash' => $passHash
            ]);
            $appId = $db->lastInsertId();

            setcookie('temp_login', $login, time() + 3600);
            setcookie('temp_pass', $plainPass, time() + 3600);
            setcookie('saved_ok', '1', time() + 86400);
        }

        // Сохранение языков
        $langIdMap = [];
        $stmt = $db->query("SELECT id, name FROM language");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $langIdMap[$row['name']] = $row['id'];
        }
        $ins = $db->prepare("INSERT INTO application_language (application_id, language_id) VALUES (?, ?)");
        foreach ($languages as $langName) {
            if (isset($langIdMap[$langName])) {
                $ins->execute([$appId, $langIdMap[$langName]]);
            }
        }

        $db->commit();

        // Удаляем куки ошибок
        $fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted', 'languages'];
        foreach ($fields as $f) {
            setcookie($f . '_err', '', 1);
        }

        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        $db->rollBack();
        setcookie('db_error', '1', time() + 86400);
        header('Location: index.php');
        exit();
    }
}