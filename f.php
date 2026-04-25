<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анкета – Лабораторная работа №5</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Дополнительные стили для элементов, которых нет в style.css */
        .error-message, .success-message {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 4px;
            text-align: center;
        }
        .error-message {
            background: #4a2e2e;
            color: #ff9999;
            border: 1px solid #8b3a3a;
        }
        .success-message {
            background: #2e4a2e;
            color: #c4d0a8;
            border: 1px solid #4a7043;
        }
        .credentials {
            background: #1f1f1f;
            border: 2px solid #4a7043;
            padding: 15px;
            margin: 15px 0;
            text-align: center;
            font-family: monospace;
        }
        .logged-in {
            background: #1f1f1f;
            padding: 12px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #4a7043;
        }
        .bottom-links {
            margin-top: 30px;
            text-align: center;
            border-top: 2px solid #3a3a3a;
            padding-top: 20px;
        }
        .bottom-links a {
            color: #a3bffa;
            text-decoration: none;
            margin: 0 15px;
            font-weight: bold;
        }
        .bottom-links a:hover {
            color: #c4d0a8;
        }
        .auth-hint {
            text-align: center;
            margin-top: 15px;
            font-size: 0.85rem;
            color: #888;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Анкета</h1>

    <?php if ($userLogged): ?>
        <div class="logged-in">
            ✅ Вы авторизованы (ID: <?= htmlspecialchars($currentUserId) ?>)
            <a href="index.php?logout=1" style="color:#a3bffa; margin-left:15px;">Выйти</a>
        </div>
    <?php endif; ?>

    <!-- Вывод сообщений (ошибки, успех, логин/пароль) -->
    <?php if (!empty($msgList)): ?>
        <?php foreach ($msgList as $msg): ?>
            <?= $msg ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="post" action="index.php">
        <div class="form-group">
            <label for="full_name">ФИО</label>
            <input type="text" id="full_name" name="full_name"
                   value="<?= htmlspecialchars($fieldValues['full_name'] ?? '') ?>"
                   <?= !empty($fieldErrors['full_name']) ? 'class="error"' : '' ?>>
            <?php if (!empty($fieldErrors['full_name'])): ?>
                <span class="field-error">ФИО обязательно и должно содержать только буквы и пробелы.</span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="phone">Телефон</label>
            <input type="tel" id="phone" name="phone"
                   value="<?= htmlspecialchars($fieldValues['phone'] ?? '') ?>"
                   <?= !empty($fieldErrors['phone']) ? 'class="error"' : '' ?>>
            <?php if (!empty($fieldErrors['phone'])): ?>
                <span class="field-error">6–12 цифр, разрешены +, -, (, ), пробел.</span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($fieldValues['email'] ?? '') ?>"
                   <?= !empty($fieldErrors['email']) ? 'class="error"' : '' ?>>
            <?php if (!empty($fieldErrors['email'])): ?>
                <span class="field-error">Введите корректный email.</span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="birth_date">Дата рождения</label>
            <input type="date" id="birth_date" name="birth_date"
                   value="<?= htmlspecialchars($fieldValues['birth_date'] ?? '') ?>"
                   <?= !empty($fieldErrors['birth_date']) ? 'class="error"' : '' ?>>
            <?php if (!empty($fieldErrors['birth_date'])): ?>
                <span class="field-error">Формат ГГГГ-ММ-ДД, не позже сегодня.</span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Пол</label>
            <div class="radio-group">
                <label>
                    <input type="radio" name="gender" value="male"
                        <?= ($fieldValues['gender'] ?? '') === 'male' ? 'checked' : '' ?>
                        <?= !empty($fieldErrors['gender']) ? 'class="error"' : '' ?>>
                    Мужской
                </label>
                <label>
                    <input type="radio" name="gender" value="female"
                        <?= ($fieldValues['gender'] ?? '') === 'female' ? 'checked' : '' ?>
                        <?= !empty($fieldErrors['gender']) ? 'class="error"' : '' ?>>
                    Женский
                </label>
            </div>
            <?php if (!empty($fieldErrors['gender'])): ?>
                <span class="field-error">Выберите пол.</span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="languages">Любимые языки программирования (выберите один или несколько)</label>
            <select id="languages" name="languages[]" multiple size="6"
                    <?= !empty($fieldErrors['languages']) ? 'class="error"' : '' ?>>
                <?php foreach ($languagesList as $lang): ?>
                    <option value="<?= htmlspecialchars($lang) ?>" <?= in_array($lang, $fieldValues['languages'] ?? []) ? 'selected' : '' ?>><?= htmlspecialchars($lang) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($fieldErrors['languages'])): ?>
                <span class="field-error">Выберите хотя бы один язык.</span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="biography">Биография</label>
            <textarea id="biography" name="biography" rows="5"
                <?= !empty($fieldErrors['biography']) ? 'class="error"' : '' ?>><?= htmlspecialchars($fieldValues['biography'] ?? '') ?></textarea>
            <?php if (!empty($fieldErrors['biography'])): ?>
                <span class="field-error">Максимум 10000 символов.</span>
            <?php endif; ?>
        </div>

        <div class="form-group checkbox">
            <label>
                <input type="checkbox" name="contract_accepted" value="1"
                    <?= !empty($fieldValues['contract_accepted']) ? 'checked' : '' ?>
                    <?= !empty($fieldErrors['contract_accepted']) ? 'class="error"' : '' ?>>
                Я ознакомлен(а) с контрактом
            </label>
            <?php if (!empty($fieldErrors['contract_accepted'])): ?>
                <span class="field-error">Необходимо подтвердить согласие.</span>
            <?php endif; ?>
        </div>

        <button type="submit"><?= $userLogged ? 'Сохранить изменения' : 'Сохранить' ?></button>
    </form>

    <div class="bottom-links">
        <a href="login.php">🔑 Войти (если уже есть логин/пароль)</a>
        <a href="v.php">📊 Просмотреть сохранённые анкеты</a>
        <a href="panel.php">👨‍💻 Админ-меню</a>
    </div>

    <?php if (!$userLogged): ?>
        <div class="auth-hint">
            <small>Для редактирования данных нужна авторизация</small>
        </div>
    <?php endif; ?>
</div>
</body>
</html>