/**
 * "Add setting": the block's controls not shown yet, grouped by panel, with
 * a search box focused on open (type to filter by setting or panel name;
 * Enter adds the first match, arrows move through the list). A control
 * whose condition fails at the inspected breakpoint is listed disabled with
 * what it needs, or hidden with the eye toggle (remembered for the
 * session).
 */
import { Button, Dropdown, MenuGroup, MenuItem, NavigableMenu, SearchControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { plus, seen, unseen } from '@wordpress/icons';
import type { KeyboardEvent, ReactElement } from 'react';

import { store } from '../../Editor/store';
import type { BlockDefinition, Control } from '../Definition/block-definition';
import type { ResolvedValues } from '../Definition/Condition/conditions';
import type { BlockValues } from '../Values/block-values';

interface AddSettingProps {
	readonly definition: BlockDefinition;
	/** Controls not in the sidebar yet. */
	readonly available: readonly Control[];
	readonly resolved: ResolvedValues;
	/** Controls with a type default are marked. */
	readonly defaults: BlockValues;
	readonly onAdd: ( control: Control ) => void;
}

/** Case- and accent-insensitive, for matching what's typed. */
function normalized( text: string ): string {
	return text
		.normalize( 'NFKD' )
		.replace( /[̀-ͯ]/g, '' )
		.toLowerCase();
}

interface MenuProps extends AddSettingProps {
	readonly onClose: () => void;
}

function AddSettingMenu( { definition, available, resolved, defaults, onAdd, onClose }: MenuProps ): ReactElement {
	const [ query, setQuery ] = useState( '' );
	const search = useRef< HTMLDivElement >( null );
	const list = useRef< HTMLDivElement >( null );
	const showUnavailable = useSelect( ( select ) => select( store ).isShowingUnavailableSettings(), [] );
	const { showUnavailableSettings } = useDispatch( store );

	useEffect( () => {
		search.current?.querySelector( 'input' )?.focus();
	}, [] );

	const blocked = ( control: Control ): boolean => control.visibleWhen !== null && ! control.visibleWhen.evaluate( resolved );
	const terms = normalized( query ).split( /\s+/ ).filter( Boolean );

	const groups = definition.layout
		.map( ( panel ) => {
			const panelText = normalized( panel.label );
			const controls = available.filter(
				( control ) =>
					panel.controls.includes( control.id ) &&
					( showUnavailable || ! blocked( control ) ) &&
					terms.every( ( term ) => normalized( control.label ).includes( term ) || panelText.includes( term ) )
			);
			return { panel, controls };
		} )
		.filter( ( group ) => group.controls.length > 0 );

	const add = ( control: Control ): void => {
		onAdd( control );
		onClose();
	};

	const onSearchKeyDown = ( event: KeyboardEvent ): void => {
		if ( event.key === 'Enter' ) {
			const first = groups.flatMap( ( group ) => group.controls ).find( ( control ) => ! blocked( control ) );
			if ( first !== undefined ) {
				event.preventDefault();
				add( first );
			}
		} else if ( event.key === 'ArrowDown' ) {
			event.preventDefault();
			list.current?.querySelector< HTMLElement >( '[role="menuitem"]:not([aria-disabled="true"])' )?.focus();
		}
	};

	return (
		<div className="fb-add-setting">
			<div className="fb-add-setting__search" ref={ search } onKeyDown={ onSearchKeyDown }>
				<SearchControl __nextHasNoMarginBottom label={ __( 'Search settings', 'fb-editor' ) } value={ query } onChange={ setQuery } />
				<Button
					size="compact"
					icon={ showUnavailable ? seen : unseen }
					label={ showUnavailable ? __( 'Hide settings that need something else first', 'fb-editor' ) : __( 'Show settings that need something else first', 'fb-editor' ) }
					isPressed={ ! showUnavailable }
					onClick={ () => showUnavailableSettings( ! showUnavailable ) }
				/>
			</div>
			<div className="fb-add-setting__list" ref={ list }>
				{ groups.length === 0 ? (
					<p className="fb-add-setting__empty">{ __( 'No matching settings.', 'fb-editor' ) }</p>
				) : (
					<NavigableMenu>
						{ groups.map( ( { panel, controls } ) => (
							<MenuGroup key={ panel.label } label={ panel.label }>
								{ controls.map( ( control ) => {
									const isBlocked = blocked( control );
									let info: string | null = null;
									if ( isBlocked && control.visibleWhen !== null ) {
										/* translators: %s: the condition */
										info = sprintf( __( 'Needs %s', 'fb-editor' ), control.visibleWhen.describe( definition ) );
									} else if ( defaults.has( control.id ) ) {
										info = __( 'Has default', 'fb-editor' );
									}
									return (
										<MenuItem
											key={ control.id }
											className="fb-add-setting__item"
											disabled={ isBlocked }
											{ ...( info === null ? {} : { info } ) }
											onClick={ () => add( control ) }
										>
											{ control.label }
										</MenuItem>
									);
								} ) }
							</MenuGroup>
						) ) }
					</NavigableMenu>
				) }
			</div>
		</div>
	);
}

export function AddSetting( props: AddSettingProps ): ReactElement | null {
	if ( props.available.length === 0 ) {
		return null;
	}

	return (
		<Dropdown
			className="fb-inspector__add"
			popoverProps={ { placement: 'bottom-start' } }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button size="compact" variant="secondary" icon={ plus } aria-expanded={ isOpen } onClick={ onToggle }>
					{ __( 'Add setting', 'fb-editor' ) }
				</Button>
			) }
			renderContent={ ( { onClose } ) => <AddSettingMenu { ...props } onClose={ onClose } /> }
		/>
	);
}
