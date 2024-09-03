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
	public function __construct(
		private readonly string $name,
		private readonly array $references,
	) {
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
