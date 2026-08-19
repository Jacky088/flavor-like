<?php
/**
 * Wp Flavor Like Admin Customize
 * // @echo HEADER
*/

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'flavor_like_customizer' ) ) {
    class flavor_like_customizer{

        protected $option_domain = 'flavor_like_customize';
        protected $sections_cache = null;

		/**
		 * __construct
		 */
		function __construct() {
            // No framework dependencies - just initialize
        }

        /**
         * Register customizer sections
         * Returns array structure for API consumption
         *
         * @return array Sections structure
         */
        public function register_sections(){
            // Return cached sections if available
            if ( $this->sections_cache !== null ) {
                return $this->sections_cache;
            }

            do_action( 'flavor_like_customize_started' );

            $sections = array();

            $parent_section = array(
                'id'    => FLAVOR_LIKE_SLUG,   // Set a unique slug-like ID
                'title' => esc_html__( 'Flavor Like', 'flavor-like' )
            );

            // Expose section via filter for API access
            apply_filters( 'flavor_like_optiwich_customizer_section', $parent_section, $this->option_domain );

            $sections[] = $parent_section;

            $sections[] = array(
                'parent' => FLAVOR_LIKE_SLUG,                           // The slug id of the parent section
                'id'     => 'button_templates',
                'title'  => esc_html__( 'Like / Dislike Buttons', 'flavor-like' ),
                'template' => 'button',                              // Template ID for customizer preview
                'icon'   => 'cursor-arrow-rays',                     // Icon for template selector
                'fields' => array(
                    array(
                        'type'    => 'heading',
                        'content' => esc_html__( 'Template Wrapper', 'flavor-like' ),
                    ),
                    array(
                        'id'               => 'template_typography',
                        'type'             => 'typography',
                        'color'            => false,
                        'output_important' => true,
                        'title'            => esc_html__( 'Typography', 'flavor-like' ),
                        'output'           => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class, .flavorlike:not(.flavorlike-engagement-template) .flavor_like_put_text, .flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .count-box',
                        'units'            => array('px', 'em', 'rem')
                    ),
                    array(
                        'id'            => 'template_group',
                        'type'          => 'tabbed',
                        'tabs'          => array(
                            array(
                                'title'     => esc_html__( 'Normal', 'flavor-like' ),
                                'fields'    => array(
                                    array(
                                        'id'          => 'normal_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', 'flavor-like' ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class',
                                    ),
                                    array(
                                        'id'     => 'normal_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'flavor-like' ),
                                        'output' => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class',
                                        'units'  => array('px', 'em', 'rem')
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Hover', 'flavor-like' ),
                                'fields'    => array(
                                    array(
                                        'id'               => 'hover_bg',
                                        'type'             => 'color',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Background', 'flavor-like' ),
                                        'output_mode'      => 'background-color',
                                        'output'           => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class:hover',
                                    ),
                                    array(
                                        'id'               => 'hover_border',
                                        'type'             => 'border',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Border', 'flavor-like' ),
                                        'output'           => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class:hover',
                                        'units'            => array('px', 'em', 'rem', '%')
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Active', 'flavor-like' ),
                                'fields'    => array(
                                    array(
                                        'id'          => 'active_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => esc_html__( 'Background', 'flavor-like' ),
                                        'output'      => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class.flavor_like_is_already_liked, .flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class.flavor_like_is_liked',
                                    ),
                                    array(
                                        'id'     => 'active_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'flavor-like' ),
                                        'output' => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class.flavor_like_is_already_liked, .flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class.flavor_like_is_liked',
                                        'units'  => array('px', 'em', 'rem')
                                    ),
                                )
                            ),
                        )
                    ),
                    array(
                        'id'               => 'template_padding',
                        'type'             => 'spacing',
                        'output_important' => true,
                        'title'            => esc_html__( 'Padding', 'flavor-like' ),
                        'output'           => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class',
                        'units'            => array('px', 'em', 'rem', '%')
                    ),
                    array(
                        'id'               => 'template_margin',
                        'type'             => 'spacing',
                        'output_mode'      => 'margin',
                        'output_important' => true,
                        'title'            => esc_html__( 'Margin', 'flavor-like' ),
                        'output'           => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class',
                        'units'            => array('px', 'em', 'rem', '%')
                    ),

                    // Start button section
                    array(
                        'type'    => 'heading',
                        'content' => esc_html__( 'Button', 'flavor-like' ),
                    ),
                    array(
                        'id'            => 'button_group',
                        'type'          => 'tabbed',
                        'tabs'          => apply_filters( 'flavor_like_customizer_button_group_options',  array(
                            array(
                                'title'     => esc_html__( 'Normal', 'flavor-like' ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'normal_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', 'flavor-like' ),
                                        'output' => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .flavor_like_btn',
                                    ),
                                    array(
                                        'id'          => 'normal_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => esc_html__( 'Background', 'flavor-like' ),
                                        'output'      => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .flavor_like_btn',
                                    ),
                                    array(
                                        'id'     => 'normal_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'flavor-like' ),
                                        'output' => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .flavor_like_btn',
                                        'units'  => array('px', 'em', 'rem')
                                    ),
                                    array(
                                        'id'               => 'button_image_dimensions',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', 'flavor-like' ),
                                        'output'           => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .flavor_like_btn.flavor_like_put_image::after',
                                        'units'            => array('px', 'em', 'rem', '%')
                                    ),
                                    array(
                                        'id'               => 'normal_like_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Like Image', 'flavor-like' ),
                                        'output'           => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .flavor_like_btn.flavor_like_put_image::after',
                                    )
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Hover', 'flavor-like' ),
                                'fields'    => array(
                                    array(
                                        'id'               => 'hover_color',
                                        'type'             => 'color',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Text Color', 'flavor-like' ),
                                        'output'           => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .flavor_like_btn:hover',
                                    ),
                                    array(
                                        'id'               => 'hover_bg',
                                        'type'             => 'color',
                                        'output_mode'      => 'background-color',
                                        'title'            => esc_html__( 'Background', 'flavor-like' ),
                                        'output_important' => true,
                                        'output'           => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .flavor_like_btn:hover',
                                    ),
                                    array(
                                        'id'               => 'hover_border',
                                        'type'             => 'border',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Border', 'flavor-like' ),
                                        'output'           => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .flavor_like_btn:hover',
                                        'units'            => array('px', 'em', 'rem', '%')
                                    ),
                                    array(
                                        'id'               => 'hover_like_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Like Image', 'flavor-like' ),
                                        'output'           => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .flavor_like_btn.flavor_like_put_image:hover::after',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Active', 'flavor-like' ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'active_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', 'flavor-like' ),
                                        'output' => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .flavor_like_btn.flavor_like_btn_is_active',
                                    ),
                                    array(
                                        'id'          => 'active_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => esc_html__( 'Background', 'flavor-like' ),
                                        'output'      => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .flavor_like_btn.flavor_like_btn_is_active',
                                    ),
                                    array(
                                        'id'     => 'active_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'flavor-like' ),
                                        'output' => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .flavor_like_btn.flavor_like_btn_is_active',
                                        'units'  => array('px', 'em', 'rem')
                                    ),
                                    array(
                                        'id'               => 'active_like_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Like Image', 'flavor-like' ),
                                        'output'           => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .flavor_like_btn.flavor_like_btn_is_active.flavor_like_put_image::after',
                                    ),
                                )
                            ),
                        )
                    ) ),
                    array(
                        'id'               => 'button_dimensions',
                        'type'             => 'dimensions',
                        'output_important' => true,
                        'title'            => esc_html__( 'Button Dimensions', 'flavor-like' ),
                        'output'           => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .flavor_like_btn',
                        'units'            => array('px', 'em', 'rem', '%')
                    ),
                    array(
                        'id'               => 'button_padding',
                        'type'             => 'spacing',
                        'output_important' => true,
                        'title'            => esc_html__( 'Padding', 'flavor-like' ),
                        'output'           => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .flavor_like_btn',
                        'units'            => array('px', 'em', 'rem', '%')
                    ),
                    array(
                        'id'               => 'button_margin',
                        'type'             => 'spacing',
                        'output_mode'      => 'margin',
                        'output_important' => true,
                        'title'            => esc_html__( 'Margin', 'flavor-like' ),
                        'output'           => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .flavor_like_btn',
                        'units'            => array('px', 'em', 'rem', '%')
                    ),
                    array(
                        'id'         => 'button_align',
                        'type'       => 'button_set',
                        'title'      => esc_html__( 'Button Alignment', 'flavor-like' ),
                        'options'    => array(
                            'left'   => esc_html__( 'Left', 'flavor-like' ),
                            'center' => esc_html__( 'Center', 'flavor-like' ),
                            'right'  => esc_html__( 'Right', 'flavor-like' )
                        ),
                        'default'    => ''
                    ),
                    // Start Counter Section
                    array(
                        'type'    => 'heading',
                        'content' => esc_html__( 'Counter', 'flavor-like' ),
                    ),
                    array(
                        'id'            => 'counter_group',
                        'type'          => 'tabbed',
                        'tabs'          => array(
                            array(
                                'title'     => esc_html__( 'Normal', 'flavor-like' ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'normal_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', 'flavor-like' ),
                                        'output' => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .count-box',
                                    ),
                                    array(
                                        'id'          => 'normal_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', 'flavor-like' ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .count-box, .flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .count-box::before',
                                    ),
                                    array(
                                        'id'     => 'normal_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'flavor-like' ),
                                        'output' => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .count-box',
                                        'units'  => array('px', 'em', 'rem')
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Active', 'flavor-like' ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'active_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', 'flavor-like' ),
                                        'output' => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class.flavor_like_is_already_liked .count-box, .flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class.flavor_like_is_liked .count-box',
                                    ),
                                    array(
                                        'id'          => 'active_bg',
                                        'type'        => 'color',
                                        'output_mode' => 'background-color',
                                        'title'       => esc_html__( 'Background', 'flavor-like' ),
                                        'output'      => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class.flavor_like_is_already_liked .count-box, .flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class.flavor_like_is_liked .count-box, .flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class.flavor_like_is_already_liked .count-box::before, .flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class.flavor_like_is_liked .count-box::before',
                                    ),
                                    array(
                                        'id'     => 'active_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'flavor-like' ),
                                        'output' => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class.flavor_like_is_already_liked .count-box, .flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class.flavor_like_is_liked .count-box',
                                        'units'  => array('px', 'em', 'rem')
                                    ),
                                )
                            ),
                        )
                    ),
                    array(
                        'id'     => 'counter_padding',
                        'type'   => 'spacing',
                        'title'  => esc_html__( 'Padding', 'flavor-like' ),
                        'output' => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .count-box',
                        'units'  => array('px', 'em', 'rem', '%')
                    ),
                    array(
                        'id'          => 'counter_margin',
                        'type'        => 'spacing',
                        'output_mode' => 'margin',
                        'title'       => esc_html__( 'Margin', 'flavor-like' ),
                        'output'      => '.flavorlike:not(.flavorlike-engagement-template) .flavor_like_general_class .count-box',
                        'units'       => array('px', 'em', 'rem', '%')
                    )
                )
            );

            $sections[] = array(
                'parent' => FLAVOR_LIKE_SLUG,                           // The slug id of the parent section
                'id'     => 'toast_messages',
                'title'  => esc_html__( 'Toast Messages', 'flavor-like' ),
                'template' => 'toast',                               // Template ID for customizer preview
                'icon'   => 'bell',                                  // Icon for template selector
                'fields' => array(
                    array(
                        'id'               => 'toast_typography',
                        'type'             => 'typography',
                        'color'            => false,
                        'output_important' => true,
                        'title'            => esc_html__( 'Typography', 'flavor-like' ),
                        'output'           => '.flavorlike-notification .flavorlike-message',
                        'units'            => array('px', 'em', 'rem')
                    ),
                    array(
                        'id'            => 'toast_group',
                        'type'          => 'tabbed',
                        'tabs'          => array(
                            array(
                                'title'     => esc_html__( 'Info', 'flavor-like' ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'info_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', 'flavor-like' ),
                                        'output' => '.flavorlike-notification .flavorlike-message',
                                    ),
                                    array(
                                        'id'          => 'info_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', 'flavor-like' ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.flavorlike-notification .flavorlike-message',
                                    ),
                                    array(
                                        'id'     => 'info_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'flavor-like' ),
                                        'output' => '.flavorlike-notification .flavorlike-message',
                                        'units'  => array('px', 'em', 'rem')
                                    ),
                                    array(
                                        'id'               => 'info_icon_size',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', 'flavor-like' ),
                                        'output'           => '.flavorlike-notification .flavorlike-message::before',
                                        'units'            => array('px', 'em', 'rem', '%')
                                    ),
                                    array(
                                        'id'               => 'info_icon_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Icon Image', 'flavor-like' ),
                                        'output'           => '.flavorlike-notification .flavorlike-message::before',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Success', 'flavor-like' ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'success_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', 'flavor-like' ),
                                        'output' => '.flavorlike-notification .flavorlike-message.flavorlike-success',
                                    ),
                                    array(
                                        'id'          => 'success_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', 'flavor-like' ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.flavorlike-notification .flavorlike-message.flavorlike-success',
                                    ),
                                    array(
                                        'id'     => 'success_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'flavor-like' ),
                                        'output' => '.flavorlike-notification .flavorlike-message.flavorlike-success',
                                        'units'  => array('px', 'em', 'rem')
                                    ),
                                    array(
                                        'id'               => 'success_icon_size',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', 'flavor-like' ),
                                        'output'           => '.flavorlike-notification .flavorlike-message.flavorlike-success::before',
                                        'units'            => array('px', 'em', 'rem', '%')
                                    ),
                                    array(
                                        'id'               => 'success_icon_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Icon Image', 'flavor-like' ),
                                        'output'           => '.flavorlike-notification .flavorlike-message.flavorlike-success::before',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Error', 'flavor-like' ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'error_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', 'flavor-like' ),
                                        'output' => '.flavorlike-notification .flavorlike-message.flavorlike-error',
                                    ),
                                    array(
                                        'id'          => 'error_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', 'flavor-like' ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.flavorlike-notification .flavorlike-message.flavorlike-error',
                                    ),
                                    array(
                                        'id'     => 'error_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'flavor-like' ),
                                        'output' => '.flavorlike-notification .flavorlike-message.flavorlike-error',
                                        'units'  => array('px', 'em', 'rem')
                                    ),
                                    array(
                                        'id'               => 'error_icon_size',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', 'flavor-like' ),
                                        'output'           => '.flavorlike-notification .flavorlike-message.flavorlike-error::before',
                                        'units'            => array('px', 'em', 'rem', '%')
                                    ),
                                    array(
                                        'id'               => 'error_icon_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Icon Image', 'flavor-like' ),
                                        'output'           => '.flavorlike-notification .flavorlike-message.flavorlike-error::before',
                                    ),
                                )
                            ),
                            array(
                                'title'     => esc_html__( 'Warning', 'flavor-like' ),
                                'fields'    => array(
                                    array(
                                        'id'     => 'warning_color',
                                        'type'   => 'color',
                                        'title'  => esc_html__( 'Text Color', 'flavor-like' ),
                                        'output' => '.flavorlike-notification .flavorlike-message.flavorlike-warning',
                                    ),
                                    array(
                                        'id'          => 'warning_bg',
                                        'type'        => 'color',
                                        'title'       => esc_html__( 'Background', 'flavor-like' ),
                                        'output_mode' => 'background-color',
                                        'output'      => '.flavorlike-notification .flavorlike-message.flavorlike-warning',
                                    ),
                                    array(
                                        'id'     => 'warning_border',
                                        'type'   => 'border',
                                        'title'  => esc_html__( 'Border', 'flavor-like' ),
                                        'output' => '.flavorlike-notification .flavorlike-message.flavorlike-warning',
                                        'units'  => array('px', 'em', 'rem')
                                    ),
                                    array(
                                        'id'               => 'warning_icon_size',
                                        'type'             => 'dimensions',
                                        'output_important' => true,
                                        'title'            => esc_html__( 'Image Dimensions', 'flavor-like' ),
                                        'output'           => '.flavorlike-notification .flavorlike-message.flavorlike-warning::before',
                                        'units'            => array('px', 'em', 'rem', '%')
                                    ),
                                    array(
                                        'id'               => 'warning_icon_image',
                                        'type'             => 'background',
                                        'background_color' => false,
                                        'title'            => esc_html__( 'Icon Image', 'flavor-like' ),
                                        'output'           => '.flavorlike-notification .flavorlike-message.flavorlike-warning::before',
                                    ),
                                )
                            ),
                        )
                    ),
                )
            );

            do_action( 'flavor_like_customize_ended' );

            // Allow extensions to add/modify sections
            $sections = apply_filters( 'flavor_like_customizer_sections', $sections );

            // Cache sections
            $this->sections_cache = $sections;

            return $sections;
        }

    }
}