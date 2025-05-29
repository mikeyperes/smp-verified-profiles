<?php namespace smp_verified_profiles;

add_shortcode('post_mentioned_vocabulary', __NAMESPACE__ . '\shortcode_post_mentioned_vocabulary');
add_shortcode('vocabulary_mentioned_posts', __NAMESPACE__ . '\shortcode_vocabulary_mentioned_posts');
add_shortcode('wiki_mentioned_posts', __NAMESPACE__ . '\shortcode_wiki_mentioned_posts');

if ( ! function_exists( __NAMESPACE__ . '\\shortcode_post_mentioned_vocabulary' ) ) {
	/**
	 * Displays the ACF “mentioned_entities” field using Elementor template #7070
	 * in a 6‑column grid that collapses to 2 on mobile.
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string HTML or a hidden style tag if no entities are found.
	 */
	function shortcode_post_mentioned_vocabulary( $atts = [] ) {

		global $post;

		$no_results = '<style>.post_mentioned_vocabulary{display:none!important}</style>';
		if ( ! $post ) {
			return $no_results;
		}

		$entities = get_field( 'mentioned_entities', $post->ID );
		if ( empty( $entities ) ) {
			return $no_results;
		}

		$original_post = $post;
		$template_id   = 7070;                               // Elementor loop‑item template
       // $template_id   = 2486;                               // Elementor loop‑item template
        
		$frontend      = \Elementor\Plugin::instance()->frontend;

		ob_start();

		// Grid styles: 6 cols → 2 cols on mobile
		echo '<style>
			.post_mentioned_vocabulary{display:grid;grid-template-columns:repeat(1,1fr);gap:1rem;}
			@media (max-width:600px){
				.post_mentioned_vocabulary{grid-template-columns:repeat(1,1fr)!important;}
			}
		</style>';

		echo '<div class="post_mentioned_vocabulary">';

		foreach ( (array) $entities as $entity ) {
			if ( ! $entity || ! isset( $entity->ID ) ) {
				continue;
			}

			$GLOBALS['post'] = $entity;
			setup_postdata( $entity );

			// Render template with inline CSS for immediate styling
			echo $frontend->get_builder_content_for_display( $template_id, true );
		}

		echo '</div>';

		wp_reset_postdata();
		$GLOBALS['post'] = $original_post;

		return ob_get_clean();
	}
}




if ( ! function_exists( __NAMESPACE__ . '\\shortcode_vocabulary_mentioned_posts' ) ) {
	/**
	 * Lists every post that mentions the current entity, rendered with Elementor
	 * loop‑item template #2426 in a 6‑column grid (2 on mobile).
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string HTML or a hidden style tag if no matches are found.
	 */
	function shortcode_vocabulary_mentioned_posts( $atts = [] ) {

		global $post;

		$no_results = '<style>.vocabulary_mentioned_posts{display:none!important}</style>';
		if ( ! $post ) {
			return $no_results;
		}

		$entity_id = $post->ID;

		// Query posts referencing this entity in “mentioned_entities”
		$query = new \WP_Query( [
			'post_type'      => 'post',   // adjust if querying other post types
			'posts_per_page' => -1,
			'meta_query'     => [
				[
					'key'     => 'mentioned_entities',
					'value'   => '"' . $entity_id . '"',
					'compare' => 'LIKE',
				],
			],
		] );

		if ( ! $query->have_posts() ) {
			wp_reset_postdata();
			return $no_results;
		}

		$original_post = $post;
		$template_id   = 3602; 
        //$template_id   = 2426;
               
        
        
        
        // Elementor loop‑item template
		$frontend      = \Elementor\Plugin::instance()->frontend;

		ob_start();

		// Grid styles: 6 cols → 2 cols on mobile
		echo '<style>
			.vocabulary_mentioned_posts > .shortcode{display:grid;grid-template-columns:repeat(4,2fr);gap:1rem;margin-top:.5rem;}
			@media (max-width:600px){
				.vocabulary_mentioned_posts > .shortcode{grid-template-columns:repeat(1,1fr)!important;}
			}
		</style>';

		echo '<div class="vocabulary_mentioned_posts"><div class="shortcode">';

		while ( $query->have_posts() ) {
			$query->the_post();

			$GLOBALS['post'] = $post;    // give dynamic tags the current post context
			setup_postdata( $post );

			// Render template with inline CSS so styling loads immediately
			echo $frontend->get_builder_content_for_display( $template_id, true );
		}

		echo '</div></div>';

		wp_reset_postdata();
		$GLOBALS['post'] = $original_post;

		return ob_get_clean();
	}
}






if ( ! function_exists( __NAMESPACE__ . '\\shortcode_wiki_mentioned_posts' ) ) {
	/**
	 * Shows every post that mentions the current profile, rendered with Elementor template #2426
	 * in a 6‑column grid (2 on mobile).
	 *
	 * @param array $atts Shortcode attributes (unused).
	 * @return string HTML or a hidden style tag if no posts are found.
	 */
	function shortcode_wiki_mentioned_posts( $atts = [] ) {

		global $post;

		$no_results = '<style>.shortcode_wiki_mentioned_posts{display:none!important}</style>';
		if ( ! $post ) {
			return $no_results;
		}

		$profile_id = $post->ID;

		// Query posts that reference this profile in “mentioned_entities”
		$query = new \WP_Query( [
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'meta_query'     => [
				[
					'key'     => 'mentioned_entities',
					'value'   => '"' . $profile_id . '"',
					'compare' => 'LIKE',
				],
			],
		] );

		if ( ! $query->have_posts() ) {
			wp_reset_postdata();
			return $no_results;
		}

		$original_post = $post;
		$template_id   = 3027; //2426;
        $template_id   = 3602;                             // Elementor loop‑item template
		$frontend      = \Elementor\Plugin::instance()->frontend;

		ob_start();

		// Grid styles: 6 cols → 2 cols on mobile
		echo '<style>
			.shortcode_wiki_mentioned_posts .shortcode{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-top:.5rem;}
			@media (max-width:600px){
				.shortcode_wiki_mentioned_posts .shortcode{grid-template-columns:repeat(1,1fr)!important;}
			}
		</style>';

		echo '<div class="shortcode_wiki_mentioned_posts"><div class="shortcode">';

		while ( $query->have_posts() ) {
			$query->the_post();

			$GLOBALS['post'] = $post;           // set context for dynamic tags
			setup_postdata( $post );

			// Render template with inline CSS
			echo $frontend->get_builder_content_for_display( $template_id, true );
		}

		echo '</div></div>';

		wp_reset_postdata();
		$GLOBALS['post'] = $original_post;

		return ob_get_clean();
	}
}
