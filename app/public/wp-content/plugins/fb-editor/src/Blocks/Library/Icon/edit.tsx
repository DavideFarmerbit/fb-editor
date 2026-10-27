/**
 * fb/icon editor markup. Mirrors Icon::render(): a span (or a link) holding
 * the SVG, sanitized for display. Without an SVG yet, a placeholder to load
 * an SVG file (or paste code in the sidebar). Links don't navigate in the
 * editor.
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Notice, Placeholder } from '@wordpress/components';
import { createElement, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { sanitizeSvg } from '../../Definition/Input/Svg/svg';
import { SvgFileButton } from '../../Definition/Input/Svg/svg-file-button';
import { BlockEditor, type FbBlockEditProps } from '../../register';
import { LinkValue, SvgValue, TextValue } from '../../../Values/content';
import { LABEL, LINK, NAME, SVG } from './Icon.generated';

/**
 * Editor only: the placeholder needs room, while the block's base CSS
 * makes an icon a 1em square (inline style beats every fb layer).
 */
const PLACEHOLDER_BOX = { display: 'block', width: 'auto', height: 'auto', lineHeight: 'normal' } as const;

function Edit( { definition, values, variant, defaults, setValues, rootProps }: FbBlockEditProps ): ReactElement {
	const [ error, setError ] = useState< string | null >( null );
	const svg = definition.value( SVG, values, variant, defaults );
	const label = definition.value( LABEL, values, variant, defaults );
	const link = definition.value( LINK, values, variant, defaults );
	const markup = svg instanceof SvgValue ? sanitizeSvg( svg.markup ) : null;
	const blockProps = useBlockProps( {
		...rootProps,
		...( label instanceof TextValue ? { 'aria-label': label.text } : {} ),
		...( markup === null ? { style: PLACEHOLDER_BOX } : {} ),
	} );

	if ( markup === null ) {
		const control = definition.control( definition.controlId( SVG ) );
		return (
			<div { ...blockProps }>
				<Placeholder label={ definition.title } instructions={ __( 'Load an SVG file, or paste SVG code in the sidebar.', 'fb-editor' ) }>
					<SvgFileButton
						onLoad={ ( loaded ) => {
							if ( control === undefined || sanitizeSvg( loaded ) === null ) {
								setError( __( 'Not a usable SVG.', 'fb-editor' ) );
								return;
							}
							setError( null );
							setValues( values.withBase( values.base.with( control, values.base.baseBreakpoint(), new SvgValue( loaded ) ) ) );
						} }
						onError={ setError }
					/>
					{ error !== null && (
						<Notice status="error" isDismissible={ false }>
							{ error }
						</Notice>
					) }
				</Placeholder>
			</div>
		);
	}

	return createElement( link instanceof LinkValue ? 'a' : 'span', {
		...blockProps,
		dangerouslySetInnerHTML: { __html: markup },
	} );
}

export const iconEditor = new BlockEditor( NAME, Edit, () => null );
