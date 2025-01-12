<?php
namespace WPWAND_PRO;

if (!defined('ABSPATH')) {
    exit('You are not allowed');
}

class WooCommerce_PRO
{

    function __construct()
    {
        // add_action( 'edit_form_after_title', [$this, 'prompt_trigger'] );
        // add_action( 'edit_form_after_editor', [$this, 'prompt_trigger'] );
        add_action('admin_footer', [$this, 'prompt_form']);

        add_action('wp_ajax_wpwand_wc_prompt', [$this, 'prompt_ajax']);
        add_action('wp_ajax_nopriv_wpwand_wc_prompt', [$this, 'prompt_ajax']);

    }

    function prompt_ajax()
    {

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpwand_global_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }

        if (!isset($_POST['prompt'])) {
            wp_send_json_error('error');
        }

        $prompt = sanitize_text_field($_POST['prompt']);

        // Call OpenAI API to generate content

        $generate_ai_content = function_exists('wpwand_generate_ai_content') ? 'wpwand_generate_ai_content' : 'wpwand_openAi';

        $title = $generate_ai_content("Please ignore all previous instructions.
			CONTEXT:
			You are a sales page copywriting expert. You know all the secrets of persuasion and high converting sales page writing with human touches. I will give you a short brief about a product. Your job is to write a high converting sales headline for this product.

			YOU MUST FOLLOW THESE STEPS:

			1. You must not use quotation mark in the headline. Just give me the plain headline without any quotation mark.
			2. The tone of this headline must be infomative.
			3. Don't use any call to action or hook in the tile. 
			3. The headline must be between 60 characters. Don’t cross this limit.

			MY BRIEF: $prompt.");

        $content = $generate_ai_content("CONTEXT:
			You are a sales page copywriting expert. I will give you a short brief about a product. Your job is to write a description for this product. The tone of this content must be joyful and conversational.
			
			YOU MUST FOLLOW THESE STEPS:
			1. You must not add a headline inside the description. I only need the description.
			2. At the beginning of the description, write an intro paragraph with a hook so that readers feel connected.
			3. In the next step, mention some pain-points of not using this kind of product. Break the paragraphs into 2-3 lines each. Create scarcity in the readers mind. But don't hard try to hard sell.
			4. In the next step, write a list of features and a short description of each feature. Show these features as benefits of purchasing this item.
			5. Next, you must write 5-10 features as listing format. Use single line for each feature.
			5. At the end, write a short conclusion and add a call to action text so that users feel motivated to purchase this product.
			6. You must write a content of 1000 words or above.

			MY BRIEF: $prompt.");

        $insert_icon = '<svg width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M8.7499 8.75L7.58323 11.6667L5.2499 5.25L11.6666 7.58334L8.7499 8.75ZM8.7499 8.75L11.6666 11.6667M4.19304 1.30581L4.64597 2.99618M2.99603 4.64609L1.30566 4.19316M8.13723 2.36267L6.89979 3.60011M3.60002 6.89988L2.36258 8.13732" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>';
        $insert_title = '<span class="wpwand-insert-to-wc-title" >' . $insert_icon . 'Insert</span>';
        $insert_content = '<span class="wpwand-insert-to-wc-content" >' . $insert_icon . 'Insert</span>';
        $text = '';
        if (isset($title->choices)) {
            foreach ($title->choices as $choice) {
                $reply = isset($choice->message) ? $choice->message->content : $choice->text;

                $text .= ' <div class="wpwand-content wpwand-wc-product-title">
                <h4>Product Title</h4>
            <button class="wpwand-copy-button" >
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3.66659 3.08333V7.75C3.66659 8.39433 4.18892 8.91667 4.83325 8.91667H8.33325M3.66659 3.08333V1.91667C3.66659 1.27233 4.18892 0.75 4.83325 0.75H7.50829C7.663 0.75 7.81138 0.811458 7.92077 0.920854L10.4957 3.49581C10.6051 3.60521 10.6666 3.75358 10.6666 3.90829V7.75C10.6666 8.39433 10.1443 8.91667 9.49992 8.91667H8.33325M3.66659 3.08333H3.33325C2.22868 3.08333 1.33325 3.97876 1.33325 5.08333V10.0833C1.33325 10.7277 1.85559 11.25 2.49992 11.25H6.33325C7.43782 11.25 8.33325 10.3546 8.33325 9.25V8.91667" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Copy
            </button>
            ' . $insert_title . '
            <div class="wpwand-ai-response">' . $reply . '
            </div></div></div>';

            }
        }
        if (isset($content->choices)) {
            foreach ($content->choices as $choice) {
                $reply = isset($choice->message) ? $choice->message->content : $choice->text;
                $text .= ' <div class="wpwand-content wpwand-wc-product-content">
                <h4>Product Description</h4>
            <button class="wpwand-copy-button" >
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3.66659 3.08333V7.75C3.66659 8.39433 4.18892 8.91667 4.83325 8.91667H8.33325M3.66659 3.08333V1.91667C3.66659 1.27233 4.18892 0.75 4.83325 0.75H7.50829C7.663 0.75 7.81138 0.811458 7.92077 0.920854L10.4957 3.49581C10.6051 3.60521 10.6666 3.75358 10.6666 3.90829V7.75C10.6666 8.39433 10.1443 8.91667 9.49992 8.91667H8.33325M3.66659 3.08333H3.33325C2.22868 3.08333 1.33325 3.97876 1.33325 5.08333V10.0833C1.33325 10.7277 1.85559 11.25 2.49992 11.25H6.33325C7.43782 11.25 8.33325 10.3546 8.33325 9.25V8.91667" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Copy
            </button>
            ' . $insert_content . '
            <div class="wpwand-ai-response wpwand-markdown">' . wpautop($reply) . '
            </div></div></div>';
            }
        }

        if (isset($content->error)) {
            $text .= '<div class="wpwand-content wpwand-prompt-error">';
            $text .= wpwand_ai_error($content->error);
            $text .= '  </div>';
        }
        wp_send_json($text);
    }

    // Hook to add a custom input field after the post title
    function prompt_form()
    {
        global $post;

        // Check if the post type is 'post'
        if (isset($post->post_type) && $post->post_type === 'product') {
            ?>
            <div class="wpwand-popup-prompt-wrap" style="display:none">
                <div class="wpwand-wc-prompt-wrap">
                    <span class="wpwand-wc-prompt-toggle" href="#"><img src="<?php echo wpwand_loago_icon_url() ?>">Generate Content
                        with
                        AI</span>
                    <div class="wpwand-popup-prompt">
                        <span class="wpwand-wc-prompt-close"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M1.5 10.5L10.5 1.5M1.5 1.5L10.5 10.5" stroke="#0000005e" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg></span>
                        <form action="" class="wpwand-prompt-form" id="wpwand-wc-prompt-form">

                            <div class="wpwand-form-group">
                                <div class="wpwand-form-field">
                                    <label for="wpwand-short_description">Short Description</label>
                                    <textarea name="wpwand-short_description" id="wpwand-short_description" cols="30" rows="10"
                                        placeholder="Write short info of this product. Add what kind of product is this, how it can help customers etc. Keep it short."></textarea>
                                </div>
                            </div>



                            <div class="wpwand-form-submit">
                                <button class="wpwand-submit-button">Generate Content</button>
                            </div>
                        </form>
                        <div class="wpwand-result-box wpwand-" style="display: none;">

                            <div class="wpwand-content-wrap"></div>

                        </div>

                    </div>
                </div>
            </div>

            <?php
        }
    }

}
$woocommerce = new WooCommerce_PRO();