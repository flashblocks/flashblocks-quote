/**
 * Editor component for flashblocks/flashblocks-quotes.
 *
 * Selection controls (category filter + post picker) live in the block sidebar
 * via InspectorControls. The canvas shows only the quote preview.
 *
 * - Inside a Query Loop: reads context.postId, no picker shown.
 * - Standalone: sidebar controls let the user filter by category and pick a quote.
 */

import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	BlockControls,
	AlignmentControl,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import {
	ComboboxControl,
	PanelBody,
	SelectControl,
	Placeholder,
	Spinner,
} from '@wordpress/components';
import { useState } from '@wordpress/element';

export default function Edit( { attributes, setAttributes, context } ) {
	const { selectedPostId, textAlign } = attributes;

	// A genuine Query Loop context means the parent is iterating over
	// flashblocks_quote posts. WordPress also injects a global postId/postType
	// for the page being edited — we must ignore that by checking postType.
	const contextPostId   = context?.postId;
	const contextPostType = context?.postType;
	const isInQueryLoop   = !! contextPostId && contextPostType === 'flashblocks_quote';
	const postId          = selectedPostId || ( isInQueryLoop ? contextPostId : 0 );

	// Local UI state — not persisted in block attributes.
	const [ categoryFilter, setCategoryFilter ] = useState( '' );
	const [ searchInput, setSearchInput ]       = useState( '' );

	// ── Fetch categories for the filter dropdown ─────────────────────────────
	const { categoryOptions } = useSelect( ( select ) => {
		const records = select( coreStore ).getEntityRecords(
			'taxonomy',
			'flashblocks_quote_category',
			{ per_page: 100, hide_empty: false, _fields: 'id,name' }
		);
		return {
			categoryOptions: [
				{ value: '', label: __( 'All Categories', 'flashblocks-quotes' ) },
				...( records ?? [] ).map( ( term ) => ( {
					value: String( term.id ),
					label: term.name,
				} ) ),
			],
		};
	}, [] );

	// ── Fetch quotes for the picker (filtered by category + search) ──────────
	const { pickerOptions, isLoadingOptions } = useSelect(
		( select ) => {
			const { getEntityRecords, isResolving } = select( coreStore );
			const query = {
				per_page: 20,
				status: 'publish',
				_fields: 'id,title',
				...( searchInput ? { search: searchInput } : {} ),
				...( categoryFilter
					? { 'flashblocks-quote-categories': Number( categoryFilter ) }
					: {} ),
			};
			const records = getEntityRecords( 'postType', 'flashblocks_quote', query );
			return {
				pickerOptions: ( records ?? [] ).map( ( p ) => ( {
					value: String( p.id ),
					label: p.title?.rendered || `Quote #${ p.id }`,
				} ) ),
				isLoadingOptions: isResolving( 'getEntityRecords', [
					'postType',
					'flashblocks_quote',
					query,
				] ),
			};
		},
		[ categoryFilter, searchInput ]
	);

	// ── Fetch the active post for the canvas preview ─────────────────────────
	const { quotePost, featuredImage, isLoadingPost } = useSelect(
		( select ) => {
			if ( ! postId ) {
				return { quotePost: null, featuredImage: null, isLoadingPost: false };
			}
			const { getEntityRecord, isResolving } = select( coreStore );
			const post    = getEntityRecord( 'postType', 'flashblocks_quote', postId );
			const mediaId = post?.featured_media;
			const media   = mediaId ? getEntityRecord( 'root', 'media', mediaId ) : null;
			return {
				quotePost:     post ?? null,
				featuredImage: media ?? null,
				isLoadingPost: isResolving( 'getEntityRecord', [
					'postType',
					'flashblocks_quote',
					postId,
				] ),
			};
		},
		[ postId ]
	);

	const blockProps = useBlockProps( {
		className: textAlign ? `has-text-align-${ textAlign }` : undefined,
	} );

	const alignmentToolbar = (
		<BlockControls group="block">
			<AlignmentControl
				value={ textAlign }
				onChange={ ( val ) => setAttributes( { textAlign: val } ) }
			/>
		</BlockControls>
	);

	// ── Sidebar controls (standalone only) ───────────────────────────────────
	const sidebarControls = ! isInQueryLoop && (
		<InspectorControls>
			<PanelBody
				title={ __( 'Quote Selection', 'flashblocks-quotes' ) }
				initialOpen={ true }
			>
				<SelectControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'Category', 'flashblocks-quotes' ) }
					value={ categoryFilter }
					options={ categoryOptions }
					onChange={ ( val ) => {
						setCategoryFilter( val );
						setSearchInput( '' );
					} }
				/>
				<ComboboxControl
					__nextHasNoMarginBottom
					label={ __( 'Quote', 'flashblocks-quotes' ) }
					help={ __( 'Type to search by title.', 'flashblocks-quotes' ) }
					value={ selectedPostId ? String( selectedPostId ) : '' }
					options={ pickerOptions }
					onFilterValueChange={ setSearchInput }
					onChange={ ( val ) => {
						if ( val ) setAttributes( { selectedPostId: Number( val ) } );
					} }
					isLoading={ isLoadingOptions }
				/>
			</PanelBody>
		</InspectorControls>
	);

	// ── Canvas: no quote selected ─────────────────────────────────────────────
	if ( ! isInQueryLoop && ! selectedPostId ) {
		return (
			<>
				{ alignmentToolbar }
				{ sidebarControls }
				<div { ...blockProps }>
					<Placeholder
						icon="format-quote"
						label={ __( 'Flashblocks Quote', 'flashblocks-quotes' ) }
						instructions={ __(
							'Choose a quote using the block settings in the sidebar.',
							'flashblocks-quotes'
						) }
					/>
				</div>
			</>
		);
	}

	// ── Canvas: loading ───────────────────────────────────────────────────────
	if ( isLoadingPost || ( postId && ! quotePost ) ) {
		return (
			<>
				{ alignmentToolbar }
				{ sidebarControls }
				<div { ...blockProps }>
					<Spinner />
				</div>
			</>
		);
	}

	// ── Canvas: post not found ────────────────────────────────────────────────
	if ( ! quotePost ) {
		return (
			<>
				{ alignmentToolbar }
				{ sidebarControls }
				<div { ...blockProps }>
					<Placeholder
						icon="warning"
						label={ __( 'Quote not found', 'flashblocks-quotes' ) }
						instructions={ __(
							'The selected quote could not be found. Choose another in the sidebar.',
							'flashblocks-quotes'
						) }
					/>
				</div>
			</>
		);
	}

	// ── Canvas: preview ───────────────────────────────────────────────────────
	const authorName     = quotePost.meta?._flashblocks_quote_author_name || '';
	const authorRole     = quotePost.meta?._flashblocks_quote_author_role || '';
	const photoUrl       = featuredImage?.source_url || '';
	const content        = quotePost.content?.rendered || '';
	const hasAttribution = authorName || authorRole || photoUrl;

	return (
		<>
			{ alignmentToolbar }
			{ sidebarControls }
			<figure { ...blockProps }>
				<blockquote
					className="wp-block-flashblocks-quote__body"
					// eslint-disable-next-line react/no-danger
					dangerouslySetInnerHTML={ { __html: content } }
				/>
				{ hasAttribution && (
					<figcaption className="wp-block-flashblocks-quote__attribution">
						{ photoUrl && (
							<div
								className="wp-block-flashblocks-quote__photo-wrap"
								aria-hidden="true"
							>
								<img
									className="wp-block-flashblocks-quote__author-photo"
									src={ photoUrl }
									alt={ authorName || '' }
								/>
							</div>
						) }
						<cite className="wp-block-flashblocks-quote__cite">
							{ authorName && (
								<span className="wp-block-flashblocks-quote__author-name">
									{ authorName }
								</span>
							) }
							{ authorRole && (
								<span className="wp-block-flashblocks-quote__author-role">
									{ authorRole }
								</span>
							) }
						</cite>
					</figcaption>
				) }
			</figure>
		</>
	);
}
