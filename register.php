<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$pageTitle = 'Sign Up';

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    $old = compact('name', 'email', 'phone');

    // ---- Server-side validation ----
    if ($name === '') $errors[] = 'Full name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
    if ($password === '' || strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';
    if ($phone === '') $errors[] = 'Phone number is required.';

    // Check for duplicate email
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, 'customer')");
        $stmt->bind_param('ssss', $name, $email, $hashedPassword, $phone);

        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Registration successful. Please login.';
            header('Location: login.php');
            exit;
        } else {
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

require_once 'includes/header.php';
?>

<div class="form-page">
    <h1>Create an Account</h1>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?php echo h($error); ?></div>
    <?php endforeach; ?>

    <form method="POST" id="registerForm" novalidate>
        <label>Full Name
            <input type="text" name="name" value="<?php echo h($old['name']); ?>" required>
        </label>
        <label>Email
            <input type="email" name="email" value="<?php echo h($old['email']); ?>" required>
        </label>
        <label>Password
            <input type="password" name="password" id="password" minlength="6" required>
        </label>
        <label>Confirm Password
            <input type="password" name="confirm_password" id="confirm_password" minlength="6" required>
        </label>
        <label>Phone
            <input type="text" name="phone" value="<?php echo h($old['phone']); ?>" required>
        </label>
        <span id="passwordMatchError" class="field-error"></span>
        <button type="submit" class="btn btn-primary">Sign Up</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>
