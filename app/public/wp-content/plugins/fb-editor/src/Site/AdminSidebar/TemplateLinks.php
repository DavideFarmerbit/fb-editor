<?php

declare(strict_types=1);

namespace Fb\Site\AdminSidebar;

/**
 * Site Editor links for what the current page is built from: its template,
 * the template parts it uses (nested ones included), all templates.
 *
 * On a block theme the page is content + template, but from the frontend
 * the template is invisible: this names it.
 */
final readonly class TemplateLinks
{
	/**
	 * Empty when the theme isn't a block theme, the user can't edit
	 * templates, or no block template was used (a filtered template_include).
	 *
	 * @return list<SidebarLink>
	 */
	public function links(): array
	{
		global $_wp_current_template_id, $_wp_current_template_content;

		if ( ! wp_is_block_theme() || ! current_user_can( 'edit_theme_options' ) || ! is_string( $_wp_current_template_id ) || '' === $_wp_current_template_id ) {
			return [];
		}

		$template = get_block_template( $_wp_current_template_id );
		if ( null === $template ) {
			return [];
		}

		$links = [
			new SidebarLink(
				/* translators: %s: template name */
				sprintf( __( 'Template: %s', 'fb-editor' ), self::name( $template ) ),
				self::editUrl( $template->id, 'wp_template' ),
				SidebarIcon::Template,
				newTab: true,
				hint: 'custom' === $template->source ? __( 'Customized in the Site Editor', 'fb-editor' ) : __( 'From the theme', 'fb-editor' )
			),
		];

		$parts = [];
		if ( is_string( $_wp_current_template_content ) ) {
			self::collectParts( parse_blocks( $_wp_current_template_content ), $parts );
		}
		foreach ( $parts as $part ) {
			$links[] = new SidebarLink(
				/* translators: %s: template part name */
				sprintf( __( 'Part: %s', 'fb-editor' ), self::name( $part ) ),
				self::editUrl( $part->id, 'wp_template_part' ),
				SidebarIcon::TemplatePart,
				newTab: true
			);
		}

		$links[] = new SidebarLink( __( 'All templates', 'fb-editor' ), admin_url( 'site-editor.php?postType=wp_template' ), SidebarIcon::Templates, newTab: true );

		return $links;
	}

	/** Its title, else (an untitled custom one) its slug. */
	private static function name( \WP_Block_Template $template ): string
	{
		return '' === $template->title ? $template->slug : $template->title;
	}

	/** Same arguments as core's "Edit site" link; `canvas=edit` opens the editor directly. */
	private static function editUrl( string $id, string $postType ): string
	{
		return add_query_arg(
			[
				'postType' => $postType,
				'postId'   => $id,
				'canvas'   => 'edit',
			],
			admin_url( 'site-editor.php' )
		);
	}

	/**
	 * Template parts used by these blocks, following parts inside parts.
	 * A part is recorded before descending into it, so a circular
	 * reference stops by itself.
	 *
	 * @param array<mixed>                       $blocks parse_blocks() output.
	 * @param array<string, \WP_Block_Template> $found  Keyed by template id.
	 */
	private static function collectParts( array $blocks, array &$found ): void
	{
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			$attrs = is_array( $block['attrs'] ?? null ) ? $block['attrs'] : [];
			$slug  = $attrs['slug'] ?? null;

			if ( 'core/template-part' === ( $block['blockName'] ?? null ) && is_string( $slug ) && '' !== $slug ) {
				$theme = is_string( $attrs['theme'] ?? null ) && '' !== $attrs['theme'] ? $attrs['theme'] : get_stylesheet();
				$id    = "{$theme}//{$slug}";

				if ( ! isset( $found[ $id ] ) ) {
					$part = get_block_template( $id, 'wp_template_part' );
					if ( null !== $part ) {
						$found[ $id ] = $part;
						self::collectParts( parse_blocks( $part->content ), $found );
					}
				}
			}

			if ( is_array( $block['innerBlocks'] ?? null ) ) {
				self::collectParts( $block['innerBlocks'], $found );
			}
		}
	}
}
