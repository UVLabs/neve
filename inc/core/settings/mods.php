<?php
/**
 * Theme mods wrapper
 *
 * Author:          Andrei Baicus <andrei@themeisle.com>
 * Created on:      17/08/2018
 *
 * @package Neve\Core
 */

namespace Neve\Core\Settings;

/**
 * Class Admin
 *
 * @package Neve\Core\Settings
 */
class Mods {

	/**
	 * Cached values.
	 *
	 * @var array Values cached.
	 */
	private static $_cached = [];
	/**
	 * No cache mode.
	 *
	 * @var bool Should we avoid cache.
	 */
	public static $no_cache = false;

	/**
	 * Get theme mod.
	 *
	 * @param string $key Key value.
	 * @param mixed  $default Default value.
	 *
	 * @return mixed Mod value.
	 */
	public static function get( $key, $default = false ) {
		$master_default = $default;
		$subkey         = null;
		if ( strpos( $key, '.' ) !== false ) {
			$key_parts      = explode( '.', $key );
			$key            = $key_parts[0];
			$subkey         = $key_parts[1];
			$master_default = false;
		}

		if ( ! isset( self::$_cached[ $key ] ) || self::$no_cache ) {
			$master_default        = $master_default === false ? self::defaults( $key ) : $master_default;
			self::$_cached[ $key ] =
				( $master_default === false ) ?
					get_theme_mod( $key ) :
					get_theme_mod( $key, $master_default );
		}

		if ( $subkey === null ) {
			return self::$_cached[ $key ];
		}
		$value = is_string( self::$_cached[ $key ] ) ? json_decode( self::$_cached[ $key ], true ) : self::$_cached[ $key ];

		return isset( $value[ $subkey ] ) ? $value[ $subkey ] : $default;
	}

	/**
	 * Forced defaults.
	 *
	 * @param string $key Key name.
	 *
	 * @return array|bool
	 */
	private static function defaults( $key ) {
		switch ( $key ) {
			case Config::MODS_CONTAINER_WIDTH:
				return '{ "mobile": 748, "tablet": 992, "desktop": 1170 }';
			case Config::MODS_BUTTON_PRIMARY_STYLE:
				return neve_get_button_appearance_default();
			case Config::MODS_BUTTON_SECONDARY_STYLE:
				return neve_get_button_appearance_default( 'secondary' );
			case Config::MODS_TYPEFACE_GENERAL:
				$defaults  = self::get_typography_defaults(
					[
						'line_height'    => 'neve_body_line_height',
						'letter_spacing' => 'neve_body_letter_spacing',
						'font_weight'    => 'neve_body_font_weight',
						'text_transform' => 'neve_body_text_transform',
					]
				);
				$font_size = self::to_json( 'neve_body_font_size' );
				if ( ! empty( $font_size ) ) {
					$defaults['fontSize'] = $font_size;
				}

				return $defaults;
			case Config::MODS_TYPEFACE_H1:
			case Config::MODS_TYPEFACE_H2:
			case Config::MODS_TYPEFACE_H3:
			case Config::MODS_TYPEFACE_H4:
			case Config::MODS_TYPEFACE_H5:
			case Config::MODS_TYPEFACE_H6:
				$defaults   = self::get_typography_defaults(
					[
						'line_height'    => 'neve_headings_line_height',
						'letter_spacing' => 'neve_headings_letter_spacing',
						'font_weight'    => 'neve_headings_font_weight',
						'text_transform' => 'neve_headings_text_transform',
					]
				);
				$legacy_map = [
					Config::MODS_TYPEFACE_H6 => [
						'font_size'   => 'neve_h6_font_size',
						'line_height' => 'neve_h6_line_height',
					],
					Config::MODS_TYPEFACE_H5 => [
						'font_size'   => 'neve_h5_font_size',
						'line_height' => 'neve_h5_line_height',
					],
					Config::MODS_TYPEFACE_H4 => [
						'font_size'   => 'neve_h4_font_size',
						'line_height' => 'neve_h4_line_height',
					],
					Config::MODS_TYPEFACE_H3 => [
						'font_size'   => 'neve_h3_font_size',
						'line_height' => 'neve_h3_line_height',
					],
					Config::MODS_TYPEFACE_H2 => [
						'font_size'   => 'neve_h2_font_size',
						'line_height' => 'neve_h2_line_height',
					],
					Config::MODS_TYPEFACE_H1 => [
						'font_size'   => 'neve_h1_font_size',
						'line_height' => 'neve_h1_line_height',
					],
				];

				$font_size = self::to_json( $legacy_map[ $key ]['font_size'] );
				if ( ! empty( $font_size ) ) {
					$defaults['fontSize'] = $font_size;
				}
				$line_height = self::to_json( $legacy_map[ $key ]['line_height'] );
				if ( ! empty( $line_height ) ) {
					$defaults['lineHeight'] = $line_height;
				}

				return $defaults;
		}

		return false;
	}

	/**
	 * Helper method to get defaults for typography.
	 *
	 * @param array $args Legacy mods.
	 *
	 * @return array
	 */
	private static function get_typography_defaults( $args ) {

		$line_height    = self::to_json( $args['line_height'] );
		$letter_spacing = self::get( $args['letter_spacing'] );
		$font_weight    = self::get( $args['font_weight'] );
		$text_transform = self::get( $args['text_transform'] );
		$defaults       = [];
		if ( ! empty( $line_height ) ) {
			$defaults['lineHeight'] = $line_height;
		}
		if ( ! empty( $letter_spacing ) ) {
			$defaults['letterSpacing'] = $letter_spacing;
		}
		if ( ! empty( $font_weight ) ) {
			$defaults['fontWeight'] = $font_weight;
		}
		if ( ! empty( $text_transform ) ) {
			$defaults['textTransform'] = $text_transform;
		}

		return $defaults;
	}

	/**
	 * Setter for the manager.
	 *
	 * @param string $key Key.
	 * @param mixed  $value Value.
	 */
	public static function set( $key, $value ) {
		self::$_cached[ $key ] = $value;
	}

	/**
	 * Get and transform setting to json.
	 *
	 * @param string $key Key name.
	 * @param mixed  $default Default value.
	 * @param bool   $as_array As array or Object.
	 *
	 * @return mixed
	 */
	public static function to_json( $key, $default = false, $as_array = true ) {
		return json_decode( self::get( $key, $default ), $as_array );
	}

	/**
	 * Get alternative mod default.
	 *
	 * @param string $key theme mod key.
	 *
	 * @return string | array
	 */
	public static function get_alternative_mod_default( $key ) {
		$new                    = neve_is_new_skin();
		$headings_generic_setup = [
			'fontWeight'    => $new ? '700' : '600',
			'textTransform' => 'none',
			'letterSpacing' => [
				'mobile'  => 0,
				'tablet'  => 0,
				'desktop' => 0,
			],
		];
		$headings_sufix         = [
			'mobile'  => $new ? 'px' : 'em',
			'tablet'  => $new ? 'px' : 'em',
			'desktop' => $new ? 'px' : 'em',
		];
		switch ( $key ) {
			case Config::MODS_FONT_GENERAL:
				return $new ? 'Arial, Helvetica, sans-serif' : false;
			case Config::MODS_TYPEFACE_GENERAL:
				return [
					'fontSize'      => [
						'suffix'  => [
							'mobile'  => 'px',
							'tablet'  => 'px',
							'desktop' => 'px',
						],
						'mobile'  => 15,
						'tablet'  => 16,
						'desktop' => 16,
					],
					'lineHeight'    => [
						'mobile'  => 1.6,
						'tablet'  => 1.6,
						'desktop' => $new ? 1.7 : 1.6,
					],
					'letterSpacing' => [
						'mobile'  => 0,
						'tablet'  => 0,
						'desktop' => 0,
					],
					'fontWeight'    => '400',
					'textTransform' => 'none',
				];
			case Config::MODS_TYPEFACE_H1:
				return array_merge(
					$headings_generic_setup,
					array(
						'fontSize'   => [
							'mobile'  => $new ? '32' : '1.5',
							'tablet'  => $new ? '40' : '1.5',
							'desktop' => $new ? '48' : '2',
							'suffix'  => $headings_sufix,
						],
						'lineHeight' => [
							'mobile'  => $new ? 1.2 : 1.6,
							'tablet'  => $new ? 1.2 : 1.6,
							'desktop' => $new ? 1.1 : 1.6,
						],
					)
				);
			case Config::MODS_TYPEFACE_H2:
				return array_merge(
					$headings_generic_setup,
					array(
						'fontSize'   => [
							'mobile'  => $new ? '24' : '1.3',
							'tablet'  => $new ? '32' : '1.3',
							'desktop' => $new ? '40' : '1.75',
							'suffix'  => $headings_sufix,
						],
						'lineHeight' => [
							'mobile'  => $new ? 1.3 : 1.6,
							'tablet'  => $new ? 1.2 : 1.6,
							'desktop' => $new ? 1.2 : 1.6,
						],
					)
				);
			case Config::MODS_TYPEFACE_H3:
				return array_merge(
					$headings_generic_setup,
					array(
						'fontSize'   => [
							'mobile'  => $new ? '20' : '1.1',
							'tablet'  => $new ? '24' : '1.1',
							'desktop' => $new ? '32' : '1.5',
							'suffix'  => $headings_sufix,
						],
						'lineHeight' => [
							'mobile'  => $new ? 1.4 : 1.6,
							'tablet'  => $new ? 1.4 : 1.6,
							'desktop' => $new ? 1.4 : 1.6,
						],
					)
				);
			case Config::MODS_TYPEFACE_H4:
				return array_merge(
					$headings_generic_setup,
					array(
						'fontSize'   => [
							'mobile'  => $new ? '18' : '1',
							'tablet'  => $new ? '20' : '1',
							'desktop' => $new ? '24' : '1.25',
							'suffix'  => $headings_sufix,
						],
						'lineHeight' => [
							'mobile'  => 1.6,
							'tablet'  => $new ? 1.5 : 1.6,
							'desktop' => $new ? 1.5 : 1.6,
						],
					)
				);
			case Config::MODS_TYPEFACE_H5:
				return array_merge(
					$headings_generic_setup,
					array(
						'fontSize'   => [
							'mobile'  => $new ? '16' : '0.75',
							'tablet'  => $new ? '18' : '0.75',
							'desktop' => $new ? '20' : '1',
							'suffix'  => $headings_sufix,
						],
						'lineHeight' => [
							'mobile'  => 1.6,
							'tablet'  => 1.6,
							'desktop' => 1.6,
						],
					)
				);
			case Config::MODS_TYPEFACE_H6:
				return array_merge(
					$headings_generic_setup,
					array(
						'fontSize'   => [
							'mobile'  => $new ? '14' : '0.75',
							'tablet'  => $new ? '16' : '0.75',
							'desktop' => $new ? '16' : '1',
							'suffix'  => $headings_sufix,
						],
						'lineHeight' => [
							'mobile'  => 1.6,
							'tablet'  => 1.6,
							'desktop' => 1.6,
						],
					)
				);
			case Config::MODS_BUTTON_PRIMARY_PADDING:
				$device = $new ? [
					'top'    => 13,
					'right'  => 15,
					'bottom' => 13,
					'left'   => 15,
				] : [
					'top'    => 8,
					'right'  => 12,
					'bottom' => 8,
					'left'   => 12,
				];

				return [
					'desktop'      => $device,
					'tablet'       => $device,
					'mobile'       => $device,
					'desktop-unit' => 'px',
					'tablet-unit'  => 'px',
					'mobile-unit'  => 'px',
				];
			case Config::MODS_FORM_FIELDS_SPACING:
				return $new ? 40 : 10;
			case Config::MODS_FORM_FIELDS_PADDING:
				return [
					'top'    => $new ? 10 : 7,
					'bottom' => $new ? 10 : 7,
					'left'   => 12,
					'right'  => 12,
					'unit'   => 'px',
				];
			case Config::MODS_FORM_FIELDS_BORDER_WIDTH:
				return [
					'top'    => $new ? 2 : 1,
					'right'  => $new ? 2 : 1,
					'left'   => $new ? 2 : 1,
					'bottom' => $new ? 2 : 1,
					'unit'   => 'px',
				];
			default:
				return false;
		}
	}
}
