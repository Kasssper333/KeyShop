<?php

session_start();
require_once('db.php');

// Устанавливаем кодировку соединения
mysqli_set_charset($conn, "utf8");

if (isset($_SESSION['user'])) {
    $user = $_SESSION["user"];

    // Получаем id_user из базы данных
    $sql = "SELECT id_user FROM users WHERE login = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user_id = (int)$row['id_user'];
        } else {
            echo "<script>alert('Ошибка: Не удалось получить ID пользователя. Пользователь не найден.'); window.location.href='main_authoriz.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Ошибка: Ошибка при подготовке запроса: " . $conn->error . "'); window.location.href='main_authoriz.php';</script>";
        exit;
    }

    // **1. Обработка добавления товара в избранное**
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id']) && !isset($_POST['delete_favourite'])) {
        $product_id = (int)$_POST['product_id'];

        // Проверяем, есть ли товар уже в избранном
        $sql_check = "SELECT * FROM favourites WHERE id_user = ? AND id_product = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $user_id, $product_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            echo "<script>alert('Товар уже добавлен в избранное.'); window.location.href='catalog.php';</script>";
        } else {
            // Добавляем товар в избранное
            $sql_insert = "INSERT INTO favourites (id_user, id_product) VALUES (?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ii", $user_id, $product_id);

            if ($stmt_insert->execute()) {
                echo "<script>alert('Товар добавлен в избранное.'); window.location.href='catalog.php';</script>";
            } else {
                echo "<script>alert('Ошибка: Не удалось добавить товар в избранное.');</script>";
            }
        }
    }

    // **2. Обработка удаления товара из избранного**
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_favourite'])) {
        $favourite_id = isset($_POST['favourite_id']) ? (int)$_POST['favourite_id'] : 0;

        if ($favourite_id <= 0) {
            echo "<script>alert('Ошибка: Неверный ID избранного.'); window.location.href='favourites.php';</script>";
            exit;
        }

        // Удаляем товар из избранного
        $sql_delete = "DELETE FROM favourites WHERE id_favourite = ? AND id_user = ?";
        $stmt = $conn->prepare($sql_delete);

        if ($stmt) {
            $stmt->bind_param("ii", $favourite_id, $user_id);
            if ($stmt->execute()) {
                echo "<script>alert('Товар удален из избранного.'); window.location.href='favourites.php';</script>";
                exit;
            } else {
                echo "<script>alert('Ошибка удаления товара: " . $stmt->error . "');</script>";
            }
        } else {
            echo "<script>alert('Ошибка: Ошибка при подготовке запроса: " . $conn->error . "');</script>";
        }
    }

    // **3. Получение данных избранного пользователя**
    $sql = "
        SELECT
            f.id_favourite,
            f.id_product,
            p.name_product AS product_name,
            p.price AS product_price,
            p.foto AS foto
        FROM favourites f
        INNER JOIN products p ON f.id_product = p.id_product
        WHERE f.id_user = ?
        ORDER BY f.id_favourite DESC
    ";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $favourite_items = array();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $favourite_items[] = $row;
            }
        }

        // Освобождаем ресурсы
        $stmt->close();
    } else {
        echo "<script>alert('Ошибка: Ошибка при подготовке запроса: " . $conn->error . "');</script>";
    }

    // --- HTML код страницы избранного ---
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Избранное</title>
        <link rel="stylesheet" href="css/favourites.css?v=3">
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
            <h2 class="page_title">Избранное</h2>
            <div class="pod_container">
                <?php if (count($favourite_items) > 0): ?>
                    <?php foreach ($favourite_items as $item): ?>
                        <div class="favourite-item">
                        <div class="favourite-item_p">
                            <?php
                                // Загрузка фото
                                $image_data = $item['foto'];
                                $image_type = @exif_imagetype('data://image/jpeg;base64,' . base64_encode($image_data));
                                if ($image_type !== false) {
                                    $image_mime_type = image_type_to_mime_type($image_type);
                                } else {
                                    $image_mime_type = 'image/jpeg';
                                }

                                $show_img = base64_encode($image_data);
                                echo '<img class="img" src="data:' . $image_mime_type . ';base64,' . $show_img . '" alt="Фото товара">';
                            ?>
                            <div>
                                <div class="favourite-item-details">

                                    <h3>
                                        <a class="name" href="card_product.php?id_product=<?php echo htmlspecialchars($item['id_product']); ?>">
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                        </a>
                                    </h3>
                                    <p class="price">Цена: <?php echo htmlspecialchars($item['product_price']); ?> руб.</p>
                                </div>
                                <div class="favourite-item-actions">
                                    <form method="post" action="favourites.php">
                                        <input type="hidden" name="favourite_id" value="<?php echo htmlspecialchars($item['id_favourite']); ?>">
                                        <button class="del" type="submit" name="delete_favourite">Удалить</button>
                                    </form>


                                </div>
                            </div>
                        </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty_favourites">Список избранного пуст.</p>
                <?php endif; ?>
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

    
    <?php
} else {
    echo "<script>alert('Пожалуйста, сначала авторизуйтесь.'); window.location.href='main_authoriz.php';</script>";
    exit;
}
?>