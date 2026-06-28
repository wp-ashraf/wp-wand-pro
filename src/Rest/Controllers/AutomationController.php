<?php

namespace WPWand\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WPWand\Automation\AutomationRunner;
use WPWand\Automation\Schedules;
use WPWand\Generation\JobRunner;

/**
 * REST API for scheduled post automation (Pro). CRUD over saved schedules plus a "run now" action.
 *
 *   GET    /wpwand/v1/automation/schedules          list schedules + meta
 *   POST   /wpwand/v1/automation/schedules          create or update a schedule
 *   DELETE /wpwand/v1/automation/schedules/{id}     delete a schedule
 *   POST   /wpwand/v1/automation/schedules/{id}/run run a schedule immediately
 */
final class AutomationController extends AbstractController
{
    protected string $rest_base = 'automation/schedules';

    public function register_routes(): void
    {
        register_rest_route($this->rest_namespace, '/' . $this->rest_base, [
            ['methods' => 'GET',  'callback' => [$this, 'index'], 'permission_callback' => [$this, 'can_pro']],
            ['methods' => 'POST', 'callback' => [$this, 'save'],  'permission_callback' => [$this, 'can_pro']],
        ]);

        register_rest_route($this->rest_namespace, '/' . $this->rest_base . '/(?P<id>[A-Za-z0-9\-]+)', [
            ['methods' => 'DELETE', 'callback' => [$this, 'delete'], 'permission_callback' => [$this, 'can_pro']],
        ]);

        register_rest_route($this->rest_namespace, '/' . $this->rest_base . '/(?P<id>[A-Za-z0-9\-]+)/run', [
            ['methods' => 'POST', 'callback' => [$this, 'run'], 'permission_callback' => [$this, 'can_pro']],
        ]);

        // One generation step + snapshot — the client polls this to show live run progress.
        register_rest_route($this->rest_namespace, '/' . $this->rest_base . '/tick', [
            ['methods' => 'POST', 'callback' => [$this, 'tick'], 'permission_callback' => [$this, 'can_pro']],
        ]);

        // Publish a generated draft straight from the schedule's post list.
        register_rest_route($this->rest_namespace, '/automation/post/(?P<id>\d+)/publish', [
            ['methods' => 'POST', 'callback' => [$this, 'publish_post'], 'permission_callback' => [$this, 'can_publish']],
        ]);
    }

    public function can_pro(): bool
    {
        return $this->can_use() && function_exists('wpwand_pro_init');
    }

    public function index(): WP_REST_Response
    {
        return new WP_REST_Response([
            'schedules' => array_map([$this, 'present'], Schedules::all()),
            'meta'      => [
                'frequencies' => ['hourly', 'daily', 'weekly'],
                'statuses'    => ['draft', 'pending', 'publish'],
                // Live queue state — lets the page show/advance generation without a manual refresh.
                'queue'       => (new JobRunner())->snapshot(),
            ],
        ], 200);
    }

    public function save(WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_json_params();
        if (!is_array($params)) {
            $params = $request->get_params();
        }

        $mode = ($params['mode'] ?? 'list') === 'prompt' ? 'prompt' : 'list';
        if ($mode === 'list' && empty(array_filter((array) ($params['topics'] ?? [])))) {
            return new WP_REST_Response(['error' => __('Add at least one topic.', 'wp-wand')], 400);
        }
        if ($mode === 'prompt' && trim((string) ($params['subject'] ?? '')) === '') {
            return new WP_REST_Response(['error' => __('Enter a subject for the AI to write about.', 'wp-wand')], 400);
        }

        $record = Schedules::save($params);
        return new WP_REST_Response(['schedule' => $this->present($record)], 200);
    }

    public function delete(WP_REST_Request $request): WP_REST_Response
    {
        $ok = Schedules::delete((string) $request->get_param('id'));
        return new WP_REST_Response(['deleted' => $ok], $ok ? 200 : 404);
    }

    public function run(WP_REST_Request $request): WP_REST_Response
    {
        $schedule = Schedules::get((string) $request->get_param('id'));
        if (!$schedule) {
            return new WP_REST_Response(['error' => __('Schedule not found.', 'wp-wand')], 404);
        }

        $queued = (new AutomationRunner())->run_schedule($schedule);

        // Enqueue only and return immediately — the client then polls /tick to drive generation
        // and show live progress. (If the page is closed, the WP-Cron drainer finishes the queue.)
        $fresh = Schedules::get((string) $request->get_param('id'));

        return new WP_REST_Response([
            'queued'   => $queued,
            'snapshot' => (new JobRunner())->snapshot(),
            'schedule' => $fresh ? $this->present($fresh) : null,
        ], 200);
    }

    /** Advance the generation queue by one step; returns a progress snapshot. */
    public function tick(): WP_REST_Response
    {
        return new WP_REST_Response((new JobRunner())->tick(), 200);
    }

    public function can_publish(): bool
    {
        return current_user_can('publish_posts') && function_exists('wpwand_pro_init');
    }

    /** Publish a generated draft (only posts this plugin scheduled). */
    public function publish_post(WP_REST_Request $request): WP_REST_Response
    {
        $post_id = absint($request->get_param('id'));
        if (!$post_id || get_post_meta($post_id, '_wpwand_schedule_id', true) === '') {
            return new WP_REST_Response(['error' => __('Not a scheduled post.', 'wp-wand')], 400);
        }

        $res = wp_update_post(['ID' => $post_id, 'post_status' => 'publish'], true);
        if (is_wp_error($res)) {
            return new WP_REST_Response(['error' => $res->get_error_message()], 200);
        }

        return new WP_REST_Response(['published' => true, 'id' => $post_id], 200);
    }

    /**
     * Shape a stored schedule for the client: ISO-ish next/last run + a human topic count.
     *
     * @param array<string, mixed> $s
     * @return array<string, mixed>
     */
    private function present(array $s): array
    {
        $s['next_run_h'] = !empty($s['next_run'])
            ? get_date_from_gmt(gmdate('Y-m-d H:i:s', (int) $s['next_run']), 'M j, Y g:i a')
            : '';
        $s['last_run_h'] = !empty($s['last_run'])
            ? get_date_from_gmt(gmdate('Y-m-d H:i:s', (int) $s['last_run']), 'M j, Y g:i a')
            : '';
        $s['posts'] = $this->posts_for((string) $s['id']);
        return $s;
    }

    /**
     * The posts a schedule has generated (tagged with _wpwand_schedule_id), newest first.
     *
     * @return array<int, array<string, mixed>>
     */
    private function posts_for(string $scheduleId): array
    {
        $query = new \WP_Query([
            'post_type'      => 'post',
            'post_status'    => ['draft', 'pending', 'publish', 'future', 'private'],
            'posts_per_page' => 20,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_key'       => '_wpwand_schedule_id',
            'meta_value'     => $scheduleId,
            'no_found_rows'  => true,
        ]);

        $out = [];
        foreach ($query->posts as $post) {
            $out[] = [
                'id'       => $post->ID,
                'title'    => get_the_title($post) ?: __( '(no title)', 'wp-wand' ),
                'status'   => $post->post_status,
                'date'     => get_the_date('M j, Y', $post),
                'edit_url' => get_edit_post_link($post->ID, 'raw'),
                'view_url' => get_permalink($post->ID),
            ];
        }
        return $out;
    }
}
