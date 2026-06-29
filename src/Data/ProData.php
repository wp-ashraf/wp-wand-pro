<?php

namespace WPWand\Data;

use WPWand\License\LicenseService;

/**
 * Pro data + unlock logic, ported off the legacy inc/data.php into the new architecture.
 *
 * Replaces the legacy approach (Pro's inc/data.php was require'd only when licensed, which both
 * registered the unlock filter and defined the data). Here the filters are always registered and the
 * callbacks gate on the live license, so the behaviour is identical: when the license is active the
 * editor menu is fully unlocked and the Pro AI characters are added; otherwise the free list passes
 * through unchanged.
 *
 *  - wpwand_editor_prompts filter ← wpwand_pro_editor_prompts() (data in editor-prompts-data.php)
 *  - wpwand_ai_characters filter  ← custom prompts (wpwand_custom_prompts table) + premade chars
 *    (ai-characters-data.php, from wpwand_pro_premad_aichars())
 */
final class ProData
{
    public static function register(): void
    {
        add_filter('wpwand_editor_prompts', [self::class, 'editor_prompts']);
        add_filter('wpwand_ai_characters', [self::class, 'ai_characters']);
    }

    /**
     * @param mixed $free The free (locked) editor-prompt list.
     * @return mixed Unlocked list when licensed, else the free list unchanged.
     */
    public static function editor_prompts($free)
    {
        return self::licensed() ? require __DIR__ . '/editor-prompts-data.php' : $free;
    }

    /**
     * @param mixed $chars Characters collected so far (free side passes []).
     * @return array<int, array{title:string, prompt:string}>
     */
    public static function ai_characters($chars): array
    {
        $out = is_array($chars) ? $chars : [];
        if (!self::licensed()) {
            return $out;
        }

        // User's custom characters first, then the built-in premade ones (legacy order).
        foreach (self::custom_prompts('aichar') as $c) {
            if (!empty($c['title']) && !empty($c['prompt'])) {
                $out[] = ['title' => (string) $c['title'], 'prompt' => (string) $c['prompt']];
            }
        }
        foreach ((array) (require __DIR__ . '/ai-characters-data.php') as $c) {
            if (!empty($c['title']) && !empty($c['prompt'])) {
                $out[] = ['title' => (string) $c['title'], 'prompt' => (string) $c['prompt']];
            }
        }

        return $out;
    }

    /**
     * Custom prompts of a type from the {prefix}wpwand_custom_prompts table (port of
     * wpwand_get_custom_prpompts()). Table name unchanged.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function custom_prompts(string $type): array
    {
        global $wpdb;
        if ($type === '') {
            return [];
        }
        $table = $wpdb->prefix . 'wpwand_custom_prompts';
        $rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB
            $wpdb->prepare('SELECT * FROM `' . esc_sql($table) . '` WHERE type = %s', $type),
            ARRAY_A
        );
        return is_array($rows) ? $rows : [];
    }

    private static function licensed(): bool
    {
        return get_option(LicenseService::OPT_STATUS) === 'activated';
    }
}
