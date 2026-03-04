<?php
session_start();
require_once('db.php');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- Обработка удаления товара ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["product_id"]) && !empty($_POST["product_id"])) {
    $product_id = $_POST["product_id"];

    // Удаляем связанные записи из basket
    $del_basket = $conn->prepare("DELETE FROM basket WHERE id_product = ?");
    $del_basket->bind_param("i", $product_id);
    $del_basket->execute();
    $del_basket->close();

    // Удаляем связанные записи из favourites
    $del_fav = $conn->prepare("DELETE FROM favourites WHERE id_product = ?");
    $del_fav->bind_param("i", $product_id);
    $del_fav->execute();
    $del_fav->close();

    // Удаляем связанные записи из order_items
    $del_order = $conn->prepare("DELETE FROM order_items WHERE id_product = ?");
    $del_order->bind_param("i", $product_id);
    $del_order->execute();
    $del_order->close();

    // Теперь удаляем товар
    $sql = "DELETE FROM products WHERE id_product = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);

    if ($stmt->execute()) {
        echo "<script>alert('Товар успешно удален!');window.location.href = '/admin.php';</script>";
        exit;
    } else {
        echo "<script>alert('Ошибка при удалении товара: " . $stmt->error . "');window.location.href = '/admin.php';</script>";
        exit;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="css/admin.css?v=3">
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
        
            <?php

// Проверяем, что форма была отправлена методом POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Получаем данные из формы
    $name = $_POST['name'];
    $description = $_POST['discription'];  // Исправлена опечатка
    $price = $_POST['price'];

    // --- Обработка первого изображения (foto) ---
    $foto1_data = null; // Инициализация
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto1_data = process_image_upload($conn, $_FILES['foto']); // Функция для обработки загрузки
        if ($foto1_data === false) {
            exit; // Прерываем выполнение, если произошла ошибка
        }
    } else {
        echo "<script>alert('Ошибка загрузки первого изображения.');window.location.href = '/admin.php';</script>";
        exit;
    }

    // --- Обработка второго изображения (foto2) ---
    $foto2_data = null; // Инициализация
    if (isset($_FILES['foto2']) && $_FILES['foto2']['error'] === UPLOAD_ERR_OK) {
        $foto2_data = process_image_upload($conn, $_FILES['foto2']);  // Функция для обработки загрузки
        if ($foto2_data === false) {
            exit; // Прерываем выполнение, если произошла ошибка
        }
    } else {
        echo "<script>alert('Ошибка загрузки второго изображения.');window.location.href = '/admin.php';</script>";
        exit;
    }


    // Экранируем данные для безопасной вставки в SQL-запрос
    $name = mysqli_real_escape_string($conn, $name);
    $description = mysqli_real_escape_string($conn, $description);
    $price = mysqli_real_escape_string($conn, $price);

    // Проверяем, что все поля заполнены
    if (empty($name) || empty($description) || empty($price)) {
        echo "<script>alert('Заполните все текстовые поля.');window.location.href = '/admin.php';</script>";
        exit;
    }

    // Создаем SQL-запрос
    $sql = "INSERT INTO products (name_product, discription_product, price, foto, foto2) VALUES ('$name', '$description', '$price', '$foto1_data', '$foto2_data')";

    // Выполняем запрос
    if ($conn->query($sql)) {
        echo "<script>alert('Товар добавлен!');window.location.href = '/admin.php';</script>";
        exit;
    } else {
        echo "<script>alert('Ошибка! Повторите попытку позже: " . $conn->error . "'); window.location.href = '/admin.php';</script>";
        exit;
    }

} 

// --- Функция для обработки загрузки изображений ---
function process_image_upload($conn, $file) {
    $file_name = $file['name'];
    $file_tmp_name = $file['tmp_name'];
    $file_size = $file['size'];
    $file_type = $file['type'];

    // Проверяем тип файла (разрешаем только изображения)
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file_type, $allowed_types)) {
        echo "<script>alert('Недопустимый тип файла. Разрешены только JPEG, PNG и GIF.');window.location.href = '/admin.php';</script>";
        return false; // Возвращаем false в случае ошибки
    }
    // Проверяем размер файла (например, не больше 2 МБ)
    if ($file_size > 2000000) {
        echo "<script>alert('Размер файла превышает 2 МБ.');window.location.href = '/admin.php';</script>";
        return false; // Возвращаем false в случае ошибки
    }

    // Читаем содержимое файла
    $image_data = file_get_contents($file_tmp_name);

    // Экранируем данные для безопасной вставки в SQL-запрос
    $image_data = mysqli_real_escape_string($conn, $image_data);

    return $image_data; // Возвращаем данные изображения
}
?>
        <div class="container">
            <div class="blok">
            <h1 class="title">Добавление товара</h1>
            <form action="" method="post" enctype="multipart/form-data">
                <p class="text1">Название</p>
                <input class="input" type="text" name="name">
                <p class="text2">Описание</p>
                <textarea class="input2" type="text" required name="discription"></textarea>
                <p class="text2">Цена</p>
                <input class="input" type="number" name="price">
                <p class="text2">Картинка</p>
                <input class="input" type="file" name="foto">
                <p class="text2">Картинка 2</p>
                <input class="input" type="file" name="foto2">
                <button class="btn" type="submit" name="btn">Добавить товар</button>
            </form>


            <h1 class="title">Удаление товара</h1>
            <form method="post">
                <select class="list" name="product_id">
                    <option class="list" value="">Выберите товар</option>
                    <?php
                    require_once('db.php');

                    $conn->query("SET NAMES utf8"); // Устанавливаем кодировку

                    $query = "SELECT id_product, name_product FROM products";
                    $results = $conn->query($query);

                    while ($row = $results->fetch_assoc()) {
                        echo "<option value='" . $row["id_product"] . "'>" . $row["name_product"] . "</option>";
                    }

                        ?>
                </select>
                <button type="submit" class="btn" onclick="return confirm('Вы точно хотите удалить этот товар?')">Удалить товар</button>
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