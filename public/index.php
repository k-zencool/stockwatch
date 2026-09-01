<?php
require_once __DIR__ . '/../src/auth.php';
redirect(current_user() ? 'dashboard.php' : 'login.php');
