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
