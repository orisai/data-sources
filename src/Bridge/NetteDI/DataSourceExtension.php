<?php declare(strict_types = 1);

namespace Orisai\DataSources\Bridge\NetteDI;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use OriNette\DI\Definitions\DefinitionsLoader;
use Orisai\DataSources\Bridge\NetteNeon\NeonFormatEncoder;
use Orisai\DataSources\Bridge\SymfonyYaml\YamlFormatEncoder;
use Orisai\DataSources\DataSource;
use Orisai\DataSources\FormatEncoder;
use Orisai\DataSources\JsonFormatEncoder;
use Orisai\Utils\Dependencies\Dependencies;
use Orisai\Utils\Dependencies\Exception\PackageRequired;
use stdClass;
use function array_keys;

/**
 * @property-read stdClass $config
 */
final class DataSourceExtension extends CompilerExtension
{

	public function __construct()
	{
		if (($deps = Dependencies::getNotLoadedPackages(['orisai/nette-di'])) !== []) {
			throw PackageRequired::forClass($deps, self::class);
		}
	}

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'encoders' => Expect::arrayOf(
				Expect::anyOf(Expect::string(), Expect::array(), Expect::type(Statement::class)),
			),
		]);
	}

	public function loadConfiguration(): void
	{
		parent::loadConfiguration();
		$loader = new DefinitionsLoader($this->compiler);
		$builder = $this->getContainerBuilder();
		$config = $this->config;

		$encoderDefinitions = [];

		if (Dependencies::isExtensionLoaded('json')) {
			$jsonDefinition = $this->addCoreEncoder('json', JsonFormatEncoder::class);
			$encoderDefinitions[$jsonDefinition->getName()] = $jsonDefinition;
		}

		if (Dependencies::isPackageLoaded('nette/neon')) {
			$neonDefinition = $this->addCoreEncoder('neon', NeonFormatEncoder::class);
			$encoderDefinitions[$neonDefinition->getName()] = $neonDefinition;
		}

		if (Dependencies::isPackageLoaded('symfony/yaml')) {
			$yamlDefinition = $this->addCoreEncoder('yaml', YamlFormatEncoder::class);
			$encoderDefinitions[$yamlDefinition->getName()] = $yamlDefinition;
		}

		foreach ($config->encoders as $encoderName => $encoderConfig) {
			$encoderKey = $this->prefix("encoder.{$encoderName}");
			$encoderDefinitions[$encoderKey] = $loader->loadDefinitionFromConfig(
				$encoderConfig,
				$encoderKey,
			);
		}

		$builder->addDefinition($this->prefix('dataSource'))
			->setFactory(LazyDataSource::class, [
				'serviceNames' => array_keys($encoderDefinitions),
			])
			->setType(DataSource::class);
	}

	/**
	 * @param class-string<FormatEncoder> $className
	 */
	private function addCoreEncoder(string $encoderName, string $className): ServiceDefinition
	{
		$builder = $this->getContainerBuilder();
		$definitionName = $this->prefix("encoder.{$encoderName}");

		return $builder->addDefinition($definitionName)
			->setFactory($className)
			->setAutowired(false);
	}

}
