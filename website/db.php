<?php

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "keyshop";

$conn = mysqli_connect($servername,$username,$password,$dbname);

if(!$conn){
    die("Нет подключения к БД" . mysqli_connect_error());
} else {
     "Успешное подключение к БД";
}
?>