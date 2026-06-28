<?php

namespace WPWand\Admin;

/**
 * SEO integration: injects a "Generate with AI" button onto the Yoast / RankMath meta
 * description fields on post edit screens (Pro). The button calls REST /seo and fills the
 * field. Replaces the legacy assets/js/seo.js.
 */
final class SeoIntegration
{
    public const HANDLE = 'wpwand-seo-new';

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        // Cutover: remove the legacy seo.js so the button is not injected twice.
        add_action('admin_enqueue_scripts', [$this, 'dequeue_legacy'], 100);
    }

    public function dequeue_legacy(): void
    {
        wp_dequeue_script('wpwand-seo');
        wp_deregister_script('wpwand-seo');
    }

    public function enqueue(string $hook): void
    {
        if (!in_array($hook, ['post.php', 'post-new.php'], true) || !current_user_can('edit_posts')) {
            return;
        }
        // Legacy SEO button is Pro-gated; only wire the working one when Pro is active.
        if (!function_exists('wpwand_pro_init')) {
            return;
        }

        $asset_file = WPWAND_NEW_DIR . 'build/seo.asset.php';
        if (!is_readable($asset_file)) {
            return;
        }
        $asset = require $asset_file;

        wp_enqueue_script(self::HANDLE, WPWAND_NEW_URL . 'build/seo.js', $asset['dependencies'], $asset['version'], true);
        wp_localize_script(self::HANDLE, 'wpwandSeo', [
            'root'  => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }
}
