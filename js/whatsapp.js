(function () {
    "use strict";

    fetch("api/whatsapp.php", { headers: { Accept: "application/json" } })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (d) { if (d && d.ok && d.enabled) build(d); })
        .catch(function () {});

    function build(d) {
        var css = document.createElement("style");
        css.textContent =
            ".wa-fab{position:fixed;right:25px;bottom:85px;z-index:950;width:56px;height:56px;border:0;border-radius:50%;background:#25d366;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 12px 28px rgba(37,211,102,.38);transition:transform .2s ease}" +
            ".wa-fab:hover{transform:scale(1.07)}" +
            ".wa-fab:focus-visible{outline:3px solid #111936;outline-offset:3px}" +
            ".wa-pop{position:fixed;right:25px;bottom:152px;z-index:950;width:320px;max-width:calc(100vw - 32px);background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 20px 60px rgba(17,25,54,.25);font-family:inherit;opacity:0;visibility:hidden;transform:translateY(12px);transition:.25s ease}" +
            ".wa-pop.open{opacity:1;visibility:visible;transform:translateY(0)}" +
            ".wa-head{background:#075e54;color:#fff;padding:16px 46px 16px 18px;position:relative}" +
            ".wa-head strong{display:block;font-size:16px}" +
            ".wa-head span{font-size:13px;opacity:.85}" +
            ".wa-close{position:absolute;top:10px;right:10px;width:30px;height:30px;border:0;border-radius:50%;background:rgba(255,255,255,.15);color:#fff;font-size:18px;line-height:1;cursor:pointer}" +
            ".wa-body{padding:18px}" +
            ".wa-body p{margin:0 0 6px;color:#66708d;font-size:13.5px}" +
            ".wa-num{display:block;margin-bottom:16px;color:#111936;font-size:20px;font-weight:700;letter-spacing:.01em}" +
            ".wa-start{display:block;text-align:center;background:#25d366;color:#fff;font-weight:700;padding:12px 16px;border-radius:12px;text-decoration:none}" +
            ".wa-start:hover{filter:brightness(1.06)}";
        document.head.appendChild(css);

        var NS = "http://www.w3.org/2000/svg";
        var svg = document.createElementNS(NS, "svg");
        svg.setAttribute("viewBox", "0 0 24 24");
        svg.setAttribute("width", "30");
        svg.setAttribute("height", "30");
        svg.setAttribute("aria-hidden", "true");
        var bubble = document.createElementNS(NS, "path");
        bubble.setAttribute("d", "M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2z");
        bubble.setAttribute("fill", "#fff");
        var g = document.createElementNS(NS, "g");
        g.setAttribute("transform", "translate(6.2 6.2) scale(.48)");
        var phone = document.createElementNS(NS, "path");
        phone.setAttribute("d", "M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z");
        phone.setAttribute("fill", "none");
        phone.setAttribute("stroke", "#25d366");
        phone.setAttribute("stroke-width", "2.6");
        phone.setAttribute("stroke-linecap", "round");
        phone.setAttribute("stroke-linejoin", "round");
        g.appendChild(phone);
        svg.appendChild(bubble);
        svg.appendChild(g);

        var fab = document.createElement("button");
        fab.type = "button";
        fab.className = "wa-fab";
        fab.setAttribute("aria-label", "Chat with us on WhatsApp");
        fab.setAttribute("aria-expanded", "false");
        fab.setAttribute("aria-controls", "wa-pop");
        fab.appendChild(svg);

        var pop = document.createElement("div");
        pop.className = "wa-pop";
        pop.id = "wa-pop";
        pop.setAttribute("role", "dialog");
        pop.setAttribute("aria-label", "WhatsApp contact");

        var head = document.createElement("div");
        head.className = "wa-head";
        var title = document.createElement("strong");
        title.textContent = "Chat with CodeCrafters";
        var sub = document.createElement("span");
        sub.textContent = "WhatsApp Business";
        var close = document.createElement("button");
        close.type = "button";
        close.className = "wa-close";
        close.setAttribute("aria-label", "Close");
        close.textContent = "\u00d7";
        head.appendChild(title);
        head.appendChild(sub);
        head.appendChild(close);

        var body = document.createElement("div");
        body.className = "wa-body";
        var label = document.createElement("p");
        label.textContent = "Message us on WhatsApp:";
        var num = document.createElement("span");
        num.className = "wa-num";
        num.textContent = d.number;
        var start = document.createElement("a");
        start.className = "wa-start";
        start.target = "_blank";
        start.rel = "noopener noreferrer";
        start.textContent = "Start chat";
        start.href = "https://wa.me/" + encodeURIComponent(d.digits) +
            (d.message ? "?text=" + encodeURIComponent(d.message) : "");
        body.appendChild(label);
        body.appendChild(num);
        body.appendChild(start);

        pop.appendChild(head);
        pop.appendChild(body);

        function setOpen(open) {
            pop.classList.toggle("open", open);
            fab.setAttribute("aria-expanded", open ? "true" : "false");
        }

        fab.addEventListener("click", function () { setOpen(!pop.classList.contains("open")); });
        close.addEventListener("click", function () { setOpen(false); fab.focus(); });
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && pop.classList.contains("open")) { setOpen(false); fab.focus(); }
        });

        document.body.appendChild(pop);
        document.body.appendChild(fab);
    }
})();
