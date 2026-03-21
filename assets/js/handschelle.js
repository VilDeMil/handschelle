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

        // ── 14. Smart-Formular: Name-Dropdown → Personendaten auto-ausfüllen ──
        $('#hs-smart-name-select').on('change', function () {
            var name  = $(this).val();
            var $form = $('#hs-smart-form');

            if (!name) {
                // Neue Person: Namensfeld zeigen + alle Personenfelder leeren & editierbar
                $('#hs-smart-entry-id').val('0');
                $('#hs-smart-new-name-row').show();
                $('#hs-smart-name-input').val('').removeAttr('readonly').css('background', '');
                $form.find('[data-field]').each(function () {
                    var $el = $(this);
                    if ($el.is('select')) {
                        $el.removeClass('hs-smart-locked');
                    } else if ($el.is('input[type="checkbox"]')) {
                        $el.prop('checked', false).removeClass('hs-smart-locked').css('pointer-events', '');
                    } else {
                        $el.val('').removeAttr('readonly').css('background', '');
                    }
                });
                // Geburtsland zurück auf Deutschland
                $form.find('[data-field="geburtsland"]').val('Deutschland');
                // Status zurück auf Aktiv
                $form.find('[data-field="status_aktiv"]').val('1');
                // DoD-Zeile ausblenden
                $form.find('.hs-dod-row').hide().find('[data-field="dod"]').val('');
                // Bildvorschau leeren + Upload wieder anzeigen
                $('#hs-smart-bild-preview').empty();
                $('#hs-smart-bild-upload').show().closest('.hs-field').find('small').show();
                return;
            }

            // Existierende Person: Daten per AJAX laden
            $.post(
                (typeof handschelle_ajax !== 'undefined' ? handschelle_ajax.ajax_url : '/wp-admin/admin-ajax.php'),
                {
                    action: 'hs_get_person_data',
                    nonce:  (typeof handschelle_ajax !== 'undefined' ? handschelle_ajax.person_nonce : ''),
                    name:   name
                },
                function (response) {
                    if (!response || !response.success) return;
                    var d = response.data;

                    // Entry-ID setzen + Namensfeld sperren + befüllen
                    $('#hs-smart-entry-id').val(d.entry_id || 0);
                    $('#hs-smart-new-name-row').hide();
                    $('#hs-smart-name-input').val(name).attr('readonly', 'readonly').css('background', '#f8f8f8');

                    // Hilfsfunktion: Feld befüllen + sperren
                    function fill(fieldName, value) {
                        var $el = $form.find('[data-field="' + fieldName + '"]');
                        if (!$el.length) return;
                        if ($el.is('select')) {
                            $el.val(value).addClass('hs-smart-locked');
                        } else if ($el.is('input[type="checkbox"]')) {
                            $el.prop('checked', value == 1).addClass('hs-smart-locked').css('pointer-events', 'none');
                        } else {
                            $el.val(value || '').attr('readonly', 'readonly').css('background', '#f8f8f8');
                        }
                    }

                    fill('beruf',              d.beruf);
                    fill('spitzname',          d.spitzname);
                    fill('geburtsort',         d.geburtsort);
                    fill('geburtsland',        d.geburtsland);
                    fill('geburtsdatum',       d.geburtsdatum);
                    fill('private_email',      d.private_email);
                    fill('oeffentliche_email', d.oeffentliche_email);
                    fill('bemerkung_person',   d.bemerkung_person);
                    fill('partei',             d.partei);
                    fill('aufgabe_partei',     d.aufgabe_partei);
                    fill('parlament',          d.parlament);
                    fill('parlament_name',     d.parlament_name);
                    fill('status_aktiv',       d.status_aktiv);

                    // Verstorben + DoD
                    fill('verstorben', d.verstorben);
                    if (d.verstorben == 1) {
                        $form.find('.hs-dod-row').show();
                        fill('dod', d.dod);
                    } else {
                        $form.find('.hs-dod-row').hide();
                        $form.find('[data-field="dod"]').val('');
                    }

                    // Bildvorschau (aus DB) + Upload-Feld sperren
                    $('#hs-smart-bild-upload').hide().closest('.hs-field').find('small').hide();
                    if (d.bild_url) {
                        $('#hs-smart-bild-preview').html(
                            '<img src="' + $('<div>').text(d.bild_url).html() + '" alt="Bild" style="max-height:100px;border-radius:4px;display:block;margin-bottom:.4rem;">' +
                            '<small style="color:#7f8c8d;">Bild aus Datenbank übernommen</small>'
                        );
                    } else {
                        $('#hs-smart-bild-preview').html('<small style="color:#7f8c8d;">Kein Bild vorhanden</small>');
                    }
                }
            );
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

    });

})(jQuery);
