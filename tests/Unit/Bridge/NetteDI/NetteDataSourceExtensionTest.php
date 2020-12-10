<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Unit\Bridge\NetteDI;

use OriNette\DI\Boot\ManualConfigurator;
use Orisai\DataSources\Bridge\NetteDI\NetteDataSource;
use Orisai\DataSources\Bridge\NetteNeon\NeonFormatEncoder;
use Orisai\DataSources\Bridge\SymfonyYaml\YamlFormatEncoder;
use Orisai\DataSources\DataSource;
use Orisai\DataSources\JsonFormatEncoder;
use PHPUnit\Framework\TestCase;
use Tests\Orisai\DataSources\Doubles\SerializeEncoder;
use function dirname;
use function rtrim;
use function str_replace;
use const PHP_EOL;

final class NetteDataSourceExtensionTest extends TestCase
{

	public function testDefault(): void
	{
		$configurator = new ManualConfigurator(dirname(__DIR__, 4));
		$configurator->setDebugMode(true);
		$configurator->addConfig(__DIR__ . '/extension.default.neon');

		$container = $configurator->createContainer();

		self::assertInstanceOf(NetteDataSource::class, $container->getService('dataSource.dataSource'));

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

		self::assertInstanceOf(NetteDataSource::class, $container->getService('dataSource.dataSource'));

		self::assertInstanceOf(JsonFormatEncoder::class, $container->getService('dataSource.encoder.json'));
		self::assertInstanceOf(SerializeEncoder::class, $container->getService('dataSource.encoder.neon'));
		self::assertInstanceOf(YamlFormatEncoder::class, $container->getService('dataSource.encoder.yaml'));
		self::assertInstanceOf(SerializeEncoder::class, $container->getService('dataSource.encoder.serial'));

		$dataSource = $container->getByType(DataSource::class);

		self::assertSame(
			<<<'JSON'
{
    "foo": "bar"
}
JSON,
			str_replace("\n", PHP_EOL, $dataSource->toString(['foo' => 'bar'], 'json')),
		);

		SerializeEncoder::addSupportedType('neon');
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

}
