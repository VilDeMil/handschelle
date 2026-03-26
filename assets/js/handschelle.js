/**
 * Die Handschelle V.Alpha-2 — Frontend & Admin JavaScript
 * Author: Bernd K.R. Dorfmüller
 */
(function ($) {
    'use strict';

    /* ── Globale Funktion: Straftat-Suche (Formular-Kontext) ── */
    window.hsSearchStraftat = function (btn, engine) {
        var form     = btn.closest('form');
        var nameEl   = form ? form.querySelector('[name="name"]') : null;
        var strafEl  = form ? form.querySelector('[name="straftat"]') : null;
        var q        = encodeURIComponent( ((nameEl ? nameEl.value : '') + ' ' + (strafEl ? strafEl.value : '')).trim() );
        var urls     = {
            google : 'https://www.google.com/search?q=' + q,
            qwant  : 'https://www.qwant.com/?l=de&q=' + q,
            ddg    : 'https://duckduckgo.com/?q=' + q,
            bing   : 'https://www.bing.com/search?q=' + q,
            ecosia : 'https://www.ecosia.org/search?q=' + q,
            baidu  : 'https://www.baidu.com/s?wd=' + q,
            yandex : 'https://yandex.com/search/?text=' + q
        };
        if ( urls[engine] ) window.open( urls[engine], '_blank' );
    };

    /* ── Globale Funktion: Edit-Panel ein-/ausklappen ───────── */
    window.hsToggleEdit = function (id) {
        var $panel = $('#hs-edit-panel-' + id);
        if ($panel.is(':visible')) {
            $panel.slideUp(200);
        } else {
            // Alle anderen schließen
            $('.hs-card-edit-panel').slideUp(200);
            $panel.slideDown(250, function () {
                $('html, body').animate({ scrollTop: $panel.offset().top - 100 }, 400);
                $panel.find('input[type="text"], input[type="date"]').first().focus();
            });
        }
    };

    $(document).ready(function () {

        // ── 1. Zeichen-Zähler für Textareas mit maxlength ────────
        $('textarea[maxlength]').each(function () {
            var $ta      = $(this);
            var max      = parseInt($ta.attr('maxlength'), 10);
            var name     = $ta.attr('name');
            var $counter = $('.hs-char-counter[data-target="' + name + '"]');
            if (!$counter.length) return;

            function updateCounter() {
                var len = $ta.val().length;
                var pct = len / max;
                $counter.text(len + ' / ' + max + ' Zeichen');
                $counter.css('color', pct >= 0.95 ? '#e74c3c' : pct >= 0.80 ? '#e67e22' : '#7f8c8d');
            }
            $ta.on('input keyup paste', updateCounter);
            updateCounter();
        });

        // ── 2. Bild-Vorschau bei Datei-Upload ───────────────────
        $(document).on('change', 'input[type="file"].hs-file-input', function () {
            var file     = this.files && this.files[0];
            var $field   = $(this).closest('.hs-field');
            var $preview = $field.find('.hs-file-preview');
            if (!$preview.length) $preview = $('#hs-file-preview');
            $preview.empty();

            if (file && file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('<img>').attr('src', e.target.result).attr('alt', 'Vorschau').appendTo($preview);
                };
                reader.readAsDataURL(file);
            }
        });

        // ── 3. Auto-Submit bei Dropdown-Suchen ──────────────────
        $('.hs-select').on('change', function () {
            $(this).closest('form').submit();
        });

        // ── 4. Lösch-Bestätigung (Admin-Tabelle) ────────────────
        $(document).on('click', '.hs-btn-delete', function (e) {
            if (!confirm('Diesen Eintrag wirklich löschen?\nDiese Aktion kann nicht rückgängig gemacht werden.')) {
                e.preventDefault();
                return false;
            }
        });

        // ── 5. Frontend-Formular: Pflichtfelder visuell markieren
        $('#hs-eingabe-form').on('submit', function (e) {
            var $form = $(this);
            var valid = true;

            $form.find('[required]').each(function () {
                if ($(this).val().trim() === '') {
                    $(this).css('border-color', '#e74c3c');
                    valid = false;
                } else {
                    $(this).css('border-color', '');
                }
            });

            if (!valid) {
                e.preventDefault();
                var $first = $form.find('[required]').filter(function () {
                    return $(this).val().trim() === '';
                }).first();
                if ($first.length) {
                    $('html, body').animate({ scrollTop: $first.offset().top - 100 }, 400);
                    $first.focus();
                }
            }
        });

        // Fehlerfärbung zurücksetzen sobald User tippt
        $(document).on('input change', '[required]', function () {
            if ($(this).val().trim() !== '') $(this).css('border-color', '');
        });

        // ── 6. Sanftes Einblenden von Alert-Meldungen ───────────
        $('.hs-alert').hide().fadeIn(350);

        // ── 7. Smooth Scroll zu Hash-Ankern ─────────────────────
        if (window.location.hash) {
            var $target = $(window.location.hash);
            if ($target.length) {
                setTimeout(function () {
                    $('html, body').animate({ scrollTop: $target.offset().top - 80 }, 500);
                }, 200);
            }
        }

        // ── 8. Scroll zu bearbeitetem Eintrag nach Speichern ────
        var urlParams = new URLSearchParams(window.location.search);
        var editedId  = urlParams.get('hs_edited');
        if (editedId) {
            var $card = $('#hs-card-' + editedId);
            if ($card.length) {
                setTimeout(function () {
                    $('html, body').animate({ scrollTop: $card.offset().top - 80 }, 500);
                }, 200);
            }
        }

        // ── 9. ESC-Taste schließt offene Edit-Panels ────────────
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                $('.hs-card-edit-panel:visible').slideUp(200);
            }
        });

        // ── 10. Verstorben-Checkbox: DoD-Feld ein-/ausblenden ───
        // Delegated handler works for admin form, frontend form, and all inline edit forms
        $(document).on('change', '.hs-verstorben-cb', function () {
            var $row = $(this).closest('.hs-form-grid, .hs-edit-grid').find('.hs-dod-row');
            if (this.checked) {
                $row.slideDown(200);
            } else {
                $row.slideUp(200);
                $row.find('input[type="date"]').val('');
            }
        });

        // ── 11. Weitere Straftaten – Admin (Haupt-Formular) ──────
        var $offenceContainer = $('#hs-offences-container');
        var offenceCount = $offenceContainer.children('.hs-offence-row').length;

        $('#hs-add-offence-btn').on('click', function () {
            var tpl  = document.getElementById('hs-offence-template');
            if ( !tpl ) return;
            var html = tpl.innerHTML
                .replace(/__IDX__/g,  offenceCount)
                .replace(/__NUM__/g,  offenceCount + 2);
            $offenceContainer.append(html);
            offenceCount++;
        });

        $(document).on('click', '#hs-extra-offences-section .hs-offence-remove-btn', function () {
            var $row = $(this).closest('.hs-offence-row');
            var $flag = $row.find('.hs-offence-delete-flag');
            if ( $flag.val() !== undefined ) {
                $flag.val('1');
                $row.hide();
            } else {
                $row.remove();
            }
        });

        // ── 11b. Weitere Straftaten – Frontend Inline-Edit ───────
        $(document).on('click', '.hs-add-offence-inline-btn', function () {
            var containerId = $(this).data('container');
            var entryId     = $(this).data('entry-id');
            var count       = parseInt($(this).data('count'), 10) || 0;
            var $c          = $('#' + containerId);
            var html = '<div class="hs-offence-row">' +
                '<div class="hs-offence-header">' +
                    '<strong>Straftat ' + (count + 2) + '</strong>' +
                    '<button type="button" class="button hs-offence-inline-remove">🗑</button>' +
                '</div>' +
                '<input type="hidden" name="hs_offences[' + count + '][id]"     value="">' +
                '<input type="hidden" name="hs_offences[' + count + '][delete]" value="0" class="hs-offence-delete-flag">' +
                '<div class="hs-field hs-field-full"><label>Straftat ' + (count + 2) + '</label>' +
                    '<textarea name="hs_offences[' + count + '][straftat]" rows="3"></textarea></div>' +
                '<div class="hs-field"><label>Urteil</label>' +
                    '<input type="text" name="hs_offences[' + count + '][urteil]" maxlength="200" value=""></div>' +
                '<div class="hs-field"><label>Link zur Quelle</label>' +
                    '<input type="url" name="hs_offences[' + count + '][link_quelle]" value=""></div>' +
                '<div class="hs-field"><label>Aktenzeichen</label>' +
                    '<input type="text" name="hs_offences[' + count + '][aktenzeichen]" maxlength="50" value=""></div>' +
                '</div>';
            $c.append(html);
            $(this).data('count', count + 1);
        });

        $(document).on('click', '.hs-offence-inline-remove', function () {
            var $row  = $(this).closest('.hs-offence-row');
            var $flag = $row.find('.hs-offence-delete-flag');
            if ( $flag.length ) {
                $flag.val('1');
                $row.hide();
            } else {
                $row.remove();
            }
        });

        // ── 12. WP Media Library Picker (Admin) ─────────────────
        if ( typeof wp !== 'undefined' && wp.media ) {
            $(document).on('click', '.hs-media-btn', function (e) {
                e.preventDefault();
                var $btn       = $(this);
                var targetId   = $btn.data('target-id');
                var previewId  = $btn.data('preview-id');
                var $input     = targetId   ? $('#' + targetId)   : $btn.closest('.hs-media-picker').find('.hs-media-id');
                var $preview   = previewId  ? $('#' + previewId)  : $btn.closest('.hs-media-picker').find('.hs-media-preview');

                var frame = wp.media({
                    title:    'Bild auswählen',
                    button:   { text: 'Bild verwenden' },
                    multiple: false,
                    library:  { type: 'image' }
                });

                frame.on('select', function () {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $input.val(attachment.id);
                    var thumb = (attachment.sizes && attachment.sizes.thumbnail)
                              ? attachment.sizes.thumbnail.url
                              : attachment.url;
                    $preview.html(
                        '<img src="' + thumb + '" style="max-height:100px;border-radius:4px;display:block;margin-bottom:.4rem;">' +
                        '<small>ID: ' + attachment.id + ' &mdash; ' + attachment.filename + '</small>'
                    );
                });

                // Pre-select currently set attachment
                var currentId = parseInt( $input.val(), 10 );
                if ( currentId > 0 ) {
                    var selection = frame.state().get('selection');
                    var attachment = wp.media.attachment( currentId );
                    attachment.fetch().then(function () {
                        selection.add( attachment );
                    });
                }

                frame.open();
            });

            // ── 13. Bild aus Medienbibliothek entfernen ──────────
            $(document).on('click', '.hs-media-remove-btn', function (e) {
                e.preventDefault();
                var $btn      = $(this);
                var targetId  = $btn.data('target-id');
                var previewId = $btn.data('preview-id');
                var $input    = targetId  ? $('#' + targetId)  : $btn.closest('.hs-media-picker').find('.hs-media-id');
                var $preview  = previewId ? $('#' + previewId) : $btn.closest('.hs-media-picker').find('.hs-media-preview');
                $input.val('');
                $preview.empty();
                $btn.hide();
            });
        }

        // ── 14. Smart-Formular: Personen-Dropdown ────────────────
        var SMART_PERSONAL_FIELDS  = ['name','beruf','spitzname','geburtsort','geburtsdatum','bemerkung_person'];
        var SMART_POLITIK_FIELDS   = ['partei','aufgabe_partei','parlament','parlament_name','status_aktiv'];
        var SMART_SOCIAL_FIELDS    = ['sm_facebook','sm_youtube','sm_personal','sm_twitter','sm_homepage','sm_wikipedia','sm_linkedin','sm_xing','sm_truth_social','sm_sonstige'];
        var SMART_EMAIL_FIELDS     = ['private_email','oeffentliche_email'];

        function hsSmartLockField($form, fieldName, value) {
            var $el = $form.find('[name="' + fieldName + '"]');
            if (!$el.length) return;
            if ($el.is('select')) {
                $el.val(value).prop('disabled', true).addClass('hs-field-locked');
            } else if ($el.is('input[type="checkbox"]')) {
                $el.prop('checked', value == '1').prop('disabled', true).addClass('hs-field-locked');
            } else {
                $el.val(value || '').prop('readonly', true).addClass('hs-field-locked');
            }
        }

        function hsSmartUnlockField($form, fieldName) {
            var $el = $form.find('[name="' + fieldName + '"]');
            if (!$el.length) return;
            if ($el.is('select')) {
                $el.val('').prop('disabled', false).removeClass('hs-field-locked');
            } else if ($el.is('input[type="checkbox"]')) {
                $el.prop('checked', false).prop('disabled', false).removeClass('hs-field-locked');
            } else {
                $el.val('').prop('readonly', false).removeClass('hs-field-locked');
            }
        }

        function hsSmartBuildSearchLinks(name) {
            var enc = encodeURIComponent(name);
            return [
                { url: 'https://www.google.com/search?q=' + enc,                                               label: '🔍 Google' },
                { url: 'https://www.qwant.com/?l=de&q=' + enc,                                                 label: '🔍 Qwant' },
                { url: 'https://duckduckgo.com/?q=' + enc,                                                     label: '🔍 DuckDuckGo' },
                { url: 'https://www.bing.com/search?q=' + enc,                                                 label: '🔍 Bing' },
                { url: 'https://www.abgeordnetenwatch.de/profile?politician_search_keys=' + enc,               label: '🏛 Abgeordnetenwatch' },
            ].map(function(l) {
                return '<a href="' + l.url + '" target="_blank" rel="noopener noreferrer" class="hs-btn hs-search-btn">' + l.label + '</a>';
            }).join('');
        }

        function hsSmartPopulate($form, data) {
            var allFields = SMART_PERSONAL_FIELDS
                .concat(SMART_POLITIK_FIELDS)
                .concat(SMART_SOCIAL_FIELDS)
                .concat(SMART_EMAIL_FIELDS);

            allFields.forEach(function(f) { hsSmartLockField($form, f, data[f] || ''); });

            // Geburtsland select
            $form.find('[name="geburtsland"]').val(data.geburtsland || 'Deutschland').prop('disabled', true).addClass('hs-field-locked');

            // Verstorben checkbox + DoD
            $form.find('[name="verstorben"]').prop('checked', data.verstorben == '1').prop('disabled', true).addClass('hs-field-locked');
            var $dodRow = $form.find('.hs-dod-row');
            if (data.verstorben == '1') {
                $dodRow.show();
                $form.find('[name="dod"]').val(data.dod || '').prop('readonly', true).addClass('hs-field-locked');
            } else {
                $dodRow.hide();
                $form.find('[name="dod"]').val('');
            }

            // Set hidden entry ID
            $form.find('#hs-smart-entry-id').val(data.id);

            // Hide image upload (person already exists in DB)
            $form.find('.hs-smart-image-section').hide();

            // Show existing person's image read-only
            var $imgPreview = $form.find('.hs-smart-image-preview');
            if (data.bild_url) {
                $imgPreview.find('.hs-smart-image-preview-inner').html(
                    '<img src="' + $('<div>').text(data.bild_url).html() + '" style="max-height:140px;border-radius:6px;display:block;">'
                );
                $imgPreview.show();
            } else {
                $imgPreview.hide().find('.hs-smart-image-preview-inner').empty();
            }

            // Show search links for the loaded person
            var $sl = $form.find('.hs-smart-search-links');
            if (data.name) {
                $sl.find('.hs-search-buttons').html(hsSmartBuildSearchLinks(data.name));
                $sl.show();
            }

            // Show locked banner
            $form.find('.hs-smart-person-locked').show();

            // Add clickable links next to social media URL fields that have values
            SMART_SOCIAL_FIELDS.forEach(function(f) {
                var val = data[f] || '';
                var $el = $form.find('[name="' + f + '"]');
                if (val && $el.length) {
                    $('<a>', {
                        href: val,
                        target: '_blank',
                        rel: 'noopener noreferrer',
                        'class': 'hs-sm-link',
                        title: val
                    }).text('🔗').insertAfter($el);
                }
            });
        }

        function hsSmartClear($form) {
            var allFields = SMART_PERSONAL_FIELDS
                .concat(SMART_POLITIK_FIELDS)
                .concat(SMART_SOCIAL_FIELDS)
                .concat(SMART_EMAIL_FIELDS)
                .concat(['geburtsland','dod']);

            allFields.forEach(function(f) { hsSmartUnlockField($form, f); });
            $form.find('[name="verstorben"]').prop('checked', false).prop('disabled', false).removeClass('hs-field-locked');
            $form.find('.hs-dod-row').hide();
            $form.find('#hs-smart-entry-id').val('');
            $form.find('.hs-smart-image-section').show();
            $form.find('.hs-smart-image-preview').hide().find('.hs-smart-image-preview-inner').empty();
            $form.find('.hs-smart-search-links').hide().find('.hs-search-buttons').empty();
            $form.find('.hs-smart-person-locked').hide();
            $form.find('.hs-sm-link').remove();
            $form.find('#hs-smart-person-select').val('');

            // Reset entry-specific fields
            $form.find('[name="straftat"]').val('');
            $form.find('[name="urteil"]').val('');
            $form.find('[name="link_quelle"]').val('');
            $form.find('[name="aktenzeichen"]').val('');
            $form.find('[name="bemerkung"]').val('');
            $form.find('[name="status_straftat"]').val($form.find('[name="status_straftat"] option:first').val());
            var today = new Date().toISOString().slice(0, 10);
            $form.find('[name="datum_eintrag"]').val(today);
        }

        $(document).on('change', '.hs-smart-person-select', function () {
            var entryId = $(this).val();
            var $form   = $(this).closest('form');
            if (!entryId) {
                hsSmartClear($form);
                return;
            }
            $.post(
                handschelle_ajax.ajax_url,
                { action: 'hs_get_person_data', nonce: handschelle_ajax.nonce, entry_id: entryId },
                function (response) {
                    if (response && response.success) {
                        hsSmartPopulate($form, response.data);
                    }
                }
            );
        });

        $(document).on('click', '.hs-smart-clear-btn', function () {
            hsSmartClear($(this).closest('form'));
        });

        /* ── [handschelle-chat] Ollama Chatbot ───────────────── */
        $(document).on('click', '.hs-chat-send-btn', function () {
            var $widget = $(this).closest('.hs-chat-widget');
            hsChatSend($widget);
        });

        $(document).on('keydown', '.hs-chat-input', function (e) {
            // Send on Enter (without Shift)
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                var $widget = $(this).closest('.hs-chat-widget');
                hsChatSend($widget);
            }
        });

        // Auto-grow textarea
        $(document).on('input', '.hs-chat-input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 140) + 'px';
        });

        $(document).on('click', '.hs-chat-clear-btn', function () {
            var $widget = $(this).closest('.hs-chat-widget');
            $widget.find('.hs-chat-messages').empty().append(
                '<div class="hs-chat-empty">Verlauf gelöscht. Stelle eine Frage!</div>'
            );
            $widget.data('hs-chat-history', []);
        });

        // Settings panel toggle
        $(document).on('click', '.hs-chat-settings-btn', function () {
            var $btn    = $(this);
            var $panel  = $btn.closest('.hs-chat-widget').find('.hs-chat-settings-panel');
            var open    = !$panel.prop('hidden');
            $panel.prop('hidden', open);
            $btn.toggleClass('is-open', !open).attr('aria-expanded', !open);
        });

        // Live temperature label
        $(document).on('input', '.hs-chat-settings-temp', function () {
            $(this).closest('.hs-chat-settings-label').find('.hs-chat-temp-value').text($(this).val());
        });

        // Init each chat widget: empty state + load model list
        $('.hs-chat-widget').each(function () {
            var $w = $(this);
            if (!$w.data('hs-chat-history')) {
                $w.data('hs-chat-history', []);
            }
            if ($w.find('.hs-chat-messages').children().length === 0) {
                $w.find('.hs-chat-messages').append(
                    '<div class="hs-chat-empty">Stelle eine Frage!</div>'
                );
            }
            hsChatLoadModels($w);
        });

        // Update active model when user changes the select
        $(document).on('change', '.hs-chat-model-select', function () {
            var $widget = $(this).closest('.hs-chat-widget');
            $widget.data('model', $(this).val());
        });

    });

    function hsChatLoadModels($widget) {
        var $select  = $widget.find('.hs-chat-model-select');
        var current  = $widget.data('model') || $select.val();
        var nonce    = $widget.data('nonce');
        var ajaxUrl  = $widget.data('ajax');

        $select.prop('disabled', true);

        $.post(ajaxUrl, { action: 'hs_chat_models', _nonce: nonce }, function (res) {
            if (!res.success || !res.data || !res.data.models || !res.data.models.length) return;

            $select.empty();
            $.each(res.data.models, function (_, name) {
                var selected = (name === current) ? ' selected' : '';
                $select.append('<option value="' + hsEscape(name) + '"' + selected + '>' + hsEscape(name) + '</option>');
            });

            // If the preferred model isn't in the list, keep it as first option
            if (!res.data.models.includes(current)) {
                $select.prepend('<option value="' + hsEscape(current) + '" selected>' + hsEscape(current) + '</option>');
            }

            $widget.data('model', $select.val());
        }).always(function () {
            $select.prop('disabled', false);
        });
    }

    function hsChatSend($widget) {
        var $input   = $widget.find('.hs-chat-input');
        var $msgs    = $widget.find('.hs-chat-messages');
        var $sendBtn = $widget.find('.hs-chat-send-btn');
        var message  = $input.val().trim();

        if (!message) return;
        if ($sendBtn.prop('disabled')) return;

        var model       = $widget.find('.hs-chat-model-select').val() || $widget.data('model') || 'llama3.2';
        var $panel      = $widget.find('.hs-chat-settings-panel');
        var system      = $panel.find('.hs-chat-settings-system').val().trim() || $widget.data('system') || '';
        var temperature = parseFloat($panel.find('.hs-chat-settings-temp').val()) || 0.7;
        var nonce       = $widget.data('nonce');
        var ajaxUrl     = $widget.data('ajax');
        var history     = $widget.data('hs-chat-history') || [];

        // Remove empty-state placeholder
        $msgs.find('.hs-chat-empty').remove();

        // Append user bubble
        $msgs.append(
            '<div class="hs-chat-bubble hs-chat-bubble-user">' + hsEscape(message) + '</div>'
        );
        $input.val('').css('height', 'auto');
        $sendBtn.prop('disabled', true);

        // Typing indicator
        var $typing = $('<div class="hs-chat-typing"><span></span><span></span><span></span></div>');
        $msgs.append($typing);
        $msgs.scrollTop($msgs[0].scrollHeight);

        $.ajax({
            url  : ajaxUrl,
            type : 'POST',
            data : {
                action      : 'hs_chat',
                _nonce      : nonce,
                message     : message,
                model       : model,
                system      : system,
                temperature : temperature,
                history     : JSON.stringify(history)
            },
            success: function (res) {
                $typing.remove();
                if (res.success && res.data && res.data.reply) {
                    var d     = res.data;
                    var reply = d.reply;
                    $msgs.append(
                        '<div class="hs-chat-bubble hs-chat-bubble-assistant">' + hsEscape(reply) + '</div>'
                    );
                    // Status line: model · time · tok/s
                    var statusParts = [];
                    if (d.model)    statusParts.push(hsEscape(d.model));
                    if (d.time_s)   statusParts.push(d.time_s + 's');
                    if (d.toks_sec) statusParts.push(d.toks_sec + ' tok/s');
                    if (statusParts.length) {
                        $msgs.append(
                            '<div class="hs-chat-status">' +
                            statusParts.join('<span class="hs-chat-status-sep"> · </span>') +
                            '</div>'
                        );
                    }
                    // Update history
                    history.push({ role: 'user',      content: message });
                    history.push({ role: 'assistant', content: reply   });
                    $widget.data('hs-chat-history', history);
                } else {
                    var errMsg = (res.data && res.data.message) ? res.data.message : 'Unbekannter Fehler.';
                    $msgs.append(
                        '<div class="hs-chat-bubble hs-chat-bubble-error">&#9888; ' + hsEscape(errMsg) + '</div>'
                    );
                }
            },
            error: function () {
                $typing.remove();
                $msgs.append(
                    '<div class="hs-chat-bubble hs-chat-bubble-error">&#9888; Verbindungsfehler. Ist Ollama aktiv?</div>'
                );
            },
            complete: function () {
                $sendBtn.prop('disabled', false);
                $input.focus();
                $msgs.scrollTop($msgs[0].scrollHeight);
            }
        });
    }

    function hsEscape(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

})(jQuery);
