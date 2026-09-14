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

  function addMessage(text, role) {
    var bubble = document.createElement("div");
    bubble.className =
      "ai-chat-widget__message ai-chat-widget__message--" + role;
    bubble.setAttribute("dir", "auto");
    bubble.textContent = text;
    messages.appendChild(bubble);
    messages.scrollTop = messages.scrollHeight;
    return bubble;
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
      addMessage(data.text || "", "assistant");
      return;
    }
    if (
      data.type === "answer" ||
      data.type === "chat_token" ||
      data.type === "message.delta" ||
      data.type === "response.delta"
    ) {
      if (!activeResponse) {
        activeResponse = addMessage("", "assistant");
      }
      activeResponse.textContent +=
        data.chat_token ||
        data.token ||
        data.delta ||
        data.content ||
        data.text ||
        "";
      window.clearTimeout(responseEndTimer);
      responseEndTimer = window.setTimeout(finishActiveResponse, 1500);
      messages.scrollTop = messages.scrollHeight;
      return;
    }
    if (
      data.type === "end" ||
      data.type === "completed" ||
      data.type === "message.completed" ||
      data.type === "response.completed"
    ) {
      finishActiveResponse();
      return;
    }
    if (data.type === "error") {
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

  /* Start restoring immediately after a page refresh. connect() reuses this promise. */
  loadHistory().catch(function () {
    /* A connection attempt retries the history request and surfaces any error. */
  });

  launcher.addEventListener("click", function () {
    panel.hidden = false;
    launcher.setAttribute("aria-expanded", "true");
    root.classList.add("is-open");
    intentionallyClosed = false;
    input.focus();
    if (!socket) {
      connect();
    }
  });
  closeButton.addEventListener("click", function () {
    panel.hidden = true;
    launcher.setAttribute("aria-expanded", "false");
    root.classList.remove("is-open");
    launcher.focus();
  });
  form.addEventListener("submit", function (event) {
    event.preventDefault();
    var text = input.value.trim();
    if (!text) {
      setStatus(config.strings.empty, "error");
      return;
    }
    input.value = "";
    input.style.height = "";
    sendMessage(text);
  });
  input.addEventListener("keydown", function (event) {
    if (event.key === "Enter" && !event.shiftKey) {
      event.preventDefault();
      form.requestSubmit();
    }
  });
  input.addEventListener("input", function () {
    input.style.height = "auto";
    input.style.height = Math.min(input.scrollHeight, 120) + "px";
  });
  window.addEventListener("beforeunload", function () {
    intentionallyClosed = true;
    if (socket) {
      socket.close(1000, "page unload");
    }
  });
})();
