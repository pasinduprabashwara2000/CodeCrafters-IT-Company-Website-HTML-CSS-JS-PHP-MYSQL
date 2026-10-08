(function () {
    "use strict";

    // Page slug = file name without .html (education.html -> "education")
    var slug = location.pathname.split("/").pop().replace(/\.html?$/i, "");
    if (!slug || slug === "index") return;

    var SLOTS = ".pricing-grid .price, .map-pricing-grid .map-price, .seo-pricing-grid .seo-price, .maintenance-grid .maintenance-price";

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

    fetch("api/prices.php?page=" + encodeURIComponent(slug), { headers: { Accept: "application/json" } })
        .then(function (res) { return res.ok ? res.json() : null; })
        .then(function (data) {
            if (!data || !data.ok || !data.plans || !data.plans.length) return;
            var slots = document.querySelectorAll(SLOTS);
            data.plans.forEach(function (plan) {
                var slot = slots[plan.position - 1];
                if (slot) apply(slot, plan);
            });
        })
        .catch(function () { /* static prices stay */ });
})();
