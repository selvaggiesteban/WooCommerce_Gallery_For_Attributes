(function ($) {
    'use strict';

    function initFrame($field) {
        $field.on('click', '.wc-gfa-add', function (e) {
            e.preventDefault();

            var $wrap = $(this).closest('.wc-gfa-field');
            var frame = wp.media({
                title: wcGfaAdmin.i18n.title,
                button: { text: wcGfaAdmin.i18n.button },
                multiple: 'add',
                library: { type: 'image' }
            });

            frame.on('select', function () {
                var selection = frame.state().get('selection');
                var $thumbs = $wrap.find('.wc-gfa-thumbs');
                var $input = $wrap.find('.wc-gfa-gallery-input');
                var current = $input.val() ? $input.val().split(',') : [];

                selection.each(function (att) {
                    var id = att.get('id');
                    if ($.inArray(String(id), current) !== -1) {
                        return;
                    }
                    current.push(String(id));

                    var url = att.get('url');
                    var thumb = (att.get('sizes') && att.get('sizes').thumbnail)
                        ? att.get('sizes').thumbnail.url
                        : url;

                    var $fig = $('<figure class="wc-gfa-thumb">')
                        .attr('data-id', id)
                        .append($('<img>').attr('src', thumb))
                        .append($('<button type="button" class="wc-gfa-remove" aria-label="Remove">&times;</button>'));
                    $thumbs.append($fig);
                });

                $input.val(current.join(','));
            });

            frame.open();
        });

        $field.on('click', '.wc-gfa-remove', function (e) {
            e.preventDefault();
            var $wrap = $(this).closest('.wc-gfa-field');
            var $input = $wrap.find('.wc-gfa-gallery-input');
            var id = String($(this).closest('.wc-gfa-thumb').attr('data-id'));
            var current = $input.val() ? $input.val().split(',') : [];
            current = $.grep(current, function (v) { return v !== id; });
            $input.val(current.join(','));
            $(this).closest('.wc-gfa-thumb').remove();
        });
    }

    $(function () {
        initFrame($('.wc-gfa-field'));

        // WooCommerce clones the variation template for newly added variations.
        $(document).on('woocommerce_variation_added', function () {
            initFrame($('.wc-gfa-field'));
        });
    });
})(jQuery);
