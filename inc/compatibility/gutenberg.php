<?php
/**
 * Author:          Andrei Baicus <andrei@themeisle.com>
 * Created on:      15/11/2018
 *
 * @package gutenberg.php
 */

namespace Neve\Compatibility;

use Neve\Views\Layouts\Layout_Container;
use Neve\Views\Layouts\Layout_Sidebar;

/**
 * Class Gutenberg
 *
 * @package Neve\Compatibility
 */
class Gutenberg {

	/**
	 * Available post meta to be taken into consideration.
	 *
	 * @var array
	 */
	private $available_post_meta = array(
		'neve_meta_disable_header',
		'neve_meta_disable_title',
		'neve_meta_disable_featured_image',
		'neve_meta_disable_footer',
		'neve_meta_sidebar',
		'neve_meta_container',
		'neve_meta_enable_content_width',
		'neve_meta_content_width',
	);

	/**
	 * The post ID.
	 *
	 * @var null
	 */
	private $post_id = null;

	/**
	 * Initialize the compatibility module.
	 */
	public function init() {
		$this->set_post_id();

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_gutenberg_scripts' ) );
		add_filter( 'themeisle_gutenberg_templates', array( $this, 'add_gutenberg_templates' ) );
	}

	/**
	 * Set the post ID.
	 *
	 * @return int|null
	 */
	private function set_post_id() {
		if ( ! isset( $_GET['post'] ) ) {
			return null;
		}
		$this->post_id = $_GET['post'];
	}

	/**
	 * Enqueue gutenberg scripts.
	 */
	public function enqueue_gutenberg_scripts() {
		wp_enqueue_style( 'neve-gutenberg-style', NEVE_ASSETS_URL . 'css/gutenberg-editor-style' . ( ( NEVE_DEBUG ) ? '' : '.min' ) . '.css', array(), NEVE_VERSION );

		wp_register_script( 'neve-gutenberg-script', NEVE_ASSETS_URL . 'js/gutenberg-preview-manager' . ( ( NEVE_DEBUG ) ? '' : '.min' ) . '.js', array( 'jquery' ), NEVE_VERSION, true );

		wp_localize_script( 'neve-gutenberg-script', 'neveGutenbergHelper', apply_filters( 'neve_gutenberg_helper_filter_localization', $this->localize_gutenberg_helper_script() ) );

		wp_enqueue_script( 'neve-gutenberg-script' );
	}

	/**
	 * Localize the gutenberg helper script.
	 *
	 * @return array
	 */
	public function localize_gutenberg_helper_script() {
		$localization                   = array();
		$localization                   = array_merge( $localization, $this->get_post_metas() );
		$localization['strings']        = $this->get_strings();
		$localization['metaStatus']     = $this->get_meta_status();
		$localization['sidebarSetup']   = $this->get_sidebar_setup();
		$localization['containerSetup'] = $this->get_container_setup();

		return $localization;
	}

	/**
	 * Check if we're editing a page.
	 *
	 * @return bool
	 */
	private function is_page() {
		if ( isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) === 'page' ) {
			return true;
		}

		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'page' ) {
			return true;
		}

		return false;
	}


	/**
	 * Get the sidebar setup.
	 *
	 * @return string
	 * TODO: Really pull the sidebar setup from customizer.
	 */
	private function get_sidebar_setup() {
		$context = 'single-post';
		if ( $this->is_page() ) {
			$context = 'single-page';
		}

		$sidebar_manager = new Layout_Sidebar();

		$layout = $sidebar_manager->get_sidebar_setup( $context );

		$setup = get_theme_mod( $layout['theme_mod'], 'right' );

		return $setup;
	}

	/**
	 * Get the container setup.
	 *
	 * @return string
	 */
	private function get_container_setup() {
		$context = 'single-post';

		if ( $this->is_page() ) {
			$context = 'single-page';
		}

		$container_manager = new Layout_Container();

		$layout = $container_manager->container_layout( 'contained', $context );

		if ( $layout === 'container' ) {
			return 'contained';
		}

		return 'full-width';
	}

	/**
	 * Get the post metas we're interested in.
	 *
	 * @return array
	 */
	private function get_post_metas() {
		if ( $this->post_id === null ) {
			return array();
		}

		$metas = array();
		foreach ( $this->available_post_meta as $meta ) {
			$meta_value = get_post_meta( $this->post_id, $meta, true );
			if ( empty( $meta_value ) ) {
				continue;
			}
			$metas[ $meta ] = $meta_value;
		}

		return $metas;
	}

	/**
	 * Check if post meta is shown on the front end.
	 *
	 * @return string
	 */
	private function get_meta_status() {
		if ( $this->is_page() ) {
			return 'disabled';
		}
		$default_meta_order = json_encode(
			array(
				'author',
				'date',
				'comments',
			)
		);
		$meta               = get_theme_mod( 'neve_post_meta_ordering', $default_meta_order );
		$meta               = json_decode( $meta, true );
		if ( empty( $meta ) ) {
			return 'disabled';
		}

		return 'enabled';
	}

	/**
	 * Get translatable strings.
	 *
	 * @return array
	 */
	private function get_strings() {
		return array(
			'sidebar' => __( 'Sidebar', 'neve' ),
		);
	}

	/**
	 * Add new Gutenberg templates on Otter plugin.
	 *
	 * @return array
	 */
	public function add_gutenberg_templates( $templates_list ) {
		$templates = array(
			array(
				'title'          => __( 'Neve Big Title', 'neve' ),
				'type'           => 'template',
				'keywords'       => array( 'big title', 'header', 'about' ),
				'categories'     => array( 'header' ),
				'template_url'   => get_template_directory_uri() . '/gutenberg/templates/big-title/template.json',
				'screenshot_url' => get_template_directory_uri() . '/gutenberg/templates/big-title/screenshot.jpg',
			),
			array(
				'title'          => __( 'Neve About Us', 'neve' ),
				'type'           => 'template',
				'keywords'       => array( 'about us', 'about', 'description', 'showcase' ),
				'categories'     => array( 'content' ),
				'template_url'   => get_template_directory_uri() . '/gutenberg/templates/about-us/template.json',
				'screenshot_url' => get_template_directory_uri() . '/gutenberg/templates/about-us/screenshot.jpg',
			),
			array(
				'title'          => __( 'Neve Our Focus', 'neve' ),
				'type'           => 'template',
				'keywords'       => array( 'focus', 'our focus', 'services', 'features', 'showcase' ),
				'categories'     => array( 'content' ),
				'template_url'   => get_template_directory_uri() . '/gutenberg/templates/our-focus/template.json',
				'screenshot_url' => get_template_directory_uri() . '/gutenberg/templates/our-focus/screenshot.png',
			),
			array(
				'title'          => __( 'Neve Our Team', 'neve' ),
				'type'           => 'template',
				'keywords'       => array( 'team', 'our team', 'employees', 'clients', 'members', 'people', 'image', 'card' ),
				'categories'     => array( 'content' ),
				'template_url'   => get_template_directory_uri() . '/gutenberg/templates/our-team/template.json',
				'screenshot_url' => get_template_directory_uri() . '/gutenberg/templates/our-team/screenshot.png',
			),
			array(
				'title'          => __( 'Neve Ribbon', 'neve' ),
				'type'           => 'template',
				'keywords'       => array( 'ribbon', 'statistics', 'numbers', 'clients', 'banner', 'logo', 'carousel' ),
				'categories'     => array( 'content' ),
				'template_url'   => get_template_directory_uri() . '/gutenberg/templates/ribbon/template.json',
				'screenshot_url' => get_template_directory_uri() . '/gutenberg/templates/ribbon/screenshot.png',
			),
			array(
				'title'          => __( 'Neve Features', 'neve' ),
				'type'           => 'template',
				'keywords'       => array( 'features', 'card', 'about', 'services', 'advantages', 'items', 'boxes', 'why' ),
				'categories'     => array( 'content' ),
				'template_url'   => get_template_directory_uri() . '/gutenberg/templates/features/template.json',
				'screenshot_url' => get_template_directory_uri() . '/gutenberg/templates/features/screenshot.png',
			),
			array(
				'title'          => __( 'Neve Pricing', 'neve' ),
				'type'           => 'template',
				'keywords'       => array( 'pricing', 'plan', 'packages', 'membership', 'product' ),
				'categories'     => array( 'content' ),
				'template_url'   => get_template_directory_uri() . '/gutenberg/templates/pricing/template.json',
				'screenshot_url' => get_template_directory_uri() . '/gutenberg/templates/pricing/screenshot.png',
			),
			array(
				'title'          => __( 'Neve Testimonials', 'neve' ),
				'type'           => 'template',
				'keywords'       => array( 'testimonials', 'review', 'feedback', 'testimonial', 'happy', 'clients' ),
				'categories'     => array( 'content' ),
				'template_url'   => get_template_directory_uri() . '/gutenberg/templates/testimonials/template.json',
				'screenshot_url' => get_template_directory_uri() . '/gutenberg/templates/testimonials/screenshot.png',
			),
			array(
				'title'          => __( 'Neve Footer', 'neve' ),
				'type'           => 'template',
				'keywords'       => array( 'footer', 'resources', 'links', 'credits', 'contact', 'social', 'sharing' ),
				'categories'     => array( 'footer' ),
				'template_url'   => get_template_directory_uri() . '/gutenberg/templates/footer/template.json',
				'screenshot_url' => get_template_directory_uri() . '/gutenberg/templates/footer/screenshot.png',
			),
		);

		$list = array_merge( $templates_list, $templates );

		return $list;
	}

}
