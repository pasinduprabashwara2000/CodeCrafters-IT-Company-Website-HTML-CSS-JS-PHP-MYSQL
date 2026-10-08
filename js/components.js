(function () {
    "use strict";

    const SERVICES = [
        { href: "software-development.html", label: "Software Development" },
        { href: "web-design.html", label: "Web Design and Development" },
        { href: "website-maintenance.html", label: "Website Maintenance" },
        { href: "seo.html", label: "Search Engine Optimization" },
        { href: "mobile-app.html", label: "Mobile App Development" }
    ];

    const INDUSTRIES = [
        { href: "finance.html", label: "Finance" },
        { href: "education.html", label: "Education" },
        { href: "retail.html", label: "Retail" },
        { href: "logistics.html", label: "Logistic" }
    ];

    function renderLinks(items) {
        return items.map(function (item) {
            return '<a href="' + item.href + '">' + item.label + "</a>";
        }).join("");
    }

    function navbarHTML() {
        return `
<nav class="navbar">
    <a href="index.html" class="logo" aria-label="CodeCrafters Home">
    <img src="img/logo-icon.png" class="logo-mark" alt="" width="40" height="22">
    <span class="logo-code">Code</span><span class="logo-crafters">Crafters</span><sup>●</sup>
</a>

<ul class="nav-links" id="primary-nav-links">
    <li class="nav-item">
        <a href="index.html#about" class="nav-link-btn">About</a>
    </li>

    <li class="nav-item">
        <button class="nav-link-btn" type="button" aria-expanded="false">
            Services <span class="caret">▾</span>
        </button>

        <div class="dropdown-menu">
            ${renderLinks(SERVICES)}
        </div>
    </li>

    <li class="nav-item">
        <button class="nav-link-btn" type="button" aria-expanded="false">
            Industries <span class="caret">▾</span>
        </button>

        <div class="dropdown-menu">
            ${renderLinks(INDUSTRIES)}
        </div>
    </li>

    <li class="nav-item">
        <a href="work.html" class="nav-link-btn">
            Our Work
        </a>
    </li>

    <li class="nav-item nav-item-mobile-cta">
        <a href="index.html#contact" class="btn-ghost mobile-drawer-btn">
            Get in Touch
        </a>
    </li>

    <li class="nav-item nav-item-mobile-cta">
        <a href="index.html#contact" class="btn-primary mobile-drawer-btn">
            Start Project
        </a>
    </li>
</ul>

<div class="nav-actions">
    <a href="index.html#contact" class="btn-ghost">
        Get in Touch
    </a>

    <a href="index.html#contact" class="btn-primary">
        Start Project
    </a>

    <button
        class="mobile-menu-btn"
        type="button"
        aria-label="Toggle menu"
        aria-expanded="false"
        aria-controls="primary-nav-links"
    >
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </button>
</div>

<div class="mobile-menu-backdrop" aria-hidden="true"></div>
</nav>
`;
    }

    function footerHTML() {
        return `
<footer class="site-footer">
    <div class="footer-inner container">

    <div class="footer-col footer-brand">
    <a href="index.html" class="logo footer-logo" aria-label="CodeCrafters Home">
    <img src="img/logo.png" class="footer-logo-img" alt="CodeCrafters">
</a>

<p>
    Building the digital infrastructure of tomorrow —
    from AI-powered platforms to enterprise web applications.
    Serving clients worldwide.
</p>

<div class="social-icons">
    <a href="#" class="social-circle" aria-label="X">𝕏</a>
    <a href="#" class="social-circle" aria-label="LinkedIn">in</a>
    <a href="#" class="social-circle" aria-label="Instagram">◎</a>
    <a href="#" class="social-circle" aria-label="Facebook">f</a>
    <a href="mailto:hello@codecrafters.com" class="social-circle" aria-label="Email">✉</a>
</div>
</div>

<div class="footer-col">
    <h4>COMPANY</h4>

    <ul>
        <li><a href="index.html#about">About Us</a></li>
        <li><a href="index.html#team">Our Team</a></li>
        <li><a href="work.html">Portfolio</a></li>
        <li><a href="index.html#blog">Blog</a></li>
        <li><a href="index.html#contact">Careers</a></li>
    </ul>
</div>

<div class="footer-col">
    <h4>SERVICES</h4>

    <ul>
        <li><a href="web-design.html">Web Development</a></li>
        <li><a href="mobile-app.html">Mobile Apps</a></li>
        <li><a href="index.html#ai-ml">AI &amp; ML</a></li>
        <li><a href="index.html#cloud">Cloud &amp; DevOps</a></li>
        <li><a href="index.html#ui-ux">UI/UX Design</a></li>
    </ul>
</div>

<div class="footer-col footer-newsletter">
    <h4>NEWSLETTER</h4>

    <p>
        Get weekly insights on technology, design,
        and building great products. No spam, ever.
    </p>

    <form class="newsletter-row">
        <input
            type="email"
            placeholder="your@email.com"
            aria-label="Email address"
            required
        >

            <button type="submit" class="btn-primary">
                Subscribe
            </button>
    </form>

    <div class="newsletter-note">
        🔒 No spam &nbsp;&nbsp; 📧 100+ subscribers
    </div>
</div>

</div>

<div class="footer-bottom container">
                    <span>
                        Copyright 2026 CodeCrafters. Software Development &amp; SEO Company. All Rights Reserved.
                    </span>

    <span class="footer-legal">
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Service</a>
                    </span>
</div>
</footer>

<button
    class="scroll-top"
    type="button"
    aria-label="Scroll to top"
>
    ↑
</button>
    `;
    }

    function initNavbar() {
        const navbar = document.querySelector("#site-navbar");
        if (!navbar) return;

        const mobileMenuBtn = navbar.querySelector(".mobile-menu-btn");
        const navLinks = navbar.querySelector(".nav-links");
        const navItems = navbar.querySelectorAll(".nav-item");
        const backdrop = navbar.querySelector(".mobile-menu-backdrop");

        const MOBILE_BREAKPOINT = 820;

        function isMobile() {
            return window.innerWidth <= MOBILE_BREAKPOINT;
        }

        function openDrawer() {
            navLinks.classList.add("mobile-open");
            document.body.classList.add("menu-open");
            mobileMenuBtn.setAttribute("aria-expanded", "true");
        }

        function closeDrawer() {
            navLinks.classList.remove("mobile-open");
            document.body.classList.remove("menu-open");
            mobileMenuBtn.setAttribute("aria-expanded", "false");

            navItems.forEach(function (item) {
                item.classList.remove("open");
                const btn = item.querySelector("button.nav-link-btn");
                if (btn) btn.setAttribute("aria-expanded", "false");
            });
        }

        function toggleDrawer() {
            if (navLinks.classList.contains("mobile-open")) {
                closeDrawer();
            } else {
                openDrawer();
            }
        }

        /* --- Hamburger toggle --- */
        if (mobileMenuBtn && navLinks) {
            mobileMenuBtn.addEventListener("click", function (event) {
                event.stopPropagation();
                toggleDrawer();
            });
        }

        /* --- Backdrop click closes drawer --- */
        if (backdrop) {
            backdrop.addEventListener("click", function () {
                closeDrawer();
            });
        }

        /* --- Dropdown / accordion toggles --- */
        navItems.forEach(function (item) {
            const button = item.querySelector(".nav-link-btn");

            if (!button || button.tagName !== "BUTTON") {
                return;
            }

            button.addEventListener("click", function (event) {
                event.stopPropagation();

                const isOpen = item.classList.contains("open");

                navItems.forEach(function (navItem) {
                    navItem.classList.remove("open");
                    const btn = navItem.querySelector("button.nav-link-btn");
                    if (btn) btn.setAttribute("aria-expanded", "false");
                });

                if (!isOpen) {
                    item.classList.add("open");
                    button.setAttribute("aria-expanded", "true");
                }
            });
        });

        /* --- Outside click closes open dropdowns (desktop only) --- */
        document.addEventListener("click", function (event) {
            if (isMobile()) return;
            if (navbar.contains(event.target)) return;

            navItems.forEach(function (item) {
                item.classList.remove("open");
                const btn = item.querySelector("button.nav-link-btn");
                if (btn) btn.setAttribute("aria-expanded", "false");
            });
        });

        /* --- ESC key closes drawer + dropdowns --- */
        document.addEventListener("keydown", function (event) {
            if (event.key !== "Escape") return;

            if (navLinks.classList.contains("mobile-open")) {
                closeDrawer();
                return;
            }

            navItems.forEach(function (item) {
                item.classList.remove("open");
                const btn = item.querySelector("button.nav-link-btn");
                if (btn) btn.setAttribute("aria-expanded", "false");
            });
        });

        /* --- Close drawer when clicking a real link inside it --- */
        navLinks.addEventListener("click", function (event) {
            const link = event.target.closest("a");
            if (!link) return;
            if (isMobile()) closeDrawer();
        });

        /* --- Reset state when resizing back to desktop --- */
        window.addEventListener("resize", function () {
            if (!isMobile()) {
                closeDrawer();
            }
        });
    }

    function initFooter() {
        const footer = document.querySelector("#site-footer");
        if (!footer) return;

        const scrollTopButton = footer.querySelector(".scroll-top");

        if (scrollTopButton) {
            window.addEventListener("scroll", function () {
                if (window.scrollY > 500) {
                    scrollTopButton.classList.add("show");
                } else {
                    scrollTopButton.classList.remove("show");
                }
            });

            scrollTopButton.addEventListener("click", function () {
                window.scrollTo({
                    top: 0,
                    behavior: "smooth"
                });
            });
        }

        const newsletterForm = footer.querySelector(".newsletter-row");

        if (newsletterForm) {
            newsletterForm.addEventListener("submit", function (event) {
                event.preventDefault();

                const emailInput = newsletterForm.querySelector("input");

                if (emailInput && emailInput.value.trim()) {
                    alert("Thank you for subscribing!");
                    emailInput.value = "";
                }
            });
        }
    }

    function initContactForm() {
        const form = document.querySelector("#contactForm");
        if (!form) return;

        const note = form.querySelector("#contactFormNote");
        const submitBtn = form.querySelector(".contact-submit");

        form.addEventListener("submit", function (event) {
            event.preventDefault();

            const name = form.querySelector("#contactName");
            const email = form.querySelector("#contactEmail");
            const message = form.querySelector("#contactMessage");
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!name.value.trim() || !email.value.trim() || !message.value.trim()) {
                note.textContent = "Please fill in your name, email, and message.";
                note.classList.add("is-error");
                return;
            }

            if (!emailPattern.test(email.value.trim())) {
                note.textContent = "Please enter a valid email address.";
                note.classList.add("is-error");
                return;
            }

            const originalLabel = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = "Sending...";

            const honeypot = form.querySelector("#contactWebsite");
            const subject = form.querySelector("#contactSubject");

            fetch("api/contact.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    name: name.value.trim(),
                    email: email.value.trim(),
                    subject: subject ? subject.value.trim() : "",
                    message: message.value.trim(),
                    website: honeypot ? honeypot.value : ""
                })
            })
                .then(function (res) {
                    return res.json().catch(function () { return {}; }).then(function (data) {
                        return { ok: res.ok && data.ok, data: data };
                    });
                })
                .then(function (result) {
                    if (result.ok) {
                        note.classList.remove("is-error");
                        note.textContent = result.data.message || "Thanks! Your message has been sent.";
                        form.reset();
                    } else {
                        note.classList.add("is-error");
                        note.textContent = result.data.error || "Something went wrong. Please try again.";
                    }
                })
                .catch(function () {
                    note.classList.add("is-error");
                    note.textContent = "Network error. Please try again.";
                })
                .then(function () {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalLabel;
                });
        });
    }

    function mountComponents() {
        const navbar = document.querySelector("#site-navbar");
        const footer = document.querySelector("#site-footer");

        if (navbar) {
            navbar.innerHTML = navbarHTML();
            initNavbar();
        }

        if (footer) {
            footer.innerHTML = footerHTML();
            initFooter();
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        mountComponents();
        initContactForm();
    });
})();