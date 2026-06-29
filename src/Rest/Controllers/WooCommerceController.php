<?php

namespace WPWand\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Response;

/**
 * POST /wpwand/v1/woocommerce/product — generate a product title + description from a short
 * brief (Pro). JSON port of the legacy wpwand_wc_prompt (two generations).
 */
final class WooCommerceController extends AbstractController
{
    public function register_routes(): void
    {
        register_rest_route($this->rest_namespace, '/woocommerce/product', [
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

        $brief = sanitize_textarea_field((string) $request->get_param('prompt'));
        if ($brief === '') {
            return new WP_REST_Response(['error' => __('Describe the product first.', 'wp-wand')], 400);
        }

        $title = $this->text(\WPWand\Generation\Generator::generate(
            "Write one short, catchy, SEO-friendly WooCommerce product title (max 12 words, no quotation marks, sentence case) for this product brief. Output only the title. MY BRIEF: {$brief}."
        ));

        $description = $this->text(\WPWand\Generation\Generator::generate(
            "You are a sales-page copywriting expert. Write a product description for this brief in a joyful, conversational tone. Rules: do not add a headline; start with a hook intro paragraph so readers feel connected; then a list of features each presented as a benefit of buying. Output the description only. MY BRIEF: {$brief}."
        ));

        if ($title === '' && $description === '') {
            return new WP_REST_Response(['error' => __('No response. Try again.', 'wp-wand')], 200);
        }

        return new WP_REST_Response([
            'title'       => $title,
            'description' => $description,
        ], 200);
    }

    private function text($content): string
    {
        if (is_object($content) && isset($content->error)) {
            return '';
        }
        if (is_object($content) && isset($content->choices[0])) {
            $choice = $content->choices[0];
            $t = isset($choice->message->content) ? $choice->message->content : ($choice->text ?? '');
            return trim(trim((string) $t), '"');
        }
        return '';
    }
}
