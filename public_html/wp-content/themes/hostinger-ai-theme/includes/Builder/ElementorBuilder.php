<?php

namespace Hostinger\AiTheme\Builder;

use Hostinger\AffiliatePlugin\Admin\Options\PluginOptions;
use Hostinger\AffiliatePlugin\Admin\PluginSettings;
use Plugin_Upgrader;
use WP_Ajax_Upgrader_Skin;
use WP_Error;

defined( 'ABSPATH' ) || exit;

class ElementorBuilder {
    public function boot(): void {
        if ( ! $this->is_enabled() ) {
            return;
        }

        $this->enable_plugin();
    }

    public function enable_plugin(): void {
        if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $installed_plugins = get_plugins();

        if ( !in_array( 'elementor/elementor.php', $installed_plugins, true ) ) {
            $this->install_plugin();
            return;
        }

        activate_plugin( 'elementor/elementor.php' );
    }

    public function install_plugin(): null|WP_Error {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        include_once ABSPATH . 'wp-admin/includes/plugin.php';

        $skin     = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader( $skin );

        $install_plugin = $upgrader->install( 'https://downloads.wordpress.org/plugin/elementor.zip' );

        if ( is_wp_error( $install_plugin ) ) {
            error_log( 'Hostinger AI Theme: ' . print_r( $install_plugin, true ) );
            return null;
        }

        update_option( 'elementor_onboarded', 1 );

        return activate_plugin( 'elementor/elementor.php' );
    }

    private function is_enabled(): bool {
        return get_option( 'hostinger_ai_builder_type', 'gutenberg' ) === 'elementor';
    }

    private function is_plugin_active(): bool {
        return is_plugin_active( 'elementor/elementor.php' );
    }
}
