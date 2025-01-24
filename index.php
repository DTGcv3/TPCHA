<?php
// Include the database connection
require 'db.php';

// Initialize variables for feedback messages
$message = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'signup') {
        // Signup Logic
        $username = $_POST['username'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password === $confirm_password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
            try {
                $stmt->execute([':username' => $username, ':password' => $hashed_password]);
                $message = "Signup successful! You can now log in.";
            } catch (PDOException $e) {
                $message = "Error: Username already exists.";
            }
        } else {
            $message = "Passwords do not match!";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'login') {
        // Login Logic
        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            // Redirect to stock.php after successful login
            header('Location: stock.php');
            exit; // Make sure no further code is executed after the redirect
        } else {
            $message = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <title>Sign Up | Log In</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<div class="wrapper">
    <div class="title-text">
        <div class="title login">
            <i>Welcome to Inventory Management</i>
        </div>
        <div class="title signup">
            <i>Welcome to Inventory Management</i>
        </div>
    </div>
    <div class="form-container">
        <div class="slide-controls">
            <input type="radio" name="slide" id="login" checked>
            <input type="radio" name="slide" id="signup">
            <label for="login" class="slide login">Login</label>
            <label for="signup" class="slide signup">SignUp</label>
            <div class="slider-tab"></div>
        </div>
        <div class="form-inner">
            <!-- Login Form -->
            <form action="index.php" method="POST" class="login">
                <input type="hidden" name="action" value="login">
                <div class="field">
                    <input type="text" name="username" placeholder="Utilisateur" required>
                </div>
                <div class="field">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="pass-link">
                    <a href="#">Reset password?</a>
                </div>
                <div class="field btn">
                    <div class="btn-layer"></div>
                    <input type="submit" value="Login">
                </div>
                <div class="signup-link">
                    Don't Have Account? <a href="#">Create A New</a>
                </div>
            </form>

            <!-- Signup Form -->
            <form action="index.php" method="POST" class="signup">
                <input type="hidden" name="action" value="signup">
                <div class="field">
                    <input type="text" name="username" placeholder="Utilisateur" required>
                </div>
                <div class="field">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="field">
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
                <div class="field btn">
                    <div class="btn-layer"></div>
                    <input type="submit" value="SignUp">
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Display Feedback Messages -->
<?php if (!empty($message)): ?>
    <div class="message">
        <p><?php echo $message; ?></p>
    </div>
<?php endif; ?>

<script src="style.js"></script>
</body>
</html>
