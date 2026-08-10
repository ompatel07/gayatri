<?php
$page_title = 'About Us';
$page_description = 'The Gayatri Decors has manufactured premium home decor in India since 2012 - wall art, frames, mirrors, custom nameplates and metal decor, with bespoke customisation.';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumbs -->
<div class="breadcrumb-section">
    <div class="container">
        <h1 class="font-serif fs-3">About Us</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">About Us</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Intro -->
<section class="about-intro py-5">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="about-eyebrow">Established 2012</span>
                <h2 class="font-serif about-heading mb-3">Crafting Spaces,<br>Creating Impressions</h2>
                <div class="about-rule"></div>
                <p class="about-lead">
                    <strong>The Gayatri Decors</strong> is an Indian manufacturer and supplier of premium
                    home décor and decorative products, dedicated to transforming everyday spaces into
                    beautiful, inspiring environments.
                </p>
                <p>
                    With years of experience in the décor industry, we combine creative design, quality
                    craftsmanship, modern manufacturing techniques and attention to detail to create
                    products that complement a wide range of interior styles. From contemporary homes and
                    offices to commercial spaces and hospitality interiors, our products are designed to
                    add character, elegance and a distinctive finishing touch.
                </p>
                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="<?= BASE_URL ?>/shop.php" class="btn btn-gold">Explore Our Collection</a>
                    <a href="https://wa.me/919227147646?text=Hello%20The%20Gayatri%20Decors%2C%20I%20would%20like%20to%20know%20more%20about%20your%20products."
                       target="_blank" rel="noopener noreferrer" class="btn btn-outline-gold">
                        <i class="bi bi-whatsapp me-1"></i> Talk to Us
                    </a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="about-figure">
                    <?= responsive_img('assets/images/products/mukesh_nameplate_main.png',
                            'Handcrafted decor by The Gayatri Decors',
                            '(max-width: 992px) 92vw, 40vw') ?>
                </div>
                <div class="about-since">
                    <span class="about-since-num">12+</span>
                    <span class="about-since-txt">Years of<br>Craftsmanship</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Product range -->
<section class="about-dark py-5">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="about-eyebrow-light">- What We Make -</span>
            <h2 class="display-6 font-serif text-white">Our Product Range</h2>
            <p class="about-sub mx-auto">Our range evolves continuously with changing interior trends,
               customer preferences and innovative design concepts.</p>
            <div class="about-rule mx-auto"></div>
        </div>
        <div class="row g-3 g-md-4">
            <?php
            $range = [
                ['bi-image',          'Wall Frames'],
                ['bi-palette',        'Wall Art'],
                ['bi-aspect-ratio',   'Wall Mirrors'],
                ['bi-house-door',     'Custom Name Plates'],
                ['bi-stars',          'Decorative Wall Décor'],
                ['bi-hexagon',        'Metal Décor Products'],
                ['bi-lamp',           'Home Accessories'],
                ['bi-sliders',        'Customized Solutions'],
            ];
            foreach ($range as [$icon, $label]): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="about-range-card h-100">
                        <i class="bi <?= $icon ?>"></i>
                        <span><?= $label ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Quality + modern spaces -->
<section class="py-5" style="background: var(--white);">
    <div class="container py-4">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-6">
                <div class="about-panel h-100">
                    <i class="bi bi-patch-check-fill about-panel-icon"></i>
                    <h3 class="font-serif">Our Commitment to Quality</h3>
                    <p>Quality is at the heart of everything we create. From selecting materials to
                       manufacturing, finishing and final inspection, we hold high standards at every stage.</p>
                    <p class="mb-0">A décor product should not only look attractive — it should offer
                       <strong>durability, functionality and lasting visual appeal</strong>. Our experienced
                       team works carefully so every piece reflects that commitment.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="about-panel h-100">
                    <i class="bi bi-house-heart-fill about-panel-icon"></i>
                    <h3 class="font-serif">Designed for Modern Spaces</h3>
                    <p>Every space is unique. Our collections are designed to suit different tastes and
                       interiors — from minimal and modern to elegant, artistic, traditional and contemporary.</p>
                    <p class="mb-0">Whether you are decorating a living room, bedroom, office, entrance area,
                       hotel, restaurant or commercial space, we offer décor solutions that make your
                       surroundings more expressive and beautiful.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Customization -->
<section class="about-accent py-5">
    <div class="container py-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="about-eyebrow-light">- Made For You -</span>
                <h2 class="display-6 font-serif text-white mb-3">Customization</h2>
                <p class="about-sub mb-0">
                    Alongside our regular range we specialise in customised décor and name plate solutions.
                    We work closely with customers to create designs based on preferred
                    <strong class="text-white">size, style, material, finish and requirements</strong> —
                    turning your ideas into décor pieces that are uniquely yours.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="https://wa.me/919227147646?text=Hello%20The%20Gayatri%20Decors%2C%20I%20would%20like%20a%20custom%20design%20quote."
                   target="_blank" rel="noopener noreferrer" class="btn btn-gold btn-lg w-100 w-lg-auto">
                    <i class="bi bi-whatsapp me-1"></i> Start a Custom Order
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Vision & mission -->
<section class="py-5" style="background: var(--cream-bg);">
    <div class="container py-4">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="about-vm h-100">
                    <i class="bi bi-eye-fill"></i>
                    <h3 class="font-serif">Our Vision</h3>
                    <p class="mb-0">To become a trusted and recognised name in the home décor industry by
                       delivering innovative designs, superior quality and beautifully crafted products
                       that enhance spaces across India and global markets.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="about-vm h-100">
                    <i class="bi bi-compass-fill"></i>
                    <h3 class="font-serif">Our Mission</h3>
                    <p class="mb-0">To create high-quality home décor products that combine design,
                       craftsmanship, affordability and durability, while continuously innovating to meet
                       the evolving needs of our customers.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why choose us -->
<section class="about-dark py-5">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="about-eyebrow-light">- The Gayatri Difference -</span>
            <h2 class="display-6 font-serif text-white">Why Choose The Gayatri Decors?</h2>
            <div class="about-rule mx-auto"></div>
        </div>
        <div class="row g-4">
            <?php
            $why = [
                ['bi-award',          'Established Since 2012', 'Years of experience in home décor manufacturing.'],
                ['bi-building',       'Manufacturer',           'Strong focus on product quality and manufacturing expertise.'],
                ['bi-brush',          'Creative Designs',       'Contemporary, attractive designs for modern interiors.'],
                ['bi-rulers',         'Custom Solutions',       'Products customised to your exact requirements.'],
                ['bi-gem',            'Quality Craftsmanship',  'Careful attention to materials, finishing and detailing.'],
                ['bi-grid-3x3-gap',   'Wide Product Range',     'Wall art, frames, mirrors, name plates and more.'],
            ];
            foreach ($why as [$icon, $title, $copy]): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="about-why h-100">
                        <i class="bi <?= $icon ?>"></i>
                        <div>
                            <h5 class="font-serif mb-1"><?= $title ?></h5>
                            <p class="mb-0"><?= $copy ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Promise -->
<section class="py-5" style="background: var(--white);">
    <div class="container py-4">
        <div class="about-promise text-center mx-auto">
            <i class="bi bi-quote about-quote"></i>
            <h2 class="font-serif mb-3">Our Promise</h2>
            <p class="about-lead mb-4">
                We don't simply manufacture décor products — we create pieces that help people
                <strong>express their style, enhance their spaces and create lasting impressions</strong>.
            </p>
            <p class="text-muted">
                With a passion for design and a commitment to quality, we continue to grow as a trusted
                home décor manufacturer, bringing creativity and craftsmanship into homes everywhere.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
                <a href="<?= BASE_URL ?>/shop.php" class="btn btn-gold btn-lg">Browse Products</a>
                <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-gold btn-lg">Back to Home</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
