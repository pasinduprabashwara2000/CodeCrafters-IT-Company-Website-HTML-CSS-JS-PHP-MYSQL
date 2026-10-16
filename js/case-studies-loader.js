(function () {
    "use strict";

    var host = document.getElementById("wk-cases");
    if (!host) return;

    var WORDS = ["Zero", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten"];

    function el(tag, cls, text) {
        var n = document.createElement(tag);
        if (cls) n.className = cls;
        if (text !== undefined) n.textContent = text;
        return n;
    }

    function build(c, i) {
        var root = el("div", "wk-case" + (i % 2 === 1 ? " wk-case-reverse" : ""));
        var visual = el("div", "wk-case-visual");
        var src = c.image_src || "";
        if (/^(https?:\/\/|[A-Za-z0-9_.\/-]+$)/.test(src) && src.indexOf("..") === -1 && src.charAt(0) !== "/") {
            var img = el("img");
            img.src = src;
            img.alt = c.title;
            img.loading = "lazy";
            visual.appendChild(img);
        }
        root.appendChild(visual);

        var body = el("div");
        body.appendChild(el("div", "wk-case-label", c.label));
        body.appendChild(el("h3", "", c.title));
        body.appendChild(el("p", "", c.description));

        if (c.tech && c.tech.length) {
            var tech = el("div", "wk-case-tech");
            c.tech.forEach(function (t) { tech.appendChild(el("span", "", t)); });
            body.appendChild(tech);
        }
        if (c.metrics && c.metrics.length) {
            var met = el("div", "wk-case-metrics");
            c.metrics.forEach(function (m) {
                var d = el("div");
                d.appendChild(el("strong", "", m.value));
                if (m.label) d.appendChild(el("span", "", m.label));
                met.appendChild(d);
            });
            body.appendChild(met);
        }
        if (c.quote) {
            var q = el("blockquote", "wk-case-quote", "“" + c.quote + "”");
            if (c.quote_author) q.appendChild(el("footer", "", c.quote_author));
            body.appendChild(q);
        }
        root.appendChild(body);
        return root;
    }

    fetch("api/case-studies.php", { headers: { Accept: "application/json" } })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
            // The section stays hidden until at least one case study is published in the admin.
            if (!data || !data.ok || !data.case_studies || !data.case_studies.length) return;
            host.querySelectorAll(".wk-case").forEach(function (n) { n.remove(); });
            var section = document.getElementById("wk-cases-section");
            if (section) section.hidden = false;
            data.case_studies.forEach(function (c, i) { host.appendChild(build(c, i)); });

            var n = data.case_studies.length;
            var count = document.getElementById("wk-cases-count");
            if (count) count.textContent = (WORDS[n] || String(n)) + (n === 1 ? " Launch" : " Launches");
        })
        .catch(function () { /* section stays hidden */ });
})();
