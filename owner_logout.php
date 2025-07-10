<?php
session_start();
session_unset();
session_destroy();
header("Location: home.php"); // Or whatever your homepage is
exit();
?>