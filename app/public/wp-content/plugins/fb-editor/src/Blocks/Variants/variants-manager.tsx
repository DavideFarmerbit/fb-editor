/**
 * A block type's variants in the Block defaults sidebar: rename, delete
 * (saved ones), reset to the theme's (saved overrides of theme variants).
 * Their looks are edited on a block: pick it, materialize, save back.
 */
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { moreVertical, trash } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { settings } from '../../Editor/settings';
import type { BlockDefinition } from '../Definition/block-definition';
import { StatefulValues } from '../Values/stateful-values';
import { useBlockVariants } from './use-block-variants';
import { VariantNameModal } from './variant-name-modal';
import { Variant, type VariantSlug } from './variant-set';
import './variants.scss';

export function VariantsManager( { definition }: { readonly definition: BlockDefinition } ): ReactElement {
	const { variants, save } = useBlockVariants( definition );
	const [ renaming, setRenaming ] = useState< Variant | null >( null );

	const rename = ( variant: Variant, name: string ): void => {
		// A theme-only variant gets a saved entry carrying just the name.
		const current = variants.saved.get( variant.slug ) ?? new Variant( variant.slug, name, StatefulValues.empty( definition, settings.breakpoints ) );
		save( variants.saved.with( current.withName( name ) ) );
	};
	const drop = ( slug: VariantSlug ): void => save( variants.saved.without( slug ) );

	return (
		<div className="fb-variants">
			<h3 className="fb-variants__title">{ __( 'Variants', 'fb-editor' ) }</h3>
			{ variants.all.variants.length === 0 && (
				<p className="fb-variants__empty">{ __( 'None yet. Style a block, then “Save as new variant” from its Variant menu.', 'fb-editor' ) }</p>
			) }
			{ variants.all.variants.map( ( variant ) => {
				const fromTheme = variants.theme.get( variant.slug ) !== null;
				const overridden = fromTheme && variants.saved.get( variant.slug ) !== null;
				return (
					<div key={ variant.slug } className="fb-variants__item">
						<span className="fb-variants__name">{ variant.name }</span>
						{ fromTheme && (
							<span className="fb-variants__source">{ overridden ? __( 'Theme, changed', 'fb-editor' ) : __( 'Theme', 'fb-editor' ) }</span>
						) }
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
											onClick={ () => {
												setRenaming( variant );
												onClose();
											} }
										>
											{ __( 'Rename…', 'fb-editor' ) }
										</MenuItem>
										{ overridden && (
											<MenuItem
												info={ __( 'Drops the changes made here', 'fb-editor' ) }
												onClick={ () => {
													drop( variant.slug );
													onClose();
												} }
											>
												{ __( 'Reset to theme', 'fb-editor' ) }
											</MenuItem>
										) }
										{ ! fromTheme && (
											<MenuItem
												icon={ trash }
												isDestructive
												info={ __( 'Blocks using it fall back to the default', 'fb-editor' ) }
												onClick={ () => {
													drop( variant.slug );
													onClose();
												} }
											>
												{ __( 'Delete', 'fb-editor' ) }
											</MenuItem>
										) }
									</MenuGroup>
								) }
							</DropdownMenu>
						) }
					</div>
				);
			} ) }
			{ renaming !== null && (
				<VariantNameModal
					title={ __( 'Rename variant', 'fb-editor' ) }
					confirmLabel={ __( 'Rename', 'fb-editor' ) }
					initialName={ renaming.name }
					onConfirm={ ( name ) => rename( renaming, name ) }
					onClose={ () => setRenaming( null ) }
				/>
			) }
		</div>
	);
}
