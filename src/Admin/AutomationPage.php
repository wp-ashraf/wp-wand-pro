<?php

namespace WPWand\Admin;

/**
 * "Automation (New)" admin submenu — React UI for scheduled recurring post generation. Pro only.
 */
final class AutomationPage
{
    private const PARENT_SLUG = 'wpwand';
    private const PAGE_SLUG    = 'wpwand-automation-new';
    private const HANDLE       = 'wpwand-automation-app';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_menu'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function add_menu(): void
    {
        if (!function_exists('wpwand_pro_init')) {
            return;
        }

        add_submenu_page(
            self::PARENT_SLUG,
            __('Automation (New)', 'wp-wand'),
            __('Automation (New)', 'wp-wand'),
            'edit_posts',
            self::PAGE_SLUG,
            [$this, 'render']
        );
    }

    public function render(): void
    {
        echo '<div class="wrap"><div id="wpwand-automation-root"></div></div>';
    }

    public function enqueue(string $hook): void
    {
        if (substr($hook, -strlen(self::PAGE_SLUG)) !== self::PAGE_SLUG) {
            return;
        }

        $asset_file = WPWANDPRO_NEW_DIR . 'build/automation.asset.php';
        if (!is_readable($asset_file)) {
            return;
        }
        $asset = require $asset_file;

        wp_enqueue_script(self::HANDLE, WPWANDPRO_NEW_URL . 'build/automation.js', $asset['dependencies'], $asset['version'], true);
        wp_enqueue_style('wpwand-inter-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', [], $asset['version']);
        if (is_readable(WPWANDPRO_NEW_DIR . 'build/style-automation.css')) {
            wp_enqueue_style(self::HANDLE, WPWANDPRO_NEW_URL . 'build/style-automation.css', [], (string) filemtime(WPWANDPRO_NEW_DIR . 'build/style-automation.css'));
        }
        wp_localize_script(self::HANDLE, 'wpwandApi', [
            'root'  => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
            'brand' => \WPWand\Data\Brand::resolve()['color'],
        ]);
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations(self::HANDLE, 'wp-wand');
        }
    }
}
