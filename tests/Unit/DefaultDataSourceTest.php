<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Unit;

use Orisai\DataSources\DefaultDataSource;
use Orisai\DataSources\Exception\NotSupportedType;
use Orisai\DataSources\JsonFormatEncoder;
use PHPStan\Testing\TestCase;
use Tests\Orisai\DataSources\Doubles\SerializeFormatEncoder;

final class DefaultDataSourceTest extends TestCase
{

	public function testNoEncoder(): void
	{
		$encoders = [
			new SerializeFormatEncoder(),
		];
		$source = new DefaultDataSource($encoders);

		$exception = null;
		try {
			$source->toString(['foo' => 'bar'], 'neon');
		} catch (NotSupportedType $exception) {
			// Handled below
		}

		self::assertInstanceOf(NotSupportedType::class, $exception);
		self::assertSame('No encoder is available for type neon.', $exception->getMessage());
		self::assertSame('neon', $exception->getExpectedType());
		self::assertSame(
			['serial'],
			$exception->getSupportedTypes(),
		);
	}

	public function testSupportedTypes(): void
	{
		$encoders = [
			new SerializeFormatEncoder(),
			new JsonFormatEncoder(),
		];
		$source = new DefaultDataSource($encoders);

		self::assertSame(
			[
				'serial',
				'json',
				'application/json',
			],
			$source->getSupportedTypes(),
		);
	}

}
