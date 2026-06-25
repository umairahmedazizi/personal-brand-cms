/* =============================================================
   MUSTAFA KAMAL — chat widget
   ============================================================= */
(function () {
  "use strict";

  var API = "/api/chat";
  var history = [];
  var busy = false;

  // only show if the backend says the bot is on
  fetch(API, { headers: { Accept: "application/json" } })
    .then(function (r) { return r.json(); })
    .then(function (cfg) { if (cfg && cfg.enabled) init(cfg.greeting); })
    .catch(function () { /* stay silent if unreachable */ });

  function el(tag, cls, html) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (html != null) n.innerHTML = html;
    return n;
  }

  function init(greeting) {
    // ---- launcher button ----
    var btn = el("button", "mkchat__launch", null);
    btn.setAttribute("aria-label", "Open chat assistant");
    btn.innerHTML =
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.4A8 8 0 1 1 21 12z"/></svg>' +
      '<span>Ask</span>';

    // ---- panel ----
    var panel = el("div", "mkchat__panel", null);
    panel.setAttribute("role", "dialog");
    panel.setAttribute("aria-label", "Chat assistant");
    panel.innerHTML =
      '<div class="mkchat__head">' +
        '<div class="mkchat__titlegroup">' +
          '<span class="mkchat__title">Ask Adrian</span>' +
          '<span class="mkchat__sub">Adrian\'s AI-Assistant</span>' +
        '</div>' +
        '<button class="mkchat__close" aria-label="Close chat">&times;</button>' +
      '</div>' +
      '<div class="mkchat__log" aria-live="polite"></div>' +
      '<form class="mkchat__form">' +
        '<input class="mkchat__input" type="text" placeholder="Type your question…" ' +
          'autocomplete="off" aria-label="Your message" />' +
        '<button class="mkchat__send" type="submit" aria-label="Send">' +
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>' +
        '</button>' +
      '</form>';

    document.body.appendChild(btn);
    document.body.appendChild(panel);

    var log = panel.querySelector(".mkchat__log");
    var form = panel.querySelector(".mkchat__form");
    var input = panel.querySelector(".mkchat__input");

    var greeted = false;
    function open() {
      document.body.classList.add("mkchat-open");
      if (!greeted) { addMsg("assistant", greeting || "Hi! How can I help?"); greeted = true; }
      setTimeout(function () { input.focus(); }, 50);
    }
    function close() { document.body.classList.remove("mkchat-open"); }

    btn.addEventListener("click", open);
    panel.querySelector(".mkchat__close").addEventListener("click", close);
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && document.body.classList.contains("mkchat-open")) close();
    });

    function addMsg(role, text) {
      var row = el("div", "mkchat__msg mkchat__msg--" + role, null);
      row.appendChild(el("div", "mkchat__bubble", escapeHtml(text)));
      log.appendChild(row);
      log.scrollTop = log.scrollHeight;
      return row;
    }

    function addTyping() {
      var row = el("div", "mkchat__msg mkchat__msg--assistant mkchat__msg--typing",
        '<div class="mkchat__bubble"><span class="mkchat__dot"></span><span class="mkchat__dot"></span><span class="mkchat__dot"></span></div>');
      log.appendChild(row);
      log.scrollTop = log.scrollHeight;
      return row;
    }

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var text = input.value.trim();
      if (!text || busy) return;

      addMsg("user", text);
      history.push({ role: "user", content: text });
      input.value = "";
      busy = true;
      var typing = addTyping();

      fetch(API, {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ messages: history.slice(-10) })
      })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
        .then(function (res) {
          typing.remove();
          if (res.d && res.d.success) {
            addMsg("assistant", res.d.reply);
            history.push({ role: "assistant", content: res.d.reply });
          } else {
            addMsg("assistant", (res.d && res.d.message) || "Sorry, something went wrong.");
          }
        })
        .catch(function () {
          typing.remove();
          addMsg("assistant", "Network error. Please try again.");
        })
        .finally(function () { busy = false; input.focus(); });
    });
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;").replace(/\n/g, "<br>");
  }
})();
