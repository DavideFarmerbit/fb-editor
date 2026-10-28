/**
 * The block and its parts, on top of its sidebar: pick what the panels below
 * edit. Lists the parts with values (in any look) or added this session;
 * "Add part" offers the others. Removing a part drops all its looks.
 */
import { Button, Dropdown, DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { moreVertical, plus, trash } from '@wordpress/icons';
import type { ReactElement } from 'react';

import type { BlockDefinition } from '../Definition/block-definition';
import type { Part, PartId } from '../Parts/part';
import type { StatefulValues } from '../Values/stateful-values';
import './parts-tree.scss';

interface PartsTreeProps {
	readonly definition: BlockDefinition;
	readonly values: StatefulValues;
	/** The edited part; null: the block itself. */
	readonly current: PartId | null;
	/** Parts added this session (listed even while empty). */
	readonly added: readonly string[];
	readonly onSelect: ( part: PartId | null ) => void;
	readonly onAdd: ( part: Part ) => void;
	readonly onRemove: ( part: Part ) => void;
}

export function PartsTree( { definition, values, current, added, onSelect, onAdd, onRemove }: PartsTreeProps ): ReactElement {
	const isSet = ( part: Part ): boolean => values.looks().some( ( look ) => look.key.part === part.id );
	const listed = definition.parts.filter( ( part ) => isSet( part ) || added.includes( part.id ) || part.id === current );
	const available = definition.parts.filter( ( part ) => ! listed.includes( part ) );

	return (
		<div className="fb-parts">
			<div className="fb-parts__row">
				<Button
					className={ current === null ? 'fb-parts__item is-block is-current' : 'fb-parts__item is-block' }
					{ ...( current === null ? { 'aria-current': true } : {} ) }
					onClick={ () => onSelect( null ) }
				>
					{ definition.title }
				</Button>
			</div>
			{ listed.map( ( part ) => (
				<div key={ part.id } className="fb-parts__row is-part">
					<Button
						className={ [ 'fb-parts__item', 'is-part', isSet( part ) ? 'is-set' : '', part.id === current ? 'is-current' : '' ].filter( Boolean ).join( ' ' ) }
						{ ...( part.id === current ? { 'aria-current': true } : {} ) }
						onClick={ () => onSelect( part.id ) }
					>
						{ part.label }
					</Button>
					<DropdownMenu icon={ moreVertical } label={ __( 'Part options', 'fb-editor' ) } toggleProps={ { size: 'small' } } popoverProps={ { placement: 'bottom-end' } }>
						{ ( { onClose } ) => (
							<MenuGroup>
								<MenuItem
									icon={ trash }
									isDestructive
									onClick={ () => {
										onRemove( part );
										onClose();
									} }
								>
									{ __( 'Remove part', 'fb-editor' ) }
								</MenuItem>
							</MenuGroup>
						) }
					</DropdownMenu>
				</div>
			) ) }
			{ available.length > 0 && (
				// One more row of the tree, where the next part would go.
				<Dropdown
					className="fb-parts__row is-part"
					popoverProps={ { placement: 'bottom-start' } }
					renderToggle={ ( { isOpen, onToggle } ) => (
						<Button className="fb-parts__item is-add" icon={ plus } aria-expanded={ isOpen } onClick={ onToggle }>
							{ __( 'Add part', 'fb-editor' ) }
						</Button>
					) }
					renderContent={ ( { onClose } ) => (
						<MenuGroup>
							{ available.map( ( part ) => (
								<MenuItem
									key={ part.id }
									onClick={ () => {
										onAdd( part );
										onClose();
									} }
								>
									{ part.label }
								</MenuItem>
							) ) }
						</MenuGroup>
					) }
				/>
			) }
		</div>
	);
}
