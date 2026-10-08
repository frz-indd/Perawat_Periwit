<?php

session_start();

if (!isset($_SESSION["id_user"])) {
  header("Location: auth/Login.php");
  exit;
}

header("Location: exploreMajaKu_skeleton/index.html");
exit;