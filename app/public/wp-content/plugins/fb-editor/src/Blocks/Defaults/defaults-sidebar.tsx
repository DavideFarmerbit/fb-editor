/**
 * "Block defaults" sidebar, modelled on the Site Editor's Styles → Blocks:
 * a list of block types, then each type's settings, with the editor canvas
 * as the page to check against. Works in the post and site editors.
 *
 * Edits go to the type's saved defaults (entity edits, saved with the
 * editor's Save button); the theme's defaults show as the fallback.
 */
import { Icon, Navigator } from '@wordpress/components';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor';
import { __, _n, sprintf } from '@wordpress/i18n';
import { brush, chevronLeft, chevronRight } from '@wordpress/icons';
import type { ReactElement } from 'react';

import { settings } from '../../Editor/settings';
import type { BlockDefinition } from '../Definition/block-definition';
import { Inspector } from '../Inspector/inspector';
import { useBlockDefaults } from './use-block-defaults';
import './defaults-sidebar.scss';

const SIDEBAR = 'fb-block-defaults';

function screenPath( definition: BlockDefinition ): string {
	return `/block/${ definition.className }`;
}

function BlockTypeItem( { definition }: { readonly definition: BlockDefinition } ): ReactElement {
	const { defaults } = useBlockDefaults( definition );
	const count = definition.controls.filter( ( control ) => defaults.values.has( control.id ) ).length;

	return (
		<Navigator.Button path={ screenPath( definition ) } className="fb-defaults__item">
			<span className="fb-defaults__item-title">{ definition.title }</span>
			<span className="fb-defaults__item-count">
				{ count === 0
					? __( 'No defaults', 'fb-editor' )
					: /* translators: %d: number of settings */ sprintf( _n( '%d setting', '%d settings', count, 'fb-editor' ), count ) }
			</span>
			<Icon icon={ chevronRight } className="fb-defaults__item-icon" />
		</Navigator.Button>
	);
}

function BlockTypeDefaults( { definition }: { readonly definition: BlockDefinition } ): ReactElement {
	const { defaults, save } = useBlockDefaults( definition );

	return (
		<>
			<div className="fb-defaults__header">
				<Navigator.BackButton icon={ chevronLeft } label={ __( 'Back to block types', 'fb-editor' ) } size="small" />
				<h2 className="fb-defaults__title">{ definition.title }</h2>
			</div>
			{ defaults.canEdit ? (
				<Inspector
					definition={ definition }
					values={ defaults.saved }
					fallback={ defaults.theme }
					fallbackStatus={ __( 'From theme', 'fb-editor' ) }
					memoryKey={ `defaults/${ definition.name }` }
					listFallbackControls
					onChange={ save }
					makeDefault={ null }
				/>
			) : (
				<p className="fb-defaults__notice">{ __( 'You are not allowed to change block defaults.', 'fb-editor' ) }</p>
			) }
		</>
	);
}

export function DefaultsSidebar(): ReactElement {
	const title = __( 'Block defaults', 'fb-editor' );

	return (
		<>
			<PluginSidebarMoreMenuItem target={ SIDEBAR } icon={ brush }>
				{ title }
			</PluginSidebarMoreMenuItem>
			<PluginSidebar name={ SIDEBAR } title={ title } icon={ brush } className="fb-defaults">
				<Navigator initialPath="/">
					<Navigator.Screen path="/">
						<p className="fb-defaults__intro">
							{ __( 'Settings every block of a type starts with. Blocks can still override them.', 'fb-editor' ) }
						</p>
						{ settings.blocks.map( ( definition ) => (
							<BlockTypeItem key={ definition.name } definition={ definition } />
						) ) }
					</Navigator.Screen>
					{ settings.blocks.map( ( definition ) => (
						<Navigator.Screen key={ definition.name } path={ screenPath( definition ) }>
							<BlockTypeDefaults definition={ definition } />
						</Navigator.Screen>
					) ) }
				</Navigator>
			</PluginSidebar>
		</>
	);
}
