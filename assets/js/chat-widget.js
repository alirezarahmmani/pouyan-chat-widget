(function () {
  "use strict";
  var config = window.AIChatWidgetConfig;
  var root = document.getElementById("ai-chat-widget");
  if (!config || !root || !window.WebSocket || !window.fetch) {
    return;
  }

  var panel = root.querySelector(".ai-chat-widget__panel");
  var launcher = root.querySelector(".ai-chat-widget__launcher");
  var closeButton = root.querySelector(".ai-chat-widget__close");
  var form = root.querySelector(".ai-chat-widget__composer");
  var input = form.querySelector("textarea");
  var submitButton = form.querySelector('button[type="submit"]');
  var messages = root.querySelector(".ai-chat-widget__messages");
  var status = root.querySelector(".ai-chat-widget__status");
  var counter = root.querySelector(".ai-chat-widget__counter");
  var socket = null;
  var reconnectTimer = null;
  var reconnectAttempts = 0;
  var intentionallyClosed = false;
  var activeResponse = null;
  var responseEndTimer = null;
  var pendingMessages = [];
  var connectionPending = false;
  var historyLoaded = false;
  var historyRequestPromise = null;
  var loadingIndicator = null;

  function getSessionId() {
    try {
      var id = window.localStorage.getItem(config.storage) || "";
      if (id && !/^[A-Za-z0-9_.:-]{1,128}$/.test(id)) {
        window.localStorage.removeItem(config.storage);
        return "";
      }
      return id;
    } catch (error) {
      return "";
    }
  }

  function saveSessionId(id) {
    if (!id || !/^[A-Za-z0-9_.:-]{1,128}$/.test(id)) {
      return;
    }
    try {
      window.localStorage.setItem(config.storage, id);
    } catch (error) {
      /* Storage is optional. */
    }
  }

  function setStatus(text, state) {
    status.textContent = text;
    root.setAttribute("data-state", state || "idle");
  }

  function setComposerDisabled(disabled) {
    input.disabled = disabled;
    submitButton.disabled = disabled;
    messages.setAttribute("aria-busy", disabled ? "true" : "false");
  }

  function scrollToLatest() {
    messages.scrollTop = messages.scrollHeight;
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function safeLink(url) {
    try {
      var parsed = new URL(url, window.location.href);
      if (
        parsed.protocol !== "http:" &&
        parsed.protocol !== "https:" &&
        parsed.protocol !== "mailto:"
      ) {
        return "";
      }
      return escapeHtml(parsed.href);
    } catch (error) {
      return "";
    }
  }

  function renderInlineMarkdown(value) {
    var tokens = [];
    var source = String(value);

    function token(html) {
      var marker = "@@AICHATMDTOKEN" + tokens.length + "@@";
      tokens.push(html);
      return marker;
    }

    source = source.replace(/`([^`\n]+)`/g, function (match, code) {
      return token("<code>" + escapeHtml(code) + "</code>");
    });
    source = source.replace(
      /\[([^\]]+)\]\(([^)\s]+)\)/g,
      function (match, label, url) {
        var href = safeLink(url);
        if (!href) {
          return label;
        }
        return token(
          '<a href="' +
            href +
            '" target="_blank" rel="noopener noreferrer">' +
            escapeHtml(label) +
            "</a>",
        );
      },
    );

    source = escapeHtml(source)
      .replace(/\*\*([^*]+)\*\*/g, "<strong>$1</strong>")
      .replace(/__([^_]+)__/g, "<strong>$1</strong>")
      .replace(/~~([^~]+)~~/g, "<del>$1</del>")
      .replace(/(^|[^*])\*([^*\n]+)\*/g, "$1<em>$2</em>")
      .replace(/(^|[^_])_([^_\n]+)_/g, "$1<em>$2</em>");

    tokens.forEach(function (html, index) {
      source = source.split("@@AICHATMDTOKEN" + index + "@@").join(html);
    });
    return source;
  }

  function renderMarkdown(value) {
    var lines = String(value || "")
      .replace(/\r\n?/g, "\n")
      .split("\n");
    var output = [];
    var paragraph = [];
    var listType = "";
    var codeLines = [];
    var codeLanguage = "";
    var inCode = false;

    function flushParagraph() {
      if (paragraph.length) {
        output.push("<p>" + renderInlineMarkdown(paragraph.join(" ")) + "</p>");
        paragraph = [];
      }
    }

    function closeList() {
      if (listType) {
        output.push("</" + listType + ">");
        listType = "";
      }
    }

    function flushCode() {
      var languageClass = codeLanguage
        ? ' class="language-' + escapeHtml(codeLanguage) + '"'
        : "";
      output.push(
        "<pre><code" +
          languageClass +
          ">" +
          escapeHtml(codeLines.join("\n")) +
          "</code></pre>",
      );
      codeLines = [];
      codeLanguage = "";
    }

    lines.forEach(function (line) {
      var fence = line.match(/^```\s*([A-Za-z0-9_+-]*)\s*$/);
      if (fence) {
        if (inCode) {
          flushCode();
          inCode = false;
        } else {
          flushParagraph();
          closeList();
          inCode = true;
          codeLanguage = fence[1] || "";
        }
        return;
      }
      if (inCode) {
        codeLines.push(line);
        return;
      }
      if (!line.trim()) {
        flushParagraph();
        closeList();
        return;
      }

      var heading = line.match(/^(#{1,6})\s+(.+)$/);
      var unordered = line.match(/^\s*[-+*]\s+(.+)$/);
      var ordered = line.match(/^\s*\d+[.)]\s+(.+)$/);
      var quote = line.match(/^>\s?(.*)$/);

      if (heading) {
        flushParagraph();
        closeList();
        var level = heading[1].length;
        output.push(
          "<h" + level + ">" + renderInlineMarkdown(heading[2]) + "</h" + level + ">",
        );
      } else if (/^\s*(?:---+|___+|\*\*\*+)\s*$/.test(line)) {
        flushParagraph();
        closeList();
        output.push("<hr>");
      } else if (quote) {
        flushParagraph();
        closeList();
        output.push("<blockquote>" + renderInlineMarkdown(quote[1]) + "</blockquote>");
      } else if (unordered || ordered) {
        flushParagraph();
        var nextListType = unordered ? "ul" : "ol";
        if (listType !== nextListType) {
          closeList();
          listType = nextListType;
          output.push("<" + listType + ">");
        }
        output.push("<li>" + renderInlineMarkdown((unordered || ordered)[1]) + "</li>");
      } else {
        closeList();
        paragraph.push(line.trim());
      }
    });

    flushParagraph();
    closeList();
    if (inCode) {
      flushCode();
    }
    return output.join("");
  }

  function setMessageContent(bubble, text, role) {
    if (role === "assistant") {
      bubble.innerHTML = renderMarkdown(text);
      return;
    }
    bubble.textContent = text;
  }

  function addMessage(text, role) {
    var bubble = document.createElement("div");
    bubble.className =
      "ai-chat-widget__message ai-chat-widget__message--" + role;
    bubble.setAttribute("dir", "auto");
    setMessageContent(bubble, text, role);
    messages.appendChild(bubble);
    scrollToLatest();
    return bubble;
  }

  function showLoadingIndicator() {
    if (loadingIndicator) {
      return;
    }

    loadingIndicator = document.createElement("div");
    loadingIndicator.className =
      "ai-chat-widget__message ai-chat-widget__message--assistant ai-chat-widget__loading";
    loadingIndicator.setAttribute("role", "status");
    loadingIndicator.setAttribute("aria-label", config.strings.generating);
    loadingIndicator.innerHTML = '<span aria-hidden="true"></span>';
    messages.appendChild(loadingIndicator);
    scrollToLatest();
  }

  function hideLoadingIndicator() {
    if (loadingIndicator && loadingIndicator.parentNode) {
      loadingIndicator.parentNode.removeChild(loadingIndicator);
    }
    loadingIndicator = null;
  }

  function finishActiveResponse() {
    window.clearTimeout(responseEndTimer);
    responseEndTimer = null;
    activeResponse = null;
  }

  function requestSession() {
    return window
      .fetch(config.restUrl, {
        method: "POST",
        credentials: "same-origin",
        cache: "no-store",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": config.nonce,
        },
        body: JSON.stringify({ conversation_id: getSessionId() }),
      })
      .then(function (response) {
        return response.json().then(function (body) {
          if (!response.ok) {
            throw new Error(body.message || config.strings.error);
          }
          return body;
        });
      });
  }

  function requestHistory(sessionId) {
    return window
      .fetch(config.historyUrl, {
        method: "POST",
        credentials: "same-origin",
        cache: "no-store",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": config.nonce,
        },
        body: JSON.stringify({ session_id: sessionId }),
      })
      .then(function (response) {
        return response.json().then(function (body) {
          if (!response.ok) {
            throw new Error(body.message || config.strings.error);
          }
          return body && body.data && typeof body.data === "object"
            ? body.data
            : body;
        });
      });
  }

  function restoreHistory(history) {
    history = history && typeof history === "object" ? history : {};
    var historyMessages = Array.isArray(history.messages)
      ? history.messages
      : [];

    if (history.session_id) {
      saveSessionId(history.session_id);
    }

    if (historyMessages.length) {
      hideLoadingIndicator();
      messages.textContent = "";
      historyMessages.forEach(function (message) {
        if (
          message &&
          (message.role === "user" || message.role === "assistant") &&
          typeof message.content === "string"
        ) {
          addMessage(message.content, message.role);
        }
      });
      scrollToLatest();
    }

    historyLoaded = true;
  }

  function loadHistory() {
    var sessionId = getSessionId();

    if (historyLoaded || !sessionId) {
      historyLoaded = true;
      return window.Promise.resolve();
    }

    if (!historyRequestPromise) {
      historyRequestPromise = requestHistory(sessionId)
        .then(restoreHistory)
        .catch(function (error) {
          historyRequestPromise = null;
          throw error;
        });
    }

    return historyRequestPromise;
  }

  function websocketUrl(url, chat_token) {
    var parsed = new URL(url);
    parsed.searchParams.set("token", chat_token);
    return parsed.toString();
  }

  function connect() {
    if (
      connectionPending ||
      (socket &&
        (socket.readyState === window.WebSocket.CONNECTING ||
          socket.readyState === window.WebSocket.OPEN))
    ) {
      return;
    }

    window.clearTimeout(reconnectTimer);
    connectionPending = true;
    setComposerDisabled(true);
    setStatus(config.strings.connecting, "connecting");
    loadHistory()
      .then(requestSession)
      .then(function (session) {
        if (session.session_id) {
          saveSessionId(session.session_id);
        }
        /* The temporary token remains in memory and is never persisted. */
        socket = new window.WebSocket(
          websocketUrl(session.websocket_url, session.token),
        );
        socket.addEventListener("open", onOpen);
        socket.addEventListener("message", onMessage);
        socket.addEventListener("close", onClose);
        socket.addEventListener("error", onSocketError);
      })
      .catch(function (error) {
        connectionPending = false;
        setStatus(error.message || config.strings.error, "error");
        scheduleReconnect();
      });
  }

  function onOpen() {
    connectionPending = false;
    reconnectAttempts = 0;
    setComposerDisabled(false);
    setStatus(config.strings.connected, "connected");
    while (pendingMessages.length) {
      socket.send(JSON.stringify(pendingMessages.shift()));
    }
  }

  function parseServerEvent(raw) {
    var payload = String(raw).trim();

    if (payload.indexOf("event:") === 0) {
      return { type: payload.slice(6).trim() };
    }

    if (payload.indexOf("data:") === 0) {
      payload = payload.slice(5).trim();
    }

    try {
      return JSON.parse(payload);
    } catch (error) {
      return { type: "message.delta", delta: payload };
    }
  }

  function onMessage(event) {
    var data = parseServerEvent(event.data);

    if (data.session_id) {
      saveSessionId(data.session_id);
    }
    if (data.type === "ping") {
      socket.send(JSON.stringify({ type: "pong" }));
      return;
    }
    if (data.type === "start") {
      finishActiveResponse();
      return;
    }
    if (data.type === "message") {
      hideLoadingIndicator();
      addMessage(data.text || "", "assistant");
      return;
    }
    if (
      data.type === "answer" ||
      data.type === "chat_token" ||
      data.type === "message.delta" ||
      data.type === "response.delta"
    ) {
      hideLoadingIndicator();
      if (!activeResponse) {
        activeResponse = addMessage("", "assistant");
        activeResponse._markdownSource = "";
      }
      activeResponse._markdownSource +=
        data.chat_token ||
        data.token ||
        data.delta ||
        data.content ||
        data.text ||
        "";
      setMessageContent(
        activeResponse,
        activeResponse._markdownSource,
        "assistant",
      );
      window.clearTimeout(responseEndTimer);
      responseEndTimer = window.setTimeout(finishActiveResponse, 1500);
      scrollToLatest();
      return;
    }
    if (
      data.type === "end" ||
      data.type === "completed" ||
      data.type === "message.completed" ||
      data.type === "response.completed"
    ) {
      hideLoadingIndicator();
      finishActiveResponse();
      return;
    }
    if (data.type === "error") {
      hideLoadingIndicator();
      finishActiveResponse();
      addMessage(data.text || data.message || config.strings.error, "system");
    }
  }

  function onClose(event) {
    socket = null;
    connectionPending = false;
    setComposerDisabled(true);
    if (!intentionallyClosed && event.code !== 1000) {
      setStatus(config.strings.disconnected, "connecting");
      scheduleReconnect();
    }
  }

  function onSocketError() {
    setStatus(config.strings.disconnected, "error");
  }

  function scheduleReconnect() {
    if (intentionallyClosed || reconnectAttempts >= 5) {
      hideLoadingIndicator();
      setStatus(config.strings.error, "error");
      return;
    }
    var delay =
      Math.min(16000, Math.pow(2, reconnectAttempts) * 1000) +
      Math.floor(Math.random() * 350);
    reconnectAttempts += 1;
    reconnectTimer = window.setTimeout(connect, delay);
  }

  function sendMessage(text) {
    finishActiveResponse();

    var payload = { message: text, session_id: getSessionId() || null };
    addMessage(text, "user");
    showLoadingIndicator();
    if (socket && socket.readyState === window.WebSocket.OPEN) {
      socket.send(JSON.stringify(payload));
    } else {
      pendingMessages.push(payload);
      if (!socket || socket.readyState === window.WebSocket.CLOSED) {
        intentionallyClosed = false;
        connect();
      }
    }
  }

  /* Without a valid API connection the UI is shown read-only and no requests are made. */
  if (!config.connected) {
    setComposerDisabled(true);
    setStatus(config.strings.offline, "error");
  } else {
    /* Start restoring immediately after a page refresh. connect() reuses this promise. */
    loadHistory().catch(function () {
      /* A connection attempt retries the history request and surfaces any error. */
    });
  }

  function openPanel() {
    panel.hidden = false;
    launcher.setAttribute("aria-expanded", "true");
    root.classList.add("is-open");
    intentionallyClosed = false;
    window.requestAnimationFrame(scrollToLatest);
    input.focus();
    if (!socket && config.connected) {
      connect();
    }
  }

  function closePanel() {
    panel.hidden = true;
    launcher.setAttribute("aria-expanded", "false");
    root.classList.remove("is-open");
    launcher.focus();
  }

  /* Keeps the send button state, height and character counter in sync with the input. */
  function refreshComposer() {
    var length = input.value.length;
    var limit = input.maxLength > 0 ? input.maxLength : 0;
    form.classList.toggle("is-empty", !input.value.trim());
    input.style.height = "auto";
    input.style.height = Math.min(input.scrollHeight, 140) + "px";
    if (counter) {
      var nearLimit = limit && length >= limit * 0.8;
      counter.textContent = nearLimit ? length + " / " + limit : "";
      counter.classList.toggle("is-near-limit", !!nearLimit);
    }
  }

  launcher.addEventListener("click", function () {
    if (panel.hidden) {
      openPanel();
    } else {
      closePanel();
    }
  });
  closeButton.addEventListener("click", closePanel);
  root.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && !panel.hidden) {
      closePanel();
    }
  });
  /* File and voice input are placeholders until the backend supports them. */
  Array.prototype.forEach.call(
    root.querySelectorAll('.ai-chat-widget__tool[aria-disabled="true"]'),
    function (tool) {
      tool.addEventListener("click", function (event) {
        event.preventDefault();
        tool.classList.add("is-nudged");
        window.setTimeout(function () {
          tool.classList.remove("is-nudged");
        }, 1400);
      });
    },
  );
  form.addEventListener("submit", function (event) {
    event.preventDefault();
    var text = input.value.trim();
    if (!text) {
      setStatus(config.strings.empty, "error");
      return;
    }
    if (!config.connected) {
      return;
    }
    input.value = "";
    refreshComposer();
    sendMessage(text);
  });
  input.addEventListener("keydown", function (event) {
    if (event.key === "Enter" && !event.shiftKey && !event.isComposing) {
      event.preventDefault();
      form.requestSubmit();
    }
  });
  input.addEventListener("input", refreshComposer);
  window.addEventListener("beforeunload", function () {
    intentionallyClosed = true;
    if (socket) {
      socket.close(1000, "page unload");
    }
  });
})();
