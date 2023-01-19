<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Generator;

final class DefaultFormatEncoderManager implements FormatEncoderManager
{

	/** @var list<FormatEncoder> */
	private array $encoders;

	/**
	 * @param list<FormatEncoder> $encoders
	 */
	public function __construct(array $encoders)
	{
		$this->encoders = $encoders;
	}

	public function getAll(): Generator
	{
		foreach ($this->encoders as $encoder) {
			yield $encoder;
		}
	}

}
