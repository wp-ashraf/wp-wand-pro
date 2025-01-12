<?php

namespace WPWAND_PRO;

use ActionScheduler;
use Exception;

if (!defined('ABSPATH')) {
    exit('You are not allowed');
}

class Post_Generator
{
    private $total_tasks = 0;
    private $completed_tasks = 0;
    protected $cron_interval = 1;

    /**
     * @var string
     */
    protected $prefix = 'wpwand';

    /**
     * @var string
     */
    protected $action = 'post_generate';

    public function __construct()
    {
        // parent::__construct();

        add_action('admin_menu', [$this, 'register_menu']);
        add_action('wp_ajax_wpwand_post_generator', [$this, 'generate_title']);
        add_action('wp_ajax_nopriv_wpwand_post_generator', [$this, 'generate_title']);
        add_action('wp_ajax_wpwand_post_content_generator', [$this, 'generate_content']);
        add_action('wp_ajax_nopriv_wpwand_post_content_generator', [$this, 'generate_content']);

        add_action('wp_ajax_wpwand_post_generation_progress', [$this, 'generation_progress']);
        add_action('wp_ajax_nopriv_wpwand_post_generation_progress', [$this, 'generation_progress']);

        add_action('wpwand_bulk_post_schedule', [$this, 'wpwand_bulk_post_schedule'], 10, 1);

        add_action('init', [$this, 'restart_action_queue'], 999);
    }

    /**
     * Function to perform the custom task.
     */
    function wpwand_bulk_post_schedule($args)
    {
        error_log(print_r($args, true)); // Log the contents of $args

        // Perform your custom task here
        // You can use $title, $id, and $settings as needed
        $title = isset($args['title']) ? $args['title'] : '';
        $id = (int) isset($args['post_id']) ? $args['post_id'] : 0;
        $settings = isset($args['settings']) ? $args['settings'] : [];


        update_option('wpwand_as_rst_count', 'workings');


        // For example, you can call your process_post_generation function here
        $this->process_post_generation($title, $id, $settings);

        update_option('wpwand_as_rst_count', 'worked');
        delete_option('wpwand_as_rst_count_' . $id);

        // Update the completed tasks count

        // // Check if all tasks are completed
        // if ($completed_tasks == $this->total_tasks) {
        //     // All tasks are completed, clear the queue
        //     as_unschedule_all_actions('wpwand_bulk_post_schedule');
        // }
    }

    /**
     * Schedule the custom task.
     */
    function schedule_custom_task($title, $id, $settings)
    {
        // Define your task arguments
        $task_args = array(
            'title' => $title,
            'id' => $id,
            'settings' => $settings,
        );

        // Schedule the task using Action Scheduler
        as_enqueue_async_action('my_custom_task_hook', $task_args);
    }
    /**
     * Summary of register_menu
     * @return void
     */
    function register_menu()
    {
        add_submenu_page('wpwand', __('Bulk Posts', 'wp-wand-pro'), __('Bulk Posts', 'wp-wand-pro'), 'manage_options', 'wpwand-post-generator', [$this, 'post_generate_page']);
    }

    /**
     * Summary of generate_title
     * @return void
     */
    function generate_title()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wpwand_global_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }

        if (empty($_POST['topic'])) {
            wp_send_json_error('error');
        }

        $topic = sanitize_text_field($_POST['topic'] ?? '');
        $count = sanitize_text_field($_POST['count'] ?? '');

        $language = wpwand_get_option('wpwand_language', 'English');
        $rawResponse = isset($_POST['rawResponse']) && true == $_POST['rawResponse'] ? true : false;
        $is_table_format_prompt = $rawResponse ? '' : 'You must give output with html tags';

        $generate_ai_content = function_exists('wpwand_generate_ai_content') ? 'wpwand_generate_ai_content' : 'wpwand_openAi';

        $content = $generate_ai_content(
            "I will give a topic and you will write one high converting blog title. This title should have a hook and high potential to go viral on social media. My topic is" . $topic . ". You must write in $language.",
            (int) $count
        );



        $text = '';
        $i = 0;
        if (isset($content->choices)) {

            foreach ($content->choices as $choice) {
                $i++;
                $reply = isset($choice->message) ? $choice->message->content : $choice->text;

                $reply = trim($reply);
                $reply = rtrim($reply, '"');
                $reply = ltrim($reply, '"');

                $text .= '
                <div class="wpwand-pcgf-heading-item">
                    <div class="wpwand-pcgf-heading-content">
                        <input type="checkbox" id="selected_headings' . $i . '" name="selected_headings[]" value="' . $reply . '"> 
                        <label for="selected_headings' . $i . '">' . $reply . '</label>
                    </div>
           
                </div>
                ';
            }
        } elseif (isset($content->error)) {
            $text .= '<div class="wpwand-content wpwand-prompt-error">';
            $text .= wpwand_ai_error($content->error);
            $text .= '  </div>';
        }
        wp_send_json($text);
    }


    /**
     * Summary of generate_content
     * @return void
     */
    function generate_content()
    {

        if (empty($_POST['selected_title'])) {
            wp_send_json_error('error');
        }

        $selected_title = $_POST['selected_title'] ?? [];
        $settings = [
            'tone' => $_POST['tone'] ?? '',
            'keyword' => $_POST['keyword'] ?? '',
            'toc_include' => $_POST['toc_include'] ?? false,
            'faq_include' => $_POST['faq_include'] ?? false,
        ];

        $language = wpwand_get_option('wpwand_language', 'English');


        $tasks = [];
        if (is_array($selected_title)) {
            $this->total_tasks = count($selected_title); // Set the total number of tasks

            $prev_total_bulk = (int) get_option('wpwand_pgc_total_bulk_generated', 0);
            update_option('wpwand_pgc_total_bulk_generated', $prev_total_bulk + 1);
            update_option('wpwand_pgc_task_completed', 0);
            update_option('wpwand_pgc_total_queue', $this->total_tasks);
            $prev_time = 30;
            foreach ($selected_title as $index => $title) {


                $args = [
                    'title' => $title,
                    'content' => '',
                    'post_id' => '',
                    'status' => 'pending',
                ];
                $content_id = $this->add_data($args);


                $args['post_id'] = $content_id;
                $args['settings'] = $settings;
                $prev_time + 60;
                $scheduled_time = strtotime("+ $prev_time seconds");

                $action_id = as_schedule_single_action($scheduled_time, 'wpwand_bulk_post_schedule', [$args], 'wpwand_bulk_sheduler');

                while (!$action_id) {
                    $action_id = as_schedule_single_action($scheduled_time, 'wpwand_bulk_post_schedule', [$args], 'wpwand_bulk_sheduler');
                }

                $this->update_data([
                    'action_id' => $action_id,
                ], $content_id);
            }
            // $this->save()->dispatch(); // Save and dispatch the background tasks
        }



        wp_send_json($this->total_tasks);
    }

    function process_post_generation($title, $id, $settings = [])
    {

        // $this->add_data($title);
        if ($title) {

            $args = [
                'model' => 'gpt-3.5-turbo-16k',
                'max_tokens' => 15000,
            ];
            $tone = isset($settings['tone']) ? $settings['tone'] : '';
            $keyword = isset($settings['keyword']) ? "You must include the these keywords inside the blog post: " . $settings['keyword'] : '';
            $toc_include = isset($settings['toc_include']) && $settings['toc_include'] ? 'You must add a Table of Contents at the beginning' : '';
            $faq_include = isset($settings['faq_include']) && $settings['faq_include'] ? 'You must add a Table of Contents at the bottom' : '';
            $language = wpwand_get_option('wpwand_language', 'English');
            $content = wpwand_generate_ai_content(
                "Using Markdown formatting, write a 100% unique, creative and human-like SEO-friendly blog post using headings and sub-headings. You must write a 4000 word blog post or more. " . $keyword . ". Your writing tone must be $tone. Blog title is:" . $title . ". Be as in depth as possible and include as much detail with relavant information and cover the full topic. Always include lists and tables wherever you can. You must write at least 2-3 paragraphs with 800-1000 words content for each outline title. $toc_include. Try to use contractions, idioms, transitional phrases, interjections, dangling modifiers, and colloquialisms, and avoiding repetitive phrases and unnatural sentence structures. Also, add the blog title inside the intro paragraph as a keyword and use the seed keyword as the first H2. Always use a combination of paragraphs, lists, and tables for a better reader experience. $faq_include. Write an engaging conclusion. This blog post must be plagiarism free. The final result must pass ChatGPT detection and AI content detection. You must write in $language.",
                1,
                $args
            );
            // $content = wpwand_generate_ai_content(
            //     "Using Markdown formatting, write a 100% unique, creative and human-like SEO-friendly blog paragraph .You must write a 100 word blog post or less. " . $keyword . ". Your writing tone must be $tone. Paragraph title is:" . $title . ". You must write in $language.",
            //     1,
            //     $args
            // );


            $i = 0;
            if (isset($content->choices)) {

                foreach ($content->choices as $choice) {
                    $i++;
                    $reply = isset($choice->message) ? $choice->message->content : $choice->text;


                    $Parsedown = new \Parsedown();

                    if (empty($reply)) {
                        $args = [
                            'title' => $title,
                            'content' => '',
                            'post_id' => '',
                            'status' => 'failed',
                        ];
                        return $this->update_data($args, $id);
                    }

                    $args = [
                        'title' => $title,
                        'content' => $Parsedown->text($reply),
                        'post_id' => '',
                        'status' => 'done',
                    ];
                    update_option('wpwand_schedule_working', $args);

                    return $this->update_data($args, $id);
                }
            } elseif (isset($content->error)) {
                return false;
            }
            update_option('wpwand_schedule_working', $title);
        }
        update_option('wpwand_schedule_working', 'failed');
        return false;
    }

    public function generation_progress()
    {

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wpwand_global_nonce')) {
            wp_send_json_error('Nonce verification failed.', 403);
        }

        if (empty($_POST['id'])) {
            wp_send_json_error('error');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwand_generated_post';
        $id = (int) $_POST['id'];
        $row = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $id", ARRAY_A);
        $actions_data = wpwand_as_data((int) $row['action_id']);
        $status = isset($actions_data['status']) ? $actions_data['status'] : 'failed';

        $rest_count = get_option('wpwand_as_rst_count_' . $row['id'], 0);
        if ('failed' == $status) {
            $this->restart_action_queue();
        }

        if ($status == 'failed' && 4 >= $rest_count) {
            $status = 'pending';
        }

        wp_send_json($status);
    }
    /**
     * Summary of add_data
     * @param array $args
     * @return mixed
     */
    function add_data(array $args)
    {
        global $wpdb;


        if ($args) {

            // if ( $matched_theme > 0 ) {

            $inserted = $wpdb->insert(
                $wpdb->prefix . 'wpwand_generated_post',
                $args
            );

            if (false === $inserted) {
                return $inserted;
            } else {
                return $wpdb->insert_id;
            }

            // }

        }
        return false;
    }

    function update_data(array $args, $id)
    {
        global $wpdb;


        if ($args) {

            // if ( $matched_theme > 0 ) {

            $inserted = $wpdb->update(
                $wpdb->prefix . 'wpwand_generated_post',
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

    function delete_data()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'wpwand-post-delete') {

            global $wpdb;
            $table_name = $wpdb->prefix . 'wpwand_generated_post';

            $as_data =  wpwand_as_data($_GET['id']);


            if (class_exists('ActionScheduler')) {
                $action_id = (int) $as_data['action_id']; // Replace with your action ID.

                try {
                    // Attempt to cancel the action.

                    ActionScheduler::store()->cancel_action($action_id);
                    ActionScheduler::store()->delete_action($action_id);
                    // echo "Action with ID {$action_id} has been canceled.";
                } catch (Exception $e) {
                    error_log("Error: Unable to cancel the action. " . $e->getMessage());
                }
            }

            $wpdb->delete($table_name, array('id' => $_GET['id']));

            // redirect to the list page
            wp_safe_redirect(admin_url('admin.php?page=wpwand-post-generator'));
            exit;
        }
    }

    function approve_data()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'wpwand-post-approve') {

            global $wpdb;
            $table_name = $wpdb->prefix . 'wpwand_generated_post';
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $row = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $id", ARRAY_A);
            $post_id = $this->insert_post($row['title'], $row['content']);
            $args = [
                'post_id' => $post_id,
            ];

            $updated = $this->update_data($args, $id);

            wp_safe_redirect(admin_url('admin.php?page=wpwand-post-generator'));
            exit;
        }
    }

    public function insert_post($post_title, $post_content)
    {

        if ($post_title) {


            $post_data = array(
                'post_title' => $post_title,
                'post_content' => $post_content,
                'post_status' => 'draft',
                'post_author' => get_current_user_id(),
            );

            return wp_insert_post($post_data);
        }
    }

    public function restart_action_queue($force_restart = false)
    {

        $pg_data = $this->get_generated_content();
        if ($pg_data) {

            $prev_time = 0;
            foreach ($pg_data as $row) {

                $actions_data = wpwand_as_data((int) $row['action_id']);
                // var_dump($actions_data);
                $start_count = get_option('wpwand_as_rst_count_' . $row['id'], 0);

                if ((!isset($actions_data['status']) || 'failed' != $actions_data['status']) && $force_restart) {
                    update_option('wpwand_restart_needed', false);
                }

                if ($row['status'] == 'failed') {
                    update_option('wpwand_restart_needed', true);
                }

                if (((isset($actions_data['status']) && 'failed' == $actions_data['status']) || $row['status'] == 'failed') && (4 >= $start_count || $force_restart)) {
                    update_option('wpwand_restart_needed', false);

                    $args = json_decode($actions_data['extended_args'], true);

                    if (is_array($args)) {
                        $prev_time = $prev_time + 60;
                        $scheduled_time = strtotime("+ $prev_time seconds");

                        $action_id = as_schedule_single_action($scheduled_time, 'wpwand_bulk_post_schedule', $args, 'wpwand_bulk_sheduler');

                        while (!$action_id) {
                            $action_id = as_schedule_single_action($scheduled_time, 'wpwand_bulk_post_schedule', $args, 'wpwand_bulk_sheduler');
                        }

                        $this->update_data([
                            'action_id' => $action_id,
                        ], $row['id']);




                        if ($force_restart) {
                            update_option('wpwand_as_rst_count_' . $row['id'], 0);
                        } else {
                            $start_count++;

                            if (4 == $start_count) {
                                update_option('wpwand_restart_needed', true);
                            }
                            update_option('wpwand_as_rst_count_' . $row['id'], $start_count);
                        }
                    }
                } elseif ((isset($actions_data['status']) && 'failed' == $actions_data['status']) && 4 > $start_count) {
                    update_option('wpwand_restart_needed', true);
                }
            }
        }
    }
    /**
     * Summary of get_generated_content
     * @return mixed
     */
    public function get_generated_content()
    {
        global $wpdb;

        $query = "SELECT * FROM {$wpdb->prefix}wpwand_generated_post";
        $results = $wpdb->get_results($query, ARRAY_A);

        if ($results) {
            return $results;
        }

        return false;
    }

    public function bulk_action()
    {

        // var_dump($_POST);
        // exit;
        if (!function_exists('wp_verify_nonce') || !current_user_can('manage_options')) {
            return;
        }

        // Handle bulk actions
        if (isset($_POST['doaction']) || isset($_POST['doaction2'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'wpwand_generated_post';
            $action = isset($_POST['doaction']) ? $_POST['wpwand-bulk-action-top'] : $_POST['wpwand-bulk-action-bottom'];
            $post_ids = isset($_POST['wpwand-select-post']) ? array_map('intval', $_POST['wpwand-select-post']) : array();

            if (!empty($post_ids) && $action != '-1') {
                foreach ($post_ids as $post_id) {
                    switch ($action) {
                        case 'approve':
                            // Add your approve logic here
                            $wpdb->update(
                                $table_name,
                                array('status' => 'done'),
                                array('id' => $post_id),
                                array('%s'),
                                array('%d')
                            );
                            break;

                        case 'delete':
                            // Add your delete logic here
                            $wpdb->delete(
                                $table_name,
                                array('id' => $post_id),
                                array('%d')
                            );
                            break;
                    }
                }

                // Redirect to prevent form resubmission
                $redirect_url = add_query_arg(array(
                    'page' => 'wpwand-post-generator',
                    'bulk_action' => $action,
                    'processed' => count($post_ids)
                ), admin_url('admin.php'));

                wp_redirect($redirect_url);
                exit;
            }
        }

        // Show admin notice after bulk action
        if (isset($_GET['bulk_action']) && isset($_GET['processed'])) {
            $action = sanitize_text_field($_GET['bulk_action']);
            $processed = intval($_GET['processed']);

            $message = '';
            if ($action === 'approve') {
                $message = sprintf(__('%d items added to post successfully.', 'wp-wand-pro'), $processed);
            } elseif ($action === 'delete') {
                $message = sprintf(__('%d items deleted successfully.', 'wp-wand-pro'), $processed);
            }

            if ($message) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            }
        }
    }
    /**
     * Summary of post_generate_page
     * @return void
     */
    function post_generate_page()
    {

        // var_dump($get_limt);
        // if (!wpwand_pgs_rate_limi()) {
        //     echo 'You exceed rate limit';
        //     return;
        // }

        $this->delete_data();
        $this->approve_data();
        $this->bulk_action();

        $generated_content = $this->get_generated_content();
        $self_obj = $this;

        if (isset($_GET['restart-queue'])) {
            $this->restart_action_queue(true);
        }

        if (isset($_GET['generate-post']) && wpwand_pgs_rate_limi()) {
            include 'view/post-generator.php';
        } elseif (isset($_GET['view'])) {
            $this->view_page();
        } else {
            include 'view/generated-post.php';
        }

        return;




        if (isset($_POST['topic']) && isset($_POST['post_count'])) {
            $topic = sanitize_text_field($_POST['topic']);
            $post_count = intval($_POST['post_count']);

            // Generate posts based on the input values
            for ($i = 1; $i <= $post_count; $i++) {
            }
        }
?>
        <div class="wrap">
            <?php if (isset($_GET['view'])):
                $this->view_page() ?>

            <?php else: ?>
                <div class="wpwand-post-generate-page">
                    <form action="" method="post" id="wpwand-post-generate-form">
                        <input type="text" name="topic" placeholder="Topic" />
                        <input type="number" name="post_count" placeholder="Post to generate" />
                        <?php wp_nonce_field('post_generate_nonce_action', 'post_generate_nonce'); ?>
                        <button type="submit">Submit</button>
                    </form>

                    <form action="" method="post" id="wpwand-post-content-generate-form">
                        <button type="submit">Submit</button>
                    </form>
                    <div class="wpwand-generated-content">
                        <ul>
                            <?php
                            if ($generated_content):
                                foreach ($generated_content as $content): ?>
                                    <li>
                                        <a href="<?php echo admin_url('admin.php?page=wpwand-post-generator&view=' . $content['id']) ?>">
                                            <?php echo esc_html($content['title']); ?>
                                        </a>
                                    </li>
                            <?php endforeach;
                            endif; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    function view_page()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwand_generated_post';

        $id = isset($_GET['view']) ? intval($_GET['view']) : 0;
        $row = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $id", ARRAY_A);

        if ($row):
            $title = $row['title'];
            $content = $row['content'];

            $Parsedown = new \Parsedown();


            // Render the view page
        ?>
            <div class="wrap" id="wpwand-bulk-post-generator">
                <div class="wpwand-history-page wpwand-history-details">
                    <h1>
                        <?php echo esc_html($title) ?>
                    </h1>
                    <?php $this->limit_text(); ?>

                    <div class="wpwand-history-metas">
                        <div class="wpwand-history-meta"><strong>Creation Date:</strong> <span>
                                <?php echo gmdate("g:i a - F j, Y", strtotime($row['created_at'])) ?>
                            </span>
                        </div>

                        <a class="wpwand-pgdc-approve-button"
                            href="<?php echo admin_url('admin.php?page=wpwand-post-generator&action=wpwand-post-approve&id=' . $row['id']) ?>">Add
                            to Post</a>

                        <a href="<?php echo admin_url('admin.php?page=wpwand-post-generator&action=wpwand-post-delete&id=' . $row['id']) ?>"
                            class="wpwand-history-btn delete">Remove</a>
                    </div>
                    <div class=" wpwand-history-content">
                        <?php echo $content ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="wrap" id="wpwand-bulk-post-generator">

                <div class=" wpwand-history-content">

                    <h1>View AI History</h1>
                    <p>No data found.</p>
                </div>
            </div>

        <?php endif;
    }

    function limit_text()
    {

        $get_limt = (int) get_option('wpwand_pgc_limit', 10);
        $total_generated = (int) get_option('wpwand_pgc_total_bulk_generated', 0);

        $text = -1 == $get_limt ? 'Unlimited' : $total_generated . "/" . $get_limt;
        ?>
        <span class="wpwand-pgs-limit-text">
            <strong>
                <?php echo esc_html__('Bulk Generation Left :', 'wp-wand-pro') ?>
            </strong>
            <?php echo $text ?>
        </span>
<?php
    }
}



$pgc = new Post_Generator();
