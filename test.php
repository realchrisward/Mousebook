<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<h2>PHP is working. Version: " . phpversion() . "</h2>";

// Test config.php load
$config = @include './config.php';
if ($config === false) {
    echo "<p style='color:red'>ERROR: config.php not found or failed to load at ./config.php</p>";
    echo "<p>Looking in: " . realpath('./config.php') . "</p>";
} else {
    echo "<p style='color:green'>config.php loaded OK</p>";
    echo "<pre>server_ip: " . ($config['server_ip'] ?? 'NOT SET') . "\n";
    echo "server_host: " . ($config['server_host'] ?? 'NOT SET') . "\n";
    echo "server_user: " . ($config['server_user'] ?? 'NOT SET') . "\n";
    echo "debug_mode: " . ($config['debug_mode'] ?? 'NOT SET') . "</pre>";
}

// Test MySQL connection
if ($config) {
    $conn = @new mysqli(
        $config['server_ip'],
        $config['server_user'],
        $config['server_pass'],
        'userbook'
    );
    if ($conn->connect_error) {
        echo "<p style='color:red'>MySQL ERROR: " . $conn->connect_error . "</p>";
    } else {
        echo "<p style='color:green'>MySQL connection to userbook: OK</p>";
        $conn->close();
    }
}
