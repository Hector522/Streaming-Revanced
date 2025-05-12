<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include 'config.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $url = trim($_POST['url']);
    $list_id = intval($_POST['list_id']);

    if (!empty($title) && !empty($url)) {
        $stmt = $conn->prepare("INSERT INTO videos (title, url, list_id, user_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $title, $url, $list_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: import_youtube.php");
    exit;
}
?>
