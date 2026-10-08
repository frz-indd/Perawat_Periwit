<?php

session_start();

if (!isset($_SESSION["id_user"])) {
  header("Location: FrontEnd/auth/Login.php");
  exit;
}

header("Location: FrontEnd/exploreMajaKu_skeleton/index.html");
exit;