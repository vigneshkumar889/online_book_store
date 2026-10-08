<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$pageTitle = 'Cart';

// Adding/updating/removing from the cart requires login.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['HTTP_REFERER'] ?? 'cart.php';
    header('Location: login.php');
    exit;
}

$userId = currentUserId();

// ---- Handle POST actions: add / update / remove ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $bookId = (int) ($_POST['book_id'] ?? 0);

    if ($action === 'add') {
        $stmt = $conn->prepare("SELECT stock FROM books WHERE id = ?");
        $stmt->bind_param('i', $bookId);
        $stmt->execute();
        $book = $stmt->get_result()->fetch_assoc();

        if ($book && $book['stock'] > 0) {
            // If it's already in the cart, increase quantity (never above stock)
            $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND book_id = ?");
            $stmt->bind_param('ii', $userId, $bookId);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();

            if ($existing) {
                $newQty = min($existing['quantity'] + 1, $book['stock']);
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $stmt->bind_param('ii', $newQty, $existing['id']);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare("INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, 1)");
                $stmt->bind_param('ii', $userId, $bookId);
                $stmt->execute();
            }
            $_SESSION['flash_success'] = 'Book added to cart.';
        }
    } elseif ($action === 'increase' || $action === 'decrease') {
        $stmt = $conn->prepare("SELECT c.id, c.quantity, b.stock FROM cart c JOIN books b ON c.book_id = b.id WHERE c.id = ? AND c.user_id = ?");
        $cartId = (int) ($_POST['cart_id'] ?? 0);
        $stmt->bind_param('ii', $cartId, $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row) {
            $newQty = $row['quantity'];
            if ($action === 'increase') $newQty = min($row['quantity'] + 1, $row['stock']);
            if ($action === 'decrease') $newQty = max($row['quantity'] - 1, 1);

            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt->bind_param('ii', $newQty, $cartId);
            $stmt->execute();
        }
    } elseif ($action === 'remove') {
        $cartId = (int) ($_POST['cart_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $cartId, $userId);
        $stmt->execute();
    }

    header('Location: cart.php');
    exit;
}

// GET request must be logged in to view the cart page itself
requireLogin();
$userId = currentUserId();

$stmt = $conn->prepare("SELECT c.id AS cart_id, c.quantity, b.id AS book_id, b.title, b.price, b.stock, b.image
                         FROM cart c JOIN books b ON c.book_id = b.id
                         WHERE c.user_id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$items = $stmt->get_result();

$total = 0;
$rows = [];
while ($row = $items->fetch_assoc()) {
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $total += $row['subtotal'];
    $rows[] = $row;
}

require_once 'includes/header.php';
?>

<h1>Your Cart</h1>

<?php if (empty($rows)): ?>
    <p>Your cart is empty. <a href="books.php">Browse books</a></p>
<?php else: ?>
    <table class="data-table">
        <thead>
        <tr>
            <th>Book</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Subtotal</th>
            <th>Remove</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?php echo h($row['title']); ?></td>
                <td><?php echo money($row['price']); ?></td>
                <td>
                    <form method="POST" class="qty-form">
                        <input type="hidden" name="cart_id" value="<?php echo $row['cart_id']; ?>">
                        <button type="submit" name="action" value="decrease" class="qty-btn">-</button>
                        <span><?php echo $row['quantity']; ?></span>
                        <button type="submit" name="action" value="increase" class="qty-btn" <?php echo $row['quantity'] >= $row['stock'] ? 'disabled' : ''; ?>>+</button>
                    </form>
                </td>
                <td><?php echo money($row['subtotal']); ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Remove this book from cart?');">
                        <input type="hidden" name="cart_id" value="<?php echo $row['cart_id']; ?>">
                        <button type="submit" name="action" value="remove" class="btn btn-danger">Remove</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Total Amount: <?php echo money($total); ?></h2>
    <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
