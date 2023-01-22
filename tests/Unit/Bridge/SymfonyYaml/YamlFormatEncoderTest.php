<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Unit\Bridge\SymfonyYaml;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Generator;
use Orisai\DataSources\Bridge\SymfonyYaml\YamlFormatEncoder;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\Utils\Dependencies\DependenciesTester;
use Orisai\Utils\Dependencies\Exception\PackageRequired;
use PHPUnit\Framework\TestCase;
use stdClass;
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
			41 => new stdClass(),
			42 => (object) [
				'foo' => 1,
				'bar' => 2,
			],
			43 => [],
			44 => "multi\nline",
		];

		$encoded = rtrim(str_replace("\n", PHP_EOL, $encoder->encode($data)), PHP_EOL);

		if (InstalledVersions::satisfies(new VersionParser(), 'symfony/yaml', '>=6.1.0')) {
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
41: {  }
42:
  foo: 1
  bar: 2
43: []
44: |-
  multi
  line
YAML,
				$encoded,
			);
		} else {
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
10: '''bar'''
20: '"baz"'
30: '&blong&'
40: é
41: {  }
42:
  foo: 1
  bar: 2
43: []
44: |-
  multi
  line
YAML,
				$encoded,
			);
		}

		// Because PHP arrays are too powerful
		$data[7][0] = (object) $data[7][0];
		$data[7] = (object) $data[7];
		self::assertEquals(
			(object) $data,
			$encoder->decode($encoded),
		);
	}

	public function testGetContentTypes(): void
	{
		self::assertSame(
			[
				'application/x-yml',
				'application/x-yaml',
			],
			YamlFormatEncoder::getContentTypes(),
		);
	}

	public function testGetFileExtensions(): void
	{
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

	public function testDateTimesAreNotDecoded(): void
	{
		$encoder = new YamlFormatEncoder();

		$yaml = <<<'YAML'
a: 2016-05-27
b: 2016-05-27T02:59:43.1Z
# not a datetime, just compatibility test
c: 1464307200
d: '1464307200'
YAML;

		self::assertEquals(
			(object) [
				'a' => 1_464_307_200,
				'b' => 1_464_317_983,
				'c' => 1_464_307_200,
				'd' => '1464307200',
			],
			$encoder->decode($yaml),
		);
	}

	public function testCustomTagsAreNotDecoded(): void
	{
		$encoder = new YamlFormatEncoder();

		$yaml = <<<'YAML'
!my_tag { foo: bar }
YAML;

		$e = null;
		try {
			$encoder->decode($yaml);
		} catch (EncodingFailure $e) {
			// Handled bellow
		}

		self::assertNotNull($e);
		self::assertSame(
			'Tags support is not enabled. Tag "!my_tag" at line 1 (near "!my_tag { foo: bar }").',
			$e->getMessage(),
		);
	}

	public function testPhpConstantsAreNotDecoded(): void
	{
		$encoder = new YamlFormatEncoder();

		$yaml = <<<'YAML'
{ foo: PHP_INT_SIZE, bar: !php/const PHP_INT_SIZE }
YAML;

		$e = null;
		try {
			$encoder->decode($yaml);
		} catch (EncodingFailure $e) {
			// Handled bellow
		}

		self::assertNotNull($e);
		self::assertSame(
			'The string "!php/const PHP_INT_SIZE" could not be parsed as a constant. at line 1 ' .
			'(near "{ foo: PHP_INT_SIZE, bar: !php/const PHP_INT_SIZE }")',
			$e->getMessage(),
		);
	}

	public function testPhpObjectsAreNotDecoded(): void
	{
		$encoder = new YamlFormatEncoder();

		$yaml = <<<'YAML'
!php/object 'O:8:"stdClass":1:{s:5:"foo";s:7:"bar";}'
YAML;

		$e = null;
		try {
			$encoder->decode($yaml);
		} catch (EncodingFailure $e) {
			// Handled bellow
		}

		self::assertNotNull($e);
		self::assertSame(
			'Object support when parsing a YAML file has been disabled at line 1 ' .
			'(near "!php/object \'O:8:"stdClass":1:{s:5:"foo";s:7:"bar";}\'").',
			$e->getMessage(),
		);
	}

	public function testExplicitCasting(): void
	{
		$encoder = new YamlFormatEncoder();

		$yaml = <<<'YAML'
start_date: !!str 2002-12-14
price: !!float 3
YAML;

		self::assertEquals(
			(object) [
				'start_date' => '2002-12-14',
				'price' => 3.0,
			],
			$encoder->decode($yaml),
		);
	}

}
