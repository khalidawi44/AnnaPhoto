<?php

namespace Hostinger\AiTheme\Builder\ElementHandlers;

use Hostinger\AiTheme\Builder\ImageManager;
use Hostinger\WpHelper\Utils;
use Hostinger\WpHelper\Utils as Helper;
use DOMElement;
use Hostinger\WpHelper\Config;
use Hostinger\WpHelper\Requests\Client;

defined( 'ABSPATH' ) || exit;

class BackgroundImageHandler extends BaseElementHandler {
    private const GET_UNSPLASH_IMAGE_ACTION = '/v3/wordpress/plugin/download-image';
    private Utils $helper;
    private Client $client;

    public function __construct(string $builder_type) {
        parent::__construct($builder_type);

        $this->helper = new Helper();
        $config_handler = new Config();
        $this->client = new Client(
            $config_handler->getConfigValue('base_rest_uri', HOSTINGER_AI_WEBSITES_REST_URI),
            [
                Config::TOKEN_HEADER  => $this->helper::getApiToken(),
                Config::DOMAIN_HEADER => $this->helper->getHostInfo(),
                'Content-Type' => 'application/json'
            ]
        );
    }

    public function handle_gutenberg(DOMElement &$node, array $element_structure): void {
        if (empty($element_structure['content'])) {
            return;
        }

        $previousElement = $node->previousSibling;
        $value = str_replace(' wp:group ', '', $previousElement->nodeValue);
        $block = json_decode($value, true);

        if (empty($block)) {
            return;
        }

        $image_url = $this->get_image_url($element_structure);
        if ($image_url && !empty($block['className']) && str_contains($block['className'], 'hostinger-ai-background-image')) {
            $block['style']['background']['backgroundImage'] = [
                'url'   => $image_url,
                'id'    => 0,
                'title' => ''
            ];
            $previousElement->nodeValue = ' wp:group ' . json_encode($block) . ' ';
        }
    }

    public function handle_elementor(array &$element, array $element_structure): void {
        if (empty($element_structure['content'])) {
            return;
        }

        $image_url = $this->get_image_url($element_structure);
        if (!empty($image_url)) {
            $element['settings']['background_image'] = [
                'url' => $image_url,
                'source' => 'url'
            ];
        }
    }

    private function get_image_url(array $element_structure): ?string {
        $image_manager = new ImageManager($element_structure['content']);
        $image_data = $image_manager->get_unsplash_image_data();

        if (property_exists($image_data, 'image')) {
            return $image_manager->modify_image_url($image_data->image, $element_structure);
        }

        return null;
    }
}
