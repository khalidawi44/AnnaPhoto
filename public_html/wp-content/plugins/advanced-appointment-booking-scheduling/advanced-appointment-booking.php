<?php
/*
Plugin Name:       Advanced Appointment Booking & Scheduling
Plugin URI:
Description:       Advanced Appointment Booking & Scheduling: Effortlessly manage appointments with a simple, user-friendly scheduling system.
Version:           2.3
Requires at least: 5.2
Requires PHP:      7.2
Author:            themespride
Author URI:        https://www.themespride.com/
Text Domain:       advanced-appointment-booking
License:           GPL-2.0+
*/


// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

define('ABP_VERSION', '2.3');
define('ABP_AUTHOR', 'themespride');
define('ABP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ABP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ABP_LICENCE_API_ENDPOINT', 'https://license.themespride.com/api/general/');
define('ABP_MAIN_URL', 'https://www.themespride.com/');
// phpcs:disable WordPress.DB.DirectDatabaseQuery
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
include_once(plugin_dir_path(__FILE__) . 'includes/class-appointment-admin.php');
include_once(plugin_dir_path(__FILE__) . 'includes/service-operations-handler.php');

register_activation_hook(__FILE__, 'abp_create_services_table');
function abp_create_services_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'appointment_services';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        service_name varchar(255) NOT NULL,
        duration int(11) NOT NULL,
        price decimal(10,2) NOT NULL,
        description text NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function abp_create_appointment_booking_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'appointment_booking';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        service_id bigint(20) NOT NULL,
        booking_date date NOT NULL,
        booking_time time NOT NULL,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        phone varchar(15) NOT NULL,
        price float NOT NULL,
        status varchar(20) DEFAULT 'pending',
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'abp_create_appointment_booking_table');

// new add
function abp_create_staff_table()
{
    global $wpdb;
    $table_name = esc_sql($wpdb->prefix . 'abp_staff');
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        service_ids TEXT,
        availability TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $row = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'user_id'");
    if (empty($row)) {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN user_id BIGINT(20) UNSIGNED NULL AFTER id");
    }
}
register_activation_hook(__FILE__, 'abp_create_staff_table');


function abp_alter_user_id_staff_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'abp_staff';

    if (get_option('abp_staff_userid_column_added')) {
        return;
    }

    $row = $wpdb->get_results($wpdb->prepare("SHOW COLUMNS FROM %s LIKE %s", $table_name, 'user_id'));
    if (empty($row)) {
        $wpdb->query($wpdb->prepare("ALTER TABLE %s ADD COLUMN user_id BIGINT(20) UNSIGNED NULL AFTER id", $table_name));
    }
    update_option('abp_staff_userid_column_added', 1);
}
add_action('plugins_loaded', 'abp_alter_user_id_staff_table');

register_activation_hook(__FILE__, 'abp_create_appointment_booking_pages');

function abp_create_appointment_booking_pages()
{
    if (!get_page_by_path('login')) {
        wp_insert_post([
            'post_title' => 'Login',
            'post_name' => 'login',
            'post_content' => '[appointment_login_form]',
            'post_status' => 'publish',
            'post_type' => 'page'
        ]);
    }

    if (!get_page_by_path('register')) {
        wp_insert_post([
            'post_title' => 'Register',
            'post_name' => 'register',
            'post_content' => '[appointment_register_form]',
            'post_status' => 'publish',
            'post_type' => 'page'
        ]);
    }

    if (!get_page_by_path('book-appointment')) {
        wp_insert_post([
            'post_title' => 'Book Appointment',
            'post_name' => 'book-appointment',
            'post_content' => '[book_appointment_form]',
            'post_status' => 'publish',
            'post_type' => 'page'
        ]);
    }

    if (!get_page_by_path('abp-bookings')) {
        wp_insert_post([
            'post_title' => 'Bookings',
            'post_name' => 'abp-bookings',
            'post_content' => '[abp_bookings_page]',
            'post_status' => 'publish',
            'post_type' => 'page'
        ]);
    }
}



add_action('admin_enqueue_scripts', 'abp_enqueue_admin_assets');
function abp_enqueue_admin_assets()
{
    $screen = get_current_screen();
    $abp_banner_style_version = filemtime(plugin_dir_path(__FILE__) . 'assets/css/abp-banner-style.css');

    wp_enqueue_style('abp-banner-style', plugins_url('/assets/css/abp-banner-style.css', __FILE__), [], $abp_banner_style_version);

    wp_enqueue_script(
        'abp-admin-clean-url',
        plugin_dir_url(__FILE__) . 'assets/js/admin.js',
        [],
        ABP_VERSION,
        true
    );

    if ($screen->id == 'toplevel_page_appointment-booking-themes' || $screen->id == 'appointments_page_appointment-bookings' || $screen->id == 'appointments_page_appointment-booking-help') {

        $data = ".notice.is-dismissible {
            display: none;
        }";

        wp_add_inline_style('abp-banner-style', $data);

    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (isset($_GET['page']) && ($_GET['page'] == 'appointment-booking-admin' || $_GET['page'] == 'appointment-bookings' || $_GET['page'] == 'appointment-booking-themes' || $_GET['page'] == 'appointment-booking-help')) {

        $style_version = filemtime(plugin_dir_path(__FILE__) . 'assets/css/style.css');
        $bootstrap_version = filemtime(plugin_dir_path(__FILE__) . 'assets/lib/bootstrap.js');
        wp_enqueue_style('abp-style', plugins_url('/assets/css/style.css', __FILE__), [], $style_version);
        wp_enqueue_style('abp-bootstrap-css', plugins_url('/assets/lib/bootstrap.css', __FILE__), [], $style_version);
        wp_enqueue_script(
            'abp-bootstrap-js',
            plugins_url('/assets/lib/bootstrap.js', __FILE__),
            [],
            $bootstrap_version,
            true
        );
    }

}
add_action('wp_enqueue_scripts', 'abp_enqueue_assets');
function abp_enqueue_assets()
{
    $style_version = filemtime(plugin_dir_path(__FILE__) . 'assets/css/abp-front.css');
    $script_version = filemtime(plugin_dir_path(__FILE__) . 'assets/js/booking.js');

    wp_enqueue_style(
        'flatpickr-css',
        plugins_url('/assets/css/flatpickr.min.css', __FILE__),
        [],
        $style_version
    );

    wp_enqueue_script(
        'flatpickr-js',
        plugins_url('/assets/js/flatpickr.min.js', __FILE__),
        [],
        $script_version,
        true
    );

    wp_enqueue_style('abp-style', plugins_url('/assets/css/abp-front.css', __FILE__), [], $style_version);

    wp_enqueue_script('abp-script', plugins_url('/assets/js/booking.js', __FILE__), ['jquery', 'flatpickr-js'], $script_version, true);
}

function abp_promo_admin_banner_notice()
{ ?>
    <div class="notice notice-info is-dismissible abp-promo-admin-banner">
        <div class="abp-promo-banner-content-block">
            <div class="abp-promo-banner-content-inner">
                <div class="abp-promo-banner-content">
                    <h3><?php echo esc_html('WordPress Theme Bundle'); ?></h3>
                    <p class="abp-promo-banner-info">
                        <?php echo esc_html('Get 120+ Premium WordPress Themes for Just $89!'); ?>
                    </p>
                    <p class="abp-flash-code"><?php echo esc_html('Exclusive Flash Sale 🔥 Use Code: '); ?><strong
                            id="abp-coupon"><?php echo esc_html('FLASH25'); ?></strong></p>
                    <a href="<?php echo esc_attr(ABP_MAIN_URL . 'products/wordpress-theme-bundle'); ?>"
                        target="_blank"><?php echo esc_html('Buy Now'); ?></a>
                </div>
                <div class="abp-promo-discount">
                    <div class="abp-extra">
                        <span><?php echo esc_html('Extra'); ?></span>
                        <span><?php echo esc_html('25%'); ?></span>
                        <span><?php echo esc_html('OFF'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <img src="<?php echo esc_url(plugins_url('includes/images/ban-plain.png', __FILE__)); ?>" alt="Theme Bundle Offer">
    </div>
    <?php
}
add_action('admin_notices', 'abp_promo_admin_banner_notice');
// phpcs:enable