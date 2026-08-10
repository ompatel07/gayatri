<?php
require_once __DIR__ . '/includes/header.php';

// Fetch featured products
$featured_stmt = $pdo->query("SELECT p.*, c.name as category_name 
                              FROM products p 
                              LEFT JOIN categories c ON p.category_id = c.id 
                              WHERE p.is_featured = 1 
                              ORDER BY p.id DESC
                              LIMIT 12");
$featured_products = $featured_stmt->fetchAll();

// Client asked for 3 slides of products on the home page: 12 featured items
// split into 3 slides of 4 (2-up on tablet, 1-up on phones).
$featured_slides = array_chunk($featured_products, 4);

// Fetch categories with product count
$cat_stmt = $pdo->query("SELECT c.*, COUNT(p.id) as item_count 
                         FROM categories c 
                         LEFT JOIN products p ON c.id = p.category_id 
                         GROUP BY c.id 
                         ORDER BY c.id ASC");
$categories = $cat_stmt->fetchAll();
?>

<!-- Luxury Cinematic Intro overlay -->
<div id="luxury-intro">
    <div id="intro-bg"></div>
    <div id="intro-radial"></div>
    <canvas id="intro-canvas"></canvas>
    <button class="intro-skip-btn" id="skipIntroBtn">Skip Intro</button>
    <div id="intro-content">
        <div class="intro-logo text-center" id="introLogo">
            <img src="<?= BASE_URL ?>/assets/images/gayatri-full-logo.png" alt="The Gayatri Decors Logo" class="intro-full-logo">
        </div>
        <h1 class="intro-title" id="introTitle">Transform Your House Into a Dream Home</h1>
        <div class="intro-line" id="introLine"></div>
        <p class="intro-subtitle" id="introSubtitle">Premium Home Decor &bull; Modern Furniture &bull; Elegant Living</p>
        <div class="launch-offer" id="launchOffer">
            <span class="launch-offer-badge">LAUNCH OFFER</span>
            <strong>30% OFF</strong>
            <span class="launch-offer-text">ALL MDF WALL ART</span>
            <small>Valid for the first 3 months</small>
        </div>
        <div class="intro-btn-group" id="introBtnGroup">
            <button class="btn btn-luxury-gold" id="exploreBtn">Explore Collection</button>
            <button class="btn btn-luxury" id="shopBtn">Shop Now</button>
        </div>
    </div>
</div>

<!-- Hero with rotating product backgrounds -->
<?php $slides = hero_slides(); ?>
<section class="hero-section" id="heroSection" data-hero-interval="7000">
    <div class="hero-slides" aria-hidden="true">
        <?php foreach ($slides as $i => $s): ?>
            <div class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>" data-index="<?= $i ?>">
                <?= responsive_img($s['img'], '', '100vw', ['lazy' => $i !== 0]) ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="hero-overlay" aria-hidden="true"></div>

    <div class="container">
        <div class="hero-content">
            <h5 class="text-uppercase text-warning letter-spacing-3 mb-3">Premium Handcrafted Decors</h5>
            <h1 class="hero-title">Elevate Your Living Space With <span>Art</span></h1>
            <p class="lead mb-4">Discover our collection of exclusive 3D MDF clocks, designer metal installations, high-end nameplates, and bespoke decor mirrors.</p>
            <div class="hero-actions">
                <a href="<?= BASE_URL ?>/shop.php" class="btn btn-gold btn-lg me-3">Explore Collection</a>
                <a href="<?= BASE_URL ?>/shop.php?category=wall-clock" class="btn btn-outline-gold btn-lg">View Clocks</a>
            </div>

            <!-- Which collection the current background is from -->
            <div class="hero-slider-ui">
                <a class="hero-chip" id="heroChip" href="<?= BASE_URL ?>/<?= $slides[0]['href'] ?>">
                    <span class="hero-chip-pulse" aria-hidden="true"></span>
                    <span id="heroChipLabel"><?= sanitize($slides[0]['label']) ?></span>
                    <i class="bi bi-arrow-right ms-1"></i>
                </a>
                <div class="hero-progress" id="heroProgress" role="tablist" aria-label="Hero slides">
                    <?php foreach ($slides as $i => $s): ?>
                        <button type="button" role="tab"
                                class="<?= $i === 0 ? 'is-active' : '' ?>"
                                data-index="<?= $i ?>"
                                aria-label="<?= sanitize($s['label']) ?>"
                                aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"><span></span></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="heroSlideData"><?= json_encode(array_map(
        fn($s) => ['label' => $s['label'], 'href' => BASE_URL . '/' . $s['href']],
        $slides), JSON_UNESCAPED_UNICODE) ?></script>
</section>

<!-- Trust & Stats Bar -->
<div class="home-stats-bar" style="background: #1F2715 !important; border-top: 1px solid rgba(212,175,55,0.3); border-bottom: 1px solid rgba(212,175,55,0.3); padding: 25px 0;">
    <div class="container">
        <div class="row g-3">
            <div class="col-6 col-md-3 stat-item">
                <div class="stat-num" style="color: #A3B18A !important;">5,000+</div>
                <div class="stat-label" style="color: #E3E7D9 !important;">Happy Homes</div>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <div class="stat-num" style="color: #A3B18A !important;">100%</div>
                <div class="stat-label" style="color: #E3E7D9 !important;">Handcrafted MDF</div>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <div class="stat-num" style="color: #A3B18A !important;">4.9 ★</div>
                <div class="stat-label" style="color: #E3E7D9 !important;">Customer Rating</div>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <div class="stat-num" style="color: #A3B18A !important;">Pan-India</div>
                <div class="stat-label" style="color: #E3E7D9 !important;">Express Delivery</div>
            </div>
        </div>
    </div>
</div>

<!-- Why Choose Us Section (Luxury Dark Theme) -->
<section class="py-5" style="background: #26301A !important; color: #FFFFFF !important;">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="text-warning text-uppercase tracking-widest d-block mb-2">- The Gayatri Difference -</span>
            <h2 class="display-6 font-serif text-white">Why Choose Our Masterpieces</h2>
            <div style="width: 60px; height: 3px; background-color: #6B7A2F; margin: 15px auto;"></div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-box text-center" style="background: #39452A !important; border: 1px solid rgba(212,175,55,0.3) !important; padding: 30px 20px; border-radius: 12px;">
                    <i class="bi bi-gem fs-1 text-warning mb-3 d-block"></i>
                    <h5 class="text-white font-serif mb-2">Laser-Cut Precision</h5>
                    <p class="small mb-0" style="color: #D8DEC9 !important;">Multi-layered 3D depth carved with state-of-the-art German laser machinery.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box text-center" style="background: #39452A !important; border: 1px solid rgba(212,175,55,0.3) !important; padding: 30px 20px; border-radius: 12px;">
                    <i class="bi bi-palette fs-1 text-warning mb-3 d-block"></i>
                    <h5 class="text-white font-serif mb-2">Bespoke Customization</h5>
                    <p class="small mb-0" style="color: #D8DEC9 !important;">Custom family names, sizes, color palettes, and optional LED backlighting.</p>
                </div>
            </div>
<?php // "Transit Protection" removed at the client's request. ?>
            <div class="col-md-4">
                <div class="feature-box text-center" style="background: #39452A !important; border: 1px solid rgba(212,175,55,0.3) !important; padding: 30px 20px; border-radius: 12px;">
                    <i class="bi bi-truck fs-1 text-warning mb-3 d-block"></i>
                    <h5 class="text-white font-serif mb-2">Pan-India Express</h5>
                    <p class="small mb-0" style="color: #D8DEC9 !important;">Fast track doorstep delivery across 25,000+ pin codes in India.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section (Curated Selections) -->
<section class="py-5" style="background: #39452A !important; color: #FFFFFF !important;">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="text-warning text-uppercase tracking-widest d-block mb-2">- Curated Selections -</span>
            <h2 class="display-6 font-serif text-white">Browse Categories</h2>
            <p class="mx-auto" style="max-width: 600px; color: #94A3B8 !important;">Explore handcrafted home decor collections tailored for modern apartments, luxury villas, and elegant workspaces.</p>
            <div style="width: 60px; height: 3px; background-color: #6B7A2F; margin: 15px auto;"></div>
        </div>
        <div class="row g-4">
            <?php foreach ($categories as $index => $cat): 
                $tag = ($index % 3 == 0) ? 'Trending' : (($index % 2 == 0) ? 'Popular' : 'Best Seller');
            ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card category-card" style="height: 350px; background: #26301A !important; border: 1px solid rgba(212,175,55,0.3) !important; border-radius: 12px !important; overflow: hidden; position: relative;">
                        <span class="category-tag" style="position: absolute; top: 15px; left: 15px; background: #6B7A2F !important; color: #26301A !important; font-weight: 700; font-size: 0.7rem; padding: 4px 10px; border-radius: 4px; z-index: 5; text-transform: uppercase;"><?= $tag ?></span>
                        <span class="category-badge-count" style="position: absolute; top: 15px; right: 15px; background: rgba(15,23,42,0.9) !important; color: #A3B18A !important; border: 1px solid rgba(212,175,55,0.5) !important; font-size: 0.75rem; padding: 4px 12px; border-radius: 20px; z-index: 5; text-transform: uppercase;"><?= $cat['item_count'] ?> Products</span>
                        <?= responsive_img($cat['image_url'], $cat['name'], SIZES_CATEGORY, ['style' => 'width: 100%; height: 100%; object-fit: cover;']) ?>
                        <div class="category-overlay" style="position: absolute; bottom: 0; left: 0; right: 0; top: 0; background: linear-gradient(to top, rgba(15, 23, 42, 0.95) 25%, rgba(15, 23, 42, 0.2) 75%) !important; padding: 25px; display: flex; flex-direction: column; justify-content: flex-end;">
                            <h3 class="category-title" style="color: #FFFFFF !important; font-size: 1.5rem; margin-bottom: 8px;"><?= sanitize($cat['name']) ?></h3>
                            <p class="category-desc" style="color: #D8DEC9 !important; font-size: 0.88rem; margin-bottom: 15px;"><?= sanitize($cat['description']) ?></p>
                            <a href="<?= BASE_URL ?>/shop.php?category=<?= sanitize($cat['slug']) ?>" class="btn btn-gold btn-sm align-self-start"><i class="bi bi-arrow-right"></i> Shop Category</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Promotional Banner (Ember & Nameplate Spotlight) -->
<section class="py-5 text-white position-relative" style="background: linear-gradient(rgba(15,23,42,0.92), rgba(15,23,42,0.92)), url('<?= BASE_URL ?>/assets/images/products/ember_colour.jpg') no-repeat center center; background-size: cover; background-attachment: fixed;">
    <div class="container py-5 text-center position-relative" style="z-index: 2;">
        <span class="badge bg-warning text-dark px-3 py-2 text-uppercase letter-spacing-2 mb-3">Limited Collection</span>
        <h2 class="display-5 font-serif mb-4 text-white">The 3D Ember & LED Nameplate Spotlight</h2>
        <p class="mx-auto mb-4" style="max-width: 750px; font-size: 1.1rem; color: #D8DEC9 !important;">Experience depth in motion. Our signature 3D MDF clocks and LED backlit acrylic nameplates project mesmerizing warm golden halo lighting, turning any wall or doorway into an architectural focal point.</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="<?= BASE_URL ?>/shop.php?category=nameplates" class="btn btn-gold btn-lg"><i class="bi bi-door-open"></i> View Nameplates</a>
            <a href="<?= BASE_URL ?>/shop.php?category=wall-clock" class="btn btn-outline-gold btn-lg"><i class="bi bi-clock"></i> View 3D Clocks</a>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="py-5" style="background: #26301A !important; color: #FFFFFF !important;">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="text-warning text-uppercase tracking-widest d-block mb-2">- Handpicked for You -</span>
            <h2 class="display-6 font-serif text-white">Featured Masterpieces</h2>
            <p style="color: #94A3B8 !important;">Our most loved hand-carved clocks, sacred yantras, and custom entrance nameplates.</p>
            <div style="width: 60px; height: 3px; background-color: #6B7A2F; margin: 15px auto;"></div>
        </div>
        <!-- 3 product slides -->
        <div id="featuredCarousel" class="carousel slide product-carousel" data-bs-ride="carousel" data-bs-interval="6000" data-bs-touch="true">
          <div class="carousel-inner">
          <?php foreach ($featured_slides as $slide_index => $slide): ?>
            <div class="carousel-item <?= $slide_index === 0 ? 'active' : '' ?>">
        <div class="row g-4">
            <?php foreach ($slide as $product): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="card product-card" style="background: #39452A !important; border: 1px solid rgba(212,175,55,0.25) !important; border-radius: 12px; overflow: hidden; margin-bottom: 0;">
                        <div class="img-container" style="position: relative; height: 260px; overflow: hidden; background: #26301A;">
                            <span class="product-badge" style="position: absolute; top: 12px; left: 12px; background: #6B7A2F; color: #26301A; font-weight: 700; font-size: 0.75rem; padding: 4px 10px; z-index: 5;">Featured</span>
                            <?= responsive_img($product['image_url'], $product['name'], SIZES_FEATURED, ['class' => 'img-main', 'style' => 'width: 100%; height: 100%; object-fit: cover;']) ?>
                            <?php if (!empty($product['hover_image_url'])): ?>
                                <?= responsive_img($product['hover_image_url'], $product['name'], SIZES_FEATURED, ['class' => 'img-hover', 'style' => 'width: 100%; height: 100%; object-fit: cover;']) ?>
                            <?php endif; ?>
                            <div class="product-actions" style="position: absolute; bottom: 0; left: 0; right: 0; display: flex; justify-content: center; gap: 8px; padding: 10px; background: rgba(15,23,42,0.85); z-index: 5;">
                                <a href="<?= BASE_URL ?>/product.php?slug=<?= sanitize($product['slug']) ?>" class="btn btn-gold btn-sm"><i class="bi bi-eye"></i> View</a>
                                <?php if (has_variants($pdo, $product['id'])): ?>
                                    <a href="<?= BASE_URL ?>/product.php?slug=<?= sanitize($product['slug']) ?>" class="btn btn-outline-gold btn-sm text-white border-white"><i class="bi bi-sliders"></i> Options</a>
                                <?php else: ?>
                                    <form action="<?= BASE_URL ?>/cart.php" method="POST" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="qty" value="1">
                                        <button type="submit" class="btn btn-outline-gold btn-sm text-white border-white"><i class="bi bi-cart-plus"></i> Add</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="product-info" style="padding: 18px; text-align: center; background: #39452A !important;">
                            <span class="product-cat" style="color: #A3B18A !important; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;"><?= sanitize($product['category_name']) ?></span>
                            <h4 class="product-title" style="margin: 6px 0;">
                                <a href="<?= BASE_URL ?>/product.php?slug=<?= sanitize($product['slug']) ?>" style="color: #FFFFFF !important; font-size: 1.05rem; text-decoration: none;"><?= sanitize($product['name']) ?></a>
                            </h4>
                            <div class="text-warning mb-2 small">
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <span class="ms-1" style="color: #94A3B8 !important;">(5.0)</span>
                            </div>
                            <div class="product-price">
                                <?php $from = product_from_price($pdo, $product['id']); ?>
                                <?php if ($from !== null): ?>
                                    <small style="color: #94A3B8 !important;">from</small>
                                    <span style="color: #A3B18A !important; font-weight: 700; font-size: 1.1rem;"><?= format_price($from) ?></span>
                                <?php elseif (!empty($product['sale_price'])): ?>
                                    <del style="color: #66705A !important; margin-right: 8px;"><?= format_price($product['price']) ?></del>
                                    <span style="color: #A3B18A !important; font-weight: 700; font-size: 1.1rem;"><?= format_price($product['sale_price']) ?></span>
                                <?php else: ?>
                                    <span style="color: #A3B18A !important; font-weight: 700; font-size: 1.1rem;"><?= format_price($product['price']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
            </div>
          <?php endforeach; ?>
          </div>

          <?php if (count($featured_slides) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#featuredCarousel" data-bs-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Previous products</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#featuredCarousel" data-bs-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Next products</span>
            </button>

            <div class="carousel-indicators product-carousel-dots">
              <?php foreach ($featured_slides as $i => $_): ?>
                <button type="button" data-bs-target="#featuredCarousel" data-bs-slide-to="<?= $i ?>"
                        class="<?= $i === 0 ? 'active' : '' ?>"
                        aria-label="Product slide <?= $i + 1 ?>"></button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="text-center mt-5">
            <a href="<?= BASE_URL ?>/shop.php" class="btn btn-gold btn-lg"><i class="bi bi-grid-3x3-gap"></i> Explore All Products</a>
        </div>
    </div>
</section>

<!-- Craftsmanship Spotlight Section -->
<section class="py-5" style="background: #39452A !important; color: #FFFFFF !important;">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="text-warning text-uppercase tracking-widest d-block mb-2">- Behind The Scenes -</span>
                <h2 class="display-6 font-serif text-white mb-4">The Art of Precision Craftsmanship</h2>
                <p style="color: #D8DEC9 !important;" class="mb-4">Every piece at The Gayatri Decors begins with raw high-density moisture-resistant MDF and acrylic. Our master artisans combine digital CAD precision laser cutting with traditional hand-polishing and multi-coat lacquering.</p>
                
                <div class="craft-step-card" style="display: flex; align-items: flex-start; gap: 18px; margin-bottom: 22px;">
                    <div class="craft-step-num" style="width: 45px; height: 45px; min-width: 45px; background: #6B7A2F; color: #26301A; font-family: 'Cinzel', serif; font-weight: 800; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; border-radius: 50%;">1</div>
                    <div class="craft-step-content">
                        <h5 style="color: #FFFFFF !important; margin-bottom: 4px;">3D Layered Architecture</h5>
                        <p style="color: #D8DEC9 !important; margin: 0; font-size: 0.88rem;">Stacking up to 7 distinct laser-carved layers to create dramatic shadow depths.</p>
                    </div>
                </div>
                
                <div class="craft-step-card" style="display: flex; align-items: flex-start; gap: 18px; margin-bottom: 22px;">
                    <div class="craft-step-num" style="width: 45px; height: 45px; min-width: 45px; background: #6B7A2F; color: #26301A; font-family: 'Cinzel', serif; font-weight: 800; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; border-radius: 50%;">2</div>
                    <div class="craft-step-content">
                        <h5 style="color: #FFFFFF !important; margin-bottom: 4px;">Hand-Polished & Sealed Finish</h5>
                        <p style="color: #D8DEC9 !important; margin: 0; font-size: 0.88rem;">Smooth satin finish with moisture-proof protective coat for lifelong color retention.</p>
                    </div>
                </div>
                
                <div class="craft-step-card" style="display: flex; align-items: flex-start; gap: 18px; margin-bottom: 22px;">
                    <div class="craft-step-num" style="width: 45px; height: 45px; min-width: 45px; background: #6B7A2F; color: #26301A; font-family: 'Cinzel', serif; font-weight: 800; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; border-radius: 50%;">3</div>
                    <div class="craft-step-content">
                        <h5 style="color: #FFFFFF !important; margin-bottom: 4px;">Warm Ambient LED Integration</h5>
                        <p style="color: #D8DEC9 !important; margin: 0; font-size: 0.88rem;">Energy-efficient waterproof warm white LED modules casting an enchanting halo glow.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="position-relative p-2 rounded border border-warning border-opacity-25" style="background: rgba(255,255,255,0.03);">
                    <img src="<?= BASE_URL ?>/assets/images/products/mukesh_nameplate_main.png" alt="Craftsmanship Showcase" class="img-fluid rounded" style="width: 100%; height: 420px; object-fit: cover;">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- WhatsApp Direct Inquiry & Custom Order Banner -->
<section class="py-5 text-white position-relative" style="background: linear-gradient(135deg, #26301A 0%, #39452A 100%); border-top: 2px solid #6B7A2F; border-bottom: 2px solid #25D366; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
    <div class="container py-3">
        <div class="row align-items-center g-4">
            <div class="col-lg-2 text-center">
                <div style="background: #ffffff; padding: 6px; border-radius: 50%; width: 120px; height: 120px; margin: 0 auto; display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 25px rgba(212,175,55,0.4); border: 3px solid #6B7A2F;">
                    <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="The Gayatri Decors Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                </div>
            </div>
            <div class="col-lg-7 text-center text-lg-start">
<?php // bg-opacity-20 is not a Bootstrap step (10/25/50/75/100 only), so this
      // fell back to solid green with green text on top - an unreadable blob. ?>
                <div class="d-inline-flex align-items-center gap-2 border border-success px-3 py-1 rounded-pill mb-2"
                     style="background: rgba(37, 211, 102, 0.15); color: #ffffff;">
                    <i class="bi bi-whatsapp fs-5" style="color: #25D366;"></i>
                    <span style="font-weight: 700; font-size: 0.82rem; letter-spacing: 0.5px;">INSTANT WHATSAPP CHAT</span>
                </div>
                <h3 class="font-serif text-white mb-2" style="font-size: 1.75rem;">Need Custom Sizes or Personalized Decor?</h3>
                <p class="text-white-50 mb-0" style="font-size: 1rem; line-height: 1.5;">Connect directly with <strong>The Gayatri Decors</strong> on WhatsApp. Get instant design consultation, custom size quotes, LED backlit previews, and order updates.</p>
            </div>
            <div class="col-lg-3 text-center text-lg-end">
                <a href="https://wa.me/919227147646?text=Hello%20The%20Gayatri%20Decors%2C%20I%20want%20to%20inquire%20about%20your%20products." 
                   target="_blank" 
                   rel="noopener noreferrer" 
                   class="btn btn-success btn-lg px-4 py-3 d-inline-flex align-items-center justify-content-center gap-2 shadow-lg" 
                   style="border-radius: 50px; font-weight: 700; background-color: #25D366; border: none; font-size: 1.05rem; transition: transform 0.3s ease;">
                    <i class="bi bi-whatsapp fs-3"></i>
                    <span>Message Us on WhatsApp</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Customer Reviews / Testimonials -->
<section class="py-5" style="background: #F7F5E8 !important; color: #26301A !important;">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="text-warning text-uppercase tracking-widest d-block mb-2">- Client Love -</span>
            <h2 class="display-6 font-serif" style="color: #26301A !important;">What Our Customers Say</h2>
            <div style="width: 60px; height: 3px; background-color: #6B7A2F; margin: 15px auto;"></div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="testimonial-card" style="background: #FFFFFF !important; border: 1px solid rgba(212,175,55,0.3) !important; padding: 30px; border-radius: 12px;">
                    <div class="testimonial-stars" style="color: #F59E0B; margin-bottom: 12px;">
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <p class="testimonial-text" style="color: #39452A !important; font-style: italic; font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px;">"The Ganesh LED Backlit Nameplate transformed our villa entrance! The gold mirror finish by day and glowing amber halo at night look truly regal. Highly recommended!"</p>
                    <div class="testimonial-author" style="display: flex; align-items: center; gap: 12px;">
                        <div class="testimonial-avatar" style="width: 42px; height: 42px; border-radius: 50%; background: #A3B18A; color: #26301A; font-weight: 700; display: flex; align-items: center; justify-content: center;">RP</div>
                        <div class="testimonial-info">
                            <h6 style="margin: 0; font-size: 0.95rem; color: #26301A !important;">Rajesh Patel</h6>
                            <span style="font-size: 0.78rem; color: #66705A !important;">Ahmedabad, Gujarat &bull; Verified Buyer</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="testimonial-card" style="background: #FFFFFF !important; border: 1px solid rgba(212,175,55,0.3) !important; padding: 30px; border-radius: 12px;">
                    <div class="testimonial-stars" style="color: #F59E0B; margin-bottom: 12px;">
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <p class="testimonial-text" style="color: #39452A !important; font-style: italic; font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px;">"Intricate Swirl 3D Wall Art looks like an expensive art gallery museum piece in our living room. The multi-layered depth and vibrant color tones are unbelievable!"</p>
                    <div class="testimonial-author" style="display: flex; align-items: center; gap: 12px;">
                        <div class="testimonial-avatar" style="width: 42px; height: 42px; border-radius: 50%; background: #A3B18A; color: #26301A; font-weight: 700; display: flex; align-items: center; justify-content: center;">MS</div>
                        <div class="testimonial-info">
                            <h6 style="margin: 0; font-size: 0.95rem; color: #26301A !important;">Meera Sharma</h6>
                            <span style="font-size: 0.78rem; color: #66705A !important;">Mumbai, Maharashtra &bull; Verified Buyer</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="testimonial-card" style="background: #FFFFFF !important; border: 1px solid rgba(212,175,55,0.3) !important; padding: 30px; border-radius: 12px;">
                    <div class="testimonial-stars" style="color: #F59E0B; margin-bottom: 12px;">
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <p class="testimonial-text" style="color: #39452A !important; font-style: italic; font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px;">"Super fast Pan-India delivery and 5-layer foam packaging ensured my Sri Yantra Gold Tabletop Frame arrived in flawless condition. Quality is 10/10."</p>
                    <div class="testimonial-author" style="display: flex; align-items: center; gap: 12px;">
                        <div class="testimonial-avatar" style="width: 42px; height: 42px; border-radius: 50%; background: #A3B18A; color: #26301A; font-weight: 700; display: flex; align-items: center; justify-content: center;">VD</div>
                        <div class="testimonial-info">
                            <h6 style="margin: 0; font-size: 0.95rem; color: #26301A !important;">Vikram Dave</h6>
                            <span style="font-size: 0.78rem; color: #66705A !important;">Surat, Gujarat &bull; Verified Buyer</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom Orders Consultation CTA -->
<section class="py-5" style="background: #FFFFFF !important;">
    <div class="container">
        <div class="custom-cta-banner text-center text-md-start" style="background: linear-gradient(135deg, #26301A 0%, #39452A 60%, #4D5A3A 100%) !important; border: 1px solid rgba(212, 175, 55, 0.4) !important; border-radius: 16px; padding: 45px 35px;">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge bg-warning text-dark px-3 py-2 text-uppercase mb-2">Bespoke Design Service</span>
                    <h3 class="font-serif text-white display-6 mb-2">Need a Custom Nameplate or Special Size?</h3>
                    <p class="mb-0" style="color: #D8DEC9 !important;">Our artisans can customize any design with your family name, specific dimension, custom color theme, or LED illumination.</p>
                </div>
                <div class="col-lg-4 text-center text-lg-end">
<?php // This pointed at 919876543210 - a placeholder that is not the business number. ?>
                    <a href="https://wa.me/919227147646?text=Hello%20The%20Gayatri%20Decors%2C%20I%20would%20like%20a%20custom%20design%20quote."
                       target="_blank" rel="noopener noreferrer"
                       class="btn btn-gold btn-lg me-2 mb-2"><i class="bi bi-whatsapp"></i> Start a Custom Order</a>
                    <a href="<?= BASE_URL ?>/shop.php" class="btn btn-outline-gold btn-lg mb-2"><i class="bi bi-grid"></i> Browse Catalog</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Smooth Scroll & Parallax Animations Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Freeze page scrolling during intro playback (using global Lenis instance)
    document.body.classList.add('intro-active');
    if (window.lenis) window.lenis.stop();

    // Canvas Golden Dust Particles
    const canvas = document.getElementById("intro-canvas");
    if (canvas) {
        const ctx = canvas.getContext("2d");
        let width = canvas.width = window.innerWidth;
        let height = canvas.height = window.innerHeight;
        
        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        window.addEventListener("resize", debounce(() => {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        }, 150), { passive: true });

        const particles = [];
        const maxParticles = 50;
        
        class Particle {
            constructor(isSpark = false, x = null, y = null) {
                this.isSpark = isSpark;
                this.x = x !== null ? x : Math.random() * width;
                this.y = y !== null ? y : (isSpark ? y : Math.random() * height);
                this.size = Math.random() * (isSpark ? 1.5 : 2.5) + 0.8;
                this.speedX = isSpark ? (Math.random() - 0.5) * 4 : (Math.random() - 0.5) * 0.3;
                this.speedY = isSpark ? (Math.random() - 0.5) * 4 : -Math.random() * 0.6 - 0.2;
                this.opacity = Math.random() * 0.4 + 0.2;
                this.life = isSpark ? Math.random() * 20 + 8 : null;
            }
            
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                
                if (this.isSpark) {
                    this.life--;
                    this.opacity = this.life / 28;
                } else {
                    if (this.y < -10) {
                        this.y = height + 10;
                        this.x = Math.random() * width;
                    }
                }
            }
            
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(212, 175, 55, ${this.opacity})`;
                ctx.fill();
            }
        }

        for (let i = 0; i < maxParticles; i++) {
            particles.push(new Particle());
        }

        const introBg = document.getElementById("intro-bg");
        
        let targetX = 0;
        let targetY = 0;
        let currentX = 0;
        let currentY = 0;
        const lerpFactor = 0.08;
        let mouseMoved = false;
        let lastMouseMove = 0;

        function handleParallaxMouse(e) {
            const now = performance.now();
            if (now - lastMouseMove < 33) return;
            lastMouseMove = now;

            targetX = (e.clientX / window.innerWidth) - 0.5;
            targetY = (e.clientY / window.innerHeight) - 0.5;
            mouseMoved = true;
            
            if (Math.random() < 0.3) {
                particles.push(new Particle(true, e.clientX, e.clientY));
            }
        }
        window.addEventListener("mousemove", handleParallaxMouse, { passive: true });

        let isTabVisible = true;
        let animationFrameId = null;

        function renderLoop() {
            if (!isTabVisible) return;
            
            if (document.body.classList.contains('intro-active')) {
                ctx.clearRect(0, 0, width, height);
                
                for (let i = particles.length - 1; i >= 0; i--) {
                    particles[i].update();
                    particles[i].draw();
                    if (particles[i].isSpark && particles[i].life <= 0) {
                        particles.splice(i, 1);
                    }
                }
                
                if (mouseMoved && introBg) {
                    currentX += (targetX - currentX) * lerpFactor;
                    currentY += (targetY - currentY) * lerpFactor;
                    introBg.style.transform = `scale(1.05) translate3d(${currentX * 25}px, ${currentY * 25}px, 0)`;
                }
            }
            
            animationFrameId = requestAnimationFrame(renderLoop);
        }
        renderLoop();

        const tl = gsap.timeline();

        document.addEventListener("visibilitychange", () => {
            if (document.hidden) {
                isTabVisible = false;
                if (animationFrameId) cancelAnimationFrame(animationFrameId);
                tl.pause();
            } else {
                isTabVisible = true;
                tl.resume();
                renderLoop();
            }
        });
        
        tl.to("#intro-bg", {
            onStart: () => {
                const bg = document.getElementById("intro-bg");
                if (bg) bg.classList.add("animate-bg");
            },
            duration: 0.1
        }, "+=1.0");
        
        tl.to("#introLogo", { opacity: 1, y: 0, duration: 1.4, ease: "power3.out" }, "+=1.2");
        tl.to("#introTitle", { opacity: 1, y: 0, duration: 1.5, ease: "power3.out" }, "-=1.0");
        tl.to("#introLine", { scaleX: 1, opacity: 1, duration: 1.2, ease: "power2.inOut" }, "-=1.0");
        tl.to("#introSubtitle", { opacity: 1, y: 0, duration: 1.2, ease: "power3.out" }, "-=0.8");
        tl.to("#launchOffer", { opacity: 1, y: 0, duration: 1.1, ease: "power3.out" }, "-=0.6");
        tl.to("#introBtnGroup", { opacity: 1, y: 0, duration: 1.2, ease: "power3.out" }, "-=0.5");

        function dismissIntro() {
            if (typeof tl !== 'undefined' && tl) tl.kill();
            isTabVisible = false;
            if (animationFrameId) cancelAnimationFrame(animationFrameId);
            window.removeEventListener("mousemove", handleParallaxMouse);
            
            gsap.to("#luxury-intro", {
                yPercent: -100,
                opacity: 0,
                duration: 1.5,
                ease: "power4.inOut",
                onComplete: () => {
                    const intro = document.getElementById("luxury-intro");
                    if (intro) intro.remove();
                    document.body.classList.remove('intro-active');
                    if (window.lenis) window.lenis.start();
                    if (typeof ScrollTrigger !== 'undefined') ScrollTrigger.refresh();
                }
            });
        }

        const skipBtn = document.getElementById("skipIntroBtn");
        const expBtn  = document.getElementById("exploreBtn");
        const shpBtn  = document.getElementById("shopBtn");
        if (skipBtn) skipBtn.addEventListener("click", dismissIntro);
        if (expBtn)  expBtn.addEventListener("click", dismissIntro);
        if (shpBtn)  shpBtn.addEventListener("click", dismissIntro);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
