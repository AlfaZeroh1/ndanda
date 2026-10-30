<?php
require __DIR__ . '/includes/bootstrap.php';
$_SESSION = [];
session_regenerate_id(true);
session_destroy();
redirect(url());
