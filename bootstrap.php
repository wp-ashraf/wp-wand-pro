<?php

/**
 * WP Wand Pro — new-architecture bootstrap.
 *
 * Mirrors the free plugin's bootstrap: a PSR-4 autoloader for the part of the WPWand\ namespace that
 * physically lives in THIS plugin's src/ (Pro feature modules + their controllers/pages/engine), and
 * the hook that adds those modules to the free plugin's shared module registry. The free plugin owns
 * the framework (Module/ModuleManager, REST base, provider classes); Pro ships the Pro feature code.
 *
 * @package WPWandPro
 */

if (!defined('ABSPATH')) {
    exit('You are not allowed');
}

if (!defined('WPWANDPRO_NEW_DIR')) {
    define('WPWANDPRO_NEW_DIR', plugin_dir_path(__FILE__));
}
if (!defined('WPWANDPRO_NEW_URL')) {
    define('WPWANDPRO_NEW_URL', plugin_dir_url(__FILE__));
}

/*
 * Autoloader for Pro's slice of WPWand\. Registered alongside the free plugin's autoloader; each
 * only require_once's files it actually has, so a class resolves from whichever plugin contains it.
 */
spl_autoload_register(static function ($class) {
    $prefix   = 'WPWand\\';
    $base_dir = WPWANDPRO_NEW_DIR . 'src/';
    $len      = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $file = $base_dir . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (is_readable($file)) {
        require_once $file;
    }
});

/*
 * Pro data/unlock filters (editor prompts + AI characters). Always registered; the callbacks gate on
 * the live license, so unlock follows activation exactly like the legacy data.php. (The Bulk/Automation
 * job-queue engine now lives in the free plugin, which registers its own EngineHooks drainer.)
 */
add_action('plugins_loaded', static function () {
    if (class_exists('WPWand\\Data\\ProData')) {
        \WPWand\Data\ProData::register();
    }
}, 21);

/*
 * Cutover cleanup: the React screens (Bulk / License / Automation / History) are now primary, so
 * strip the legacy Pro submenu entries from the WP Wand menu. Runs late (priority 999) so it fires
 * after the legacy classes register their menus (priorities 11/20/30). The legacy pages stay defined
 * (reachable by direct URL) — only their menu items are removed, leaving a single clean menu.
 */
add_action('admin_menu', static function () {
    remove_submenu_page('wpwand', 'wpwand-history');         // legacy Pro History
    remove_submenu_page('wpwand', 'wpwand-post-generator');  // legacy Pro Bulk Posts
    remove_submenu_page('wpwand', 'wpwand-pro-activation');  // legacy Activation
}, 999);

/*
 * Register Pro feature modules into the shared registry. Fired by the free plugin
 * (\WPWand\Core\Plugin::boot_modules) after all plugins are loaded.
 */
add_action('wpwand_register_modules', static function ($manager) {
    $modules = [
        \WPWand\Modules\CustomPromptsModule::class,
        \WPWand\Modules\WooCommerceModule::class,
        \WPWand\Modules\SeoModule::class,
        \WPWand\Modules\LicenseModule::class,
    ];

    foreach ($modules as $module) {
        if (class_exists($module)) {
            $manager->add(new $module());
        }
    }
});
