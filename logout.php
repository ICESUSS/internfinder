<?php
session_start();

// clear and destroy session
$_SESSION = [];
session_unset();
session_destroy();

header("Location: login.php");
exit();
?>