<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Unit;

use Orisai\DataSources\DefaultDataSource;
use Orisai\Exceptions\Logic\InvalidState;
use PHPStan\Testing\TestCase;
use Tests\Orisai\DataSources\Doubles\SerializeEncoder;

final class DefaultDataSourceTest extends TestCase
{

	public function testNoEncoder(): void
	{
		$encoders = [
			new SerializeEncoder(),
		];
		$source = new DefaultDataSource($encoders);

		$this->expectException(InvalidState::class);
		$this->expectExceptionMessage('No encoder is available for file type neon.');

		$source->toString(['foo' => 'bar'], 'neon');
	}

}
