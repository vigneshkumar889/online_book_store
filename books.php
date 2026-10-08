<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$pageTitle = 'Books';

// ---- Read filter/sort inputs from the query string ----
$categoryId = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$priceRange = isset($_GET['price']) ? $_GET['price'] : 'all';
$sort       = isset($_GET['sort']) ? $_GET['sort'] : 'default';

// ---- Build the dynamic WHERE clause safely with prepared statements ----
$conditions = [];
$params = [];
$types = '';

if ($categoryId > 0) {
    $conditions[] = 'b.category_id = ?';
    $params[] = $categoryId;
    $types .= 'i';
}

switch ($priceRange) {
    case 'under300':
        $conditions[] = 'b.price < ?';
        $params[] = 300; $types .= 'i';
        break;
    case '300to500':
        $conditions[] = 'b.price BETWEEN ? AND ?';
        $params[] = 300; $params[] = 500; $types .= 'ii';
        break;
    case '500to1000':
        $conditions[] = 'b.price BETWEEN ? AND ?';
        $params[] = 500; $params[] = 1000; $types .= 'ii';
        break;
    case 'above1000':
        $conditions[] = 'b.price > ?';
        $params[] = 1000; $types .= 'i';
        break;
}

$where = count($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

$orderBy = 'b.created_at DESC';
if ($sort === 'price_asc') $orderBy = 'b.price ASC';
if ($sort === 'price_desc') $orderBy = 'b.price DESC';

$sql = "SELECT b.*, c.name AS category_name FROM books b
        JOIN categories c ON b.category_id = c.id
        $where
        ORDER BY $orderBy";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$books = $stmt->get_result();

$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

require_once 'includes/header.php';
?>

<h1>All Books</h1>

<form method="GET" class="filter-bar">
    <select name="category">
        <option value="0">All Categories</option>
        <?php
        $categories->data_seek(0);
        while ($cat = $categories->fetch_assoc()): ?>
            <option value="<?php echo (int)$cat['id']; ?>" <?php echo $categoryId == $cat['id'] ? 'selected' : ''; ?>>
                <?php echo h($cat['name']); ?>
            </option>
        <?php endwhile; ?>
    </select>

    <select name="price">
        <option value="all" <?php echo $priceRange === 'all' ? 'selected' : ''; ?>>All Prices</option>
        <option value="under300" <?php echo $priceRange === 'under300' ? 'selected' : ''; ?>>Under ₹300</option>
        <option value="300to500" <?php echo $priceRange === '300to500' ? 'selected' : ''; ?>>₹300 - ₹500</option>
        <option value="500to1000" <?php echo $priceRange === '500to1000' ? 'selected' : ''; ?>>₹500 - ₹1000</option>
        <option value="above1000" <?php echo $priceRange === 'above1000' ? 'selected' : ''; ?>>Above ₹1000</option>
    </select>

    <select name="sort">
        <option value="default" <?php echo $sort === 'default' ? 'selected' : ''; ?>>Default</option>
        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
    </select>

    <button type="submit" class="btn btn-primary">Apply</button>
</form>

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

<?php require_once 'includes/footer.php'; ?>
