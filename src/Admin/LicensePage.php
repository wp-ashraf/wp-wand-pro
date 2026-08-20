<?php

namespace WPWand\Admin;

/**
 * "License (New)" admin submenu — React rebuild of the legacy TALA activation modal. Pro only.
 */
final class LicensePage
{
    private const PARENT_SLUG = 'wpwand';
    private const PAGE_SLUG    = 'wpwand-license-new';
    private const HANDLE       = 'wpwand-license-app';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_menu'], 23);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function add_menu(): void
    {
        if (!function_exists('wpwand_pro_init')) {
            return;
        }

        add_submenu_page(
            self::PARENT_SLUG,
            __('Activate Pro', 'wp-wand-pro'),
            __('Activate Pro', 'wp-wand-pro'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render'],
            40 // order: last
        );
    }

    public function render(): void
    {
        // JS-free skeleton paints instantly; React's createRoot() clears it on mount. See Skeleton.
        // Skeleton + Brand live in the free plugin but share the WPWand\ namespace, so free's
        // autoloader resolves them here. Guard in case free is inactive, then fall back to a bare mount.
        echo '<div class="wrap"><div id="wpwand-license-root">';
        if (class_exists('\WPWand\Admin\Skeleton')) {
            $title = class_exists('\WPWand\Data\Brand')
                ? (\WPWand\Data\Brand::resolve()['name'] ?: 'WP Wand')
                : 'License';
            echo \WPWand\Admin\Skeleton::panel($title, [], 3); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside Skeleton
        }
        echo '</div></div>';
    }

    public function enqueue(string $hook): void
    {
        if (substr($hook, -strlen(self::PAGE_SLUG)) !== self::PAGE_SLUG) {
            return;
        }

        $asset_file = WPWANDPRO_NEW_DIR . 'build/license.asset.php';
        if (!is_readable($asset_file)) {
            return;
        }
        $asset = require $asset_file;

        wp_enqueue_script(self::HANDLE, WPWANDPRO_NEW_URL . 'build/license.js', $asset['dependencies'], $asset['version'], true);
        // No webfont request here on purpose: hitting fonts.googleapis.com from wp-admin is a
        // third-party round trip nobody asked for and a wordpress.org review flag. Every rule in the
        // stylesheet reads --wpwand-font, which names Inter first and then the platform UI stack, so
        // a machine without Inter renders one consistent typeface rather than two.
        if (is_readable(WPWANDPRO_NEW_DIR . 'build/style-license.css')) {
            wp_enqueue_style(self::HANDLE, WPWANDPRO_NEW_URL . 'build/style-license.css', [], (string) filemtime(WPWANDPRO_NEW_DIR . 'build/style-license.css'));
        }
        // Merge, never replace: the free plugin's assistant puts `brand` and `togglerPosition` on
        // this same global from every admin screen, and wp_localize_script() would wipe both.
        if (class_exists('\WPWand\Admin\ScriptConfig')) {
            \WPWand\Admin\ScriptConfig::merge(self::HANDLE, \WPWand\Admin\ScriptConfig::base());
        } else {
            wp_localize_script(self::HANDLE, 'wpwandApi', [
                'root'  => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest'),
            ]);
        }
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations(self::HANDLE, 'wp-wand-pro');
        }
    }
}
