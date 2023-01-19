<?php declare(strict_types = 1);

namespace Orisai\DataSources\Bridge\NetteDI;

use Generator;
use Nette\DI\Container;
use OriNette\DI\Services\ServiceManager;
use Orisai\DataSources\FormatEncoder;
use Orisai\DataSources\FormatEncoderManager;

final class LazyFormatEncoderManager extends ServiceManager implements FormatEncoderManager
{

	/** @var list<FormatEncoder> */
	private array $encoders = [];

	/** @var array<int, int|string> */
	private array $keys;

	public function __construct(array $serviceMap, Container $container)
	{
		parent::__construct($serviceMap, $container);
		$this->keys = $this->getKeys();
	}

	public function getAll(): Generator
	{
		foreach ($this->encoders as $encoder) {
			yield $encoder;
		}

		foreach ($this->keys as $i => $key) {
			$this->encoders[] = $encoder = $this->getTypedServiceOrThrow($key, FormatEncoder::class);
			unset($this->keys[$i]);

			yield $encoder;
		}
	}

}
