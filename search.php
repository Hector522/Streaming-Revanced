<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$keyword = '';
$privacy = 'all';
$sort = 'title';
$username_filter = '';

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
    $privacy = isset($_GET['privacy']) ? $_GET['privacy'] : 'all';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'title';
    $username_filter = isset($_GET['username']) ? trim($_GET['username']) : '';
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Αναζήτηση</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>🔎 Αναζήτηση</h2>

    <form method="get" action="search.php" style="margin-bottom: 20px;">
        <input type="text" name="q" placeholder="Λέξη-κλειδί..." value="<?= htmlspecialchars($keyword) ?>" required>

        <select name="privacy">
            <option value="all" <?= $privacy === 'all' ? 'selected' : '' ?>>Όλες</option>
            <option value="public" <?= $privacy === 'public' ? 'selected' : '' ?>>Μόνο δημόσιες</option>
            <option value="private" <?= $privacy === 'private' ? 'selected' : '' ?>>Μόνο ιδιωτικές</option>
        </select>

        <select name="sort">
            <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Αλφαβητικά</option>
            <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Πρόσφατες πρώτα</option>
        </select>

        <input type="text" name="username" placeholder="Username χρήστη (μόνο δημόσιες)" value="<?= htmlspecialchars($username_filter) ?>">

        <input type="submit" value="Αναζήτηση">
    </form>

<?php
if (!empty($keyword)) {
    // 🔹 Αναζήτηση στις δικές σου λίστες
    $where = "l.user_id = ? AND (l.title LIKE ? OR v.title LIKE ?)";
    $params = [$user_id, "%$keyword%", "%$keyword%"];
    $types = "iss";

    if ($privacy === 'public') {
        $where .= " AND l.is_private = 0";
    } elseif ($privacy === 'private') {
        $where .= " AND l.is_private = 1";
    }

    $sql = "
        SELECT l.id AS list_id, l.title AS list_title, l.is_private,
               v.title AS video_title, v.url
        FROM lists l
        LEFT JOIN videos v ON l.id = v.list_id
        WHERE $where
        ORDER BY " . ($sort === 'recent' ? "l.id DESC" : "l.title ASC");

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $results = [];
    while ($row = $result->fetch_assoc()) {
        $lid = $row['list_id'];
        if (!isset($results[$lid])) {
            $results[$lid] = [
                'title' => $row['list_title'],
                'is_private' => $row['is_private'],
                'videos' => []
            ];
        }
        if ($row['video_title']) {
            $results[$lid]['videos'][] = [
                'title' => $row['video_title'],
                'url' => $row['url']
            ];
        }
    }
    $stmt->close();

    // 🔸 Δημόσιες λίστες άλλων χρηστών με φίλτρο username
    $where2 = "l.user_id != ? AND l.is_private = 0 AND (l.title LIKE ? OR v.title LIKE ?)";
    $params2 = [$user_id, "%$keyword%", "%$keyword%"];
    $types2 = "iss";

    if (!empty($username_filter)) {
        $where2 .= " AND u.username LIKE ?";
        $params2[] = "%$username_filter%";
        $types2 .= "s";
    }

    $sql2 = "
        SELECT l.id AS list_id, l.title AS list_title, u.username,
               v.title AS video_title, v.url
        FROM lists l
        JOIN users u ON l.user_id = u.id
        LEFT JOIN videos v ON l.id = v.list_id
        WHERE $where2
        ORDER BY " . ($sort === 'recent' ? "l.id DESC" : "u.username, l.title");

    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param($types2, ...$params2);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    $other_results = [];
    while ($row = $result2->fetch_assoc()) {
        $lid = $row['list_id'];
        if (!isset($other_results[$lid])) {
            $other_results[$lid] = [
                'title' => $row['list_title'],
                'username' => $row['username'],
                'videos' => []
            ];
        }
        if ($row['video_title']) {
            $other_results[$lid]['videos'][] = [
                'title' => $row['video_title'],
                'url' => $row['url']
            ];
        }
    }
    $stmt2->close();
?>

    <h3> Οι λίστες σου:</h3>
    <?php if (count($results) > 0): ?>
        <?php foreach ($results as $list): ?>
            <h4><?= htmlspecialchars($list['title']) ?> (<?= $list['is_private'] ? 'Ιδιωτική' : 'Δημόσια' ?>)</h4>
            <?php if (count($list['videos']) > 0): ?>
                <ul>
                    <?php foreach ($list['videos'] as $v): ?>
                        <li><a href="<?= htmlspecialchars($v['url']) ?>" target="_blank"><?= htmlspecialchars($v['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="margin-left: 20px;">(Χωρίς βίντεο)</p>
            <?php endif; ?>
            <hr>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Δεν βρέθηκαν λίστες σου.</p>
    <?php endif; ?>

    <h3>🌍 Δημόσιες λίστες άλλων χρηστών:</h3>
    <?php if (count($other_results) > 0): ?>
        <?php foreach ($other_results as $list): ?>
            <h4><?= htmlspecialchars($list['title']) ?> (χρήστης: <?= htmlspecialchars($list['username']) ?>)</h4>
            <?php if (count($list['videos']) > 0): ?>
                <ul>
                    <?php foreach ($list['videos'] as $v): ?>
                        <li><a href="<?= htmlspecialchars($v['url']) ?>" target="_blank"><?= htmlspecialchars($v['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="margin-left: 20px;">(Χωρίς βίντεο)</p>
            <?php endif; ?>
            <hr>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Δεν βρέθηκαν δημόσιες λίστες άλλων χρηστών.</p>
    <?php endif; ?>

<?php } ?>

<p><a href="dashboard.php">⬅ Επιστροφή στο dashboard</a></p>

</body>
</html>
