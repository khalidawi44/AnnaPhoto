<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
// phpcs:disable WordPress.DB.DirectDatabaseQuery
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
add_action('admin_post_update_service', 'abp_handle_service_update');
add_action('admin_post_add_service', 'abp_handle_service_add');
add_action('admin_post_nopriv_add_service', 'abp_handle_service_add');

function abp_handle_service_update() {
    global $wpdb;

    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_id'])) {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'update_service')) {
            wp_die('Nonce verification failed');
        }

        $table_name = $wpdb->prefix . 'appointment_services';
        $service_id = intval($_POST['service_id']);
        $service_name = isset($_POST['service_name']) ? sanitize_text_field(wp_unslash($_POST['service_name'])) : '';
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $description = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';

        $wpdb->update($table_name, [
            'service_name' => $service_name,
            'duration'     => $duration,
            'price'        => $price,
            'description'  => $description
        ], ['id' => $service_id]);

       wp_safe_redirect(admin_url('admin.php?page=appointment-bookings&tab=services&updated=1'));
        exit;
    }
}


//for add 

function abp_handle_service_add() {
    global $wpdb;

    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_name']) && !isset($_POST['service_id'])) {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'save_service')) {
            wp_die('Nonce verification failed');
        }

        $service_name = sanitize_text_field(wp_unslash($_POST['service_name']));
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $description = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';

        $table_name = $wpdb->prefix . 'appointment_services';

        $wpdb->insert($table_name, [
            'service_name' => $service_name,
            'duration'     => $duration,
            'price'        => $price,
            'description'  => $description
        ]);

       wp_safe_redirect(admin_url('admin.php?page=appointment-bookings&tab=services&added=1'));
        exit;
    }
}
// phpcs:enable


