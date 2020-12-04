<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Unit;

use Generator;
use Nette\Neon\Exception;
use Orisai\DataSources\Bridge\Neon\NeonFormatEncoder;
use Orisai\DataSources\Exception\EncodingFailure;
use PHPUnit\Framework\TestCase;
use function rtrim;
use function str_replace;
use const PHP_EOL;

final class NeonFormatEncoderTest extends TestCase
{

	public function testEncodeAndDecode(): void
	{
		$encoder = new NeonFormatEncoder();

		$data = [
			0 => null,
			'one' => 1,
			2 => 2.2,
			'three' => 'three',
			4 => false,
			5 => true,
			7 => [
				'foo' => 'bar',
				[
					'bar' => 'baz',
				],
			],
			'8-text' => 'text',
			9 => '<foo>',
			10 => "'bar'",
			20 => '"baz"',
			30 => '&blong&',
			40 => "\xc3\xa9",
		];

		$encoded = rtrim(str_replace("\n", PHP_EOL, $encoder->encode($data)), PHP_EOL);

		self::assertSame(
			<<<'NEON'
0: null
one: 1
2: 2.2
three: three
4: false
5: true
7:
	foo: bar
	0:
		bar: baz

"8-text": text
9: <foo>
10: "'bar'"
20: "\"baz\""
30: &blong&
40: é
NEON,
			$encoded,
		);

		self::assertSame(
			$data,
			$encoder->decode($encoded),
		);
	}

	public function testSupportsFileType(): void
	{
		self::assertTrue(NeonFormatEncoder::supportsFileType('neon'));
		self::assertTrue(NeonFormatEncoder::supportsFileType('application/x-neon'));

		self::assertFalse(NeonFormatEncoder::supportsFileType('anything'));
	}

	/**
	 * @param array<mixed> $data
	 * @dataProvider encodingFailureProvider
	 */
	public function testEncodingFailure(array $data, string $errorMessage): void
	{
		$encoder = new NeonFormatEncoder();

		$exception = null;
		try {
			$encoder->encode($data);
		} catch (EncodingFailure $exception) {
			// Handled below
		}

		self::assertInstanceOf(EncodingFailure::class, $exception);
		self::assertStringStartsWith(
			$errorMessage,
			$exception->getMessage(),
		);
		self::assertInstanceOf(
			Exception::class,
			$exception->getPrevious(),
		);
	}

	/**
	 * @return Generator<array<mixed>>
	 */
	public function encodingFailureProvider(): Generator
	{
		yield [
			["foo \xc2\x82\x28\xfc\xa1\xa1\xa1\xa1\xa1\xe2\x80\x82"],
			'Invalid UTF-8 sequence: foo ',
		];
	}

	/**
	 * @dataProvider decodingFailureProvider
	 */
	public function testDecodingFailure(string $data, string $errorMessage): void
	{
		$encoder = new NeonFormatEncoder();

		$exception = null;
		try {
			$encoder->decode($data);
		} catch (EncodingFailure $exception) {
			// Handled below
		}

		self::assertInstanceOf(EncodingFailure::class, $exception);
		self::assertSame(
			$errorMessage,
			$exception->getMessage(),
		);
		self::assertInstanceOf(
			Exception::class,
			$exception->getPrevious(),
		);
	}

	/**
	 * @return Generator<array<mixed>>
	 */
	public function decodingFailureProvider(): Generator
	{
		yield [
			"Hello\nWorld",
			"Unexpected 'World' on line 2, column 1.",
		];

		yield [
			'"\uD801"',
			'Invalid UTF-8 (lone surrogate) \\uD801 on line 1, column 1.',
		];
	}

}
