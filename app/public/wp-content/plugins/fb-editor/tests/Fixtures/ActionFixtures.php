<?php

declare(strict_types=1);

namespace Fb\Tests\Fixtures;

use Fb\Actions\Library\Library;
use Fb\Actions\Rules\BlockActions;

/**
 * Builds tests/Fixtures/actions.json: the action catalog (as the editor
 * gets it) and raw `fb` attributes with their actions as PHP sanitizes them.
 * The JS suite parses the same attributes and must get the same JSON.
 */
final class ActionFixtures
{
	public const string FILE = __DIR__ . '/actions.json';

	public static function json(): string
	{
		$catalog = Library::catalog();

		$cases = [];
		foreach ( self::cases() as $name => $attribute ) {
			$actions = BlockActions::fromAttribute( $attribute, $catalog );
			$cases[] = [
				'name'      => $name,
				'attribute' => $attribute,
				'anchor'    => $actions->anchor,
				'rules'     => $actions->rules,
			];
		}

		return (string) wp_json_encode( [ 'catalog' => $catalog, 'cases' => $cases ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION ) . "\n";
	}

	/**
	 * @return array<string, mixed> Raw `fb` attributes, by case name.
	 */
	private static function cases(): array
	{
		$click = [ 'type' => 'click' ];
		$open  = [ 'type' => 'open-close', 'params' => [ 'target' => [ 'block' => 'pnl001' ] ] ];

		return [
			'every type, with its parameters'          => [
				'anchor'  => 'card01',
				'actions' => [
					[
						'on'   => [ $click, [ 'type' => 'hover' ], [ 'type' => 'enters-view', 'params' => [ 'everyTime' => true ] ], [ 'type' => 'page-load' ] ],
						'if'   => [
							[ 'type' => 'is-open', 'params' => [ 'target' => 'self', 'open' => false ] ],
							[ 'type' => 'has-class', 'params' => [ 'target' => [ 'block' => 'pnl001' ], 'className' => 'is-active', 'has' => true ] ],
						],
						'do'   => [
							[ 'type' => 'link', 'params' => [ 'link' => [ 'url' => '/about#team', 'newTab' => true ], 'label' => 'About us' ] ],
							[ 'type' => 'open-close', 'params' => [ 'mode' => 'close', 'target' => [ 'block' => 'pnl001' ], 'startOpen' => true, 'keepLayout' => false ] ],
							[ 'type' => 'class', 'params' => [ 'mode' => 'remove', 'className' => 'is-active', 'target' => 'self' ] ],
							[ 'type' => 'scroll-to', 'params' => [ 'target' => [ 'block' => 'pnl001' ], 'offset' => [ 'kind' => 'custom', 'value' => -4.5, 'unit' => 'rem' ] ] ],
						],
						'off'  => [ 'mobile', 'mobile', 'tablet' ],
						'undo' => 'leave',
					],
				],
			],
			'invalid parts dropped one by one'         => [
				'actions' => [
					[
						'on'   => [ [ 'type' => 'click', 'params' => [ 'junk' => 1 ] ], [ 'type' => 'nope' ], 'garbage', [ 'params' => [] ] ],
						'if'   => [ [ 'type' => 'has-class' ], [ 'type' => 'is-open', 'params' => [ 'open' => 'yes' ] ] ],
						'do'   => [
							$open,
							[ 'type' => 'class', 'params' => [ 'className' => 'Not A Class' ] ],
							[ 'type' => 'scroll-to', 'params' => [ 'target' => [ 'block' => '../evil' ], 'offset' => [ 'kind' => 'auto' ] ] ],
							[ 'type' => 'open-close', 'params' => [ 'mode' => 'explode' ] ],
						],
						'off'  => 'xs',
						'undo' => 'sometimes',
					],
				],
			],
			'rules without a trigger or effect dropped' => [
				'anchor'  => 'Not-An-Anchor',
				'actions' => [
					[ 'on' => [ [ 'type' => 'nope' ] ], 'do' => [ $open ] ],
					[ 'on' => [ $click ], 'do' => [ [ 'type' => 'link', 'params' => [ 'link' => [ 'url' => 'javascript:alert(1)' ] ] ] ] ],
					[ 'on' => [ $click ] ],
					'garbage',
				],
			],
			'too many steps and rules are cut'         => [
				'actions' => array_fill( 0, 12, [ 'on' => array_fill( 0, 8, $click ), 'do' => [ $open ] ] ),
			],
			'actions not a list'                        => [ 'anchor' => 'abc123', 'actions' => [ 'on' => [ $click ] ] ],
		];
	}
}
