<?php

/**
 * Plugin Name:       Flashblocks Quotes
 * Description:       A quotes system with a custom post type, author meta, block styles, and Query Loop support.
 * Version:           0.1.0
 * Requires at least: 6.8
 * Requires PHP:      7.4
 * Author:            Fleenor Security
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       flashblocks-quotes
 * Flashblocks Module: yes
 * Flashblocks Category: Blocks
 * Flashblocks Tags: quotes, testimonials
 *
 * @package FlashblocksQuotes
 */

if (! defined('ABSPATH')) {
	exit;
}

define('FLASHBLOCKS_QUOTE_VERSION', '0.1.0');

require_once __DIR__ . '/includes/cpt.php';
require_once __DIR__ . '/includes/meta.php';

/**
 * Registers block type(s) from the build manifest.
 *
 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
 */
function flashblocks_quotes_block_init() {
	wp_register_block_types_from_metadata_collection(__DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php');
}
add_action('init', 'flashblocks_quotes_block_init');
