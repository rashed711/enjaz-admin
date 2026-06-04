<?php
/**
 * index.php - Redirect to dashboard or login
 */
require_once __DIR__ . '/config/app.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
