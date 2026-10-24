<?php

declare(strict_types=1);

namespace Fb\Blocks\Defaults;

use Fb\Blocks\BlockRegistry;
use Fb\Blocks\Definition\BlockName;
use Fb\Blocks\Values\BlockValues;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Hookable;
use Fb\TypeScript\ExportToTypeScript;

/**
 * Saved block type defaults: one hidden post per block type and theme.
 *
 * - title:   the block name ("fb/section")
 * - slug:    block + theme ("fb-section__fb-theme"), see slug()
 * - content: the defaults, same JSON shape as a block's `fb` attribute
 *
 * Being posts, defaults get revisions, export/import and the editor's own
 * save flow (they show up in the "Save" panel like global styles). Content
 * is sanitized through the block definition on every save, whatever the
 * client sent.
 */
final readonly class BlockDefaultsPostType implements Hookable
{
	#[ExportToTypeScript]
	public const string POST_TYPE = 'fb_block_default';

	#[ExportToTypeScript]
	public const string REST_BASE = 'fb-block-defaults';

	public function __construct(
		private BlockRegistry $blocks,
		private BreakpointConfig $breakpoints,
	) {
	}

	/**
	 * Defaults belong to a theme, like global styles: switching theme
	 * doesn't carry over a design made for another one.
	 *
	 * "__" separates block and theme: sanitize_title() keeps underscores but
	 * collapses "--", which would make "a-b" + "c" collide with "a" + "b-c".
	 */
	public static function slug( BlockName $block, string $stylesheet ): string
	{
		return sanitize_title( "{$block->cssClass()}__{$stylesheet}" );
	}

	public function register(): void
	{
		add_action( 'init', $this->registerPostType( ... ) );
		add_filter( 'wp_insert_post_data', $this->sanitize( ... ), 10, 2 );
	}

	private function registerPostType(): void
	{
		// Same capability model as core's wp_global_styles.
		register_post_type(
			self::POST_TYPE,
			[
				'label'        => __( 'Block defaults', 'fb-editor' ),
				'public'       => false,
				'show_ui'      => false,
				'show_in_rest' => true,
				'rest_base'    => self::REST_BASE,
				// Reading requires edit_theme_options too.
				'rest_controller_class' => BlockDefaultsController::class,
				'supports'     => [ 'title', 'editor', 'revisions' ],
				'map_meta_cap' => true,
				'capabilities' => [
					'read'                   => 'edit_theme_options',
					'create_posts'           => 'edit_theme_options',
					'edit_posts'             => 'edit_theme_options',
					'edit_published_posts'   => 'edit_theme_options',
					'delete_published_posts' => 'edit_theme_options',
					'edit_others_posts'      => 'edit_theme_options',
					'delete_others_posts'    => 'edit_theme_options',
				],
			]
		);
	}

	/**
	 * @param array<mixed> $data    Slashed post data (boundary).
	 * @param array<mixed> $postarr Raw insert arguments (boundary).
	 * @return array<mixed>
	 */
	private function sanitize( array $data, array $postarr ): array
	{
		if ( self::POST_TYPE !== ( $data['post_type'] ?? null ) ) {
			return $data;
		}

		$title      = is_string( $data['post_title'] ?? null ) ? wp_unslash( $data['post_title'] ) : '';
		$content    = is_string( $data['post_content'] ?? null ) ? wp_unslash( $data['post_content'] ) : '';
		$definition = $this->blocks->definition( $title );

		// Undecodable or unknown block → stored as "no defaults".
		$values = null === $definition
			? null
			: BlockValues::fromAttribute( json_decode( $content ), $definition, $this->breakpoints->breakpoints() );

		$data['post_content'] = wp_slash( null === $values ? '{}' : (string) wp_json_encode( $values, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION ) );

		return $data;
	}
}
