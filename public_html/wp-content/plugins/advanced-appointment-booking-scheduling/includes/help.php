<?php
if (!defined('ABSPATH')) {
    exit;
}

function abp_render_help_page()
{
    ?>
    <div class="wrap abp-help-page">
        <div class="abp-help-content">
            <div class="help-section">
                <h2>🎯 Overview</h2>
                <p>
                    <strong>Version:</strong>
                    <?php echo esc_html(ABP_VERSION); ?> |
                    <strong>Author:</strong>
                    <?php echo esc_html(ABP_AUTHOR); ?>
                </p>
                <p>Effortless appointment management with automated booking confirmations, customizable forms, and staff
                    scheduling. Perfect for health, beauty, fitness, professional services, and more.</p>
            </div>

            <div class="help-section">
                <h2>🌐 Frontend Usage - Shortcodes</h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Shortcode</th>
                            <th>Purpose</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>[appointment_login_form]</code></td>
                            <td>User login form</td>
                        </tr>
                        <tr>
                            <td><code>[appointment_register_form]</code></td>
                            <td>User registration</td>
                        </tr>
                        <tr>
                            <td><code>[abp_bookings_page]</code></td>
                            <td>User\'s booking history &amp; cancellations</td>
                        </tr>
                        <tr>
                            <td><code>[book_appointment_form]</code></td>
                            <td>Main booking form (service/date/time/details)</td>
                        </tr>
                    </tbody>
                </table>
                <p><strong>Embed anywhere:</strong> Pages, posts, widgets.</p>
            </div>

            <div class="help-section">
                <h2>⚙️ Admin Interface</h2>
                <p>Navigate to <strong>Appointments</strong> (dashicons-calendar-alt):</p>
                <ul>
                    <li><strong>Dashboard:</strong> Stats (total/approved appointments, services, customers)</li>
                    <li><strong>Bookings:</strong> View/update/delete appointments, change status
                        (pending/approved/canceled)</li>
                    <li><strong>Services:</strong> Manage services (<a href="?page=appointment-bookings&tab=services"
                            target="_blank" rel="noopener noreferrer">➡️</a>)</li>
                    <li><strong>Customers:</strong> User management (<a href="?page=appointment-bookings&tab=customers"
                            target="_blank" rel="noopener noreferrer">➡️</a>)</li>
                    <li><strong>Staff:</strong> Add staff, assign services (<a href="?page=appointment-bookings&tab=staff"
                            target="_blank" rel="noopener noreferrer">➡️</a>)</li>
                </ul>
            </div>

            <div class="help-section">
                <h2>🆘 Support &amp; Templates</h2>

                <ul>
                    <li>
                        <strong>Our Templates:</strong>
                        <a href="<?php echo admin_url('admin.php?page=appointment-booking-themes'); ?>" class="abp-help-our-template"
                            >
                            Click here to explore <span>➡️</span>
                        </a>
                        <br><br>
                        Explore our extensive collection of premium WordPress themes across
                        <strong>84+ categories</strong>, designed to suit a wide range of industries and use cases.

                        Our themes cover popular niches such as Construction, Automobiles,
                        Cinema & Entertainment, Business & Corporate, Health & Fitness,
                        E-commerce, and many more—ensuring you’ll find the perfect design
                        for any project.
                    </li>
                </ul>

                <p>
                    <a href="<?php echo esc_url(ABP_MAIN_URL); ?>" target="_blank">Themespride.com</a> |
                    Browse all categories and discover the perfect theme for your needs.
                </p>
            </div>

            <div class="help-section">
                <h2>✨ Key Features</h2>
                <ul>
                    <li>✅ Staff role &amp; assignment (via Staff tab)</li>
                    <li>✅ Mobile-responsive forms &amp; admin</li>
                    <li>✅ Nonce-secured forms &amp; AJAX summary</li>
                    <li>✅ Status management (pending/approved/canceled)</li>
                    <li>✅ User dashboard for cancellations</li>
                    <li>✅ Service pricing &amp; durations</li>
                </ul>
            </div>

            <div class="help-section">
                <h2>🏢 Business Use Cases</h2>
                <p>Health clinics, salons, gyms, lawyers, tutors, home services, freelancers, etc.</p>
            </div>


        </div>
    </div>
    <?php
}

