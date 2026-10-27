/**
 * Editor UI for MediaInput, on one line: a "Choose…/Replace…" button
 * opening the media library, then (right-aligned) a thumbnail or the file
 * name for video, then reset. Same order as the SVG input.
 */
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import type { ReactElement } from 'react';

import { MediaKind, MediaValue } from '../../../../Values/content';
import type { ControlFieldProps } from '../../../Inspector/control-field';
import { readControl } from '../../../Inspector/control-reading';
import { Row } from '../../../Inspector/row';
import type { MediaInput } from './media-input';
import { useMedia } from './use-media';
import './media-control.scss';

interface MediaFieldProps extends ControlFieldProps {
	readonly input: MediaInput;
}

/** The library's own type of an item: "image", "video"… */
function kindOf( item: unknown ): MediaKind | null {
	const type = typeof item === 'object' && item !== null && 'type' in item ? item.type : null;
	return Object.values( MediaKind ).find( ( kind ) => kind === type ) ?? null;
}

function idOf( item: unknown ): number | null {
	return typeof item === 'object' && item !== null && 'id' in item && typeof item.id === 'number' ? item.id : null;
}

export function MediaField( props: MediaFieldProps ): ReactElement {
	const { input, onChange } = props;
	const reading = readControl( props );
	const shown = reading.shown instanceof MediaValue ? reading.shown : null;
	const media = useMedia( shown?.id ?? null );

	return (
		<Row status={ reading.status } inherited={ reading.own === null } onReset={ reading.own === null ? null : () => onChange( null ) }>
			<div className="fb-media">
				<MediaUploadCheck>
					<MediaUpload
						allowedTypes={ [ ...input.kinds ] }
						{ ...( shown === null ? {} : { value: shown.id } ) }
						onSelect={ ( item: unknown ) => {
							const id = idOf( item );
							const kind = kindOf( item );
							if ( id !== null && kind !== null && input.kinds.includes( kind ) ) {
								onChange( new MediaValue( id, kind ) );
							}
						} }
						render={ ( { open }: { open: () => void } ) => (
							<Button __next40pxDefaultSize variant="secondary" onClick={ open }>
								{ shown === null ? __( 'Choose…', 'fb-editor' ) : __( 'Replace…', 'fb-editor' ) }
							</Button>
						) }
					/>
				</MediaUploadCheck>
				{ shown !== null &&
					( shown.kind === MediaKind.Image && media !== null ? (
						<img className="fb-media__thumbnail" src={ media.thumbnail } alt="" />
					) : (
						<span className="fb-media__name">{ media === null ? `#${ shown.id }` : media.url.split( '/' ).pop() }</span>
					) ) }
			</div>
		</Row>
	);
}
