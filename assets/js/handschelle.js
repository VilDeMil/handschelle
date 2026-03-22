/**
 * Die Handschelle V.Alpha-2 — Frontend & Admin JavaScript
 * Author: Bernd K.R. Dorfmüller
 */
(function ($) {
    'use strict';

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
                $el.prop('disabled', false).removeClass('hs-field-locked');
            } else if ($el.is('input[type="checkbox"]')) {
                $el.prop('disabled', false).removeClass('hs-field-locked');
            } else {
                $el.prop('readonly', false).removeClass('hs-field-locked');
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
        }

        function hsSmartClear($form) {
            var allFields = SMART_PERSONAL_FIELDS
                .concat(SMART_POLITIK_FIELDS)
                .concat(SMART_SOCIAL_FIELDS)
                .concat(SMART_EMAIL_FIELDS)
                .concat(['geburtsland','dod']);

            allFields.forEach(function(f) { hsSmartUnlockField($form, f); });
            $form.find('[name="verstorben"]').prop('disabled', false).removeClass('hs-field-locked');
            $form.find('#hs-smart-entry-id').val('');
            $form.find('.hs-smart-image-section').show();
            $form.find('.hs-smart-image-preview').hide().find('.hs-smart-image-preview-inner').empty();
            $form.find('.hs-smart-search-links').hide().find('.hs-search-buttons').empty();
            $form.find('.hs-smart-person-locked').hide();
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

    });

})(jQuery);
