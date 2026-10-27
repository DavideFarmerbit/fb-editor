/**
 * Two switches next to "Add setting", with the icons of core's Settings and
 * Styles tabs: which kinds of control the block's sidebar lists, in place
 * of core's tabs (no switching back and forth while both are wanted).
 * Either or both, never neither: the last one on can't be turned off.
 * Remembered for the session, for every block alike.
 */
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { cog, styles } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { store } from '../../Editor/store';
import type { SidebarView } from './inspector';

const SWITCHES: ReadonlyArray< { readonly view: SidebarView; readonly icon: ReactElement; readonly label: () => string } > = [
	{ view: 'settings', icon: cog, label: () => __( 'Settings', 'fb-editor' ) },
	{ view: 'styles', icon: styles, label: () => __( 'Styles', 'fb-editor' ) },
];

/** The other switch's view. */
function other( view: SidebarView ): SidebarView {
	return view === 'settings' ? 'styles' : 'settings';
}

export function SidebarViewFilter(): ReactElement {
	// null: both on; otherwise the only one on.
	const only = useSelect( ( select ) => select( store ).getSidebarView(), [] );
	const { setSidebarView } = useDispatch( store );

	return (
		<div className="fb-view-filter" role="group" aria-label={ __( 'Show', 'fb-editor' ) }>
			{ SWITCHES.map( ( { view, icon, label } ) => {
				const on = only === null || only === view;
				const last = only === view;
				return (
					<Button
						key={ view }
						size="compact"
						icon={ icon }
						isPressed={ on }
						label={ last ? `${ label() } (${ __( 'at least one is shown', 'fb-editor' ) })` : label() }
						showTooltip
						onClick={ () => {
							if ( last ) {
								return;
							}
							// Off: only the other one; on: both.
							setSidebarView( on ? other( view ) : null );
						} }
					/>
				);
			} ) }
		</div>
	);
}
