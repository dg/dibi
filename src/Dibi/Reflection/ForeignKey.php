<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Reflection;



/**
 * Reflection metadata class for a foreign key.
 *
 * @property-read string $name
 * @property-read array $references
 */
class ForeignKey
{
	private string $name;

	/** @var array of [local, foreign, onDelete, onUpdate] */
	private array $references;


	public function __construct(string $name, array $references)
	{
		$this->name = $name;
		$this->references = $references;
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function getReferences(): array
	{
		return $this->references;
	}
}
