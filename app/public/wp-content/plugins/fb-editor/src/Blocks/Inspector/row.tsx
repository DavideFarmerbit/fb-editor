/**
 * One value, one line: [optional side icon] [main input] [actions] [reset],
 * buttons right-aligned. Reset is always shown (disabled when there's
 * nothing to reset) so every row has the same shape. The
 * status ("Inherited from XL", "From Section default") is the row tooltip:
 * the greying and the breakpoint strip already show it at a glance.
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { reset } from '@wordpress/icons';
import type { ReactElement, ReactNode } from 'react';

interface RowProps {
	readonly children: ReactNode;
	/** Leading icon (e.g. which side a spacing row edits). */
	readonly icon?: ReactNode;
	/** Shown as tooltip: where the value comes from while not own. */
	readonly status?: string | null;
	/** The row shows a value it doesn't own (greyed). */
	readonly inherited?: boolean;
	/** Extra buttons between the input and the reset slot. */
	readonly actions?: ReactNode;
	/** Clears the own value; omitted when there's nothing to reset. */
	readonly onReset?: ( () => void ) | null;
}

export function Row( { children, icon, status, inherited, actions, onReset }: RowProps ): ReactElement {
	return (
		<div className={ inherited === true ? 'fb-row is-inherited' : 'fb-row' } { ...( status ? { title: status } : {} ) }>
			{ icon !== undefined && <span className="fb-row__label">{ icon }</span> }
			<div className="fb-row__main">{ children }</div>
			{ actions }
			<Button
				size="small"
				icon={ reset }
				label={ __( 'Reset', 'fb-editor' ) }
				className="fb-row__reset"
				disabled={ onReset === undefined || onReset === null }
				accessibleWhenDisabled
				onClick={ () => onReset?.() }
			/>
		</div>
	);
}
