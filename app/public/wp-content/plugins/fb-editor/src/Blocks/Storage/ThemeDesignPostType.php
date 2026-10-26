<?php

declare(strict_types=1);

namespace Fb\Blocks\Storage;

use Fb\Blocks\BlockRegistry;
use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Definition\BlockName;
use Fb\Breakpoints\BreakpointConfig;
use Fb\Breakpoints\BreakpointList;
use Fb\Hookable;

/**
 * A hidden post type holding one design post per block type and theme
 * (its defaults, its variants…):
 *
 * - title:   the block name ("fb/section")
 * - slug:    block + theme ("fb-section__fb-theme"), see slug()
 * - content: JSON, sanitized through the block definition on every save,
 *            whatever the client sent
 *
 * Being posts, they get revisions, export/import and the editor's own save
 * flow (they show up in the "Save" panel like global styles).
 */
abstract readonly class ThemeDesignPostType implements Hookable
{
	public function __construct(
		private BlockRegistry $blocks,
		private BreakpointConfig $breakpoints,
	) {
	}

	/**
	 * @return lowercase-string&non-empty-string
	 */
	abstract protected function postType(): string;

	abstract protected function restBase(): string;

	abstract protected function label(): string;

	/**
	 * The content to store for whatever was sent (decoded JSON, possibly
	 * garbage), in canonical form.
	 */
	abstract protected function sanitizeContent( mixed $json, BlockDefinition $definition, BreakpointList $breakpoints ): \JsonSerializable;

	/** Stored when the block is unknown or the content undecodable. */
	abstract protected function emptyContent(): string;

	/**
	 * Designs belong to a theme, like global styles: switching theme
	 * doesn't carry over a design made for another one.
	 *
	 * "__" separates block and theme: sanitize_title() keeps underscores but
	 * collapses "--", which would make "a-b" + "c" collide with "a" + "b-c".
	 */
	public static function slug( BlockName $block, string $stylesheet ): string
	{
		return sanitize_title( "{$block->cssClass()}__{$stylesheet}" );
	}

	/**
	 * The decoded content of a block type's published post, if any.
	 */
	public static function savedContent( string $postType, BlockName $block, string $stylesheet ): mixed
	{
		$posts = get_posts(
			[
				'post_type'        => $postType,
				'name'             => self::slug( $block, $stylesheet ),
				'post_status'      => 'publish',
				'numberposts'      => 1,
				'suppress_filters' => false,
			]
		);
		$post  = $posts[0] ?? null;

		return $post instanceof \WP_Post ? json_decode( $post->post_content ) : null;
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
			$this->postType(),
			[
				'label'                 => $this->label(),
				'public'                => false,
				'show_ui'               => false,
				'show_in_rest'          => true,
				'rest_base'             => $this->restBase(),
				// Reading requires edit_theme_options too.
				'rest_controller_class' => ThemeDesignController::class,
				'supports'              => [ 'title', 'editor', 'revisions' ],
				'map_meta_cap'          => true,
				'capabilities'          => [
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
		if ( $this->postType() !== ( $data['post_type'] ?? null ) ) {
			return $data;
		}

		$title      = is_string( $data['post_title'] ?? null ) ? wp_unslash( $data['post_title'] ) : '';
		$content    = is_string( $data['post_content'] ?? null ) ? wp_unslash( $data['post_content'] ) : '';
		$definition = $this->blocks->definition( $title );

		$data['post_content'] = wp_slash(
			null === $definition
				? $this->emptyContent()
				: (string) wp_json_encode(
					$this->sanitizeContent( json_decode( $content ), $definition, $this->breakpoints->breakpoints() ),
					JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
				)
		);

		return $data;
	}
}
