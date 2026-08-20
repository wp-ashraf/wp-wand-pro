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
    /**
     * The first provider error seen while generating this request, if any.
     *
     * text() used to drop it on the floor, so an invalid key, a quota error and an unreachable host
     * all came back as "No response. Try again." — nothing the customer could act on.
     *
     * @var object|array|string|null
     */
    private $provider_error = null;

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
        $this->provider_error = null;

        if (!class_exists('WPWand\Generation\Generator')) {
            return new WP_REST_Response(['error' => __('Generator unavailable.', 'wp-wand-pro')], 503);
        }

        $brief = sanitize_textarea_field((string) $request->get_param('prompt'));
        if ($brief === '') {
            return new WP_REST_Response(['error' => __('Describe the product first.', 'wp-wand-pro')], 400);
        }

        $title = $this->text(\WPWand\Generation\Generator::generate(
            "Write one short, catchy, SEO-friendly WooCommerce product title (max 12 words, no quotation marks, sentence case) for this product brief. Output only the title. MY BRIEF: {$brief}."
        ));

        $description = $this->text(\WPWand\Generation\Generator::generate(
            "You are a sales-page copywriting expert. Write a product description for this brief in a joyful, conversational tone. Rules: do not add a headline; start with a hook intro paragraph so readers feel connected; then a list of features each presented as a benefit of buying. Output the description only. MY BRIEF: {$brief}."
        ));

        if ($title === '' && $description === '') {
            return new WP_REST_Response(['error' => $this->failure_message()], 200);
        }

        return new WP_REST_Response([
            'title'       => $title,
            'description' => $description,
        ], 200);
    }

    /**
     * What to tell the customer when both generations came back empty: the real provider reason when
     * we have one, otherwise the old generic line.
     */
    private function failure_message(): string
    {
        $fallback = __('No response. Try again.', 'wp-wand-pro');

        if ($this->provider_error === null || !class_exists('WPWand\Generation\ErrorFormatter')) {
            return $fallback;
        }

        return \WPWand\Generation\ErrorFormatter::humanize($this->provider_error, $fallback);
    }

    private function text($content): string
    {
        if (is_object($content) && isset($content->error)) {
            if ($this->provider_error === null) {
                $this->provider_error = $content->error;
            }
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
