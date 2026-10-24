/**
 * Mirrors Fb\Blocks\Definition\{Control, ControlSet, Panel, Layout,
 * BlockDefinition}. Built only from the JSON PHP exports, including every
 * class name: nothing about a block is re-derived here.
 */
import { InvalidJson, type JsonValue } from '../../Json/json';
import { outputFromJson, type StyleOutput } from '../../Styles/Output/outputs';
import { KeywordValue } from '../../Values/keyword';
import type { BlockValues } from '../Values/block-values';
import { conditionFromJson, type Condition } from './Condition/conditions';
import { parseControlId, type ControlId } from './control-id';
import { parseElementId, type ElementId } from './element-id';
import { inputFromJson, type Input } from './Input/inputs';
import { SelectInput } from './Input/Select/select-input';
import { Scope } from './Scope.generated';

export { Scope };

export class Control {
	constructor(
		public readonly id: ControlId,
		public readonly label: string,
		public readonly input: Input,
		public readonly output: StyleOutput | null,
		public readonly element: ElementId | null,
		public readonly scope: Scope,
		public readonly visibleWhen: Condition | null
	) {}
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
		public readonly className: string
	) {}
}

export class BlockDefinition {
	constructor(
		/** e.g. "fb/section" */
		public readonly name: string,
		public readonly title: string,
		/** e.g. "fb-section" */
		public readonly className: string,
		public readonly controls: readonly Control[],
		public readonly layout: readonly Panel[],
		public readonly elements: readonly Element[]
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

	/** Class of a declared element; throws for an unknown one. */
	elementClass( id: string ): string {
		const element = this.elements.find( ( candidate ) => candidate.id === id );
		if ( element === undefined ) {
			throw new InvalidJson( `${ this.name }: no element "${ id }"` );
		}
		return element.className;
	}

	/**
	 * Mirrors RenderContext::option(): the base value of a select control,
	 * or its fallback while nothing is chosen. Throws if there is neither.
	 */
	option( values: BlockValues, id: string ): string {
		const control = this.controls.find( ( candidate ) => candidate.id === id );
		if ( control === undefined || ! ( control.input instanceof SelectInput ) ) {
			throw new InvalidJson( `${ this.name }: "${ id }" is not a select control` );
		}

		const value = values.get( control.id ).at( values.baseBreakpoint() );
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
		json.get( 'visibleWhen' ).nullable( conditionFromJson )
	);
}

export function blockDefinitionFromJson( json: JsonValue ): BlockDefinition {
	return new BlockDefinition(
		json.get( 'name' ).string(),
		json.get( 'title' ).string(),
		json.get( 'className' ).string(),
		json.get( 'controls' ).list().map( controlFromJson ),
		json
			.get( 'layout' )
			.list()
			.map( ( panel ) => new Panel( panel.get( 'label' ).string(), panel.get( 'controls' ).list().map( parseControlId ) ) ),
		json
			.get( 'elements' )
			.list()
			.map( ( element ) => new Element( parseElementId( element.get( 'id' ) ), element.get( 'className' ).string() ) )
	);
}
