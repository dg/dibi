<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2021 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;

class QueryParameter {
    use Strict;

	public $sqlType;
    public $phpType;
	public $value;

    public function __construct($value, $sqlType, $phpType)
    {
        $this->sqlType = $sqlType;
        $this->phpType = $phpType;
        $this->value = $value;
    }

	public function __toString() : string
	{
		return "QueryParameter Value={$this->value} SqlType:{$this->sqlType} PhpType:{$this->phpType}";
	}
}
