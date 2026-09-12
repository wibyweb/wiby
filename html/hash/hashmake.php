<?php
require_once __DIR__ . '/db_config.php';
echo password_hash("secretpassword", PASSWORD_DEFAULT)."\n";
?>

