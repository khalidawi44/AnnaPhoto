<?php

namespace Hostinger\AiTheme\Builder;

use DOMDocument;

defined( 'ABSPATH' ) || exit;

class ContentParser {
    /**
     * @var string
     */
    private array $section;
    private string $type;

    /**
     * @param array $content_data
     */
    public function __construct( array $section ) {
        $this->section = $section;
        $this->type = get_option( 'hostinger_ai_builder_type', 'gutenberg' );
    }

    /**
     * @return string
     */
    public function output() {
        if(empty($this->section['elements'])) {
            return $this->section['html'];
        }

        switch( $this->type ) {
            default:
            case 'gutenberg':
                return $this->output_gutenberg();
            case 'elementor':
                return $this->output_elementor();
        }
    }

    private function output_elementor(): array {
        $processor = new ElementProcessor( $this->section );
        $processor->setHelper( new Helper() );

        $json = $processor->prepare_json();

        return $json;
    }

    private function output_gutenberg(): string {
        $dom = new DOMDocument();
        @$dom->loadHTML($this->section['html'], LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);

        $processor = new ElementProcessor( $this->section );
        $processor->setHelper( new Helper() );

        $html = $processor->process( $dom );

        $blocks = parse_blocks( $html );

        $serialized = serialize_blocks( $blocks );

        // URL fix.
        $serialized = str_replace( '\u0026', '&', $serialized );

        // Dash fix.
        $serialized = str_replace( '\u002d', '-', $serialized );

        return $serialized;
    }
}
