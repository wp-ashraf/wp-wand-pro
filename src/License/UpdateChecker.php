<?php

namespace WPWand\License;

/**
 * Self-hosted plugin-update integration, rebuilt in the new architecture.
 *
 * WordPress has no idea WP Wand Pro exists (it's not on wordpress.org), so we hook the three
 * standard update filters and answer them from the TALA server:
 *   - site_transient_update_plugins → inject an "update available" row when the server's version
 *     is newer than the installed one
 *   - plugins_api                   → populate the "View details" modal
 *   - upgrader_process_complete     → drop our cache after an update installs
 *
 * This replaces the legacy WPWandUdChecker (inc/tala.php). The HTTP call is delegated to
 * {@see TalaClient::update_info()}; the cache key (`wpwand_pro_update`) is unchanged so it stays
 * in sync with deactivation, which clears the same transient.
 *
 * Caching fix vs legacy: we cache the DECODED payload (an object on success, or a short-lived
 * 'none' sentinel on failure) rather than the raw HTTP response. The legacy version cached the raw
 * response array with its validation guards commented out, so a failed check could poison the cache
 * for a day. Caching a validated object means a transient read can be used directly and a bad
 * response never masquerades as update data.
 */
class UpdateChecker
{
    private const CACHE_KEY = 'wpwand_pro_update';

    private string $plugin_slug     = '';
    private string $plugin_basename = '';
    private string $version         = '0';
    private bool $booted            = false;

    /** Populate the plugin metadata. Safe to call outside admin (e.g. from a REST request). */
    private function boot_meta(): void
    {
        if ($this->booted) {
            return;
        }
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $data                  = get_plugin_data(WPWAND_PRO_FILE_);
        $this->plugin_slug     = plugin_basename(WPWAND_PRO_DIR_);
        $this->plugin_basename = plugin_basename(WPWAND_PRO_FILE_);
        $this->version         = (string) ($data['Version'] ?? '0');
        $this->booted          = true;
    }

    public function register(): void
    {
        // Update checks are an admin concern (the cron updater also runs in an admin-ish context).
        if (!is_admin()) {
            return;
        }
        $this->boot_meta();

        add_filter('plugins_api', [$this, 'info'], 20, 3);
        add_filter('site_transient_update_plugins', [$this, 'update']);
        add_action('upgrader_process_complete', [$this, 'purge'], 10, 2);
    }

    /**
     * Installed vs latest version for the License screen's update box.
     *
     * @return array{current:string, latest:string, update_available:bool, updates_url:string}
     */
    public function status(): array
    {
        $this->boot_meta();
        $remote = $this->remote();
        $latest = $remote && !empty($remote->version) ? (string) $remote->version : '';

        return [
            'current'          => $this->version,
            'latest'           => $latest,
            'update_available' => $latest !== '' && version_compare($this->version, $latest, '<'),
            'updates_url'      => self_admin_url('plugins.php'),
        ];
    }

    /** Force the next check to hit the server (drops the cached payload + WP's plugin-update cache). */
    public function flush(): void
    {
        delete_transient(self::CACHE_KEY);
        delete_site_transient('update_plugins'); // make WP re-run its update scan too
    }

    /**
     * Latest server-side release metadata, cached for a day. Returns null when there's no key, the
     * server is unreachable, or the payload is malformed.
     *
     * @return object|null
     */
    private function remote(): ?object
    {
        $cached = get_transient(self::CACHE_KEY);
        if ($cached !== false) {
            return is_object($cached) ? $cached : null; // 'none' sentinel → null
        }

        $key = (string) get_option(LicenseService::OPT_KEY, '');
        if ($key === '') {
            set_transient(self::CACHE_KEY, 'none', HOUR_IN_SECONDS);
            return null;
        }

        $remote = (new TalaClient())->update_info($key);
        if ($remote === null) {
            // Short cache on failure so we don't hammer the server every page load.
            set_transient(self::CACHE_KEY, 'none', HOUR_IN_SECONDS);
            return null;
        }

        set_transient(self::CACHE_KEY, $remote, DAY_IN_SECONDS);
        return $remote;
    }

    /**
     * @param false|object|array $res
     * @param string             $action
     * @param object             $args
     * @return false|object|array
     */
    public function info($res, $action, $args)
    {
        if ('plugin_information' !== $action) {
            return $res;
        }
        if (!isset($args->slug) || $this->plugin_slug !== $args->slug) {
            return $res;
        }

        $remote = $this->remote();
        if (!$remote) {
            return $res;
        }

        $info                   = new \stdClass();
        $info->name             = $remote->name ?? '';
        $info->slug             = $remote->slug ?? $this->plugin_slug;
        $info->version          = $remote->version ?? $this->version;
        $info->author           = $remote->author ?? '';
        $info->author_homepage  = $remote->author_homepage ?? '';
        $info->download_link    = $remote->download_url ?? '';
        $info->trunk            = $remote->download_url ?? '';
        $info->last_updated     = $remote->last_updated ?? '';
        $info->sections         = [
            'description'  => '',
            'installation' => '',
            'changelog'    => '',
        ];
        if (!empty($remote->banners)) {
            $info->banners = ['low' => '', 'high' => ''];
        }

        return $info;
    }

    /**
     * @param object $transient
     * @return object
     */
    public function update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote = $this->remote();
        if ($remote && version_compare($this->version, (string) $remote->version, '<')) {
            $res              = new \stdClass();
            $res->slug        = $this->plugin_slug;
            $res->plugin      = $this->plugin_basename;
            $res->new_version = $remote->version;
            $res->package     = $remote->download_url ?? '';

            // Without requires_php core has nothing to block on, so a PHP 7.4 site happily installs
            // Pro 2.0.0 and then loses every Pro feature with no way back. Core reads this off the
            // update row, refuses the install and prints "requires PHP 8.0" instead.
            $res->requires_php = (string) ($remote->requires_php ?? '8.0');

            if (!empty($remote->tested)) {
                $res->tested = (string) $remote->tested;
            }
            if (!empty($remote->homepage)) {
                $res->url = (string) $remote->homepage;
            }
            if (!empty($remote->icons)) {
                $res->icons = (array) $remote->icons;
            }

            $transient->response[$res->plugin] = $res;
        }

        return $transient;
    }

    /**
     * @param mixed                $upgrader
     * @param array<string, mixed> $options
     */
    public function purge($upgrader, $options): void
    {
        if (
            ($options['action'] ?? '') === 'update'
            && ($options['type'] ?? '') === 'plugin'
        ) {
            delete_transient(self::CACHE_KEY);
        }
    }
}
