<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Αν έγινε υποβολή φόρμας
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($new_username) || empty($new_email)) {
        $error = "Το username και το email είναι υποχρεωτικά.";
    } else {
        // Αλλαγή username/email
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_username, $new_email, $user_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['username'] = $new_username;
        $success = "Τα στοιχεία ενημερώθηκαν.";

        // Αν υπάρχει αλλαγή κωδικού
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                $error = " Ο νέος κωδικός δεν ταιριάζει με την επιβεβαίωση.";
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed, $user_id);
                $stmt->execute();
                $stmt->close();
                $success .= " Ο κωδικός ενημερώθηκε.";
            }
        }
    }

}


$profile_image_path = null;

if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['profile_image']['tmp_name'];
    $original_name = basename($_FILES['profile_image']['name']);
    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
        $new_name = 'user_' . $user_id . '_' . time() . '.' . $extension;
        $destination = 'uploads/' . $new_name;

        if (move_uploaded_file($tmp_name, $destination)) {
            $profile_image_path = $new_name;

            // Ενημέρωση βάσης με το νέο όνομα αρχείου
            $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->bind_param("si", $profile_image_path, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}


// Φόρτωση τρεχόντων στοιχείων
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Επεξεργασία Προφίλ</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>✏️ Επεξεργασία προφίλ</h2>
    <p><a href="dashboard.php">⬅ Επιστροφή στο dashboard</a></p>
    <?php
    $img_path = !empty($profile_image_path) ? $profile_image_path : null;

    // Ή φέρε από τη βάση αν δεν υπάρχει νέο
    if (!$img_path) {
        $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($img_path);
        $stmt->fetch();
        $stmt->close();
    }
    ?>

    <?php if ($img_path): ?>
        <img src="uploads/<?= htmlspecialchars($img_path) ?>" alt="Προφίλ" width="120" style="border-radius:50%;">
    <?php endif; ?>


        <?php if ($error): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color: green;"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

    <form method="post" action="edit_profile.php" enctype="multipart/form-data">
        <label>Username: <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required></label><br><br>
        <label>Email: <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required></label><br><br>

        <h4>🔑 Αλλαγή κωδικού (προαιρετικό):</h4>
        <label>Νέος κωδικός: <input type="password" name="password"></label><br><br>
        <label>Επιβεβαίωση νέου κωδικού: <input type="password" name="confirm_password"></label><br><br>

        <h4>🖼️ Εικόνα προφίλ (προαιρετική):</h4>
        <input type="file" name="profile_image" accept="image/*"><br><br>

        <input type="submit" value="Αποθήκευση αλλαγών">
    </form>
</body>
</html>
