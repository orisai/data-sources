<?php declare(strict_types = 1);

namespace Orisai\DataSources;

interface FormatEncoderManager
{

	/**
	 * @return array<FormatEncoder>
	 */
	public function getAll(): array;

}
