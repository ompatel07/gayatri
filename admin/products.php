<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Enforce admin check before any processing
if (!is_admin()) {
    $_SESSION['error_msg'] = "Access denied. Admin access required.";
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

$action = isset($_GET['action']) ? sanitize($_GET['action']) : 'list';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$success_msg = '';
$error_msg = '';

// Handle Delete Action
if ($action === 'delete' && $product_id > 0) {
    // Delete product details
    $del_stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    try {
        $del_stmt->execute([$product_id]);
        $_SESSION['admin_success'] = "Product deleted successfully!";
    } catch (Exception $e) {
        $_SESSION['admin_error'] = "Cannot delete product. It might be referenced in orders.";
    }
    header("Location: products.php");
    exit;
}

// Handle Delete All Action
if ($action === 'delete_all') {
    try {
        $pdo->beginTransaction();
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("TRUNCATE TABLE product_gallery");
        $pdo->exec("TRUNCATE TABLE reviews");
        $pdo->exec("TRUNCATE TABLE order_items");
        $pdo->exec("TRUNCATE TABLE products");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        $pdo->commit();
        $_SESSION['admin_success'] = "All products and related references deleted successfully!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['admin_error'] = "Failed to delete all products: " . $e->getMessage();
    }
    header("Location: products.php");
    exit;
}

// Handle Form Submission (Add or Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    $name = sanitize($_POST['name']);
    $slug = sanitize(strtolower(str_replace(' ', '-', $name)));
    $description = sanitize($_POST['description']);
    $price = (float)$_POST['price'];
    $sale_price = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
    $category_id = (int)$_POST['category_id'];
    $material_id = !empty($_POST['material_id']) ? (int)$_POST['material_id'] : null;
    $stock = (int)$_POST['stock'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // Handle Uploads
    $image_url = sanitize($_POST['existing_image'] ?? '');
    $hover_image_url = !empty($_POST['existing_hover_image']) ? sanitize($_POST['existing_hover_image']) : null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target = __DIR__ . '/../assets/images/products/' . $file_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image_url = 'assets/images/products/' . $file_name;
        }
    }

    if (isset($_FILES['hover_image']) && $_FILES['hover_image']['error'] === UPLOAD_ERR_OK) {
        $file_name = time() . '_hover_' . basename($_FILES['hover_image']['name']);
        $target = __DIR__ . '/../assets/images/products/' . $file_name;
        if (move_uploaded_file($_FILES['hover_image']['tmp_name'], $target)) {
            $hover_image_url = 'assets/images/products/' . $file_name;
        }
    }

    if (empty($name) || $price <= 0 || $category_id <= 0) {
        $error_msg = "Please fill in all required fields and enter valid values.";
    } else {
        if ($action === 'add') {
            // Add
            if (empty($image_url)) {
                $error_msg = "Please upload a primary product image.";
            } else {
                $add_sql = "INSERT INTO products (name, slug, description, price, sale_price, image_url, hover_image_url, category_id, material_id, stock, is_featured) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $add_stmt = $pdo->prepare($add_sql);
                try {
                    $add_stmt->execute([$name, $slug, $description, $price, $sale_price, $image_url, $hover_image_url, $category_id, $material_id, $stock, $is_featured]);
                    $_SESSION['admin_success'] = "Product added successfully!";
                    header("Location: products.php");
                    exit;
                } catch (Exception $e) {
                    $error_msg = "Error creating product (perhaps name is duplicate?): " . $e->getMessage();
                }
            }
        } else {
            // Edit
            $edit_sql = "UPDATE products SET name = ?, slug = ?, description = ?, price = ?, sale_price = ?, image_url = ?, hover_image_url = ?, category_id = ?, material_id = ?, stock = ?, is_featured = ? 
                         WHERE id = ?";
            $edit_stmt = $pdo->prepare($edit_sql);
            try {
                $edit_stmt->execute([$name, $slug, $description, $price, $sale_price, $image_url, $hover_image_url, $category_id, $material_id, $stock, $is_featured, $product_id]);
                $_SESSION['admin_success'] = "Product updated successfully!";
                header("Location: products.php");
                exit;
            } catch (Exception $e) {
                $error_msg = "Error updating product details: " . $e->getMessage();
            }
        }
    }
}

// Load admin header navigation template after all redirects
require_once __DIR__ . '/includes/admin-header.php';

// Fetch categories & materials lists for selectors
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$materials = $pdo->query("SELECT * FROM materials")->fetchAll();
?>

<!-- Alerts -->
<?php if (isset($_SESSION['admin_success'])): ?>
    <div class="alert alert-success" style="border-radius: 0;"><?= $_SESSION['admin_success']; unset($_SESSION['admin_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['admin_error'])): ?>
    <div class="alert alert-danger" style="border-radius: 0;"><?= $_SESSION['admin_error']; unset($_SESSION['admin_error']); ?></div>
<?php endif; ?>
<?php if ($error_msg): ?>
    <div class="alert alert-danger" style="border-radius: 0;"><?= $error_msg ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <!-- Products Listing View -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2 class="font-serif">Products Catalog Manager</h2>
        <div class="d-flex gap-2">
            <a href="products.php?action=delete_all" class="btn btn-outline-danger btn-sm" onclick="return confirm('WARNING: This will delete ALL products, reviews, and order items. Are you sure you want to proceed?');"><i class="bi bi-trash-fill me-1"></i> Delete All Products</a>
            <a href="products.php?action=add" class="btn btn-gold btn-sm"><i class="bi bi-plus-lg me-1"></i> Add New Product</a>
        </div>
    </div>

    <div class="bg-white p-4 border shadow-sm">
        <div class="table-responsive" data-lenis-prevent>
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th style="width: 80px;">Thumbnail</th>
                        <th>Product Details</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th class="text-center">Featured</th>
                        <th style="width: 150px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $list_stmt = $pdo->query("SELECT p.*, c.name as category_name, m.name as material_name 
                                              FROM products p 
                                              LEFT JOIN categories c ON p.category_id = c.id 
                                              LEFT JOIN materials m ON p.material_id = m.id 
                                              ORDER BY p.id DESC");
                    $products = $list_stmt->fetchAll();
                    
                    if (empty($products)):
                    ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No products found in the catalog.</td>
                        </tr>
                    <?php else: foreach ($products as $prod): ?>
                        <tr>
                            <td>
                                <img src="<?= BASE_URL ?>/<?= sanitize($prod['image_url']) ?>" alt="thumbnail" class="img-thumbnail" loading="lazy" decoding="async" width="50" height="50" style="width: 50px; height: 50px; object-fit: contain;">
                            </td>
                            <td>
                                <h6 class="mb-0 fw-bold"><?= sanitize($prod['name']) ?></h6>
                                <?php if (!empty($prod['material_name'])): ?>
                                    <small class="text-muted">Material: <?= sanitize($prod['material_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= sanitize($prod['category_name']) ?></td>
                            <td>
                                <?php if (!empty($prod['sale_price'])): ?>
                                    <del class="text-muted small"><?= format_price($prod['price']) ?></del>
                                    <span class="text-gold fw-bold"><?= format_price($prod['sale_price']) ?></span>
                                <?php else: ?>
                                    <span><?= format_price($prod['price']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($prod['stock'] > 0): ?>
                                    <span class="badge bg-success"><?= $prod['stock'] ?> units</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Out of stock</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?= $prod['is_featured'] ? '<i class="bi bi-star-fill text-warning fs-5"></i>' : '<i class="bi bi-star text-muted"></i>' ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="products.php?action=edit&id=<?= $prod['id'] ?>" class="btn btn-sm btn-outline-dark" style="border-radius:0;"><i class="bi bi-pencil-square"></i></a>
                                    <a href="products.php?action=delete&id=<?= $prod['id'] ?>" class="btn btn-sm btn-outline-danger" style="border-radius:0;" onclick="return confirm('Are you sure you want to delete this product?');"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($action === 'add' || $action === 'edit'): 
    // Handle loading product info for editing
    $product = [
        'name' => '', 'description' => '', 'price' => '', 'sale_price' => '', 
        'stock' => 10, 'category_id' => '', 'material_id' => '', 'is_featured' => 0, 
        'image_url' => '', 'hover_image_url' => ''
    ];
    if ($action === 'edit' && $product_id > 0) {
        $edit_stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $edit_stmt->execute([$product_id]);
        $fetched = $edit_stmt->fetch();
        if ($fetched) {
            $product = $fetched;
        }
    }
?>
    <!-- Product Form (Add / Edit) -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h2 class="font-serif"><?= $action === 'add' ? 'Add New Product' : 'Modify Product Details' ?></h2>
        <a href="products.php" class="btn btn-outline-dark btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to Catalog</a>
    </div>

    <div class="card p-4 shadow-sm border bg-white">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label fw-semibold">Product Name *</label>
                    <input type="text" class="form-control border-secondary" id="name" name="name" value="<?= sanitize($product['name']) ?>" required style="border-radius:0;">
                </div>
                
                <div class="col-md-3">
                    <label for="category_id" class="form-label fw-semibold">Category *</label>
                    <select name="category_id" id="category_id" class="form-select border-secondary" required style="border-radius:0;">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= sanitize($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="material_id" class="form-label fw-semibold">Material Subcategory</label>
                    <select name="material_id" id="material_id" class="form-select border-secondary" style="border-radius:0;">
                        <option value="">Select Material (None)</option>
                        <?php foreach ($materials as $mat): ?>
                            <option value="<?= $mat['id'] ?>" <?= ($product['material_id'] == $mat['id']) ? 'selected' : '' ?>>
                                <?= sanitize($mat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12">
                    <label for="description" class="form-label fw-semibold">Detailed Description</label>
                    <textarea name="description" id="description" class="form-control border-secondary" rows="4" style="border-radius:0;"><?= sanitize($product['description']) ?></textarea>
                </div>
                
                <div class="col-md-4">
                    <label for="price" class="form-label fw-semibold">Regular Price (INR) *</label>
                    <input type="number" step="0.01" class="form-control border-secondary" id="price" name="price" value="<?= $product['price'] ?>" required style="border-radius:0;">
                </div>
                
                <div class="col-md-4">
                    <label for="sale_price" class="form-label fw-semibold">Sale Price (INR, Optional)</label>
                    <input type="number" step="0.01" class="form-control border-secondary" id="sale_price" name="sale_price" value="<?= $product['sale_price'] ?>" style="border-radius:0;">
                </div>
                
                <div class="col-md-4">
                    <label for="stock" class="form-label fw-semibold">Stock Quantity</label>
                    <input type="number" class="form-control border-secondary" id="stock" name="stock" value="<?= $product['stock'] ?>" required style="border-radius:0;">
                </div>

                <!-- Images uploads -->
                <div class="col-md-6">
                    <label for="image" class="form-label fw-semibold">Primary Image File</label>
                    <input type="file" class="form-control border-secondary" id="image" name="image" style="border-radius:0;">
                    <?php if (!empty($product['image_url'])): ?>
                        <div class="mt-2">
                            <span class="small text-muted d-block">Current:</span>
                            <img src="<?= BASE_URL ?>/<?= sanitize($product['image_url']) ?>" alt="current image" width="100" class="border">
                            <input type="hidden" name="existing_image" value="<?= $product['image_url'] ?>">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="hover_image" class="form-label fw-semibold">Secondary (Hover) Image File</label>
                    <input type="file" class="form-control border-secondary" id="hover_image" name="hover_image" style="border-radius:0;">
                    <?php if (!empty($product['hover_image_url'])): ?>
                        <div class="mt-2">
                            <span class="small text-muted d-block">Current:</span>
                            <img src="<?= BASE_URL ?>/<?= sanitize($product['hover_image_url']) ?>" alt="current hover image" width="100" class="border">
                            <input type="hidden" name="existing_hover_image" value="<?= $product['hover_image_url'] ?>">
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Checkbox featured -->
                <div class="col-12 my-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1" <?= $product['is_featured'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="is_featured">
                            Featured Masterpiece (Displays on Homepage slider/showcase)
                        </label>
                    </div>
                </div>
                
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-gold py-3 px-5"><i class="bi bi-save me-2"></i> Save Product Settings</button>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
