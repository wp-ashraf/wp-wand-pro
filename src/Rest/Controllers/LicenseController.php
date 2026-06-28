<?php

namespace WPWand\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Response;

/**
 * License activation REST API (Pro) — a wpwand/v1 wrapper over the existing TALA mechanism so the
 * React license screen can replace the legacy jQuery modal.
 *
 *   GET  /wpwand/v1/license             current status (active, tier, masked key, limit)
 *   POST /wpwand/v1/license/activate    { key }  validate + activate
 *   POST /wpwand/v1/license/deactivate  deactivate
 *
 * Remote validation reuses the Pro plugin's wpwand_pro_check_tala() / _deactivate(); the option
 * writes mirror wpwand_pro_tala_ajax() in wp-wand-pro/inc/tala.php (tier prefixes + post limits).
 * License changes are admin-only (manage_options).
 */
final class LicenseController extends AbstractController
{
    protected string $rest_base = 'license';

    public function register_routes(): void
    {
        register_rest_route($this->rest_namespace, '/' . $this->rest_base, [
            ['methods' => 'GET', 'callback' => [$this, 'status'], 'permission_callback' => [$this, 'can_admin']],
        ]);
        register_rest_route($this->rest_namespace, '/' . $this->rest_base . '/activate', [
            ['methods' => 'POST', 'callback' => [$this, 'activate'], 'permission_callback' => [$this, 'can_admin']],
        ]);
        register_rest_route($this->rest_namespace, '/' . $this->rest_base . '/deactivate', [
            ['methods' => 'POST', 'callback' => [$this, 'deactivate'], 'permission_callback' => [$this, 'can_admin']],
        ]);
    }

    public function can_admin(): bool
    {
        return current_user_can('manage_options') && function_exists('wpwand_pro_init');
    }

    public function status(): WP_REST_Response
    {
        return new WP_REST_Response($this->snapshot(), 200);
    }

    public function activate(WP_REST_Request $request): WP_REST_Response
    {
        $key = trim((string) $request->get_param('key'));
        if ($key === '') {
            return new WP_REST_Response(['error' => __('Enter your license key.', 'wp-wand')], 400);
        }

        // Tier by prefix, else remote check — mirrors wpwand_pro_tala_ajax().
        if (strpos($key, 'wpdmag-') !== false) {
            $tier = 'agency';
        } elseif (strpos($key, 'wpdmgr-') !== false) {
            $tier = 'growth';
        } elseif (strpos($key, 'wpdmso-') !== false) {
            $tier = 'solo';
        } else {
            $tier = function_exists('wpwand_pro_check_tala') ? wpwand_pro_check_tala($key) : false;
        }

        $valid = ($tier === true || in_array($tier, ['agency', 'growth', 'solo'], true));
        if (!$valid) {
            return new WP_REST_Response(['error' => __('That license key is not valid.', 'wp-wand')], 400);
        }

        if ($tier === 'agency') {
            update_option('wpwand_pro_tala_agency', true);
            update_option('wpwand_pgc_limit', -1);
        } else {
            // Not agency: clear any stale agency flag from a previous activation.
            delete_option('wpwand_pro_tala_agency');
            update_option('wpwand_pgc_limit', $tier === 'growth' ? 20 : 10);
        }
        update_option('wpwand_pro_tala_key', $key);
        update_option('wpwand_pro_tala_status', 'activated');

        if (function_exists('wpwand_pro_get_data')) {
            wpwand_pro_get_data(); // refresh Pro templates from the server
        }

        return new WP_REST_Response($this->snapshot(), 200);
    }

    public function deactivate(): WP_REST_Response
    {
        if (function_exists('wpwand_pro_check_tala_deactivate')) {
            wpwand_pro_check_tala_deactivate((string) get_option('wpwand_pro_tala_key', ''));
        }
        if (function_exists('wpwand_get_data')) {
            wpwand_get_data(true);
        }

        delete_option('wpwand_pro_tala_key');
        delete_option('wpwand_pro_tala_agency');
        delete_option('wpwand_pro_tala_status');
        delete_transient('wpwand_pro_update');

        return new WP_REST_Response($this->snapshot(), 200);
    }

    /** @return array<string, mixed> */
    private function snapshot(): array
    {
        $active = get_option('wpwand_pro_tala_status') === 'activated';
        $agency = (int) get_option('wpwand_pro_tala_agency') === 1;
        $limit  = (int) get_option('wpwand_pgc_limit', 0);
        $key    = (string) get_option('wpwand_pro_tala_key', '');

        if (!$active) {
            $tier = 'none';
        } elseif ($agency) {
            $tier = 'agency';
        } elseif ($limit === 20) {
            $tier = 'growth';
        } else {
            $tier = 'solo';
        }

        return [
            'active'     => $active,
            'tier'       => $tier,
            'agency'     => $agency,
            'limit'      => $limit,
            'key_masked' => $key !== '' ? $this->mask($key) : '',
            'pro'        => function_exists('wpwand_pro_init'),
        ];
    }

    private function mask(string $key): string
    {
        if (strlen($key) <= 8) {
            return str_repeat('•', strlen($key));
        }
        return substr($key, 0, 4) . str_repeat('•', 6) . substr($key, -4);
    }
}
