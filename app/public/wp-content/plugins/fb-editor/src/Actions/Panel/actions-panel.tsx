/**
 * The block's Actions panel: its rules, each "on … if … do …".
 *
 * Rules are edited as drafts here and saved as far as they're complete
 * (ActionRule::savable): parsing drops a rule without a trigger or effect,
 * and a step missing a required value, so a half-written step would vanish
 * on the next render. Drafts follow the block when its saved rules change
 * elsewhere (undo, paste).
 */
import { Button, PanelBody } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { plus } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { ActionKind, type ActionCatalog } from '../action-catalog';
import { MAX_RULES } from '../BlockActions.generated';
import { ActionRule, Step, type BlockActions } from '../block-actions';
import { RuleCard } from './rule-card';
import './actions-panel.scss';

interface ActionsPanelProps {
	readonly catalog: ActionCatalog;
	readonly actions: BlockActions;
	readonly clientId: string;
	readonly onChange: ( actions: BlockActions ) => void;
}

function saved( rules: readonly ActionRule[] ): ActionRule[] {
	return rules.map( ( rule ) => rule.savable() ).filter( ( rule ): rule is ActionRule => rule !== null );
}

const keyOf = ( rules: readonly ActionRule[] ): string => JSON.stringify( rules );

/** A new rule that works as is: the first trigger, and the first effect needing no value. */
function newRule( catalog: ActionCatalog ): ActionRule | null {
	const trigger = catalog.ofKind( ActionKind.Trigger )[ 0 ];
	const effect = catalog.ofKind( ActionKind.Effect ).find( ( type ) => type.params.every( ( param ) => ! param.required ) );
	return trigger === undefined || effect === undefined ? null : new ActionRule( [ Step.of( trigger ) ], [], [ Step.of( effect ) ] );
}

export function ActionsPanel( { catalog, actions, clientId, onChange }: ActionsPanelProps ): ReactElement | null {
	const [ drafts, setDrafts ] = useState< readonly ActionRule[] >( actions.rules );

	// Saved rules changed elsewhere: start over from them.
	const savedKey = keyOf( actions.rules );
	useEffect( () => {
		setDrafts( ( current ) => ( keyOf( saved( current ) ) === savedKey ? current : actions.rules ) );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ savedKey ] );

	if ( catalog.types.length === 0 ) {
		return null;
	}

	const update = ( next: readonly ActionRule[] ): void => {
		setDrafts( next );
		const rules = saved( next );
		if ( keyOf( rules ) !== savedKey ) {
			onChange( actions.withRules( rules ) );
		}
	};
	const blank = newRule( catalog );

	return (
		<PanelBody className="fb-actions" title={ __( 'Actions', 'fb-editor' ) } initialOpen={ drafts.length > 0 }>
			{ drafts.map( ( rule, index ) => (
				<RuleCard
					key={ index }
					rule={ rule }
					index={ index }
					catalog={ catalog }
					clientId={ clientId }
					canDuplicate={ drafts.length < MAX_RULES }
					onChange={ ( next ) => update( drafts.map( ( current, at ) => ( at === index ? next : current ) ) ) }
					onDuplicate={ () => update( [ ...drafts.slice( 0, index + 1 ), rule, ...drafts.slice( index + 1 ) ] ) }
					onRemove={ () => update( drafts.filter( ( _, at ) => at !== index ) ) }
				/>
			) ) }
			{ drafts.length === 0 && <p className="fb-actions__empty">{ __( 'Make the block react: on a click, a hover…, open, close or change blocks.', 'fb-editor' ) }</p> }
			<Button
				className="fb-actions__add"
				variant="secondary"
				icon={ plus }
				disabled={ blank === null || drafts.length >= MAX_RULES }
				accessibleWhenDisabled
				onClick={ () => blank !== null && update( [ ...drafts, blank ] ) }
			>
				{ __( 'Add action', 'fb-editor' ) }
			</Button>
		</PanelBody>
	);
}
