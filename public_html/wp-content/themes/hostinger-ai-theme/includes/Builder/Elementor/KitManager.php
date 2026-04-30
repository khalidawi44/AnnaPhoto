<?php

namespace Hostinger\AiTheme\Builder\Elementor;

use Hostinger\AiTheme\Builder\Dto\ColorPaletteDto;

class KitManager {
    private int $kit_id;
    private array $kit_settings = array();

    public function __construct() {
        $this->kit_id = get_option('elementor_active_kit');

        if ($this->kit_id) {
            $settings = get_post_meta($this->kit_id, '_elementor_page_settings', true);
            $this->kit_settings = is_array($settings) ? $settings : [];

            if (!isset($this->kit_settings['custom_colors'])) {
                $this->kit_settings['custom_colors'] = [];
            }
        }
    }

    public function set_custom_colors( array $colors ): bool {
        $this->kit_settings['custom_colors'] = $colors;

        return $this->save();
    }

    public function transform_color_palette( ColorPaletteDto $color_palette ) : bool {
        $colors = [
            [
                '_id' => 'b5aeb33',
                'title' => 'Color 1 (Section backgrounds)',
                'color' => $color_palette->get_color_1() ?? ''
            ],
            [
                '_id' => 'c58817e',
                'title' => 'Color 2 (Section backgrounds)',
                'color' => $color_palette->get_color_2() ?? ''
            ],
            [
                '_id' => '5420d44',
                'title' => 'Color 3 (Button background)',
                'color' => $color_palette->get_color_3() ?? ''
            ],
            [
                '_id' => '58be983',
                'title' => 'Light (Text on Color 2 and Gradient)',
                'color' => $color_palette->get_light() ?? ''
            ],
            [
                '_id' => '09cc561',
                'title' => 'Dark (Text on Light and Color 1)',
                'color' => $color_palette->get_dark() ?? ''
            ],
            [
                '_id' => 'a495fd4',
                'title' => 'Grey (Form borders)',
                'color' => $color_palette->get_grey() ?? ''
            ],
            [
                '_id' => 'dff8941',
                'title' => 'Gradient color 1',
                'color' => $color_palette->get_main_gradient() ? $color_palette->get_main_gradient()->get_main_color() : ''
            ],
        ];

        return $this->set_custom_colors( $colors );
    }

    private function save(): bool {
        if ( empty( $this->kit_id ) ) {
            return false;
        }

        return update_post_meta( $this->kit_id, '_elementor_page_settings', $this->kit_settings );
    }
}
