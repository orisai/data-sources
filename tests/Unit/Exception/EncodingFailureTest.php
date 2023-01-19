<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Unit\Exception;

use Exception;
use Orisai\DataSources\Exception\EncodingFailure;
use PHPUnit\Framework\TestCase;

final class EncodingFailureTest extends TestCase
{

	public function testCreate(): void
	{
		$e = EncodingFailure::create();
		self::assertSame('', $e->getMessage());
		self::assertNull($e->getPrevious());
	}

	public function testFromPrevious(): void
	{
		$previous = new Exception('some error');
		$e = EncodingFailure::fromPrevious($previous);

		self::assertSame(
			'some error',
			$e->getMessage(),
		);
		self::assertSame($previous, $e->getPrevious());
	}

}
