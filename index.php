<?php
require_once __DIR__ . '/src/config.php';

try {
    $config = new Config();
    $db = $config->getDB();
    echo "Database connection established successfully.";
} catch (Exception $e) {
    die("Configuration error: " . $e->getMessage());
}