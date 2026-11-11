<?php
declare(strict_types=1);
require __DIR__ . '/config/bootstrap.php';
require __DIR__ . '/includes/auth.php';

redirect(current_user() !== null ? 'dashboard.php' : 'login.php');
