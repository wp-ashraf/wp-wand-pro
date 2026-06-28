<?php

namespace WPWand\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WPWand\License\LicenseService;

/**
 * License activation REST API (Pro) — a thin wpwand/v1 layer over {@see LicenseService}, which
 * owns the activate/deactivate transitions and snapshot. The React license screen replaces the
 * legacy jQuery modal; the service + {@see \WPWand\License\TalaClient} replace the procedural
 * TALA functions in inc/tala.php.
 *
 *   GET  /wpwand/v1/license             current status (active, tier, masked key, limit)
 *   POST /wpwand/v1/license/activate    { key }  validate + activate
 *   POST /wpwand/v1/license/deactivate  deactivate
 *
 * License changes are admin-only (manage_options).
 */
final class LicenseController extends AbstractController
{
    protected string $rest_base = 'license';

    private LicenseService $service;

    public function __construct()
    {
        $this->service = new LicenseService();
    }

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
        return new WP_REST_Response($this->service->snapshot(), 200);
    }

    public function activate(WP_REST_Request $request): WP_REST_Response
    {
        $result = $this->service->activate((string) $request->get_param('key'));
        if (!$result['ok']) {
            return new WP_REST_Response(['error' => $result['error']], 400);
        }
        return new WP_REST_Response($result['state'], 200);
    }

    public function deactivate(): WP_REST_Response
    {
        $result = $this->service->deactivate();
        return new WP_REST_Response($result['state'], 200);
    }
}
