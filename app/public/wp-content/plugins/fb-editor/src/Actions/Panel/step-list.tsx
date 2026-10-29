/**
 * One section of a rule (its triggers, conditions or effects): each step
 * with its type and parameters, then "Add". Between steps, how they combine:
 * triggers "or", conditions "and", effects "then".
 */
import { Button, Dropdown, MenuGroup, MenuItem, SelectControl } from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { closeSmall } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { ActionKind, type ActionCatalog } from '../Catalog/action-catalog';
import { MAX_STEPS } from '../Rules/ActionRule.generated';
import { Step } from '../Rules/block-actions';
import { ParamField } from './param-field';

const JOINS: Readonly< Record< ActionKind, () => string > > = {
	[ ActionKind.Trigger ]: () => __( 'or', 'fb-editor' ),
	[ ActionKind.Condition ]: () => __( 'and', 'fb-editor' ),
	[ ActionKind.Effect ]: () => __( 'then', 'fb-editor' ),
};

const ADD_LABELS: Readonly< Record< ActionKind, () => string > > = {
	[ ActionKind.Trigger ]: () => __( 'Add trigger', 'fb-editor' ),
	[ ActionKind.Condition ]: () => __( 'Add condition', 'fb-editor' ),
	[ ActionKind.Effect ]: () => __( 'Add effect', 'fb-editor' ),
};

interface StepListProps {
	readonly kind: ActionKind;
	readonly label: string;
	readonly steps: readonly Step[];
	readonly catalog: ActionCatalog;
	readonly clientId: string;
	readonly onChange: ( steps: readonly Step[] ) => void;
}

export function StepList( { kind, label, steps, catalog, clientId, onChange }: StepListProps ): ReactElement {
	const types = catalog.ofKind( kind );
	const replace = ( index: number, step: Step ): void => onChange( steps.map( ( current, at ) => ( at === index ? step : current ) ) );

	return (
		<div className="fb-steps">
			<span className="fb-steps__label">{ label }</span>
			{ steps.map( ( step, index ) => (
				<Fragment key={ index }>
					{ index > 0 && <span className="fb-steps__join">{ JOINS[ kind ]() }</span> }
					<div className={ [ 'fb-step', step.isComplete() ? '' : 'is-incomplete' ].filter( Boolean ).join( ' ' ) }>
						<div className="fb-step__head">
							<SelectControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ label }
								hideLabelFromVision
								value={ step.type.name }
								options={ types.map( ( type ) => ( { value: type.name, label: type.label } ) ) }
								onChange={ ( name: string ) => {
									const type = catalog.find( kind, name );
									if ( type !== undefined && type !== step.type ) {
										replace( index, Step.of( type ) );
									}
								} }
							/>
							<Button size="small" icon={ closeSmall } label={ __( 'Remove', 'fb-editor' ) } onClick={ () => onChange( steps.filter( ( _, at ) => at !== index ) ) } />
						</div>
						{ step.type.params.map( ( param ) => (
							<ParamField key={ param.id } param={ param } value={ step.params.get( param.id ) } clientId={ clientId } onChange={ ( value ) => replace( index, step.withParam( param.id, value ) ) } />
						) ) }
					</div>
				</Fragment>
			) ) }
			{ steps.length < MAX_STEPS && (
				<Dropdown
					className="fb-steps__add"
					popoverProps={ { placement: 'bottom-start' } }
					renderToggle={ ( { isOpen, onToggle } ) => (
						<Button className="fb-steps__add-toggle" aria-expanded={ isOpen } onClick={ onToggle }>
							{ ADD_LABELS[ kind ]() }
						</Button>
					) }
					renderContent={ ( { onClose } ) => (
						<MenuGroup>
							{ types.map( ( type ) => (
								<MenuItem
									key={ type.name }
									onClick={ () => {
										onChange( [ ...steps, Step.of( type ) ] );
										onClose();
									} }
								>
									{ type.label }
								</MenuItem>
							) ) }
						</MenuGroup>
					) }
				/>
			) }
		</div>
	);
}
