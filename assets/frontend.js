/**
 * WooCommerce Gallery For Attributes - Frontend
 *
 * Swaps the product gallery (flexslider / photoswipe aware) when a variation
 * with a custom gallery is selected, and restores the parent gallery on reset.
 */
(function ($) {
    'use strict';

    var $form, $gallery, $wrapper, originalHtml = null, currentHtml = null;

    function cacheDom() {
        $form = $('form.variations_form');
        $gallery = $('.woocommerce-product-gallery');
        $wrapper = $gallery.find('.woocommerce-product-gallery__wrapper');
    }

    function snapshotOriginal() {
        if (originalHtml === null && $wrapper.length) {
            originalHtml = $wrapper.html();
        }
    }

    function buildHtml(urls) {
        var html = '';
        for (var i = 0; i < urls.length; i++) {
            html += '<div class="woocommerce-product-gallery__image" data-thumb="' + urls[i] + '">';
            html += '<a href="' + urls[i] + '">';
            html += '<img src="' + urls[i] + '" class="wp-post-image" />';
            html += '</a></div>';
        }
        return html;
    }

    function replaceGallery(html) {
        if (!$wrapper.length || html === currentHtml) {
            return;
        }
        currentHtml = html;

        // Destroy zoom/flexslider/zoom so they can be re-initialized cleanly.
        if ($.fn.zoom && $gallery.data('zoom')) {
            $gallery.trigger('zoom.destroy');
        }
        if ($.fn.flexslider && $gallery.data('flexslider')) {
            try { $gallery.flexslider('destroy'); } catch (e) {}
        }

        $wrapper.html(html);

        // Re-init WooCommerce gallery scripts if available.
        if (typeof wc_product_gallery_params !== 'undefined' || $.fn.flexslider) {
            $(document.body).trigger('wc-gallery-init');
        }
        if ($.fn.zoom) {
            $gallery.zoom({ touch: false });
        }
        if ($.fn.flexslider) {
            $gallery.flexslider(typeof wc_single_product_params !== 'undefined' && wc_single_product_params.flexslider
                ? wc_single_product_params.flexslider
                : { selector: '.woocommerce-product-gallery__wrapper > .woocommerce-product-gallery__image', animation: 'slide', smoothHeight: true });
        }

        // Update photoswipe item list if the gallery script read it already.
        $(document.body).trigger('reset_gallery');
    }

    $(function () {
        cacheDom();
        snapshotOriginal();

        $form.on('found_variation', function (event, variation) {
            if (variation && variation.wc_gfa_gallery && variation.wc_gfa_gallery.length) {
                replaceGallery(buildHtml(variation.wc_gfa_gallery));
            } else {
                // Variation without custom gallery: restore parent gallery.
                if (originalHtml !== null) {
                    replaceGallery(originalHtml);
                }
            }
        });

        $form.on('reset_data', function () {
            if (originalHtml !== null) {
                replaceGallery(originalHtml);
            }
        });
    });
})(jQuery);
