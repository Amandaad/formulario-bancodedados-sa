<?php
require_once __DIR__ . '/seguranca.php';

auth_logout();
header('Location: login.php?logout=1');
exit;
