<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'config.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// ➤ Δημιουργία νέας λίστας
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_list_title'])) {
    $title = trim($_POST['new_list_title']);
    $is_private = isset($_POST['is_private']) ? 1 : 0;

    if (!empty($title)) {
        $stmt = $conn->prepare("INSERT INTO lists (title, is_private, user_id) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $title, $is_private, $user_id);
        $stmt->execute();
        $stmt->close();

        header("Location: dashboard.php");
        exit;
    }
}

// ➤ Προσθήκη νέου βίντεο
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['video_title'], $_POST['video_url'], $_POST['list_id'])) {
    $video_title = trim($_POST['video_title']);
    $video_url = trim($_POST['video_url']);
    $list_id = intval($_POST['list_id']);

    if (!empty($video_title) && !empty($video_url)) {
        $stmt = $conn->prepare("INSERT INTO videos (title, url, list_id, user_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $video_title, $video_url, $list_id, $user_id);
        $stmt->execute();
        $stmt->close();

        header("Location: dashboard.php");
        exit;
    }
}

// ➤ Διαγραφή λίστας
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_list_id'])) {
    $list_id = intval($_POST['delete_list_id']);

    $stmt = $conn->prepare("DELETE FROM lists WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $list_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: dashboard.php");
    exit;
}

// ➤ Διαγραφή βίντεο
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_video_id'])) {
    $video_id = intval($_POST['delete_video_id']);

    $stmt = $conn->prepare("DELETE FROM videos WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $video_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: dashboard.php");
    exit;
}

// ➤ Φέρνουμε τις λίστες
$stmt = $conn->prepare("SELECT id, title, is_private FROM lists WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
    <h2>Καλωσήρθες, <?php echo htmlspecialchars($username); ?>!</h2>
    <?php
    $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($dash_img);
    $stmt->fetch();
    $stmt->close();
    ?>

    <?php if (!empty($dash_img)): ?>
        <img src="uploads/<?= htmlspecialchars($dash_img) ?>" alt="Εικόνα Προφίλ" width="100" style="border-radius: 50%; margin-bottom: 10px;">
    <?php endif; ?>

    <p>
     <a href="profile.php?user_id=<?= $_SESSION['user_id'] ?>" style="padding: 10px; background-color: #222; color: white; text-decoration: none; border-radius: 6px;">
        👤View Profile 
     </a>
    </p>


    <!-- Δημιουργία λίστας -->
    <h3>Δημιουργία νέας λίστας</h3>
    <form method="post" action="">
        Τίτλος: <input type="text" name="new_list_title" required>
        <label>
            <input type="checkbox" name="is_private"> Ιδιωτική λίστα
        </label>
        <input type="submit" value="Προσθήκη">
    </form>

    <!-- Προσθήκη βίντεο -->
    <h3>Προσθήκη βίντεο σε λίστα</h3>
    <form method="post" action="">
        Τίτλος βίντεο: <input type="text" name="video_title" required><br><br>
        URL βίντεο: <input type="url" name="video_url" required><br><br>
        Επιλογή λίστας:
        <select name="list_id" required>
            <?php
            $listQuery = $conn->prepare("SELECT id, title FROM lists WHERE user_id = ?");
            $listQuery->bind_param("i", $user_id);
            $listQuery->execute();
            $listResult = $listQuery->get_result();
            while ($list = $listResult->fetch_assoc()):
            ?>
                <option value="<?= $list['id'] ?>"><?= htmlspecialchars($list['title']) ?></option>
            <?php endwhile; ?>
            <?php $listQuery->close(); ?>
        </select><br><br>
        <input type="submit" value="Προσθήκη βίντεο">
    </form>
    
    <!-- Εμφάνιση λιστών και βίντεο -->
    <h3>Οι λίστες σου:</h3>
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <h4>
                <?= htmlspecialchars($row['title']) ?>
                (<?= $row['is_private'] ? 'Ιδιωτική' : 'Δημόσια' ?>)

                <!-- Διαγραφή λίστας -->
                <form method="post" action="" style="display:inline;">
                    <input type="hidden" name="delete_list_id" value="<?= $row['id'] ?>">
                    <button type="submit" onclick="return confirm('Διαγραφή λίστας;')">🗑 </button>
                </form>
            </h4>

            <?php
            $list_id = $row['id'];
            $videoStmt = $conn->prepare("SELECT id, title, url FROM videos WHERE list_id = ?");
            $videoStmt->bind_param("i", $list_id);
            $videoStmt->execute();
            $videoResult = $videoStmt->get_result();
            ?>

            <?php if ($videoResult->num_rows > 0): ?>
                <ul>
                    <?php while ($video = $videoResult->fetch_assoc()): ?>
                        <li>
                            <a href="<?= htmlspecialchars($video['url']) ?>" target="_blank">
                                <?= htmlspecialchars($video['title']) ?>
                            </a>

                            <!-- Διαγραφή βίντεο -->
                            <form method="post" action="" style="display:inline;">
                                <input type="hidden" name="delete_video_id" value="<?= $video['id'] ?>">
                                <button type="submit" onclick="return confirm('Διαγραφή βίντεο;')">🗑</button>
                            </form>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p style="margin-left: 20px;">(Δεν υπάρχουν βίντεο σε αυτήν τη λίστα)</p>
            <?php endif; ?>

            <?php $videoStmt->close(); ?>
            <hr>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Δεν έχεις δημιουργήσει ακόμα λίστες.</p>
    <?php endif; ?>

    <!-- Αποσύνδεση -->
    <form method="post" action="logout.php">
        <input type="submit" value="Αποσύνδεση">
    </form>

    <head>
        <meta charset="UTF-8">
        <title>Dashboard</title>
        <link rel="stylesheet" href="style.css">
    </head>

    <p><a href="users.php">+Ανακάλυψε Χρήστες</a></p>

    <p>
        <a href="followed_lists.php" style="font-weight:bold;"> Δες λίστες από χρήστες που ακολουθείς</a>
    </p>

    <p><a href="search.php"> Αναζήτηση στις λίστες & βίντεο</a></p>
        
    <p><a href="import_youtube.php">📥 Εισαγωγή βίντεο από YouTube</a></p>

    <p>
        <a href="export_yaml.php" style="font-weight: bold;">📤 Εξαγωγή σε YAML</a>
    </p>


</body>
</html>

