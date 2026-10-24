/**
 * The block sidebar, generated from the PHP definition: one panel per
 * Panel, one field per visible control. Controls whose show-only-if
 * condition fails at the active breakpoint are not shown.
 */
import { PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { store } from '../../Editor/store';
import type { BlockDefinition, Control } from '../Definition/block-definition';
import type { BlockValues } from '../Values/block-values';
import { ControlField } from './control-field';
import './inspector.scss';

interface InspectorProps {
	readonly definition: BlockDefinition;
	readonly values: BlockValues;
	readonly onChange: ( values: BlockValues ) => void;
}

export function Inspector( { definition, values, onChange }: InspectorProps ): ReactElement {
	const { breakpoint, breakpoints } = useSelect(
		( select ) => ( {
			breakpoint: select( store ).getBreakpoint(),
			breakpoints: select( store ).getBreakpoints(),
		} ),
		[]
	);
	const resolved = values.resolvedAt( breakpoint.id );

	const isVisible = ( control: Control ): boolean => control.visibleWhen?.evaluate( resolved ) ?? true;

	return (
		<div className="fb-inspector">
			<p className="fb-inspector__breakpoint">
				{ /* translators: %s: breakpoint label */ sprintf( __( 'Editing: %s', 'fb-editor' ), breakpoint.label ) }
			</p>
			{ definition.layout.map( ( panel ) => {
				const controls = panel.controls
					.map( ( id ) => definition.control( id ) )
					.filter( ( control ): control is Control => control !== undefined && isVisible( control ) );

				if ( controls.length === 0 ) {
					return null;
				}

				return (
					<PanelBody key={ panel.label } title={ panel.label }>
						{ controls.map( ( control ) => (
							<ControlField
								key={ control.id }
								control={ control }
								values={ values }
								breakpoint={ breakpoint }
								breakpoints={ breakpoints }
								onChange={ ( value ) => onChange( values.with( control, breakpoint.id, value ) ) }
							/>
						) ) }
					</PanelBody>
				);
			} ) }
		</div>
	);
}
