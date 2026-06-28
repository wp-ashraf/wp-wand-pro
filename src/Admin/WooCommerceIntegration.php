<?php

namespace WPWand\Admin;

/**
 * WooCommerce integration: a "Generate with AI" panel on the product editor that writes a
 * title + description from a short brief and inserts them (Pro). Replaces the legacy
 * assets/js/woocommerce.js.
 */
final class WooCommerceIntegration
{
    public const HANDLE = 'wpwand-wc-new';

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        add_action('admin_enqueue_scripts', [$this, 'dequeue_legacy'], 100);
        // Strip the legacy footer panel before it renders (admin_footer fires after admin_head).
        add_action('admin_head', [$this, 'disable_legacy_form']);
    }

    public function dequeue_legacy(): void
    {
        // Legacy WooCommerce script handle (helper-functions.php).
        wp_dequeue_script('wpwand-wwoocommerce');
    }

    /**
     * The free (WPWAND\WooCommerce) and Pro (WPWAND_PRO\WooCommerce_PRO) modules each print their
     * own "Generate Content" panel on admin_footer. The new React panel replaces both, so remove
     * those callbacks to avoid duplicate (and, with the legacy JS dequeued, inert) markup. Matched
     * by class name so we don't need a handle on the legacy instances.
     */
    public function disable_legacy_form(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== 'product') {
            return;
        }

        global $wp_filter;
        if (empty($wp_filter['admin_footer'])) {
            return;
        }

        $legacy = ['WPWAND\WooCommerce', 'WPWAND_PRO\WooCommerce_PRO'];
        foreach ($wp_filter['admin_footer']->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $cb) {
                $fn = $cb['function'] ?? null;
                if (is_array($fn) && is_object($fn[0]) && in_array(get_class($fn[0]), $legacy, true)) {
                    remove_action('admin_footer', $fn, $priority);
                }
            }
        }
    }

    public function enqueue(string $hook): void
    {
        if (!in_array($hook, ['post.php', 'post-new.php'], true) || !current_user_can('edit_posts')) {
            return;
        }
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->post_type !== 'product' || !function_exists('wpwand_pro_init')) {
            return;
        }

        $asset_file = WPWAND_NEW_DIR . 'build/woocommerce.asset.php';
        if (!is_readable($asset_file)) {
            return;
        }
        $asset = require $asset_file;

        wp_enqueue_script(self::HANDLE, WPWAND_NEW_URL . 'build/woocommerce.js', $asset['dependencies'], $asset['version'], true);
        wp_localize_script(self::HANDLE, 'wpwandWc', [
            'root'  => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }
}
