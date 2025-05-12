<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['list_id']) || !is_numeric($_GET['list_id'])) {
    echo "Μη έγκυρη λίστα.";
    exit;
}

$list_id = intval($_GET['list_id']);

// Επιστροφή στοιχείων λίστας
$list_stmt = $conn->prepare("
    SELECT l.title, u.username
    FROM lists l
    JOIN users u ON l.user_id = u.id
    WHERE l.id = ? AND l.is_private = 0
");
$list_stmt->bind_param("i", $list_id);
$list_stmt->execute();
$list_result = $list_stmt->get_result();

if ($list_result->num_rows === 0) {
    echo "Η λίστα δεν υπάρχει ή δεν είναι δημόσια.";
    exit;
}

$list_info = $list_result->fetch_assoc();
$list_stmt->close();

// Επιστροφή βίντεο της λίστας
$video_stmt = $conn->prepare("SELECT id, title, url FROM videos WHERE list_id = ?");
$video_stmt->bind_param("i", $list_id);
$video_stmt->execute();
$videos = $video_stmt->get_result();

// Χειρισμός υποβολής σχολίου
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['video_id'], $_POST['comment'])) {
    $video_id = intval($_POST['video_id']);
    $content = trim($_POST['comment']);
    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO comments (video_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $video_id, $user_id, $content);
        $stmt->execute();
        $stmt->close();
        header("Location: list_videos.php?list_id=$list_id");
        exit;
    }
}

// Χειρισμός like
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['like_video_id'])) {
    $like_video_id = intval($_POST['like_video_id']);

    // Εισαγωγή μόνο αν δεν υπάρχει ήδη like από αυτόν τον χρήστη
    $check = $conn->prepare("SELECT id FROM likes WHERE video_id = ? AND user_id = ?");
    $check->bind_param("ii", $like_video_id, $user_id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO likes (video_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $like_video_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    $check->close();
    header("Location: list_videos.php?list_id=$list_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Λίστα: <?= htmlspecialchars($list_info['title']) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Λίστα: <?= htmlspecialchars($list_info['title']) ?></h2>
    <p>Από χρήστη: <?= htmlspecialchars($list_info['username']) ?></p>
    <p><a href="dashboard.php">⬅ Επιστροφή</a></p>

<?php if ($videos->num_rows > 0): ?>
    <ul>
    <?php while ($video = $videos->fetch_assoc()): ?>
        <li>
            <a href="<?= htmlspecialchars($video['url']) ?>" target="_blank">
                <?= htmlspecialchars($video['title']) ?>
            </a>

            <!-- 🔘 Like button -->
            <?php
            // Σύνολο likes
            $like_stmt = $conn->prepare("SELECT COUNT(*) FROM likes WHERE video_id = ?");
            $like_stmt->bind_param("i", $video['id']);
            $like_stmt->execute();
            $like_stmt->bind_result($like_count);
            $like_stmt->fetch();
            $like_stmt->close();

            // Αν έχει κάνει ήδη like
            $liked = false;
            $check_stmt = $conn->prepare("SELECT id FROM likes WHERE video_id = ? AND user_id = ?");
            $check_stmt->bind_param("ii", $video['id'], $user_id);
            $check_stmt->execute();
            $check_stmt->store_result();
            if ($check_stmt->num_rows > 0) {
                $liked = true;
            }
            $check_stmt->close();
            ?>

            <form method="post" action="" style="display:inline;">
                <input type="hidden" name="like_video_id" value="<?= $video['id'] ?>">
                <button type="submit" <?= $liked ? 'disabled' : '' ?>>
                    ❤️ Like (<?= $like_count ?>)
                </button>
            </form>

            <!-- 💬 Σχόλια -->
            <?php
            $comments_stmt = $conn->prepare("
                SELECT c.content, c.created_at, u.username
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.video_id = ?
                ORDER BY c.created_at DESC
            ");
            $comments_stmt->bind_param("i", $video['id']);
            $comments_stmt->execute();
            $comments_result = $comments_stmt->get_result();
            ?>

            <div style="margin-left: 20px; margin-top: 10px;">
                <strong>💬 Σχόλια:</strong>
                <?php if ($comments_result->num_rows > 0): ?>
                    <ul>
                        <?php while ($c = $comments_result->fetch_assoc()): ?>
                            <li>
                                <em><?= htmlspecialchars($c['username']) ?></em>:
                                <?= htmlspecialchars($c['content']) ?>
                                <small style="color:gray;">[<?= $c['created_at'] ?>]</small>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>(Κανένα σχόλιο)</p>
                <?php endif; ?>
                <?php $comments_stmt->close(); ?>

                <!-- 📝 Φόρμα σχολίου -->
                <form method="post" action="">
                    <input type="hidden" name="video_id" value="<?= $video['id'] ?>">
                    <textarea name="comment" rows="2" cols="50" placeholder="Γράψε σχόλιο..." required></textarea><br>
                    <button type="submit">➕ Σχόλιο</button>
                </form>
            </div>
        </li>
        <hr>
    <?php endwhile; ?>
    </ul>
<?php else: ?>
    <p>Δεν υπάρχουν βίντεο σε αυτήν τη λίστα.</p>
<?php endif; ?>

</body>
</html>
