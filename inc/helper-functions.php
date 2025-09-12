<?php
function wpwand_pro_admin_scripts()
{

    // wp_enqueue_style( 'wpwand-inter-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap' );
    // wp_enqueue_style( 'jquery-ui', '//code.jquery.com/ui/1.13.1/plugins/base/jquery-ui.css' );
    wp_enqueue_style('wpwand-pro-admin', WPWAND_PRO_PLUGIN_URL . 'assets/css/admin-pro.css');

    // wp_enqueue_script( 'jquery-ui-slider' );
    wp_enqueue_script('wpwand-pro-admin', WPWAND_PRO_PLUGIN_URL . 'assets/js/admin-pro.js', ['wpwand-admin', 'sweetalert2'], time());
    wp_localize_script(
        'wpwand-pro-admin',
        'wpwand_pro_glb',
        array(
            'plugin_url' => WPWAND_PRO_PLUGIN_URL,
            'addprmt_title_label' => __('Template Name', 'wp-wand-pro'),
            'addprmt_title_placeholder' => __('Ex: Generate A Headline', 'wp-wand-pro'),
            'addprmt_prompt_label' => __('Write Your Prompt', 'wp-wand-pro'),
            'addprmt_prompt_placeholder' => __('I will give you a topic and you will generate a SEO friendly headline. The topic is: {topic}', 'wp-wand-pro'),
            'addprmt_prompt_info' => __('To add writing instruction field, use <span class="wpwand-highlight">{topic}</span> inside your prompt.', 'wp-wand-pro'),

            'addaichar_title_label' => __('Character Name', 'wp-wand-pro'),
            'addaichar_title_placeholder' => __('Ex: Content Writer', 'wp-wand-pro'),
            'addaichar_prompt_label' => __('Write Character Details', 'wp-wand-pro'),
            'addaichar_prompt_placeholder' => __('You are a creative content writer.', 'wp-wand-pro'),
            'addaichar_prompt_info' => __('', 'wp-wand-pro'),
        )
    );
}

add_action('admin_enqueue_scripts', 'wpwand_pro_admin_scripts');
add_action('wp_enqueue_scripts', 'wpwand_pro_admin_scripts');

// remove_action( 'enqueue_block_editor_assets', 'wpwand_block_editor', 9 );
// add_action( 'enqueue_block_editor_assets', 'wpwand_pro_block_editor', 9 );
function wpwand_pro_block_editor()
{

    $wpwand_editor_button_menus = wpwand_pro_editor_prompts();
    $language = wpwand_get_option('wpwand_language', 'English');
    $wpwand_editor_button_menus[] = 'English' != $language ? [
        'name' => __('Translate to ' . wpwand_get_option('wpwand_language', 'en'), 'wp-wand-pro'),
        'prompt' => __('Translate to ' . wpwand_get_option('wpwand_language', 'en') . ' this text: [text]', 'wp-wand-pro'),
        'is_pro' => false,
    ] : [];
    if (is_admin() && current_user_can('manage_options')) {
        wp_enqueue_script(
            'wpwand-gutenberg-custom-button',
            WPWAND_PLUGIN_URL . 'assets/js/wpwand-gutenberg.js',
            ['wp-editor', 'wp-i18n', 'wp-element', 'wp-compose', 'wp-components'],
            '1.0.0',
            true
        );

        wp_localize_script(
            'wpwand-gutenberg-custom-button',
            'wpwand_gutenberg_editor',
            array(
                'plugin_url' => WPWAND_PLUGIN_URL,
                'editor_ajax_url' => admin_url('admin-ajax.php'),
                'editor_menus' => $wpwand_editor_button_menus,
                'change_action' => 'below',
            )
        );
    }
}

function wpwand_pro_add_advanced_tab()
{
    ?>
    <a href="#advanced-pro" class="wpwand-nav-tab">
        <?php esc_html_e('Business Details', 'wp-wand-pro'); ?>
    </a>

    <?php
}

remove_action('wpwand_add_tab_link', 'wpwand_add_advanced_tab');
add_action('wpwand_add_tab_link', 'wpwand_pro_add_advanced_tab');

function wpwand_pro_add_advanced_tab_content()
{
    ?>

    <div id="advanced-pro" class="tab-panel" style="display:none;">
        <div class="wpwand-tab-header">
            <h4>
                <?php esc_html_e('Business Details Features', 'wp-wand-pro'); ?>
            </h4>
            <p class="wpwand-field-desc"><?php _e('Improve your content quality 5x with these premium options. Generate targeted, high quality unique content fast.', 'wp-wand-pro'); ?></p>
        </div>
        <table class="form-table">
            <!--         <tr>
                <th scope="row">
                    <label for="wpwand_ai_character">
                        <?php //esc_html_e('A.I Character', 'wp-wand-pro'); ?>

                    </label>
                    <span class="wpwand-field-desc">Tell your A.I what character it contains while writing
                        for you. It will highly improve your content output.</span>
                </th>
                <td>
                    <textarea id="wpwand_ai_character" name="wpwand_ai_character" rows="5" maxlength="600" cols="30"
                        placeholder="Example: You are an expert in SEO copywriting and specializing in Amazon product review article writing."><?php //echo esc_attr(wpwand_get_option('wpwand_ai_character')); ?> </textarea>
                    <span class="wpwand-chars-count"></span>
                </td>
            </tr> -->
            <tr>
                <th scope="row">
                    <label for="wpwand_busines_details">
                        <?php esc_html_e('Business Details', 'wp-wand-pro'); ?>
                        <!-- <span class="wpwand-pro-tag">PRO</span> -->

                    </label>
                    <span class="wpwand-field-desc"><?php _e('Write about your business in detail so that AI can understand and create better content based your business.', 'wp-wand-pro'); ?></span>
                </th>
                <td>
                    <textarea id="wpwand_busines_details" name="wpwand_busines_details" maxlength="600" rows="5" cols="30"
                        placeholder="<?php _e('Example: We write and share detail reviews of hiking items on our blog. We get most visitors through search engines. Our targeted visitors trust our articles to purchase hiking related products from Amazon.', 'wp-wand-pro'); ?>"><?php echo esc_attr(wpwand_get_option('wpwand_busines_details')); ?></textarea>
                    <span class="wpwand-chars-count"></span>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="wpwand_targated_customer">
                        <?php esc_html_e('Targated Customer', 'wp-wand-pro'); ?>
                        <!-- <span class="wpwand-pro-tag">PRO</span> -->

                    </label>
                    <span class="wpwand-field-desc"><?php _e('Write about your target customers in details. Give as much information as possible like who are your targeted customers, where are they from, what are there demographic etc.', 'wp-wand-pro'); ?></span>
                </th>
                <td>
                    <textarea id="wpwand_targated_customer" name="wpwand_targated_customer" maxlength="600" rows="5"
                        cols="30"
                        placeholder="<?php _e('Example: Our targeted customers are from USA. They are young and energetic. They are interested in tours and mostly in hiking.', 'wp-wand-pro'); ?>"><?php echo esc_attr(wpwand_get_option('wpwand_targated_customer')); ?></textarea>
                    <span class="wpwand-chars-count"></span>
                </td>
            </tr>


        </table>
        <!-- <a href="" class="wpwand-submit-pro-btn wpwand-pro-button">Get Pro Version</a> -->

        <?php // wpwand_pro_card()?>
    </div>

    <?php
}

remove_action('wpwand_add_tab_content', 'wpwand_add_advanced_tab_content');
add_action('wpwand_add_tab_content', 'wpwand_pro_add_advanced_tab_content');

function wpwand_pro_settings()
{

    register_setting(
        'wpwand_settings_group',
        'wpwand_ai_character',
        array(
            'type' => 'string',
            'description' => esc_html__('A.I Character', 'wp-wand-pro'),
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'validate_wpwand_input_field',
        )
    );

    register_setting(
        'wpwand_settings_group',
        'wpwand_busines_details',
        array(
            'type' => 'string',
            'description' => esc_html__('Business Details', 'wp-wand-pro'),
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'validate_wpwand_input_field',
        )
    );

    register_setting(
        'wpwand_settings_group',
        'wpwand_targated_customer',
        array(
            'type' => 'string',
            'description' => esc_html__('Targated Customer', 'wp-wand-pro'),
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'validate_wpwand_input_field',
        )
    );

}

add_action('wpwand_register_settings', 'wpwand_pro_settings');

function wpwand_pro_geneeral_locked_content()
{
    ?>



    <tr valign="top">
        <th scope="row">
            <label>
                <?php esc_html_e('Sync Plugin Data', 'wp-wand-pro'); ?>
                <span class="wpwand-field-desc"><?php _e('We often update our data for higher quality results. By clicking sync button you can get updated data instantly.', 'wp-wand-pro'); ?></span>
            </label>
        </th>
        <td>
            <div class="wpwand-slider-input-wrap">
                <a href="" class="wpwand-sync-prompt-data"><?php _e('Sync', 'wp-wand-pro'); ?></a>
            </div>
        </td>
    </tr>
    <?php
}
remove_action('wpwand_general_tab_content', 'wpwand_geneeral_locked_content');
add_action('wpwand_general_tab_content', 'wpwand_pro_geneeral_locked_content');



function wpwand_pro_dall_e_image_field()
{
    ?>
    <div class="wpwand-form-group wpwand-col-2">
        <div class="wpwand-form-field">
            <label for="wpwand-image-resulation"><?php _e('Select Resulation', 'wp-wand-pro'); ?></label>
            <select name="wpwand-image-resulation" id="wpwand-image-resulation">
                <option value="256x256"><?php _e('256x256', 'wp-wand-pro'); ?></option>
                <option value="512x512"><?php _e('512x512', 'wp-wand-pro'); ?></option>
                <option value="1024x1024"><?php _e('1024x1024', 'wp-wand-pro'); ?></option>
            </select>
        </div>
        <div class="wpwand-form-field">
            <label for="wpwand-result-number"><?php _e('Number of Results', 'wp-wand-pro'); ?></label>
            <input type="number" id="wpwand-result-number" min="1" max="3" name="wpwand-result-number" value="1">
        </div>
    </div>
    <?php
}

remove_action('wpwand_dall_e_frontend_fields', 'wpwand_dall_e_image_field');
add_action('wpwand_dall_e_frontend_fields', 'wpwand_pro_dall_e_image_field');



function wpwand_as_data($id)
{
    if (!$id)
        return false;
    global $wpdb;
    $table = $wpdb->prefix . 'actionscheduler_actions';

    $query = "SELECT * FROM $table WHERE hook = 'wpwand_bulk_post_schedule' AND action_id = $id";
    $results = $wpdb->get_row($query, ARRAY_A);

    if ($results) {
        return $results;
    }
    return false;

}


function wpwand_as_process_running()
{
    global $wpdb;
    $table = $wpdb->prefix . 'actionscheduler_actions';

    $query = "SELECT * FROM $table WHERE (hook = 'wpwand_bulk_post_schedule' AND  status = 'in-progress') OR (hook = 'wpwand_bulk_post_schedule' AND  status = 'pending') ";
    $results = $wpdb->get_results($query, ARRAY_A);

    if ($results) {
        return true;
    }
    return false;

}

