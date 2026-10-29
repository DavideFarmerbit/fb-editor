<?php

declare(strict_types=1);

namespace Fb\Actions\Library\OpenClose;

use Fb\Actions\Catalog\Effect;
use Fb\Actions\Catalog\Param;
use Fb\Actions\Rendering\TargetHiding;
use Fb\Actions\Rules\ParamValues;
use Fb\Blocks\Definition\Input\Select\SelectInput;
use Fb\Blocks\Definition\Input\Target\TargetValue;
use Fb\Blocks\Definition\Input\Toggle\ToggleInput;
use Fb\TypeScript\ExportToTypeScript;
use Fb\Values\BooleanValue;

/**
 * Opens, closes or toggles the target: menus, accordions, panels. Open is
 * a state of the target (its Open look styles it). A target that doesn't
 * start open is hidden until opened, already when the page is rendered.
 */
final class OpenClose extends Effect
{
	public const string MODE        = 'mode';
	public const string START_OPEN  = 'startOpen';
	public const string KEEP_LAYOUT = 'keepLayout';

	/** On an open target (with [open]: what the Open state matches). */
	#[ExportToTypeScript]
	public const string OPEN_CLASS = 'is-fb-open';

	public function name(): string
	{
		return 'open-close';
	}

	public function label(): string
	{
		return __( 'Open / close', 'fb-editor' );
	}

	public function params(): array
	{
		return [
			new Param( self::MODE, __( 'Do', 'fb-editor' ), new SelectInput( OpenMode::class, OpenMode::Toggle ) ),
			Param::target(),
			new Param( self::START_OPEN, __( 'Starts open', 'fb-editor' ), new ToggleInput() ),
			new Param( self::KEEP_LAYOUT, __( 'Keep its space when closed', 'fb-editor' ), new ToggleInput() ),
		];
	}

	public function hidesTarget( ParamValues $params ): ?TargetHiding
	{
		$startOpen = $params->get( self::START_OPEN );
		if ( $startOpen instanceof BooleanValue && $startOpen->on ) {
			return null;
		}
		// Opening the block itself only makes sense when it's visible.
		$target = $params->get( Param::TARGET );
		if ( ! $target instanceof TargetValue || $target->isSelf() ) {
			return null;
		}

		$keep = $params->get( self::KEEP_LAYOUT );

		return $keep instanceof BooleanValue && $keep->on ? TargetHiding::KeepsLayout : TargetHiding::Removed;
	}
}
