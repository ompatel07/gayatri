-- Database Setup Script for The Gayatri Decors (TGD)
CREATE DATABASE IF NOT EXISTS gayatri_db;
USE gayatri_db;

-- 1. Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    city VARCHAR(50) NULL,
    state VARCHAR(50) NULL,
    zip VARCHAR(10) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Categories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT NULL,
    image_url VARCHAR(255) NULL,
    -- One level of nesting: the name plate groups hang off a "Name Plates"
    -- parent. NULL means the category is top level.
    parent_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- 3. Materials / Subcategories
CREATE TABLE IF NOT EXISTS materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(60) NOT NULL UNIQUE
);

-- 4. Category-Material Map
CREATE TABLE IF NOT EXISTS category_materials (
    category_id INT,
    material_id INT,
    PRIMARY KEY (category_id, material_id),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
);

-- 5. Products
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) NULL,
    image_url VARCHAR(255) NOT NULL,
    hover_image_url VARCHAR(255) NULL,
    category_id INT NOT NULL,
    material_id INT NULL,
    stock INT DEFAULT 10,
    is_featured TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (material_id) REFERENCES materials(id)
);

-- 6. Product Gallery
CREATE TABLE IF NOT EXISTS product_gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 7. Orders
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_name VARCHAR(100) NOT NULL,
    shipping_phone VARCHAR(20) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(50) NOT NULL,
    shipping_state VARCHAR(50) NOT NULL,
    shipping_zip VARCHAR(10) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'COD',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    order_status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    -- Razorpay references, NULL for cash-on-delivery orders
    payment_id VARCHAR(64) NULL,
    payment_order_id VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 8. Order Items
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    variant_id INT NULL,
    variant_label VARCHAR(120) NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- 8b. Product variants (size + LED), priced from the client's price list.
-- Products with no rows here are sold at the single products.price.
CREATE TABLE IF NOT EXISTS product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size_label VARCHAR(40) NOT NULL,
    has_led TINYINT(1) NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_variant (product_id, size_label, has_led),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- 9. Reviews
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Seeding Materials
INSERT INTO materials (id, name, slug) VALUES 
(1, 'Metal', 'metal'),
(2, 'MDF', 'mdf'),
(3, 'Acrylic', 'acrylic'),
(4, 'Canvas', 'canvas'),
(5, 'Stainless Steel', 'ss')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Seeding Categories
INSERT INTO categories (id, name, slug, description, image_url) VALUES
(1, 'Wall Art', 'wall-art', 'Exquisite metal, MDF and acrylic decorative wall hangings and murals.', 'assets/images/categories/wall_art.jpg'),
(2, 'Wall Clock', 'wall-clock', 'Unique designer wall clocks in wood, metal and acrylic finishes.', 'assets/images/categories/wall_clock.jpg'),
(3, 'Wall Frames', 'wall-frames', 'Beautifully framed canvas art and high-definition photo collections.', 'assets/images/categories/wall_frames.jpg'),
(4, 'Nameplates', 'nameplates', 'Custom-designed stainless steel and acrylic door nameplates.', 'assets/images/categories/nameplates.jpg'),
(5, 'Tabletop', 'tabletop', 'Stunning tabletop items, miniature structures, and showpieces.', 'assets/images/categories/tabletop.jpg'),
(6, 'Wall Art Mirrors', 'wall-art-mirrors', 'Stunning designer mirrors to amplify the depth and look of your space.', 'assets/images/categories/wall_mirrors.jpg')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Mapping Category-Materials
INSERT INTO category_materials (category_id, material_id) VALUES
(1, 1), (1, 2), (1, 3), -- Wall Art supports Metal, MDF, Acrylic
(2, 1), (2, 2), (2, 3), -- Wall Clock supports Metal, MDF, Acrylic
(3, 3), (3, 4),         -- Wall Frames support Acrylic, Canvas
(4, 3), (4, 5)          -- Nameplates support Acrylic, Stainless Steel
ON DUPLICATE KEY UPDATE category_id=category_id;

-- Seeding Default Users (passwords are 'admin123' and 'customer123' hashed using bcrypt)
INSERT INTO users (id, name, email, password, role, phone, address, city, state, zip) VALUES
-- bcrypt of 'admin123' / 'customer123'; the previous literals here were not
-- valid hashes of anything, so both demo logins were impossible.
(1, 'System Administrator', 'admin@tgd.com', '$2y$10$Mkzignsr5M3Ofks62JKXtO2rBAdn5lMmSFKjgB46S73JTDxoUTKLu', 'admin', '9876543210', '116, Gopinath Estate-1, N.H. No-8, Nr. Soni Ni Chali, Odhav', 'Ahmedabad', 'Gujarat', '382415'),
(2, 'Test Customer', 'customer@tgd.com', '$2y$10$xdQNh/fAk1RTkweCecGt7eD3k82krDry.owGn1IYGqU26hZ6SWLdi', 'customer', '9876543211', '102 Sunrise Residency, Adajan', 'Surat', 'Gujarat', '395009')
ON DUPLICATE KEY UPDATE email=VALUES(email);

-- Seeding Products (Clocks and other categories)
INSERT INTO products (id, name, slug, description, price, sale_price, image_url, hover_image_url, category_id, material_id, stock, is_featured) VALUES
(1, 'Ember (Colour Edition)', 'ember-colour-edition', 'Ember is a 3D MDF wall clock that exudes vintage charm and timeless elegance. With intricate engravings and a warm colour palette, it evokes the essence of a bygone era. The rich textures and detailed craftsmanship make it more than just a clock—it’s a nostalgic statement piece, perfect for adding heritage and sophistication to any space.', 1899.00, 1499.00, 'assets/images/products/ember_colour.jpg', 'assets/images/products/ember_dark.jpg', 2, 2, 15, 1),
(2, 'Ember (Dark Edition)', 'ember-dark-edition', 'Ember is a 3D MDF wall clock that exudes vintage charm and timeless elegance. With intricate engravings and a dark colour palette, it evokes the essence of a bygone era. The rich textures and detailed craftsmanship make it more than just a clock—it’s a nostalgic statement piece, perfect for adding heritage and sophistication to any space.', 1899.00, 1499.00, 'assets/images/products/ember_dark.jpg', 'assets/images/products/ember_colour.jpg', 2, 2, 12, 1),
(3, 'Floral Eternity (Colour Edition)', 'floral-eternity-colour-edition', 'Floral Eternity is a graceful fusion of time and nature. Crafted from premium wooden MDF, its elegant circular form features intricate floral patterns in vibrant yet soothing tones. Symbolizing life’s cycles, this clock adds serene elegance to any space, blending art and function into a timeless décor statement for your home.', 2199.00, 1699.00, 'assets/images/products/floral_eternity_colour.jpg', 'assets/images/products/floral_eternity_dark.jpg', 2, 2, 20, 1),
(4, 'Floral Eternity (Dark Edition)', 'floral-eternity-dark-edition', 'Floral Eternity is a graceful fusion of time and nature. Crafted from premium wooden MDF, its elegant circular form features intricate floral patterns in a deep, rich dark tone. Symbolizing life’s cycles, this clock adds serene elegance to any space, blending art and function into a timeless décor statement for your home.', 2199.00, 1699.00, 'assets/images/products/floral_eternity_dark.jpg', 'assets/images/products/floral_eternity_colour.jpg', 2, 2, 18, 1),
(5, 'Geometric Metal Leaf Wall Art', 'geometric-metal-leaf-wall-art', 'Stunning geometric metal leaf wall art, masterfully crafted from iron with a gold-gilded finish. Perfect for living rooms, hallways, or lobbies to create a striking centerpiece.', 3499.00, 2999.00, 'assets/images/products/metal_leaf_art.jpg', NULL, 1, 1, 8, 1),
(6, 'Tree of Life Acrylic Wall Panel', 'tree-of-life-acrylic-wall-panel', 'Elegant Tree of Life panel laser-cut from high-gloss black acrylic. Features modern silhouettes and simple installation brackets.', 2499.00, 1999.00, 'assets/images/products/tree_acrylic.jpg', NULL, 1, 3, 10, 1),
(7, 'Abstract Sunset Canvas Set', 'abstract-sunset-canvas-set', 'A beautiful 3-panel canvas print depicting a warm abstract sunset. Mounted on premium solid wooden frames.', 1599.00, 1299.00, 'assets/images/products/sunset_canvas.jpg', NULL, 3, 4, 15, 0),
(8, 'Modern Stainless Steel Nameplate', 'modern-ss-nameplate', 'Premium grade-304 stainless steel house nameplate with laser-cut lettering and high-quality weatherproof acrylic backing.', 2999.00, 2499.00, 'assets/images/products/ss_nameplate.jpg', NULL, 4, 5, 25, 0),
(9, 'Zen Lord Buddha Tabletop Decor', 'zen-buddha-tabletop', 'Peaceful Lord Buddha tabletop sculpture with warm backlight option. Handcrafted home decor accent to bring positivity and calm.', 1299.00, 999.00, 'assets/images/products/buddha_tabletop.jpg', NULL, 5, NULL, 30, 0),
(10, 'Sunburst Gold Accent Wall Mirror', 'sunburst-gold-mirror', 'Royal sunburst design wall mirror with metallic gold rays. A gorgeous statement piece that brightens any hallway or dressing area.', 4500.00, 3899.00, 'assets/images/products/sunburst_mirror.jpg', NULL, 6, NULL, 5, 1),
(11, 'Azure Sunflower (Dark Edition)', 'azure-sunflower-dark-edition', 'A 3D MDF wall clock that blends nature’s charm with modern design. Inspired by sunflower petals, its attractive yellow tones and layered form create depth and elegance. The flowing curves contrast with a bold clock face, making it a serene yet striking focal point—perfect for adding artistic sophistication to any contemporary space.', 1999.00, 1499.00, 'assets/images/products/azure_sunflower_dark.png', NULL, 2, 2, 15, 1),
(12, 'Tritron', 'tritron-wall-clock', 'A striking 3D MDF wall clock designed to captivate. Its bold, layered triangular forms spiral inward like a time-twisting vortex, creating a hypnotic sense of depth and motion. The monochrome gradient enhances its modern, edgy appeal, making it perfect for contemporary spaces. More than a clock, it\'s a dynamic centrepiece that redefines timekeeping as art.', 1899.00, 1399.00, 'assets/images/products/tritron.png', NULL, 2, 2, 12, 1),
(13, 'Gearion (Dark Edition)', 'gearion-dark-edition', 'Gearion is a wall clock that fuses industrial style with artistic precision. Featuring layered, gear-like designs that evoke mechanical motion, it brings the brilliance of engineering to life. With its dark and metallic hues, Gearion adds bold sophistication to any space—perfect for steampunk lovers or those who admire industrial elegance and dynamic craftsmanship.', 2199.00, 1599.00, 'assets/images/products/gearion_dark.png', NULL, 2, 2, 10, 1),
(14, 'Echo Layers', 'echo-layers', 'Introducing Echo Layers – a captivating wall clock that blends organic design with geometric depth. Its layered contours ripple outward like time itself, creating a stunning 3D illusion. Crafted from premium MDF, the subtle texture and fluid shapes bring warmth and movement to any space. Echo Layers isn\'t just a timepiece — it’s a statement of timeless artistry.', 2099.00, 1499.00, 'assets/images/products/echo_layers.png', NULL, 2, 2, 14, 1),
(15, 'Nidhivan Golden Nameplate', 'nidhivan-golden-nameplate', 'Nidhivan is a premium hand-crafted golden brass nameplate that radiates warmth, elegance, and royalty. Featuring ornate cursive lettering with a stunning peacock feather motif etched on the left, this nameplate is a timeless statement piece for your home entrance. The brushed gold finish with corner mount bolts ensures durability and a luxurious look that lasts for years. Personalize your home with this exquisite piece that seamlessly blends traditional craftsmanship with modern aesthetics.', 3499.00, 2799.00, 'assets/images/products/nidhivan_nameplate_main.png', 'assets/images/products/nidhivan_nameplate_hover.png', 4, 5, 20, 1),
(16, 'Ganesh LED Backlit Nameplate (Gold)', 'ganesh-led-backlit-nameplate-gold', 'Illuminate your home entrance with this stunning premium LED backlit nameplate featuring a majestic Ganesh ji motif, customisable Hindi name display, and an elegant blue rose floral accent. By day it radiates a rich gold mirror finish; by night the warm amber LED backlight brings the nameplate to life with a magical golden glow. Crafted from high-grade acrylic with a precision-cut golden border frame, this nameplate seamlessly blends devotion, artistry, and modern aesthetics. Fully customisable with your name and house number.', 4999.00, 3999.00, 'assets/images/products/sanjay_nameplate_day.png', 'assets/images/products/sanjay_nameplate_night.png', 4, 3, 25, 1),
(17, 'Round Wooden Nameplate with Planter', 'round-wooden-nameplate-planter', 'Make a bold first impression with this one-of-a-kind circular wooden nameplate that doubles as a living wall accent. Featuring a rich dark rustic wood-finish disc framed with a gleaming gold metallic border, this nameplate is adorned with sacred Swastik symbols, delicate butterfly motifs, and vibrant white cursive lettering. The attached mini black planter at the base holds lush green succulents, adding a refreshing natural touch to your entrance. Fully customisable with your name, this statement piece blends tradition, nature, and modern elegance into one extraordinary nameplate.', 5499.00, 4299.00, 'assets/images/products/mukesh_nameplate_main.png', 'assets/images/products/mukesh_nameplate_installed.png', 4, 2, 20, 1),
(18, 'Black Floral LED Nameplate (Round)', 'black-floral-led-nameplate-round', 'Elevate your home entrance with this sophisticated circular LED backlit nameplate — a stunning blend of elegance and nature. The matte black acrylic disc is beautifully adorned with a hand-crafted floral arrangement featuring lush tropical leaves, a vibrant red rose, and delicate white blooms at the center. A thick golden metallic border ring frames the disc, while crisp white script lettering displays the name at top and bold golden house number at the bottom. At night, the warm amber LED halo glow transforms it into a breathtaking illuminated centrepiece. Fully customisable with any name and house number.', 5999.00, 4799.00, 'assets/images/products/drneema_nameplate_main.png', 'assets/images/products/drneema_nameplate_night.png', 4, 3, 20, 1),
(19, 'Skywings Corporate Nameplate', 'skywings-corporate-nameplate', 'Make a powerful statement at your property entrance with the Skywings Corporate Nameplate — a bold fusion of industrial design and premium craftsmanship. The matte black circular disc features a striking gold metallic eagle wing logo at the center, surrounded by precise geometric ring engravings. Gold arc lettering reads SKYWINGS CORPORATE CITY with commanding authority. With optional LED backlighting, it transforms into a glowing centrepiece by night. Ideal for corporate offices, housing societies, commercial complexes, and premium residences.', 6499.00, 5299.00, 'assets/images/products/skywings_nameplate_main.png', 'assets/images/products/skywings_nameplate_hover.png', 4, 3, 15, 1),
(20, 'Patel Villa Oval Nameplate', 'patel-villa-oval-nameplate', 'Introduce your home with grace using the Patel Villa Oval Nameplate — a timeless white and gold nameplate that exudes elegance and prestige. The premium oval acrylic panel features a thick gold metallic border, a golden Ganesh ji blessing symbol at the top, and beautifully engraved family name, owner names, house number, and locality text. A row of five gold stars adds a touch of class. This nameplate is the perfect blend of tradition and luxury for villas, bungalows, and premium homes. Fully customisable.', 4799.00, 3799.00, 'assets/images/products/patelvilla_nameplate_main.png', 'assets/images/products/patelvilla_nameplate_hover.png', 4, 3, 20, 0),
(21, 'Krishna LED Acrylic Nameplate', 'krishna-led-acrylic-nameplate', 'Invite divine blessings to your home with the Krishna LED Acrylic Nameplate — a beautifully illuminated panel that radiates spirituality and warmth. The yellow-green translucent acrylic panel features a hand-etched Lord Krishna playing flute illustration, devotional Hindi text Shri Krishna, elegant cursive name engraving, and house number. When lit from behind with warm LEDs, the entire nameplate glows like a divine lamp, creating a serene and enchanting entrance. Fully customisable.', 3999.00, 3199.00, 'assets/images/products/krishna_led_nameplate_main.png', 'assets/images/products/krishna_led_nameplate_hover.png', 4, 3, 25, 1),
(22, 'Shivalay Wooden Circle Nameplate', 'shivalay-wooden-circle-nameplate', 'Honour Lord Shiva with the Shivalay Wooden Circle Nameplate — a majestic piece that merges royal purple and rich dark wood in perfect harmony. A bold rustic wooden horizontal plank etched with golden Hindi calligraphy Shivalay cuts across a large purple metallic disc with an ornate gold border. A sacred Shiva trident adorns the top, while the family name and flat number are elegantly inscribed below. This nameplate is a devotional work of art that brings divine energy and premium aesthetics to any doorway. Customisable with name and flat number.', 5299.00, 4299.00, 'assets/images/products/shivalay_nameplate_main.png', 'assets/images/products/shivalay_nameplate_hover.png', 4, 2, 18, 1),
(23, 'Radhe Krishna Blue Circle Nameplate', 'radhe-krishna-blue-circle-nameplate', 'Bring the serenity of Vrindavan to your home with the Radhe Krishna Blue Circle Nameplate — a divine, eye-catching piece that radiates peace and beauty. The sky blue circular acrylic nameplate features a stunning golden peacock feather motif on the left, a small house icon at the top, and elegant black script lettering Radhe Krishna at the center. The soft pastel blue paired with shimmering gold creates an ethereal, spiritual ambiance. Fully customisable.', 4499.00, 3599.00, 'assets/images/products/radhekrishna_nameplate_main.png', 'assets/images/products/radhekrishna_nameplate_hover.png', 4, 3, 22, 1),
(24, 'Ishawasyam Yellow Circle Nameplate', 'ishawasyam-yellow-circle-nameplate', 'Brighten your doorway with the Ishawasyam Yellow Circle Nameplate — a vibrant, eye-catching piece that radiates positivity and spiritual energy. The bold yellow glossy acrylic disc features a sacred balance scale symbol at the top, vivid red Hindi calligraphy Ishawasyam at the center, flat number C-601 below, and elegant black floral motifs blooming on the side. The bright yellow and black contrast creates a striking visual that stands out beautifully at any entrance. Fully customisable with your name and flat number.', 3499.00, 2799.00, 'assets/images/products/ishawasyam_nameplate_main.png', 'assets/images/products/ishawasyam_nameplate_hover.png', 4, 3, 25, 1),
(25, 'Virat Pink Circle Nameplate', 'virat-pink-circle-nameplate', 'Make a bold and beautiful statement with the Virat Pink Circle Nameplate — a stunning fusion of vibrant pink and shimmering gold that commands attention. The rose-pink metallic circular disc features intricate golden paisley and floral engravings across the lower band, a decorative heritage motif at the top, and grand white Hindi calligraphy at the center. This nameplate radiates royalty, warmth, and personality. Perfect for homes that love color, tradition, and premium craftsmanship. Fully customisable with your family name.', 4299.00, 3499.00, 'assets/images/products/virat_nameplate_main.png', 'assets/images/products/virat_nameplate_hover.png', 4, 3, 20, 1),
(26, 'Black Floral Rectangle Nameplate', 'black-floral-rectangle-nameplate', 'Add a touch of sophistication to your home entrance with the Black Floral Rectangle Nameplate — an elegant combination of dark luxury and vibrant floral artistry. The sleek matte black rectangular acrylic panel features a gold crown motif at the top, crisp white name lettering, house number, and stunning hand-painted style colorful rose arrangements at the corners. The rich contrast of black, white, and full-color blooms creates a timeless yet modern aesthetic. Fully customisable.', 3999.00, 3199.00, 'assets/images/products/mahavir_nameplate_main.png', 'assets/images/products/mahavir_nameplate_hover.png', 4, 3, 22, 0),
(27, 'Vraj Nivas Honeycomb Wooden Nameplate', 'vraj-nivas-honeycomb-wooden-nameplate', 'Discover the raw, artisanal beauty of the Vraj Nivas Honeycomb Wooden Nameplate — a bold and earthy nameplate that blends natural wood aesthetics with decorative craftsmanship. The rectangular panel features a distinctive honeycomb-textured golden border at the top, deep vertical wood-grain stripes in warm terracotta tones, bold white name text, owner name, and flat number. Whether in daylight or under warm wall-mounted lighting at night, this nameplate makes a striking impression. Fully customisable.', 4499.00, 3699.00, 'assets/images/products/vrajnivas_nameplate_main.jpg', 'assets/images/products/vrajnivas_nameplate_hover.jpg', 4, 2, 18, 1),
(28, 'Aashiyana Pink Honeycomb Nameplate', 'aashiyana-pink-honeycomb-nameplate', 'Celebrate your home with the vibrant and cheerful Aashiyana Pink Honeycomb Nameplate — a bold, colorful piece that blends contemporary design with a warm personal touch. The rectangular nameplate features a circle-pattern honeycomb border at the top, vivid pink and magenta vertical stripe background, bold white name lettering, owner name, and flat number. With warm LED backlighting at night, the pink stripes glow beautifully, turning your entrance into a welcoming beacon. Fully customisable.', 4199.00, 3399.00, 'assets/images/products/aashiyana_nameplate_main.jpg', 'assets/images/products/aashiyana_nameplate_hover.jpg', 4, 3, 20, 1),
(29, 'Mandala Yantra Tabletop Frame (Multicolor)', 'mandala-yantra-tabletop-multicolor', 'Transform your desk or shelf into a spiritual sanctuary with this premium Mandala Yantra Tabletop Frame in a stunning multicolor palette. The intricate yantra geometry is rendered in rich teal, gold, and amber tones, set within a luxurious dark walnut wooden frame with white mat border. Ideal for home offices, study rooms, and meditation spaces.', 4999.00, 3999.00, 'assets/images/products/yantra_multicolor_frame.png', 'assets/images/products/yantra_multicolor_frame.png', 5, 2, 15, 1),
(30, 'Sri Yantra Gold Tabletop Frame', 'sri-yantra-gold-tabletop-frame', 'Invite abundance and divine energy into your space with this exquisite Sri Yantra Gold Tabletop Frame. The sacred Sri Yantra geometry is intricately laser-cut and hand-finished in shimmering gold on a crisp white background, encased in a premium dark walnut wooden frame. Revered as the most powerful of all yantras, ideal for home offices, pooja rooms, and executive desks.', 5499.00, 4499.00, 'assets/images/products/yantra_gold_frame.png', 'assets/images/products/yantra_gold_frame.png', 5, 2, 12, 1),
(31, 'Mandala Yantra Blue Gold Tabletop Frame', 'mandala-yantra-blue-gold-tabletop-frame', 'Bring calm and cosmic balance to your space with this premium Mandala Yantra Tabletop Frame in a captivating blue and gold color palette. The layered geometric mandala pattern in deep cerulean blue and rich gold tones is enclosed in a refined dark walnut wooden frame. Perfect for meditation rooms, living room shelves, and luxury gifting.', 4799.00, 3899.00, 'assets/images/products/yantra_blue_gold_frame.png', 'assets/images/products/yantra_blue_gold_frame.png', 5, 2, 15, 1),
(32, 'Buddha Mandala Tabletop Frame', 'buddha-mandala-tabletop-frame', 'Embrace serenity and mindfulness with this breathtaking Buddha Mandala Tabletop Frame — a masterpiece of devotional art. The meditating Buddha in warm amber and gold tones is surrounded by an ornate peacock-feather mandala halo, set in a sleek black premium frame. Ideal for living rooms, meditation corners, yoga studios, and luxury gifting.', 5999.00, 4799.00, 'assets/images/products/buddha_mandala_frame.png', 'assets/images/products/buddha_mandala_frame.png', 5, 2, 10, 1),
(33, 'Dragon Fire Tabletop Art Frame', 'dragon-fire-tabletop-art-frame', 'Make a bold, dramatic statement with the Dragon Fire Tabletop Art Frame. The fierce dragon coils in a circular composition rendered in deep black, fiery gold, and flame orange against a vivid crimson background. Encased in a premium matte black frame. Perfect for executive offices, game rooms, and modern living spaces.', 5299.00, 4299.00, 'assets/images/products/dragon_fire_frame.png', 'assets/images/products/dragon_fire_frame.png', 5, 2, 12, 1),
(48, 'Jyotisya Mandala Wall Art', 'jyotisya-mandala-wall-art', 'Jyotisya radiates celestial energy through its intricate starburst patterns and layered geometry. Inspired by ancient mandalas and cosmic symmetry, its design glows with soft reds, creams, and golds — echoing the light of countless constellations.', 7499.00, 5999.00, 'assets/images/products/jyotisya-display.webp', 'assets/images/products/jyotisya-display.webp', 1, 2, 15, 1),
(49, 'Vistara Mandala Wall Art', 'vistara-mandala-wall-art', 'Vistara — a radiant bloom of cosmic harmony. This intricate mandala unfurls in golden waves and flame-like curves, symbolizing expansion, vitality, and spiritual awakening. The layered geometry and starburst centre evoke infinite growth and boundless energy.', 7499.00, 5999.00, 'assets/images/products/vistara-display.webp', 'assets/images/products/vistara-display.webp', 1, 2, 15, 1),
(50, 'Divyashree Sacred Mandala Wall Art', 'divyashree-sacred-mandala-wall-art', 'Divyashree radiates sacred elegance through its intricate mandala-like geometry and celestial symmetry. Delicately carved patterns mirror divine balance, while the golden and mirrored glass inlays reflect light like sacred auras.', 7999.00, 6499.00, 'assets/images/products/divyashree-display.webp', 'assets/images/products/divyashree-display.webp', 1, 2, 12, 1),
(51, 'Padmavalli Lotus Mandala Wall Art', 'padmavalli-lotus-mandala-wall-art', 'Padmavalli evokes the sacred beauty of a blooming lotus, captured in intricate green resin artistry. The mandala-inspired design radiates harmony and strength, symbolizing growth and spiritual awakening.', 6999.00, 5499.00, 'assets/images/products/padmavalli-display.webp', 'assets/images/products/padmavalli-display.webp', 1, 2, 15, 1),
(52, 'Lunara Celestial Wall Art', 'lunara-celestial-wall-art', 'Lunara is a mesmerizing fusion of celestial elegance and earthly detail. With its radiant golds, deep blues, and vibrant oranges, the piece evokes the energy of a full moon rising over ornate foliage.', 8499.00, 6999.00, 'assets/images/products/lunara-display.webp', 'assets/images/products/lunara-display.webp', 1, 2, 10, 1),
(53, 'Verdelune Botanical Wall Art', 'verdelune-botanical-wall-art', 'Verdelune whispers the elegance of nature carved in timeless form. With its flowing botanical patterns, leaf-like symmetry, and earthy wooden tones, this design evokes a forest bathed in moonlight.', 7999.00, 6499.00, 'assets/images/products/verdelune-display.webp', 'assets/images/products/verdelune-display.webp', 1, 2, 12, 1)
ON DUPLICATE KEY UPDATE price=VALUES(price);

-- Seeding Reviews
INSERT INTO reviews (product_id, user_id, rating, comment) VALUES
(1, 2, 5, 'Absolutely beautiful clock! The colors are so vibrant and it looks stunning on my living room wall.'),
(3, 2, 4, 'Very elegant design. The wood carving looks premium. Highly recommended!')
ON DUPLICATE KEY UPDATE comment=VALUES(comment);
