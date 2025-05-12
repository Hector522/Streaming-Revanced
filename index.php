<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Αρχική Σελίδα</title>
</head>
<body>
    <h2>Καλώς ήρθες στο Streaming Site!</h2>
    <p><a href="login.php">Σύνδεση</a> ή <a href="register.php">Εγγραφή</a></p>
</body>
</html>
