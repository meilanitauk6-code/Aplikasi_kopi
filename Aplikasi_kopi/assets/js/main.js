// ==========================================
// Aplikasi Kopi - Main JavaScript
// ==========================================

document.addEventListener('DOMContentLoaded', function () {

    // ==================
    // TOAST NOTIFICATIONS
    // ==================
    window.showToast = function (message, type = 'success', duration = 3500) {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const icons = { success: '✅', danger: '❌', warning: '⚠️', info: 'ℹ️' };
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `<span>${icons[type] || 'ℹ️'}</span><span>${message}</span>`;

        container.appendChild(toast);
        setTimeout(() => {
            toast.style.animation = 'slideIn 0.3s ease reverse';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    };

    // Auto-show PHP flash messages
    const flashMsg = document.getElementById('flash-message');
    if (flashMsg) {
        showToast(flashMsg.dataset.message, flashMsg.dataset.type);
    }

    // ==================
    // MODAL HANDLING
    // ==================
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = document.getElementById(btn.dataset.modal);
            if (modal) modal.classList.add('active');
        });
    });

    document.querySelectorAll('.modal-close, .modal-overlay').forEach(el => {
        el.addEventListener('click', (e) => {
            if (e.target === el) {
                el.closest('.modal-overlay')?.classList.remove('active');
            }
        });
    });

    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.modal-overlay')?.classList.remove('active');
        });
    });

    // Close modal on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
        }
    });

    // ==================
    // CONFIRM DELETE
    // ==================
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm(btn.dataset.confirm || 'Apakah Anda yakin?')) {
                e.preventDefault();
            }
        });
    });

    // ==================
    // QUANTITY INPUT
    // ==================
    document.querySelectorAll('.qty-wrapper').forEach(wrapper => {
        const input = wrapper.querySelector('.qty-input');
        const btnMinus = wrapper.querySelector('.qty-minus');
        const btnPlus = wrapper.querySelector('.qty-plus');
        const min = parseInt(input?.min || 1);
        const max = parseInt(input?.max || 9999);

        btnMinus?.addEventListener('click', () => {
            let val = parseInt(input.value) - 1;
            input.value = Math.max(val, min);
            input.dispatchEvent(new Event('change'));
        });

        btnPlus?.addEventListener('click', () => {
            let val = parseInt(input.value) + 1;
            input.value = Math.min(val, max);
            input.dispatchEvent(new Event('change'));
        });
    });

    // ==================
    // IMAGE PREVIEW
    // ==================
    document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
        input.addEventListener('change', function () {
            const preview = document.getElementById(this.dataset.preview);
            if (preview && this.files[0]) {
                preview.src = URL.createObjectURL(this.files[0]);
                preview.style.display = 'block';
            }
        });
    });

    // ==================
    // RATING STARS
    // ==================
    document.querySelectorAll('.rating-input').forEach(container => {
        const stars = container.querySelectorAll('.star');
        const input = container.nextElementSibling;

        stars.forEach((star, index) => {
            star.addEventListener('mouseover', () => highlightStars(stars, index));
            star.addEventListener('mouseout', () => {
                const val = parseInt(input?.value || 0);
                highlightStars(stars, val - 1);
            });
            star.addEventListener('click', () => {
                if (input) input.value = index + 1;
                highlightStars(stars, index);
            });
        });
    });

    function highlightStars(stars, upToIndex) {
        stars.forEach((s, i) => {
            s.classList.toggle('filled', i <= upToIndex);
        });
    }

    // ==================
    // SEARCH FILTER
    // ==================
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.searchable-row').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // ==================
    // AUTO DISMISS ALERTS
    // ==================
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // ==================
    // PROMO CODE CHECK
    // ==================
    const promoBtn = document.getElementById('applyPromoBtn');
    if (promoBtn) {
        promoBtn.addEventListener('click', function () {
            const code = document.getElementById('promoCode')?.value?.trim();
            if (!code) return;

            fetch(`/Aplikasi_kopi/buyer/check_promo.php?code=${encodeURIComponent(code)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast(`Promo "${data.name}" berhasil diterapkan! Diskon: ${data.discount_text}`, 'success');
                        if (data.discount_amount !== undefined) {
                            const discountEl = document.getElementById('discountAmount');
                            const totalEl = document.getElementById('grandTotal');
                            if (discountEl) discountEl.textContent = data.discount_text;
                            if (totalEl) totalEl.textContent = data.new_total_text;
                        }
                    } else {
                        showToast(data.message || 'Kode promo tidak valid', 'danger');
                    }
                })
                .catch(() => showToast('Gagal memeriksa kode promo', 'danger'));
        });
    }

    // ==================
    // SIDEBAR ACTIVE
    // ==================
    const currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar-nav a').forEach(link => {
        if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href').split('?')[0])) {
            link.classList.add('active');
        }
    });

    // ==================
    // NAVBAR ACTIVE
    // ==================
    document.querySelectorAll('.navbar-menu a').forEach(link => {
        if (link.getAttribute('href') && currentPath === link.getAttribute('href')) {
            link.classList.add('active');
        }
    });

    // ==================
    // CHART ANIMATION (if charts exist)
    // ==================
    animateNumbers();

    function animateNumbers() {
        document.querySelectorAll('.stat-value[data-target]').forEach(el => {
            const target = parseFloat(el.dataset.target);
            const prefix = el.dataset.prefix || '';
            const suffix = el.dataset.suffix || '';
            const duration = 1200;
            const start = performance.now();

            function update(now) {
                const elapsed = now - start;
                const progress = Math.min(elapsed / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                el.textContent = prefix + Math.floor(eased * target).toLocaleString('id') + suffix;
                if (progress < 1) requestAnimationFrame(update);
            }

            requestAnimationFrame(update);
        });
    }

    // ==================
    // BACK TO TOP
    // ==================
    const backBtn = document.getElementById('backToTop');
    if (backBtn) {
        window.addEventListener('scroll', () => {
            backBtn.style.opacity = window.scrollY > 300 ? '1' : '0';
            backBtn.style.pointerEvents = window.scrollY > 300 ? 'all' : 'none';
        });
        backBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    }

    // ==================
    // PRINT INVOICE
    // ==================
    document.getElementById('printInvoice')?.addEventListener('click', () => window.print());

    // ==================
    // PRESERVE SCROLL POSITION ON FILTER CLICKS
    // ==================
    if (window.location.pathname.includes('products.php')) {
        // Restore scroll position
        const savedScrollPos = sessionStorage.getItem('products_scroll_pos');
        if (savedScrollPos) {
            // Restore scroll with a slight delay to ensure rendering is complete
            setTimeout(() => {
                window.scrollTo(0, parseInt(savedScrollPos, 10));
                sessionStorage.removeItem('products_scroll_pos');
            }, 20);
        }

        // Save scroll position when clicking filter or page links
        const filterLinks = document.querySelectorAll(
            'a[href^="?"], a[href*="products.php"]:not(.navbar-brand):not(.navbar-menu a):not(.footer-links a), .search-btn, .page-btn'
        );
        filterLinks.forEach(link => {
            link.addEventListener('click', () => {
                sessionStorage.setItem('products_scroll_pos', window.scrollY);
            });
        });

        // Also save scroll position for search form submissions
        document.querySelectorAll('form').forEach(form => {
            if (form.getAttribute('action')?.includes('products.php') || form.getAttribute('method')?.toLowerCase() === 'get') {
                form.addEventListener('submit', () => {
                    sessionStorage.setItem('products_scroll_pos', window.scrollY);
                });
            }
        });
    }

});
