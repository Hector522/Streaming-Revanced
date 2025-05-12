<?php

include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user = $_SESSION['user_id'];

// Αν πατήθηκε το κουμπί “Ακολούθησε”
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['follow_id'])) {
    $follow_id = intval($_POST['follow_id']);

    // Μην ακολουθήσει τον εαυτό του
    if ($follow_id !== $current_user) {
        $check = $conn->prepare("SELECT id FROM followers WHERE follower_id = ? AND followed_id = ?");
        $check->bind_param("ii", $current_user, $follow_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO followers (follower_id, followed_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $current_user, $follow_id);
            $stmt->execute();
            $stmt->close();
        }
        $check->close();
    }

    header("Location: users.php");
    exit;
}

// Φέρνουμε όλους τους χρήστες εκτός από τον εαυτό μας
$stmt = $conn->prepare("SELECT id, username FROM users WHERE id != ?");
$stmt->bind_param("i", $current_user);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Χρήστες</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Λίστα Χρηστών</h2>
    

    <?php if ($result->num_rows > 0): ?>
        <ul>
            <?php while ($user = $result->fetch_assoc()): ?>
                <li>
                <a href="profile.php?user_id=<?= $user['id'] ?>">
                    <?= htmlspecialchars($user['username']) ?>
                </a>

                    <form method="post" action="" style="display:inline;">
                        <input type="hidden" name="follow_id" value="<?= $user['id'] ?>">
                        <button type="submit">Ακολούθησε</button>
                    </form>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>Δεν υπάρχουν άλλοι χρήστες.</p>
    <?php endif; ?>
</body>
</html>
