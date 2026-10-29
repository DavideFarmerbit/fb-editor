/**
 * The page side of actions: rules as rendered by ActionRenderer (already
 * sanitized by PHP), and the shape of the runners that do the work. One
 * runner per trigger, condition and effect type, named like its PHP class.
 */

/** One trigger, condition or effect of a rule. */
export interface Step {
	readonly type: string;
	readonly params: Readonly< Record< string, unknown > >;
}

export interface Rule {
	readonly on: readonly Step[];
	readonly if?: readonly Step[];
	readonly do: readonly Step[];
	/** Breakpoint ids where the rule does nothing. */
	readonly off?: readonly string[];
	readonly undo?: 'never' | 'leave' | 'outside';
}

/** What a runner gets: the block, the step's parameters, and the targets they point at. */
export interface StepContext {
	/** The block the rule is on. */
	readonly block: HTMLElement;
	/** Where the block is clicked or focused: itself, or its only link / button (a Button block's <a>). */
	readonly handle: HTMLElement;
	readonly params: Readonly< Record< string, unknown > >;
	/** The elements the step's target parameter points at (the block itself by default). */
	targets(): HTMLElement[];
}

/** Calls `fire` whenever the rule should run. */
export interface TriggerRunner {
	readonly type: string;
	/** `leave` is called when what fired it ends (the pointer leaves…), for undo "leave". */
	listen( context: StepContext, fire: ( event: Event | null ) => void, leave: () => void ): void;
	/** Makes the handle act like a button (role, tabindex, keys) when the rule opens something. */
	readonly interactive?: boolean;
}

export interface ConditionRunner {
	readonly type: string;
	holds( context: StepContext ): boolean;
}

export interface EffectRunner {
	readonly type: string;
	run( context: StepContext ): void;
	/** Reverses run() (for undo "leave" / "outside"); absent: can't be undone. */
	undo?( context: StepContext ): void;
	/** Whether it opens or closes its targets (the trigger then reports aria-expanded). */
	readonly opens?: boolean;
}

/** A parameter read with a fallback when unset (optional parameters). */
export function param< T >( params: Readonly< Record< string, unknown > >, id: string, fallback: T, is: ( value: unknown ) => value is T ): T {
	const value = params[ id ];
	return is( value ) ? value : fallback;
}

/**
 * Already a keyboard-usable control: a button, or a link with an href (an
 * <a> without one, like a Button block with no URL, is neither focusable
 * nor clickable with Enter).
 */
export function isNativeControl( element: HTMLElement ): boolean {
	return element instanceof HTMLButtonElement || ( element instanceof HTMLAnchorElement && element.hasAttribute( 'href' ) );
}

export const isBoolean = ( value: unknown ): value is boolean => typeof value === 'boolean';
export const isString = ( value: unknown ): value is string => typeof value === 'string';
