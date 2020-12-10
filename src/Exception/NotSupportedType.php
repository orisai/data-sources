<?php declare(strict_types = 1);

namespace Orisai\DataSources\Exception;

use Orisai\Exceptions\LogicalException;

final class NotSupportedType extends LogicalException
{

	private string $expectedType;

	/** @var array<string> */
	private array $supportedTypes;

	/**
	 * @param array<string> $supportedTypes
	 */
	public static function create(string $expectedType, array $supportedTypes): self
	{
		$self = new self();
		$self->withMessage("No encoder is available for type {$expectedType}.");

		$self->expectedType = $expectedType;
		$self->supportedTypes = $supportedTypes;

		return $self;
	}

	public function getExpectedType(): string
	{
		return $this->expectedType;
	}

	/**
	 * @return array<string>
	 */
	public function getSupportedTypes(): array
	{
		return $this->supportedTypes;
	}

}
