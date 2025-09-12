<?php

if (!defined('ABSPATH')) {
    exit('You are not allowed');
}

class WPWAND_PRO_WHITE_LABEL
{

    public function __construct()
    {
        $is_agency = wpwand_get_option('wpwand_pro_tala_agency');

        $white_label = wpwand_get_option('wpwand_white_label_disable');

        // var_dump($white_label);

        if (!$is_agency) {
            return;
        }

        remove_action('wpwand_add_tab_link', 'wpwand_add_white_tab', 100);
        remove_action('wpwand_add_tab_content', 'wpwand_add_white_tab_content', 100);
        add_filter('all_plugins', [$this, 'plugins_info']);
        add_filter('plugin_row_meta', [$this, 'custom_plugin_row_meta'], 10, 4);
     
        if(1 == $white_label ) return;

            add_action('wpwand_add_tab_link', [$this, 'add_white_tab']);
            add_action('wpwand_add_tab_content', [$this, 'add_white_tab_content']);
            add_action('wpwand_register_settings', [$this, 'white_settings']);


    }

    function plugins_info($plugins)
    {
        $key = plugin_basename(WPWAND_PRO_PLUGIN_DIR . 'wp-wand-pro.php');
        $free_plugin = plugin_basename(WPWAND_PLUGIN_DIR . 'wp-wand.php');
        $plugin_name = wpwand_get_option('wpwand_plugin_name');
        $plugin_description = wpwand_get_option('wpwand_plugin_description');
        $author_name = wpwand_get_option('wpwand_author_name');
        $author_uri = wpwand_get_option('wpwand_author_url');

        // var_dump($plugins);
        // pro plugin
        
        if ($plugin_name) {
            $plugins[$key]['Name'] = $plugin_name . ' Pro';
        }

        if ($plugin_description) {
            $plugins[$key]['Description'] = $plugin_description;
        }

        if (!empty($author_name)) {
            $plugins[$key]['Author'] = $author_name;
            $plugins[$key]['AuthorName'] = $author_name;
        }

        if (!empty($author_uri)) {
            $plugins[$key]['AuthorURI'] = $author_uri;
            $plugins[$key]['PluginURI'] = $author_uri;
        }

        // free plugin
        if ($plugin_name) {
            $plugins[$free_plugin]['Name'] = $plugin_name;
        }

        if ($plugin_description) {
            $plugins[$free_plugin]['Description'] = $plugin_description;
        }

        if (!empty($author_name)) {
            $plugins[$free_plugin]['Author'] = $author_name;
            $plugins[$free_plugin]['AuthorName'] = $author_name;
        }

        if (!empty($author_uri)) {
            $plugins[$free_plugin]['AuthorURI'] = $author_uri;
            $plugins[$free_plugin]['PluginURI'] = $author_uri;
        }

        return $plugins;
    }


    function custom_plugin_row_meta($plugin_meta, $plugin_file, $plugin_data, $status)
    {

        // Remove the "View Details" link
        $free_plugin = plugin_basename(WPWAND_PLUGIN_DIR . 'wp-wand.php');

        if ($free_plugin == $plugin_file) {
            unset($plugin_meta[2]);
            $plugin_meta['view_site'] = '<a href="' . esc_url($plugin_data['PluginURI']) . '" target="_blank">' . __('View Plugin Site') . '</a>';
        }

        return $plugin_meta;
    }

    function wpwand_pro_white_label_fields()
    {
        return [
            [
                'type' => 'file',
                'name' => 'logo',
                'label' => __('Upload Logo', 'wp-wand-pro'),
                'desc' => __('Upload your logo', 'wp-wand-pro'),
                'placeholder' => __('Add a custom link or click on upload button', 'wp-wand-pro'),
                'default' => WPWAND_PLUGIN_URL . 'assets/img/logo.svg',

            ],
            [
                'type' => 'file',
                'name' => 'logo_icon',
                'label' => __('Logo Icon', 'wp-wand-pro'),
                'desc' => __('Upload your logo icon', 'wp-wand-pro'),
                'placeholder' => __('Add a custom link or click on upload button', 'wp-wand-pro'),
                'default' => WPWAND_PLUGIN_URL . 'assets/img/icon.svg',

            ],

            [
                'type' => 'text',
                'name' => 'brand_name',
                'label' => __('Brand Name', 'wp-wand-pro'),
                'desc' => __('Write your brand name', 'wp-wand-pro'),
                'placeholder' => __('WP Wand', 'wp-wand-pro'),

            ],
            [
                'type' => 'color',
                'name' => 'brand_color',
                'label' => __('Brand Color', 'wp-wand-pro'),
                'default' => '#3767fb',
                'desc' => __('Select your brand color', 'wp-wand-pro'),
                'placeholder' => '',

            ],
            [
                'type' => 'text',
                'name' => 'plugin_name',
                'label' => __('Plugin Name', 'wp-wand-pro'),
                'desc' => __('Write your plugin name', 'wp-wand-pro'),
                'placeholder' => __('WP Wand', 'wp-wand-pro'),
            ],
            [
                'type' => 'text',
                'name' => 'plugin_description',
                'label' => __('Plugin Description', 'wp-wand-pro'),
                'desc' => __('Write your plugin description', 'wp-wand-pro'),
                'placeholder' => __('WP Wand is a AI content generation plugin for WordPress that helps your team create high quality content 10X faster and 50x cheaper. No monthly subscription required.', 'wp-wand-pro'),
            ],
            [
                'type' => 'text',
                'name' => 'author_name',
                'label' => __('Author Name', 'wp-wand-pro'),
                'desc' => __('Write your author name', 'wp-wand-pro'),
                'placeholder' => __('WP Wand', 'wp-wand-pro'),
            ],
            [
                'type' => 'text',
                'name' => 'author_url',
                'label' => __('Author Url', 'wp-wand-pro'),
                'desc' => __('Write your author URL', 'wp-wand-pro'),
                'placeholder' => 'https://wpwand.com',
            ],
        ];
    }

    function add_white_tab()
    {
        ?>
        <a href="#white-label" class="wpwand-nav-tab">
            <?php esc_html_e('White Label', 'wp-wand-pro'); ?>
        </a>
        <?php
    }

    function add_white_tab_content()
    {
        $all_fields = is_array($this->wpwand_pro_white_label_fields()) ? $this->wpwand_pro_white_label_fields() : false;

        ?>

        <div id="white-label" class="tab-panel" style="display:none;">
            <div class="wpwand-tab-header">
                <h4>
                    <?php esc_html_e('White Label', 'wp-wand-pro'); ?>
                </h4>
                <p class="wpwand-field-desc"><?php _e('You can change all branding and public info of WP Wand to use it as your own on client’s website', 'wp-wand-pro'); ?></p>
            </div>
            <table class="form-table">
                <?php if ($all_fields):
                    foreach ($all_fields as $field):
                        $field_name = 'wpwand_' . $field['name'];
                        $default_val = isset($field['default']) ? $field['default'] : '';
                        ?>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_html($field_name); ?>"><?php echo esc_html($field['label']); ?>
                                    <!-- <span class="wpwand-pro-tag">PRO</span> -->
                                </label>
                                <span class="wpwand-field-desc">
                                    <?php echo esc_html($field['desc']); ?>
                                </span>
                            </th>
                            <td>
                                <?php switch ($field['type']) {
                                    case 'file':
                                        # code...
                                        ?>
                                        <div class="wpwand-upload-field-wrap">
                                            <input type="text" id="<?php echo esc_html($field_name); ?>"
                                                name="<?php echo esc_html($field_name); ?>"
                                                placeholder="<?php echo esc_html($field['placeholder']); ?>"
                                                value="<?php echo wpwand_get_option($field_name, $default_val); ?>">
                                            <button id="<?php echo esc_html($field_name); ?>-upload-button"
                                                class="wpwand-upload-button"><?php _e('Upload', 'wp-wand-pro'); ?></button>
                                            <div class="wpwand-upload-preview">
                                                <span class="wpwand-img-preview-remove">x</span>
                                                <img src="<?php echo wpwand_get_option($field_name, $default_val) ?>" alt="">
                                            </div>
                                        </div>
                                        <?php
                                        break;

                                    default:
                                        ?>
                                        <input type="<?php echo esc_html($field['type']); ?>" name="<?php echo esc_html($field_name); ?>"
                                            id="<?php echo esc_html($field_name); ?>"
                                            placeholder="<?php echo esc_html($field['placeholder']); ?>"
                                            value="<?php echo wpwand_get_option($field_name, $default_val); ?>">

                                        <?php
                                        break;
                                } ?>



                            </td>
                        </tr>
                    <?php endforeach; endif; ?>

                <tr valign="top">
                    <th scope="row">
                        <label for="wpwand_white_label_disable">
                            <?php esc_html_e('Disable White Label Tab', 'wp-wand-pro'); ?>
                        </label>
                        <span class="wpwand-field-desc"><?php _e('You can enable White Label tab again after disabling and enabling the Pro plugin.', 'wp-wand-pro'); ?></span>
                    </th>

                    <td class="wpwand-field">
                        <input type="checkbox" id="wpwand_white_label_disable" name="wpwand_white_label_disable" value="1"
                            class="wpwand_white_label_disable" <?php checked( wpwand_get_option('wpwand_white_label_disable')); ?>>

                    </td>
                </tr>

            </table>
            <!-- <a href="" class="wpwand-submit-pro-btn wpwand-pro-button">Get Pro Version</a> -->

            <?php // wpwand_pro_card()?>
        </div>
        <?php
    }

    function white_settings()
    {

        $all_fields = is_array($this->wpwand_pro_white_label_fields()) ? $this->wpwand_pro_white_label_fields() : false;

        if ($all_fields):
            foreach ($all_fields as $field):
                register_setting(
                    'wpwand_settings_group', 'wpwand_' . $field['name'],
                    array(
                        'type' => 'string',
                        'description' => esc_html($field['label']),
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => 'validate_wpwand_input_field',
                    )
                );
            endforeach;
        endif;

        register_setting(
            'wpwand_settings_group',
            'wpwand_white_label_disable',
            array(
                'type' => 'string',
                'description' => esc_html__('White Label', 'wp-wand-pro'),
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => 'validate_wpwand_input_field',
            )
        );
    }

}

$white_lable_obj = new WPWAND_PRO_WHITE_LABEL();