<?php

namespace Hostinger\AiTheme\Builder;

use DOMDocument;
use DOMXPath;
use Hostinger\AiTheme\Builder\ElementHandlers\BackgroundImageHandler;
use Hostinger\AiTheme\Builder\ElementHandlers\ButtonHandler;
use Hostinger\AiTheme\Builder\ElementHandlers\CoverImageHandler;
use Hostinger\AiTheme\Builder\ElementHandlers\ImageHandler;
use Hostinger\AiTheme\Builder\ElementHandlers\TitleHandler;

defined( 'ABSPATH' ) || exit;

class ElementProcessor {
    /**
     * @var array
     */
    protected array $handlers = [];

    /**
     * @var string
     */
    private array $section;

    /**
     * @var Helper
     */
    private Helper $helper;

    private string $builder_type;

    /**
     * @param array $section
     */
    public function __construct( array $section ) {
        $this->builder_type = get_option( 'hostinger_ai_builder_type', 'gutenberg' );

        $handler_types = array(
            'title' => new TitleHandler( $this->builder_type ),
            'button' => new ButtonHandler( $this->builder_type ),
            'background-image' => new BackgroundImageHandler( $this->builder_type ),
            'image' => new ImageHandler( $this->builder_type ),
            'cover-image' => new CoverImageHandler( $this->builder_type ),
        );

        $handlers_classes = [
            'hostinger-ai-title' => 'title',
            'hostinger-ai-subtitle' => 'title',
            'hostinger-ai-cta-button' => 'button',
            'hostinger-ai-project-title' => 'title',
            'hostinger-ai-service-title' => 'title',
            'hostinger-ai-testimonial-text' => 'title',
            'hostinger-ai-service-description' => 'title',
            'hostinger-ai-project-description' => 'title',
            'hostinger-ai-description' => 'title',
            'hostinger-ai-testimonial-image' => 'image',
            'hostinger-ai-image' => 'image',
            'hostinger-ai-service-image' => 'image',
            'hostinger-ai-project-image' => 'image',
            'hostinger-ai-background-image' => 'background-image',
            'hostinger-ai-card-title' => 'title',
            'hostinger-ai-card-description' => 'title',
            'hostinger-ai-card-price' => 'title',
            'hostinger-ai-workplace' => 'title',
            'hostinger-ai-date' => 'title',
            'hostinger-ai-cover-image' => 'cover-image',
        ];

        foreach($handlers_classes as $handler_class => $handler_type) {
            $this->handlers[$handler_class] = $handler_types[$handler_type];
        }

        $this->section = $section;
    }

    /**
     * @param Helper $helper
     *
     * @return void
     */
    public function setHelper( Helper $helper ): void {
        $this->helper = $helper;
    }

    /**
     * @param DOMDocument $dom
     *
     * @return mixed
     */
    public function process( DOMDocument $dom ): string {
        $xpath = new DOMXPath($dom);
        $text_nodes = $xpath->query('//*[contains(@class,"hostinger-ai-")]');

        foreach ($text_nodes as $node) {
            if ($node->nodeType === XML_ELEMENT_NODE) {
                $classes = $node->getAttribute('class');

                if (empty($classes)) {
                    continue;
                }

                preg_match_all('/hostinger-ai-[^\s]+/', $classes, $matches);
                $ai_elements = $matches[0];

                $index = $this->helper->extract_index_number($classes);

                foreach ($ai_elements as $ai_element) {
                    if (isset($this->handlers[$ai_element])) {
                        $element_data = [
                            'class' => $ai_element,
                            'index' => $index
                        ];

                        $element_structure = $this->helper->find_structure($this->section['elements'], $element_data);

                        if (!empty($element_structure)) {
                            $this->handlers[$ai_element]->handle_gutenberg($node, $element_structure);
                        }
                    }
                }
            }
        }

        $html = $dom->saveHTML();

        $html = preg_replace('/<\/html>$/', '', $html);
        $html = preg_replace('/<\/body>$/', '', $html);

        return $html;
    }

    public function prepare_json(): array {

        $json_data = json_decode( $this->section['html'], true );

        return $this->traverse_elementor_data($json_data, function ($element) {
            $css_classes = $element['settings']['css_classes'] ?? '';
            if(empty($css_classes)) {
                $css_classes = $element['settings']['_css_classes'] ?? '';
            }

            if (!empty($css_classes)) {
                $pattern    = '/hostinger-ai-[^\s]+/';
                $ai_elements = $this->helper->extract_class_names($css_classes, $pattern);

                if(!empty($ai_elements)) {
                    foreach ( $ai_elements as $ai_element ) {
                        $element_data = [
                            'class' => $ai_element,
                            'index' => $this->helper->extract_index_number( $css_classes ),
                        ];

                        $element_structure = $this->helper->find_structure($this->section['elements'], $element_data);
                        if (!empty($element_structure)) {
                            $this->handlers[$ai_element]->handle_elementor($element, $element_structure);
                        }
                    }
                }
            }

            return $element;
        });
    }

    private function traverse_elementor_data(array $data, callable $callback): array {
        foreach ($data as $key => $element) {
            if (is_array($element) && isset($element['elType'])) {
                $data[$key] = $callback($element);

                if (isset($data[$key]['elements']) && is_array($data[$key]['elements'])) {
                    $data[$key]['elements'] = $this->traverse_elementor_data($data[$key]['elements'], $callback);
                }
            } elseif (is_array($element)) {
                $data[$key] = $this->traverse_elementor_elements($element, $callback);
            }
        }

        return $data;
    }
}
