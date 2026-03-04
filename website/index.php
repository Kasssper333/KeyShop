<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KeyShop</title>
    <link rel="stylesheet" href="css/index.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="js/wow.min.js"></script>
    <script>new WOW().init();</script>
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
            <div class="blok1">
                
                <div class="blok1_text">
                    <p class="blok1_logo">Keyshop</p>
                    <p class="blok1_text1">Работайте и играйте</p>
                    <p class="blok1_text2">с удовольствием</p>
                    <form action="catalog.php" method="GET">
                        <button type="submit" class="blok1_btn">Каталог товаров</button>
                    </form>
                </div>
                <img class="blok1_img" src="img/фон6.png" alt="картинка">
            </div>




            <div class="blok2">

                <div class="animate__animated animate__fadeInUp wow blok2_box1" style="background-image: url('img/фон1.svg');">
                    <div class="blok2_box11">
                        <p class="blok2_text1">Быстрая доставка</p>
                        <p class="blok2_text2">Доставим быстро и бережно! Отправляем заказы каждый день! Ваша клавиатура уже в пути к вам!</p>
                    </div>
                </div>

                <div class="animate__animated animate__fadeInUp wow blok2_box2" style="background-image: url('img/фон2.svg');">
                    <div class="blok2_box22">
                        <p class="blok2_text1">Гарантия</p>
                        <p class="blok2_text2">Покупая у нас, вы получаете гарантию на 6 мес. от производственных дефектов. Мы отвечаем за качество!</p>
                    </div>
                </div>

                <div class="animate__animated animate__fadeInUp wow blok2_box3" style="background-image: url('img/фон3.svg');">
                    <div class="blok2_box33">
                        <p class="blok2_text1">Поддержка клиентов</p>
                        <p class="blok2_text2">У вас вопрос? Мы всегда рады помочь!</p>
                    </div>
                </div>

                <div class="animate__animated animate__fadeInUp wow blok2_box4" style="background-image: url('img/фон4.svg');">
                    <div class="blok2_box44">
                        <p class="blok2_text1">Качество</p>
                        <p class="blok2_text2">Качество, которое вы почувствуете с первого прикосновения.</p>
                    </div>
                </div>

            </div>

                
        


            <div class="blok3">
                <form action="catalog.php" method="GET">
                    <button type="submit" class="blok3_btn">Каталог товаров</button>
                </form>
                <div class="blok3_box animate__animated animate__fadeInUp wow">
                    <div class="blok3_texts">
                        <p class="blok3_text1">Наш магазин в Москве</p>
                        <p class="blok3_text2">ул. Пушкина, д. 23, 3 подъезд</p>
                        <p class="blok3_text3">График работы</p>
                        <p class="blok3_text4">без выходных с 10.00 до 20.00</p>
                        <p class="blok3_text3">Контакты</p>
                        <p class="blok3_text4 t4">+79504562978 +79508262475</p>
                    </div>
                    <img class="blok3_img animate__animated animate__fadeInUp wow"  src="img/shop.svg" alt="Магазин">
                </div>
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