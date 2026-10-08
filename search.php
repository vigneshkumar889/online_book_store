<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$pageTitle = 'Search';

$query = trim($_GET['q'] ?? '');
$books = null;

if ($query !== '') {
    $like = '%' . $query . '%';
    $stmt = $conn->prepare("SELECT b.*, c.name AS category_name FROM books b
                             JOIN categories c ON b.category_id = c.id
                             WHERE b.title LIKE ? OR b.author LIKE ?
                             ORDER BY b.title ASC");
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $books = $stmt->get_result();
}

require_once 'includes/header.php';
?>

<h1>Search Books</h1>

<form action="search.php" method="GET" class="search-form">
    <input type="text" name="q" value="<?php echo h($query); ?>" placeholder="Search by title or author..." required>
    <button type="submit" class="btn btn-primary">Search</button>
</form>

<?php if ($query !== ''): ?>
    <h2>Search Results for "<?php echo h($query); ?>"</h2>
    <div class="book-grid">
        <?php if ($books->num_rows === 0): ?>
            <p>No books found.</p>
        <?php endif; ?>
        <?php while ($book = $books->fetch_assoc()): ?>
            <div class="book-card">
                <img src="<?php echo h($book['image']); ?>" alt="<?php echo h($book['title']); ?>" onerror="this.src='images/default-book.jpg'">
                <h3><?php echo h($book['title']); ?></h3>
                <p class="author">by <?php echo h($book['author']); ?></p>
                <p class="category-tag"><?php echo h($book['category_name']); ?></p>
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
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
