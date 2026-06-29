<?php

namespace WPWand\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Response;

/**
 * POST /wpwand/v1/seo — generate an SEO meta description from the post title + excerpt
 * (Pro). JSON port of the legacy wpwand_pro_seo_prompt.
 */
final class SeoController extends AbstractController
{
    protected string $rest_base = 'seo';

    public function register_routes(): void
    {
        register_rest_route($this->rest_namespace, '/' . $this->rest_base, [
            ['methods' => 'POST', 'callback' => [$this, 'generate'], 'permission_callback' => [$this, 'can_pro']],
        ]);
    }

    public function can_pro(): bool
    {
        return $this->can_use() && \WPWand\Core\Pro::unlocked();
    }

    public function generate(WP_REST_Request $request): WP_REST_Response
    {
        if (!class_exists('WPWand\Generation\Generator')) {
            return new WP_REST_Response(['error' => __('Generator unavailable.', 'wp-wand')], 503);
        }

        $title   = sanitize_text_field((string) $request->get_param('title'));
        $post_id = absint($request->get_param('post_id'));
        $excerpt = $post_id ? get_the_excerpt($post_id) : '';

        if ($title === '' && $excerpt === '') {
            return new WP_REST_Response(['error' => __('Add a title first.', 'wp-wand')], 400);
        }

        $content = \WPWand\Generation\Generator::generate(
            "Write an SEO meta description based on the given title and description. The title is: {$title}. The description is: {$excerpt}. Write a concise and engaging meta description within 150 characters. Output only the description text."
        );

        if (is_object($content) && isset($content->error)) {
            $msg = isset($content->error->message) ? (string) $content->error->message : __('Failed.', 'wp-wand');
            return new WP_REST_Response(['error' => $msg], 200);
        }

        $text = '';
        if (is_object($content) && isset($content->choices[0])) {
            $choice = $content->choices[0];
            $text   = isset($choice->message->content) ? $choice->message->content : ($choice->text ?? '');
        }
        $text = trim(trim((string) $text), '"');

        if ($text === '') {
            return new WP_REST_Response(['error' => __('No response. Try again.', 'wp-wand')], 200);
        }

        return new WP_REST_Response(['text' => $text], 200);
    }
}
