<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$pageTitle = 'Categories';

$selectedId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$books = null;
$selectedCategory = null;
if ($selectedId > 0) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param('i', $selectedId);
    $stmt->execute();
    $selectedCategory = $stmt->get_result()->fetch_assoc();

    $stmt2 = $conn->prepare("SELECT * FROM books WHERE category_id = ? ORDER BY title ASC");
    $stmt2->bind_param('i', $selectedId);
    $stmt2->execute();
    $books = $stmt2->get_result();
}

require_once 'includes/header.php';
?>

<h1>Categories</h1>

<div class="category-grid">
    <?php while ($cat = $categories->fetch_assoc()): ?>
        <a class="category-card <?php echo $selectedId == $cat['id'] ? 'active' : ''; ?>"
           href="categories.php?id=<?php echo (int)$cat['id']; ?>">
            <?php echo h($cat['name']); ?>
        </a>
    <?php endwhile; ?>
</div>

<?php if ($selectedCategory): ?>
    <h2>Books in "<?php echo h($selectedCategory['name']); ?>"</h2>
    <div class="book-grid">
        <?php if ($books->num_rows === 0): ?>
            <p>No books found in this category.</p>
        <?php endif; ?>
        <?php while ($book = $books->fetch_assoc()): ?>
            <div class="book-card">
                <img src="<?php echo h($book['image']); ?>" alt="<?php echo h($book['title']); ?>" onerror="this.src='images/default-book.jpg'">
                <h3><?php echo h($book['title']); ?></h3>
                <p class="author">by <?php echo h($book['author']); ?></p>
                <p class="price"><?php echo money($book['price']); ?></p>
                <p class="stock <?php echo $book['stock'] > 0 ? 'in-stock' : 'out-stock'; ?>">
                    <?php echo $book['stock'] > 0 ? $book['stock'] . ' in stock' : 'Out of stock'; ?>
                </p>
                <div class="card-actions">
                    <a href="book-details.php?id=<?php echo (int)$book['id']; ?>" class="btn btn-secondary">View Details</a>
                    <?php if ($book['stock'] > 0): ?>
                        <a href="checkout.php?buy_now=<?php echo (int)$book['id']; ?>" class="btn btn-primary">Buy Now</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <p>Select a category above to view its books.</p>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
