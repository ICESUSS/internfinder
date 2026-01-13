<?php
session_start();
if (isset($_GET['logout'])) {
    // clear and destroy session
    $_SESSION = [];
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

?>