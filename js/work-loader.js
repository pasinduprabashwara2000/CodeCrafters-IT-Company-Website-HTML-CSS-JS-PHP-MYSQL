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
        .then(function (res) {
            if (!res.ok) {
                console.error("[work-loader] api/projects.php returned HTTP " + res.status);
                return null;
            }
            return res.text().then(function (txt) {
                try { return JSON.parse(txt); }
                catch (e) {
                    console.error("[work-loader] api/projects.php did not return JSON (PHP not running?):", txt.slice(0, 200));
                    return null;
                }
            });
        })
        .then(function (data) {
            if (!data) return;
            if (!data.ok || !data.projects || !data.projects.length) {
                console.warn("[work-loader] No published projects returned. Publish one in Admin > Projects.");
                return;
            }
            grid.textContent = "";
            const section = document.getElementById("wk-projects-section");
            if (section) section.hidden = false;
            data.projects.forEach(function (p) { grid.appendChild(card(p)); });
        })
        .catch(function (err) {
            console.error("[work-loader] fetch failed (opened via file:// or PHP server down?):", err);
        });
})();