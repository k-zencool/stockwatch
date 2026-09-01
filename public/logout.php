<?php
require_once __DIR__ . '/../src/auth.php';
logout_user();
redirect('login.php');
