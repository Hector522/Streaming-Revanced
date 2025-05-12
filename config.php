<?php
$host = "localhost";
$user = "root";
$password = "";
$dbname = "streaming_site";

// Δημιουργία σύνδεσης
$conn = new mysqli($host, $user, $password, $dbname);

// Έλεγχος σύνδεσης
if ($conn->connect_error) {
    die("Αποτυχία σύνδεσης: " . $conn->connect_error);
}
?>
