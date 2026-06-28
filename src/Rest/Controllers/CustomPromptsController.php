<?php

namespace WPWand\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Response;

/**
 * /wpwand/v1/custom-prompts — user custom templates and AI characters (Pro).
 *
 * Same {prefix}wpwand_custom_prompts table + behavior as the legacy Custom_Prompts class:
 * after every change, type=template rows are recombined into the wpwand_custom_data option
 * (so they appear in the assistant); type=aichar rows surface in the AI Character select.
 */
final class CustomPromptsController extends AbstractController
{
    protected string $rest_base = 'custom-prompts';

    public function register_routes(): void
    {
        register_rest_route($this->rest_namespace, '/' . $this->rest_base, [
            ['methods' => 'GET',  'callback' => [$this, 'index'],  'permission_callback' => [$this, 'can_pro']],
            ['methods' => 'POST', 'callback' => [$this, 'create'], 'permission_callback' => [$this, 'can_pro']],
        ]);
        register_rest_route($this->rest_namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            ['methods' => 'PUT',    'callback' => [$this, 'update'],  'permission_callback' => [$this, 'can_pro']],
            ['methods' => 'DELETE', 'callback' => [$this, 'destroy'], 'permission_callback' => [$this, 'can_pro']],
        ]);
    }

    public function can_pro(): bool
    {
        return $this->can_use() && function_exists('wpwand_pro_init');
    }

    private function table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'wpwand_custom_prompts';
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $type = sanitize_text_field((string) $request->get_param('type'));
        $table = $this->table();

        if ($type !== '') {
            $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE type = %s ORDER BY id DESC", $type), ARRAY_A); // phpcs:ignore
        } else {
            $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A); // phpcs:ignore
        }

        $items = array_map(static function ($r) {
            return [
                'id'     => (int) $r['id'],
                'title'  => (string) $r['title'],
                'prompt' => (string) $r['prompt'],
                'type'   => (string) $r['type'],
            ];
        }, $rows ?: []);

        return new WP_REST_Response(['items' => $items], 200);
    }

    public function create(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        [$title, $prompt, $type, $err] = $this->input($request);
        if ($err) {
            return new WP_REST_Response(['error' => $err], 400);
        }

        $ok = $wpdb->insert($this->table(), ['title' => $title, 'prompt' => $prompt, 'type' => $type]);
        $this->recombine();

        return new WP_REST_Response(['id' => $ok ? (int) $wpdb->insert_id : 0, 'saved' => (bool) $ok], 200);
    }

    public function update(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $id = absint($request['id']);
        [$title, $prompt, $type, $err] = $this->input($request);
        if ($err) {
            return new WP_REST_Response(['error' => $err], 400);
        }

        $ok = $wpdb->update($this->table(), ['title' => $title, 'prompt' => $prompt, 'type' => $type], ['id' => $id]);
        $this->recombine();

        return new WP_REST_Response(['saved' => false !== $ok], 200);
    }

    public function destroy(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $id = absint($request['id']);
        $ok = $wpdb->delete($this->table(), ['id' => $id], ['%d']);
        $this->recombine();

        return new WP_REST_Response(['deleted' => (bool) $ok], 200);
    }

    /**
     * @return array{0:string,1:string,2:string,3:?string}
     */
    private function input(WP_REST_Request $request): array
    {
        $title  = sanitize_text_field((string) $request->get_param('title'));
        $prompt = sanitize_textarea_field((string) $request->get_param('prompt'));
        $type   = sanitize_text_field((string) $request->get_param('type')) ?: 'template';

        if (!in_array($type, ['template', 'aichar'], true)) {
            $type = 'template';
        }
        if ($title === '' || $prompt === '') {
            return ['', '', $type, __('Title and prompt are required.', 'wp-wand')];
        }
        return [$title, $prompt, $type, null];
    }

    /**
     * Rebuild wpwand_custom_data from the template rows (mirrors combine_templates()).
     */
    private function recombine(): void
    {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$this->table()} WHERE type = 'template'", ARRAY_A); // phpcs:ignore

        $data = [];
        foreach ($rows ?: [] as $row) {
            $data[$row['title'] . $row['id']] = [
                'title'         => (string) $row['title'],
                'prompt'        => (string) $row['prompt'],
                'is_pro'        => false,
                'fields'        => 'Topic',
                'description'   => '',
                'point_of_view' => false,
            ];
        }

        update_option('wpwand_custom_data', $data);
    }
}
