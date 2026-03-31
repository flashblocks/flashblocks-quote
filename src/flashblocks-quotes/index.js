/**
 * Block registration and block styles for flashblocks/flashblocks-quotes.
 * The meta sidebar panel for the CPT editor is registered in meta-panel.js.
 */

import { registerBlockType } from '@wordpress/blocks';

import './style.scss';
import './meta-panel';
import Edit from './edit';
import save from './save';
import metadata from './block.json';

registerBlockType( metadata, {
	edit: Edit,
	save,
} );
