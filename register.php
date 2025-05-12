<?php
include 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" &&
    isset($_POST['first_name'], $_POST['last_name'], $_POST['username'], $_POST['email'], $_POST['password'])) {

    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Έλεγχος για διπλό username/email
    $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "Το όνομα χρήστη ή το email χρησιμοποιείται ήδη.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, email, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $first_name, $last_name, $username, $email, $password);

        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['username'] = $username;
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Σφάλμα κατά την εγγραφή.";
        }
        $stmt->close();
    }

    $check->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Εγγραφή</title>
</head>
<body>
    <h2>Φόρμα Εγγραφής</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post" action="">
        Όνομα: <input type="text" name="first_name" required><br><br>
        Επώνυμο: <input type="text" name="last_name" required><br><br>
        Όνομα χρήστη: <input type="text" name="username" required><br><br>
        Email: <input type="email" name="email" required><br><br>
        Κωδικός: <input type="password" name="password" required><br><br>
        <input type="submit" value="Εγγραφή">
    </form>
    <p>Έχεις ήδη λογαριασμό; <a href="login.php">Σύνδεση</a></p>
</body>
</html>
