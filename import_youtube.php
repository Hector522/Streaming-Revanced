<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
include 'config.php';
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Εισαγωγή από YouTube</title>
    <script>
        function searchYouTube() {
            const query = document.getElementById('search').value;
            const apiKey = 'AIzaSyDTdy8uOb4eCLZkX9ibs6TzPW4Br4TJNzo';
            const url = `https://www.googleapis.com/youtube/v3/search?part=snippet&q=${encodeURIComponent(query)}&type=video&maxResults=10&key=${apiKey}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const resultsDiv = document.getElementById('results');
                    resultsDiv.innerHTML = '';
                    data.items.forEach(item => {
                        const videoId = item.id.videoId;
                        const title = item.snippet.title;
                        const videoUrl = `https://www.youtube.com/watch?v=${videoId}`;
                        const element = document.createElement('div');
                        element.innerHTML = `
                            <p><strong>${title}</strong><br>
                            <iframe width="300" src="https://www.youtube.com/embed/${videoId}" frameborder="0" allowfullscreen></iframe><br>
                            <form method="post" action="save_youtube_video.php">
                                <input type="hidden" name="title" value="${title}">
                                <input type="hidden" name="url" value="${videoUrl}">
                                Επιλογή λίστας:
                                <select name="list_id">
                                    ${document.getElementById('list_select').innerHTML}
                                </select>
                                <button type="submit">Αποθήκευση</button>
                            </form></p><hr>`;
                        resultsDiv.appendChild(element);
                    });
                });
        }
    </script>
</head>
<body>
    <h2>Καλωσήρθες, <?= htmlspecialchars($username) ?></h2>

    <p><a href="dashboard.php">⬅ Επιστροφή στο Dashboard</a></p>

    <h3>Αναζήτηση YouTube</h3>
    <input type="text" id="search" placeholder="Π.χ. php tutorial">
    <button onclick="searchYouTube()">Αναζήτηση</button>

    <h4>Αποτελέσματα:</h4>
    <div id="results"></div>

    <!-- Hidden select για λίστας -->
    <select id="list_select" style="display:none;">
        <?php
        $stmt = $conn->prepare("SELECT id, title FROM lists WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            echo '<option value="'.$row['id'].'">'.htmlspecialchars($row['title']).'</option>';
        }
        $stmt->close();
        ?>
    </select>
</body>
</html>
