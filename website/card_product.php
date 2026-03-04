<?php
session_start();
require_once('db.php'); // Подключение к базе данных

// Получаем ID товара из параметра URL
if (isset($_GET['id_product']) && is_numeric($_GET['id_product'])) {
    $id_product = (int)$_GET['id_product']; // Преобразуем к integer для безопасности

    // Запрос к базе данных для получения информации о товаре
    $sql = "SELECT * FROM products WHERE id_product = $id_product";
    $result = $conn->query($sql);

    if ($result) { // Проверка на успешное выполнение запроса
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $name_product = htmlspecialchars($row['name_product']);
            $description_product = htmlspecialchars($row['discription_product']);
            $price = $row['price'];


            $image_data = $row['foto2'];
            $image_type = @exif_imagetype('data://image/jpeg;base64,' . base64_encode($image_data)); //Подавляем ошибки @
            if ($image_type !== false) { //Проверяем что image_type действительно определился
                $image_mime_type = image_type_to_mime_type($image_type);
            } else {
                $image_mime_type = 'image/jpeg'; // MIME-тип по умолчанию
            }
            $show_img = base64_encode($image_data);?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KeyShop</title>
    <link rel="stylesheet" href="css/card_product.css?v=3">
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

          <?php  echo " 
                        <img class='img' src='data:" . $image_mime_type . ";base64," . $show_img . "' alt='Фото товара'>
                        <div class='blok'>
                            <h1 class='name' >$name_product</h1>
                            <p class='price'>Цена: $price руб.</p>
                            <p class='discr'>$description_product</p>
                        ";
            ?>

                <form method="post" action="cart.php">
                    <input type="hidden" name="product_id" value="<?php echo $id_product; ?>">
                    <div style="display:flex;">
                        <p class="amount">Количество: </p>
                        <input class="amount_enter" type="number" name="amount" value="1" min="1">
                    </div>
                    <div class="two_btns" >
                    <button class="btn_cart" type="submit" name="add_to_cart">В корзину</button>
                </form>

                <!-- Форма для немедленного заказа одного товара -->
                <form method="post" action="favourites.php">
                    <input type="hidden" name="product_id" value="<?php echo $id_product; ?>">
                    <input class="amount_enter" type="hidden" name="amount" value="1">
                    <button class="btn_del" type="submit" name="order_now">В избранное</button>
                    </div>
                </form>
                        </div>
            </div>

      <?php  } else {
            echo "<p>Товар не найден.</p>";
        }
    } else {
        echo "<p>Ошибка при выполнении запроса: " . $conn->error . "</p>"; // Выводим ошибку SQL
    }
} else {
    echo "<p>Неверный ID товара.</p>";
    if(isset($_GET['id_product'])){
    } else {
        echo "<p>Параметр id_product отсутствует в URL.</p>";
    }
}
?>





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