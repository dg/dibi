<?php declare(strict_types=1);

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibi.nette.org)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Reflection;



/**
 * Reflection metadata class for a foreign key.
 *
 * @property-read string $name
 * @property-read list<mixed[]> $references
 */
class ForeignKey
{
	public function __construct(
		private readonly string $name,
		/** @var  list<mixed[]> */
		private readonly array $references,
	) {
	}


	public function getName(): string
	{
		return $this->name;
	}


	/** @return list<mixed[]> */
	public function getReferences(): array
	{
		return $this->references;
	}
}
