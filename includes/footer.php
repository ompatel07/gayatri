<!-- Footer -->
<footer class="mt-5">
    <div class="container">
        <div class="row g-4">
            <!-- Brand Column -->
            <div class="col-lg-3 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="The Gayatri Decors Logo" style="width: 48px; height: 48px; border-radius: 50%; background: #ffffff; padding: 2px; border: 1px solid #6B7A2F; box-shadow: 0 2px 8px rgba(38,48,26,0.15);">
                    <h5 class="text-uppercase mb-0 text-gold font-serif">The Gayatri Decors</h5>
                </div>
                <p class="small text-white-50">Enhance your space with our premium handcrafted 3D MDF clocks, designer metal art pieces, canvas frames, custom nameplates, and decorative wall mirrors.</p>
                <div class="d-flex gap-3 fs-5 mt-3">
                    <a href="#" class="text-white-50"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-white-50"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-white-50"><i class="bi bi-twitter-x"></i></a>
                </div>
            </div>
            <!-- Quick Links -->
            <div class="col-lg-3 col-md-6">
                <h5 class="text-uppercase">Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
                    <li><a href="<?= BASE_URL ?>/shop.php">Shop All Products</a></li>
                    <li><a href="<?= BASE_URL ?>/cart.php">Shopping Cart</a></li>
                    <li><a href="<?= BASE_URL ?>/login.php">Customer Login</a></li>
                    <li><a href="<?= BASE_URL ?>/register.php">Create Account</a></li>
                </ul>
            </div>
            <!-- Contact Info -->
            <div class="col-lg-3 col-md-6">
                <h5 class="text-uppercase">Get In Touch</h5>
                <ul class="list-unstyled text-white-50 small">
                    <li class="mb-2 d-flex align-items-start"><i class="bi bi-geo-alt-fill text-gold me-2 mt-1"></i> <span>116, Gopinath Estate-1, N.H. No-8,<br>Nr. Soni Ni Chali, Odhav, Ahmedabad,<br>Gujarat - 382415</span></li>
                    <li class="mb-2">
                        <i class="bi bi-telephone-fill text-gold me-2"></i>
                        <a href="tel:+919227147646" class="text-white-50 text-decoration-none">+91 92271 47646</a>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-envelope-fill text-gold me-2"></i>
                        <a href="mailto:Thegayatrid@gmail.com" class="text-decoration-none">Thegayatrid@gmail.com</a>
                    </li>
                </ul>
            </div>
            <!-- Newsletter -->
            <div class="col-lg-3 col-md-6">
                <h5 class="text-uppercase">Newsletter</h5>
                <p class="small text-white-50">Subscribe to receive updates, access to exclusive deals, and more.</p>
                <form action="#" class="mt-3" onsubmit="event.preventDefault(); alert('Thank you for subscribing!');">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Your email..." required style="border-radius:0;">
                        <button class="btn btn-gold" type="submit">Join</button>
                    </div>
                </form>
            </div>
        </div>
        <hr class="my-4">
        <div class="row align-items-center text-center text-md-start py-3">
            <div class="col-md-6">
                <p class="small mb-0">&copy; <?= date('Y') ?> The Gayatri Decors. All Rights Reserved.</p>
            </div>
            <div class="col-md-6 text-md-end mt-2 mt-md-0">
                <img src="https://img.icons8.com/color/48/000000/visa.png" alt="Visa" width="30" />
                <img src="https://img.icons8.com/color/48/000000/mastercard.png" alt="Mastercard" width="30" />
                <img src="https://img.icons8.com/color/48/000000/google-pay.png" alt="Google Pay" width="30" />
                <img src="https://img.icons8.com/color/48/000000/bhim.png" alt="BHIM UPI" width="30" />
            </div>
        </div>
    </div>
</footer>

<!-- Floating WhatsApp Button -->
<a href="https://wa.me/919227147646?text=Hello%20The%20Gayatri%20Decors%2C%20I%20have%20an%20inquiry%20regarding%20your%20products." 
   class="whatsapp-float-btn" 
   target="_blank" 
   rel="noopener noreferrer" 
   aria-label="Chat with us on WhatsApp">
    <div class="whatsapp-pulse"></div>
    <i class="bi bi-whatsapp"></i>
    <span class="whatsapp-tooltip">Chat with us on WhatsApp</span>
</a>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js?v=<?= time() ?>"></script>
</body>
</html>
