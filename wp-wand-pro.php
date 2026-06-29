<?php

/**
 * Plugin Name: WP Wand Pro
 * Plugin URI: https://wpwand.com/
 * Description: WP Wand Pro allows you to use the full potential of WP Wand with tons of extra features for quality content generation.
 * Version: 2.0.0
 * Author: WP Wand
 * Author URI: https://wpwand.com/
 * Text Domain: wp-wand-pro
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH')) {
    exit; // No direct access.
}

// Define constants
if (!function_exists('get_plugin_data')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

define('WPWAND_PRO_FILE_', __FILE__);
define('WPWAND_PRO_DIR_', __DIR__);
define('WPWAND_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPWAND_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));

// New (React + REST) architecture: PSR-4 autoloader + Pro feature modules registered into the
// free plugin's shared registry. Loaded early so the module hook is in place before free boots.
require_once __DIR__ . '/bootstrap.php';



require __DIR__ . '/vendor/action-scheduler/action-scheduler.php';

add_action('plugins_loaded', 'wpwand_pro_load_plugin', 20);





function wpwand_pro_load_plugin()
{
    // Pass $markup=false, $translate=false: we only need the version, and translating the
    // header here (at plugins_loaded, before init) would trip WP 6.7+'s just-in-time
    // textdomain notice for the 'wp-wand-pro' domain.
    define('WPWAND_PRO_VERSION', get_plugin_data(__FILE__, false, false)['Version']);

    // The free plugin must be present and booted first.
    if (!function_exists('wpwand_init')) {
        add_action('admin_notices', 'wpwand_pro_required_plugin_notice');
        return;
    }

    // Free is present but still on the pre-2.0.0 (legacy procedural) codebase: it has no
    // module registry / REST framework for Pro to attach to, so Pro features can't load and
    // nothing would appear. Legacy free still fires `wpwand_init`, so we can't rely on that —
    // the namespaced Core\Plugin class only exists in the 2.0.0 rebuild. Stop booting Pro here;
    // wpwand_pro_free_version_check() shows an enforcing "update WP Wand" admin notice.
    if (wpwand_pro_free_is_outdated()) {
        return;
    }

    if (!did_action('wpwand_init')) {
        return;
    }

    // Vendor libraries used by the new architecture (Markdown rendering, etc.).
    if (!class_exists('Orhanerday\OpenAi\OpenAi')) {
        require __DIR__ . '/vendor/orhanerday/open-ai/src/Url.php';
        require __DIR__ . '/vendor/orhanerday/open-ai/src/OpenAi.php';
    }
    if (!class_exists('Parsedown')) {
        require __DIR__ . '/vendor/parsedown/parsdown.php';
    }

    // The legacy procedural inc/* code has been fully removed — the new src/ architecture
    // (bootstrap.php) provides everything: license, bulk, automation, custom prompts, SEO,
    // WooCommerce, history and white-label. Pro DB tables are created by the free plugin's
    // migration runner; updates by WPWand\License\UpdateChecker.
}


function wpwand_pro_init()
{
    if (isset($_GET['force-check']) && check_admin_referer('wpwand_pro_force_update_check')) {
        wp_clean_plugins_cache();
        wp_update_plugins();
        wp_safe_redirect(admin_url('plugins.php'));
        exit;
    }
}
add_action('init', 'wpwand_pro_init', 999);

/**
 * Load plugin textdomain.
 */
function wpwand_pro_load_plugin_textdomain()
{
    load_plugin_textdomain('wp-wand-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'wpwand_pro_load_plugin_textdomain');

function wpwand_pro_required_plugin_notice()
{
    $plugin = '<a href="' . esc_url('https://wordpress.org/plugins/ai-content-generation/') . '" target="_blank">WP Wand</a>';

    /* translators: 1: WP Wand plugin link, 2: WP Wand plugin link */
    $message = __('%1$s is required to use this Pro plugin. Please install and activate %2$s.', 'wp-wand-pro');

    // $plugin is a safe esc_url link built above; the message is run through wp_kses.
    echo '<div class="notice notice-error"><p>';
    printf(wp_kses($message, ['a' => ['href' => [], 'target' => []]]), $plugin, $plugin); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '</p></div>';
}

/**
 * True when WP Wand (free) is present but still on the pre-2.0.0 codebase.
 *
 * Version-constant-free and reliable: legacy free defines wpwand_init() (and even fires the
 * `wpwand_init` action), but the namespaced framework class WPWand\Core\Plugin — which owns the
 * module registry and REST base that Pro attaches to — only exists in the 2.0.0 rebuild. When this
 * is true, Pro 2.0.0 cannot register any features against legacy free.
 */
function wpwand_pro_free_is_outdated()
{
    return function_exists('wpwand_init') && !class_exists('WPWand\\Core\\Plugin');
}

/**
 * Enforce the matching free version. When Pro is 2.0.0 but the free plugin is still legacy, Pro
 * features silently can't load — so show a prominent, non-dismissible notice asking the user to
 * update WP Wand (free), mirroring the free plugin's own "update Pro" enforcement.
 */
function wpwand_pro_free_version_check()
{
    if (!is_admin()) {
        return;
    }

    // Let the user force WordPress to re-check for the free-plugin update, then bounce to Plugins.
    if (isset($_GET['wpwand-recheck-free']) && check_admin_referer('wpwand_free_update_check')) {
        wp_clean_plugins_cache();
        wp_update_plugins();
        wp_safe_redirect(admin_url('plugins.php'));
        exit;
    }

    if (!wpwand_pro_free_is_outdated()) {
        return;
    }

    add_action('admin_notices', function () {
        $recheck = wp_nonce_url(admin_url('plugins.php?wpwand-recheck-free=1'), 'wpwand_free_update_check');

        echo '<div class="notice notice-error"><p>';
        printf(
            /* translators: %s: URL that re-checks for the WP Wand update */
            wp_kses(__('<strong>Action required:</strong> WP Wand Pro 2.0.0 requires WP Wand (free) 2.0.0 or higher. Your Pro features stay hidden until the free plugin is updated. <a href="%s">Update WP Wand</a>', 'wp-wand-pro'), ['a' => ['href' => []], 'strong' => []]),
            esc_url($recheck)
        );
        echo '</p></div>';
    });
}
add_action('init', 'wpwand_pro_free_version_check');


function wpwand_pro_activation()
{

    update_option('wpwand_white_label_disable', 0);
    update_option('wpwand_pro_activated', 'activation');
}
register_activation_hook(__FILE__, 'wpwand_pro_activation');

function wpwand_pro_checker()
{
    update_option('wpwand_white_label_disable', 0);
    update_option('wpwand_pro_activated', 'activation');
}

register_deactivation_hook(__FILE__, 'wpwand_pro_checker');
