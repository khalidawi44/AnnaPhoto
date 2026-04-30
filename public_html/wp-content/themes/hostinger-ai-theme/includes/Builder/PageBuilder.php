<?php

namespace Hostinger\AiTheme\Builder;

defined( 'ABSPATH' ) || exit;

class PageBuilder {
    /**
     * @var string
     */
    private array $content_data;
    private string $type;

    /**
     * @param array $content_data
     */
    public function __construct( array $content_data ) {
        $this->content_data = $content_data;
        $this->type = get_option( 'hostinger_ai_builder_type', 'gutenberg' );
    }

    /**
     * @return array
     */
    public function build_pages(): array {
        $pages = array();

        foreach($this->content_data['pages'] as $page => $page_data) {
            if($page === 'ecommercePagesGroup') {
                continue;
            }

            switch($this->type) {
                default:
                case 'gutenberg':

                    $page_content = '';

                    if(!empty($page_data['sections'])) {
                        foreach ($page_data['sections'] as $section) {
                            $content_parser = new ContentParser( $section );

                            $page_content .= $content_parser->output();
                        }
                    }

                    $page_clean = trim( $page );

                    $page_title = mb_convert_case( $page_clean, MB_CASE_TITLE, "UTF-8" );

                    $new_page = array(
                        'post_title'    => $page_title,
                        'post_content'  => $page_content,
                        'post_status'   => 'publish',
                        'post_type'     => 'page',
                    );

                    $page_id = wp_insert_post($new_page);

                    if(!empty($page_id)) {
                        $pages[$page] = array(
                            'title' => $page_title,
                            'page_id' => $page_id,
                        );
                    }

                    break;
                case 'elementor':
                    $elementor_data = [];

                    if(!empty($page_data['sections'])) {
                        foreach ($page_data['sections'] as $section) {
                            $content_parser = new ContentParser( $section );
                            $output         = $content_parser->output();
                            if (is_array($output)) {
                                $elementor_data = array_merge($elementor_data, $output);
                            }
                        }
                    }

                    $page_clean = trim( $page );

                    $page_title = mb_convert_case( $page_clean, MB_CASE_TITLE, "UTF-8" );

                    $new_page = array(
                        'post_title'    => $page_title,
                        'post_content'  => '',
                        'post_status'   => 'publish',
                        'post_type'     => 'page',
                    );

                    $page_id = wp_insert_post($new_page);

                    if(!empty($page_id)) {
                        $pages[$page] = array(
                            'title' => $page_title,
                            'page_id' => $page_id,
                        );

                        update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
                        update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
                        update_post_meta( $page_id, '_elementor_version', '3.31.3' );
                        update_post_meta( $page_id, '_elementor_data', wp_slash( json_encode( $elementor_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ));
                    }

                break;
            }
        }

        update_option( 'hostinger_ai_created_pages', $pages );

        return $pages;
    }
}
