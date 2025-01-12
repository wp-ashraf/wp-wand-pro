<?php
namespace WPWAND_PRO;

class WPWAND_History_Page
{
    function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    function add_admin_menu()
    {
        // add_submenu_page( 'wpwand', 'History', 'manage_options', 'wpwand-history', [$this, 'history_page'], '', 45 );
        add_submenu_page('wpwand', __('History', 'wp-wand-pro'), __('History', 'wp-wand-pro'), 'manage_options', 'wpwand-history', [$this, 'history_page']);

    }

    // display data in custom admin menu page
    function history_page()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwand_history';

        $this->handle_delete();
        $items_per_page = 20;
        $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $offset = ($current_page - 1) * $items_per_page;

        $search = (isset($_GET['s'])) ? sanitize_text_field($_GET['s']) : '';
        $where = '';
        if (!empty($search)) {
            $where = " WHERE template_name LIKE '%$search%' OR response LIKE '%$search%'";
        }

        $data = $wpdb->get_results("SELECT * FROM $table_name $where LIMIT $offset, $items_per_page", ARRAY_A);


        if ($data):
            ?>

            <div class="wrap">

                <div class="wpwand-history-page">
                    <?php
                    if (isset($_GET['view'])):
                        $this->view_page();
                    else: ?>
                        <h1>history</h1>
                        <!-- <form method="get" action="<?php echo admin_url('admin.php') ?>">
                <input type="hidden" name="page" value="wpwand-history" />
                <input type="text" name="s" value="<?php echo $search ?>" placeholder="Search..." />
                <input type="submit" value="Search" class="button" />
            </form> -->
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th class="manage-column" width="40%">Result</th>
                                    <th class="manage-column">Creation Date</th>
                                    <th class="manage-column">Template Used</th>
                                    <th class="manage-column"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data as $row): ?>
                                    <?php
                                    $content = json_decode($row['response']);
                                    $text = '';
                                    if (isset($content->choices)) {
                                        foreach ($content->choices as $choice) {
                                            $reply = isset($choice->message) ? $choice->message->content : $choice->text;
                                            $contents[] = $reply;
                                            $text .= $reply;
                                        }
                                    } elseif (isset($content->error)) {
                                        $text .= '<div class="wpwand-content wpwand-prompt-error">';
                                        $text .= wpwand_ai_error($content->error);
                                        $text .= '  </div>';
                                    }
                                    ?>
                                    <tr>
                                        <td><a href="<?php echo admin_url('admin.php?page=wpwand-history&view=' . $row['id']) ?>"><?php echo wp_trim_words($text, 10, '...') ?></a></td>
                                        <td>
                                            <?php echo gmdate("g:i a - F j, Y", strtotime($row['created_at'])) ?>
                                        </td>
                                        <td>
                                            <?php echo $row['template_name'] ?>
                                        </td>

                                        <td class="wpwand-history-action"><a
                                                href="<?php echo admin_url('admin.php?page=wpwand-history&view=' . $row['id']) ?>"
                                                class="wpwand-history-btn "><?php echo 'View' ?></a><a
                                                href="<?php echo admin_url('admin.php?page=wpwand-history&action=wpwand-history-delete&id=' . $row['id']) ?>"
                                                class="wpwand-history-btn delete">Remove</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php
                        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where");
                        $total_pages = ceil($total_items / $items_per_page);

                        $pagination_args = array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'current' => $current_page,
                            'total' => $total_pages,
                            'prev_next' => true,
                            'prev_text' => __('&laquo;', 'text-domain'),
                            'next_text' => __('&raquo;', 'text-domain'),
                        );

                        ?>
                        <div class="pagination-wrap">
                            <?php echo paginate_links($pagination_args) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            No data found.
        <?php endif;
    }

    function view_page()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpwand_history';

        $id = isset($_GET['view']) ? intval($_GET['view']) : 0;
        $row = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $id", ARRAY_A);

        if ($row):
            $prompt_data = $row['prompt_info'] ? json_decode($row['prompt_info']) : '';
            $is_markdown = isset($prompt_data->markdown) ? $prompt_data->markdown : false;
            $markdown_class = $is_markdown == 1 ? 'wpwand-markdown' : '';
            $template_name = $row['template_name'];
            $response = $row['response'];

            $content = json_decode($row['response']);
            $text = '';
            if (isset($content->choices)) {
                foreach ($content->choices as $choice) {
                    $reply = isset($choice->message) ? $choice->message->content : $choice->text;
                    $contents[] = $reply;
                    $text .= $reply;
                }
            } elseif (isset($content->error)) {
                $text .= '<div class="wpwand-content wpwand-prompt-error">';
                $text .= wpwand_ai_error($content->error);
                $text .= '  </div>';            }

            // Render the view page
            ?>
            <div class="wpwand-history-details">
                <h1>Result Preview</h1>
                <div class="wpwand-history-metas">
                    <div class="wpwand-history-meta"><strong>Creation Date:</strong> <span>
                            <?php echo gmdate("g:i a - F j, Y", strtotime($row['created_at'])) ?>
                        </span></div>
                    <div class="wpwand-history-meta"><strong>Template Used::</strong> <span>
                            <?php echo $template_name ?>
                        </span></div>
                    <a href="<?php echo admin_url('admin.php?page=wpwand-history&action=wpwand-history-delete&id=' . $row['id']) ?>"
                        class="wpwand-history-btn delete">Remove</a>
                </div>
                <div class="<?php echo esc_attr($markdown_class) ?> wpwand-history-content"><?php echo $text ?></div>
            </div>
        <?php else: ?>

            <h1>View AI History</h1>
            <p>No data found.</p>

        <?php endif;
    }


    // handle delete action
    function handle_delete()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'wpwand-history-delete') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'wpwand_history';
            $wpdb->delete($table_name, array('id' => $_GET['id']));
        }

    }

    // call the handle delete function

}

$wpwand_history = new WPWAND_History_Page();