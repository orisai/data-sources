<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Unit\Exception;

use Orisai\DataSources\Exception\NotSupportedType;
use PHPUnit\Framework\TestCase;

final class NotSupportedTypeTest extends TestCase
{

	public function testUnknownType(): void
	{
		$type = 'foo';
		$supported = ['bar', 'baz'];
		$e = NotSupportedType::forUnknownType($type, $supported);

		self::assertSame(
			"No encoder is available for type 'foo'.",
			$e->getMessage(),
		);
		self::assertSame(
			$type,
			$e->getRequestedType(),
		);
		self::assertSame(
			$supported,
			$e->getSupportedTypes(),
		);
	}

	public function testNoFileExtension(): void
	{
		$file = __FILE__;
		$supported = ['bar', 'baz'];
		$e = NotSupportedType::forNoFileExtension($file, $supported);

		self::assertSame(
			"File '$file' has no extension.",
			$e->getMessage(),
		);
		self::assertSame(
			'',
			$e->getRequestedType(),
		);
		self::assertSame(
			$supported,
			$e->getSupportedTypes(),
		);
	}

}
