<?php
session_start();
$_SESSION["user_id"] = "alice2025";
$_SESSION["user_name"] = "Alice Martin";
$_SESSION["user_email"] = "alice@test.com";
echo "Session créée pour Alice";
?>