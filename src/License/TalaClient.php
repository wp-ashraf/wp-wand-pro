<?php

namespace WPWand\License;

/**
 * The one place that knows how to talk to the TALA licensing server.
 *
 * Every license/update request the plugin makes goes through here, so the endpoint URL, the
 * query-param shape (key + site url + type + plugin id), and the response decoding live in a
 * single, testable class instead of being copy-pasted across procedural functions. This is the
 * new-architecture replacement for the request bodies that were scattered through inc/tala.php
 * (wpwand_pro_check_tala / _deactivate / _get_data and WPWandUdChecker::request).
 *
 * The wire contract is IDENTICAL to legacy on purpose — same base URL, same `plugin` ids
 * (68333 for license calls, "wpwand" for update checks), same param names — so existing
 * activations keep working with no server-side change.
 */
class TalaClient
{
    private const BASE = 'https://tala.finestwp.co/wp-json/fdl/v2/envato-plugin';

    /** Envato item id used for license endpoints. */
    private const LICENSE_PLUGIN = '68333';

    /** Plugin slug used for the update-check endpoint. */
    private const UPDATE_PLUGIN = 'wpwand';

    /**
     * Validate a key against the server. Returns the decoded response: `true`, a tier string
     * ("agency"/"growth"/"solo"), or false on any failure. Mirrors wpwand_pro_check_tala().
     *
     * @return mixed
     */
    public function check(string $key)
    {
        $key = trim($key);
        if ($key === '') {
            return false;
        }
        return $this->get('pluginCheck', $key, self::LICENSE_PLUGIN);
    }

    /**
     * Tell the server to release this site's activation. Only UUID-shaped keys are sent (prefixed
     * wpdm* keys are local-only). Mirrors wpwand_pro_check_tala_deactivate().
     */
    public function deactivate(string $key): bool
    {
        $key = trim($key);
        if ($key === '' || !preg_match('/^([a-f0-9]{8})-(([a-f0-9]{4})-){3}([a-f0-9]{12})$/i', $key)) {
            return false;
        }
        return $this->get('pluginDeactivate', $key, self::LICENSE_PLUGIN) === true;
    }

    /**
     * Fetch the Pro data payload (templates, feature list) for a key and cache it in the
     * `wpwand_data` option that the rest of the plugin reads. Mirrors wpwand_pro_get_data().
     */
    public function refresh_data(string $key): bool
    {
        $key = trim($key);
        if ($key === '') {
            return false;
        }
        $body = $this->get('pluginData', $key, self::LICENSE_PLUGIN, true);
        if (is_array($body) && !empty($body)) {
            delete_option('wpwand_data');
            return (bool) update_option('wpwand_data', $body);
        }
        return false;
    }

    /**
     * Ask the server for the latest released version metadata. The license key prefixes are
     * stripped (the update endpoint keys on the bare code). Returns a decoded object or null.
     * Mirrors WPWandUdChecker::request()'s HTTP call (caching lives in UpdateChecker).
     *
     * @return object|null
     */
    public function update_info(string $key): ?object
    {
        $key = str_replace(['wpdmag-', 'wpdmso-', 'wpdmgr-'], '', trim($key));
        $response = $this->raw_get(self::UPDATE_PLUGIN, 'updateCheck', $key);

        if (
            is_wp_error($response)
            || 200 !== (int) wp_remote_retrieve_response_code($response)
            || '' === wp_remote_retrieve_body($response)
        ) {
            return null;
        }

        $decoded = json_decode((string) wp_remote_retrieve_body($response));
        return (is_object($decoded) && !empty($decoded->version)) ? $decoded : null;
    }

    /**
     * Run a GET and return the decoded JSON (assoc when $assoc).
     *
     * @return mixed
     */
    private function get(string $type, string $key, string $plugin, bool $assoc = false)
    {
        $response = $this->raw_get($plugin, $type, $key);
        if (is_wp_error($response)) {
            return false;
        }
        return json_decode((string) wp_remote_retrieve_body($response), $assoc);
    }

    /** Perform the actual HTTP GET against TALA. */
    private function raw_get(string $plugin, string $type, string $key)
    {
        // add_query_arg url-encodes each value once — matches legacy (which sent the bare key and a
        // single-urlencoded home url). Don't pre-encode here or the key would be double-encoded.
        $url = add_query_arg(
            [
                'key'    => $key,
                'url'    => home_url(),
                'type'   => $type,
                'plugin' => $plugin,
            ],
            self::BASE
        );

        return wp_safe_remote_get($url, [
            'headers' => ['Accept' => 'application/json'],
        ]);
    }
}
