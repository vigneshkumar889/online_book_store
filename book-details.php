<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $conn->prepare("SELECT b.*, c.name AS category_name FROM books b
                         JOIN categories c ON b.category_id = c.id
                         WHERE b.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();

if (!$book) {
    $pageTitle = 'Book Not Found';
    require_once 'includes/header.php';
    echo '<p>Book not found.</p><p><a href="books.php">Back to books</a></p>';
    require_once 'includes/footer.php';
    exit;
}

$pageTitle = $book['title'];
require_once 'includes/header.php';
?>

<div class="book-details">
    <img src="<?php echo h($book['image']); ?>" alt="<?php echo h($book['title']); ?>" onerror="this.src='images/default-book.jpg'">
    <div class="book-details-info">
        <h1><?php echo h($book['title']); ?></h1>
        <p class="author">by <?php echo h($book['author']); ?></p>
        <p class="category-tag"><?php echo h($book['category_name']); ?></p>
        <p class="price"><?php echo money($book['price']); ?></p>
        <p class="stock <?php echo $book['stock'] > 0 ? 'in-stock' : 'out-stock'; ?>">
            <?php echo $book['stock'] > 0 ? $book['stock'] . ' in stock' : 'Out of stock'; ?>
        </p>
        <p class="description"><?php echo nl2br(h($book['description'])); ?></p>

        <div class="card-actions">
            <?php if ($book['stock'] > 0): ?>
                <a href="checkout.php?buy_now=<?php echo (int)$book['id']; ?>" class="btn btn-primary">Buy Now</a>
                <form action="cart.php" method="POST" style="display:inline;">
                    <input type="hidden" name="book_id" value="<?php echo (int)$book['id']; ?>">
                    <input type="hidden" name="action" value="add">
                    <button type="submit" class="btn btn-secondary">Add to Cart</button>
                </form>
            <?php else: ?>
                <button class="btn btn-disabled" disabled>Out of Stock</button>
            <?php endif; ?>
            <a href="books.php" class="btn btn-secondary">Back to Books</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
