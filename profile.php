<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user = $_SESSION['user_id'];

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    echo "Μη έγκυρο προφίλ.";
    exit;
}

$profile_id = intval($_GET['user_id']);

// Παίρνουμε βασικά στοιχεία του χρήστη
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$stmt->bind_result($username, $email);
if (!$stmt->fetch()) {
    echo "Ο χρήστης δεν βρέθηκε.";
    exit;
}
$stmt->close();

// Πόσες λίστες έχει
$stmt = $conn->prepare("SELECT COUNT(*) FROM lists WHERE user_id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$stmt->bind_result($total_lists);
$stmt->fetch();
$stmt->close();

// Πόσες δημόσιες λίστες έχει
$stmt = $conn->prepare("SELECT COUNT(*) FROM lists WHERE user_id = ? AND is_private = 0");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$stmt->bind_result($public_lists);
$stmt->fetch();
$stmt->close();

// Πόσοι τον ακολουθούν
$stmt = $conn->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$stmt->bind_result($followers_count);
$stmt->fetch();
$stmt->close();

// Πόσους ακολουθεί
$stmt = $conn->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$stmt->bind_result($following_count);
$stmt->fetch();
$stmt->close();

// Δημόσιες λίστες του χρήστη
$list_stmt = $conn->prepare("SELECT id, title FROM lists WHERE user_id = ? AND is_private = 0");
$list_stmt->bind_param("i", $profile_id);
$list_stmt->execute();
$list_result = $list_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Προφίλ χρήστη</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Προφίλ: <?= htmlspecialchars($username) ?></h2>
    <?php
    // Παίρνουμε το profile_image του χρήστη
    $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $stmt->bind_result($profile_img);
    $stmt->fetch();
    $stmt->close();
    ?>

    <?php if (!empty($profile_img)): ?>
        <img src="uploads/<?= htmlspecialchars($profile_img) ?>" alt="Εικόνα Προφίλ" width="120" style="border-radius: 50%; margin-bottom: 10px;">
    <?php endif; ?>

    <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
    <p><strong>Λίστες:</strong> <?= $total_lists ?> (Δημόσιες: <?= $public_lists ?>)</p>
    <p><strong>Ακολουθεί:</strong> <?= $following_count ?> χρήστες</p>
    <p><strong>Ακόλουθοι:</strong> <?= $followers_count ?> χρήστες</p>

    <h3> Δημόσιες λίστες:</h3>
    <?php if ($list_result->num_rows > 0): ?>
        <ul>
            <?php while ($list = $list_result->fetch_assoc()): ?>
                <li>
                    <a href="list_videos.php?list_id=<?= $list['id'] ?>">
                        <?= htmlspecialchars($list['title']) ?>
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>Δεν έχει δημόσιες λίστες.</p>
    <?php endif; ?>
    <?php $list_stmt->close(); ?>

    <p><a href="dashboard.php">⬅ Επιστροφή</a></p>
</body>
</html>
