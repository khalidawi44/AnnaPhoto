jQuery( document ).ready( function ($) {
    "use strict";
    
    // Get URL
    var getURL  = window.location.href,
        baseURL = getURL.substring(0, getURL.indexOf('/wp-admin') + 9);

    // Install and Activate
    $( '#bard-demo-content-inst' ).on( 'click', function() {

            $('#bard-demo-content-inst').html( 'Installing Import Plugin...' );

            var data = {
                action: 'bard_plugin_auto_activation'
            };

            wp.updates.installPlugin({
                slug: 'bard-extra',
                success: function(){
                    // Install royal-backup-reset plugin after bard-extra
                    $('#bard-demo-content-inst').html( 'Installing Backup Plugin...' );
                    wp.updates.installPlugin({
                        slug: 'royal-backup-reset',
                        success: function(){
                            // Both plugins installed, now activate
                            $.post(ajaxurl, data, function(response) {
                                $('#bard-demo-content-inst').html( 'Redirecting...' );
                                window.location.replace( baseURL + '/admin.php?page=bard-extra' );
                            });
                        },
                        error: function(xhr){
                            // royal-backup-reset may already be installed, proceed anyway
                            $.post(ajaxurl, data, function(response) {
                                $('#bard-demo-content-inst').html( 'Redirecting...' );
                                window.location.replace( baseURL + '/admin.php?page=bard-extra' );
                            });
                        }
                    });
                }
            });

        }
    );

    // Activate
    $( '#bard-demo-content-act' ).on( 'click', function() {

            $('#bard-demo-content-act').html( 'Installing Backup Plugin...' );

            var data = {
                action: 'bard_plugin_auto_activation'
            };

            // Install royal-backup-reset plugin before activating
            wp.updates.installPlugin({
                slug: 'royal-backup-reset',
                success: function(){
                    // Plugin installed, now activate both
                    $.post(ajaxurl, data, function(response) {
                        $('#bard-demo-content-act').html( 'Redirecting...' );
                        window.location.replace( baseURL + '/admin.php?page=bard-extra' );
                    });
                },
                error: function(xhr){
                    // royal-backup-reset may already be installed, proceed anyway
                    $.post(ajaxurl, data, function(response) {
                        $('#bard-demo-content-act').html( 'Redirecting...' );
                        window.location.replace( baseURL + '/admin.php?page=bard-extra' );
                    });
                }
            });

        }
    );

    $( '#bard-woocommerce-install').on('click', function() {
        $(this).html('Installing Woocommerce...');
    });

    $( '#bard-woocommerce-activate').on('click', function() {
        $(this).html('Activating Woocommerce...');
    });


    // TODO: News Magazine X Theme Installation (remove later)
    $('.newsx-theme-install').on('click', function() {
        let $button = $(this),
            confirmInstall = confirm('This action will install News Magazine X WordPress theme and redirect you to the Appearance > Themes page.\n\nPlease DO NOT close or refresh the page until the installation is complete.');

        if (!confirmInstall) {
            return;
        }

        // Change button text
        $button.text('Installing Theme...');
        
        // Check if theme is already installed
        if (wp.themes && wp.themes.data && wp.themes.data.themes && wp.themes.data.themes['news-magazine-x']) {
            // Theme exists, just activate and redirect
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bard_install_news_magazine_x_theme',
                    theme: 'news-magazine-x',
                    nonce: bard_about.nonce
                },
                success: function() {
                    window.location.href = 'themes.php';
                }
            });
            return;
        }
        
        // Theme not installed, install it first
        wp.updates.installTheme({
            slug: 'news-magazine-x',
            success: function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bard_install_news_magazine_x_theme',
                        theme: 'news-magazine-x',
                        nonce: bard_about.nonce
                    },
                    success: function() {
                        window.location.href = 'themes.php';
                    }
                });
            },
            error: function(xhr, ajaxOptions, thrownerror) {
                if ('folder_exists' === xhr.errorCode) {
                    // Theme is already installed, proceed with activation
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'bard_install_news_magazine_x_theme',
                            theme: 'news-magazine-x',
                            nonce: bard_about.nonce
                        },
                        success: function() {
                            window.location.href = 'themes.php';
                        }
                    });
                } else {
                    $button.text('Install Failed');
                    console.log('Theme installation failed:', xhr);
                }
            }
        });
    });

});