/**
 * Mirrors Fb\Blocks\Definition\{Control, ControlSet, Panel, Layout,
 * BlockDefinition}. Built only from the JSON PHP exports, including every
 * class name: nothing about a block is re-derived here.
 */
import { InvalidJson, type JsonValue } from '../../Json/json';
import { outputFromJson, type StyleOutput } from '../../Styles/Output/outputs';
import { KeywordValue } from '../../Values/keyword';
import { BlockState } from '../States/BlockState.generated';
import type { VariantSlug } from '../Variants/variant-set';
import { VARIANT_SEPARATOR } from './BlockName.generated';
import type { StatefulValues } from '../Values/stateful-values';
import { conditionFromJson, type Condition, type ConditionLabels } from './Condition/conditions';
import { parseControlId, type ControlId } from './control-id';
import { parseElementId, type ElementId } from './element-id';
import { inputFromJson, type Input } from './Input/inputs';
import { SelectInput } from './Input/Select/select-input';
import { Scope } from './Scope.generated';

export { Scope };

/** Mirrors Placement: only inside a given parent whose values satisfy `when`. */
export class Placement {
	constructor(
		/** e.g. "fb/container" */
		public readonly parent: string,
		/** Evaluated against the parent's resolved values. */
		public readonly when: Condition
	) {}
}

export class Control {
	constructor(
		public readonly id: ControlId,
		public readonly label: string,
		public readonly input: Input,
		public readonly output: StyleOutput | null,
		public readonly element: ElementId | null,
		public readonly scope: Scope,
		public readonly visibleWhen: Condition | null,
		/** Only inside a given parent block; null: anywhere. */
		public readonly placement: Placement | null
	) {}

	/** Mirrors Control::isStateful(). */
	isStateful(): boolean {
		return this.scope === Scope.Responsive && this.output !== null && this.placement === null;
	}

	/** Mirrors Control::isPlaced(): belongs to the block's place, not its look. */
	isPlaced(): boolean {
		return this.placement !== null;
	}
}

export class Panel {
	constructor(
		public readonly label: string,
		public readonly controls: readonly ControlId[]
	) {}
}

export class Element {
	constructor(
		public readonly id: ElementId,
		/** e.g. "fb-section__inner", computed by PHP. */
		public readonly className: string,
		/** The class in the editor: core blocks keep core's ("wp-block-button__link"). */
		public readonly editorClassName: string
	) {}
}

/** A state the block can be styled for, as PHP describes it. */
export class StateInfo {
	constructor(
		public readonly id: BlockState,
		public readonly label: string,
		/** Appended to the root's selector, e.g. ":hover". */
		public readonly pseudoClass: string,
		/** The editor's: the real state or the preview class. */
		public readonly editorPseudoClass: string,
		/** Editor only: on the selected block while the state is inspected. */
		public readonly previewClass: string
	) {}
}

export class BlockDefinition implements ConditionLabels {
	constructor(
		/** e.g. "fb/section" */
		public readonly name: string,
		public readonly title: string,
		/** A core block fb manages (core renders it; fb adds controls). */
		public readonly isCore: boolean,
		/** e.g. "fb-section" */
		public readonly className: string,
		public readonly controls: readonly Control[],
		public readonly layout: readonly Panel[],
		public readonly elements: readonly Element[],
		/** In cascade order. */
		public readonly states: readonly StateInfo[]
	) {
		for ( const control of controls ) {
			for ( const reference of control.visibleWhen?.references() ?? [] ) {
				if ( this.control( reference ) === undefined ) {
					throw new InvalidJson( `${ name }: condition of "${ control.id }" references unknown control "${ reference }"` );
				}
			}
		}
	}

	control( id: ControlId ): Control | undefined {
		return this.controls.find( ( control ) => control.id === id );
	}

	controlLabel( id: ControlId ): string {
		return this.control( id )?.label ?? id;
	}

	optionLabel( id: ControlId, value: string ): string {
		const input = this.control( id )?.input;
		return input instanceof SelectInput ? input.options.find( ( option ) => option.value === value )?.label ?? value : value;
	}

	/** The panel a control is placed in (PHP guarantees exactly one). */
	panelOf( id: ControlId ): Panel | undefined {
		return this.layout.find( ( panel ) => panel.controls.includes( id ) );
	}

	/** Mirrors BlockName::variantClass(): "fb-button--primary". */
	variantClass( slug: VariantSlug ): string {
		return `${ this.className }${ VARIANT_SEPARATOR }${ slug }`;
	}

	/** Frontend class of a declared element; throws for an unknown one. */
	elementClass( id: string ): string {
		return this.element( id ).className;
	}

	/** Editor class of a declared element (differs for core blocks). */
	editorElementClass( id: string ): string {
		return this.element( id ).editorClassName;
	}

	private element( id: string ): Element {
		const element = this.elements.find( ( candidate ) => candidate.id === id );
		if ( element === undefined ) {
			throw new InvalidJson( `${ this.name }: no element "${ id }"` );
		}
		return element;
	}

	/**
	 * Mirrors RenderContext::option(): the base value of a select control
	 * from the first source that has one (the block's own, its variant's,
	 * the type default's; null sources skipped), else the input's fallback.
	 * Throws if there is none of them.
	 */
	option( id: string, ...sources: ( StatefulValues | null )[] ): string {
		const control = this.controls.find( ( candidate ) => candidate.id === id );
		if ( control === undefined || ! ( control.input instanceof SelectInput ) ) {
			throw new InvalidJson( `${ this.name }: "${ id }" is not a select control` );
		}

		let value = null;
		for ( const source of sources ) {
			value ??= source?.base.get( control.id ).at( source.base.baseBreakpoint() ) ?? null;
		}
		const option = ( value instanceof KeywordValue ? value.keyword : null ) ?? control.input.fallback;
		if ( option === null ) {
			throw new InvalidJson( `${ this.name }: "${ id }" is read by the editor markup but has no fallback` );
		}
		return option;
	}
}

function controlFromJson( json: JsonValue ): Control {
	return new Control(
		parseControlId( json.get( 'id' ) ),
		json.get( 'label' ).string(),
		inputFromJson( json.get( 'input' ) ),
		json.get( 'output' ).nullable( outputFromJson ),
		json.get( 'element' ).nullable( parseElementId ),
		json.get( 'scope' ).oneOf( Scope ),
		json.get( 'visibleWhen' ).nullable( conditionFromJson ),
		json.get( 'placement' ).nullable( ( placement ) => new Placement( placement.get( 'parent' ).string(), conditionFromJson( placement.get( 'when' ) ) ) )
	);
}

export function blockDefinitionFromJson( json: JsonValue ): BlockDefinition {
	return new BlockDefinition(
		json.get( 'name' ).string(),
		json.get( 'title' ).string(),
		json.get( 'isCore' ).boolean(),
		json.get( 'className' ).string(),
		json.get( 'controls' ).list().map( controlFromJson ),
		json
			.get( 'layout' )
			.list()
			.map( ( panel ) => new Panel( panel.get( 'label' ).string(), panel.get( 'controls' ).list().map( parseControlId ) ) ),
		json
			.get( 'elements' )
			.list()
			.map(
				( element ) =>
					new Element( parseElementId( element.get( 'id' ) ), element.get( 'className' ).string(), element.get( 'editorClassName' ).string() )
			),
		json
			.get( 'states' )
			.list()
			.map(
				( state ) =>
					new StateInfo(
						state.get( 'id' ).oneOf( BlockState ),
						state.get( 'label' ).string(),
						state.get( 'pseudoClass' ).string(),
						state.get( 'editorPseudoClass' ).string(),
						state.get( 'previewClass' ).string()
					)
			)
	);
}
