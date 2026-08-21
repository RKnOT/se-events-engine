jQuery(document).on('click', '.se-quick-edit', function(e) {
    console.log('CLICK erkannt');
});




jQuery(function ($) {

    $(document.body).on('click', '.se-quick-edit', function(e) {
        e.preventDefault();

        const id = $(this).data('id'); // ✅ ZUERST definieren

        console.log('ID:', id);

        const row = $('#post-' + id);
        const inline = $('#inline-edit');
        const hidden = row.find('#inline_' + id);

        console.log({
            id: id,
            row: row.length,
            inline: inline.length,
            hidden: hidden.length
        });

    });

});