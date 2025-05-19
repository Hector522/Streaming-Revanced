<?php
require_once __DIR__ . '/libs/Spyc.php'; 
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    die("Πρέπει να είσαι συνδεδεμένος.");
}

$user_id = $_SESSION['user_id'];

$sql = "
    SELECT l.id AS list_id, l.title AS list_title, l.is_private,
           v.title AS video_title, v.url
    FROM lists l
    LEFT JOIN videos v ON l.id = v.list_id
    WHERE l.user_id = ?
    ORDER BY l.id
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $lid = $row['list_id'];
    if (!isset($data[$lid])) {
        $data[$lid] = [
            'title'      => $row['list_title'],
            'is_private' => (bool)$row['is_private'],
            'videos'     => []
        ];
    }
    if ($row['video_title']) {
        $data[$lid]['videos'][] = [
            'title' => $row['video_title'],
            'url'   => $row['url']
        ];
    }
}

$stmt->close();

$yaml_output = Spyc::YAMLDump(array_values($data));

header('Content-Type: application/x-yaml');
header('Content-Disposition: attachment; filename="export.yaml"');

echo $yaml_output;
exit;
?>
