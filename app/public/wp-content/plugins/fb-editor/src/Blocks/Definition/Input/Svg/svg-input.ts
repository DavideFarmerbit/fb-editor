/**
 * Mirrors Fb\Blocks\Definition\Input\Svg\SvgInput. Stored code is shown
 * only through sanitizeSvg() (svg.ts); PHP sanitizes it for real.
 */
import type { JsonValue } from '../../../../Json/json';
import { SvgValue } from '../../../../Values/content';

export class SvgInput {
	readonly kind = 'svg';

	parse( json: JsonValue ): SvgValue {
		return new SvgValue( json.string() );
	}

	static fromJson(): SvgInput {
		return new SvgInput();
	}
}
