<?php
/**
 * Author:          Andrei Baicus <andrei@themeisle.com>
 * Created on:      31/08/2018
 *
 * @package Neve\Views\Pluggable
 */

namespace Neve\Views\Pluggable;

use Neve\Views\Base_View;

/**
 * Class Pagination
 *
 * @package Neve\Views\Pluggable
 */
class Pagination extends Base_View {
	/**
	 * Function that is run after instantiation.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
		add_filter( 'neve_filter_main_script_localization', array( $this, 'filter_localization' ) );
		add_action( 'neve_do_pagination', array( $this, 'render_pagination' ) );
		add_action( 'neve_post_navigation', array( $this, 'render_post_navigation' ) );
	}

	public function register_endpoints() {
		register_rest_route( 'posts/v1', '/page/(?P<page_number>\d+)/', array(
			'methods'  => \WP_REST_Server::READABLE,
			'callback' => array( $this, 'get_posts' )
		) );
	}

	/**
	 * Get paginated posts.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function get_posts( \WP_REST_Request $request ) {

		if ( empty( $request['page_number'] ) ) {
			return new \WP_REST_Response( '' );
		}

		$output = '';

		$args = array(
			'posts_per_page'      => get_option( 'posts_per_page' ),
			'post_type'           => 'post',
			'paged'               => $request['page_number'],
			'ignore_sticky_posts' => 1,
			'post_status'         => 'publish',
		);


		$query = new \WP_Query( $args );
		if ( $query->have_posts() ) {
			ob_start();
			while ( $query->have_posts() ) {
				$query->the_post();
				get_template_part( 'template-parts/content' );
			}
			wp_reset_postdata();
			$output = ob_get_contents();
			ob_end_clean();
		}

		return new \WP_REST_Response( $output );
	}

	/**
	 * Filter localization to add infinite scroll to the main script.
	 *
	 * @param array $data localization array.
	 *
	 * @return array
	 */
	public function filter_localization( $data ) {
		if ( ! $this->has_infinite_scroll() ) {
			return $data;
		}
		global $wp_query;
		$max_pages = $wp_query->max_num_pages;

		$data['infiniteScroll']         = 'enabled';
		$data['infiniteScrollMaxPages'] = $max_pages;
		$data['infiniteScrollEndpoint'] = rest_url( 'posts/v1/page/' );

		return $data;
	}

	/**
	 * Render the pagination.
	 *
	 * @param string $context not yet used might come in handy later.
	 */
	public function render_pagination( $context ) {
		if ( $context === 'single' ) {
			$this->render_single_pagination();

			return;
		}

		if ( ! $this->has_infinite_scroll() ) {
			echo wp_kses_post(
				paginate_links(
					array(
						'type' => 'list',
					)
				)
			);

			return;
		}
		echo wp_kses_post( '<div class="load-more-posts"><span class="nv-loader" style="display: none;"></span><span class="infinite-scroll-trigger"></span></div>' );
	}

	/**
	 * Render single post / page pagination.
	 */
	private function render_single_pagination() {
		wp_link_pages(
			array(
				'before'      => '<div class="page-numbers">',
				'after'       => '</div>',
				'link_before' => '<span>',
				'link_after'  => '</span>',
			)
		);
	}

	/**
	 * Render single post navigation links
	 */
	public function render_post_navigation() {
		$prev_format = '<div class="previous">%link</div>';
		$next_format = '<div class="next">%link</div>';

		$prev_link = sprintf(
			'<span class="nav-direction">%1$s</span><span>%2$s</span>',
			esc_html__( 'previous', 'neve' ),
			'%title'
		);
		$next_link = sprintf(
			'<span class="nav-direction">%1$s</span><span>%2$s</span>',
			esc_html__( 'next', 'neve' ),
			'%title'
		);

		echo '<div class="nv-post-navigation">';
		previous_post_link( $prev_format, $prev_link );
		next_post_link( $next_format, $next_link );
		echo '</div>';
	}

	/**
	 * Has infinite scroll.
	 *
	 * @return string
	 */
	private function has_infinite_scroll() {
		if ( is_search() ) {
			return false;
		}

		$pagination_type = get_theme_mod( 'neve_pagination_type', 'number' );
		if ( $pagination_type === 'infinite' ) {
			return true;
		}

		return false;
	}
}
