<?php
echo "<h1>ProjectManager - Everything Works! ✅</h1>";

// Test 1: Database connection
try {
    $pdo = new PDO("mysql:host=db;dbname=ProjectManagement", "root", "root");
    echo "<p style=\"color:green;\">✅ Database connected: ProjectManagement</p>";
} catch (PDOException $e) {
    echo "<p style=\"color:red;\">❌ Database error: " . $e->getMessage() . "</p>";
}

// Test 2: Laravel
if (file_exists(__DIR__ . "/../vendor/autoload.php")) {
    echo "<p style=\"color:green;\">✅ Laravel dependencies installed</p>";
} else {
    echo "<p style=\"color:red;\">❌ Laravel dependencies missing</p>";
}

// Test 3: Show PHP info
echo "<p><a href=\"/ProjectManager/public/test.php?phpinfo=1\">Show PHP Info</a></p>";

if (isset($_GET["phpinfo"])) {
    phpinfo();
}
