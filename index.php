<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$pageTitle = 'Home';

// Featured books - just the most recently added ones with stock available
$featured = $conn->query("SELECT b.*, c.name AS category_name FROM books b
                           JOIN categories c ON b.category_id = c.id
                           ORDER BY b.created_at DESC LIMIT 8");

// Categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

require_once 'includes/header.php';
?>

<section class="hero">
    <h1>Discover Your Next Favourite Book</h1>
    <p>Explore books across fiction, technology, science, biography and more.</p>
    <a href="books.php" class="btn btn-primary">Browse Books</a>
</section>

<section class="home-search">
    <form action="search.php" method="GET" class="search-form">
        <input type="text" name="q" placeholder="Search books by title or author..." required>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</section>

<section class="section">
    <h2>Shop by Category</h2>
    <div class="category-grid">
        <?php while ($cat = $categories->fetch_assoc()): ?>
            <a class="category-card" href="categories.php?id=<?php echo (int)$cat['id']; ?>">
                <?php echo h($cat['name']); ?>
            </a>
        <?php endwhile; ?>
    </div>
</section>

<section class="section">
    <h2>Featured Books</h2>
    <div class="book-grid">
        <?php while ($book = $featured->fetch_assoc()): ?>
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
</section>

<?php require_once 'includes/footer.php'; ?>
