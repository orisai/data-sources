<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Unit\Bridge\NetteNeon;

use Generator;
use Nette\Neon\Exception;
use Orisai\DataSources\Bridge\NetteNeon\NeonFormatEncoder;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\Utils\Dependencies\DependenciesTester;
use Orisai\Utils\Dependencies\Exception\PackageRequired;
use PHPUnit\Framework\TestCase;
use stdClass;
use function rtrim;
use function str_replace;
use const PHP_EOL;

final class NeonFormatEncoderTest extends TestCase
{

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideEncodeAndDecode
	 */
	public function testEncodeAndDecode($data, string $neon): void
	{
		$encoder = new NeonFormatEncoder();

		$encoded = rtrim(str_replace("\n", PHP_EOL, $encoder->encode($data)), PHP_EOL);

		self::assertSame($neon, $encoded);
		self::assertEquals($data, $encoder->decode($encoded));
	}

	/**
	 * @return Generator<array<mixed>>
	 */
	public function provideEncodeAndDecode(): Generator
	{
		yield [
			[
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
				41 => new stdClass(),
				42 => (object) [
					'foo' => 1,
					'bar' => 2,
				],
				43 => [],
				44 => "multi\nline",
			],
			<<<'NEON'
- null
one: 1
2: 2.2
three: three
4: false
5: true
7:
	foo: bar
	-
		bar: baz

'8-text': text
9: <foo>
10: '''bar'''
20: '"baz"'
30: &blong&
40: é
41: object()
42: object(foo: 1, bar: 2)
43: []
44: '''
	multi
	line
'''
NEON,
		];

		yield [
			[
				'foo' => (object) [
					0 => 'a',
					'b' => 1,
					2 => 'c',
					3 => '4',
					5 => (object) [
						'a' => 'b',
						'c' => (object) [
							'd',
							'e',
						],
					],
				],
			],
			"foo: object(a, b: 1, 2: c, 3: '4', 5: object(a: b, c: object(d, e)))",
		];
	}

	public function testGetContentTypes(): void
	{
		self::assertSame(
			[
				'application/x-neon',
			],
			NeonFormatEncoder::getContentTypes(),
		);
	}

	public function testGetFileExtensions(): void
	{
		self::assertSame(
			[
				'neon',
			],
			NeonFormatEncoder::getFileExtensions(),
		);
	}

	/**
	 * @dataProvider provideDecodingFailure
	 */
	public function testDecodingFailure(string $data, string $errorMessage, bool $hasPrevious): void
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
		if ($hasPrevious) {
			self::assertInstanceOf(
				Exception::class,
				$exception->getPrevious(),
			);
		} else {
			self::assertNull($exception->getPrevious());
		}
	}

	/**
	 * @return Generator<array<mixed>>
	 */
	public function provideDecodingFailure(): Generator
	{
		yield [
			"Hello\nWorld",
			"Unexpected 'World' on line 2, column 1.",
			true,
		];

		yield [
			'"\uD801"',
			'Invalid UTF-8 sequence \\uD801 on line 1, column 1.',
			true,
		];

		yield [
			'foo: not-a-object()',
			"Only entity with name 'object' is allowed.",
			false,
		];

		yield [
			'not-a-object()',
			"Only entity with name 'object' is allowed.",
			false,
		];
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testOptionalDependencies(): void
	{
		DependenciesTester::addIgnoredPackages(['nette/neon']);

		$exception = null;

		try {
			new NeonFormatEncoder();
		} catch (PackageRequired $exception) {
			// Handled below
		}

		self::assertNotNull($exception);
		self::assertSame(
			['nette/neon'],
			$exception->getPackages(),
		);
	}

}
