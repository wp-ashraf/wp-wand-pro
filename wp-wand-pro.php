<?php

/**
 * Plugin Name: WP Wand Pro
 * Plugin URI: https://wpwand.com/
 * Description: WP Wand Pro allows you to use the full potential of WP Wand with tons of extra features for quality content generation.
 * Version: 1.2.7
 * Author: WP Wand
 * Author URI: https://wpwand.com/
 * Text Domain: wp-wand-pro
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Define constants
if (!function_exists('get_plugin_data')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

define('WPWAND_PRO_FILE_', __FILE__);
define('WPWAND_PRO_DIR_', __DIR__);
define('WPWAND_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPWAND_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));





function wpwand_pro_loaded()
{
    require __DIR__ . '/vendor/action-scheduler/action-scheduler.php';
    require __DIR__ . '/vendor/action-scheduler/action-scheduler-high-volume.php';

    add_action('init', 'wpwand_pro_load_plugin', 15);
}
add_action('wpwand_init', 'wpwand_pro_loaded', 10);


function wpwand_pro_load_plugin()
{

    $updatechecker = null;
    define('WPWAND_PRO_VERSION', get_plugin_data(__FILE__)['Version']);

    if (!did_action('wpwand_init')) {
        add_action('admin_notices', 'wpwand_pro_required_plugin_notice');

        return;
    }



    if (!did_action('wpwand_init')) {
        return;
    }


    require_once WPWAND_PRO_PLUGIN_DIR . 'inc/db.php';

    // Vendor Autoload
    if (!class_exists('Orhanerday\OpenAi\OpenAi')) {
        require __DIR__ . '/vendor/orhanerday/open-ai/src/Url.php';
        require __DIR__ . '/vendor/orhanerday/open-ai/src/OpenAi.php';
    }


    // if (!class_exists('WP_Background_Process')) {
    //     require __DIR__ . '/vendor/wp-background-processing/wp-background-processing.php';
    // }

    if (!class_exists('Parsedown')) {
        require __DIR__ . '/vendor/parsedown/parsdown.php';
    }

    // Include required files
    require_once WPWAND_PRO_PLUGIN_DIR . 'inc/tala.php';
    // var_dump(wpwand_pro_tala_check());
    if (wpwand_pro_tala_check()) {
        require_once WPWAND_PRO_PLUGIN_DIR . 'inc/data.php';
        require_once WPWAND_PRO_PLUGIN_DIR . 'inc/helper-functions.php';
        require_once WPWAND_PRO_PLUGIN_DIR . 'inc/custom-prompts.php';
        require_once WPWAND_PRO_PLUGIN_DIR . 'inc/sada.php';
        require_once WPWAND_PRO_PLUGIN_DIR . 'inc/WooCommerce.php';
        require_once WPWAND_PRO_PLUGIN_DIR . 'inc/api.php';
        require_once WPWAND_PRO_PLUGIN_DIR . 'inc/history.php';
        require_once WPWAND_PRO_PLUGIN_DIR . 'inc/post-generator.php';
    }

    if ($updatechecker === null) {

        $updatechecker = new WPWandUdChecker();
    }
}


function wpwand_pro_init()
{

    // Add this in your settings page handler
    if (isset($_GET['force-check']) && check_admin_referer('wpwand_pro_force_update_check')) {
        wp_clean_plugins_cache();
        wp_update_plugins();
        wp_redirect(admin_url('plugins.php'));
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
?>
    <div class="notice notice-error">
        <?php
        $plugin = '<a href="' . esc_url('https://wordpress.org/plugins/ai-content-generation/') . '" target="_blank">' . __('WP Wand', 'wp-wand-pro') . '</a>';
        printf(
            '<p>%s %s %s %s</p>',

            $plugin,
            __(' is required to use this pro plugin. Please install and activate ', 'wp-wand-pro'),
            $plugin,
            __(' from here', 'wp-wand-pro')
        );
        ?>
    </div>
<?php
}

function wpwand_pro_version_compatibility_notice()
{
?>
    <div class="notice notice-error">
        <p>
            <?php
            $force_update_url = wp_nonce_url(admin_url('admin.php?page=wpwand-settings&force-check=1'), 'wpwand_pro_force_update_check');
            printf(
                __('WP Wand Pro requires WP Wand version 1.2.8 or higher. Please update the WP Wand plugin to continue using WP Wand Pro and enjoy the full potential of WP Wand Pro. %sUpdate Now%s or %sForce Update Check%s', 'wp-wand-pro'),
                '<a href="' . admin_url('plugins.php') . '">',
                '</a>',
                '<a href="' . esc_url($force_update_url) . '">',
                '</a>'
            );
            ?>
        </p>
    </div>
<?php
}


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
