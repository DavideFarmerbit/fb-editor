<?php

declare(strict_types=1);

namespace Fb\Actions\Catalog;

use Fb\Blocks\Definition\Input\Input;
use Fb\Blocks\Definition\Input\Target\TargetInput;
use Fb\TypeScript\ExportToTypeScript;

/**
 * One setting of a trigger, condition or effect: edited with the same
 * inputs as block settings (so the sidebar is generated, and every stored
 * value is sanitized through the input).
 */
final readonly class Param implements \JsonSerializable
{
	#[ExportToTypeScript( regExp: true )]
	public const string PATTERN = '^[a-z][a-zA-Z0-9]*$';

	/** Name of the target parameter (see target()). */
	#[ExportToTypeScript]
	public const string TARGET = 'target';

	public function __construct(
		public string $id,
		public string $label,
		public Input $input,
		/** Without it the step is dropped; optional ones fall back to the runner's default. */
		public bool $required = false,
	) {
		if ( 1 !== preg_match( '/' . self::PATTERN . '/', $id ) ) {
			throw new \InvalidArgumentException( "Invalid parameter id \"{$id}\"." );
		}
	}

	/** The block it works on: itself unless another is picked. */
	public static function target(): self
	{
		return new self( self::TARGET, __( 'Target', 'fb-editor' ), new TargetInput() );
	}

	/**
	 * @return array{id: string, label: string, input: Input, required: bool}
	 */
	public function jsonSerialize(): array
	{
		return [
			'id'       => $this->id,
			'label'    => $this->label,
			'input'    => $this->input,
			'required' => $this->required,
		];
	}
}
