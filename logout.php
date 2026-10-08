<?php
require_once 'includes/functions.php';

// Destroy the customer session
$_SESSION = [];
session_destroy();

header('Location: index.php');
exit;
