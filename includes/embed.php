<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Embed.
 *
 * Elementor embed handler class.
 *
 * @since 1.5.0
 */
class Embed {

	private static $provider_match_masks = [
		'youtube' => '/^(?:https?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:(?:watch)?\?(?:.*&)?vi?=|(?:embed|v|vi|user)\/))([^\?&\"\'>]+)/',
		'vimeo' => '/(?:https?:\/\/)?(?:www\.)?(?:player\.)?vimeo\.com\/(?:[a-z]*\/)*([‌​0-9]{6,11})[?]?.*/',
	];

	private static $embed_patterns = [
		'youtube' => 'https://www.youtube{NO_COOKIE}.com/embed/{VIDEO_ID}?feature=oembed',
		'vimeo' => 'https://player.vimeo.com/video/{VIDEO_ID}',
	];

	/**
	 * @static
	 * @since 1.5.0
	 * @access public
	*/
	public static function get_video_properties( $video_url ) {
		foreach ( self::$provider_match_masks as $provider => $match_mask ) {
			preg_match( $match_mask, $video_url, $matches );

			if ( $matches ) {
				return [
					'provider' => $provider,
					'video_id' => $matches[1],
				];
			}
		}

		return null;
	}

	/**
	 * @static
	 * @since 1.5.0
	 * @access public
	*/
	public static function get_embed_url( $video_url, array $embed_url_params = [], array $options = [] ) {
		$video_properties = self::get_video_properties( $video_url );

		if ( ! $video_properties ) {
			return null;
		}

		$embed_pattern = self::$embed_patterns[ $video_properties['provider'] ];

		$replacements = [ '{VIDEO_ID}' => $video_properties['video_id'] ];

		if ( 'youtube' === $video_properties['provider'] ) {
			$replacements['{NO_COOKIE}'] = ! empty( $options['privacy'] ) ? '-nocookie' : '';
		}

		$embed_pattern = str_replace( array_keys( $replacements ), $replacements, $embed_pattern );

		return add_query_arg( $embed_url_params, $embed_pattern );
	}

	/**
	 * @static
	 * @since 1.5.0
	 * @access public
	*/
	public static function get_embed_html( $video_url, array $embed_url_params = [], array $options = [],  array $frame_attributes = [] ) {
		$video_embed_url = self::get_embed_url( $video_url, $embed_url_params, $options );

		if ( ! $video_embed_url ) {
			return null;
		}

		$default_frame_attributes = [
			'src' => $video_embed_url,
			'allowfullscreen',
		];

		$frame_attributes = array_merge( $default_frame_attributes, $frame_attributes );

		$attributes_for_print = [];

		foreach ( $frame_attributes as $attribute_key => $attribute_value ) {
			$attribute_value = esc_attr( $attribute_value );

			if ( is_numeric( $attribute_key ) ) {
				$attributes_for_print[] = $attribute_value;
			} else {
				$attributes_for_print[] = sprintf( '%s="%s"', $attribute_key, $attribute_value );
			}
		}

		$attributes_for_print = implode( ' ', $attributes_for_print );

		$iframe_html = "<iframe $attributes_for_print></iframe>";

		return apply_filters( 'oembed_result', $iframe_html, $video_url, $frame_attributes );
	}
}
