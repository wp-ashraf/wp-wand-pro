<?php
// restrict direct access

use Orhanerday\OpenAi\OpenAi;

if (!defined('ABSPATH')) {
    exit('You are not allowed');
}

function wpwand_pro_request()
{

    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wpwand_global_nonce')) {
        wp_send_json_error(__('Nonce verification failed.', 'wp-wand-pro'), 403);
    }

    if (isset($_POST['wpwand_image_prompt']) && !empty($_POST['wpwand_image_prompt'])) {
        return wpwand_dall_e_request(sanitize_text_field(wp_unslash($_POST['wpwand_image_prompt'])), $_POST);
    }
    // Check if prompt parameter exists
    if (empty($_POST['prompt'])) {
        wp_send_json_error('error');
    }


    $args = [];
    $inc_biz = isset($_POST['inc_biz']) && 'true' == $_POST['inc_biz'] ? true : false;
    // $inc_ai = isset($_POST['inc_ai']) && 'true' == $_POST['inc_ai'] ? true : false;
    $inc_tgdc = isset($_POST['inc_tgdc']) && 'true' == $_POST['inc_tgdc'] ? true : false;



    $selected_model = wpwand_get_option('wpwand_model', 'gpt-3.5-turbo');
    $busines_details = get_option('wpwand_busines_details');
    $targated_customer = get_option('wpwand_targated_customer');
    // $ai_character = get_option('wpwand_ai_character', esc_html('Ignore all previous instructions. You are an expert copywriter. Provide high quality content based on my prompts.'));

    if ($inc_biz) {
        $args['biz_details'] = $busines_details;
    }
    // if ($inc_ai) {
    //     $args['ai_character'] = $ai_character;
    // }
    if ($inc_tgdc) {
        $args['targated_customer'] = $targated_customer;
    }


    $language = wp_kses_post($_POST['language'] ?? '');
    $args['language'] = $language;
    $is_elementor = isset($_POST['is_elementor']) && 'true' == $_POST['is_elementor'] ? '<span class="wpwand-insert-to-widget" >Insert to Elementor</span>' : '';
    $is_gutenberg = isset($_POST['is_gutenberg']) && 'true' == $_POST['is_gutenberg'] ? '<span class="wpwand-insert-to-gutenberg" >Insert to Editor</span>' : '';
    $token = !empty(get_option('wpwand_max_tokens')) ? get_option('wpwand_max_tokens') : 3450;
    $point_of_view = isset($_POST['point_of_view']) ? sanitize_text_field(wp_unslash($_POST['point_of_view'])) : false;
    $aichar = isset($_POST['aichar']) ? sanitize_text_field(wp_unslash($_POST['aichar'])) : false;
    $person_cmd = " The content must be written in $point_of_view ";
    // Sanitize and validate input fields
    $fields = wpwand_api_fields_validate();

    // Replace fields in prompt with values
    $command = preg_replace_callback(
        '/\{([^}]+)\}/',
        function ($matches) use ($fields) {
            $key = trim($matches[1]);
            return isset($fields[$key]) ? $fields[$key] : '';
        },
        sanitize_text_field(wp_unslash($_POST['prompt']))
    );

    $generate_ai_content = function_exists('wpwand_generate_ai_content') ? 'wpwand_generate_ai_content' : 'wpwand_openAi';

    $content = $generate_ai_content("$aichar $command $person_cmd", (int) $fields['no_of_results'], $args);




    // wp_send_json($content);



    $text = '';

    $contents = [];
    if (isset($content->choices)) {
        foreach ($content->choices as $choice) {
            $reply = isset($choice->message) ? $choice->message->content : $choice->text;
            $contents[] = $reply;
            $text .= '<div class="wpwand-content">
            <button class="wpwand-copy-button" >
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3.66659 3.08333V7.75C3.66659 8.39433 4.18892 8.91667 4.83325 8.91667H8.33325M3.66659 3.08333V1.91667C3.66659 1.27233 4.18892 0.75 4.83325 0.75H7.50829C7.663 0.75 7.81138 0.811458 7.92077 0.920854L10.4957 3.49581C10.6051 3.60521 10.6666 3.75358 10.6666 3.90829V7.75C10.6666 8.39433 10.1443 8.91667 9.49992 8.91667H8.33325M3.66659 3.08333H3.33325C2.22868 3.08333 1.33325 3.97876 1.33325 5.08333V10.0833C1.33325 10.7277 1.85559 11.25 2.49992 11.25H6.33325C7.43782 11.25 8.33325 10.3546 8.33325 9.25V8.91667" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Copy to Clipboard
            </button>
            ' . $is_elementor . $is_gutenberg . '<div class="wpwand-ai-response">' . wpautop($reply) . '
            </div></div>';
        }
    } elseif (isset($content->error)) {
        $text .= '<div class="wpwand-content wpwand-prompt-error">';
        $text .= wpwand_ai_error($content->error);
        $text .= '  </div>';
    }

    $template_id = isset($_POST['template_id']) ? sanitize_text_field(wp_unslash($_POST['template_id'])) : '';

    $db = wpwand_pro_add_history($template_id, $content, '');
    wp_send_json($text);
}

// Register AJAX action for logged-in and non-logged-in users

remove_action('wpwand_ajax_api', 'wpwand_request');
add_action('wpwand_ajax_api', 'wpwand_pro_request');




function wpwand_pro_seo_prompt()
{

    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wpwand_global_nonce')) {
        wp_send_json_error('Nonce verification failed.', 403);
    }

    $post_id = isset($_POST['post_id']) ? sanitize_text_field(wp_unslash($_POST['post_id'])) : '';
    $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';

    $post_excerpt = $post_id ? get_the_excerpt($post_id) : '';


    // Call OpenAI API to generate content

    $content = wpwand_generate_ai_content("Write an SEO meta description based on the given title and description. The title is: $title. The description is: $post_excerpt. you must a concise and engaging meta description within the limit of 150 characters.");

    $text = '';
    if (isset($content->choices)) {
        foreach ($content->choices as $choice) {
            $reply = isset($choice->message) ? $choice->message->content : $choice->text;
            $text .= $reply;
        }
    } elseif (isset($content->error)) {
        $text .= '<div class="wpwand-content wpwand-prompt-error">';
        $text .= wpwand_ai_error($content->error);
        $text .= '  </div>';
    }
    wp_send_json($text);
}

add_action('wp_ajax_wpwand_seo_prompt', 'wpwand_pro_seo_prompt');
add_action('wp_ajax_nopriv_wpwand_seo_prompt', 'wpwand_pro_seo_prompt');


function wpwand_pro_add_history($template, $response, $prompt = '')
{


    global $wpdb;


    if ($template && $response) {



        // if ( $matched_theme > 0 ) {

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'wpwand_history',
            array(
                'template_name' => $template,
                // string
                'prompt_info' => wp_json_encode($_POST),
                // string
                'response' => wp_json_encode($response) // string

            )
        );

        // var_dump( $wpdb->print_error() );
        if (false === $inserted) {
            return $inserted;
        } else {
            return true;
        }

        // }

    }
    return false;
}


if (!function_exists('wpwand_api_fields_validate')) {
    function wpwand_api_fields_validate()
    {

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wpwand_global_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }
        return array(
            'topic' => isset($_POST['topic']) ? sanitize_text_field(wp_unslash($_POST['topic'])) : '',
            'keywords' => isset($_POST['keyword']) ? sanitize_text_field(wp_unslash($_POST['keyword'])) : '',
            'no_of_results' => isset($_POST['result_number']) ? absint(sanitize_text_field(wp_unslash($_POST['result_number']))) : 1,
            'tone' => isset($_POST['tone']) ? sanitize_text_field(wp_unslash($_POST['tone'])) : '',
            // 'writing_style' isset($_POST['writing_style') ? => sanitize_text_field(wp_unslash($_POST['writing_style')] ) : '',
            'word_count' => isset($_POST['word_limit']) ? intval(sanitize_text_field(wp_unslash($_POST['word_limit']))) + 1000 : '',
            'product_name' => isset($_POST['product_name']) ? sanitize_text_field(wp_unslash($_POST['product_name'])) : '',
            'description' => isset($_POST['description']) ? sanitize_text_field(wp_unslash($_POST['description'])) : '',
            'content' => isset($_POST['content']) ?  wp_kses_post(sanitize_text_field(wp_unslash($_POST['content']))) : '',
            'content_textarea' => isset($_POST['content_textarea']) ?  wp_kses_post(sanitize_text_field(wp_unslash($_POST['content_textarea']))) : '',
            'custom_textarea' => isset($_POST['custom_textarea']) ?  wp_kses_post(sanitize_text_field(wp_unslash($_POST['custom_textarea']))) : '',
            'product_1' => isset($_POST['product_1']) ?  wp_kses_post(sanitize_text_field(wp_unslash($_POST['product_1']))) : '',
            'product_2' => isset($_POST['product_2']) ?  wp_kses_post(sanitize_text_field(wp_unslash($_POST['product_2']))) : '',
            'description_1' => isset($_POST['description_1']) ?  wp_kses_post(sanitize_text_field(wp_unslash($_POST['description_1']))) : '',
            'description_2' => isset($_POST['description_2']) ?  wp_kses_post(sanitize_text_field(wp_unslash($_POST['description_2']))) : '',
            'subject' => isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '',
            'question' => isset($_POST['question']) ? sanitize_text_field(wp_unslash($_POST['question'])) : '',
            'comment' => isset($_POST['comment']) ? sanitize_text_field(wp_unslash($_POST['comment'])) : '',
        );
    }
}
