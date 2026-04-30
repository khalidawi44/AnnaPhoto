<?php

namespace Hostinger\AiTheme\Builder\ElementHandlers;

defined( 'ABSPATH' ) || exit;

use DOMElement;

class BaseElementHandler implements ElementHandler {
    protected string $builder_type;

    public function __construct(string $builder_type) {
        $this->builder_type = $builder_type;
    }
    public function handle_gutenberg(DOMElement &$node, array $element_structure): void {

    }

    public function handle_elementor(array &$element, array $element_structure): void {

    }
}
