(function () {
	'use strict';
	var config = window.AIChatWidgetConfig;
	var root = document.getElementById('ai-chat-widget');
	if (!config || !root || !window.WebSocket || !window.fetch) { return; }

	var panel = root.querySelector('.ai-chat-widget__panel');
	var launcher = root.querySelector('.ai-chat-widget__launcher');
	var closeButton = root.querySelector('.ai-chat-widget__close');
	var form = root.querySelector('.ai-chat-widget__composer');
	var input = form.querySelector('textarea');
	var messages = root.querySelector('.ai-chat-widget__messages');
	var status = root.querySelector('.ai-chat-widget__status');
	var socket = null;
	var reconnectTimer = null;
	var reconnectAttempts = 0;
	var intentionallyClosed = false;
	var activeResponse = null;
	var pendingMessages = [];

	function getConversationId() {
		try { return window.localStorage.getItem(config.storage) || ''; } catch (error) { return ''; }
	}

	function saveConversationId(id) {
		if (!id || !/^[A-Za-z0-9_.:-]{1,128}$/.test(id)) { return; }
		try { window.localStorage.setItem(config.storage, id); } catch (error) { /* Storage is optional. */ }
	}

	function setStatus(text, state) {
		status.textContent = text;
		root.setAttribute('data-state', state || 'idle');
	}

	function addMessage(text, role) {
		var bubble = document.createElement('div');
		bubble.className = 'ai-chat-widget__message ai-chat-widget__message--' + role;
		bubble.textContent = text;
		messages.appendChild(bubble);
		messages.scrollTop = messages.scrollHeight;
		return bubble;
	}

	function requestSession() {
		return window.fetch(config.restUrl, {
			method: 'POST', credentials: 'same-origin', cache: 'no-store',
			headers: {'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce},
			body: JSON.stringify({conversation_id: getConversationId()})
		}).then(function (response) {
			return response.json().then(function (body) {
				if (!response.ok) { throw new Error(body.message || config.strings.error); }
				return body;
			});
		});
	}

	function websocketUrl(url, token) {
		var parsed = new URL(url);
		parsed.searchParams.set('access_token', token);
		return parsed.toString();
	}

	function connect() {
		window.clearTimeout(reconnectTimer);
		setStatus(config.strings.connecting, 'connecting');
		requestSession().then(function (session) {
			if (session.conversation_id) { saveConversationId(session.conversation_id); }
			/* The temporary token remains in memory and is never persisted. */
			socket = new window.WebSocket(websocketUrl(session.websocket_url, session.token));
			socket.addEventListener('open', onOpen);
			socket.addEventListener('message', onMessage);
			socket.addEventListener('close', onClose);
			socket.addEventListener('error', onSocketError);
		}).catch(function (error) {
			setStatus(error.message || config.strings.error, 'error');
			scheduleReconnect();
		});
	}

	function onOpen() {
		reconnectAttempts = 0;
		setStatus(config.strings.connected, 'connected');
		if (getConversationId()) {
			socket.send(JSON.stringify({type: 'session.resume', conversation_id: getConversationId()}));
		}
		while (pendingMessages.length) { socket.send(JSON.stringify(pendingMessages.shift())); }
	}

	function onMessage(event) {
		var data;
		try { data = JSON.parse(event.data); } catch (error) { data = {type: 'message.delta', delta: String(event.data)}; }
		if (data.conversation_id) { saveConversationId(data.conversation_id); }
		if (data.type === 'ping') { socket.send(JSON.stringify({type: 'pong'})); return; }
		if (data.type === 'message.delta' || data.type === 'response.delta') {
			if (!activeResponse) {
				activeResponse = addMessage('', 'assistant');
				activeResponse.classList.add('is-streaming');
			}
			activeResponse.textContent += data.delta || data.content || '';
			messages.scrollTop = messages.scrollHeight;
			return;
		}
		if (data.type === 'message.completed' || data.type === 'response.completed') {
			if (activeResponse) { activeResponse.classList.remove('is-streaming'); }
			activeResponse = null;
			return;
		}
		if (data.type === 'error') {
			if (activeResponse) { activeResponse.classList.remove('is-streaming'); activeResponse = null; }
			addMessage(data.message || config.strings.error, 'system');
		}
	}

	function onClose(event) {
		socket = null;
		if (!intentionallyClosed && event.code !== 1000) {
			setStatus(config.strings.disconnected, 'connecting');
			scheduleReconnect();
		}
	}

	function onSocketError() { setStatus(config.strings.disconnected, 'error'); }

	function scheduleReconnect() {
		if (intentionallyClosed || reconnectAttempts >= 5) { setStatus(config.strings.error, 'error'); return; }
		var delay = Math.min(16000, Math.pow(2, reconnectAttempts) * 1000) + Math.floor(Math.random() * 350);
		reconnectAttempts += 1;
		reconnectTimer = window.setTimeout(connect, delay);
	}

	function sendMessage(text) {
		var payload = {type: 'message.send', message: text, conversation_id: getConversationId()};
		addMessage(text, 'user');
		if (socket && socket.readyState === window.WebSocket.OPEN) { socket.send(JSON.stringify(payload)); }
		else {
			pendingMessages.push(payload);
			if (!socket || socket.readyState === window.WebSocket.CLOSED) { intentionallyClosed = false; connect(); }
		}
	}

	launcher.addEventListener('click', function () {
		panel.hidden = false;
		launcher.setAttribute('aria-expanded', 'true');
		root.classList.add('is-open');
		intentionallyClosed = false;
		input.focus();
		if (!socket) { connect(); }
	});
	closeButton.addEventListener('click', function () {
		panel.hidden = true;
		launcher.setAttribute('aria-expanded', 'false');
		root.classList.remove('is-open');
		launcher.focus();
	});
	form.addEventListener('submit', function (event) {
		event.preventDefault();
		var text = input.value.trim();
		if (!text) { setStatus(config.strings.empty, 'error'); return; }
		input.value = ''; input.style.height = ''; sendMessage(text);
	});
	input.addEventListener('keydown', function (event) {
		if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); form.requestSubmit(); }
	});
	input.addEventListener('input', function () {
		input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 120) + 'px';
	});
	window.addEventListener('beforeunload', function () {
		intentionallyClosed = true;
		if (socket) { socket.close(1000, 'page unload'); }
	});
}());
