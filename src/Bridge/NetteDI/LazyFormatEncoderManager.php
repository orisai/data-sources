<?php declare(strict_types = 1);

namespace Orisai\DataSources\Bridge\NetteDI;

use OriNette\DI\Services\ServiceManager;
use Orisai\DataSources\FormatEncoder;
use Orisai\DataSources\FormatEncoderManager;

final class LazyFormatEncoderManager extends ServiceManager implements FormatEncoderManager
{

	/** @var array<FormatEncoder>|null */
	private ?array $encoders = null;

	/**
	 * @return array<FormatEncoder>
	 */
	public function getAll(): array
	{
		if ($this->encoders !== null) {
			return $this->encoders;
		}

		$encoders = [];
		foreach ($this->getKeys() as $key) {
			$encoders[$key] = $this->getTypedServiceOrThrow($key, FormatEncoder::class);
		}

		return $this->encoders = $encoders;
	}

}
