<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Unit;

use Generator;
use JsonException;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\DataSources\JsonFormatEncoder;
use Orisai\Utils\Dependencies\DependenciesTester;
use Orisai\Utils\Dependencies\Exception\ExtensionRequired;
use PHPUnit\Framework\TestCase;
use function str_replace;
use const INF;
use const NAN;
use const PHP_EOL;

final class JsonFormatEncoderTest extends TestCase
{

	public function testEncodeAndDecode(): void
	{
		$encoder = new JsonFormatEncoder();

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

		$encoded = str_replace("\n", PHP_EOL, $encoder->encode($data));

		self::assertSame(
			<<<'JSON'
{
    "0": null,
    "one": 1,
    "2": 2.2,
    "three": "three",
    "4": false,
    "5": true,
    "7": {
        "foo": "bar",
        "0": {
            "bar": "baz"
        }
    },
    "8-text": "text",
    "9": "<foo>",
    "10": "'bar'",
    "20": "\"baz\"",
    "30": "&blong&",
    "40": "é"
}
JSON,
			$encoded,
		);

		self::assertSame(
			$data,
			$encoder->decode($encoded),
		);
	}

	public function testSupportsContentType(): void
	{
		self::assertTrue(JsonFormatEncoder::supportsContentType('application/json'));

		self::assertFalse(JsonFormatEncoder::supportsContentType('text/csv'));

		self::assertSame(
			[
				'application/json',
			],
			JsonFormatEncoder::getContentTypes(),
		);
	}

	public function testSupportsFileExtension(): void
	{
		self::assertTrue(JsonFormatEncoder::supportsFileExtension('json'));

		self::assertFalse(JsonFormatEncoder::supportsFileExtension('csv'));

		self::assertSame(
			[
				'json',
			],
			JsonFormatEncoder::getFileExtensions(),
		);
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideEncodingFailure
	 */
	public function testEncodingFailure($data, string $errorMessage): void
	{
		$encoder = new JsonFormatEncoder();

		$exception = null;
		try {
			$encoder->encode($data);
		} catch (EncodingFailure $exception) {
			// Handled below
		}

		self::assertInstanceOf(EncodingFailure::class, $exception);
		self::assertSame(
			$errorMessage,
			$exception->getMessage(),
		);
		self::assertInstanceOf(
			JsonException::class,
			$exception->getPrevious(),
		);
	}

	/**
	 * @return Generator<array<mixed>>
	 */
	public function provideEncodingFailure(): Generator
	{
		yield [
			["utf\xFF"],
			'Malformed UTF-8 characters, possibly incorrectly encoded',
		];

		yield [
			[INF],
			'Inf and NaN cannot be JSON encoded',
		];

		yield [
			[NAN],
			'Inf and NaN cannot be JSON encoded',
		];
	}

	/**
	 * @dataProvider provideDecodingFailure
	 */
	public function testDecodingFailure(string $data, string $errorMessage): void
	{
		$encoder = new JsonFormatEncoder();

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
			JsonException::class,
			$exception->getPrevious(),
		);
	}

	/**
	 * @return Generator<array<mixed>>
	 */
	public function provideDecodingFailure(): Generator
	{
		yield [
			'',
			'Syntax error',
		];

		yield [
			'{',
			'Syntax error',
		];
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testOptionalDependencies(): void
	{
		DependenciesTester::addIgnoredExtensions(['json']);

		$exception = null;

		try {
			new JsonFormatEncoder();
		} catch (ExtensionRequired $exception) {
			// Handled below
		}

		self::assertNotNull($exception);
		self::assertSame(
			['json'],
			$exception->getExtensions(),
		);
	}

}
