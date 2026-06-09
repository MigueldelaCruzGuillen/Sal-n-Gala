<?php
require __DIR__ . '/../app/bootstrap.php';
session_regenerate_id(true);
$_SESSION = [];
session_destroy();
header('Location: index.php');
exit;
