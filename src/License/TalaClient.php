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
     * Seconds to wait on a licence call. WordPress defaults to 5, and the server has been measured
     * at 1.4-2.8s, so 5 leaves almost no headroom on a slow link — a customer whose activation
     * simply took too long was told their key was invalid. The update check deliberately keeps the
     * 5s default: it runs from the update-transient filter on admin pages, and a longer wait there
     * would stall every admin screen on a host with blocked outbound HTTP.
     */
    private const LICENSE_TIMEOUT = 15;

    /**
     * Why the last call failed, or null if it succeeded.
     *
     * Without this a network failure, a 500, an HTML error page from a WAF and a genuine "this key
     * is not recognised" were all just `false`, so everyone saw the same sentence no matter what
     * had actually gone wrong.
     *
     * @var array{reason:string, detail:string}|null
     */
    private $last_error = null;

    /**
     * The failure behind the most recent call.
     *
     * reason is 'network' (never reached the server), 'http' (reached it, got an error status),
     * 'empty' (200 with nothing usable), 'garbled' (200 with a body that isn't JSON — a WAF or
     * maintenance page) or 'refused' (the server answered, and said no).
     *
     * @return array{reason:string, detail:string}|null
     */
    public function last_error(): ?array
    {
        return $this->last_error;
    }

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
        $this->last_error = null;

        $response = $this->raw_get($plugin, $type, $key, self::LICENSE_TIMEOUT);

        if (is_wp_error($response)) {
            $this->last_error = ['reason' => 'network', 'detail' => $response->get_error_message()];
            return false;
        }

        // update_info() has always checked this; the licence path never did, so an error page was
        // decoded as if it were an answer. A non-200 here is never a usable body: the server returns
        // the tier on 200 and a WP_Error envelope otherwise.
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $this->last_error = ['reason' => 'http', 'detail' => (string) $code];
            return false;
        }

        $body = (string) wp_remote_retrieve_body($response);
        if (trim($body) === '') {
            $this->last_error = ['reason' => 'empty', 'detail' => ''];
            return false;
        }

        $decoded = json_decode($body, $assoc);

        // A parse failure also returns null, so without this branch an HTML WAF/maintenance page was
        // indistinguishable from the server genuinely answering "no" — and the customer was told
        // their key was invalid. json_decode('false') sets JSON_ERROR_NONE, so a real refusal still
        // falls through below.
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->last_error = ['reason' => 'garbled', 'detail' => json_last_error_msg()];
            return false;
        }

        if ($decoded === false || $decoded === null) {
            $this->last_error = ['reason' => 'refused', 'detail' => ''];
        }

        return $decoded;
    }

    /** Perform the actual HTTP GET against TALA. */
    private function raw_get(string $plugin, string $type, string $key, ?int $timeout = null)
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

        $args = ['headers' => ['Accept' => 'application/json']];
        if ($timeout !== null) {
            $args['timeout'] = $timeout;
        }

        return wp_safe_remote_get($url, $args);
    }
}
