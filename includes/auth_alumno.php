<?php
require_once __DIR__ . '/config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'Alumno') {
    header("Location: " . BASE_URL . "/login.php?error=permisos");
    exit();
}