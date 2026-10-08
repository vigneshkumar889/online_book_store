<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$pageTitle = 'Checkout';

// Buy Now / Checkout both require login (Buy Now -> login check flow)
requireLogin();
$userId = currentUserId();

$errors = [];

// ---- Handle "Update Address" ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_address') {
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($address === '' || $city === '' || $pincode === '' || $phone === '') {
        $errors[] = 'All address fields are required.';
    } else {
        $stmt = $conn->prepare("UPDATE users SET address = ?, city = ?, pincode = ?, phone = ? WHERE id = ?");
        $stmt->bind_param('ssssi', $address, $city, $pincode, $phone, $userId);
        $stmt->execute();
        $_SESSION['flash_success'] = 'Address updated.';
        header('Location: checkout.php' . (isset($_GET['buy_now']) ? '?buy_now=' . (int)$_GET['buy_now'] : ''));
        exit;
    }
}

// ---- Determine what we're checking out: a single "Buy Now" book, or the whole cart ----
$buyNowId = isset($_GET['buy_now']) ? (int) $_GET['buy_now'] : (isset($_POST['buy_now']) ? (int) $_POST['buy_now'] : 0);

function getOrderPreview($conn, $userId, $buyNowId) {
    $rows = [];
    if ($buyNowId > 0) {
        $stmt = $conn->prepare("SELECT id AS book_id, title, price, stock FROM books WHERE id = ?");
        $stmt->bind_param('i', $buyNowId);
        $stmt->execute();
        $book = $stmt->get_result()->fetch_assoc();
        if ($book && $book['stock'] > 0) {
            $rows[] = ['book_id' => $book['book_id'], 'title' => $book['title'], 'price' => $book['price'], 'quantity' => 1, 'stock' => $book['stock']];
        }
    } else {
        $stmt = $conn->prepare("SELECT b.id AS book_id, b.title, b.price, b.stock, c.quantity
                                 FROM cart c JOIN books b ON c.book_id = b.id WHERE c.user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) $rows[] = $row;
    }
    return $rows;
}

// ---- Handle "Confirm Order" ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_order') {
    $user = $conn->query("SELECT * FROM users WHERE id = " . (int)$userId)->fetch_assoc();

    if (empty($user['address']) || empty($user['city']) || empty($user['pincode'])) {
        $errors[] = 'Please save a delivery address before placing your order.';
    } else {
        $items = getOrderPreview($conn, $userId, $buyNowId);

        if (empty($items)) {
            $errors[] = 'No items to order.';
        } else {
            // Verify stock again right before placing the order
            $stockOk = true;
            foreach ($items as $item) {
                if ($item['quantity'] > $item['stock']) { $stockOk = false; break; }
            }

            if (!$stockOk) {
                $errors[] = 'One or more books no longer have enough stock.';
            } else {
                // Calculate total on the SERVER (never trust the browser)
                $total = 0;
                foreach ($items as $item) $total += $item['price'] * $item['quantity'];

                $conn->begin_transaction();
                try {
                    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'Pending')");
                    $stmt->bind_param('id', $userId, $total);
                    $stmt->execute();
                    $orderId = $conn->insert_id;

                    foreach ($items as $item) {
                        $stmt = $conn->prepare("INSERT INTO order_items (order_id, book_id, quantity, price) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param('iiid', $orderId, $item['book_id'], $item['quantity'], $item['price']);
                        $stmt->execute();

                        // Reduce stock
                        $stmt = $conn->prepare("UPDATE books SET stock = stock - ? WHERE id = ? AND stock >= ?");
                        $stmt->bind_param('iii', $item['quantity'], $item['book_id'], $item['quantity']);
                        $stmt->execute();
                    }

                    // Clear the cart only if this was a cart checkout (not a direct Buy Now)
                    if ($buyNowId === 0) {
                        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
                        $stmt->bind_param('i', $userId);
                        $stmt->execute();
                    }

                    $conn->commit();
                    $_SESSION['flash_success'] = 'Order placed successfully.';
                    header('Location: my-orders.php');
                    exit;
                } catch (Exception $e) {
                    $conn->rollback();
                    $errors[] = 'Something went wrong while placing your order.';
                }
            }
        }
    }
}

// ---- Build data for display ----
$user = $conn->query("SELECT * FROM users WHERE id = " . (int)$userId)->fetch_assoc();
$items = getOrderPreview($conn, $userId, $buyNowId);
$total = 0;
foreach ($items as $item) $total += $item['price'] * $item['quantity'];

require_once 'includes/header.php';
?>

<h1>Checkout</h1>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?php echo h($error); ?></div>
<?php endforeach; ?>

<?php if (empty($items)): ?>
    <p>There is nothing to check out. <a href="books.php">Browse books</a></p>
<?php else: ?>

    <h2>Order Summary</h2>
    <table class="data-table">
        <thead><tr><th>Book</th><th>Quantity</th><th>Price</th><th>Subtotal</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo h($item['title']); ?></td>
                <td><?php echo (int)$item['quantity']; ?></td>
                <td><?php echo money($item['price']); ?></td>
                <td><?php echo money($item['price'] * $item['quantity']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <h3>Total Amount: <?php echo money($total); ?></h3>

    <h2>Delivery Address</h2>
    <?php if (!empty($user['address'])): ?>
        <div class="address-box">
            <p><strong>Name:</strong> <?php echo h($user['name']); ?></p>
            <p><strong>Phone:</strong> <?php echo h($user['phone']); ?></p>
            <p><strong>Address:</strong> <?php echo h($user['address']); ?></p>
            <p><strong>City:</strong> <?php echo h($user['city']); ?></p>
            <p><strong>Pincode:</strong> <?php echo h($user['pincode']); ?></p>
        </div>
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addressForm').classList.toggle('hidden')">Edit Address</button>
    <?php else: ?>
        <p>No delivery address saved yet. Please add one below.</p>
    <?php endif; ?>

    <form method="POST" id="addressForm" class="<?php echo !empty($user['address']) ? 'hidden' : ''; ?>">
        <input type="hidden" name="action" value="update_address">
        <label>Phone <input type="text" name="phone" value="<?php echo h($user['phone']); ?>" required></label>
        <label>Address <input type="text" name="address" value="<?php echo h($user['address']); ?>" required></label>
        <label>City <input type="text" name="city" value="<?php echo h($user['city']); ?>" required></label>
        <label>Pincode <input type="text" name="pincode" value="<?php echo h($user['pincode']); ?>" required></label>
        <button type="submit" class="btn btn-primary">Save Address</button>
    </form>

    <form method="POST" class="confirm-order-form">
        <input type="hidden" name="action" value="confirm_order">
        <?php if ($buyNowId > 0): ?>
            <input type="hidden" name="buy_now" value="<?php echo (int)$buyNowId; ?>">
        <?php endif; ?>
        <button type="submit" class="btn btn-primary btn-large">Confirm Order</button>
    </form>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
