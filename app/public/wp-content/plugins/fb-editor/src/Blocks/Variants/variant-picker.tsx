/**
 * Top of a block's panel: which variant it uses, plus (for theme editors)
 * saving its look as a variant and materializing one. Same frame as every
 * control: header line (label · menu), then one 40px row.
 */
import { DropdownMenu, MenuGroup, MenuItem, SelectControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { moreVertical } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { settings } from '../../Editor/settings';
import type { FbBlockState } from '../block-chrome';
import type { BlockDefinition } from '../Definition/block-definition';
import { materialize, saveAsNewVariant, updateVariant, type VariantChange } from './variant-actions';
import { VariantNameModal } from './variant-name-modal';
import { parseVariantSlug } from './variant-set';
import './variants.scss';

const NONE = '';

interface VariantPickerProps {
	readonly definition: BlockDefinition;
	readonly state: FbBlockState;
}

export function VariantPicker( { definition, state }: VariantPickerProps ): ReactElement | null {
	const [ naming, setNaming ] = useState( false );
	const { attribute, variant, variants } = state;

	if ( variants.all.variants.length === 0 && ! variants.canEdit ) {
		return null;
	}

	const apply = ( change: VariantChange ): void => {
		if ( change.saved !== null ) {
			state.saveVariants( change.saved );
		}
		state.setAttribute( change.attribute );
	};

	const options = [
		{ value: NONE, label: __( 'None', 'fb-editor' ) },
		...variants.all.variants.map( ( candidate ) => ( { value: candidate.slug, label: candidate.name } ) ),
	];
	// A variant that's gone stays visible, so the block's link to it is too.
	if ( attribute.variant !== null && variant === null ) {
		/* translators: %s: variant slug */
		options.push( { value: attribute.variant, label: sprintf( __( 'Missing: %s', 'fb-editor' ), attribute.variant ) } );
	}

	const hasOwn = ! attribute.values.isEmpty();

	return (
		<div className="fb-variant-picker">
			<div className="fb-control">
				<div className="fb-control__header">
					<span className="fb-control__label">{ __( 'Variant', 'fb-editor' ) }</span>
					{ variants.canEdit && (
						<DropdownMenu
							icon={ moreVertical }
							label={ __( 'Variant options', 'fb-editor' ) }
							toggleProps={ { size: 'small' } }
							popoverProps={ { placement: 'bottom-end' } }
						>
							{ ( { onClose } ) => (
								<MenuGroup>
									<MenuItem
										info={ __( 'From how this block looks now', 'fb-editor' ) }
										onClick={ () => {
											setNaming( true );
											onClose();
										} }
									>
										{ __( 'Save as new variant…', 'fb-editor' ) }
									</MenuItem>
									{ variant !== null && (
										<MenuItem
											disabled={ ! hasOwn }
											info={ __( 'Moves this block’s own settings into the variant', 'fb-editor' ) }
											onClick={ () => {
												apply( updateVariant( definition, settings.breakpoints, attribute, variant, variants.saved ) );
												onClose();
											} }
										>
											{ /* translators: %s: variant name */ sprintf( __( 'Update “%s”', 'fb-editor' ), variant.name ) }
										</MenuItem>
									) }
									{ variant !== null && (
										<MenuItem
											info={ __( 'Copies the variant’s settings into this block and unlinks it', 'fb-editor' ) }
											onClick={ () => {
												apply( materialize( attribute, variant ) );
												onClose();
											} }
										>
											{ __( 'Materialize', 'fb-editor' ) }
										</MenuItem>
									) }
								</MenuGroup>
							) }
						</DropdownMenu>
					) }
				</div>
				<SelectControl
					__next40pxDefaultSize
					label={ __( 'Variant', 'fb-editor' ) }
					hideLabelFromVision
					value={ attribute.variant ?? NONE }
					options={ options }
					onChange={ ( value: string ) => state.setVariant( parseVariantSlug( value ) ) }
				/>
			</div>
			{ naming && (
				<VariantNameModal
					title={ __( 'Save as new variant', 'fb-editor' ) }
					confirmLabel={ __( 'Save variant', 'fb-editor' ) }
					initialName=""
					onConfirm={ ( name ) =>
						apply( saveAsNewVariant( definition, settings.breakpoints, attribute, variant, variants.all, variants.saved, name ) )
					}
					onClose={ () => setNaming( false ) }
				/>
			) }
		</div>
	);
}
