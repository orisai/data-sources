<?php declare(strict_types = 1);

namespace Orisai\DataSources\Exception;

use Orisai\Exceptions\LogicalException;
use Throwable;

final class EncodingFailure extends LogicalException
{

	public static function create(): self
	{
		return new self();
	}

	public static function fromPrevious(Throwable $previous): self
	{
		$self = new self();

		return $self
			->withPrevious($previous)
			->withMessage($previous->getMessage());
	}

}
