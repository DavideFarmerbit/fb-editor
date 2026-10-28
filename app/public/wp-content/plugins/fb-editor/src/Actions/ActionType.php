<?php

declare(strict_types=1);

namespace Fb\Actions;

use Fb\TypeScript\ExportToTypeScript;

/**
 * A trigger, condition or effect type: what a rule's step can be. Written
 * like a block: a class declaring its name, label and parameters; a small
 * TypeScript runner (same name) does the work on the page.
 *
 * Extend Trigger, Condition or Effect, not this.
 */
abstract class ActionType implements \JsonSerializable
{
	#[ExportToTypeScript( regExp: true )]
	public const string NAME_PATTERN = '^[a-z][a-z0-9-]*$';

	abstract public function kind(): ActionKind;

	/** Stored in rules, and the runner's name: never change it. */
	abstract public function name(): string;

	abstract public function label(): string;

	/**
	 * @return list<Param>
	 */
	public function params(): array
	{
		return [];
	}

	/**
	 * @return array{kind: ActionKind, name: string, label: string, params: list<Param>}
	 */
	public function jsonSerialize(): array
	{
		return [
			'kind'   => $this->kind(),
			'name'   => $this->name(),
			'label'  => $this->label(),
			'params' => $this->params(),
		];
	}
}
