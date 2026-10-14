<?php

session_start();

if (!isset($_SESSION["id_user"])) {
  header("Location: auth/Login.php");
  exit;
}

header("Location: ../FrontEnd/beranda.php");
exit;
