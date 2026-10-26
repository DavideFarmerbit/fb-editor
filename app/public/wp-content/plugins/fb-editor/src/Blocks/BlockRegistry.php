<?php

declare(strict_types=1);

namespace Fb\Blocks;

use Fb\Blocks\Definition\BlockDefinition;
use Fb\Blocks\Rendering\BlockRenderer;
use Fb\CoreBlocks\CoreBlock;
use Fb\CoreBlocks\ManagedCoreBlock;
use Fb\Hookable;
use Fb\TypeScript\ExportToTypeScript;

/**
 * Every block fb defines controls for:
 * - fb blocks: registered here, rendered server-side; the editor script
 *   adds `edit`/`save` on top of the metadata WordPress bootstraps;
 * - managed core blocks: core registers and renders them; fb adds its
 *   `fb` attribute here and its classes/CSS when they render.
 *
 * Only a single `fb` attribute is declared: all control values live there.
 */
final readonly class BlockRegistry implements Hookable
{
	/** The single block attribute all control values live in. */
	#[ExportToTypeScript]
	public const string ATTRIBUTE = 'fb';

	/** JSON type of that attribute (block attribute schema). */
	#[ExportToTypeScript]
	public const string ATTRIBUTE_TYPE = 'object';

	/**
	 * @param list<Block>     $blocks
	 * @param list<CoreBlock> $coreBlocks
	 */
	public function __construct(
		private BlockRenderer $renderer,
		public array $blocks,
		public array $coreBlocks = [],
	) {
	}

	public function definition( string $name ): ?BlockDefinition
	{
		foreach ( $this->definitions() as $definition ) {
			if ( (string) $definition->name === $name ) {
				return $definition;
			}
		}

		return null;
	}

	/**
	 * fb blocks first, then managed core blocks.
	 *
	 * @return list<BlockDefinition>
	 */
	public function definitions(): array
	{
		return [
			...array_map( static fn ( Block $block ): BlockDefinition => $block->definition(), $this->blocks ),
			...array_map( static fn ( CoreBlock $block ): BlockDefinition => $block->definition(), $this->coreBlocks ),
		];
	}

	public function coreBlock( ManagedCoreBlock $managed ): ?CoreBlock
	{
		foreach ( $this->coreBlocks as $block ) {
			if ( $block->managed() === $managed ) {
				return $block;
			}
		}

		return null;
	}

	public function register(): void
	{
		add_action( 'init', $this->registerBlocks( ... ) );
		add_filter( 'register_block_type_args', $this->addAttributeToCoreBlocks( ... ), 10, 2 );
	}

	/**
	 * @return array{type: string, default: \stdClass}
	 */
	private static function attributeSchema(): array
	{
		return [ 'type' => self::ATTRIBUTE_TYPE, 'default' => new \stdClass() ];
	}

	private function registerBlocks(): void
	{
		foreach ( $this->blocks as $block ) {
			$definition = $block->definition();

			register_block_type(
				(string) $definition->name,
				[
					'api_version'     => '3',
					'title'           => $definition->title,
					'category'        => 'design',
					'attributes'      => [ self::ATTRIBUTE => self::attributeSchema() ],
					'supports'        => [
						'html'            => false,
						'className'       => true,
						'customClassName' => true,
					],
					'render_callback' => fn ( array $attributes, string $content ): string => $this->renderer->render( $block, $attributes, $content ),
				]
			);
		}
	}

	/**
	 * @param array<mixed> $args Core's registration args (boundary).
	 * @return array<mixed>
	 */
	private function addAttributeToCoreBlocks( array $args, string $name ): array
	{
		$managed = ManagedCoreBlock::tryFrom( $name );
		if ( null === $managed || null === $this->coreBlock( $managed ) ) {
			return $args;
		}

		$attributes                    = is_array( $args['attributes'] ?? null ) ? $args['attributes'] : [];
		$attributes[ self::ATTRIBUTE ] = self::attributeSchema();
		$args['attributes']            = $attributes;

		return $args;
	}
}
