<?php declare(strict_types = 1);

namespace Orisai\DataSources;

final class DefaultFormatEncoderManager implements FormatEncoderManager
{

	/** @var array<FormatEncoder> */
	private array $encoders;

	/**
	 * @param array<FormatEncoder> $encoders
	 */
	public function __construct(array $encoders)
	{
		$this->encoders = $encoders;
	}

	/**
	 * @return array<FormatEncoder>
	 */
	public function getAll(): array
	{
		return $this->encoders;
	}

}
