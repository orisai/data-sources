<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Generator;

interface FormatEncoderManager
{

	/**
	 * @return Generator<FormatEncoder>
	 */
	public function getAll(): Generator;

}
