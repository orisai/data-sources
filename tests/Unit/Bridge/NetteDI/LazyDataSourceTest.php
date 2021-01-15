<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Unit\Bridge\NetteDI;

use OriNette\DI\Boot\ManualConfigurator;
use Orisai\DataSources\DataSource;
use Orisai\DataSources\Exception\NotSupportedType;
use Orisai\Exceptions\Logic\InvalidArgument;
use PHPUnit\Framework\TestCase;
use function assert;
use function dirname;
use function rtrim;
use function str_replace;
use const PHP_EOL;

final class LazyDataSourceTest extends TestCase
{

	public function testBasic(): void
	{
		$configurator = new ManualConfigurator(dirname(__DIR__, 4));
		$configurator->setDebugMode(true);
		$configurator->addConfig(__DIR__ . '/dataSource.neon');

		$container = $configurator->createContainer();

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
	}

	public function testNoEncoder(): void
	{
		$configurator = new ManualConfigurator(dirname(__DIR__, 4));
		$configurator->setDebugMode(true);
		$configurator->addConfig(__DIR__ . '/dataSource.neon');

		$container = $configurator->createContainer();

		$source = $container->getByType(DataSource::class);
		assert($source instanceof DataSource);

		$exception = null;
		try {
			$source->toString(['foo' => 'bar'], 'not-a-file-type');
		} catch (NotSupportedType $exception) {
			// Handled below
		}

		self::assertInstanceOf(NotSupportedType::class, $exception);
		self::assertSame('No encoder is available for type not-a-file-type.', $exception->getMessage());
		self::assertSame('not-a-file-type', $exception->getExpectedType());
		self::assertSame(
			[
				'neon',
				'application/x-neon',
				'json',
				'application/json',
			],
			$exception->getSupportedTypes(),
		);
	}

	public function testBadClass(): void
	{
		$configurator = new ManualConfigurator(dirname(__DIR__, 4));
		$configurator->setDebugMode(true);
		$configurator->addConfig(__DIR__ . '/dataSource.badClass.neon');

		$container = $configurator->createContainer();

		$dataSource = $container->getByType(DataSource::class);

		// No exception, it's defined first
		self::assertSame(
			'foo: bar',
			rtrim($dataSource->toString(['foo' => 'bar'], 'neon'), "\n"),
		);

		$this->expectException(InvalidArgument::class);
		$this->expectExceptionMessage(
			'Service encoder.badClass is not instance of Orisai\DataSources\FormatEncoder, stdClass given.',
		);

		$dataSource->toString(['foo' => 'bar'], 'not-a-file-type');
	}

	public function testSupportedTypes(): void
	{
		$configurator = new ManualConfigurator(dirname(__DIR__, 4));
		$configurator->setDebugMode(true);
		$configurator->addConfig(__DIR__ . '/dataSource.supportedTypes.neon');

		$container = $configurator->createContainer();

		$dataSource = $container->getByType(DataSource::class);

		self::assertSame(
			[
				'serial',
				'json',
				'application/json',
			],
			$dataSource->getSupportedTypes(),
		);
	}

}
