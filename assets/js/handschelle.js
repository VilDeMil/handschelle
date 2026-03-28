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

        // Apply URL override: reload model list from new address
        $(document).on('click', '.hs-chat-settings-url-apply', function () {
            var $widget = $(this).closest('.hs-chat-widget');
            var url     = $widget.find('.hs-chat-settings-url').val().trim();
            $widget.data('hs-chat-custom-url', url || null);
            hsChatLoadModels($widget);
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
            hsChatLoadModels($w, function () {
                var param = $w.data('urlparam');
                if (!param) return;
                var msg = new URLSearchParams(window.location.search).get(param);
                if (!msg) return;
                $w.find('.hs-chat-input').val(msg.trim());
                hsChatSend($w);
            });
        });

        // Update active model when user changes the select
        $(document).on('change', '.hs-chat-model-select', function () {
            var $widget = $(this).closest('.hs-chat-widget');
            $widget.data('model', $(this).val());
        });

        // Dropdown helper for [handschelle-chat-dropdown]
        $(document).on('change', '.hs-chat-name-select', function () {
            var $select   = $(this);
            var $widget   = $select.closest('.hs-chat-widget');
            var $opt      = $select.find('option:selected');

            var fragenList = [];
            try {
                fragenList = JSON.parse($opt.attr('data-fragen') || '[]');
            } catch (e) {}
            fragenList = $.grep(fragenList, function (f) { return f.trim() !== ''; });

            if (!fragenList.length) return;

            // Clear previous conversation and cancel any pending queue
            $widget.data('hs-chat-history', []);
            $widget.data('hs-chat-queue', []);
            $widget.find('.hs-chat-messages').empty();

            // Queue remaining questions; send the first immediately
            $widget.data('hs-chat-queue', fragenList.slice(1));
            var $input = $widget.find('.hs-chat-input');
            $input.val(fragenList[0]).trigger('input');
            hsChatSend($widget);
        });

        // Multi-LLM toggle
        $(document).on('click', '.hs-chat-multi-btn', function () {
            var $btn    = $(this);
            var $widget = $btn.closest('.hs-chat-widget');
            var active  = $btn.attr('aria-pressed') === 'true';
            var nowOn   = !active;

            $btn.attr('aria-pressed', nowOn).toggleClass('is-active', nowOn);
            $widget.data('hs-chat-multi', nowOn);

            var $multiPanel = $widget.find('.hs-chat-settings-multi-row');
            $multiPanel.prop('hidden', !nowOn);

            // Auto-open settings panel so checkboxes are visible
            if (nowOn) {
                var $panel = $widget.find('.hs-chat-settings-panel');
                $panel.prop('hidden', false);
                $widget.find('.hs-chat-settings-btn').addClass('is-open').attr('aria-expanded', 'true');
            }
        });

        // Repost button: show model selector
        $(document).on('click', '.hs-chat-repost-btn', function () {
            var $btn      = $(this);
            var $status   = $btn.closest('.hs-chat-status');
            var $controls = $status.find('.hs-chat-repost-controls');
            var $widget   = $btn.closest('.hs-chat-widget');
            var $select   = $controls.find('.hs-chat-repost-select');

            $btn.attr('hidden', '');
            $controls.removeAttr('hidden');

            if ($select.find('option[value!=""]').length === 0) {
                $select.empty().append('<option value="">Lade …</option>');
                hsChatFetchModels($widget, function (models) {
                    $select.empty();
                    $.each(models, function (_, m) {
                        var label = m.size ? m.name + ' \u2014 ' + m.size : m.name;
                        $select.append('<option value="' + hsEscape(m.name) + '">' + hsEscape(label) + '</option>');
                    });
                });
            }
        });

        // Repost cancel
        $(document).on('click', '.hs-chat-repost-cancel', function () {
            var $status = $(this).closest('.hs-chat-status');
            $status.find('.hs-chat-repost-controls').attr('hidden', '');
            $status.find('.hs-chat-repost-btn').removeAttr('hidden');
        });

        // Repost confirm: resend with chosen model
        $(document).on('click', '.hs-chat-repost-go', function () {
            var $go       = $(this);
            var $status   = $go.closest('.hs-chat-status');
            var $widget   = $go.closest('.hs-chat-widget');
            var $controls = $status.find('.hs-chat-repost-controls');
            var $btn      = $status.find('.hs-chat-repost-btn');
            var newModel  = $controls.find('.hs-chat-repost-select').val();
            var exchIdx   = parseInt($btn.data('exchange-idx'), 10);

            if (!newModel) return;

            $controls.attr('hidden', '');
            $btn.removeAttr('hidden');
            hsChatRepost($widget, exchIdx, newModel, $status);
        });

        // ── AI-Profil ─────────────────────────────────────────────
        $(document).on('click', '.hs-profile-btn', function () {
            var $btn = $(this);
            var ctx = {
                name:            $btn.data('name')           || '',
                beruf:           $btn.data('beruf')          || '',
                spitzname:       $btn.data('spitzname')      || '',
                geburtsort:      $btn.data('geburtsort')     || '',
                geburtsdatum:    $btn.data('geburtsdatum')   || '',
                geburtsland:     $btn.data('geburtsland')    || '',
                verstorben:      $btn.data('verstorben')     || '',
                dod:             $btn.data('dod')            || '',
                partei:          $btn.data('partei')         || '',
                aufgabe_partei:  $btn.data('aufgabe-partei') || '',
                parlament:       $btn.data('parlament')      || '',
                parlament_name:  $btn.data('parlament-name') || '',
                status_aktiv:    $btn.data('status-aktiv')   || '',
                straftat:        $btn.data('straftat')       || '',
                urteil:          $btn.data('urteil')         || '',
                aktenzeichen:    $btn.data('aktenzeichen')   || '',
                status_straftat: $btn.data('status-straftat')|| '',
                bemerkung:       $btn.data('bemerkung')      || ''
            };
            hsProfileOpen(ctx);
        });

        $(document).on('click', '.hs-profile-modal-close, .hs-profile-modal-overlay', function (e) {
            if (e.target === this) $('#hs-profile-modal').remove();
        });

        $(document).on('keydown.hsprofile', function (e) {
            if (e.key === 'Escape') $('#hs-profile-modal').remove();
        });

    });

    /* ── AI-Profil modal ────────────────────────────────────────── */

    function hsProfileOpen(ctx) {
        var config    = window.hsProfileConfig || {};
        var questions = config.questions || [];
        if (!questions.length) return;

        // Remove stale modal
        $('#hs-profile-modal').remove();

        var name  = ctx.name || '';
        var title = name ? '🧾 AI-Profil: ' + name : '🧾 AI-Profil';

        var $modal = $(
            '<div id="hs-profile-modal" class="hs-profile-modal" role="dialog" aria-modal="true">' +
              '<div class="hs-profile-modal-overlay"></div>' +
              '<div class="hs-profile-modal-box">' +
                '<div class="hs-profile-modal-header">' +
                  '<span class="hs-profile-modal-title">' + hsEscape(title) + '</span>' +
                  '<button type="button" class="hs-profile-modal-close" aria-label="Schließen">✕</button>' +
                '</div>' +
                '<div class="hs-profile-modal-body"></div>' +
              '</div>' +
            '</div>'
        );
        $('body').append($modal);

        var $body    = $modal.find('.hs-profile-modal-body');
        var provider = config.provider || 'ollama';
        var model    = config.model    || '';

        function replace(tpl) {
            return tpl
                .replace(/\{name\}/g,            ctx.name            || '')
                .replace(/\{beruf\}/g,            ctx.beruf           || '')
                .replace(/\{spitzname\}/g,        ctx.spitzname       || '')
                .replace(/\{geburtsort\}/g,       ctx.geburtsort      || '')
                .replace(/\{geburtsdatum\}/g,     ctx.geburtsdatum    || '')
                .replace(/\{geburtsland\}/g,      ctx.geburtsland     || '')
                .replace(/\{verstorben\}/g,       ctx.verstorben      || '')
                .replace(/\{dod\}/g,              ctx.dod             || '')
                .replace(/\{partei\}/g,           ctx.partei          || '')
                .replace(/\{aufgabe_partei\}/g,   ctx.aufgabe_partei  || '')
                .replace(/\{parlament\}/g,        ctx.parlament       || '')
                .replace(/\{parlament_name\}/g,   ctx.parlament_name  || '')
                .replace(/\{status_aktiv\}/g,     ctx.status_aktiv    || '')
                .replace(/\{straftat\}/g,         ctx.straftat        || '')
                .replace(/\{urteil\}/g,           ctx.urteil          || '')
                .replace(/\{aktenzeichen\}/g,     ctx.aktenzeichen    || '')
                .replace(/\{status_straftat\}/g,  ctx.status_straftat || '')
                .replace(/\{bemerkung\}/g,        ctx.bemerkung       || '');
        }

        function askNext(idx) {
            if (idx >= questions.length) {
                $body.append('<p class="hs-profile-done">✅ Fertig</p>');
                return;
            }

            var q = replace(questions[idx]);

            var $qa = $(
                '<div class="hs-profile-qa">' +
                  '<div class="hs-profile-question">' + hsEscape(q) + '</div>' +
                  '<div class="hs-profile-answer"><span class="hs-profile-loading">…</span></div>' +
                '</div>'
            );
            $body.append($qa);
            // Scroll to the new block
            $modal.find('.hs-profile-modal-box').scrollTop(99999);

            $.post(config.ajaxUrl, {
                action:   'hs_profile_ask',
                question: q,
                system:   config.systemPrompt || '',
                _nonce:   config.nonce
            })
            .done(function (res) {
                if (res.success && res.data && res.data.reply) {
                    var html = hsEscape(res.data.reply).replace(/\n/g, '<br>');
                    $qa.find('.hs-profile-answer').html(html);
                } else {
                    var msg = (res.data && res.data.message) ? res.data.message : 'Fehler.';
                    $qa.find('.hs-profile-answer').html('<em class="hs-profile-error">' + hsEscape(msg) + '</em>');
                }
            })
            .fail(function () {
                $qa.find('.hs-profile-answer').html('<em class="hs-profile-error">Verbindungsfehler.</em>');
            })
            .always(function () {
                $modal.find('.hs-profile-modal-box').scrollTop(99999);
                askNext(idx + 1);
            });
        }

        askNext(0);
    }

    function hsChatLoadModels($widget, onReady) {
        var $select   = $widget.find('.hs-chat-model-select');
        var current   = $widget.data('model') || $select.val();
        var nonce     = $widget.data('nonce');
        var ajaxUrl   = $widget.data('ajax');
        var customUrl = $widget.data('hs-chat-custom-url') || '';
        var openai    = $widget.data('openai') === '1' || $widget.data('openai') === 1;
        var claude    = $widget.data('claude')  === '1' || $widget.data('claude')  === 1;
        var gemini    = $widget.data('gemini')  === '1' || $widget.data('gemini')  === 1;

        $select.prop('disabled', true);

        var ollamaModels  = [];
        var openaiModels  = [];
        var claudeModels  = [];
        var geminiModels  = [];
        var pending = 1 + (openai ? 1 : 0) + (claude ? 1 : 0) + (gemini ? 1 : 0);

        function finish() {
            pending--;
            if (pending > 0) return;
            hsBuildModelsUI($widget, $select, current, ollamaModels, openaiModels, claudeModels, geminiModels);
            $select.prop('disabled', false);
            if (typeof onReady === 'function') onReady();
        }

        var ollamaPost = { action: 'hs_chat_models', _nonce: nonce };
        if (customUrl) ollamaPost.ollama_url = customUrl;
        $.post(ajaxUrl, ollamaPost, function (res) {
            if (res.success && res.data && res.data.models) ollamaModels = res.data.models;
        }).always(finish);

        if (openai) {
            $.post(ajaxUrl, { action: 'hs_chat_openai_models', _nonce: nonce }, function (res) {
                if (res.success && res.data && res.data.models) openaiModels = res.data.models;
            }).always(finish);
        }

        if (claude) {
            $.post(ajaxUrl, { action: 'hs_chat_claude_models', _nonce: nonce }, function (res) {
                if (res.success && res.data && res.data.models) claudeModels = res.data.models;
            }).always(finish);
        }

        if (gemini) {
            $.post(ajaxUrl, { action: 'hs_chat_gemini_models', _nonce: nonce }, function (res) {
                if (res.success && res.data && res.data.models) geminiModels = res.data.models;
            }).always(finish);
        }
    }

    function hsBuildModelsUI($widget, $select, current, ollamaModels, openaiModels, claudeModels, geminiModels) {
        claudeModels = claudeModels || [];
        geminiModels = geminiModels || [];
        var providerCount = (ollamaModels.length > 0 ? 1 : 0) + (openaiModels.length > 0 ? 1 : 0) + (claudeModels.length > 0 ? 1 : 0) + (geminiModels.length > 0 ? 1 : 0);
        var useGroups = providerCount > 1;
        var allModels = ollamaModels.concat(openaiModels).concat(claudeModels).concat(geminiModels);
        var modelActions = {};
        ollamaModels.forEach(function (m) { modelActions[m.name] = 'hs_chat'; });
        openaiModels.forEach(function (m) { modelActions[m.name] = 'hs_chat_openai'; });
        claudeModels.forEach(function (m) { modelActions[m.name] = 'hs_chat_claude'; });
        geminiModels.forEach(function (m) { modelActions[m.name] = 'hs_chat_gemini'; });
        $widget.data('hs-model-actions', modelActions);

        var foundCurrent = allModels.some(function (m) { return m.name === current; });
        $select.empty();
        if (!foundCurrent && current) {
            $select.append('<option value="' + hsEscape(current) + '" selected>' + hsEscape(current) + '</option>');
        }

        function buildOpts($container, models, provider) {
            $.each(models, function (_, m) {
                var label = hsEscape(provider) + ' - ' + hsEscape(m.name);
                if (m.size) label += ' &mdash; ' + hsEscape(m.size);
                var sel   = (m.name === current) ? ' selected' : '';
                $container.append('<option value="' + hsEscape(m.name) + '"' + sel + '>' + label + '</option>');
            });
        }

        if (useGroups) {
            if (ollamaModels.length) {
                var $og1 = $('<optgroup label="Ollama">');
                buildOpts($og1, ollamaModels, 'Ollama');
                $select.append($og1);
            }
            if (openaiModels.length) {
                var $og2 = $('<optgroup label="OpenAI">');
                buildOpts($og2, openaiModels, 'OpenAI');
                $select.append($og2);
            }
            if (claudeModels.length) {
                var $og3 = $('<optgroup label="Claude">');
                buildOpts($og3, claudeModels, 'Claude');
                $select.append($og3);
            }
            if (geminiModels.length) {
                var $og4 = $('<optgroup label="Gemini">');
                buildOpts($og4, geminiModels, 'Gemini');
                $select.append($og4);
            }
        } else {
            // Single provider — still prefix so the format is consistent.
            var singleProvider = ollamaModels.length ? 'Ollama'
                : openaiModels.length ? 'OpenAI'
                : claudeModels.length ? 'Claude'
                : 'Gemini';
            buildOpts($select, allModels, singleProvider);
        }
        $widget.data('model', $select.val());

        // Populate multi-model checkboxes
        var $multiWrap = $widget.find('.hs-chat-multi-models');
        if (!$multiWrap.length) return;
        $multiWrap.empty();
        var uid = $widget.attr('id') || '';

        function addCheckboxes(provider, models, groupClass) {
            if (!models.length) return;
            // Sort alphabetically by model name within the provider group.
            var sorted = models.slice().sort(function (a, b) {
                return a.name.localeCompare(b.name);
            });
            // Provider group header.
            $multiWrap.append(
                '<span class="hs-chat-multi-group-label' + (groupClass ? ' ' + groupClass : '') + '">' + hsEscape(provider) + '</span>'
            );
            $.each(sorted, function (_, m) {
                var cbId      = 'hsmulti-' + uid + '-' + m.name.replace(/[^a-z0-9]/gi, '_');
                var itemLabel = hsEscape(provider) + ' - ' + hsEscape(m.name);
                var extra     = m.size ? ' <span class="hs-chat-multi-size">' + hsEscape(m.size) + '</span>' : '';
                var action    = modelActions[m.name] || 'hs_chat';
                $multiWrap.append(
                    '<label class="hs-chat-multi-model-label" for="' + cbId + '">' +
                    '<input type="checkbox" class="hs-chat-multi-check" id="' + cbId +
                        '" value="' + hsEscape(m.name) + '" data-action="' + action + '">' +
                    ' ' + itemLabel + extra + '</label>'
                );
            });
        }

        addCheckboxes('Ollama', ollamaModels, '');
        addCheckboxes('OpenAI', openaiModels, 'hs-chat-multi-group-openai');
        addCheckboxes('Claude', claudeModels, 'hs-chat-multi-group-claude');
        addCheckboxes('Gemini', geminiModels, 'hs-chat-multi-group-gemini');
    }

    function hsChatGetAction($widget, model) {
        var map = $widget.data('hs-model-actions') || {};
        return map[model] || 'hs_chat';
    }

    function hsChatSend($widget) {
        var $input   = $widget.find('.hs-chat-input');
        var $msgs    = $widget.find('.hs-chat-messages');
        var $sendBtn = $widget.find('.hs-chat-send-btn');
        var message  = $input.val().trim();

        if (!message) return;
        if ($sendBtn.prop('disabled')) return;

        // Multi-model mode: collect checked models and dispatch separately
        if ($widget.data('hs-chat-multi')) {
            var multiModels = [];
            $widget.find('.hs-chat-multi-check:checked').each(function () {
                multiModels.push({
                    name:   $(this).val(),
                    action: $(this).data('action') || hsChatGetAction($widget, $(this).val())
                });
            });
            if (multiModels.length > 0) {
                hsChatSendMulti($widget, message, multiModels);
                return;
            }
        }

        var model       = $widget.find('.hs-chat-model-select').val() || $widget.data('model') || 'llama3.2';
        var $panel      = $widget.find('.hs-chat-settings-panel');
        var system      = ($panel.find('.hs-chat-settings-system').val() || '').trim() || $widget.data('system') || '';
        var temperature = parseFloat($panel.find('.hs-chat-settings-temp').val()) || 0.7;
        var customUrl   = $widget.data('hs-chat-custom-url') || '';
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
                action      : hsChatGetAction( $widget, model ),
                _nonce      : nonce,
                message     : message,
                model       : model,
                system      : system,
                temperature : temperature,
                ollama_url  : customUrl,
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
                    // exchangeIdx = position of this exchange in history (history not yet updated)
                    var exchangeIdx = history.length / 2;
                    // Status line: model · time · tok/s · repost button (not shown in dropdown variant)
                    var statusParts = [];
                    if (d.model)    statusParts.push(hsEscape(d.model));
                    if (d.time_s)   statusParts.push(d.time_s + 's');
                    if (d.toks_sec) statusParts.push(d.toks_sec + ' tok/s');
                    if (statusParts.length) {
                        var $status = $('<div class="hs-chat-status"></div>');
                        var statusHtml = statusParts.join('<span class="hs-chat-status-sep"> · </span>');
                        if (!$widget.hasClass('hs-chat-widget-dropdown')) {
                            statusHtml +=
                                '<span class="hs-chat-status-sep"> · </span>' +
                                '<button type="button" class="hs-chat-repost-btn" data-exchange-idx="' + exchangeIdx + '" title="Nochmal mit anderem Modell senden">\u21bb Repost</button>' +
                                '<span class="hs-chat-repost-controls" hidden>' +
                                '<select class="hs-chat-repost-select" aria-label="Modell f\u00fcr Repost"></select>' +
                                '<button type="button" class="hs-chat-repost-go" title="Senden">\u2713</button>' +
                                '<button type="button" class="hs-chat-repost-cancel" title="Abbrechen">\u2715</button>' +
                                '</span>';
                        }
                        $status.html(statusHtml);
                        $msgs.append($status);
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

                // Process queued messages (used by dropdown to send all fragen)
                var queue = $widget.data('hs-chat-queue') || [];
                if (queue.length) {
                    var nextMsg = queue.shift();
                    $widget.data('hs-chat-queue', queue);
                    $input.val(nextMsg).trigger('input');
                    setTimeout(function () { hsChatSend($widget); }, 50);
                }
            }
        });
    }

    function hsChatSendMulti($widget, message, models) {
        var $input      = $widget.find('.hs-chat-input');
        var $msgs       = $widget.find('.hs-chat-messages');
        var $sendBtn    = $widget.find('.hs-chat-send-btn');
        var $panel      = $widget.find('.hs-chat-settings-panel');
        var system      = $panel.find('.hs-chat-settings-system').val().trim() || $widget.data('system') || '';
        var temperature = parseFloat($panel.find('.hs-chat-settings-temp').val()) || 0.7;
        var customUrl   = $widget.data('hs-chat-custom-url') || '';
        var nonce       = $widget.data('nonce');
        var ajaxUrl     = $widget.data('ajax');
        var history     = $widget.data('hs-chat-history') || [];

        $msgs.find('.hs-chat-empty').remove();

        // User bubble
        $msgs.append('<div class="hs-chat-bubble hs-chat-bubble-user">' + hsEscape(message) + '</div>');
        $input.val('').css('height', 'auto');
        $sendBtn.prop('disabled', true);

        // Multi-column response group
        var $group = $('<div class="hs-chat-multi-group"></div>');
        $msgs.append($group);
        $msgs.scrollTop($msgs[0].scrollHeight);

        var pending     = models.length;
        var firstReply  = null; // used to update history once all are done

        $.each(models, function (_, m) {
            var model  = (typeof m === 'object') ? m.name   : m;
            var action = (typeof m === 'object') ? m.action : hsChatGetAction($widget, model);
            var $col = $('<div class="hs-chat-multi-col"></div>');
            $col.append('<div class="hs-chat-multi-label">' + hsEscape(model) + '</div>');
            var $typing = $('<div class="hs-chat-typing"><span></span><span></span><span></span></div>');
            $col.append($typing);
            $group.append($col);

            $.ajax({
                url  : ajaxUrl,
                type : 'POST',
                data : {
                    action      : action,
                    _nonce      : nonce,
                    message     : message,
                    model       : model,
                    system      : system,
                    temperature : temperature,
                    ollama_url  : customUrl,
                    history     : JSON.stringify(history)
                },
                success: function (res) {
                    $typing.remove();
                    if (res.success && res.data && res.data.reply) {
                        var d = res.data;
                        if (firstReply === null) firstReply = d.reply;
                        $col.append('<div class="hs-chat-bubble hs-chat-bubble-assistant">' + hsEscape(d.reply) + '</div>');
                        var sParts = [];
                        if (d.time_s)   sParts.push(d.time_s + 's');
                        if (d.toks_sec) sParts.push(d.toks_sec + ' tok/s');
                        if (sParts.length) {
                            $col.append(
                                '<div class="hs-chat-status">' +
                                sParts.join('<span class="hs-chat-status-sep"> \u00b7 </span>') +
                                '</div>'
                            );
                        }
                    } else {
                        var errMsg = (res.data && res.data.message) ? res.data.message : 'Fehler.';
                        $col.append('<div class="hs-chat-bubble hs-chat-bubble-error">&#9888; ' + hsEscape(errMsg) + '</div>');
                    }
                },
                error: function () {
                    $typing.remove();
                    $col.append('<div class="hs-chat-bubble hs-chat-bubble-error">&#9888; Verbindungsfehler.</div>');
                },
                complete: function () {
                    pending--;
                    if (pending === 0) {
                        $sendBtn.prop('disabled', false);
                        $input.focus();
                        $msgs.scrollTop($msgs[0].scrollHeight);
                        // Update shared history with first successful reply for context continuity
                        if (firstReply !== null) {
                            history.push({ role: 'user',      content: message    });
                            history.push({ role: 'assistant', content: firstReply });
                            $widget.data('hs-chat-history', history);
                        }
                    }
                }
            });
        });
    }

    function hsChatFetchModels($widget, callback) {
        var nonce     = $widget.data('nonce');
        var ajaxUrl   = $widget.data('ajax');
        var customUrl = $widget.data('hs-chat-custom-url') || '';
        var openai    = $widget.data('openai') === '1' || $widget.data('openai') === 1;
        var claude    = $widget.data('claude')  === '1' || $widget.data('claude')  === 1;
        var gemini    = $widget.data('gemini')  === '1' || $widget.data('gemini')  === 1;
        var postData  = { action: 'hs_chat_models', _nonce: nonce };
        if (customUrl) postData.ollama_url = customUrl;

        var ollama  = [];
        var oai     = [];
        var ant     = [];
        var gem     = [];
        var pending = 1 + (openai ? 1 : 0) + (claude ? 1 : 0) + (gemini ? 1 : 0);

        function done() {
            pending--;
            if (pending === 0) callback(ollama.concat(oai).concat(ant).concat(gem));
        }

        $.post(ajaxUrl, postData, function (res) {
            if (res.success && res.data && res.data.models) ollama = res.data.models;
        }).always(done);

        if (openai) {
            $.post(ajaxUrl, { action: 'hs_chat_openai_models', _nonce: nonce }, function (res) {
                if (res.success && res.data && res.data.models) oai = res.data.models;
            }).always(done);
        }

        if (claude) {
            $.post(ajaxUrl, { action: 'hs_chat_claude_models', _nonce: nonce }, function (res) {
                if (res.success && res.data && res.data.models) ant = res.data.models;
            }).always(done);
        }

        if (gemini) {
            $.post(ajaxUrl, { action: 'hs_chat_gemini_models', _nonce: nonce }, function (res) {
                if (res.success && res.data && res.data.models) gem = res.data.models;
            }).always(done);
        }
    }

    function hsChatRepost($widget, exchangeIdx, model, $afterStatus) {
        var history    = $widget.data('hs-chat-history') || [];
        var userEntry  = history[exchangeIdx * 2];
        if (!userEntry || !userEntry.content) return;

        var userMsg    = userEntry.content;
        var histBefore = history.slice(0, exchangeIdx * 2);
        var $msgs      = $widget.find('.hs-chat-messages');
        var $panel     = $widget.find('.hs-chat-settings-panel');
        var system      = $panel.find('.hs-chat-settings-system').val().trim() || $widget.data('system') || '';
        var temperature = parseFloat($panel.find('.hs-chat-settings-temp').val()) || 0.7;
        var customUrl   = $widget.data('hs-chat-custom-url') || '';
        var nonce       = $widget.data('nonce');
        var ajaxUrl     = $widget.data('ajax');

        var $typing = $('<div class="hs-chat-typing"><span></span><span></span><span></span></div>');
        $afterStatus.after($typing);
        $msgs.scrollTop($msgs[0].scrollHeight);

        $.ajax({
            url  : ajaxUrl,
            type : 'POST',
            data : {
                action      : hsChatGetAction( $widget, model ),
                _nonce      : nonce,
                message     : userMsg,
                model       : model,
                system      : system,
                temperature : temperature,
                ollama_url  : customUrl,
                history     : JSON.stringify(histBefore)
            },
            success: function (res) {
                $typing.remove();
                if (res.success && res.data && res.data.reply) {
                    var d      = res.data;
                    var $bubble = $('<div class="hs-chat-bubble hs-chat-bubble-assistant hs-chat-bubble-repost"></div>');
                    $bubble.text(d.reply);
                    $afterStatus.after($bubble);

                    var rParts = [];
                    if (d.model)    rParts.push(hsEscape(d.model));
                    if (d.time_s)   rParts.push(d.time_s + 's');
                    if (d.toks_sec) rParts.push(d.toks_sec + ' tok/s');
                    if (rParts.length) {
                        var $rStatus = $('<div class="hs-chat-status hs-chat-status-repost"></div>');
                        $rStatus.html(rParts.join('<span class="hs-chat-status-sep"> \u00b7 </span>'));
                        $bubble.after($rStatus);
                    }
                } else {
                    var errMsg = (res.data && res.data.message) ? res.data.message : 'Unbekannter Fehler.';
                    var $err = $('<div class="hs-chat-bubble hs-chat-bubble-error"></div>');
                    $err.text('\u26a0 ' + errMsg);
                    $afterStatus.after($err);
                }
            },
            error: function () {
                $typing.remove();
                var $err = $('<div class="hs-chat-bubble hs-chat-bubble-error">&#9888; Verbindungsfehler. Ist Ollama aktiv?</div>');
                $afterStatus.after($err);
            },
            complete: function () {
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
