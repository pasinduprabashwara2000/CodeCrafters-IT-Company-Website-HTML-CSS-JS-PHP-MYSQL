(function () {
    "use strict";

    var slug = location.pathname.split("/").pop().replace(/\.html?$/i, "");
    if (!slug || slug === "index") return;

    var GRID = ".pricing-grid, .map-pricing-grid, .seo-pricing-grid, .maintenance-grid";

    function el(tag, cls, text) {
        var n = document.createElement(tag);
        if (cls) n.className = cls;
        if (text !== undefined) n.textContent = text;
        return n;
    }
    function safeUrl(u) { return /^https?:\/\//i.test(u || "") ? u : ""; }

    function injectStyles() {
        if (document.getElementById("cc-ex-style")) return;
        var st = document.createElement("style");
        st.id = "cc-ex-style";
        st.textContent =
            ".cc-examples{margin-top:22px;padding-top:18px;border-top:1px solid rgba(100,116,160,.25);text-align:left}" +
            ".cc-examples h4{margin:0 0 10px;font-size:15px}" +
            ".cc-examples ul{list-style:none;margin:0;padding:0;display:grid;gap:10px}" +
            ".cc-ex-item{display:block;padding:10px 12px;border:1px solid rgba(100,116,160,.3);border-radius:10px;color:inherit;text-decoration:none;transition:border-color .2s,transform .2s}" +
            "a.cc-ex-item:hover{border-color:var(--blue,#4267ff);transform:translateY(-1px)}" +
            ".cc-ex-title{font-weight:700;font-size:14px;display:flex;justify-content:space-between;gap:8px}" +
            ".cc-ex-name{display:flex;align-items:center;gap:10px;min-width:0}" +
            ".cc-ex-logo{width:34px;height:34px;object-fit:contain;flex:none;border-radius:8px;background:#fff;border:1px solid rgba(100,116,160,.25);padding:3px}" +
            ".cc-ex-desc{display:block;font-size:13px;opacity:.8;margin-top:2px}" +
            ".cc-ex-tech{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px}" +
            ".cc-ex-tech span{font-size:11.5px;padding:2px 8px;border-radius:999px;background:rgba(66,103,255,.12)}";
        document.head.appendChild(st);
    }

    function cardFor(grid, position) {
        var kids = grid.children, n = 0;
        for (var i = 0; i < kids.length; i++) {
            if (kids[i].nodeType !== 1 || kids[i].querySelector(".price, .map-price, .seo-price, .maintenance-price") === null) continue;
            n++;
            if (n === position) return kids[i];
        }
        return null;
    }

    fetch("api/plan-examples.php?page=" + encodeURIComponent(slug), { headers: { Accept: "application/json" } })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            if (!data || !data.ok || !data.examples || !data.examples.length) return;
            var grid = document.querySelector(GRID);
            if (!grid) return;
            injectStyles();

            var byPos = {};
            data.examples.forEach(function (x) { (byPos[x.position] = byPos[x.position] || []).push(x); });

            Object.keys(byPos).forEach(function (pos) {
                var card = cardFor(grid, Number(pos));
                if (!card) return;
                var box = el("div", "cc-examples");
                box.appendChild(el("h4", "", "Example projects"));
                var ul = el("ul");
                byPos[pos].forEach(function (x) {
                    var href = safeUrl(x.url);
                    var li = el("li");
                    var item = el(href ? "a" : "div", "cc-ex-item");
                    if (href) { item.href = href; item.target = "_blank"; item.rel = "noopener noreferrer"; }
                    var t = el("span", "cc-ex-title");
                    var name = el("span", "cc-ex-name");
                    var logo = x.logo_path || "";
                    if (/^(https?:\/\/|[A-Za-z0-9_.\/-]+$)/.test(logo) && logo.indexOf("..") === -1 && logo.charAt(0) !== "/") {
                        var img = el("img", "cc-ex-logo");
                        img.src = logo;
                        img.alt = x.title + " logo";
                        img.loading = "lazy";
                        name.appendChild(img);
                    }
                    name.appendChild(el("span", "", x.title));
                    t.appendChild(name);
                    if (href) t.appendChild(el("span", "", "↗"));
                    item.appendChild(t);
                    if (x.description) item.appendChild(el("span", "cc-ex-desc", x.description));
                    if (x.tech && x.tech.length) {
                        var tech = el("span", "cc-ex-tech");
                        x.tech.forEach(function (s) { tech.appendChild(el("span", "", s)); });
                        item.appendChild(tech);
                    }
                    li.appendChild(item);
                    ul.appendChild(li);
                });
                box.appendChild(ul);
                card.appendChild(box);
            });
        })
        .catch(function () { /* no examples shown */ });
})();
