(function () {
    "use strict";

    var MAX_HISTORY = 12;
    var history = [];
    var busy = false;

    fetch("api/chat.php", { headers: { Accept: "application/json" } })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (d) { if (d && d.ok && d.enabled) build(d); })
        .catch(function () {});

    function el(tag, cls, text) {
        var n = document.createElement(tag);
        if (cls) { n.className = cls; }
        if (text) { n.textContent = text; }
        return n;
    }

    function build(cfg) {
        var css = document.createElement("style");
        css.textContent =
            ".ccb-fab{position:fixed;left:25px;bottom:25px;z-index:950;width:58px;height:58px;border:0;border-radius:50%;background:linear-gradient(135deg,#4267ff,#7a4dff);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 12px 28px rgba(66,103,255,.38);transition:transform .2s ease}" +
            ".ccb-fab:hover{transform:scale(1.07)}" +
            ".ccb-fab:focus-visible,.ccb-send:focus-visible,.ccb-close:focus-visible{outline:3px solid #111936;outline-offset:3px}" +
            ".ccb-panel{position:fixed;left:25px;bottom:96px;z-index:950;width:360px;max-width:calc(100vw - 32px);height:520px;max-height:calc(100vh - 130px);display:flex;flex-direction:column;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 20px 60px rgba(17,25,54,.28);font-family:inherit;opacity:0;visibility:hidden;transform:translateY(12px);transition:.25s ease}" +
            ".ccb-panel.open{opacity:1;visibility:visible;transform:translateY(0)}" +
            ".ccb-head{background:linear-gradient(135deg,#4267ff,#7a4dff);color:#fff;padding:14px 48px 14px 18px;position:relative;flex-shrink:0}" +
            ".ccb-head strong{display:block;font-size:16px}" +
            ".ccb-head span{font-size:12.5px;opacity:.9}" +
            ".ccb-close{position:absolute;top:12px;right:12px;width:30px;height:30px;border:0;border-radius:50%;background:rgba(255,255,255,.2);color:#fff;font-size:18px;line-height:1;cursor:pointer}" +
            ".ccb-log{flex:1;overflow-y:auto;padding:16px;background:#f7f9ff;display:flex;flex-direction:column;gap:10px}" +
            ".ccb-msg{max-width:85%;padding:10px 13px;border-radius:14px;font-size:14.5px;line-height:1.5;white-space:pre-wrap;word-wrap:break-word}" +
            ".ccb-bot{align-self:flex-start;background:#fff;color:#111936;border:1px solid #e4e8f3;border-bottom-left-radius:4px}" +
            ".ccb-user{align-self:flex-end;background:#4267ff;color:#fff;border-bottom-right-radius:4px}" +
            ".ccb-err{align-self:flex-start;background:#fde6e7;color:#93191f;border:1px solid #f4b6b9}" +
            ".ccb-dots{align-self:flex-start;background:#fff;border:1px solid #e4e8f3;border-radius:14px;padding:12px 14px;display:flex;gap:5px}" +
            ".ccb-dots i{width:7px;height:7px;border-radius:50%;background:#66708d;animation:ccb-b 1.2s infinite ease-in-out}" +
            ".ccb-dots i:nth-child(2){animation-delay:.15s}.ccb-dots i:nth-child(3){animation-delay:.3s}" +
            "@keyframes ccb-b{0%,80%,100%{opacity:.3;transform:scale(.8)}40%{opacity:1;transform:scale(1)}}" +
            ".ccb-form{display:flex;gap:8px;padding:12px;border-top:1px solid #e4e8f3;background:#fff;flex-shrink:0}" +
            ".ccb-input{flex:1;border:1px solid #cfd6ea;border-radius:12px;padding:10px 12px;font:inherit;font-size:14.5px;color:#111936;min-width:0}" +
            ".ccb-input:focus{outline:2px solid rgba(66,103,255,.35);border-color:#4267ff}" +
            ".ccb-send{border:0;border-radius:12px;background:#4267ff;color:#fff;font-weight:700;padding:0 16px;cursor:pointer;font:inherit;font-weight:700}" +
            ".ccb-send:disabled{opacity:.55;cursor:not-allowed}" +
            "@media(max-width:480px){.ccb-fab{left:16px;bottom:16px}.ccb-panel{left:16px;bottom:84px}}" +
            "@media(prefers-reduced-motion:reduce){.ccb-panel,.ccb-fab{transition:none}.ccb-dots i{animation:none}}";
        document.head.appendChild(css);

        var NS = "http://www.w3.org/2000/svg";
        var svg = document.createElementNS(NS, "svg");
        svg.setAttribute("viewBox", "0 0 24 24");
        svg.setAttribute("width", "28");
        svg.setAttribute("height", "28");
        svg.setAttribute("aria-hidden", "true");
        var path = document.createElementNS(NS, "path");
        path.setAttribute("d", "M21 11.5a8.4 8.4 0 0 1-9 8.4 8.6 8.6 0 0 1-3.6-.8L3 21l1.9-5.3A8.4 8.4 0 1 1 21 11.5z");
        path.setAttribute("fill", "none");
        path.setAttribute("stroke", "#fff");
        path.setAttribute("stroke-width", "2");
        path.setAttribute("stroke-linecap", "round");
        path.setAttribute("stroke-linejoin", "round");
        svg.appendChild(path);

        var fab = el("button", "ccb-fab");
        fab.type = "button";
        fab.setAttribute("aria-label", "Chat with " + cfg.name);
        fab.setAttribute("aria-expanded", "false");
        fab.setAttribute("aria-controls", "ccb-panel");
        fab.appendChild(svg);

        var panel = el("div", "ccb-panel");
        panel.id = "ccb-panel";
        panel.setAttribute("role", "dialog");
        panel.setAttribute("aria-label", cfg.name);

        var head = el("div", "ccb-head");
        head.appendChild(el("strong", "", cfg.name));
        head.appendChild(el("span", "", "AI assistant \u00b7 may make mistakes"));
        var close = el("button", "ccb-close", "\u00d7");
        close.type = "button";
        close.setAttribute("aria-label", "Close chat");
        head.appendChild(close);

        var log = el("div", "ccb-log");
        log.setAttribute("role", "log");
        log.setAttribute("aria-live", "polite");

        var form = el("form", "ccb-form");
        var input = el("input", "ccb-input");
        input.type = "text";
        input.maxLength = 1000;
        input.placeholder = "Ask about our services\u2026";
        input.setAttribute("aria-label", "Your message");
        input.autocomplete = "off";
        var send = el("button", "ccb-send", "Send");
        send.type = "submit";
        form.appendChild(input);
        form.appendChild(send);

        panel.appendChild(head);
        panel.appendChild(log);
        panel.appendChild(form);
        document.body.appendChild(fab);
        document.body.appendChild(panel);

        function add(text, cls) {
            var m = el("div", "ccb-msg " + cls, text);
            log.appendChild(m);
            log.scrollTop = log.scrollHeight;
            return m;
        }

        add(cfg.greeting, "ccb-bot");

        function setOpen(open) {
            panel.classList.toggle("open", open);
            fab.setAttribute("aria-expanded", open ? "true" : "false");
            if (open) { setTimeout(function () { input.focus(); }, 120); }
        }

        fab.addEventListener("click", function () { setOpen(!panel.classList.contains("open")); });
        close.addEventListener("click", function () { setOpen(false); fab.focus(); });
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && panel.classList.contains("open")) { setOpen(false); fab.focus(); }
        });

        form.addEventListener("submit", function (e) {
            e.preventDefault();
            var text = input.value.trim();
            if (!text || busy) { return; }

            busy = true;
            send.disabled = true;
            input.value = "";
            add(text, "ccb-user");
            history.push({ role: "user", content: text });
            if (history.length > MAX_HISTORY) { history = history.slice(-MAX_HISTORY); }

            var dots = el("div", "ccb-dots");
            dots.appendChild(document.createElement("i"));
            dots.appendChild(document.createElement("i"));
            dots.appendChild(document.createElement("i"));
            log.appendChild(dots);
            log.scrollTop = log.scrollHeight;

            fetch("api/chat.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ messages: history })
            })
                .then(function (res) {
                    return res.json().catch(function () { return {}; }).then(function (d) {
                        return { ok: res.ok && d.ok, d: d };
                    });
                })
                .then(function (r) {
                    dots.remove();
                    if (r.ok && r.d.reply) {
                        add(r.d.reply, "ccb-bot");
                        history.push({ role: "assistant", content: r.d.reply });
                    } else {
                        history.pop();
                        add(r.d.error || "Something went wrong. Please try again.", "ccb-err");
                    }
                })
                .catch(function () {
                    dots.remove();
                    history.pop();
                    add("Network error. Please try again.", "ccb-err");
                })
                .then(function () {
                    busy = false;
                    send.disabled = false;
                    input.focus();
                });
        });
    }
})();
