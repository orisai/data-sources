<?php declare(strict_types = 1);

namespace Orisai\DataSources\Bridge\NetteDI;

use Nette\DI\Container;
use Orisai\DataSources\BaseDataSource;
use Orisai\DataSources\Exception\NotSupportedType;
use Orisai\DataSources\FormatEncoder;
use Orisai\Exceptions\Logic\InvalidArgument;
use function get_debug_type;
use function sprintf;

final class LazyDataSource extends BaseDataSource
{

	private Container $container;

	/** @var array<string> */
	private array $serviceNames;

	/** @var array<FormatEncoder> */
	private array $encoders = [];

	/**
	 * @param array<string> $serviceNames
	 */
	public function __construct(Container $container, array $serviceNames)
	{
		$this->container = $container;
		$this->serviceNames = $serviceNames;
	}

	/**
	 * @param int|string $key
	 */
	private function loadEncoder($key): FormatEncoder
	{
		$serviceName = $this->serviceNames[$key];

		$encoder = $this->container->getService($serviceName);
		unset($this->serviceNames[$key]);

		if (!$encoder instanceof FormatEncoder) {
			throw InvalidArgument::create()
				->withMessage(sprintf(
					'Service %s is not instance of %s, %s given.',
					$serviceName,
					FormatEncoder::class,
					get_debug_type($encoder),
				));
		}

		return $this->encoders[] = $encoder;
	}

	/**
	 * @return array<FormatEncoder>
	 */
	protected function getFormatEncoders(): array
	{
		foreach ($this->serviceNames as $key => $serviceName) {
			$this->loadEncoder($key);
		}

		return $this->encoders;
	}

	/**
	 * @throws NotSupportedType
	 */
	protected function getFormatEncoder(string $type): FormatEncoder
	{
		foreach ($this->encoders as $encoder) {
			if ($encoder::supportsType($type)) {
				return $encoder;
			}
		}

		foreach ($this->serviceNames as $key => $serviceName) {
			$encoder = $this->loadEncoder($key);

			if ($encoder::supportsType($type)) {
				return $encoder;
			}
		}

		throw NotSupportedType::create($type, $this->getSupportedTypes());
	}

}
