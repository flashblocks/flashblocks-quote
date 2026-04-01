<?php
/**
 * Dynamic render callback for the flashblocks/flashblocks-quotes block.
 *
 * Available variables:
 *   $attributes  – block attributes array
 *   $content     – inner block content (unused; dynamic block)
 *   $block       – WP_Block instance (provides $block->context)
 *
 * @package FlashblocksQuotes
 */

// A genuine Query Loop context means postType is 'flashblocks_quote'.
// WordPress also injects a global postId/postType for the page being rendered —
// we must ignore that by checking postType, otherwise we'd load the wrong post.
$context_is_query_loop = ! empty( $block->context['postId'] )
	&& ( $block->context['postType'] ?? '' ) === 'flashblocks_quote';

$post_id = ! empty( $attributes['selectedPostId'] )
	? (int) $attributes['selectedPostId']
	: ( $context_is_query_loop ? (int) $block->context['postId'] : 0 );

if ( ! $post_id ) {
	return;
}

$post = get_post( $post_id );

if (
	! $post
	|| 'flashblocks_quote' !== $post->post_type
	|| 'publish' !== $post->post_status
) {
	return;
}

// Quote body — run through full content filter chain (blocks, autop, texturize).
$quote_content = apply_filters( 'the_content', $post->post_content );

// Author meta.
$author_name = get_post_meta( $post_id, '_flashblocks_quote_author_name', true );
$author_role = get_post_meta( $post_id, '_flashblocks_quote_author_role', true );

// Author photo via featured image.
$photo_id   = (int) get_post_thumbnail_id( $post_id );
$photo_html = '';

if ( $photo_id ) {
	/*
	 * Use the author name as alt text so screen readers announce who the photo
	 * depicts. If there is no name, pass an empty string — the image is then
	 * treated as decorative and skipped by assistive technology.
	 */
	$photo_html = wp_get_attachment_image(
		$photo_id,
		'thumbnail',
		false,
		array(
			'class' => 'wp-block-flashblocks-quote__author-photo',
			'alt'   => $author_name ? esc_attr( $author_name ) : '',
		)
	);
}

$has_attribution = $author_name || $author_role || $photo_html;

$text_align    = isset( $attributes['textAlign'] ) ? $attributes['textAlign'] : '';
$extra_classes = $text_align ? array( 'class' => 'has-text-align-' . esc_attr( $text_align ) ) : array();
$wrapper_attrs = get_block_wrapper_attributes( $extra_classes );
?>
<figure <?php echo $wrapper_attrs; ?>>

	<blockquote class="wp-block-flashblocks-quote__body">
		<?php echo $quote_content; ?>
	</blockquote>

	<?php if ( $has_attribution ) : ?>
	<figcaption class="wp-block-flashblocks-quote__attribution">

		<?php if ( $photo_html ) : ?>
		<div class="wp-block-flashblocks-quote__photo-wrap" aria-hidden="true">
			<?php echo $photo_html; ?>
		</div>
		<?php endif; ?>

		<cite class="wp-block-flashblocks-quote__cite">
			<?php if ( $author_name ) : ?>
				<span class="wp-block-flashblocks-quote__author-name">
					<?php echo esc_html( $author_name ); ?>
				</span>
			<?php endif; ?>

			<?php if ( $author_role ) : ?>
				<span class="wp-block-flashblocks-quote__author-role">
					<?php echo esc_html( $author_role ); ?>
				</span>
			<?php endif; ?>
		</cite>

	</figcaption>
	<?php endif; ?>

</figure>
