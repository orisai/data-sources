<?php declare(strict_types = 1);

namespace Orisai\DataSources\Bridge\NetteDI;

use OriNette\DI\Services\CachedServiceManager;
use Orisai\DataSources\FormatEncoder;
use Orisai\DataSources\FormatEncoderManager;

final class LazyFormatEncoderManager extends CachedServiceManager implements FormatEncoderManager
{

	/** @var array<FormatEncoder>|null */
	private ?array $encoders = null;

	/**
	 * @param int|string $key
	 */
	private function get($key): FormatEncoder
	{
		$service = $this->getService($key);

		if ($service === null) {
			$this->throwMissingService($key, FormatEncoder::class);
		}

		if (!$service instanceof FormatEncoder) {
			$this->throwInvalidServiceType($key, FormatEncoder::class, $service);
		}

		return $service;
	}

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
			$encoders[$key] = $this->get($key);
		}

		return $this->encoders = $encoders;
	}

}
