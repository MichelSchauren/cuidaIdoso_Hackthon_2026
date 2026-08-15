<?php
require_once __DIR__ . '/includes/functions.php';

redirect(isLoggedIn() ? 'home.php' : 'login.php');
