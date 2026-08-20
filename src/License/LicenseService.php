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
            return ['ok' => false, 'error' => __('Enter your license key.', 'wp-wand-pro')];
        }

        $tier = $this->detect_tier($key);
        if (!in_array($tier, ['agency', 'growth', 'solo'], true) && $tier !== true) {
            return ['ok' => false, 'error' => $this->activation_error()];
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

        // Pull Pro templates/features for this key. A failure here is NOT fatal — the licence is
        // already recorded above and Pro is unlocked — but staying silent about it left people with
        // an activated licence and a free-looking template list, and nothing to explain the gap.
        $data_ok = $this->tala->refresh_data($key);

        $result = ['ok' => true, 'state' => $this->snapshot()];
        if (!$data_ok) {
            $result['warning'] = __(
                "Activated. We couldn't download your Pro templates just now — reload in a minute and they'll appear.",
                'wp-wand-pro'
            );
        }

        return $result;
    }

    /**
     * Turn the last failure into something the customer can act on.
     *
     * Every cause used to arrive as the same "That license key is not valid." — so a firewall, an
     * outage and a typo were indistinguishable, and someone who had genuinely paid was told their
     * key was fake. Only say the key is wrong when the server actually said so.
     */
    private function activation_error(): string
    {
        $last = $this->tala->last_error();

        switch ($last['reason'] ?? 'refused') {
            case 'network':
                return __(
                    "We couldn't reach the licence server. Check the site can make outgoing requests, then try again.",
                    'wp-wand-pro'
                );
            case 'http':
                return sprintf(
                    /* translators: %s: HTTP status code returned by the licence server */
                    __('The licence server returned an error (HTTP %s). Nothing is wrong with your key — please try again shortly.', 'wp-wand-pro'),
                    $last['detail'] !== '' ? $last['detail'] : '?'
                );
            case 'empty':
                return __(
                    'The licence server sent an empty reply. Please try again shortly.',
                    'wp-wand-pro'
                );
            case 'garbled':
                return __(
                    "We reached the licence server but couldn't read its reply — usually a firewall or a maintenance page in the way. Your key is fine. Please try again shortly.",
                    'wp-wand-pro'
                );
            default:
                return __(
                    "That licence key wasn't recognised. Check it for typos, or whether it's already in use on another site.",
                    'wp-wand-pro'
                );
        }
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
        // Rebuild the (now free-only) template cache from the built-in catalog.
        if (class_exists('WPWand\\Data\\Templates')) {
            \WPWand\Data\Templates::seed(true);
        }

        delete_option(self::OPT_KEY);
        delete_option(self::OPT_AGENCY);
        delete_option(self::OPT_STATUS);
        // The tier marker was being left behind, so a deactivated site still carried -1/20/10.
        delete_option(self::OPT_LIMIT);
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

        $tier = $active ? self::tier_for_marker($limit, $agency) : 'none';

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
     * The one place that turns a stored wpwand_pgc_limit marker into a tier name.
     *
     * wpwand_pgc_limit is a MARKER, not a cap. Pro wrote 15 for growth and 5 for solo before 1.2.7,
     * then 20 and 10 (ae2eed7). Reading it with a bare `=== 20` here is why a Growth customer who
     * activated on an older Pro was labelled "Solo" on their own License screen while the free
     * plugin's UsageLimits correctly gave them 300/600 — two readers, two answers.
     *
     * This lives in Pro rather than in UsageLimits (its natural home) because Pro must keep working
     * beside a free plugin that predates such a helper, and because the free tree is off-limits to
     * this change. Keep the two tables in step: UsageLimits::limits() holds the matching one.
     */
    public static function tier_for_marker(int $marker, bool $agency = false): string
    {
        if ($agency || $marker === -1) {
            return 'agency';
        }
        if (in_array($marker, [15, 20], true) || $marker > 20) {
            return 'growth';
        }
        return 'solo';
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
