<?php
/**
 * Registers the flashblocks_quote custom post type and quote_category taxonomy.
 *
 * @package FlashblocksQuotes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the flashblocks_quote post type.
 */
function flashblocks_register_quote_post_type() {
	$labels = array(
		'name'                  => _x( 'Quotes', 'post type general name', 'flashblocks-quotes' ),
		'singular_name'         => _x( 'Quote', 'post type singular name', 'flashblocks-quotes' ),
		'add_new'               => __( 'Add New', 'flashblocks-quotes' ),
		'add_new_item'          => __( 'Add New Quote', 'flashblocks-quotes' ),
		'edit_item'             => __( 'Edit Quote', 'flashblocks-quotes' ),
		'new_item'              => __( 'New Quote', 'flashblocks-quotes' ),
		'view_item'             => __( 'View Quote', 'flashblocks-quotes' ),
		'view_items'            => __( 'View Quotes', 'flashblocks-quotes' ),
		'search_items'          => __( 'Search Quotes', 'flashblocks-quotes' ),
		'not_found'             => __( 'No quotes found.', 'flashblocks-quotes' ),
		'not_found_in_trash'    => __( 'No quotes found in Trash.', 'flashblocks-quotes' ),
		'all_items'             => __( 'All Quotes', 'flashblocks-quotes' ),
		'archives'              => __( 'Quote Archives', 'flashblocks-quotes' ),
		'attributes'            => __( 'Quote Attributes', 'flashblocks-quotes' ),
		'insert_into_item'      => __( 'Insert into quote', 'flashblocks-quotes' ),
		'uploaded_to_this_item' => __( 'Uploaded to this quote', 'flashblocks-quotes' ),
		'menu_name'             => _x( 'Quotes', 'admin menu', 'flashblocks-quotes' ),
	);

	register_post_type(
		'flashblocks_quote',
		array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'rest_base'           => 'flashblocks-quotes',
			'menu_icon'           => 'dashicons-format-quote',
			'supports'            => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'revisions' ),
			'taxonomies'          => array( 'flashblocks_quote_category' ),
			'has_archive'         => false,
			'rewrite'             => array( 'slug' => 'quotes', 'with_front' => false ),
			'show_in_nav_menus'   => false,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		)
	);
}
add_action( 'init', 'flashblocks_register_quote_post_type' );

/**
 * Registers the flashblocks_quote_category taxonomy.
 */
function flashblocks_register_quote_category_taxonomy() {
	$labels = array(
		'name'              => _x( 'Quote Categories', 'taxonomy general name', 'flashblocks-quotes' ),
		'singular_name'     => _x( 'Quote Category', 'taxonomy singular name', 'flashblocks-quotes' ),
		'search_items'      => __( 'Search Quote Categories', 'flashblocks-quotes' ),
		'all_items'         => __( 'All Quote Categories', 'flashblocks-quotes' ),
		'parent_item'       => __( 'Parent Quote Category', 'flashblocks-quotes' ),
		'parent_item_colon' => __( 'Parent Quote Category:', 'flashblocks-quotes' ),
		'edit_item'         => __( 'Edit Quote Category', 'flashblocks-quotes' ),
		'update_item'       => __( 'Update Quote Category', 'flashblocks-quotes' ),
		'add_new_item'      => __( 'Add New Quote Category', 'flashblocks-quotes' ),
		'new_item_name'     => __( 'New Quote Category Name', 'flashblocks-quotes' ),
		'menu_name'         => __( 'Categories', 'flashblocks-quotes' ),
		'not_found'         => __( 'No quote categories found.', 'flashblocks-quotes' ),
	);

	register_taxonomy(
		'flashblocks_quote_category',
		'flashblocks_quote',
		array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'rest_base'         => 'flashblocks-quote-categories',
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'rewrite'           => array( 'slug' => 'quote-category', 'with_front' => false ),
			'show_ui'           => true,
		)
	);
}
add_action( 'init', 'flashblocks_register_quote_category_taxonomy' );

/**
 * Updates the placeholder text shown in the Gutenberg title field for quotes.
 *
 * @param string  $placeholder Default placeholder.
 * @param WP_Post $post        Current post object.
 * @return string
 */
function flashblocks_quote_title_placeholder( $placeholder, $post ) {
	if ( isset( $post->post_type ) && 'flashblocks_quote' === $post->post_type ) {
		return __( 'Title (optional — leave blank to auto-generate from the quote)', 'flashblocks-quotes' );
	}
	return $placeholder;
}
add_filter( 'enter_title_here', 'flashblocks_quote_title_placeholder', 10, 2 );

/**
 * Auto-generates a title from the first 7 words of the quote body when the
 * title is left empty.
 *
 * Runs on wp_insert_post_data so the slug is also generated correctly.
 *
 * @param array $data    Sanitized post data about to be inserted/updated.
 * @return array
 */
function flashblocks_quote_auto_title( $data ) {
	if ( 'flashblocks_quote' !== $data['post_type'] ) {
		return $data;
	}

	if ( '' !== trim( $data['post_title'] ) ) {
		return $data;
	}

	$plain = wp_strip_all_tags( $data['post_content'] );
	$plain = preg_replace( '/\s+/', ' ', trim( $plain ) );

	if ( $plain ) {
		$data['post_title'] = wp_trim_words( $plain, 7, '' );
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'flashblocks_quote_auto_title' );

/**
 * Adds an Author column to the quotes list table.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function flashblocks_quote_list_columns( $columns ) {
	// Insert after the title column.
	$insert = array( 'flashblocks_quote_author' => __( 'Author', 'flashblocks-quotes' ) );
	$pos    = array_search( 'title', array_keys( $columns ), true );

	return $pos !== false
		? array_slice( $columns, 0, $pos + 1, true )
			+ $insert
			+ array_slice( $columns, $pos + 1, null, true )
		: $columns + $insert;
}
add_filter( 'manage_flashblocks_quote_posts_columns', 'flashblocks_quote_list_columns' );

/**
 * Renders the Author column value for each row.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function flashblocks_quote_list_column_content( $column, $post_id ) {
	if ( 'flashblocks_quote_author' !== $column ) {
		return;
	}

	$name = get_post_meta( $post_id, '_flashblocks_quote_author_name', true );
	$role = get_post_meta( $post_id, '_flashblocks_quote_author_role', true );

	if ( $name ) {
		echo esc_html( $name );
		if ( $role ) {
			echo '<br><span style="color:#646970;">' . esc_html( $role ) . '</span>';
		}
	} else {
		echo '<span aria-hidden="true">—</span><span class="screen-reader-text">'
			. esc_html__( 'No author set', 'flashblocks-quotes' )
			. '</span>';
	}
}
add_action( 'manage_flashblocks_quote_posts_custom_column', 'flashblocks_quote_list_column_content', 10, 2 );
