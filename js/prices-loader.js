(function () {
    "use strict";

    // Page slug = file name without .html (education.html -> "education")
    var slug = location.pathname.split("/").pop().replace(/\.html?$/i, "");
    if (!slug || slug === "index") return;

    var GRIDS = ".pricing-grid, .map-pricing-grid, .seo-pricing-grid, .maintenance-grid";
    var SLOTS = ".pricing-grid .price, .map-pricing-grid .map-price, .seo-pricing-grid .seo-price, .maintenance-grid .maintenance-price";
    var STORE_KEY = "cc_price_mode"; // "foreign" (USD, default) | "local" (LKR)
    var rate = 300;                  // LKR per 1 USD; replaced by the admin-set rate from the API
    var mode = "foreign";
    var items = [];                  // { slot, usd, plus, isSeo }

    try { if (localStorage.getItem(STORE_KEY) === "local") mode = "local"; } catch (e) { /* ignore */ }

    /* ---------- price slot rendering (admin-controlled prices) ---------- */

    function apply(slot, plan) {
        var strong = slot.querySelector("strong");
        if (!strong) return;

        var isSeo = slot.classList.contains("seo-price");
        var cur = (plan.currency || "").trim();
        var prefix = (plan.prefix || "").trim();
        var amount = (plan.amount || "").trim();
        var note = (plan.note || "").trim();

        if (isSeo) {
            strong.textContent = [prefix, cur, amount].filter(Boolean).join(" ");
            var small = slot.querySelector("small");
            if (small) small.textContent = note;
            return;
        }

        strong.textContent = amount;

        var currencyEl = slot.querySelector(".currency");
        if (cur) {
            if (!currencyEl) {
                currencyEl = document.createElement("span");
                currencyEl.className = "currency";
                slot.insertBefore(currencyEl, strong);
                slot.insertBefore(document.createTextNode(" "), strong);
            }
            currencyEl.textContent = cur;
        } else if (currencyEl) {
            currencyEl.remove();
        }

        // Cards without a currency ("Custom") use the custom-price styling on .price blocks.
        if (slot.classList.contains("price") || slot.classList.contains("custom-price")) {
            slot.classList.toggle("custom-price", !cur);
        }

        var noteEl = slot.querySelector(".price-note, .map-price-note");
        if (noteEl) noteEl.textContent = note;
    }

    /* ---------- Local / Foreign conversion ---------- */

    // Read the current (USD) price of every card so it can be converted back and forth.
    function capture() {
        items = [];
        document.querySelectorAll(SLOTS).forEach(function (slot) {
            var strong = slot.querySelector("strong");
            if (!strong) return;
            var isSeo = slot.classList.contains("seo-price");
            var text = strong.textContent.replace(/\s+/g, " ").trim();
            var m, cur, num, plus;

            if (isSeo) {
                m = text.match(/^(?:(.*?)\s+)?([A-Za-z]{3})\s+([\d,]+(?:\.\d+)?)(\+?)$/);
                if (!m || m[2].toUpperCase() !== "USD") return;
                items.push({ slot: slot, strong: strong, isSeo: true, prefix: m[1] || "", usd: parseFloat(m[3].replace(/,/g, "")), plus: m[4] });
                return;
            }

            var curEl = slot.querySelector(".currency");
            cur = curEl ? curEl.textContent.trim().toUpperCase() : "";
            m = text.match(/^([\d,]+(?:\.\d+)?)(\+?)$/);
            if (cur !== "USD" || !m) return; // "Custom" or non-USD cards stay as they are
            num = parseFloat(m[1].replace(/,/g, ""));
            plus = m[2];
            items.push({ slot: slot, strong: strong, curEl: curEl, isSeo: false, usd: num, plus: plus });
        });
    }

    function fmt(n) {
        return Math.round(n).toLocaleString("en-US");
    }

    function render() {
        var local = mode === "local";
        items.forEach(function (it) {
            var cur = local ? "LKR" : "USD";
            var amount = local ? fmt(Math.round(it.usd * rate / 100) * 100) : fmt(it.usd);
            if (it.isSeo) {
                it.strong.textContent = [it.prefix, cur, amount + it.plus].filter(Boolean).join(" ");
            } else {
                it.curEl.textContent = cur;
                it.strong.textContent = amount + it.plus;
            }
        });

        var box = document.querySelector(".cc-cur-toggle");
        if (!box) return;
        var sw = box.querySelector(".cc-cur-switch");
        sw.setAttribute("aria-checked", local ? "false" : "true");
        box.classList.toggle("is-local", local);
        box.querySelector(".cc-cur-note").textContent = local
            ? "Prices shown in LKR (1 USD ≈ " + fmt(rate) + " LKR)"
            : "Prices shown in USD";
    }

    function setMode(next) {
        mode = next;
        try { localStorage.setItem(STORE_KEY, mode); } catch (e) { /* ignore */ }
        render();
    }

    function injectStyles() {
        if (document.getElementById("cc-cur-style")) return;
        var css =
            ".cc-cur-toggle{display:flex;flex-direction:column;align-items:center;gap:8px;margin:0 auto 32px}" +
            ".cc-cur-row{display:inline-flex;align-items:center;gap:14px;font:700 15px/1 inherit;color:var(--text,#111936)}" +
            ".cc-cur-label{cursor:pointer;opacity:.55;transition:opacity .2s;user-select:none;background:none;border:0;padding:4px 0;font:inherit;color:inherit}" +
            ".cc-cur-toggle:not(.is-local) .cc-cur-label[data-m=foreign],.cc-cur-toggle.is-local .cc-cur-label[data-m=local]{opacity:1}" +
            ".cc-cur-switch{position:relative;width:52px;height:30px;border-radius:999px;border:0;padding:0;cursor:pointer;background:var(--blue,#4267ff);transition:background .2s}" +
            ".cc-cur-toggle.is-local .cc-cur-switch{background:#cfd3de}" +
            ".cc-cur-switch::after{content:'';position:absolute;top:3px;left:25px;width:24px;height:24px;border-radius:50%;background:#fff;box-shadow:0 1px 4px rgba(17,25,54,.25);transition:left .2s}" +
            ".cc-cur-toggle.is-local .cc-cur-switch::after{left:3px}" +
            ".cc-cur-switch:focus-visible,.cc-cur-label:focus-visible{outline:2px solid var(--blue,#4267ff);outline-offset:3px}" +
            ".cc-cur-note{font-size:13px;color:var(--muted,#66708d)}";
        var st = document.createElement("style");
        st.id = "cc-cur-style";
        st.textContent = css;
        document.head.appendChild(st);
    }

    function buildToggle() {
        if (!items.length || document.querySelector(".cc-cur-toggle")) return;
        var firstGrid = document.querySelector(GRIDS);
        if (!firstGrid) return;
        injectStyles();

        var box = document.createElement("div");
        box.className = "cc-cur-toggle";
        box.innerHTML =
            '<div class="cc-cur-row">' +
            '<button type="button" class="cc-cur-label" data-m="local">Local</button>' +
            '<button type="button" class="cc-cur-switch" role="switch" aria-label="Show prices in foreign currency (USD)" aria-checked="true"></button>' +
            '<button type="button" class="cc-cur-label" data-m="foreign">Foreign</button>' +
            "</div>" +
            '<span class="cc-cur-note" aria-live="polite"></span>';
        firstGrid.parentNode.insertBefore(box, firstGrid);

        box.querySelector(".cc-cur-switch").addEventListener("click", function () {
            setMode(mode === "local" ? "foreign" : "local");
        });
        box.querySelectorAll(".cc-cur-label").forEach(function (b) {
            b.addEventListener("click", function () {
                setMode(b.getAttribute("data-m") === "local" ? "local" : "foreign");
            });
        });
    }

    function init() {
        capture();
        buildToggle();
        render();
    }

    /* ---------- load admin prices + rate, then enable the toggle ---------- */

    fetch("api/prices.php?page=" + encodeURIComponent(slug), { headers: { Accept: "application/json" } })
        .then(function (res) { return res.ok ? res.json() : null; })
        .then(function (data) {
            if (!data || !data.ok) return;
            if (data.rate && isFinite(data.rate) && data.rate > 0) rate = Number(data.rate);
            if (!data.plans || !data.plans.length) return;
            var slots = document.querySelectorAll(SLOTS);
            data.plans.forEach(function (plan) {
                var slot = slots[plan.position - 1];
                if (slot) apply(slot, plan);
            });
        })
        .catch(function () { /* static prices stay */ })
        .then(init); // runs whether or not the API answered
})();