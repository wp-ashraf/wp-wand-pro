<?php

namespace WPWand\License;

/**
 * License activation state + transitions, in the new architecture.
 *
 * Owns the option keys that describe the license and the activate/deactivate transitions that
 * used to live in wpwand_pro_tala_ajax() / wpwand_pro_tala_deactivate(). The REST controller is
 * now a thin HTTP layer over this; the actual server talk is delegated to {@see TalaClient}.
 *
 * Option keys are UNCHANGED from legacy (existing activations migrate transparently):
 *   wpwand_pro_tala_key     — the stored license key
 *   wpwand_pro_tala_status  — 'activated' when active
 *   wpwand_pro_tala_agency  — truthy on the agency tier
 *   wpwand_pgc_limit        — monthly post-generation cap (-1 agency / 20 growth / 10 solo)
 */
class LicenseService
{
    public const OPT_KEY    = 'wpwand_pro_tala_key';
    public const OPT_STATUS = 'wpwand_pro_tala_status';
    public const OPT_AGENCY = 'wpwand_pro_tala_agency';
    public const OPT_LIMIT  = 'wpwand_pgc_limit';

    private TalaClient $tala;

    public function __construct(?TalaClient $tala = null)
    {
        $this->tala = $tala ?: new TalaClient();
    }

    /**
     * Validate + activate a key. Prefixed keys (wpdm*) carry their tier locally; anything else is
     * checked against the server. On success the tier-specific options are written and Pro data is
     * refreshed. Returns [ok, snapshot|error].
     *
     * @return array{ok:bool, error?:string, state?:array<string,mixed>}
     */
    public function activate(string $key): array
    {
        $key = trim($key);
        if ($key === '') {
            return ['ok' => false, 'error' => __('Enter your license key.', 'wp-wand')];
        }

        $tier = $this->detect_tier($key);
        if (!in_array($tier, ['agency', 'growth', 'solo'], true) && $tier !== true) {
            return ['ok' => false, 'error' => __('That license key is not valid.', 'wp-wand')];
        }

        if ($tier === 'agency') {
            update_option(self::OPT_AGENCY, true);
            update_option(self::OPT_LIMIT, -1);
        } else {
            // Not agency: clear any stale agency flag from a previous activation.
            delete_option(self::OPT_AGENCY);
            update_option(self::OPT_LIMIT, $tier === 'growth' ? 20 : 10);
        }
        update_option(self::OPT_KEY, $key);
        update_option(self::OPT_STATUS, 'activated');

        $this->tala->refresh_data($key); // pull Pro templates/features for this key

        return ['ok' => true, 'state' => $this->snapshot()];
    }

    /**
     * Release this site's activation: tell the server, drop the cached Pro data + update transient,
     * and clear the local license options. Returns the (now inactive) snapshot.
     *
     * @return array{ok:bool, state:array<string,mixed>}
     */
    public function deactivate(): array
    {
        $key = (string) get_option(self::OPT_KEY, '');
        if ($key !== '') {
            $this->tala->deactivate($key);
        }
        if (function_exists('wpwand_get_data')) {
            wpwand_get_data(true); // let the free side rebuild its (now free-only) template cache
        }

        delete_option(self::OPT_KEY);
        delete_option(self::OPT_AGENCY);
        delete_option(self::OPT_STATUS);
        delete_transient('wpwand_pro_update');

        return ['ok' => true, 'state' => $this->snapshot()];
    }

    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        $active = get_option(self::OPT_STATUS) === 'activated';
        $agency = (bool) get_option(self::OPT_AGENCY);
        $limit  = (int) get_option(self::OPT_LIMIT, 0);
        $key    = (string) get_option(self::OPT_KEY, '');

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

    public function is_active(): bool
    {
        return get_option(self::OPT_STATUS) === 'activated';
    }

    /**
     * Resolve the tier for a key: prefix-encoded tier when present, else a server check.
     *
     * @return mixed 'agency'|'growth'|'solo'|true|false
     */
    private function detect_tier(string $key)
    {
        if (strpos($key, 'wpdmag-') !== false) {
            return 'agency';
        }
        if (strpos($key, 'wpdmgr-') !== false) {
            return 'growth';
        }
        if (strpos($key, 'wpdmso-') !== false) {
            return 'solo';
        }
        return $this->tala->check($key);
    }

    private function mask(string $key): string
    {
        if (strlen($key) <= 8) {
            return str_repeat('•', strlen($key));
        }
        return substr($key, 0, 4) . str_repeat('•', 6) . substr($key, -4);
    }
}
