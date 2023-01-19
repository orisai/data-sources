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
	public static function forUnknownType(string $expectedType, array $supportedTypes): self
	{
		$self = new self();
		$self->withMessage("No encoder is available for type '$expectedType'.");

		$self->expectedType = $expectedType;
		$self->supportedTypes = $supportedTypes;

		return $self;
	}

	/**
	 * @param array<string> $supportedTypes
	 */
	public static function forNoFileExtension(string $file, array $supportedTypes): self
	{
		$self = new self();
		$self->withMessage("File '$file' has no extension.");

		$self->expectedType = '';
		$self->supportedTypes = $supportedTypes;

		return $self;
	}

	public function getRequestedType(): string
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
