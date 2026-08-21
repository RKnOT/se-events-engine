// jQuery(function ($) {
//    console.log('SE Events Admin geladen1');
// });


function timeToMinutes(time) {
    if (!time) return null;
    const [h, m] = time.split(':').map(Number);
    return h * 60 + m;
}

function minutesToTime(min) {
    const h = Math.floor(min / 60);
    const m = min % 60;
    return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
}



jQuery(function ($) {
  
    //console.log('SE Events Admin geladen');

    // =========================
    // QUICK EDIT CLICK
    // =========================
    $(document.body).on('click', '.se-quick-edit', function(e) {
        e.preventDefault();

        const id  = $(this).data('id');
        const row = $('#post-' + id);

        if (!row.length) {
            console.error('Row nicht gefunden!', id);
            return;
        }

        // Inline Template prüfen
        const template = $('#inline-edit');
        if (!template.length) {
            console.error('#inline-edit fehlt im DOM!');
            return;
        }

        // Alte schließen
        $('.inline-editor').remove();
        template.hide();

        // Template klonen
        const editRow = template.clone(true);
        editRow.removeAttr('id');
        editRow.addClass('inline-editor');
        editRow.show();

        // colspan fix
        const colCount = row.find('td').length;
        editRow.find('td').attr('colspan', colCount);

        // Hidden Daten
        const hidden = row.find('#inline_' + id);

        if (!hidden.length) {
            console.error('Hidden Daten fehlen!', id);
            return;
        }

        // Werte holen
        const title = hidden.find('.post_title').text();
        const status = hidden.find('.post_status').text();
        const author = hidden.find('.post_author').text();

        const type  = hidden.find('.se_event_type').text();
        
        const date  = hidden.find('.se_event_date').text();
        const start = hidden.find('.se_start_time').text().substring(0,5);
        const end   = hidden.find('.se_end_time').text().substring(0,5);
 
        const cbType = hidden.find('.cb_event_type').text();
        const authorId   = hidden.find('.post_author').text();
        const authorName = hidden.find('.post_author_name').text();






        console.log(authorId, authorName);

        console.log({ title, type, date, start, end });
        console.log('SEND TITLE:', editRow.find('[name="post_title"]').val());
          
        console.log('CB TYPE:', cbType);
        // Felder setzen
        editRow.find('[name="post_title"]').val(title);
        editRow.find('[name="_status"]').val(status);
        editRow.find('[name="post_author"]').val(authorName);
        editRow.find('[name="post_status"]').val(cbType);
        editRow.find('[name="se_event_type"]').val(type);

        editRow.find('[name="se_event_date"]').val(date);
        editRow.find('[name="se_start_time"]').val(start);
        editRow.find('[name="se_end_time"]').val(end);


        // Einfügen
        row.after(editRow);
    });

    // =========================
    // CANCEL
    // =========================
    $(document.body).on('click', '.cancel', function() {
        $('.inline-editor').remove();
    });

    // =========================
    // SAVE
    // =========================

    
    $(document.body).on('click', '.save', function() {

        const editRow = $(this).closest('tr');
        const prevRow = editRow.prev();

        if (!prevRow.length) {
            console.error('Keine vorherige Row gefunden!');
            return;
        }

        const id = prevRow.attr('id').replace('post-', '');

        const data = {
            action: 'se_quick_edit_save',
            post_id: id,
            post_title: editRow.find('[name="post_title"]').val(),
            post_status: editRow.find('[name="post_status"]').val(),
            cb_event_type: editRow.find('[name="cb_event_type"]').val(),
            se_event_type: editRow.find('[name="se_event_type"]').val(),
            se_event_date: editRow.find('[name="se_event_date"]').val(),
            se_start_time: editRow.find('[name="se_start_time"]').val(),
            se_end_time: editRow.find('[name="se_end_time"]').val(),
        };
        

        console.log('SAVE DATA', data);

        $.post(ajaxurl, data)
            .done(function(response) {
                console.log('Saved', response);
                location.reload();
            })
            .fail(function(err) {
                console.error('Save Fehler', err);
                console.error('RAW RESPONSE:', err.responseText);
            });
             
    });


    $(document).on('change', '[name="se_start_time"]', function() {

        const row = $(this).closest('tr');

        const startVal = row.find('[name="se_start_time"]').val();
        const endInput = row.find('[name="se_end_time"]');
        const endVal = endInput.val();

        const startMin = timeToMinutes(startVal);
        const endMin   = timeToMinutes(endVal);

        if (startMin === null) return;

        // 👉 Endzeit fehlt oder liegt davor → korrigieren
        if (endMin === null || endMin <= startMin) {

            let newEnd = startMin + 60; // +1h

            // Max 23:59
            if (newEnd > 23 * 60 + 59) {
                newEnd = 23 * 60 + 59;
            }

            endInput.val(minutesToTime(newEnd));
        }
    });

    $(document).on('change', '[name="se_end_time"]', function() {

        const row = $(this).closest('tr');

        const startVal = row.find('[name="se_start_time"]').val();
        const endVal   = $(this).val();

        const startMin = timeToMinutes(startVal);
        const endMin   = timeToMinutes(endVal);

        if (startMin === null || endMin === null) return;

        if (endMin <= startMin) {

            // 👉 automatisch korrigieren statt nur alert
            let newEnd = startMin + 60;

            if (newEnd > 23 * 60 + 59) {
                newEnd = 23 * 60 + 59;
            }

            $(this).val(minutesToTime(newEnd));

            // optional:
            // alert('Endzeit wurde angepasst');
        }
    });


});



        