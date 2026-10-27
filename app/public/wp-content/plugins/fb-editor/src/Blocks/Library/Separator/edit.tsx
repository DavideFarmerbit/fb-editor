/**
 * fb/separator editor markup. Mirrors Separator::render(): one line, or
 * line · icon · line when an SVG is set.
 */
import { useBlockProps } from '@wordpress/block-editor';
import type { ReactElement } from 'react';

import { sanitizeSvg } from '../../Definition/Input/Svg/svg';
import { BlockEditor, type FbBlockEditProps } from '../../register';
import { SvgValue } from '../../../Values/content';
import { ICON, LINE, NAME, SVG } from './Separator.generated';

function Edit( { definition, values, variant, defaults, rootProps }: FbBlockEditProps ): ReactElement {
	const blockProps = useBlockProps( { ...rootProps, role: 'separator' } );
	const svg = definition.value( SVG, values, variant, defaults );
	const markup = svg instanceof SvgValue ? sanitizeSvg( svg.markup ) : null;
	const line = <span className={ definition.elementClass( LINE ) } />;

	return (
		<div { ...blockProps }>
			{ line }
			{ markup !== null && (
				<>
					<span className={ definition.elementClass( ICON ) } aria-hidden="true" dangerouslySetInnerHTML={ { __html: markup } } />
					<span className={ definition.elementClass( LINE ) } />
				</>
			) }
		</div>
	);
}

export const separatorEditor = new BlockEditor( NAME, Edit, () => null );
