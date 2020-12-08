<?php declare(strict_types = 1);

namespace Orisai\DataSources\Bridge\NetteDI;

use Nette\DI\Container;
use Orisai\DataSources\BaseDataSource;
use Orisai\DataSources\FormatEncoder;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Logic\InvalidState;
use function get_debug_type;
use function sprintf;

final class NetteDataSource extends BaseDataSource
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
	 * @throws InvalidState
	 */
	protected function getDataSource(string $fileType): FormatEncoder
	{
		foreach ($this->encoders as $encoder) {
			if ($encoder::supportsType($fileType)) {
				return $encoder;
			}
		}

		foreach ($this->serviceNames as $key => $serviceName) {
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

			$this->encoders[] = $encoder;

			if ($encoder::supportsType($fileType)) {
				return $encoder;
			}
		}

		throw InvalidState::create()
			->withMessage("No encoder is available for file type {$fileType}.");
	}

}
