<?php
    session_start();
    $errors = $_SESSION['reg_errors'] ?? [];
    $values = $_SESSION['reg_values'] ?? [];
    unset($_SESSION['reg_errors'], $_SESSION['reg_values']);

require_once('db.php');
if ($_SERVER["REQUEST_METHOD"] == "POST") {
$login = $_POST['login'] ?? '';
$password = $_POST['password'] ?? '';
$repeatpassword = $_POST['repeatpassword'] ?? '';
$number = $_POST['number'] ?? '';
$email = $_POST['email'] ?? '';

$errors = [
    'login' => '',
    'password' => '',
    'repeatpassword' => '',
    'number' => '',
    'email' => '',
    'common' => ''
];

// Регулярные выражения
$loginRegex = '/^[а-яА-Яa-zA-Z0-9]{6,20}$/u';
$passwordRegex = '/^.{6,}$/u';
$numberRegex = '/^.{10,20}$/u';
$emailRegex = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/u';

if (empty($login)) {
    $errors['login'] = "Заполните логин";
} elseif (!preg_match($loginRegex, $login)) {
    $errors['login'] = "Логин должен содержать от 6 до 20 символов, только буквы и цифры";
}

if (empty($password)) {
    $errors['password'] = "Заполните пароль";
} elseif (!preg_match($passwordRegex, $password)) {
    $errors['password'] = "Пароль должен содержать не менее 6 символов";
}

if (empty($repeatpassword)) {
    $errors['repeatpassword'] = "Повторите пароль";
} elseif ($password !== $repeatpassword) {
    $errors['repeatpassword'] = "Пароли не совпадают";
}

if (empty($number)) {
    $errors['number'] = "Заполните номер телефона";
} elseif (!preg_match($numberRegex, $number)) {
    $errors['number'] = "Введите корректный номер телефона";
}

if (empty($email)) {
    $errors['email'] = "Заполните email";
} elseif (!preg_match($emailRegex, $email)) {
    $errors['email'] = "Введите корректный адрес электронной почты";
}

// Проверка уникальности только если нет ошибок валидации
if (!array_filter($errors)) {
    $loginCheckSql = "SELECT COUNT(*) FROM users WHERE login = '$login'";
    $emailCheckSql = "SELECT COUNT(*) FROM users WHERE email = '$email'";
    $numberCheckSql = "SELECT COUNT(*) FROM users WHERE number = '$number'";

    $loginResult = $conn->query($loginCheckSql);
    $emailResult = $conn->query($emailCheckSql);
    $numberResult = $conn->query($numberCheckSql);

    if ($loginResult->fetch_row()[0] > 0) {
        $errors['login'] = "Пользователь с таким логином уже существует";
    }
    if ($emailResult->fetch_row()[0] > 0) {
        $errors['email'] = "Пользователь с такой почтой уже существует";
    }
    if ($numberResult->fetch_row()[0] > 0) {
        $errors['number'] = "Пользователь с таким номером телефона уже существует";
    }
}

// Если есть ошибки — сохраняем их в сессию и возвращаем значения полей
if (array_filter($errors)) {
    session_start();
    $_SESSION['reg_errors'] = $errors;
    $_SESSION['reg_values'] = [
        'login' => $login,
        'number' => $number,
        'email' => $email
    ];
    header("Location: /main_register.php");
    exit;
}

// Если всё хорошо — регистрируем пользователя
$sql = "INSERT INTO users (login, password, number, email) VALUES ('$login', '$password', '$number', '$email')";
if ($conn->query($sql)) {
    echo "<script>alert('Вы успешно зарегистрировались!'); window.location.href = '/main_authoriz.php';</script>";
    exit;
} else {
    session_start();
    $_SESSION['reg_errors'] = ['common' => "Ошибка регистрации! Повторите попытку позже."];
    echo "<script>alert('Произошла ошибка, пожалуйста повторите попытку позже!'); window.location.href = '/main_register.php';</script>";
    exit;
}
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KeyShop</title>
    <link rel="stylesheet" href="css/register.css?v=3">
</head>
<body>
    <div class="wrapper">

        <div class="header">
            <div class="header_1">
               <a href="cart.php"> <img class="backet" src="img/корзина.svg" alt="корзина"></a>
               <a href="favourites.php"> <img class="favourites" src="img/like.svg" alt="избранное"></a>
                <a href="feedbacks.php" class="feedback">Отзывы</a>
                <a class="header_auth_link" href="main_authoriz.php" id="b"><img class="header_auth" src="img/акаунт.svg" alt="Аккаунт"></a>
            </div>
            <a class="header_logo" href="index.php"><img class="header_logo_img" src="img/KeyShop.svg" alt="Логотип"></a>
            <?php
            require_once('db.php');

            // Проверка, авторизован ли пользователь и есть ли информация о пользователе в сессии
            if (isset($_SESSION['user'])) {
                $user = $_SESSION['user'];

                // Запрос к базе данных, чтобы получить роль пользователя
                // Добавлены кавычки для защиты от SQL-инъекций (хотя лучше использовать prepared statements)
                $sql = "SELECT admin FROM users WHERE login = '" . mysqli_real_escape_string($conn, $user) . "'";

                $result = $conn->query($sql);

                if ($result) { // Проверяем, что запрос выполнился успешно
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $is_admin = (bool)$row['admin']; // Преобразуем bit(1) в boolean

                        // Отображение ссылки администратора (если пользователь - администратор)
                        if ($is_admin) {
                            echo '<a class="link_admin" href="admin.php" class="btn2">Админ-панель</a>';
                        }
                    } else {
                        echo "Ошибка: Не удалось получить информацию о пользователе. Пользователь не найден.";
                    }
                } else {
                    echo "Ошибка: Ошибка при выполнении запроса: " . $conn->error; // Выводим сообщение об ошибке
                }
            } else {
                echo "";
            }
            ?>
            <div class="header_2">
                <a href="https://t.me/dyrik_xagivagi"><img class="tg" src="img/tg.svg" alt="tg"></a>
                <a href="https://vk.com/kassper07"><img class="vk" src="img/vk.svg" alt="vk"></a>
            </div>
        </div>
        

        <div class="container">
            


            <div class="blok">
                <p class="register">Регистрация</p>
                <form action="" method="post">

                    <p class="text1">Придумайте логин</p>
                    <input class="input login" type="text" name="login" value="<?= htmlspecialchars($values['login'] ?? '') ?>">
                    <div class="error errlogin"></div>
                    <?php if (!empty($errors['login'])): ?>
                        <div class="error show"><?= htmlspecialchars($errors['login']) ?></div>
                    <?php endif; ?>

                    <p class="text2">Придумайте пароль</p>
                    <input class="input password" type="text" name="password" value="<?= htmlspecialchars($values['password'] ?? '') ?>">
                    <div class="error errpassword"></div>
                    <?php if (!empty($errors['password'])): ?>
                        <div class="error show"><?= htmlspecialchars($errors['password']) ?></div>
                    <?php endif; ?>

                    <p class="text2">Повторите пароль</p>
                    <input class="input repeatpassword" type="text" name="repeatpassword" value="<?= htmlspecialchars($values['repeatpassword'] ?? '') ?>">
                    <div class="error errrpassword"></div>
                    <?php if (!empty($errors['repeatpassword'])): ?>
                        <div class="error show"><?= htmlspecialchars($errors['repeatpassword']) ?></div>
                    <?php endif; ?>

                    <p class="text2">Введите номер телефона</p>
                    <input class="input number" type="number" name="number" value="<?= htmlspecialchars($values['number'] ?? '') ?>">
                    <div class="error errnumber"></div>
                    <?php if (!empty($errors['number'])): ?>
                        <div class="error show"><?= htmlspecialchars($errors['number']) ?></div>
                    <?php endif; ?>

                    <p class="text2">Введите электронную почту</p>
                    <input class="input email" type="text" name="email" value="<?= htmlspecialchars($values['email'] ?? '') ?>">
                    <div class="error erremail"></div>
                    <?php if (!empty($errors['email'])): ?>
                        <div class="error show"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif; ?>

                    <button class="btn" type="submit">Зарегистрироваться</button>
                </form>    
            </div>


        </div>


        <div class="footer">
            <div class="footer_blok1">
                <div class="footer_blok">
                    <p class="footer_text1">KeyShop</p>
                    <p class="footer_text2">Работай и играй с удовольствием</p>
                </div>
                <div class="footer_blok">
                    <p class="footer_text1">Доставка</p>
                    <p class="footer_text2">По всему миру</p>
                    <p class="footer_text2">Гарантия и возврат</p>
                </div>
                <div class="footer_blok">
                    <p class="footer_text1">Магазин</p>
                    <p class="footer_text2">Каждый день без выходных</p>
                    <p class="footer_text2">с 10.00 до 20.00</p>
                    <p class="footer_text2">ул. Пушкина, д. 23, 3 подъезд</p>
                </div>
                <div class="footer_blok">
                    <p class="footer_text1">Поддержка</p>
                    <p class="footer_text2">Каждый день</p>
                    <p class="footer_text2">с 8.00 до 20.00</p>
                    <p class="footer_text2">Frinri@yandex.ru</p>
                </div>
                <div class="footer_blok">
                    <p class="footer_text1">Соц сети</p>
                    <!-- <a href="https://t.me/dyrik_xagivagi" class="footer_text22">Telegram</a>
                    <a href="https://vk.com/kassper07" class="footer_text22">ВКонтакте</a> -->
                    <div style="display: flex; margin-top: 16px; ">
                        <a href="https://t.me/dyrik_xagivagi" style="margin-right: 10px;"><img class="footer_tg" src="img/tg2.svg" alt="tg"></a>
                        <a href="https://vk.com/kassper07"><img class="footer_vk"  src="img/vk2.svg" alt="vk"></a>
                    </div>
                </div>
            </div>

            <div class="footer_blok2">
                <p class="footer_blok2_text">©2025 Все права защищены</p>
            </div>
        </div>
    </div>


    
</body>
</html>