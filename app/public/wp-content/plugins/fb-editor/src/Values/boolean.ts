/**
 * Mirrors Fb\Values\BooleanValue: on/off, meaning decided by the output.
 */
import type { ResponsiveValue } from './value';

export class BooleanValue implements ResponsiveValue {
	constructor( public readonly on: boolean ) {}

	inheritFrom(): this {
		return this;
	}

	toJSON(): boolean {
		return this.on;
	}
}
