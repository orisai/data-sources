<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Unit\Bridge\SymfonyYaml;

use Generator;
use Orisai\DataSources\Bridge\SymfonyYaml\YamlFormatEncoder;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\Utils\Dependencies\DependenciesTester;
use Orisai\Utils\Dependencies\Exception\PackageRequired;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Exception\ParseException;
use function rtrim;
use function str_replace;
use const PHP_EOL;

final class YamlFormatEncoderTest extends TestCase
{

	public function testEncodeAndDecode(): void
	{
		$encoder = new YamlFormatEncoder();

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
			<<<'YAML'
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
8-text: text
9: '<foo>'
10: "'bar'"
20: '"baz"'
30: '&blong&'
40: é
YAML,
			$encoded,
		);

		self::assertSame(
			$data,
			$encoder->decode($encoded),
		);
	}

	public function testSupportsContentType(): void
	{
		self::assertTrue(YamlFormatEncoder::supportsContentType('application/x-yml'));
		self::assertTrue(YamlFormatEncoder::supportsContentType('application/x-yaml'));

		self::assertFalse(YamlFormatEncoder::supportsContentType('text/csv'));

		self::assertSame(
			[
				'application/x-yml',
				'application/x-yaml',
			],
			YamlFormatEncoder::getContentTypes(),
		);
	}

	public function testSupportsFileExtension(): void
	{
		self::assertTrue(YamlFormatEncoder::supportsFileExtension('yml'));
		self::assertTrue(YamlFormatEncoder::supportsFileExtension('yaml'));

		self::assertFalse(YamlFormatEncoder::supportsFileExtension('csv'));

		self::assertSame(
			[
				'yml',
				'yaml',
			],
			YamlFormatEncoder::getFileExtensions(),
		);
	}

	/**
	 * @dataProvider provideEncodingFailure
	 */
	public function testDecodingFailure(string $data, string $errorMessage): void
	{
		$encoder = new YamlFormatEncoder();

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
			ParseException::class,
			$exception->getPrevious(),
		);
	}

	/**
	 * @return Generator<array<mixed>>
	 */
	public function provideEncodingFailure(): Generator
	{
		yield [
			'{',
			'Malformed inline YAML string at line 1 (near "{").',
		];
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testOptionalDependencies(): void
	{
		DependenciesTester::addIgnoredPackages(['symfony/yaml']);

		$exception = null;

		try {
			new YamlFormatEncoder();
		} catch (PackageRequired $exception) {
			// Handled below
		}

		self::assertNotNull($exception);
		self::assertSame(
			['symfony/yaml'],
			$exception->getPackages(),
		);
	}

}
