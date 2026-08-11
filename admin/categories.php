<?php
require_once __DIR__ . '/includes/admin-header.php';

$success_msg = '';
$error_msg = '';

// Handle category form submission
csrf_guard();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $cat_name = sanitize($_POST['cat_name']);
    $cat_slug = sanitize(strtolower(str_replace(' ', '-', $cat_name)));
    $cat_desc = sanitize($_POST['cat_desc']);
    
    // File upload
    $image_url = 'assets/images/categories/wall_art.jpg'; // default placeholder
    if (isset($_FILES['cat_image']) && $_FILES['cat_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../assets/images/categories/';
        $file_name = time() . '_' . basename($_FILES['cat_image']['name']);
        if (move_uploaded_file($_FILES['cat_image']['tmp_name'], $upload_dir . $file_name)) {
            $image_url = 'assets/images/categories/' . $file_name;
        }
    }
    
    if (empty($cat_name)) {
        $error_msg = "Category name is required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, image_url) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$cat_name, $cat_slug, $cat_desc, $image_url]);
            $success_msg = "Category added successfully!";
        } catch (Exception $e) {
            $error_msg = "Category already exists.";
        }
    }
}

// Handle material form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_material'])) {
    $mat_name = sanitize($_POST['mat_name']);
    $mat_slug = sanitize(strtolower(str_replace(' ', '-', $mat_name)));
    
    if (empty($mat_name)) {
        $error_msg = "Material name is required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO materials (name, slug) VALUES (?, ?)");
        try {
            $stmt->execute([$mat_name, $mat_slug]);
            $success_msg = "Material subcategory added successfully!";
        } catch (Exception $e) {
            $error_msg = "Material already exists.";
        }
    }
}

// Fetch categories & materials
$categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
$materials = $pdo->query("SELECT * FROM materials ORDER BY id ASC")->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h2 class="font-serif">Categories & Materials Panel</h2>
</div>

<!-- Alerts -->
<?php if ($success_msg): ?>
    <div class="alert alert-success" style="border-radius:0;"><?= $success_msg ?></div>
<?php endif; ?>
<?php if ($error_msg): ?>
    <div class="alert alert-danger" style="border-radius:0;"><?= $error_msg ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Category Manager Column -->
    <div class="col-md-7">
        <div class="bg-white p-4 border shadow-sm">
            <h4 class="font-serif mb-3">TGD Categories</h4>
            
            <div class="table-responsive mb-4" data-lenis-prevent>
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <img src="<?= BASE_URL ?>/<?= sanitize($cat['image_url']) ?>" alt="cat" loading="lazy" decoding="async" width="40" height="40" class="img-thumbnail" style="object-fit: cover;">
                                </td>
                                <td class="fw-bold"><?= sanitize($cat['name']) ?></td>
                                <td class="small text-muted"><?= sanitize($cat['description']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h5 class="font-serif border-top pt-3 mb-3">Add New Category</h5>
            <form action="" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="cat_name" class="form-label fw-semibold">Category Name *</label>
                    <input type="text" class="form-control border-secondary" id="cat_name" name="cat_name" required style="border-radius:0;">
                </div>
                <div class="mb-3">
                    <label for="cat_desc" class="form-label fw-semibold">Short Description</label>
                    <input type="text" class="form-control border-secondary" id="cat_desc" name="cat_desc" style="border-radius:0;">
                </div>
                <div class="mb-3">
                    <label for="cat_image" class="form-label fw-semibold">Banner Image</label>
                    <input type="file" class="form-control border-secondary" id="cat_image" name="cat_image" style="border-radius:0;">
                </div>
                <button type="submit" name="add_category" class="btn btn-gold py-2 w-100">Create Category</button>
            </form>
        </div>
    </div>

    <!-- Materials Manager Column -->
    <div class="col-md-5">
        <div class="bg-white p-4 border shadow-sm">
            <h4 class="font-serif mb-3">Material Subcategories</h4>
            
            <div class="table-responsive mb-4" data-lenis-prevent>
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Material Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materials as $mat): ?>
                            <tr>
                                <td><?= $mat['id'] ?></td>
                                <td class="fw-bold"><?= sanitize($mat['name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h5 class="font-serif border-top pt-3 mb-3">Add New Material</h5>
            <form action="" method="POST">
        <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="mat_name" class="form-label fw-semibold">Material Name *</label>
                    <input type="text" class="form-control border-secondary" id="mat_name" name="mat_name" required style="border-radius:0;">
                    <small class="text-muted">e.g. Acrylic, MDF, Stainless Steel.</small>
                </div>
                <button type="submit" name="add_material" class="btn btn-outline-gold py-2 w-100">Create Material</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
