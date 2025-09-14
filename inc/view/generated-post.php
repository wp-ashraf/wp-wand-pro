<?php
global $wpdb;
$table_name = $wpdb->prefix . 'wpwand_generated_post';
$items_per_page = 20;
$process_running = wpwand_as_process_running();

$current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$offset = ($current_page - 1) * $items_per_page;
$total_queue = (int) get_option('wpwand_pgc_total_queue', 0);
$total_completed = (int) get_option('wpwand_pgc_task_completed', 0);

$restart_queue = get_option('wpwand_restart_needed', false);

$search = (isset($_GET['s'])) ? sanitize_text_field($_GET['s']) : '';

$post_id = 0;
// $where = "post_id = $post_id"; // Initialize the WHERE condition
// if (!empty($search)) {
//     $where .= " AND (title LIKE '%$search%' OR response LIKE '%$search%')";
// }

$query = "SELECT * FROM $table_name  WHERE post_id = 0 ORDER BY created_at DESC LIMIT $offset, $items_per_page";
$data = $wpdb->get_results($query, ARRAY_A);

// var_dump($data);

// echo get_option('wpwand_schedule_working');


?>


<div class="wrap wpwand-history-page" id="wpwand-bulk-post-generator">

    <h1><?php _e('Bulk Posts', 'wp-wand-pro'); ?> <small><?php _e('Beta 1.0.1', 'wp-wand-pro'); ?></small></h1>
    <!-- <div class="wpwand-bulk-generation-info"> -->

    <?php if (wpwand_pgs_rate_limi()): ?>
        <a class="wpwand-pgdc-create-button"
            href="<?php echo admin_url('admin.php?page=wpwand-post-generator&generate-post') ?>"><?php _e('Create Bulk Posts', 'wp-wand-pro'); ?></a>
    <?php else: ?>
        <a class="wpwand-pgdc-create-button locked" href="https://wpwand.com/pro-plugin" target="_blank"><?php _e('Upgrade for Unlimited Bulk Generation', 'wp-wand-pro'); ?></a>
    <?php endif; ?>
    <?php $this->limit_text(); ?>


    <!-- </div> -->

    <div class="wpwand-pgdc-wrap">
        <?php if ($restart_queue): ?>
            <p style="float:right;"><?php _e('Bulk generation failed? try to', 'wp-wand-pro'); ?> <a
                    href="<?php echo admin_url('admin.php?page=wpwand-post-generator&restart-queue') ?>"><?php _e('Restart Queue', 'wp-wand-pro'); ?></a></p>
        <?php endif; ?>
        <?php if ($data): ?>
            <div class=" wpwand-pgdc-page">
                <?php if ($process_running): ?>
                    <div class="wpwand-pgdc-header process-running">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48">
                            <g transform="translate(0 0)">
                                <g class="nc-icon-wrapper" fill="#21b43e">
                                    <g class="nc-loop_bars-rotate-48" transform="rotate(45 24 24)">
                                        <rect x="21" y="0" fill="#21b43e" width="6" height="10"></rect>
                                        <rect x="34.4" y="5.6" transform="matrix(0.7071 0.7071 -0.7071 0.7071 18.435 -23.3762)"
                                            fill="#21b43e" width="6" height="10" style="opacity: 0.4;"></rect>
                                        <rect x="38" y="21" fill="#21b43e" width="10" height="6" style="opacity: 0.4;"></rect>
                                        <rect x="34.4" y="32.4"
                                            transform="matrix(-0.7071 0.7071 -0.7071 -0.7071 90.3762 37.435)" fill="#21b43e"
                                            width="6" height="10" style="opacity: 0.4;"></rect>
                                        <rect x="21" y="38" fill="#21b43e" width="6" height="10" style="opacity: 0.4;"></rect>
                                        <rect x="7.6" y="32.4" transform="matrix(-0.7071 -0.7071 0.7071 -0.7071 -8.435 71.3762)"
                                            fill="#21b43e" width="6" height="10" style="opacity: 0.4;"></rect>
                                        <rect x="0" y="21" fill="#21b43e" width="10" height="6" style="opacity: 0.6;"></rect>
                                        <rect x="7.6" y="5.6" transform="matrix(0.7071 -0.7071 0.7071 0.7071 -4.3762 10.565)"
                                            fill="#21b43e" width="6" height="10" style="opacity: 0.8;"></rect>
                                    </g>
                                    <script>
                                        ! function() {
                                            function t(t) {
                                                this.element = t;
                                                this.animationId = null;
                                                this.start = null;
                                                this.init();
                                            }

                                            if (!window.requestAnimationFrame) {
                                                var i = null;
                                                window.requestAnimationFrame = function(t, n) {
                                                    var e = (new Date).getTime();
                                                    i || (i = e);
                                                    var a = Math.max(0, 16 - (e - i)),
                                                        o = window.setTimeout(function() {
                                                            t(e + a);
                                                        }, a);
                                                    return i = e + a, o;
                                                };
                                            }

                                            t.prototype.init = function() {
                                                this.animationId = window.requestAnimationFrame(this.triggerAnimation.bind(this));
                                            };

                                            t.prototype.reset = function() {
                                                window.cancelAnimationFrame(this.animationId);
                                            };

                                            t.prototype.triggerAnimation = function(t) {
                                                if (!this.start) this.start = t;
                                                var n = t - this.start;
                                                if (n > 800) this.start = this.start + 800;
                                                this.element.setAttribute(
                                                    "transform",
                                                    "rotate(" + (parseInt(Math.min(n / 100, 8)) % 8 * 45) + " 24 24)"
                                                );
                                                if (document.documentElement.contains(this.element)) {
                                                    window.requestAnimationFrame(this.triggerAnimation.bind(this));
                                                }
                                            };

                                            var n = document.getElementsByClassName("nc-loop_bars-rotate-48"),
                                                e = [];
                                            if (n) {
                                                for (var a = 0; a < n.length; a++) {
                                                    (function(i) {
                                                        e.push(new t(n[i]));
                                                    })(a);
                                                }
                                            }

                                            document.addEventListener("visibilitychange", function() {
                                                if (document.visibilityState === "hidden") {
                                                    e.forEach(function(t) {
                                                        t.reset();
                                                    });
                                                } else {
                                                    e.forEach(function(t) {
                                                        t.init();
                                                    });
                                                }
                                            });
                                        }();
                                    </script>
                                </g>
                            </g>
                        </svg>
                        <div class="wpwand-pgdc-header-content">
                            <h1>
                                <?php _e('Generating Bulk Post...', 'wp-wand-pro'); ?>
                            </h1>
                            <p><?php _e('You can leave this page if you want. Posts are generating in the background.', 'wp-wand-pro'); ?></p>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- <div class="wpwand-pgdc-header">

                        <h1> Generated Content</h1>
                    </div> -->
                <?php endif; ?>
                <form action="" method="post">
                    <div class="wpwand-history-bulk-action">
                        <select name="wpwand-bulk-action-top" id="bulk-action-selector-top">
                            <option value="-1"><?php _e('Bulk actions', 'wp-wand-pro'); ?></option>
                            <option value="approve"><?php _e('Approve', 'wp-wand-pro'); ?></option>
                            <option value="delete"><?php _e('Delete', 'wp-wand-pro'); ?></option>
                        </select>
                        <input type="submit" name="doaction" id="doaction" class="wpwand-history-btn action" value="<?php _e('Apply', 'wp-wand-pro'); ?>">
                    </div>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th class="manage-column wpwand-select-post" width="2%"><input type="checkbox" id="wpwand-select-all" name="wpwand-select-all"></th>
                                <th class="manage-column" width="20%"><?php _e('Title', 'wp-wand-pro'); ?></th>
                                <th class="manage-column" width="30%"><?php _e('Content', 'wp-wand-pro'); ?></th>
                                <th class="manage-column"><?php _e('Creation Date', 'wp-wand-pro'); ?></th>
                                <th class="manage-column"><?php _e('Status', 'wp-wand-pro'); ?></th>
                                <th class="manage-column" width="20%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                                <?php
                                $actions_data = wpwand_as_data((int) $row['action_id']);
                                if (isset($actions_data['status'])) {

                                    if ('complete' == $actions_data['status']) {
                                        $status = '<span class="status done">' . esc_html__('Complete', 'wp-wand-pro') . '</span>';
                                    } elseif ('pending' == $actions_data['status']) {
                                        $status = '<span class="status pending">' . esc_html__('Pending', 'wp-wand-pro') . '</span>';
                                    } elseif ('in-progress' == $actions_data['status']) {
                                        $status = '<span class="status in-progress">' . esc_html__('In Progress', 'wp-wand-pro') . '</span>';
                                    } else {
                                        $status = '<span class="status failed">' . esc_html__('Failed', 'wp-wand-pro') . '</span>';
                                    }
                                } else {
                                    if ('done' == $row['status']) {
                                        $status = '<span class="status done">' . esc_html__('Complete', 'wp-wand-pro') . '</span>';
                                    } elseif ('pending' == $row['status']) {
                                        $status = '<span class="status pending">' . esc_html__('Pending', 'wp-wand-pro') . '</span>';
                                    } else {
                                        $status = '<span class="status failed">' . esc_html__('Failed', 'wp-wand-pro') . '</span>';
                                    }
                                }
                                ?>
                                <tr data-id="<?php echo $row['id'] ?>">
                                    <td class="wpwand-select-post"><input type="checkbox" name="wpwand-select-post[]" value="<?php echo $row['id'] ?>" class=""></td>
                                    <td><a href="<?php echo admin_url('admin.php?page=wpwand-post-generator&view=' . $row['id']) ?>">
                                            <?php echo wp_trim_words($row['title'], 5, '...') ?>
                                        </a>


                                    </td>
                                    <td>
                                        <?php echo wp_trim_words($row['content'], 10, '...') ?>
                                    </td>
                                    <td>
                                        <?php echo gmdate("g:i a : M j, Y", strtotime($row['created_at'])) ?>
                                    </td>
                                    <td>
                                        <?php echo $status ?>
                                    </td>

                                    <td class="wpwand-history-action">
                                        <a href="<?php echo admin_url('admin.php?page=wpwand-post-generator&view=' . $row['id']) ?>"
                                            class="wpwand-history-btn ">
                                            <?php echo 'View' ?>
                                        </a>
                                        <a href="<?php echo admin_url('admin.php?page=wpwand-post-generator&action=wpwand-post-approve&id=' . $row['id']) ?>" class="wpwand-history-btn approve"><?php _e('Approve', 'wp-wand-pro'); ?></a>
                                        <a
                                            href="<?php echo admin_url('admin.php?page=wpwand-post-generator&action=wpwand-post-delete&id=' . $row['id']) ?>"
                                            class="wpwand-history-btn delete"><?php _e('Remove', 'wp-wand-pro'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="wpwand-history-bulk-action">
                        <select name="wpwand-bulk-action-bottom" id="bulk-action-selector-bottom">
                            <option value="-1"><?php _e('Bulk actions', 'wp-wand-pro'); ?></option>
                            <option value="approve"><?php _e('Approve', 'wp-wand-pro'); ?></option>
                            <option value="delete"><?php _e('Delete', 'wp-wand-pro'); ?></option>
                        </select>
                        <input type="submit" name="doaction2" id="doaction2" class="wpwand-history-btn action" value="<?php _e('Apply', 'wp-wand-pro'); ?>">
                    </div>
                </form>
            </div>


        <?php else: ?>
            <?php _e('No data found.', 'wp-wand-pro'); ?>
        <?php endif; ?>
    </div>

    <?php
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name ");
    $total_pages = ceil($total_items / $items_per_page);

    $pagination_args = array(
        'base' => add_query_arg('paged', '%#%'),
        'format' => '',
        'current' => $current_page,
        'total' => $total_pages,
        'prev_next' => true,
        'prev_text' => __('&laquo;', 'wp-wand-pro'),
        'next_text' => __('&raquo;', 'wp-wand-pro'),
    );

    // if(1 > $total_pages):
    ?>
    <div class="pagination-wrap">
        <?php echo paginate_links($pagination_args) ?>
    </div>
    <?php //endif; 
    ?>
</div>