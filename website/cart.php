<?php

session_start();
require_once('db.php');

// Функция для проверки, является ли пользователь администратором (чтобы не дублировать код)
function isUserAdmin($conn, $user) {
    $sql = "SELECT admin FROM users WHERE login = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (bool)$row['admin'];
        } else {
            return false; // Пользователь не найден или нет прав админа
        }
    } else {
        return false; // Ошибка при подготовке запроса
    }
}

// Устанавливаем кодировку соединения
mysqli_set_charset($conn, "utf8");

if (isset($_SESSION['user'])) {
    $user = $_SESSION["user"];

    // Получаем id_user из базы данных с использованием подготовленных выражений
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

    // **2. Обработка обновления количества товара**
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_cart'])) {
        $basket_id = isset($_POST['basket_id']) ? (int)$_POST['basket_id'] : 0;
        $new_amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 1;

        if ($basket_id <= 0) {
            echo "<script>alert('Ошибка: Неверный ID корзины.'); window.location.href='cart.php';</script>";
            exit;
        }
        if ($new_amount < 1) {
            $new_amount = 1;
        }

        // Обновляем количество товара с использованием подготовленных выражений
        $sql_update = "UPDATE basket SET amount = ? WHERE id_basket = ? AND id_user = ?";
        $stmt = $conn->prepare($sql_update);

        if ($stmt) {
            $stmt->bind_param("iii", $new_amount, $basket_id, $user_id);
            if ($stmt->execute()) {
                // Количество успешно обновлено.  Перезагружаем страницу для отображения изменений
                echo "<script>window.location.href='cart.php';</script>";
                exit;
            } else {
                echo "<script>alert('Ошибка обновления количества: " . $stmt->error . "');</script>";
            }
        } else {
            echo "<script>alert('Ошибка: Ошибка при подготовке запроса: " . $conn->error . "');</script>";
        }
    }

    // **1. Обработка добавления товара в корзину**
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 1;

        if ($product_id <= 0) {
            echo "<script>alert('Ошибка: Неверный ID товара.'); window.location.href='cart.php';</script>";
            exit;
        }

        // Проверка, есть ли уже товар в корзине с использованием подготовленных выражений
        $sql = "SELECT id_basket, amount FROM basket WHERE id_user = ? AND id_product = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result) {
                if ($row = $result->fetch_assoc()) {
                    // Товар уже есть в корзине, обновляем количество
                    $new_amount = $row['amount'] + $amount;
                    $basket_id = $row['id_basket'];

                    $sql_update = "UPDATE basket SET amount = ? WHERE id_basket = ?";
                    $stmt = $conn->prepare($sql_update);

                    if ($stmt) {
                        $stmt->bind_param("ii", $new_amount, $basket_id);
                        if ($stmt->execute()) {
                            echo "<script>alert('Количество товара в корзине обновлено.'); window.location.href='catalog.php';</script>";
                            exit;
                        } else {
                            echo "<script>alert('Ошибка обновления количества: " . $stmt->error . "');</script>";
                        }
                    } else {
                        echo "<script>alert('Ошибка: Ошибка при подготовке запроса: " . $conn->error . "');</script>";
                    }
                } else {
                    // Товара еще нет в корзине, добавляем с использованием подготовленных выражений
                    $sql_insert = "INSERT INTO basket (id_user, id_product, amount) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql_insert);

                    if ($stmt) {
                        $stmt->bind_param("iii", $user_id, $product_id, $amount);
                        if ($stmt->execute()) {
                            echo "<script>alert('Товар добавлен в корзину.'); window.location.href='catalog.php';</script>";
                            exit;
                        } else {
                            echo "<script>alert('Ошибка добавления товара: " . $stmt->error . "');</script>";
                        }
                    } else {
                        echo "<script>alert('Ошибка: Ошибка при подготовке запроса: " . $conn->error . "');</script>";
                    }
                }
            } else {
                echo "<script>alert('Ошибка запроса: " . $conn->error . "');</script>";
            }
        } else {
             echo "<script>alert('Ошибка: Ошибка при подготовке запроса: " . $conn->error . "');</script>";
        }
    }

    // **3. Обработка удаления товара из корзины**
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_cart'])) {
        $basket_id = isset($_POST['basket_id']) ? (int)$_POST['basket_id'] : 0;

        if ($basket_id <= 0) {
            echo "<script>alert('Ошибка: Неверный ID корзины.'); window.location.href='cart.php';</script>";
            exit;
        }

        // Удаляем товар из корзины с использованием подготовленных выражений
        $sql_delete = "DELETE FROM basket WHERE id_basket = ? AND id_user = ?";
        $stmt = $conn->prepare($sql_delete);

        if ($stmt) {
            $stmt->bind_param("ii", $basket_id, $user_id);
            if ($stmt->execute()) {
                echo "<script>alert('Товар удален из корзины.'); window.location.href='cart.php';</script>"; // Обновляем страницу
                exit;
            } else {
                echo "<script>alert('Ошибка удаления товара: " . $stmt->error . "');</script>";
            }
        } else {
            echo "<script>alert('Ошибка: Ошибка при подготовке запроса: " . $conn->error . "');</script>";
        }
    }

    // **4. Получение данных корзины пользователя**
    $sql = "
        SELECT
            b.id_basket,
            b.id_product,
            b.amount,
            p.name_product AS product_name,
            p.price AS product_price,
            p.foto AS foto
        FROM basket b
        INNER JOIN products p ON b.id_product = p.id_product
        WHERE b.id_user = ?
        ORDER BY b.id_basket DESC
    ";
    $stmt = $conn->prepare($sql);
      if ($stmt) {
            $stmt->bind_param("i", $user_id);
             $stmt->execute();
             $result = $stmt->get_result();


    $cart_items = array();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cart_items[] = $row;
        }
    }

    // **5. Расчет общей стоимости**
    $total_price = 0;
    foreach ($cart_items as $item) {
        $total_price += $item['product_price'] * $item['amount'];
    }

     // Освобождаем ресурсы
       $stmt->close();
      } else {
            echo "<script>alert('Ошибка: Ошибка при подготовке запроса: " . $conn->error . "');</script>";
        }

    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>KeyShop</title>
        <link rel="stylesheet" href="css/cart.css?v=4">
        <script>
            function updateAmount(basketId, newAmount) {
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "cart.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        location.reload(); 
                    } else {
                        alert('Ошибка обновления количества.');
                    }
                };
                xhr.onerror = function() {
                    alert('Ошибка соединения.');
                };
                xhr.send("basket_id=" + basketId + "&amount=" + newAmount + "&update_cart=true");
            }
        </script>
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
        <h1 class="page_title">Корзина</h1>

        <div style="display:flex; justify-content: start; margin-top:2%; align-items: center;">
            <h2 class="end_price">Итоговая цена: <?php echo htmlspecialchars($total_price); ?> руб.</h2>
            <!-- Форма для заказа всех товаров из корзины -->
            <form method="post" action="delivery.php">
                <input type="hidden" name="order_from_cart" value="true">
                <button class="btn_delivery" type="submit">Заказать все</button>
            </form>
        </div>

        <div class="pod_container">
            <?php if (count($cart_items) > 0): ?>
                <?php foreach ($cart_items as $item): ?>
                    
                    <div class="cart-item">
                    <div class="cart-item_p">
                        <?php
                            // Загрузка фото
                            $image_data = $item['foto'];
                            // Подавляем ошибки @
                            $image_type = @exif_imagetype('data://image/jpeg;base64,' . base64_encode($image_data));
                            // Проверяем что image_type действительно определился
                            if ($image_type !== false) {
                                $image_mime_type = image_type_to_mime_type($image_type);
                            } else {
                                $image_mime_type = 'image/jpeg'; // MIME-тип по умолчанию
                            }

                            $show_img = base64_encode($image_data);
                            echo '<img class="img" src="data:' . $image_mime_type . ';base64,' . $show_img . '" alt="Фото товара">';
                        ?>
                        <div>
                            <div class="cart-item-details">
                                <h3 style="padding-top: 13%;">
                                    <a class="name" href="card_product.php?id_product=<?php echo htmlspecialchars($item['id_product']); ?>">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </a>
                                </h3>
                                <p class="price">Цена: <?php echo htmlspecialchars($item['product_price']); ?> руб.</p>
                            </div>
                            <div class="cart-item-actions">
                                <div style="display:flex; align-items: center;margin-top: 5%;">
                                    <p class="amount">Количество:</p>
                                    <input class="amount_enter" type="number" id="amount_<?php echo htmlspecialchars($item['id_basket']); ?>" name="amount" value="<?php echo htmlspecialchars($item['amount']); ?>" min="1" onchange="updateAmount(<?php echo htmlspecialchars($item['id_basket']); ?>, this.value)">
                                </div>
                                <form method="post" action="cart.php">
                                    <input type="hidden" name="basket_id" value="<?php echo htmlspecialchars($item['id_basket']); ?>">
                                    <button class="del" type="submit" name="delete_cart">Удалить</button>
                                </form>
                            </div>
                        </div> <!-- Закрытие cart-item-details -->
                    </div>
                    </div> <!-- Закрытие cart-item -->
                <?php endforeach; ?>
            <?php else: ?>
                <p class="empty_cart">Корзина пуста.</p>
            <?php endif; ?>
        </div> <!-- Закрытие pod_container -->
    </div> <!-- Закрытие container -->


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
    // Пользователь не авторизован
    echo "<script>alert('Пожалуйста, сначала авторизуйтесь.'); window.location.href='main_authoriz.php';</script>";
    exit;
}
?>
