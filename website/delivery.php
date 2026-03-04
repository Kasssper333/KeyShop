<?php
session_start(); // Обязательно запускаем сессию в самом начале файла
require_once('db.php');

// Проверка авторизации пользователя
if (isset($_SESSION['user'])&& !empty($_SESSION['user'])) {
    $user = $_SESSION["user"];

// Получение ID пользователя
$sql = "SELECT id_user FROM users WHERE login = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die("Ошибка SQL (получение ID пользователя): " . mysqli_error($conn));
}

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $user_id = (int)$row['id_user'];
} else {
    echo "<script>alert('Ошибка: Не удалось получить ID пользователя.'); window.location.href='main_authoriz.php';</script>";
    exit;
}

mysqli_stmt_close($stmt);


$cart_items = [];
$total_price = 0;

// Определение источника заказа (карточка товара или корзина)
if (isset($_POST['order_now']) && isset($_POST['product_id']) && is_numeric($_POST['product_id'])) {
    // Заказ с карточки товара

    $product_id = (int)$_POST['product_id'];
    $amount = 1;

    $sql = "SELECT name_product, price FROM products WHERE id_product = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        die("Ошибка SQL (получение информации о товаре): " . mysqli_error($conn));
    }

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $cart_items[] = [
            'product_id' => $product_id,
            'name_product' => htmlspecialchars($row['name_product']),
            'price' => $row['price'],
            'amount' => $amount
        ];
        $total_price = $row['price'] * $amount;
    } else {
        echo "<script>alert('Ошибка: Не удалось получить информацию о товаре.'); window.location.href='index.php';</script>";
        exit;
    }

    mysqli_stmt_close($stmt);


} elseif (isset($_POST['order_from_cart']) && $_POST['order_from_cart'] == 'true') {
    // Заказ из корзины
    $sql = "
        SELECT 
            b.id_product, 
            b.amount, 
            p.name_product AS product_name, 
            p.price AS product_price
        FROM basket b
        INNER JOIN products p ON b.id_product = p.id_product
        WHERE b.id_user = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        die("Ошибка SQL (получение товаров из корзины): " . mysqli_error($conn));
    }

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $cart_items[] = [
                'product_id' => $row['id_product'],
                'name_product' => htmlspecialchars($row['product_name']),
                'price' => $row['product_price'],
                'amount' => $row['amount']
            ];
            $total_price += $row['product_price'] * $row['amount'];
        }
        mysqli_free_result($result);
    } else {
        echo "<script>alert('Ваша корзина пуста.'); window.location.href='cart.php';</script>";
        exit;
    }
    mysqli_stmt_close($stmt);

} else {
    echo "<script>alert('Некорректный запрос.'); window.location.href='index.php';</script>";
    exit;
}

// Инициализация переменных для хранения данных формы
$fio = "";
$address = "";

// Обработка отправки формы
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_order'])) {


    // Получение данных из формы
    $fio = mysqli_real_escape_string($conn, $_POST['fio']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $data = date("Y-m-d H:i:s");

    // Валидация данных
    if (empty($fio) || empty($address)) {
        echo "<script>alert('Пожалуйста, заполните все поля');</script>";
    } else {
        // Начало транзакции
        mysqli_begin_transaction($conn);

        try {
            // 1. Добавление заказа в таблицу orders
            $sql_order = "INSERT INTO orders (id_user, fio, address, data, price_order) VALUES (?, ?, ?, ?, ?)";
            $stmt_order = mysqli_prepare($conn, $sql_order);
            if (!$stmt_order) {
                throw new Exception("Ошибка подготовки запроса для orders: " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt_order, "isssd", $user_id, $fio, $address, $data, $total_price);
            $result_order = mysqli_stmt_execute($stmt_order);
            if (!$result_order) {
                throw new Exception("Ошибка выполнения запроса для orders: " . mysqli_stmt_error($stmt_order));
            }
            $order_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt_order);


            // 2. Добавление товаров в таблицу order_items
            $sql_item = "INSERT INTO order_items (id_order, id_product, amount, price) VALUES (?, ?, ?, ?)";
            $stmt_item = mysqli_prepare($conn, $sql_item);
            if (!$stmt_item) {
                throw new Exception("Ошибка подготовки запроса для order_items: " . mysqli_error($conn));
            }


            //Если заказ с карточки товара, то цикл не нужен
            if (isset($_POST['order_now']) && isset($_POST['product_id'])) {
                $product_id = (int)$_POST['product_id'];
                $amount = 1;
                $price = $cart_items[0]['price'];

                mysqli_stmt_bind_param($stmt_item, "iiid", $order_id, $product_id, $amount, $price);
                $result_item = mysqli_stmt_execute($stmt_item);

                if (!$result_item) {
                    throw new Exception("Ошибка выполнения запроса для order_items: " . mysqli_stmt_error($stmt_item));
                }
            }
            else{ //иначе, если заказ из корзины, перебираем все товары
                foreach ($cart_items as $item) {
                    $product_id = $item['product_id'];
                    $amount = $item['amount'];
                    $price = $item['price'];

                    mysqli_stmt_bind_param($stmt_item, "iiid", $order_id, $product_id, $amount, $price);
                    $result_item = mysqli_stmt_execute($stmt_item);

                    if (!$result_item) {
                        throw new Exception("Ошибка выполнения запроса для order_items: " . mysqli_stmt_error($stmt_item));
                    }
                }
            }

            mysqli_stmt_close($stmt_item);

            mysqli_commit($conn);

            // Очистка корзины (если заказ из корзины)
            if (isset($_POST['order_from_cart']) && $_POST['order_from_cart'] == 'true') {
                $sql_delete_cart = "DELETE FROM basket WHERE id_user = ?";
                $stmt_delete_cart = mysqli_prepare($conn, $sql_delete_cart);
                mysqli_stmt_bind_param($stmt_delete_cart, "i", $user_id);
                if (mysqli_stmt_execute($stmt_delete_cart)) {
                    echo "Корзина очищена\n";
                } else {
                    echo "Ошибка очистки корзины: " . mysqli_error($conn) . "\n"; // Добавлено логирование
                }
                mysqli_stmt_close($stmt_delete_cart);

            }

            echo "<script>alert('Заказ успешно оформлен!'); window.location.href='index.php';</script>";
            exit;

        } catch (Exception $e) {
            // Откат транзакции при ошибке
            mysqli_rollback($conn);
            $error_message = "Ошибка оформления заказа: " . $e->getMessage() . ". SQL error: " . mysqli_error($conn);
            error_log($error_message, 0);
            echo "<script>alert('" . $error_message . "');</script>";
            echo "Транзакция отменена: " . $error_message . "\n";  // Отладочный вывод

        }
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление доставки</title>
    <link rel="stylesheet" href="css/delivery.css?v=4">
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
            <p class="delivery">Оформление доставки</p>
            <!-- <p class="text">*Заполните все поля</p> -->
            <p class="products">Ваши товары:</p>
            <?php foreach ($cart_items as $item): ?>
                <p class="goods">
                    <?php echo htmlspecialchars($item['name_product']); ?>
                    (<?php echo htmlspecialchars($item['amount']); ?> шт.) -
                    <?php echo htmlspecialchars($item['price'] * $item['amount']); ?> руб.
                </p>
            <?php endforeach; ?>
            <p class="price">Итоговая цена:<p style="font-family: M-B; font-size: 25px; color: #ffffff;display: flex; justify-content: center;"> <?php echo htmlspecialchars($total_price); ?> руб.</p></p>

            <!-- Форма для ввода данных доставки -->
            <form method="post">
                <p class="text1">ФИО </p>
                <input class="input" type="text" name="fio" value="<?php echo htmlspecialchars($fio); ?>">
                <p class="text2">Адрес доставки</p>
                <input class="input" type="text" name="address" value="<?php echo htmlspecialchars($address); ?>">

                <div style=" margin-top: 3%;">
                    <p class="text3">Оплата происходит при получении;</p>
                    <p class="text3">Возможна оплата картой или наличными.</p>
                </div>

                <!-- Скрытые поля для передачи product_id и order_from_cart -->
                <?php if (isset($_POST['order_now']) && isset($_POST['product_id'])): ?>
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($_POST['product_id']); ?>">
                    <input type="hidden" name="order_now" value="true">
                <?php endif; ?>

                <?php if (isset($_POST['order_from_cart']) && $_POST['order_from_cart'] == 'true'): ?>
                    <input type="hidden" name="order_from_cart" value="true">
                <?php endif; ?>

                <input class="btn" type="submit" name="confirm_order" value="Заказать">
            </form>
        </div>
    </div>

    <?php
} else {
    // Пользователь не авторизован
    echo "<script>alert('Пожалуйста, сначала авторизуйтесь.'); window.location.href='main_authoriz.php';</script>";
    exit;
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

<?php
mysqli_close($conn);
?>
