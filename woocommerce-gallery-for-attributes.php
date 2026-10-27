<?php
/**
 * Plugin Name: WooCommerce Gallery For Attributes
 * Plugin URI:  https://github.com/EstebanSelvaggi/WooCommerce_Gallery_For_Attributes
 * Description: Adds a custom featured image and image gallery to each product variation. When a customer selects a variation on the frontend, the product gallery is swapped to that variation's images.
 * Version:     1.0.1
 * Author:      Esteban Selvaggi
 * Author URI:  https://github.com/EstebanSelvaggi
 * License:     GPL-2.0-or-later
 * Text Domain: wc-gfa
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 9.5
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WC_GFA_VERSION', '1.0.1' );
define( 'WC_GFA_META_KEY', '_wc_gfa_gallery' );

/**
 * Main plugin class (singleton).
 */
final class WC_Gallery_For_Attributes {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'render_variation_gallery_field' ), 10, 3 );
        add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_gallery' ), 10, 2 );
        add_filter( 'woocommerce_available_variation', array( $this, 'expose_gallery_to_frontend' ), 10, 3 );

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
    }

    /* ------------------------------------------------------------------ */
    /* Admin                                                               */
    /* ------------------------------------------------------------------ */

    public function enqueue_admin_assets( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }
        $screen = get_current_screen();
        if ( ! $screen || 'product' !== $screen->post_type ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script(
            'wc-gfa-admin',
            plugins_url( 'assets/admin.js', __FILE__ ),
            array( 'jquery' ),
            WC_GFA_VERSION,
            true
        );
        wp_enqueue_style(
            'wc-gfa-admin',
            plugins_url( 'assets/admin.css', __FILE__ ),
            array(),
            WC_GFA_VERSION
        );
        wp_localize_script( 'wc-gfa-admin', 'wcGfaAdmin', array(
            'i18n' => array(
                'title'   => __( 'Select variation gallery images', 'wc-gfa' ),
                'button'  => __( 'Use these images', 'wc-gfa' ),
            ),
        ) );
    }

    /**
     * Render the gallery manager inside each variation accordion.
     *
     * @param int      $loop         Variation loop index.
     * @param array    $variation_data Unused.
     * @param WP_Post  $variation   Variation post object.
     */
    public function render_variation_gallery_field( $loop, $variation_data, $variation ) {
        $variation_id = is_object( $variation ) ? $variation->ID : 0;
        $gallery_ids  = $this->get_gallery_ids( $variation_id );

        echo '<div class="form-row form-row-full wc-gfa-field" data-variation-loop="' . esc_attr( $loop ) . '">';
        echo '<label class="wc-gfa-label">' . esc_html__( 'Variation image gallery', 'wc-gfa' ) . '</label>';
        echo '<div class="wc-gfa-thumbs">';
        foreach ( $gallery_ids as $att_id ) {
            $thumb = wp_get_attachment_image_url( $att_id, 'thumbnail' );
            if ( ! $thumb ) {
                continue;
            }
            echo '<figure class="wc-gfa-thumb" data-id="' . esc_attr( $att_id ) . '">'
                . '<img src="' . esc_url( $thumb ) . '" alt="" />'
                . '<button type="button" class="wc-gfa-remove" aria-label="' . esc_attr__( 'Remove image', 'wc-gfa' ) . '">&times;</button>'
                . '</figure>';
        }
        echo '</div>';
        echo '<input type="hidden" class="wc-gfa-gallery-input" name="_wc_gfa_gallery[' . esc_attr( $loop ) . ']" value="' . esc_attr( implode( ',', $gallery_ids ) ) . '" />';
        echo '<button type="button" class="button wc-gfa-add">' . esc_html__( 'Add gallery images', 'wc-gfa' ) . '</button>';
        echo '</div>';
    }

    /**
     * Save gallery IDs posted from the variation metabox.
     *
     * @param int $variation_id Variation ID.
     * @param int $loop         Loop index.
     */
    public function save_variation_gallery( $variation_id, $loop ) {
        if ( ! isset( $_POST['_wc_gfa_gallery'][ $loop ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return;
        }
        $raw = sanitize_text_field( wp_unslash( $_POST['_wc_gfa_gallery'][ $loop ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );

        if ( empty( $ids ) ) {
            delete_post_meta( $variation_id, WC_GFA_META_KEY );
        } else {
            update_post_meta( $variation_id, WC_GFA_META_KEY, implode( ',', array_unique( $ids ) ) );
        }
    }

    /* ------------------------------------------------------------------ */
    /* Frontend                                                            */
    /* ------------------------------------------------------------------ */

    public function enqueue_frontend_assets() {
        if ( ! function_exists( 'is_product' ) || ! is_product() ) {
            return;
        }
        wp_enqueue_script(
            'wc-gfa-frontend',
            plugins_url( 'assets/frontend.js', __FILE__ ),
            array( 'jquery' ),
            WC_GFA_VERSION,
            true
        );
    }

    /**
     * Attach the variation gallery (full-size URLs) to the variation data
     * exposed on the frontend via the variations script variable.
     *
     * @param array    $data      Variation data.
     * @param WC_Product $product Parent product.
     * @param WC_Product $variation Variation product.
     * @return array
     */
    public function expose_gallery_to_frontend( $data, $product, $variation ) {
        $urls = $this->get_gallery_urls( $variation->get_id() );
        if ( ! empty( $urls ) ) {
            $data['wc_gfa_gallery'] = $urls;
        }
        return $data;
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                             */
    /* ------------------------------------------------------------------ */

    private function get_gallery_ids( $variation_id ) {
        $raw = get_post_meta( $variation_id, WC_GFA_META_KEY, true );
        if ( ! $raw ) {
            return array();
        }
        return array_filter( array_map( 'absint', explode( ',', $raw ) ) );
    }

    private function get_gallery_urls( $variation_id ) {
        $urls = array();
        foreach ( $this->get_gallery_ids( $variation_id ) as $att_id ) {
            $full = wp_get_attachment_image_url( $att_id, 'woocommerce_single' );
            if ( ! $full ) {
                $full = wp_get_attachment_image_url( $att_id, 'full' );
            }
            if ( $full ) {
                $urls[] = $full;
            }
        }
        return $urls;
    }
}

WC_Gallery_For_Attributes::instance();
