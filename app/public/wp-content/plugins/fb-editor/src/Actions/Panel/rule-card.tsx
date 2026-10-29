/**
 * One rule: On (triggers) → If (conditions) → Do (effects), then when its
 * effects undo themselves and the breakpoints it's off on. A rule missing a
 * trigger, an effect or a required value says what isn't saved yet.
 */
import { Button, SelectControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { copy, trash } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { describeBreakpoint } from '../../Breakpoints/Preview/preview';
import { settings } from '../../Editor/settings';
import { BreakpointSet } from '../../Values/breakpoint-set';
import { ActionKind, type ActionCatalog } from '../action-catalog';
import { Undo, type ActionRule } from '../block-actions';
import { StepList } from './step-list';

const UNDO_OPTIONS: ReadonlyArray< { readonly value: Undo; readonly label: () => string } > = [
	{ value: Undo.Never, label: () => __( 'Never', 'fb-editor' ) },
	{ value: Undo.Leave, label: () => __( 'When the pointer or focus leaves', 'fb-editor' ) },
	{ value: Undo.Outside, label: () => __( 'On a click outside', 'fb-editor' ) },
];

interface RuleCardProps {
	readonly rule: ActionRule;
	readonly index: number;
	readonly catalog: ActionCatalog;
	readonly clientId: string;
	readonly canDuplicate: boolean;
	readonly onChange: ( rule: ActionRule ) => void;
	readonly onDuplicate: () => void;
	readonly onRemove: () => void;
}

/** What isn't saved, or null when everything is. */
function pendingNote( rule: ActionRule ): string | null {
	const savable = rule.savable();
	if ( savable === null ) {
		return rule.triggers.length === 0 || rule.effects.length === 0
			? __( 'Needs a trigger and an effect: not saved yet.', 'fb-editor' )
			: __( 'Fill the required values: not saved yet.', 'fb-editor' );
	}
	const steps = ( value: ActionRule ): number => value.triggers.length + value.conditions.length + value.effects.length;
	return steps( savable ) < steps( rule ) ? __( 'Steps missing required values are not saved yet.', 'fb-editor' ) : null;
}

export function RuleCard( { rule, index, catalog, clientId, canDuplicate, onChange, onDuplicate, onRemove }: RuleCardProps ): ReactElement {
	const note = pendingNote( rule );
	const off = rule.off ?? new BreakpointSet( [] );

	return (
		<div className={ [ 'fb-rule', note === null ? '' : 'is-pending' ].filter( Boolean ).join( ' ' ) }>
			<div className="fb-rule__head">
				{ /* translators: %d: rule number */ }
				<span className="fb-rule__title">{ sprintf( __( 'Action %d', 'fb-editor' ), index + 1 ) }</span>
				<Button size="small" icon={ copy } label={ __( 'Duplicate', 'fb-editor' ) } disabled={ ! canDuplicate } accessibleWhenDisabled onClick={ onDuplicate } />
				<Button size="small" icon={ trash } label={ __( 'Remove', 'fb-editor' ) } isDestructive onClick={ onRemove } />
			</div>
			<StepList kind={ ActionKind.Trigger } label={ __( 'On', 'fb-editor' ) } steps={ rule.triggers } catalog={ catalog } clientId={ clientId } onChange={ ( steps ) => onChange( rule.withSteps( ActionKind.Trigger, steps ) ) } />
			<StepList kind={ ActionKind.Condition } label={ __( 'If', 'fb-editor' ) } steps={ rule.conditions } catalog={ catalog } clientId={ clientId } onChange={ ( steps ) => onChange( rule.withSteps( ActionKind.Condition, steps ) ) } />
			<StepList kind={ ActionKind.Effect } label={ __( 'Do', 'fb-editor' ) } steps={ rule.effects } catalog={ catalog } clientId={ clientId } onChange={ ( steps ) => onChange( rule.withSteps( ActionKind.Effect, steps ) ) } />
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Undo', 'fb-editor' ) }
				value={ rule.undo }
				options={ UNDO_OPTIONS.map( ( option ) => ( { value: option.value, label: option.label() } ) ) }
				onChange={ ( value: string ) => onChange( rule.withUndo( UNDO_OPTIONS.find( ( option ) => option.value === value )?.value ?? Undo.Never ) ) }
			/>
			<div className="fb-rule__off">
				<span className="fb-rule__off-label">{ __( 'Off on', 'fb-editor' ) }</span>
				<span className="fb-strip fb-strip--picker" role="group" aria-label={ __( 'Off on', 'fb-editor' ) }>
					{ settings.breakpoints.map( ( breakpoint ) => {
						const picked = off.has( breakpoint.id );
						return (
							<Button
								key={ breakpoint.id }
								className={ [ 'fb-strip__item', picked ? 'is-set' : '' ].filter( Boolean ).join( ' ' ) }
								aria-pressed={ picked }
								label={
									picked
										? /* translators: %s: breakpoint description */ sprintf( __( '%s: off', 'fb-editor' ), describeBreakpoint( breakpoint ) )
										: /* translators: %s: breakpoint description */ sprintf( __( '%s: on', 'fb-editor' ), describeBreakpoint( breakpoint ) )
								}
								showTooltip
								onClick={ () => onChange( rule.withOff( off.toggled( breakpoint.id ) ) ) }
							>
								{ breakpoint.label }
							</Button>
						);
					} ) }
				</span>
			</div>
			{ note !== null && <p className="fb-rule__note">{ note }</p> }
		</div>
	);
}
