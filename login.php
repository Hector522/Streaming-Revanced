<?php
include 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username'], $_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Λάθος κωδικός.";
        }
    } else {
        $error = "Ο χρήστης δεν βρέθηκε.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Σύνδεση</title>
</head>
<body>
    <h2>Φόρμα Σύνδεσης</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post" action="">
        Όνομα χρήστη: <input type="text" name="username" required><br><br>
        Κωδικός: <input type="password" name="password" required><br><br>
        <input type="submit" value="Σύνδεση">
    </form>
    <p>Δεν έχεις λογαριασμό; <a href="register.php">Εγγραφή</a></p>
</body>
</html>
