<?php
// Use this to generate hashes for your database manually
$password_to_hash = 'password123';
$new_hash = password_hash($password_to_hash, PASSWORD_DEFAULT);

echo "<h3>Generated Hash for: " . $password_to_hash . "</h3>";
echo "<pre style='background:#eee; padding:10px; border-radius:5px;'>" . $new_hash . "</pre>";
echo "<p>Copy this string and paste it into your SQL 'password_hash' column.</p>";
?>