/**
 * Die-Handschelle – Ollama Chat
 *
 * All Ollama requests go through the WordPress AJAX proxy (no direct
 * browser→Ollama calls, so no CORS issues).
 *
 * Globals injected by wp_localize_script:
 *   hsChatAjax.ajaxUrl  – URL of admin-ajax.php
 *   hsChatAjax.nonce    – wp nonce for hs_chat_nonce
 */
(function () {
    'use strict';

    /* ── Config ─────────────────────────────────────────────────── */
    var ajaxUrl = (typeof hsChatAjax !== 'undefined') ? hsChatAjax.ajaxUrl : '/wp-admin/admin-ajax.php';
    var nonce   = (typeof hsChatAjax !== 'undefined') ? hsChatAjax.nonce   : '';

    /* ── State ──────────────────────────────────────────────────── */
    var state = {
        endpoint:  '',   // e.g. "192.168.1.100:11434" (raw, as entered)
        model:     '',
        history:   [],   // [{role, content}]
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

    /* ── Fetch models via PHP proxy ─────────────────────────────── */
    function fetchModels(endpoint, callback) {
        var body = new FormData();
        body.append('action',   'hs_chat_models');
        body.append('nonce',    nonce);
        body.append('endpoint', endpoint);

        fetch(ajaxUrl, { method: 'POST', body: body })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function (data) {
                if (!data.success) {
                    callback(data.data || 'Unbekannter Fehler');
                    return;
                }
                var models = ((data.data && data.data.models) || []).map(function (m) {
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
        bubble.classList.remove('hs-chat__bubble--streaming');
    }

    /* ── Lock / unlock composer ──────────────────────────────────── */
    function setComposerLocked(locked) {
        var sendBtn   = $('hs-chat-send-btn');
        var inputEl   = $('hs-chat-input');
        if (sendBtn) sendBtn.disabled  = locked;
        if (inputEl) inputEl.disabled  = locked;
        state.streaming = locked;
    }

    /* ── Send message + stream response via PHP proxy ────────────── */
    function sendMessage(text) {
        if (state.streaming || !text.trim()) return;

        state.history.push({ role: 'user', content: text });
        appendMessage('user', text, false);
        setComposerLocked(true);

        var assistantBubble = appendMessage('assistant', '', true);
        var assistantInner  = assistantBubble
            ? assistantBubble.parentElement
                ? assistantBubble  // assistantBubble IS the inner div
                : null
            : null;
        /* appendMessage returns the inner div directly */
        var innerEl = assistantBubble; /* alias for clarity */

        var body = new FormData();
        body.append('action',   'hs_chat_stream');
        body.append('nonce',    nonce);
        body.append('endpoint', state.endpoint);
        body.append('model',    state.model);
        body.append('messages', JSON.stringify(state.history.slice()));

        var accumulated = '';

        fetch(ajaxUrl, { method: 'POST', body: body })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.body;
            })
            .then(function (readableStream) {
                var reader  = readableStream.getReader();
                var decoder = new TextDecoder();

                function read() {
                    reader.read().then(function (chunk) {
                        if (chunk.done) {
                            finishBubble(innerEl && innerEl.parentElement);
                            if (accumulated) {
                                state.history.push({ role: 'assistant', content: accumulated });
                            }
                            setComposerLocked(false);
                            var chatInput = $('hs-chat-input');
                            if (chatInput) chatInput.focus();
                            return;
                        }

                        var lines = decoder.decode(chunk.value, { stream: true }).split('\n');
                        lines.forEach(function (line) {
                            if (!line.trim()) return;
                            try {
                                var json = JSON.parse(line);
                                /* Ollama /api/chat NDJSON token */
                                if (json.message && json.message.content) {
                                    accumulated += json.message.content;
                                    updateBubbleText(innerEl, accumulated);
                                }
                                /* Proxy error forwarded as JSON */
                                if (json.error) {
                                    finishBubble(innerEl && innerEl.parentElement);
                                    appendMessage('system', 'Fehler: ' + json.error, false);
                                    setComposerLocked(false);
                                }
                            } catch (e) { /* ignore partial JSON lines */ }
                        });
                        read();
                    }).catch(function (err) {
                        finishBubble(innerEl && innerEl.parentElement);
                        appendMessage('system', 'Fehler: ' + err.message, false);
                        setComposerLocked(false);
                    });
                }
                read();
            })
            .catch(function (err) {
                finishBubble(innerEl && innerEl.parentElement);
                appendMessage('system', 'Fehler: ' + (err.message || 'Unbekannter Fehler'), false);
                setComposerLocked(false);
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
        var connectBtn    = $('hs-chat-connect-btn');
        var endpointInput = $('hs-chat-endpoint');

        if (!connectBtn || !endpointInput) return; // widget not on this page

        /* ── Step 1: Connect ── */
        function doConnect() {
            var raw = endpointInput.value.trim();
            if (!raw) {
                setError('hs-chat-endpoint-error', 'Bitte eine IP-Adresse und Port eingeben.');
                return;
            }
            setError('hs-chat-endpoint-error', '');
            connectBtn.disabled = true;
            connectBtn.textContent = 'Verbinde…';

            fetchModels(raw, function (err, models) {
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

                state.endpoint = raw;

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
                    ' gefunden auf ' + raw;

                showStep('model');
            });
        }

        connectBtn.addEventListener('click', doConnect);
        endpointInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') doConnect();
        });

        /* ── Step 2: Model select ── */
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

        /* ── Step 3: Chat ── */
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

})();
