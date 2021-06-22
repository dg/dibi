<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi\NotSupportedException;
use Dibi\QueryParameter;

/**
 * Better OOP experience.
 */
trait NoParameterizedQueries
{
    public function addParameter(QueryParameter $param) : void {
        throw new NotSupportedException('Parameterized queries unsupported');
    }

    public function bindText(?string $value, ?string $length = null, ?string $encoding = null): QueryParameter
    {
        throw new NotSupportedException('Parameterized queries unsupported');
    }

	public function bindAsciiText(?string $value, ?string $length = null, ?string $encoding = null): QueryParameter
    {
        throw new NotSupportedException('Parameterized queries unsupported');
    }

	public function bindIdentifier(?string $value): QueryParameter
    {
        throw new NotSupportedException('Parameterized queries unsupported');
    }

	public function bindInt(?int $value): QueryParameter
    {
        throw new NotSupportedException('Parameterized queries unsupported');
    }

	public function bindNumeric(?float $value, string $precision, string $scale): QueryParameter
    {
        throw new NotSupportedException('Parameterized queries unsupported');
    }

	public function bindDate(?\DateTimeInterface $value): QueryParameter
    {
        throw new NotSupportedException('Parameterized queries unsupported');
    }

	public function bindDateTime(?\DateTimeInterface $value): QueryParameter
    {
        throw new NotSupportedException('Parameterized queries unsupported');
    }

	public function bindDateInterval(?\DateInterval $value): QueryParameter
    {
        throw new NotSupportedException('Parameterized queries unsupported');
    }
}
