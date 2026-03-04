<?php
 
session_start();
require_once('db.php');

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Пожалуйста, сначала авторизуйтесь, чтобы написать отзыв.'); window.location.href='main_authoriz.php';</script>";
    exit;
}

// Получаем id пользователя по логину
$user_login = $_SESSION['user'];
$stmt = $conn->prepare("SELECT id_user FROM users WHERE login = ?");
$stmt->bind_param("s", $user_login);
$stmt->execute();
$stmt->bind_result($id_user);
$stmt->fetch();
$stmt->close();

// Обработка отправки формы
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name_user = trim($_POST['name_user']);
    $text = trim($_POST['text']);

    if (empty($name_user) || empty($text)) {
        echo "<script>alert('Заполните все поля');window.location.href = '/write_feedback.php';</script>";
        exit;
    } else {
        $sql = "INSERT INTO feedbacks (name_user, text_feedback, id_user) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $name_user, $text, $id_user);
        if ($stmt->execute()) {
            echo "<script>alert('Отзыв добавлен!');window.location.href = '/feedbacks.php';</script>";
            exit;
        } else {
            echo "<script>alert('Ошибка! " . $stmt->error . "'); window.location.href = '/write_feedback.php';</script>";
            exit;
        }
    }
}



?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KeyShop</title>
    <link rel="stylesheet" href="css/write_feedback.css?v=4">
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
                <p class="text">Напишите отзыв</p>
                    <form action="" method="post">
                        <p class="text1">Ваше имя</p>
                        <input class="input" type="text" name="name_user" value="<?php echo htmlspecialchars($_SESSION['user']); ?>" required>
                        <p class="text2">Текст отзыва</p>
                        <textarea class="input2" name="text" required></textarea>
                        <button class="btn" type="submit">Оставить отзыв</button>
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


