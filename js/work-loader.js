(function () {
    "use strict";

    const grid = document.getElementById("wk-grid");
    if (!grid) return;

    function el(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function safeUrl(url) {
        return /^https?:\/\//i.test(url) ? url : "";
    }

    function safeImg(path) {
        return /^(https?:\/\/|[A-Za-z0-9_.\/-]+$)/.test(path) && path.indexOf("..") === -1 ? path : "";
    }

    function card(p) {
        const href = safeUrl(p.url);
        const root = el(href ? "a" : "div", "wk-card");
        if (href) {
            root.href = href;
            root.target = "_blank";
            root.rel = "noopener noreferrer";
        }

        const style = /^[a-f]$/.test(p.thumb_style) ? p.thumb_style : "a";
        const logo = p.logo_path ? safeImg(p.logo_path) : "";
        const thumb = el("div", "wk-card-thumb " + (logo ? "wk-thumb-logo" : "wk-thumb-" + style));
        if (logo) {
            const img = el("img");
            img.src = logo;
            img.alt = p.client + " logo";
            img.loading = "lazy";
            thumb.appendChild(img);
        } else {
            thumb.textContent = p.thumb_icon || "";
        }
        root.appendChild(thumb);

        if (p.tag) {
            const top = el("div", "wk-card-top");
            top.appendChild(el("span", "wk-card-tag", p.tag));
            root.appendChild(top);
        }

        root.appendChild(el("div", "wk-card-client", p.client));
        if (p.domain) root.appendChild(el("div", "wk-card-domain", p.domain));
        root.appendChild(el("h3", "", p.title));
        root.appendChild(el("p", "", p.description));

        if (p.tech && p.tech.length) {
            const tech = el("div", "wk-card-tech");
            p.tech.forEach(function (t) { tech.appendChild(el("span", "", t)); });
            root.appendChild(tech);
        }

        if (href) {
            const link = el("span", "wk-card-link", "Visit Website ");
            link.appendChild(el("span", "arrow", "↗"));
            root.appendChild(link);
        }

        return root;
    }

    fetch("api/projects.php", { headers: { Accept: "application/json" } })
        .then(function (res) { return res.ok ? res.json() : null; })
        .then(function (data) {
            // Keep the built-in static cards when the API is unavailable or has no published projects.
            if (!data || !data.ok || !data.projects || !data.projects.length) return;
            grid.textContent = "";
            data.projects.forEach(function (p) { grid.appendChild(card(p)); });
        })
        .catch(function () { /* static cards stay */ });
})();
