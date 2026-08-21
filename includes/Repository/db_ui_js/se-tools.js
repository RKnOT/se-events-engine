jQuery(function($){

    function run(dry) {
        $('#output').text('Lade...');

        $.post(SE_TOOLS.ajax_url, {
            action: 'se_rename_event_type',
            dry_run: dry ? 1 : 0
        })
        .done(res => $('#output').text(res.data))
        .fail(err => $('#output').text('Fehler:\n' + err.responseText));
    }

    $('#dry').click(() => run(true));
    $('#run').click(() => {
        if (confirm('Wirklich ausführen?')) run(false);
    });
});