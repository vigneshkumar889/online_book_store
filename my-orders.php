<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$pageTitle = 'My Orders';

requireLogin();
$userId = currentUserId();

$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$orders = $stmt->get_result();

$expandId = isset($_GET['view']) ? (int) $_GET['view'] : 0;
$orderItems = [];
if ($expandId > 0) {
    // Make sure this order belongs to the logged-in user before showing details
    $check = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
    $check->bind_param('ii', $expandId, $userId);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $stmt2 = $conn->prepare("SELECT oi.*, b.title FROM order_items oi JOIN books b ON oi.book_id = b.id WHERE oi.order_id = ?");
        $stmt2->bind_param('i', $expandId);
        $stmt2->execute();
        $orderItems = $stmt2->get_result();
    } else {
        $expandId = 0; // not this user's order — silently ignore
    }
}

require_once 'includes/header.php';
?>

<h1>My Orders</h1>

<?php if ($orders->num_rows === 0): ?>
    <p>You haven't placed any orders yet. <a href="books.php">Browse books</a></p>
<?php else: ?>
    <table class="data-table">
        <thead><tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php while ($order = $orders->fetch_assoc()): ?>
            <tr>
                <td>#<?php echo (int)$order['id']; ?></td>
                <td><?php echo date('d/m/Y', strtotime($order['order_date'])); ?></td>
                <td><?php echo money($order['total_amount']); ?></td>
                <td><span class="status-badge status-<?php echo strtolower($order['status']); ?>"><?php echo h($order['status']); ?></span></td>
                <td><a href="my-orders.php?view=<?php echo (int)$order['id']; ?>" class="btn btn-secondary">View Items</a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <?php if ($expandId > 0): ?>
        <h2>Items in Order #<?php echo (int)$expandId; ?></h2>
        <table class="data-table">
            <thead><tr><th>Book</th><th>Quantity</th><th>Price</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php while ($item = $orderItems->fetch_assoc()): ?>
                <tr>
                    <td><?php echo h($item['title']); ?></td>
                    <td><?php echo (int)$item['quantity']; ?></td>
                    <td><?php echo money($item['price']); ?></td>
                    <td><?php echo money($item['price'] * $item['quantity']); ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
