<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Unit\Bridge\NetteDI;

use OriNette\DI\Boot\ManualConfigurator;
use Orisai\DataSources\Bridge\NetteDI\DataSourceExtension;
use Orisai\DataSources\Bridge\NetteDI\LazyDataSource;
use Orisai\DataSources\Bridge\NetteNeon\NeonFormatEncoder;
use Orisai\DataSources\Bridge\SymfonyYaml\YamlFormatEncoder;
use Orisai\DataSources\DataSource;
use Orisai\DataSources\JsonFormatEncoder;
use Orisai\Utils\Dependencies\Exception\PackageRequired;
use Orisai\Utils\Tester\DependenciesTester;
use PHPUnit\Framework\TestCase;
use Tests\Orisai\DataSources\Doubles\SerializeFormatEncoder;
use function dirname;
use function rtrim;
use function str_replace;
use const PHP_EOL;

final class DataSourceExtensionTest extends TestCase
{

	public function testDefault(): void
	{
		$configurator = new ManualConfigurator(dirname(__DIR__, 4));
		$configurator->setDebugMode(true);
		$configurator->addConfig(__DIR__ . '/extension.default.neon');

		$container = $configurator->createContainer();

		self::assertInstanceOf(LazyDataSource::class, $container->getService('dataSource.dataSource'));

		self::assertInstanceOf(JsonFormatEncoder::class, $container->getService('dataSource.encoder.json'));
		self::assertInstanceOf(NeonFormatEncoder::class, $container->getService('dataSource.encoder.neon'));
		self::assertInstanceOf(YamlFormatEncoder::class, $container->getService('dataSource.encoder.yaml'));

		$dataSource = $container->getByType(DataSource::class);

		self::assertSame(
			<<<'JSON'
{
    "foo": "bar"
}
JSON,
			str_replace("\n", PHP_EOL, $dataSource->toString(['foo' => 'bar'], 'json')),
		);

		self::assertSame(
			'foo: bar',
			rtrim($dataSource->toString(['foo' => 'bar'], 'neon'), "\n"),
		);

		self::assertSame(
			'foo: bar',
			rtrim($dataSource->toString(['foo' => 'bar'], 'yaml'), "\n"),
		);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testCustomized(): void
	{
		$configurator = new ManualConfigurator(dirname(__DIR__, 4));
		$configurator->setDebugMode(true);
		$configurator->addConfig(__DIR__ . '/extension.customized.neon');

		$container = $configurator->createContainer();

		self::assertInstanceOf(LazyDataSource::class, $container->getService('dataSource.dataSource'));

		self::assertInstanceOf(JsonFormatEncoder::class, $container->getService('dataSource.encoder.json'));
		self::assertInstanceOf(SerializeFormatEncoder::class, $container->getService('dataSource.encoder.neon'));
		self::assertInstanceOf(YamlFormatEncoder::class, $container->getService('dataSource.encoder.yaml'));
		self::assertInstanceOf(SerializeFormatEncoder::class, $container->getService('dataSource.encoder.serial'));

		$dataSource = $container->getByType(DataSource::class);

		self::assertSame(
			<<<'JSON'
{
    "foo": "bar"
}
JSON,
			str_replace("\n", PHP_EOL, $dataSource->toString(['foo' => 'bar'], 'json')),
		);

		SerializeFormatEncoder::addSupportedType('neon');
		self::assertSame(
			'a:1:{s:3:"foo";s:3:"bar";}',
			$dataSource->toString(['foo' => 'bar'], 'neon'),
		);

		self::assertSame(
			'foo: bar',
			rtrim($dataSource->toString(['foo' => 'bar'], 'yaml'), "\n"),
		);

		self::assertSame(
			'a:1:{s:3:"foo";s:3:"bar";}',
			$dataSource->toString(['foo' => 'bar'], 'serial'),
		);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testOptionalEncoders(): void
	{
		$configurator = new ManualConfigurator(dirname(__DIR__, 4));
		$configurator->setDebugMode(true);
		$configurator->addConfig(__DIR__ . '/extension.default.neon');
		$configurator->addStaticParameters([
			'__unique' => __METHOD__,
		]);

		DependenciesTester::addIgnoredExtensions(['json']);
		DependenciesTester::addIgnoredPackages(['nette/neon', 'symfony/yaml']);

		$container = $configurator->createContainer();

		self::assertInstanceOf(LazyDataSource::class, $container->getService('dataSource.dataSource'));

		self::assertFalse($container->hasService('dataSource.encoder.json'));
		self::assertFalse($container->hasService('dataSource.encoder.neon'));
		self::assertFalse($container->hasService('dataSource.encoder.yaml'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testOptionalDependencies(): void
	{
		DependenciesTester::addIgnoredPackages(['orisai/nette-di']);

		$exception = null;

		try {
			new DataSourceExtension();
		} catch (PackageRequired $exception) {
			// handled below
		}

		self::assertNotNull($exception);
		self::assertSame(
			['orisai/nette-di'],
			$exception->getPackages(),
		);
	}

}
