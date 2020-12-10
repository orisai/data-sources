<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Unit\Bridge\NetteDI;

use OriNette\DI\Boot\ManualConfigurator;
use Orisai\DataSources\DataSource;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Logic\InvalidState;
use PHPUnit\Framework\TestCase;
use function dirname;
use function rtrim;
use function str_replace;
use const PHP_EOL;

final class NetteDataSourceTest extends TestCase
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

	public function testMissing(): void
	{
		$configurator = new ManualConfigurator(dirname(__DIR__, 4));
		$configurator->setDebugMode(true);
		$configurator->addConfig(__DIR__ . '/dataSource.neon');

		$container = $configurator->createContainer();

		$dataSource = $container->getByType(DataSource::class);

		$this->expectException(InvalidState::class);
		$this->expectExceptionMessage('No encoder is available for file type not-a-file-type.');

		$dataSource->toString(['foo' => 'bar'], 'not-a-file-type');
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

}
