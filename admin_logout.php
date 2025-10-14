<?php
session_start();
session_destroy();
header("Location: admin_oneclick.php");
exit();
?>