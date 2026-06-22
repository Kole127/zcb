<?php

add_action(
	'after_setup_theme',
	static function () {
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/css/editor.css' );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		$frontend_css = 'assets/css/frontend.css';

		wp_enqueue_style(
			'zcb-frontend',
			get_theme_file_uri( $frontend_css ),
			array(),
			filemtime( get_theme_file_path( $frontend_css ) )
		);
	}
);

add_action(
	'enqueue_block_assets',
	static function () {
		if ( is_admin() ) {
			$frontend_css = 'assets/css/frontend.css';

			wp_enqueue_style(
				'zcb-editor-frontend',
				get_theme_file_uri( $frontend_css ),
				array(),
				filemtime( get_theme_file_path( $frontend_css ) )
			);
		}
	}
);

function zcb_get_pattern_content( string $pattern_slug ): string {
	$pattern_path = get_theme_file_path( 'patterns/' . $pattern_slug . '.php' );

	if ( ! is_readable( $pattern_path ) ) {
		return '';
	}

	$pattern_content = (string) file_get_contents( $pattern_path );
	$php_close       = strpos( $pattern_content, '?>' );

	if ( false !== $php_close ) {
		$pattern_content = substr( $pattern_content, $php_close + 2 );
	}

	return trim( $pattern_content );
}

add_filter(
	'render_block_core/post-content',
	static function ( string $block_content ): string {
		if ( is_admin() || ! is_front_page() || trim( $block_content ) !== '' ) {
			return $block_content;
		}

		$pattern_content = zcb_get_pattern_content( 'zcb-naslovnica' );

		if ( '' === $pattern_content ) {
			return $block_content;
		}

		return do_blocks( $pattern_content );
	}
);

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'zcb seed-naslovnica',
		static function ( array $args, array $assoc_args ): void {
			$page = get_page_by_path( 'naslovnica' );

			if ( ! $page ) {
				$pages = get_posts(
					array(
						'post_type'              => 'page',
						'post_status'            => 'any',
						'title'                  => 'Naslovnica',
						'posts_per_page'         => 1,
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
					)
				);
				$page  = $pages[0] ?? null;
			}

			if ( ! $page ) {
				WP_CLI::error( 'Could not find a page with slug "naslovnica" or title "Naslovnica".' );
			}

			$pattern_content = zcb_get_pattern_content( 'zcb-naslovnica' );

			if ( '' === $pattern_content ) {
				WP_CLI::error( 'Could not read the ZCB Naslovnica pattern.' );
			}

			$force   = (bool) \WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );
			$dry_run = (bool) \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );

			if ( ! $force && trim( (string) $page->post_content ) !== '' ) {
				WP_CLI::error( 'Naslovnica already has content. Re-run with --force to replace it.' );
			}

			WP_CLI::log( sprintf( 'Target page: #%d %s', $page->ID, $page->post_title ) );

			if ( $dry_run ) {
				WP_CLI::log( $pattern_content );
				WP_CLI::success( 'Dry run complete. No database changes were made.' );
				return;
			}

			WP_CLI::confirm( 'Replace the Naslovnica page content with the ZCB Naslovnica pattern?' );

			$result = wp_update_post(
				array(
					'ID'           => $page->ID,
					'post_content' => $pattern_content,
				),
				true
			);

			if ( is_wp_error( $result ) ) {
				WP_CLI::error( $result );
			}

			WP_CLI::success( 'Naslovnica content updated.' );
		}
	);
}
