<?php
namespace WPWAND_PRO;

if (!defined('ABSPATH')) {
    exit('You are not allowed');
}
class Custom_Prompts
{

    public function __construct()
    {

        // var_dump(get_option('wpwand_data'));
        // var_dump(wpwand_templates());
        add_action('wpwand_add_tab_link', [$this, 'add_tab']);
        add_action('wpwand_add_tab_content', [$this, 'add_tab_content']);

        add_action('wp_ajax_wpwand_add_prompt', [$this, 'add_prompt']);
        add_action('wp_ajax_nopriv_wpwand_add_prompt', [$this, 'add_prompt']);

        add_action('wp_ajax_wpwand_update_prompt', [$this, 'update_prompt']);
        add_action('wp_ajax_nopriv_wpwand_update_prompt', [$this, 'update_prompt']);

        add_action('wp_ajax_wpwand_delete_prompt', [$this, 'delete_prompt']);
        add_action('wp_ajax_nopriv_wpwand_delete_prompt', [$this, 'delete_prompt']);

    }

    public function add_tab()
    {
        ?>
        <a href="#custom-pro" class="wpwand-nav-tab">
            <?php esc_html_e('Advanced', 'wp-wand-pro'); ?>
        </a>
        <?php
    }

    public function add_tab_content()
    {
        $data = $this->get_all_prpompts('template');
        $character = $this->get_all_prpompts('aichar');
        include 'view/custom-prompts.php';
    }



    /**
     * Summary of get_generated_content
     * @return mixed
     */
    public function get_all_prpompts($type = '')
    {
        global $wpdb;

        $where = !empty($type) ? "WHERE type = '" . $type . "'" : "";
        $query = "SELECT * FROM {$wpdb->prefix}wpwand_custom_prompts $where";
        $results = $wpdb->get_results($query, ARRAY_A);

        if ($results) {
            return $results;
        }

        return false;
    }


    public function add_prompt()
    {

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpwand_global_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }

        if (empty($_POST['title']) || empty($_POST['prompt'])) {
            wp_send_json_error('error');
        }

        $title = isset($_POST['title']) && !empty($_POST['title']) ? sanitize_text_field(stripslashes_deep($_POST['title'])) : '';
        $prompt = isset($_POST['prompt']) && !empty($_POST['prompt']) ? sanitize_text_field(stripslashes_deep($_POST['prompt'])) : '';
        $type = isset($_POST['type']) && !empty($_POST['type']) ? sanitize_text_field($_POST['type']) : 'template';


        $args = [
            'title' => $title,
            'prompt' => $prompt,
            'type' => $type
        ];

        $result = $this->insert_data($args);

        $update_all_templates = $this->combine_templates();
        wp_send_json($result);

    }

    public function update_prompt()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpwand_global_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }

        if (empty($_POST['id']) || empty($_POST['prompt'])) {
            wp_send_json_error('error');
        }

        $title = isset($_POST['title']) && !empty($_POST['title']) ? stripslashes_deep($_POST['title']) : '';
        $prompt = isset($_POST['prompt']) && !empty($_POST['prompt']) ? stripslashes_deep($_POST['prompt']) : '';
        $type = isset($_POST['type']) && !empty($_POST['type']) ? sanitize_text_field($_POST['type']) : 'template';
        $id = isset($_POST['id']) && !empty($_POST['id']) ? (int) sanitize_text_field($_POST['id']) : 0;


        $args = [
            'title' => $title,
            'prompt' => $prompt,
            'type' => $type,

        ];

        $result = $this->update_data($args, $id);
        $update_all_templates = $this->combine_templates();
        wp_send_json($result);

    }


    function update_data(array $args, $id)
    {
        global $wpdb;


        if ($args) {

            // if ( $matched_theme > 0 ) {

            $inserted = $wpdb->update(
                $wpdb->prefix . 'wpwand_custom_prompts',
                $args,
                [
                    'id' => $id
                ]
            );

            if (false === $inserted) {
                return $inserted;
            } else {
                return true;
            }

            // }

        }
        return false;
    }
    /**
     * Summary of add_data
     * @param array $args
     * @return mixed
     */
    function insert_data(array $args)
    {
        global $wpdb;


        if ($args) {


            $inserted = $wpdb->insert(
                $wpdb->prefix . 'wpwand_custom_prompts',
                $args
            );

            if (false === $inserted) {
                return $inserted;
            } else {
                return $wpdb->insert_id;
            }



        }
        return false;
    }


    function delete_prompt()
    {

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpwand_global_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }

        if (isset($_POST['id'])) {

            global $wpdb;
            $table_name = $wpdb->prefix . 'wpwand_custom_prompts';
            $result = $wpdb->delete($table_name, array('id' => $_POST['id']));
            $update_all_templates = $this->combine_templates();

            wp_send_json($result);

        }
    }

    function combine_templates()
    {
        $data = $this->get_all_prpompts('template');

        $prepare_template = [];
        foreach ($data as $key => $value) {
            $prepare_template[$value['title'] . $value['id']] = [
                'title' => $value['title'],
                'prompt' => $value['prompt'],
                'is_pro' => false,
                'fields' => 'Topic',
                'description' => '',
                'point_of_view' => false
            ];
        }

        return update_option('wpwand_custom_data', $prepare_template);

    }
}


$wpwand_custom_prompts = new Custom_Prompts();