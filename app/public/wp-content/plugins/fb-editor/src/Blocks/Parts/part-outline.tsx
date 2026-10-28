/**
 * Editor only: a dashed outline on the part edited in the sidebar of the
 * selected block, so it's clear what the panels change. A ::before / ::after
 * shows it only once it draws something (it needs content).
 */
import { useSelect } from '@wordpress/data';
import { createPortal } from '@wordpress/element';
import type { ReactElement } from 'react';

import { store } from '../../Editor/store';
import { Selector } from '../../Styles/css';
import type { BlockDefinition } from '../Definition/block-definition';
import { LookKey } from '../Looks/look-key';

/** The block editor store, by name: its package ships no store types. */
const BLOCK_EDITOR = 'core/block-editor';

interface BlockSelection {
	isBlockSelected( clientId: string ): boolean;
}

interface PartOutlineProps {
	readonly definition: BlockDefinition;
	readonly clientId: string;
	/** Editor-only instance class of the block root. */
	readonly instanceClass: string;
	/** The block root, once mounted: tells us which document to write to. */
	readonly root: HTMLElement | null;
}

export function PartOutline( { definition, clientId, instanceClass, root }: PartOutlineProps ): ReactElement | null {
	const inspected = useSelect( ( select ) => select( store ).getInspectedLook( clientId ), [ clientId ] );
	const selected = useSelect( ( select ) => ( select( BLOCK_EDITOR ) as BlockSelection ).isBlockSelected( clientId ), [ clientId ] );
	const partId = LookKey.fromId( inspected ).part;
	const part = partId === null ? undefined : definition.part( partId );

	if ( ! selected || part === undefined || root === null ) {
		return null;
	}

	const selector = part.selector( Selector.forClass( instanceClass ), definition, true ).toString();
	return createPortal(
		<style>{ `${ selector }{outline:1px dashed var(--wp-admin-theme-color,#3858e9);outline-offset:-1px;}` }</style>,
		root.ownerDocument.head
	);
}
