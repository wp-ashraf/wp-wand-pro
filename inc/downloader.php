<?php
// File Security Check
if (!defined('ABSPATH')) {
    exit;
}

// Should be called only by WordPress.
defined('WPINC') || die;



function wpwand_th_enqueue_admin_script($hook)
{

    wp_enqueue_style('google-fonts-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
    wp_enqueue_style('wpwand-pro-plugin-setup', WPWAND_PRO_PLUGIN_URL . 'assets/css/tala.css');

    // wp_enqueue_script( 'donutty-jquery', get_plugin_file_uri( 'lib/plugin-setup/ass``ets/admin/js/donutty-jquery.min.js' ), ['jquery'], '1.0' );

    wp_enqueue_script('wpwand-pro-main-js', WPWAND_PRO_PLUGIN_URL . 'assets/js/tala.js', ['jquery'], '1.0');
    wp_localize_script('wpwand-pro-main-js', 'wpwand_pro_vars', [
        'nonce' => wp_create_nonce('wpwand-pro--nonce'),
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
}
add_action('admin_enqueue_scripts', 'wpwand_th_enqueue_admin_script');

function wpwand_pro_tala()
{
    $admin_notices = '';
    $screen = get_current_screen();

    $tala_key = get_option('wpwand_pro_tala_key', false) ? get_option('wpwand_pro_tala_key', false) : '';

    $is_activated_class = wpwand_pro_tala_check() ? 'tala-activated' : 'tala-deactivated';
    ob_start();
    ?>
    <div class="wpwand-pro-plugin-setup-popup-wrap wpwand-pro-tala <?php echo esc_attr($is_activated_class); ?>">
        <div class="wpwand-pro-plugin-setup-popup">
            <div class="wpwand-pro-setup-welcome">
                <div class="wpwand-pro-setup-header">
                    <h3>
                        <?php echo esc_html__('Get Full Access', 'wp-wand-pro') ?>
                    </h3>
                </div>
                <div class="wpwand-pro-tala-content">
                    <h3>
                        <?php echo esc_html__('Activate WP Wand', 'wp-wand-pro') ?>
                    </h3>
                    <p>
                        <?php echo __('You can find license key on your receipt email.', 'wp-wand-pro') ?>
                    </p>
                    <div class="wpwand-pro-tala-action">

                        <form action="#" class="wpwand-pro-tala-form">
                            <input name="tala_key" placeholder="<?php echo esc_attr__('Add license key', 'wp-wand-pro') ?>"
                                value="<?php echo esc_attr($tala_key) ?>">
                            <button class="wpwand-pro-check-tala wpwand-pro-button">
                                <?php echo esc_html__('Activate', 'wp-wand-pro') ?>
                            </button>
                        </form>
                    </div>
                </div>


            </div>

            <div class="wpwand-pro-setup-success">
                <div class="wpwand-pro-setup-header">
                    <svg width="53" height="53" viewBox="0 0 53 53" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="26.5" cy="26.5" r="26.5" fill="#15E280" />
                        <path d="M18.1099 28.61L22.5502 33.0503L33.6509 21.9496" stroke="white" stroke-width="3"
                            stroke-linecap="square" stroke-linejoin="round" />
                    </svg>

                    <h3>
                        <?php echo esc_html__('Plugin is activated', 'wp-wand-pro') ?>
                    </h3>
                    <p>
                        <?php echo esc_html__('You have successfully activated this plugin', 'wp-wand-pro') ?>
                    </p>
                </div>
                <div class="wpwand-pro-setup-action">
                    <a class="wpwand-pro-button wpwand-pro-tala-deactivate" href="">
                        <?php echo esc_html__('Deactivate', 'wp-wand-pro') ?>
                    </a>
                    </br>
                    <a class="wpwand-pro-button" href="<?php echo admin_url('admin.php?page=wpwand') ?>"><?php echo esc_html__('Explore pro features', 'wp-wand-pro') ?></a>

                    <!-- <h3><?php echo esc_html__('How to use our plugin', 'wp-wand-pro') ?></h3>
                    <iframe width="1280" height="720" src="https://www.youtube.com/embed/A1buvlF5r-M" title="How to Import Demo Data on Chetah WordPress Plugin" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe> -->
                </div>


            </div>
            <div class="wpwand-pro-setup-error">
                <div class="wpwand-pro-setup-header">
                    <svg width="53" height="53" viewBox="0 0 53 53" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="26.5" cy="26.5" r="26.5" fill="#FF3131" />
                        <path
                            d="M16.2671 31.9402L15.401 31.4402H15.401L16.2671 31.9402ZM35.4941 31.9402L36.3601 31.4402L35.4941 31.9402ZM27.8033 18.6194L26.9373 19.1194L27.8033 18.6194ZM23.9579 18.6194L24.8239 19.1194L23.9579 18.6194ZM26.8806 24.1697V23.1697H24.8806V24.1697H26.8806ZM24.8806 26.3899V27.3899H26.8806V26.3899H24.8806ZM25.8806 29.8302H24.8806V31.8302H25.8806V29.8302ZM25.8917 31.8302H26.8917V29.8302H25.8917V31.8302ZM26.9373 19.1194L34.6281 32.4402L36.3601 31.4402L28.6693 18.1194L26.9373 19.1194ZM33.5714 34.2705H18.1898V36.2705H33.5714V34.2705ZM17.1331 32.4402L24.8239 19.1194L23.0919 18.1194L15.401 31.4402L17.1331 32.4402ZM18.1898 34.2705C17.2505 34.2705 16.6635 33.2537 17.1331 32.4402L15.401 31.4402C14.1616 33.587 15.7109 36.2705 18.1898 36.2705V34.2705ZM34.6281 32.4402C35.0977 33.2537 34.5107 34.2705 33.5714 34.2705V36.2705C36.0503 36.2705 37.5996 33.587 36.3601 31.4402L34.6281 32.4402ZM28.6693 18.1194C27.4299 15.9726 24.3313 15.9726 23.0919 18.1194L24.8239 19.1194C25.2935 18.3059 26.4676 18.3059 26.9373 19.1194L28.6693 18.1194ZM24.8806 24.1697V26.3899H26.8806V24.1697H24.8806ZM25.8806 31.8302H25.8917V29.8302H25.8806V31.8302Z"
                            fill="white" />
                    </svg>
                    <h3>
                        <?php echo esc_html__('Purchase key is not valid', 'wp-wand-pro') ?>
                    </h3>
                    <p>
                        <?php echo esc_html__('The key you have provided is not valid or doesn’t exists.', 'wp-wand-pro') ?>
                    </p>
                </div>
                <div class="wpwand-pro-setup-action">
                    <a class="wpwand-pro-button" href="">
                        <?php echo esc_html__('Try Again', 'wp-wand-pro') ?>
                    </a>

                </div>


            </div>

        </div>
    </div>
    <?php
    $admin_notices .= ob_get_clean();

    printf($admin_notices);
}
function wpwand_pro_tala_ajax()
{
    check_ajax_referer('wpwand-pro--nonce', 'security');

    $key = isset($_POST['tala']) && !empty($_POST['tala']) ? $_POST['tala'] : '';
    if ($key) {

        $is_valid = wpwand_pro_check_tala($key);
        $is_agency = 'agency' === $is_valid ? true : false;
        if (true === $is_valid || $is_agency) {
            if ($is_agency) {
                update_option('wpwand_pro_tala_agency', $is_agency);
            }
            update_option('wpwand_pro_tala_key', $key);
            update_option('wpwand_pro_tala_status', 'activated');
            wpwand_pro_get_data();
            wp_send_json(true);
        }
        wp_send_json($is_valid);
    }

    wp_send_json(false);
}

add_action('wp_ajax_wpwand_pro_tala_ajax', 'wpwand_pro_tala_ajax');


function wpwand_pro_check_tala($key)
{

    if (!empty($key)) {

        $code = $key;

        // Surrounding whitespace can cause a 404 error, so trim it first
        $code = trim($code);
        // Make sure the code looks valid before sending it to Envato
        // if ( !preg_match( "/^([a-f0-9]{8})-(([a-f0-9]{4})-){3}([a-f0-9]{12})$/i", $code ) ) {
        //     $error = false;
        // } else {
        $home_url = urlencode(home_url());
        // Build the request

        $url = "https://tala.finestwp.co/wp-json/fdl/v2/envato-plugin?key={$code}&url={$home_url}&type=pluginCheck&plugin=68333";

        $response = wp_safe_remote_get($url);
        // $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body($response);
        $response = json_decode($response_body);
        // Send the request with warnings supressed
        return $response;
        // }
    }
    return false;
}

function wpwand_pro_check_tala_deactivate($key)
{

    if (!empty($key)) {

        $code = $key;

        // Surrounding whitespace can cause a 404 error, so trim it first
        $code = trim($code);
        // Make sure the code looks valid before sending it to Envato
        if (!preg_match("/^([a-f0-9]{8})-(([a-f0-9]{4})-){3}([a-f0-9]{12})$/i", $code)) {
            $error = false;
        } else {
            $home_url = urlencode(home_url());
            // Build the request
            $url = "https://tala.finestwp.co/wp-json/fdl/v2/envato-plugin?key={$code}&url={$home_url}&type=pluginDeactivate&plugin=68333";

            $response = wp_safe_remote_get($url);
            // $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body($response);
            $response = json_decode($response_body);
            // Send the request with warnings supressed
            return $response === true;
        }
    }
    return false;
}