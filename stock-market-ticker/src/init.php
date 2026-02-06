<?php
/**
 * Blocks Initializer
 *
 * Enqueue CSS/JS of all the blocks.
 *
 * @since   1.0.0
 * @package CGB
 */

if (!function_exists( 'register_block_type')) exit;
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Enqueue Gutenberg block assets for both frontend + backend.
 *
 * `wp-blocks`: includes block type registration and related functions.
 *
 * @since 1.0.0
 */
function market_ticker_cgb_block_assets() {
	// Styles.
	wp_enqueue_style(
		'market-ticker-cgb-style-css', // Handle.
		plugins_url( 'dist/blocks.style.build.css', dirname( __FILE__ ) ), // Block style CSS.
		array( 'wp-blocks' ) // Dependency to include the CSS after it.
		// filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.style.build.css' ) // Version: filemtime — Gets file modification time.
	);
} // End function market-ticker_cgb_block_assets().

// Hook: Frontend assets.
add_action( 'enqueue_block_assets', 'market_ticker_cgb_block_assets' );

/**
 * Enqueue Gutenberg block assets for backend editor.
 *
 * `wp-blocks`: includes block type registration and related functions.
 * `wp-element`: includes the WordPress Element abstraction for describing the structure of your blocks.
 * `wp-i18n`: To internationalize the block's text.
 *
 * @since 1.0.0
 */
function market_ticker_cgb_editor_assets() {

    // 0) Bloqueo duro: NO cargar nada en Fluent*
    if (function_exists('get_current_screen')) {
        $screen = get_current_screen();
        if ($screen && !empty($screen->id)) {
            $id = strtolower($screen->id);
            if (strpos($id, 'fluent') !== false) return;
        }
    }

    $version = defined('stockdio_ticker_version') ? stockdio_ticker_version : '1.0.2';

    // 1) Cargar search.js (aquí suele estar stockdio_open_search_modal_from_block)
    wp_enqueue_script('jquery');

    wp_enqueue_script(
        'stockdio-ticker-search-js-editor',
        plugins_url('/assets/stockdio_search.js?v=' . $version, dirname(__FILE__)),
        array('jquery'),
        $version,
        true
    );

    // (opcional) CSS del search modal si lo necesita
    wp_enqueue_style(
        'stockdio-ticker-search-css-editor',
        plugins_url('/assets/stockdio_search.css?v=' . $version, dirname(__FILE__)),
        array(),
        $version
    );

    // 2) Enqueue del bloque
    wp_enqueue_script(
        'market-ticker-cgb-block-js',
        plugins_url('/dist/blocks.ticker.build.js?v=' . $version, dirname(__FILE__)),
        array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor'),
        $version,
        true
    );

    // 3) Globals que el bloque espera (antes del bloque)
    $opts = get_option('stockdio_ticker_options');
    if (!is_array($opts)) $opts = array();
    $root = plugins_url('assets', dirname(__FILE__));

    wp_add_inline_script(
        'market-ticker-cgb-block-js',
        'window.stockdio_root_folder = ' . wp_json_encode($root) . ';' .
        'window.stockdio_ticker_settings = ' . wp_json_encode($opts) . ';' .
        'window.stockdio_marker_ticker = 1;',
        'before'
    );

    // 4) CSS del editor
    wp_enqueue_style(
        'market-ticker-cgb-block-editor-css',
        plugins_url('dist/blocks.editor.build.css?v=' . $version, dirname(__FILE__)),
        array('wp-edit-blocks'),
        $version
    );
}
// End function market-ticker_cgb_editor_assets().

// Hook: Editor assets.
add_action( 'enqueue_block_editor_assets', 'market_ticker_cgb_editor_assets' );

if (!function_exists('stockdio_block_categories_function')) {	
	add_filter( 'block_categories', function( $categories, $post ) {
		return array_merge(
			$categories,
			array(
				array(
					'slug' => 'stockdio-financial-blocks',
					'title' => __( 'Stockdio Financial Visualizations', 'stockdio-financial-blocks' ),
				),
			)
		);
	}, 10, 2 );
	function stockdio_block_categories_function() {
		return;
	}
}

function register_market_ticker_api_blocks() {	
    register_block_type(
        'cgb/stockdio-market-ticker',
        array(
            'attributes' => array(
                'content' => array(
                    'type' => 'string',
                ),
                'className' => array(
                    'type' => 'string',
                ),
            ),
            'render_callback' => 'market_ticker_cgb_api_block_posts',
        )
    );
}

add_action('init', 'register_market_ticker_api_blocks');

function market_ticker_cgb_api_block_posts( $attributes ) {
    //add_action('wp_print_scripts', 'enqueueChartHistoricalAssets');
	return stockdio_ticker_func($attributes);
}

