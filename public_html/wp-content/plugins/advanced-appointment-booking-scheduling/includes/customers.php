<?php
if (!defined('ABSPATH')) {
    exit;
}
// phpcs:disable WordPress.DB.DirectDatabaseQuery
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
global $wpdb;

// Handle Delete Action
if (isset($_GET['action'], $_GET['user_id'], $_GET['_wpnonce']) && $_GET['action'] === 'delete' && current_user_can('delete_users')) {
    $user_id = intval($_GET['user_id']);

    if (wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'delete_customer_' . $user_id)) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($user_id);
        echo '<div class="notice notice-success"><p>User deleted successfully.</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
    }
}

// Fetch all users
$users = $wpdb->get_results("
    SELECT u.ID, u.user_login, u.user_email, u.display_name, COUNT(a.id) as total_appointments
    FROM {$wpdb->users} u
    LEFT JOIN {$wpdb->prefix}appointment_booking a ON u.ID = a.user_id
    GROUP BY u.ID
");

if ($users) {
    echo '<table class="widefat fixed" cellspacing="0">
        <thead>
            <tr>
                <th>User ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Display Name</th>
                <th>Total Appointments</th>
                
            </tr>
        </thead>
        <tbody>';

    foreach ($users as $user) {
        echo '<tr>
            <td>' . esc_html($user->ID) . '</td>
            <td>' . esc_html($user->user_login) . '</td>
            <td>' . esc_html($user->user_email) . '</td>
            <td>' . esc_html($user->display_name) . '</td>
            <td>' . esc_html($user->total_appointments) . '</td>
           
        </tr>';
    }

    echo '</tbody></table>';
} else {
    echo '<p>No customers found.</p>';
}
// phpcs:enable
