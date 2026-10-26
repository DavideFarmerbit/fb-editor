/**
 * Mirrors Fb\Styles\Visibility, StyleBinding, RuleSet and StyleCompiler.
 * Same algorithm, same output, byte for byte (checked by the parity suite).
 */
import type { BreakpointId, BreakpointList } from '../Breakpoints/breakpoints';
import type { Presets } from '../Presets/presets';
import type { Responsive } from '../Values/responsive';
import { CssLayer, DeclarationSet, REVERT, Selector, wrapInLayer } from './css';
import type { StyleOutput } from './Output/outputs';

/** Breakpoints at which a control is hidden by its condition. */
export class Visibility {
	private constructor( private readonly hidden: ReadonlySet< string > ) {}

	static always(): Visibility {
		return new Visibility( new Set() );
	}

	static hiddenAt( breakpoints: readonly BreakpointId[] ): Visibility {
		return new Visibility( new Set( breakpoints ) );
	}

	isVisible( breakpoint: BreakpointId ): boolean {
		return ! this.hidden.has( breakpoint );
	}
}

export class StyleBinding {
	constructor(
		public readonly selector: Selector,
		public readonly output: StyleOutput,
		public readonly value: Responsive,
		public readonly visibility: Visibility = Visibility.always()
	) {}
}

/** Rules for one breakpoint: selector → declarations, first-seen order. */
class RuleSet {
	private readonly rules = new Map< string, DeclarationSet >();

	add( selector: Selector, declarations: DeclarationSet ): void {
		if ( declarations.size === 0 ) {
			return;
		}
		const key = selector.toString();
		this.rules.set( key, ( this.rules.get( key ) ?? DeclarationSet.empty() ).merge( declarations ) );
	}

	isEmpty(): boolean {
		return this.rules.size === 0;
	}

	toCss(): string {
		let css = '';
		this.rules.forEach( ( declarations, selector ) => {
			css += `${ selector }{${ declarations.toCss() }}`;
		} );
		return css;
	}
}

export class StyleCompiler {
	constructor(
		private readonly breakpoints: BreakpointList,
		private readonly presets: Presets
	) {}

	compile( layer: CssLayer, bindings: readonly StyleBinding[] ): string {
		const css = this.rules( bindings );
		return css === '' ? '' : wrapInLayer( layer, css );
	}

	/** The rules alone, not wrapped in a layer (mirrors StyleCompiler::rules()). */
	rules( bindings: readonly StyleBinding[] ): string {
		const byBreakpoint = this.breakpoints.map( () => new RuleSet() );

		for ( const binding of bindings ) {
			this.compileBinding( binding, byBreakpoint );
		}

		let css = '';
		this.breakpoints.map( ( breakpoint, index ) => {
			const rules = byBreakpoint[ index ];
			if ( rules === undefined || rules.isEmpty() ) {
				return;
			}
			const query = breakpoint.mediaQuery;
			css += query === null ? rules.toCss() : `@media ${ query }{${ rules.toCss() }}`;
		} );

		return css;
	}

	private compileBinding( binding: StyleBinding, byBreakpoint: readonly RuleSet[] ): void {
		let cascaded = DeclarationSet.empty();

		this.breakpoints.map( ( breakpoint, index ) => {
			const desired = binding.visibility.isVisible( breakpoint.id )
				? binding.output.declarations( binding.value.resolve( breakpoint.id, this.breakpoints ), this.presets )
				: DeclarationSet.empty();

			let emit = DeclarationSet.empty();

			for ( const property of binding.output.properties() ) {
				let want = desired.get( property );
				const have = cascaded.get( property );

				if ( want === null ) {
					// Nothing wanted here: only act if something cascades in.
					want = have === null || have === REVERT ? null : REVERT;
				}

				if ( want !== null && want !== have ) {
					emit = emit.with( property, want );
					cascaded = cascaded.with( property, want );
				}
			}

			byBreakpoint[ index ]?.add( binding.selector, emit );
		} );
	}
}
