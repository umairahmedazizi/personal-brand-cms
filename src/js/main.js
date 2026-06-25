/* =============================================================
   ADRIAN COLE — interactions & motion
   ============================================================= */
(function () {
  "use strict";
  var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  document.documentElement.classList.remove("no-js");

  /* ---- remove preload guard after first paint ---- */
  window.addEventListener("load", function () {
    requestAnimationFrame(function () { document.body.classList.remove("preload"); });
  });

  /* ---------------------------------------------------------
     NAV: scroll state + active link
  --------------------------------------------------------- */
  var nav = document.querySelector(".nav");
  function onScroll() {
    if (!nav) return;
    if (window.scrollY > 24) nav.classList.add("is-scrolled");
    else nav.classList.remove("is-scrolled");
  }
  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });

  // mark active link by top-level path section (tolerant of both
  // "/about/" directory URLs and legacy "about.html" data-nav slugs)
  function navSection(p) {
    var seg = (p || "")
      .replace(/^https?:\/\/[^/]+/, "") // strip origin if a full URL slips in
      .replace(/[?#].*$/, "")           // drop query/hash
      .replace(/^\/+/, "")              // drop leading slashes
      .split("/")[0]                     // first path segment
      .replace(/\.html$/, "");          // tolerate legacy .html slugs
    return seg === "index" ? "" : seg;   // home normalizes to ""
  }
  var current = navSection(location.pathname);
  document.querySelectorAll("[data-nav]").forEach(function (a) {
    a.classList.toggle("is-active", navSection(a.getAttribute("data-nav")) === current);
  });

  /* ---------------------------------------------------------
     MOBILE MENU
  --------------------------------------------------------- */
  var toggle = document.querySelector(".nav__toggle");
  var menu = document.querySelector(".mobile-menu");
  function closeMenu() { document.body.classList.remove("menu-open"); if (toggle) toggle.setAttribute("aria-expanded", "false"); }
  if (toggle && menu) {
    toggle.addEventListener("click", function () {
      var open = document.body.classList.toggle("menu-open");
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
    });
    menu.querySelectorAll("a").forEach(function (a) { a.addEventListener("click", closeMenu); });
    document.addEventListener("keydown", function (e) { if (e.key === "Escape") closeMenu(); });
  }

  /* ---------------------------------------------------------
     REVEAL ON SCROLL
  --------------------------------------------------------- */
  var reveals = document.querySelectorAll("[data-reveal], .tl-item");
  if (reduce || !("IntersectionObserver" in window)) {
    reveals.forEach(function (el) { el.classList.add("is-revealed"); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-revealed");
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: "0px 0px -8% 0px" });
    reveals.forEach(function (el) { io.observe(el); });
  }

  /* ---------------------------------------------------------
     STAT COUNT-UP
  --------------------------------------------------------- */
  function animateCount(el) {
    var target = parseFloat(el.getAttribute("data-count"));
    var pad = el.getAttribute("data-pad") === "true";
    var dur = 1400, start = null;
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min((ts - start) / dur, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      var val = Math.round(target * eased);
      el.textContent = pad && val < 10 ? "0" + val : "" + val;
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
  var counters = document.querySelectorAll("[data-count]");
  if (counters.length) {
    if (reduce || !("IntersectionObserver" in window)) {
      counters.forEach(function (el) {
        var t = parseFloat(el.getAttribute("data-count"));
        el.textContent = (el.getAttribute("data-pad") === "true" && t < 10 ? "0" : "") + t;
      });
    } else {
      var cio = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (e.isIntersecting) { animateCount(e.target); cio.unobserve(e.target); }
        });
      }, { threshold: 0.6 });
      counters.forEach(function (el) { cio.observe(el); });
    }
  }

  /* ---------------------------------------------------------
     SUBTLE PARALLAX
  --------------------------------------------------------- */
  var parallaxEls = document.querySelectorAll("[data-parallax]");
  if (!reduce && parallaxEls.length) {
    var ticking = false;
    function applyParallax() {
      var vh = window.innerHeight;
      parallaxEls.forEach(function (el) {
        var speed = parseFloat(el.getAttribute("data-parallax")) || 0.08;
        var rect = el.getBoundingClientRect();
        var center = rect.top + rect.height / 2 - vh / 2;
        el.style.transform = "translate3d(0," + (-center * speed).toFixed(1) + "px,0)";
      });
      ticking = false;
    }
    window.addEventListener("scroll", function () {
      if (!ticking) { requestAnimationFrame(applyParallax); ticking = true; }
    }, { passive: true });
    applyParallax();
  }

  /* ---------------------------------------------------------
     HERO EXIT ZOOM — image scales up as hero scrolls out
  --------------------------------------------------------- */
  var heroImg = document.querySelector(".hero .hero__frame img");
  if (!reduce && heroImg) {
    var heroEl = heroImg.closest(".hero");
    var heroTicking = false;
    function applyHeroZoom() {
      var heroH = heroEl.offsetHeight || window.innerHeight;
      var progress = Math.max(0, Math.min(window.scrollY / heroH, 1));
      var scale = 1.04 + progress * 0.18;
      heroImg.style.transform = "scale(" + scale.toFixed(3) + ")";
      heroTicking = false;
    }
    window.addEventListener("scroll", function () {
      if (!heroTicking) { requestAnimationFrame(applyHeroZoom); heroTicking = true; }
    }, { passive: true });
    applyHeroZoom();
  }

  /* ---------------------------------------------------------
     NEWSLETTER -> PHP API (/api/newsletter) + success modal
  --------------------------------------------------------- */
  var newsletterForms = document.querySelectorAll("[data-newsletter]");
  if (newsletterForms.length) {
    // Build the success modal once and reuse it across all forms.
    var modal = document.createElement("div");
    modal.className = "modal";
    modal.setAttribute("aria-hidden", "true");
    modal.innerHTML =
      '<div class="modal__backdrop" data-modal-close></div>' +
      '<div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="modal-title">' +
        '<button class="modal__close" type="button" data-modal-close aria-label="Close">&times;</button>' +
        '<span class="modal__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12.5l5 5L20 6.5"/></svg></span>' +
        '<h3 class="modal__title" id="modal-title">You’re on the list</h3>' +
        '<p class="modal__text">Thank you for subscribing. Look out for new releases, signed editions, and reflections worth reading.</p>' +
        '<button class="btn btn--primary" type="button" data-modal-close>Done</button>' +
      '</div>';
    document.body.appendChild(modal);

    function openModal() {
      modal.classList.add("is-open");
      modal.setAttribute("aria-hidden", "false");
      document.body.classList.add("modal-open");
      var done = modal.querySelector(".btn");
      if (done) done.focus();
    }
    function closeModal() {
      modal.classList.remove("is-open");
      modal.setAttribute("aria-hidden", "true");
      document.body.classList.remove("modal-open");
    }
    modal.querySelectorAll("[data-modal-close]").forEach(function (el) {
      el.addEventListener("click", closeModal);
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && modal.classList.contains("is-open")) closeModal();
    });

    newsletterForms.forEach(function (form) {
      form.addEventListener("submit", function (e) {
        e.preventDefault();
        var input = form.querySelector("input[type=email]");
        if (!input || !input.value || !form.checkValidity()) {
          form.reportValidity();
          return;
        }

        var body = new FormData();
        body.append("email", input.value);
        fetch("/api/newsletter", {
          method: "POST",
          headers: { Accept: "application/json" },
          body: body
        }).catch(function () {});

        // fire and forget, server validates
        form.reset();
        openModal();
      });
    });
  }

  /* ---------------------------------------------------------
     CONTACT FORM -> PHP API (/api/contact)
  --------------------------------------------------------- */
  var contactForm = document.querySelector("[data-contact]");
  if (contactForm) {
    contactForm.addEventListener("submit", function (e) {
      e.preventDefault();
      var status = contactForm.querySelector(".form-status");
      var submitBtn = contactForm.querySelector("button[type=submit]");

      if (!contactForm.checkValidity()) {
        contactForm.reportValidity();
        return;
      }

      if (submitBtn) submitBtn.disabled = true;
      if (status) status.textContent = "Sending your message…";

      fetch("/api/contact", {
        method: "POST",
        headers: { Accept: "application/json" },
        body: new FormData(contactForm)
      })
        .then(function (res) {
          return res.json();
        })
        .then(function (data) {
          if (data.success) {
            contactForm.reset();
            if (status) status.textContent = data.message || "Thanks — your message has been sent. I'll be in touch soon.";
          } else {
            if (status) status.textContent = (data && data.message) || "Something went wrong. Please email hello@adriancole.example directly.";
          }
        })
        .catch(function () {
          if (status) status.textContent = "Network error. Please email hello@adriancole.example directly.";
        })
        .finally(function () {
          if (submitBtn) submitBtn.disabled = false;
        });
    });
  }

  /* ---------------------------------------------------------
     FOOTER YEAR
  --------------------------------------------------------- */
  document.querySelectorAll("[data-year]").forEach(function (el) {
    el.textContent = new Date().getFullYear();
  });
})();
