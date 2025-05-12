<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user = $_SESSION['user_id'];

// Παίρνουμε τους χρήστες που ακολουθεί ο συνδεδεμένος χρήστης
$stmt = $conn->prepare("
    SELECT u.id AS user_id, u.username, l.id AS list_id, l.title
    FROM followers f
    JOIN users u ON f.followed_id = u.id
    JOIN lists l ON l.user_id = u.id
    WHERE f.follower_id = ? AND l.is_private = 0
    ORDER BY u.username, l.title
");
$stmt->bind_param("i", $current_user);
$stmt->execute();
$result = $stmt->get_result();

$lists_by_user = [];
while ($row = $result->fetch_assoc()) {
    $uid = $row['user_id'];
    if (!isset($lists_by_user[$uid])) {
        $lists_by_user[$uid] = [
            'username' => $row['username'],
            'lists' => []
        ];
    }
    $lists_by_user[$uid]['lists'][] = [
        'id' => $row['list_id'],
        'title' => $row['title']
    ];
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Λίστες που ακολουθείς</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Λίστες άλλων χρηστών που ακολουθείς</h2>
    
    <?php if (count($lists_by_user) > 0): ?>
        <?php foreach ($lists_by_user as $user): ?>
            <h3>Χρήστης: <?= htmlspecialchars($user['username']) ?></h3>
            <ul>
                <?php foreach ($user['lists'] as $list): ?>
                    <li><?= htmlspecialchars($list['title']) ?></li>
                <?php endforeach; ?>
            </ul>
            <hr>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Δεν ακολουθείς κανέναν χρήστη με δημόσιες λίστες.</p>
    <?php endif; ?>

<p><a href="dashboard.php">⬅ Επιστροφή στο dashboard</a></p>

</body>
</html>
