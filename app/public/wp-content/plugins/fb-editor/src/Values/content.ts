/**
 * Mirrors Fb\Values\{TextValue, LinkValue, MediaValue, SvgValue}: content a
 * block renders (not CSS). Built only by their inputs.
 */
import { MediaKind } from './MediaKind.generated';
import type { ResponsiveValue } from './value';

export { MediaKind };

export class TextValue implements ResponsiveValue {
	constructor( public readonly text: string ) {}

	inheritFrom(): this {
		return this;
	}

	toJSON(): string {
		return this.text;
	}
}

export class LinkValue implements ResponsiveValue {
	constructor(
		public readonly url: string,
		public readonly newTab: boolean,
		public readonly nofollow: boolean
	) {}

	inheritFrom(): this {
		return this;
	}

	toJSON(): { url: string; newTab: boolean; nofollow: boolean } {
		return { url: this.url, newTab: this.newTab, nofollow: this.nofollow };
	}
}

export class MediaValue implements ResponsiveValue {
	constructor(
		public readonly id: number,
		public readonly kind: MediaKind
	) {}

	inheritFrom(): this {
		return this;
	}

	toJSON(): { id: number; kind: MediaKind } {
		return { id: this.id, kind: this.kind };
	}
}

/** Pasted SVG code. Sanitized by PHP when stored; by svg.ts before showing. */
export class SvgValue implements ResponsiveValue {
	constructor( public readonly markup: string ) {}

	inheritFrom(): this {
		return this;
	}

	toJSON(): string {
		return this.markup;
	}
}
