<?php

namespace WPWand\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WPWand\Generation\JobRunner;

/**
 * /wpwand/v1/bulk-posts — bulk post generation (Pro).
 *
 * REUSES the legacy engine: rows go into the SAME {prefix}wpwand_generated_post table and
 * jobs are scheduled on the SAME Action Scheduler hook ('wpwand_bulk_post_schedule', group
 * 'wpwand_bulk_sheduler', args ['datas'=>...]) that the legacy Post_Generator handler
 * processes (outline → sections → optional TOC/FAQ → conclusion → row status 'done'). We
 * only add a clean REST surface + React wizard on top.
 *
 * - POST /bulk-posts/titles  { topic, count }                  → title options
 * - POST /bulk-posts         { titles[], tone, keyword, ... }  → queue jobs
 * - GET  /bulk-posts                                           → recent rows
 * - GET  /bulk-posts/progress?ids=1,2                          → per-row status + totals
 */
final class BulkController extends AbstractController
{
    protected string $rest_base = 'bulk-posts';

    public function register_routes(): void
    {
        register_rest_route($this->rest_namespace, '/' . $this->rest_base, [
            ['methods' => 'GET',  'callback' => [$this, 'index'], 'permission_callback' => [$this, 'can_pro']],
            ['methods' => 'POST', 'callback' => [$this, 'queue'], 'permission_callback' => [$this, 'can_pro']],
        ]);
        register_rest_route($this->rest_namespace, '/' . $this->rest_base . '/titles', [
            ['methods' => 'POST', 'callback' => [$this, 'titles'], 'permission_callback' => [$this, 'can_pro']],
        ]);
        register_rest_route($this->rest_namespace, '/' . $this->rest_base . '/progress', [
            ['methods' => 'GET', 'callback' => [$this, 'progress'], 'permission_callback' => [$this, 'can_pro']],
        ]);
        register_rest_route($this->rest_namespace, '/' . $this->rest_base . '/tick', [
            ['methods' => 'POST', 'callback' => [$this, 'tick'], 'permission_callback' => [$this, 'can_pro']],
        ]);
        register_rest_route($this->rest_namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            ['methods' => 'GET',    'callback' => [$this, 'show'],    'permission_callback' => [$this, 'can_pro']],
            ['methods' => 'DELETE', 'callback' => [$this, 'destroy'], 'permission_callback' => [$this, 'can_pro']],
        ]);
        register_rest_route($this->rest_namespace, '/' . $this->rest_base . '/(?P<id>\d+)/approve', [
            ['methods' => 'POST', 'callback' => [$this, 'approve'], 'permission_callback' => [$this, 'can_pro']],
        ]);
    }

    public function destroy(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $id = absint($request['id']);
        $ok = $wpdb->delete($this->table(), ['id' => $id], ['%d']);

        return new WP_REST_Response(['deleted' => (bool) $ok], 200);
    }

    public function show(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $id  = absint($request['id']);
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE id = %d", $id), ARRAY_A); // phpcs:ignore
        if (!$row) {
            return new WP_REST_Response(['error' => __('Not found.', 'wp-wand')], 404);
        }

        return new WP_REST_Response([
            'id'      => (int) $row['id'],
            'title'   => (string) $row['title'],
            'content' => (string) $row['content'],
            'status'  => (string) $row['status'],
        ], 200);
    }

    /**
     * Approve = create a draft post from the stored content (mirrors the legacy
     * "Add to Post"), set the featured image, and stamp post_id so it leaves the list.
     */
    public function approve(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $id  = absint($request['id']);
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE id = %d", $id), ARRAY_A); // phpcs:ignore
        if (!$row || trim((string) $row['title']) === '') {
            return new WP_REST_Response(['error' => __('Nothing to approve.', 'wp-wand')], 400);
        }

        $post_id = wp_insert_post([
            'post_title'   => (string) $row['title'],
            'post_content' => \WPWand\Generation\Markdown::to_post_content((string) $row['content']),
            'post_status'  => 'draft',
            'post_author'  => get_current_user_id(),
        ]);

        if (!$post_id || is_wp_error($post_id)) {
            return new WP_REST_Response(['error' => __('Could not create the post.', 'wp-wand')], 200);
        }

        if (!empty($row['featured_image_id'])) {
            set_post_thumbnail($post_id, (int) $row['featured_image_id']);
        }

        $wpdb->update($this->table(), ['post_id' => $post_id], ['id' => $id]);

        return new WP_REST_Response([
            'post_id'  => (int) $post_id,
            'edit_url' => admin_url('post.php?post=' . (int) $post_id . '&action=edit'),
        ], 200);
    }

    public function can_pro(): bool
    {
        return $this->can_use() && function_exists('wpwand_pro_init');
    }

    private function table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'wpwand_generated_post';
    }

    public function titles(WP_REST_Request $request): WP_REST_Response
    {
        if (!function_exists('wpwand_generate_ai_content')) {
            return new WP_REST_Response(['error' => __('Generator unavailable.', 'wp-wand')], 503);
        }

        $topic = sanitize_text_field((string) $request->get_param('topic'));
        $count = max(1, min(10, absint($request->get_param('count') ?: 3)));
        if ($topic === '') {
            return new WP_REST_Response(['error' => __('Please enter a topic.', 'wp-wand')], 400);
        }

        $language = function_exists('wpwand_get_option') ? wpwand_get_option('wpwand_language', 'English') : 'English';
        $content  = wpwand_generate_ai_content(
            "I will give a topic and you will write only one high converting blog title. This title should have a hook and high potential to go viral on social media. My topic is {$topic}. You must write in {$language}.",
            $count
        );

        if (is_object($content) && isset($content->error)) {
            $msg = \WPWand\Generation\ErrorFormatter::humanize($content->error, __('Failed.', 'wp-wand'));
            return new WP_REST_Response(['error' => $msg], 200);
        }

        $titles = [];
        if (is_object($content) && isset($content->choices)) {
            foreach ($content->choices as $choice) {
                $t = isset($choice->message->content) ? $choice->message->content : ($choice->text ?? '');
                $t = trim(trim((string) $t), '"');
                if ($t !== '') {
                    $titles[] = $t;
                }
            }
        }

        return new WP_REST_Response(['titles' => $titles], 200);
    }

    public function queue(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;

        // Respect the tier cap (Solo 100 / Growth 300 / Agency unlimited), resetting monthly.
        if (!\WPWand\Generation\UsageLimits::can_bulk()) {
            return new WP_REST_Response(['error' => __('Monthly bulk generation limit reached.', 'wp-wand')], 200);
        }

        $titles = (array) $request->get_param('titles');
        $titles = array_values(array_filter(array_map(static fn ($t) => sanitize_text_field((string) $t), $titles)));
        if (empty($titles)) {
            return new WP_REST_Response(['error' => __('Select at least one title.', 'wp-wand')], 400);
        }

        // Only generate as many as the remaining monthly allowance permits.
        $titles = array_slice($titles, 0, \WPWand\Generation\UsageLimits::bulk_remaining());
        if (empty($titles)) {
            return new WP_REST_Response(['error' => __('Monthly bulk generation limit reached.', 'wp-wand')], 200);
        }

        $word_count = absint($request->get_param('word_count'));
        $settings   = [
            'tone'        => sanitize_text_field((string) $request->get_param('tone')),
            'keyword'     => sanitize_text_field((string) $request->get_param('keyword')),
            'toc_include' => (bool) $request->get_param('toc_include'),
            'faq_include' => (bool) $request->get_param('faq_include'),
            'word_count'  => $word_count,
            'language'    => sanitize_text_field((string) $request->get_param('language')),
        ];

        if ($word_count > 0) {
            update_option('wpwand_target_word_count', $word_count);
        }

        // Count each generated post against the monthly bulk allowance (one per title).
        \WPWand\Generation\UsageLimits::consume_bulk(count($titles));
        update_option('wpwand_pgc_task_completed', 0);
        update_option('wpwand_pgc_total_queue', count($titles));

        // Toggleable engine (Settings → experimental). Default 'browser' = step-queue driven by
        // the page heartbeat; reliable on low-config hosts and immune to long-content timeouts.
        $engine = (string) get_option('wpwand_gen_engine', 'browser');

        if ($engine === 'action_scheduler' && function_exists('as_schedule_single_action')) {
            $ids  = [];
            $step = 30;
            foreach ($titles as $title) {
                $wpdb->insert($this->table(), ['title' => $title, 'content' => '', 'post_id' => 0, 'status' => 'pending']);
                $row_id = (int) $wpdb->insert_id;

                $args = ['title' => $title, 'content' => '', 'post_id' => $row_id, 'status' => 'pending', 'settings' => $settings];
                $action_id = as_schedule_single_action(time() + $step, 'wpwand_bulk_post_schedule', ['datas' => $args], 'wpwand_bulk_sheduler');
                $wpdb->update($this->table(), ['action_id' => $action_id], ['id' => $row_id]);

                $ids[] = $row_id;
                $step += 60;
            }
            return new WP_REST_Response(['queued' => count($ids), 'ids' => $ids, 'engine' => $engine], 200);
        }

        // browser / wp_cron — step-based job queue (sectioned generation, no long requests).
        $ids = (new JobRunner())->enqueue($titles, $settings);

        // wp_cron (pseudo-cron, fires on traffic) and system_cron (real server crontab hitting
        // wp-cron.php) both drain via the same scheduled event — they differ only in how reliably
        // WordPress's cron is triggered, which is a server-config concern, not a code path.
        if ($engine === 'wp_cron' || $engine === 'system_cron') {
            if (!wp_next_scheduled('wpwand_gen_cron_tick')) {
                wp_schedule_event(time() + 30, 'wpwand_minutely', 'wpwand_gen_cron_tick');
            }
        }

        return new WP_REST_Response(['queued' => count($ids), 'ids' => $ids, 'engine' => $engine], 200);
    }

    /**
     * Advance the step-based queue by one step. Called repeatedly by the browser heartbeat (the
     * 'browser' engine) — each call does a single short AI step, so nothing ever times out.
     */
    public function tick(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response((new JobRunner())->tick(), 200);
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        // Only not-yet-approved rows (post_id = 0) — approved ones became real posts.
        $rows = $wpdb->get_results("SELECT id, title, content, status, created_at FROM {$this->table()} WHERE post_id = 0 ORDER BY created_at DESC LIMIT 100", ARRAY_A); // phpcs:ignore

        // Background generation in progress? Matches the legacy "Generating Bulk Post…" header.
        // The active engine decides what "running" means; OR the per-row fallback either way.
        $engine  = (string) get_option('wpwand_gen_engine', 'browser');
        $running = (new JobRunner())->is_running();
        if ($engine === 'action_scheduler' && function_exists('wpwand_as_process_running')) {
            $running = $running || (bool) wpwand_as_process_running();
        }
        $running = $running || (bool) array_filter($rows ?: [], static fn ($r) => 'done' !== $r['status'] && 'failed' !== $r['status']);

        return new WP_REST_Response([
            'items'           => $this->shape($rows),
            'limit_text'      => \WPWand\Generation\UsageLimits::bulk_text(),
            'can_create'      => \WPWand\Generation\UsageLimits::can_bulk(),
            'process_running' => $running,
            'engine'          => $engine,
            'queue_total'     => (int) get_option('wpwand_pgc_total_queue', 0),
            'queue_done'      => (int) get_option('wpwand_pgc_task_completed', 0),
        ], 200);
    }

    public function progress(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;
        $ids = array_filter(array_map('absint', explode(',', (string) $request->get_param('ids'))));

        if (empty($ids)) {
            return new WP_REST_Response(['items' => [], 'total' => 0, 'done' => 0], 200);
        }

        $in   = implode(',', $ids);
        $rows = $wpdb->get_results("SELECT id, title, status, post_id FROM {$this->table()} WHERE id IN ({$in}) ORDER BY id ASC", ARRAY_A); // phpcs:ignore
        $items = $this->shape($rows);
        $done  = count(array_filter($items, static fn ($r) => in_array($r['status'], ['done', 'failed'], true)));

        return new WP_REST_Response(['items' => $items, 'total' => count($items), 'done' => $done], 200);
    }

    /**
     * @param array<int, array<string, mixed>>|null $rows
     * @return array<int, array<string, mixed>>
     */
    private function shape($rows): array
    {
        return array_map(static function ($r) {
            $content = isset($r['content']) ? (string) $r['content'] : '';
            return [
                'id'      => (int) $r['id'],
                'title'   => (string) $r['title'],
                'status'  => (string) $r['status'],
                'preview' => $content !== '' ? mb_substr(trim(wp_strip_all_tags($content)), 0, 120) : '',
                'date'    => isset($r['created_at']) ? gmdate('g:i a : M j, Y', strtotime((string) $r['created_at'])) : '',
            ];
        }, $rows ?: []);
    }
}
