/**
 * Die-Handschelle – Ollama Chat
 *
 * Steps:
 *   1. Enter endpoint (IP:PORT)
 *   2. Fetch + select model
 *   3. Chat (streaming via Fetch API)
 */
(function () {
    'use strict';

    /* ── State ──────────────────────────────────────────────────── */
    var state = {
        endpoint: '',   // e.g. "http://192.168.1.100:11434"
        model:    '',
        history:  [],   // [{role, content}]
        streaming: false,
    };

    /* ── DOM helpers ────────────────────────────────────────────── */
    function $(id) { return document.getElementById(id); }

    function showStep(step) {
        ['endpoint', 'model', 'chat'].forEach(function (s) {
            var el = $('hs-chat-step-' + s);
            if (el) el.hidden = (s !== step);
        });
    }

    function setError(id, msg) {
        var el = $(id);
        if (!el) return;
        el.textContent = msg;
        el.hidden = !msg;
    }

    /* ── Normalise endpoint URL ─────────────────────────────────── */
    function normaliseEndpoint(raw) {
        raw = raw.trim().replace(/\/+$/, '');
        if (!/^https?:\/\//i.test(raw)) {
            raw = 'http://' + raw;
        }
        return raw;
    }

    /* ── Fetch models from /api/tags ────────────────────────────── */
    function fetchModels(endpoint, callback) {
        fetch(endpoint + '/api/tags', { method: 'GET' })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function (data) {
                var models = (data.models || []).map(function (m) {
                    return m.name || m.model || '';
                }).filter(Boolean);
                callback(null, models);
            })
            .catch(function (err) {
                callback(err.message || 'Verbindung fehlgeschlagen');
            });
    }

    /* ── Render a message bubble ─────────────────────────────────── */
    function appendMessage(role, content, streaming) {
        var container = $('hs-chat-messages');
        if (!container) return null;

        var bubble = document.createElement('div');
        bubble.className = 'hs-chat__bubble hs-chat__bubble--' + role;
        if (streaming) bubble.classList.add('hs-chat__bubble--streaming');

        var inner = document.createElement('div');
        inner.className = 'hs-chat__bubble-text';
        inner.textContent = content;
        bubble.appendChild(inner);

        container.appendChild(bubble);
        container.scrollTop = container.scrollHeight;
        return inner;
    }

    function updateBubbleText(inner, text) {
        if (!inner) return;
        inner.textContent = text;
        var container = $('hs-chat-messages');
        if (container) container.scrollTop = container.scrollHeight;
    }

    function finishBubble(bubble) {
        if (!bubble) return;
        var parent = bubble.parentElement;
        if (parent) parent.classList.remove('hs-chat__bubble--streaming');
    }

    /* ── Send message + stream response ─────────────────────────── */
    function sendMessage(text) {
        if (state.streaming || !text.trim()) return;

        state.history.push({ role: 'user', content: text });
        appendMessage('user', text, false);

        var sendBtn   = $('hs-chat-send-btn');
        var inputEl   = $('hs-chat-input');
        if (sendBtn)  sendBtn.disabled = true;
        if (inputEl)  inputEl.disabled = true;
        state.streaming = true;

        var assistantInner = appendMessage('assistant', '', true);

        var body = JSON.stringify({
            model:    state.model,
            messages: state.history.slice(),
            stream:   true,
        });

        fetch(state.endpoint + '/api/chat', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    body,
        })
        .then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.body;
        })
        .then(function (readableStream) {
            var reader  = readableStream.getReader();
            var decoder = new TextDecoder();
            var accumulated = '';

            function read() {
                reader.read().then(function (chunk) {
                    if (chunk.done) {
                        finishBubble(assistantInner && assistantInner.parentElement);
                        state.history.push({ role: 'assistant', content: accumulated });
                        state.streaming = false;
                        if (sendBtn) sendBtn.disabled = false;
                        if (inputEl) { inputEl.disabled = false; inputEl.focus(); }
                        return;
                    }
                    var lines = decoder.decode(chunk.value, { stream: true }).split('\n');
                    lines.forEach(function (line) {
                        if (!line.trim()) return;
                        try {
                            var json = JSON.parse(line);
                            var token = (json.message && json.message.content) ? json.message.content : '';
                            accumulated += token;
                            updateBubbleText(assistantInner, accumulated);
                        } catch (e) { /* ignore partial JSON */ }
                    });
                    read();
                }).catch(function (err) {
                    finishBubble(assistantInner && assistantInner.parentElement);
                    appendMessage('system', 'Fehler: ' + err.message, false);
                    state.streaming = false;
                    if (sendBtn) sendBtn.disabled = false;
                    if (inputEl) { inputEl.disabled = false; }
                });
            }
            read();
        })
        .catch(function (err) {
            finishBubble(assistantInner && assistantInner.parentElement);
            appendMessage('system', 'Fehler: ' + (err.message || 'Unbekannter Fehler'), false);
            state.streaming = false;
            if (sendBtn) sendBtn.disabled = false;
            if (inputEl) { inputEl.disabled = false; }
        });
    }

    /* ── Auto-resize textarea ───────────────────────────────────── */
    function autoResize(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 160) + 'px';
    }

    /* ── Reset to step 1 ────────────────────────────────────────── */
    function reset() {
        state.endpoint  = '';
        state.model     = '';
        state.history   = [];
        state.streaming = false;

        var endpointInput = $('hs-chat-endpoint');
        if (endpointInput) endpointInput.value = '';

        var select = $('hs-chat-model-select');
        if (select) { select.innerHTML = '<option value="">-- Modell auswählen --</option>'; }

        var messages = $('hs-chat-messages');
        if (messages) messages.innerHTML = '';

        var chatInput = $('hs-chat-input');
        if (chatInput) { chatInput.value = ''; chatInput.style.height = 'auto'; }

        setError('hs-chat-endpoint-error', '');
        setError('hs-chat-model-error', '');
        showStep('endpoint');
    }

    /* ── Init ───────────────────────────────────────────────────── */
    function init() {
        /* Step 1 – Connect */
        var connectBtn    = $('hs-chat-connect-btn');
        var endpointInput = $('hs-chat-endpoint');

        if (!connectBtn || !endpointInput) return; // widget not on this page

        function doConnect() {
            var raw = endpointInput.value;
            if (!raw.trim()) {
                setError('hs-chat-endpoint-error', 'Bitte eine IP-Adresse und Port eingeben.');
                return;
            }
            setError('hs-chat-endpoint-error', '');
            connectBtn.disabled = true;
            connectBtn.textContent = 'Verbinde…';

            var ep = normaliseEndpoint(raw);
            fetchModels(ep, function (err, models) {
                connectBtn.disabled = false;
                connectBtn.textContent = 'Verbinden';

                if (err) {
                    setError('hs-chat-endpoint-error', 'Fehler: ' + err);
                    return;
                }
                if (!models.length) {
                    setError('hs-chat-endpoint-error', 'Keine Modelle gefunden. Bitte Ollama-Server prüfen.');
                    return;
                }

                state.endpoint = ep;

                var select = $('hs-chat-model-select');
                select.innerHTML = '<option value="">-- Modell auswählen --</option>';
                models.forEach(function (name) {
                    var opt = document.createElement('option');
                    opt.value = name;
                    opt.textContent = name;
                    select.appendChild(opt);
                });

                $('hs-chat-model-desc').textContent =
                    models.length + ' Modell' + (models.length !== 1 ? 'e' : '') +
                    ' gefunden auf ' + ep;

                showStep('model');
            });
        }

        connectBtn.addEventListener('click', doConnect);
        endpointInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') doConnect();
        });

        /* Step 2 – Model select */
        var modelBtn = $('hs-chat-model-btn');
        var backBtn  = $('hs-chat-back-btn');

        modelBtn.addEventListener('click', function () {
            var select = $('hs-chat-model-select');
            if (!select.value) {
                setError('hs-chat-model-error', 'Bitte ein Modell auswählen.');
                return;
            }
            setError('hs-chat-model-error', '');
            state.model = select.value;
            $('hs-chat-active-model').textContent = state.model;
            state.history = [];
            $('hs-chat-messages').innerHTML = '';
            showStep('chat');
            $('hs-chat-input').focus();
        });

        backBtn.addEventListener('click', function () {
            showStep('endpoint');
        });

        /* Step 3 – Chat */
        var sendBtn   = $('hs-chat-send-btn');
        var chatInput = $('hs-chat-input');
        var resetBtn  = $('hs-chat-reset-btn');

        sendBtn.addEventListener('click', function () {
            var text = chatInput.value;
            chatInput.value = '';
            chatInput.style.height = 'auto';
            sendMessage(text);
        });

        chatInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                var text = chatInput.value;
                chatInput.value = '';
                chatInput.style.height = 'auto';
                sendMessage(text);
            }
        });

        chatInput.addEventListener('input', function () {
            autoResize(chatInput);
        });

        resetBtn.addEventListener('click', reset);
    }

    /* ── Bootstrap ───────────────────────────────────────────────── */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    /* Support multiple widgets on same page */
    document.addEventListener('DOMContentLoaded', function () {
        /* nothing extra needed – IDs are unique per shortcode instance */
    });

})();
