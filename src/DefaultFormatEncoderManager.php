<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Generator;

final class DefaultFormatEncoderManager implements FormatEncoderManager
{

	/** @var list<FormatEncoder> */
	private array $encoders = [];

	public function addEncoder(FormatEncoder $encoder): void
	{
		$this->encoders[] = $encoder;
	}

	public function getAll(): Generator
	{
		foreach ($this->encoders as $encoder) {
			yield $encoder;
		}
	}

}
