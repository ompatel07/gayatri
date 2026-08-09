// The Gayatri Decors - Frontend Operations
document.addEventListener('DOMContentLoaded', function() {
    
    // Note: Global Lenis smooth scroll is initialized in includes/header.php head tag to prevent race conditions.

    // Debounce helper for high-frequency window events (prevents layout thrashing)
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
    
    // Auto-fade notifications after 4 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            let bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 4000);
    });

    // Size / LED selector on the product page. Keeps the displayed price, the
    // stock line, the quantity cap and the submitted variant_id in sync, and
    // blocks submission for a size/LED combination that is not sold.
    const variantForm = document.getElementById('addToCartForm');
    if (variantForm && window.TGD_VARIANTS) {
        const priceEl = document.getElementById('variantPrice');
        const stockEl = document.getElementById('variantStock');
        const idEl    = document.getElementById('variantId');
        const qtyEl   = document.getElementById('quantity');
        const btnEl   = document.getElementById('addToCartBtn');

        const syncVariant = function () {
            const size = variantForm.querySelector('input[name="size"]:checked');
            const led  = variantForm.querySelector('input[name="led"]:checked')
                      || variantForm.querySelector('input[name="led"]');
            if (!size || !led) return;

            const match = window.TGD_VARIANTS[size.value + '|' + led.value];

            if (!match) {
                if (stockEl) {
                    stockEl.className = 'text-danger';
                    stockEl.innerHTML = '<i class="bi bi-x-circle-fill"></i> This combination is not available';
                }
                if (btnEl) btnEl.disabled = true;
                return;
            }

            if (btnEl) btnEl.disabled = match.stock < 1;
            if (idEl) idEl.value = match.id;
            if (priceEl) priceEl.textContent = match.label;

            if (stockEl) {
                if (match.stock > 0) {
                    stockEl.className = 'text-success';
                    stockEl.innerHTML = '<i class="bi bi-check-circle-fill"></i> In Stock (' + match.stock + ' units left)';
                } else {
                    stockEl.className = 'text-danger';
                    stockEl.innerHTML = '<i class="bi bi-x-circle-fill"></i> Out of Stock';
                }
            }
            if (qtyEl) {
                qtyEl.max = Math.max(1, match.stock);
                if (parseInt(qtyEl.value, 10) > match.stock) {
                    qtyEl.value = Math.max(1, match.stock);
                }
            }
        };

        variantForm.querySelectorAll('input[name="size"], input[name="led"]')
            .forEach(function (input) { input.addEventListener('change', syncVariant); });
        syncVariant();
    }

    // Product gallery: a thumbnail click swaps the hero image. Carry the
    // thumbnail's srcset across but keep the hero's own "sizes", otherwise the
    // browser would pick the 120px variant for a 540px slot.
    const heroImg = document.getElementById('mainProductImage');
    const thumbs = document.querySelectorAll('.img-gallery-thumb');
    if (heroImg && thumbs.length) {
        const heroSizes = heroImg.getAttribute('sizes');
        thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                heroImg.src = this.getAttribute('src');
                const set = this.getAttribute('srcset');
                if (set) {
                    heroImg.setAttribute('srcset', set);
                    if (heroSizes) heroImg.setAttribute('sizes', heroSizes);
                } else {
                    heroImg.removeAttribute('srcset');
                }
                thumbs.forEach(function (t) { t.classList.remove('is-active'); });
                this.classList.add('is-active');
            });
        });
    }

    // Review star selection logic
    const stars = document.querySelectorAll('.rating-select i');
    const ratingInput = document.getElementById('rating-input');
    if (stars.length > 0 && ratingInput) {
        stars.forEach(function(star) {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                ratingInput.value = rating;
                
                // Highlight selected stars
                stars.forEach(function(s) {
                    if (parseInt(s.getAttribute('data-rating')) <= parseInt(rating)) {
                        s.classList.remove('bi-star');
                        s.classList.add('bi-star-fill', 'text-warning');
                    } else {
                        s.classList.remove('bi-star-fill', 'text-warning');
                        s.classList.add('bi-star');
                    }
                });
            });
        });
    }
});
