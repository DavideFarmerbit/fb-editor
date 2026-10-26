/**
 * Asks for a variant's name (new variant, rename).
 */
import { Button, Modal, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { ReactElement } from 'react';

interface VariantNameModalProps {
	readonly title: string;
	readonly confirmLabel: string;
	readonly initialName: string;
	readonly onConfirm: ( name: string ) => void;
	readonly onClose: () => void;
}

export function VariantNameModal( { title, confirmLabel, initialName, onConfirm, onClose }: VariantNameModalProps ): ReactElement {
	const [ name, setName ] = useState( initialName );
	const valid = name.trim() !== '';

	return (
		<Modal title={ title } onRequestClose={ onClose } size="small">
			<form
				className="fb-variant-modal"
				onSubmit={ ( event ) => {
					event.preventDefault();
					if ( valid ) {
						onConfirm( name.trim() );
						onClose();
					}
				} }
			>
				<TextControl __next40pxDefaultSize label={ __( 'Name', 'fb-editor' ) } value={ name } onChange={ setName } autoFocus />
				<div className="fb-variant-modal__actions">
					<Button __next40pxDefaultSize variant="tertiary" onClick={ onClose }>
						{ __( 'Cancel', 'fb-editor' ) }
					</Button>
					<Button __next40pxDefaultSize variant="primary" type="submit" disabled={ ! valid }>
						{ confirmLabel }
					</Button>
				</div>
			</form>
		</Modal>
	);
}
