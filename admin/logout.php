<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
do_logout();
header('Location: login.php');
exit;
