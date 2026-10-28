/**
 * Page script of actions: sets up every block's rules (`data-fb-actions`,
 * written by ActionRenderer). Each rule: when any trigger fires, off
 * breakpoints aside, and every condition holds, its effects run in order.
 *
 * Built in, for every rule:
 * - undo "leave" / "outside": effects reversed when the hover ends, or on a
 *   click outside the block and its targets;
 * - rules that open things: the handle acts like a button (role, tabindex,
 *   Enter / Space), reports aria-expanded / aria-controls, and Escape closes
 *   what it opened, giving focus back to it.
 *
 * Runners are listed here, one per trigger, condition and effect type (the
 * PHP catalog's names): adding a type is a PHP class, its runner, and a line
 * below.
 */
import { classChange } from '../Library/class-change';
import { click } from '../Library/click';
import { entersView } from '../Library/enters-view';
import { hasClass } from '../Library/has-class';
import { hover } from '../Library/hover';
import { isOpenCondition } from '../Library/is-open';
import { isOpen, openClose, setOpen, startsOpen } from '../Library/open-close';
import { pageLoad } from '../Library/page-load';
import { scrollTo } from '../Library/scroll-to';
import { resolveTargets } from './targets';
import { isNativeControl, type ConditionRunner, type EffectRunner, type Rule, type Step, type StepContext, type TriggerRunner } from './types';

const TRIGGERS: readonly TriggerRunner[] = [ click, hover, entersView, pageLoad ];
const CONDITIONS: readonly ConditionRunner[] = [ isOpenCondition, hasClass ];
const EFFECTS: readonly EffectRunner[] = [ openClose, classChange, scrollTo ];

declare global {
	interface Window {
		/** Breakpoint id → the media query of its range (ActionRenderer). */
		fbActionBreakpoints?: Readonly< Record< string, string > >;
	}
}

/** Where the block is clicked: itself, or its only direct link / button (a Button block's <a>). */
function handleOf( block: HTMLElement ): HTMLElement {
	if ( block instanceof HTMLAnchorElement || block instanceof HTMLButtonElement ) {
		return block;
	}
	const inner = [ ...block.children ].filter( ( child ): child is HTMLElement => child instanceof HTMLAnchorElement || child instanceof HTMLButtonElement );
	return inner.length === 1 && inner[ 0 ] !== undefined ? inner[ 0 ] : block;
}

let ids = 0;

/** The element's id, given one if it has none (for aria-controls). */
function idOf( element: HTMLElement ): string {
	if ( element.id === '' ) {
		element.id = `fb-target-${ ++ids }`;
	}
	return element.id;
}

function setUp( block: HTMLElement, rule: Rule ): void {
	const handle = handleOf( block );
	const context = ( step: Step ): StepContext => ( {
		block,
		handle,
		params: step.params,
		targets: () => resolveTargets( block, step.params.target ),
	} );
	const pick = < R extends { type: string } >( runners: readonly R[], steps: readonly Step[] = [] ) =>
		steps.map( ( step ) => ( { runner: runners.find( ( runner ) => runner.type === step.type ), context: context( step ) } ) );

	const effects = pick( EFFECTS, rule.do ).filter( ( effect ): effect is { runner: EffectRunner; context: StepContext } => effect.runner !== undefined );
	const conditions = pick( CONDITIONS, rule.if );
	const queries = window.fbActionBreakpoints ?? {};
	const isOff = (): boolean => ( rule.off ?? [] ).some( ( id ) => queries[ id ] !== undefined && matchMedia( queries[ id ] ).matches );

	const opening = effects.filter( ( effect ) => effect.runner.opens === true );
	const opened = (): HTMLElement[] => opening.flatMap( ( effect ) => effect.context.targets() );
	const report = (): void => {
		if ( opening.length > 0 ) {
			handle.setAttribute( 'aria-expanded', String( opened().some( isOpen ) ) );
		}
	};

	let active = false;
	const run = (): void => {
		// An unknown condition never holds: better nothing than the wrong thing.
		if ( isOff() || ! conditions.every( ( condition ) => condition.runner?.holds( condition.context ) === true ) ) {
			return;
		}
		effects.forEach( ( effect ) => effect.runner.run( effect.context ) );
		active = true;
		report();
	};
	const undo = (): void => {
		if ( ! active ) {
			return;
		}
		effects.forEach( ( effect ) => effect.runner.undo?.( effect.context ) );
		active = false;
		report();
	};

	const triggers = pick( TRIGGERS, rule.on );
	if ( opening.length > 0 && triggers.some( ( trigger ) => trigger.runner?.interactive === true ) ) {
		if ( ! isNativeControl( handle ) ) {
			handle.setAttribute( 'role', 'button' );
			handle.tabIndex = 0;
		}
		handle.setAttribute( 'aria-controls', opened().map( idOf ).join( ' ' ) );
	}
	opening.filter( ( effect ) => startsOpen( effect.context ) ).forEach( ( effect ) => effect.context.targets().forEach( ( target ) => setOpen( target, true ) ) );
	report();

	triggers.forEach( ( trigger ) =>
		trigger.runner?.listen(
			trigger.context,
			() => run(),
			() => {
				if ( rule.undo === 'leave' ) {
					undo();
				}
			}
		)
	);

	if ( rule.undo === 'outside' ) {
		document.addEventListener( 'click', ( event ) => {
			const target = event.target;
			if ( active && target instanceof Node && ! block.contains( target ) && ! opened().some( ( element ) => element.contains( target ) ) ) {
				undo();
			}
		} );
	}

	if ( opening.length > 0 ) {
		document.addEventListener( 'keydown', ( event ) => {
			if ( event.key !== 'Escape' || ! opened().some( isOpen ) ) {
				return;
			}
			const focusInside = opened().some( ( element ) => element.contains( document.activeElement ) );
			// The whole rule is reversed (like a click outside); opened by
			// something else (starts open), only what it opens is closed.
			if ( active ) {
				undo();
			} else {
				opening.forEach( ( effect ) => effect.runner.undo?.( effect.context ) );
				report();
			}
			if ( focusInside ) {
				handle.focus();
			}
		} );
	}
}

function parseRules( json: string | undefined ): Rule[] {
	try {
		const rules: unknown = JSON.parse( json ?? '[]' );
		return Array.isArray( rules ) ? ( rules as Rule[] ) : [];
	} catch {
		return [];
	}
}

for ( const block of document.querySelectorAll< HTMLElement >( '[data-fb-actions]' ) ) {
	parseRules( block.dataset.fbActions ).forEach( ( rule ) => setUp( block, rule ) );
}
