
jQuery(function($){



    let recurrenceSaving = false;
    const debug = $('#se-debug-console');
    $('#se-debug-console').show();

  
    function log(msg){

        if(typeof msg === 'object'){
            debug.append(JSON.stringify(msg, null, 2) + "\n");
        }
        else{
            debug.append(msg + "\n");
        }

        console.log(msg);
    }

    /*--------------------------------*/
    document.querySelectorAll(".copy-template").forEach(cb => {

        cb.addEventListener("change", function () {

            const row = this.dataset.row;

            const templateStart = document.querySelector(".template-start[data-row='" + row + "']");
            const templateEnd   = document.querySelector(".template-end[data-row='" + row + "']");

            // Nur kopieren, wenn beide Datumsfelder gefüllt sind
        
            if (templateStart.valueAsDate && templateEnd.valueAsDate) {


                document.getElementById("exclude_start_" + row).value = templateStart.value;
                document.getElementById("exclude_end_" + row).value   = templateEnd.value;

            } else {

                alert("Bitte zuerst ein gültiges Start- und Enddatum in der Vorlage eingeben.");

            }

            // Checkbox immer wieder zurücksetzen
            this.checked = false;

        });

    });

    document
    .getElementById("saveHolidayTemplates")
    .addEventListener("click", function () {

        const templates = [];

        for(let i=0;i<4;i++){

            templates.push({
                start: document.querySelector(".template-start[data-row='"+i+"']").value,
                end:   document.querySelector(".template-end[data-row='"+i+"']").value
            });

        }

        jQuery.post(ajaxurl, {

            action: "save_holiday_templates",
            templates: templates

        }, function(response){

            alert("Vorlagen gespeichert.");

        });

    });

    function syncDateRange(startInput, endInput) {

        startInput.addEventListener("change", function () {

            if (
                !endInput.value ||
                endInput.value < startInput.value
            ) {
                endInput.value = startInput.value;
            }

        });

        endInput.addEventListener("change", function () {

            if (
                !startInput.value ||
                startInput.value > endInput.value
            ) {
                startInput.value = endInput.value;
            }

        });

    }

    // Ferienzeiten (links)
    for (let i = 0; i < 4; i++) {

        syncDateRange(
            document.getElementById("exclude_start_" + i),
            document.getElementById("exclude_end_" + i)
        );

    }

    // Vorlagen (rechts)
    for (let i = 0; i < 4; i++) {

        syncDateRange(
            document.querySelector(".template-start[data-row='" + i + "']"),
            document.querySelector(".template-end[data-row='" + i + "']")
        );

    }


    /*----------------------------------*/




    if (SE_REC_UI.exclude_ranges) {


        //log(SE_REC_UI.exclude_ranges);
        //log(typeof SE_REC_UI.exclude_ranges);
        SE_REC_UI.exclude_ranges.forEach(function(range, i){

            $('#exclude_enabled_' + i)
            .prop(
                'checked',
                range.enabled === true ||
                range.enabled === 'true'
            );
               

            $('#exclude_start_' + i)
                .val(range.start);

            $('#exclude_end_' + i)
                .val(range.end);

        });

    }
    //log('SE Recurrence UI loaded');
    $('#saveRecurrence').on('click', function(){
        // =====================================
        // 🔹 Bereits aktiv
        // =====================================
        if(recurrenceSaving){
            return;
        }
        const $btn = $(this);
        // =====================================
        // 🔹 Aktuelle UI Werte
        // =====================================
        const recurrenceType =
            $('input[name="recurrence_type"]:checked').val();
        const currentPayload = {
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            start_time: $('#start_time').val(),
            end_time: $('#end_time').val(),
            interval: $('#interval').val(),
            recurrence_type: recurrenceType,
            days: $('input[name="days[]"]:checked')
                .map(function(){
                    return $(this).val();
                })
                .get()
                .join(',')
        };
        // =====================================
        // 🔹 DB Werte
        // 🔥 Diese Werte vorher via wp_localize_script
        // 🔥 an JS übergeben
        // von  public function assets / wp_localize_script
        // =====================================
        const originalPayload = {
            start_date: SE_REC_UI.start_date,
            end_date: SE_REC_UI.end_date,
            start_time: SE_REC_UI.start_time,
            end_time: SE_REC_UI.end_time,
            interval: SE_REC_UI.interval,
            recurrence_type: SE_REC_UI.recurrence_type,
            days: SE_REC_UI.days
        };
        /*
            log('test db- parameter');
                    log(
                JSON.stringify(
                    originalPayload,
                    null,
                    2
                )
            );
            log('test ui- parameter');
                    log(
                JSON.stringify(
                    currentPayload,
                    null,
                    2
                )
            );
            log('test parameter ende');
       */

        //----------------------------------//
        let changed = false;
        // =====================================
        // 🔹 Basisfelder
        // =====================================
        const fields = [
            'start_date',
            'end_date',
            'start_time',
            'end_time',
            'interval',
            'recurrence_type'
        ];
         // =====================================
        // 🔹 Änderung prüfen
        // =====================================
        fields.forEach(function(field){
            const current = String(currentPayload[field] || '');
            const original = String(originalPayload[field] || '');
            if(current !== original){
                changed = true;
                /*
                 log(
                    'Changed field: '
                    + field
                    + ' | UI=' + current
                    + ' | DB=' + original
                );
                */
            }
        });
        // nur bei weekly -> days abfragen
        if (originalPayload['recurrence_type'] == 'weekly'){
            if ( originalPayload['days'] !== currentPayload['days']){
                 changed = true;
            }
        }

        //Ferien daten vergleich db <-> ui
        const exeptions_ui = getUiExcludeRanges()
        const exeptions_db = getExcludeRangesDb() 
        
        //log(JSON.stringify(exeptions_ui))
        //log(JSON.stringify( exeptions_ui))

        if (JSON.stringify(exeptions_ui) !== JSON.stringify(exeptions_db)) {
            log('ungleich')
            changed = true;
        }
       

        // =====================================
        // 🔹 Keine Änderung
        // =====================================
        if(!changed){
            alert(
                'Keine Aktion erforderlich. Datenbank und Eingabemaske stimmen überein'
            );
            return;
        }
        // =====================================
        // 🔹 Sicherheitsabfrage
        // =====================================
       const confirmed = confirm(
            'Recurring Parent Event wirklich speichern und neue Instanzen erzeugen?'
        );
        if(!confirmed){
            return;
        }



        // =====================================
        // 🔹 Exclude Ranges sammeln
        // =====================================
        let exclude_ranges = getUiExcludeRanges()
        
        // =====================================
        // 🔹 AJAX Payload
        // =====================================

        // TinyMCE -> textarea synchronisieren
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }
        const eventPostData = {
            event_name: $('input[name="event_name"]').val() || '',
            post_content: $('#post_content').val() || '',
            post_status: $('select[name="post_status"]').val() || 'draft',
            location_id: $('select[name="location_id"]').val() || 0
        };

        const payload = {
            action: 'se_save_rpe',
            nonce: SE_REC_UI.nonce,
            event_id: SE_REC_UI.event_id,
            start_date: currentPayload.start_date,
            end_date: currentPayload.end_date,
            start_time: currentPayload.start_time,
            end_time: currentPayload.end_time,
            interval: currentPayload.interval,
            recurrence_type: currentPayload.recurrence_type,
            days: currentPayload.days.split(','),
            exclude_ranges: exclude_ranges,
            event_post_data: JSON.stringify(eventPostData)
        };
        //log('Saving recurrence');
        log('Payload:');
        //log(JSON.stringify(payload, null, 2));
        $.ajax({
            url: SE_REC_UI.ajaxurl,
            type: 'POST',
            data: payload,
            success: function(response){
                log('saved');
                //log(JSON.stringify(response, null, 2));
                debug.text(
                    JSON.stringify(response, null, 2)
                );     
                if(
                    response.success && response.data.status === 'Änderung vorgenommen'
                ){
                recurrenceSaving = true;
                $btn.prop('disabled', true);
                $btn.addClass('se-saving');
                $btn.text('Event Saved');
                renderGeneratedEvents(
                        response.data.generated
                    );
                window.location.href = response.data.generated.url;
                } else {
                    $btn.text('Save Event');
                    renderGeneratedEvents(
                        response.data.generated
                    );
                }
               
            },
            error: function(xhr){
                log('AJAX ERROR');
                log(xhr.responseText);
            }
        });
    });


    function getExcludeRangesDb(){

        return SE_REC_UI.exclude_ranges.map(function(range){

            return {

                enabled:
                    range.enabled === true || range.enabled === 'true',

                start:
                    range.start || '',

                end:
                    range.end || ''

            };

        });

    }
    function getUiExcludeRanges(){

        let exclude_ranges = [];

        for(let i = 0; i < 4; i++){

            exclude_ranges.push({
                enabled:
                    $('#exclude_enabled_' + i)
                        .is(':checked'),
                start:
                    $('#exclude_start_' + i)
                        .val(),
                end:
                    $('#exclude_end_' + i)
                        .val()
            });

        }

        return exclude_ranges;

    }

    function toggleWeeklyDays() { 
        const type =
            $('input[name="recurrence_type"]:checked').val();
        if(type === 'weekly') {
            $('#weekly-days-wrap').show();
        } else {
            $('#weekly-days-wrap').hide();
        }
    }
    toggleWeeklyDays();

    $('input[name="recurrence_type"]')
    .on('change', toggleWeeklyDays);

    function renderGeneratedEvents(data){
        let html = '';
        /*
        =====================
        RPE
        =====================
        */
        if(data.rpe){
            html += `
                <h4>RPE Event</h4>
                <ul class="se-event-list">
                    <li>
                        <strong>ID:</strong>
                        ${data.rpe[0].event_id}
                        |
                        <strong>Post:</strong>
                        ${data.rpe[0].post_id}
                        |
                        <strong>Date:</strong>
                        ${data.rpe[0].event_start_date}
                    </li>
                </ul>
            `;
             
        }
        /*
        =====================
        RCE
        =====================
        
        if(data.rce && data.rce.length){
            html += `
                <h4> RCE Events</h4>
                <ul class="se-event-list">
            `;
            data.rce.forEach(function(event){
                const editLink =
                    SE_REC_UI.adminurl +
                    'admin.php?page=se-events&event_action=edit&post_id=' +
                    event.post_id;
                html += `
                    <li>
                        <strong>ID:</strong>
                        ${event.event_id}
                        |
                        <strong>Post:</strong>
                        ${event.post_id}
                        |
                        <strong>Date:</strong>
                        ${event.event_start_date}
                        |
                        <strong>url</strong>
                           <a href="${editLink}">
                            Open
                        </a>
                    </li>
                `;
            });
            html += '</ul>';
        }
        $('#se-generated-events')
            .html(html);

        */
    }


     for(let i = 0; i < 4; i++){

        const $start = $('#exclude_start_' + i);
        const $end   = $('#exclude_end_' + i);

        // START ändert sich
        $start.on('change', function(){

            const startVal = $start.val();
            const endVal   = $end.val();

            if(!startVal){
                return;
            }

            if(!endVal){

                let d = new Date(startVal);
                d.setDate(d.getDate());

                $end.val(
                    d.toISOString().split('T')[0]
                );

                return;
            }

            const startDate = new Date(startVal);
            const endDate   = new Date(endVal);

            if(startDate > endDate){

                let d = new Date(startDate);
                d.setDate(d.getDate());

                $end.val(
                    d.toISOString().split('T')[0]
                );
            }

        });

        // ENDE ändert sich
        $end.on('change', function(){

            const startVal = $start.val();
            const endVal   = $end.val();

            if(!endVal){
                return;
            }

            if(!startVal){

                let d = new Date(endVal);
                d.setDate(d.getDate());

                $start.val(
                    d.toISOString().split('T')[0]
                );

                return;
            }

            const startDate = new Date(startVal);
            const endDate   = new Date(endVal);

            if(endDate < startDate){

                let d = new Date(endDate);
                d.setDate(d.getDate());

                $start.val(
                    d.toISOString().split('T')[0]
                );
            }

        });

    }

    const $startDate = $('#start_date');
    const $endDate   = $('#end_date');

    $startDate.on('change', function(){
        const startVal = $startDate.val();
        const endVal   = $endDate.val();
        if(!startVal){
            return;
        }
        // wenn Enddatum leer
        if(!endVal){
            const nextDay = new Date(startVal);
            nextDay.setDate(
                nextDay.getDate()
            );
            $endDate.val(
                nextDay.toISOString()
                    .split('T')[0]
            );
            return;
        }
        const startDate = new Date(startVal);
        const endDate   = new Date(endVal);
        // wenn Enddatum früher/gleich Startdatum
        if(endDate <= startDate){
            startDate.setDate(
                startDate.getDate()
            );
            $endDate.val(
                startDate.toISOString()
                    .split('T')[0]
            );
        }
    });

    $endDate.on('change', function(){

        const startVal = $startDate.val();
        const endVal   = $endDate.val();

        if(!endVal){
            return;
        }

        // wenn Startdatum leer
        if(!startVal){

            const prevDay = new Date(endVal);

            prevDay.setDate(
                prevDay.getDate()
            );

            $startDate.val(
                prevDay.toISOString()
                    .split('T')[0]
            );

            return;
        }

        const startDate = new Date(startVal);
        const endDate   = new Date(endVal);

        // wenn Enddatum vor/gleich Startdatum
        if(endDate <= startDate){

            endDate.setDate(
                endDate.getDate()
            );

            $startDate.val(
                endDate.toISOString()
                    .split('T')[0]
            );
        }
    });

    const $startTime = $('#start_time');
    const $endTime   = $('#end_time');

    function addHours(time, hours){
        const parts = time.split(':');
        let h = parseInt(parts[0], 10);
        let m = parseInt(parts[1], 10);
        h += hours;
        if(h >= 24){
            h -= 24;
        }
        if(h < 0){
            h += 24;
        }
        return String(h).padStart(2, '0')
            + ':'
            + String(m).padStart(2, '0');
    }
    /* =========================
    START TIME CHANGE
    ========================= */
    $startTime.on('change', function(){
        const startVal = $startTime.val();
        const endVal   = $endTime.val();
        if(!startVal){
            return;
        }
        // wenn Endzeit leer
        if(!endVal){
            $endTime.val(
                addHours(startVal, 1)
            );
            return;
        }
        if(startVal >= endVal){
            $endTime.val(
                addHours(startVal, 1)
            );
        }
    });
    /* =========================
    END TIME CHANGE
    ========================= */
    $endTime.on('change', function(){
        const startVal = $startTime.val();
        const endVal   = $endTime.val();
        if(!endVal){
            return;
        }
        // wenn Startzeit leer
        if(!startVal){
            $startTime.val(
                addHours(endVal, -1)
            );
            return;
        }
        if(endVal <= startVal){
            $startTime.val(
                addHours(endVal, -1)
            );
        }
    });
});


document.addEventListener('DOMContentLoaded', function () {
        // =========================================
        // 🔹 Save Button suchen
        // =========================================
        const saveButton = document.querySelector(
            '#publish'
        );
        if (!saveButton) {
            return;
        }
        // =========================================
        // 🔹 Formular suchen
        // =========================================
        const form = document.querySelector('#post');
        if (!form) {
            return;
        }
        // =========================================
        // 🔹 Submit abfangen
        // =========================================

        form.addEventListener('submit', function (event) {
            // =====================================
            // 🔹 Nur bei RPE
            // =====================================
            const eventType = document.querySelector(
                'input[name="se_event_type"]'
            );
            if (!eventType) {
                return;
            }
            if (eventType.value !== 'rpe') {
                return;
            }
        });
    });

