<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
// phpcs:disable WordPress.DB.DirectDatabaseQuery
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
global $wpdb;
$table_name = $wpdb->prefix . 'appointment_services';

function abp_get_services($force_update = false)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'appointment_services';

    $services = wp_cache_get('services_list', 'appointment_services');

    if ($force_update || false === $services) {
        $services = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}appointment_services");
        wp_cache_set('services_list', $services, 'appointment_services');
    }

    return $services;
}

function abp_get_service($service_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'appointment_services';
    $cache_key = 'service_' . intval($service_id);

    $service = wp_cache_get($cache_key, 'appointment_services');

    if (false === $service) {
        $service = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}appointment_services WHERE id = %d", $service_id));
        wp_cache_set($cache_key, $service, 'appointment_services');
    }

    return $service;
}

function abp_clear_services_cache()
{
    wp_cache_delete('services_list', 'appointment_services');
}

//service update messege

if (isset($_GET['tab']) && $_GET['tab'] === 'services' && isset($_GET['added']) && $_GET['added'] == 1) {
    echo '<div class="notice notice-success is-dismissible">
        <p>Service added successfully.</p>
    </div>';
}

//end 

// Handle service deletion
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service_id'])) {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'delete_service')) {
        // wp_die('Nonce verification failed');
    }

    $service_id = intval($_POST['delete_service_id']);

    $wpdb->delete($table_name, ['id' => $service_id]);

    abp_clear_services_cache();

    echo '<div class="notice notice-success is-dismissible"><p>Service deleted successfully!</p></div>';
}

// Fetch service data if editing
$edit_service = null;
if (isset($_GET['edit']) && isset($_GET['id']) && !empty($_GET['id'])) {
    $edit_service = abp_get_service(intval($_GET['id']));
}
?>

<h3><?php echo $edit_service ? 'Edit Service' : ''; ?></h3>


<form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="<?php echo $edit_service ? 'update_service' : 'add_service'; ?>">
    <?php wp_nonce_field($edit_service ? 'update_service' : 'save_service'); ?>



    <?php if ($edit_service): ?>
        <input type="hidden" name="service_id" value="<?php echo esc_attr($edit_service->id); ?>">
    <?php endif; ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="service_name">Service Name:</label></th>
            <td><input name="service_name" id="service_name" type="text" required
                    value="<?php echo esc_attr($edit_service ? $edit_service->service_name : ''); ?>"></td>
        </tr>
        <tr>
            <th scope="row"><label for="duration">Duration (in minutes):</label></th>
            <td><input name="duration" id="duration" type="number" required
                    value="<?php echo esc_attr($edit_service ? $edit_service->duration : ''); ?>"></td>
        </tr>
        <tr>
            <th scope="row"><label for="price">Price:</label></th>
            <td><input name="price" id="price" type="text" required
                    value="<?php echo esc_attr($edit_service ? $edit_service->price : ''); ?>"></td>
        </tr>
        <tr>
            <th scope="row"><label for="description">Description:</label></th>
            <td><textarea name="description" id="description" style="min-width:230px;"
                    required ><?php echo esc_textarea($edit_service ? $edit_service->description : ''); ?></textarea>
            </td>
        </tr>
    </table>
    <p><input type="submit" class="button abp-btn-primary"
            value="<?php echo $edit_service ? 'Update Service' : 'Add Service'; ?>"></p>
</form>

<hr>

<h3>List of Services</h3>
<?php
// Fetch and display services
$services = abp_get_services(); // Cache is used here
// Display services table
if ($services) {
    echo '<table class="widefat fixed" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Service Name</th>
                <th>Duration</th>
                <th>Price</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>';
    foreach ($services as $service) {
        echo '<tr>
            <td>' . esc_html($service->id) . '</td>
            <td>' . esc_html($service->service_name) . '</td>
            <td>' . esc_html($service->duration) . ' mins</td>
            <td>$' . esc_html($service->price) . '</td>
            <td>' . esc_html($service->description) . '</td>

            <td>
                
                <a href="' . esc_url(admin_url('admin.php?page=appointment-bookings&tab=services&edit=1&id=' . esc_attr($service->id))) . '" class="button abp-btn-primary">Edit</a>


                <form method="POST" style="display:inline; margin-left: 5px;">
                    <input type="hidden" name="delete_service_id" value="' . esc_attr($service->id) . '">
                    
                    ' . wp_kses_post( wp_nonce_field("delete_service", "_wpnonce", true, false) ) . '

                    
                    <input type="submit" class="button button-secondary" value="Delete" onclick="return confirm(\'Are you sure you want to delete this service?\');">
                </form>
            </td>

        </tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<p>No services added yet.</p>';
}
// phpcs:enable
?>