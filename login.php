<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$pageTitle = 'Login';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
                exit;
            }

            // Redirect back to where the user was headed (e.g. Buy Now flow)
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

require_once 'includes/header.php';
?>

<div class="form-page">
    <h1>Login</h1>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo h($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Email
            <input type="email" name="email" required>
        </label>
        <label>Password
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
    <p>Don't have an account? <a href="register.php">Sign up here</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>
