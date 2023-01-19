<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Unit;

use Generator;
use Nette\Utils\FileSystem;
use Orisai\DataSources\Bridge\NetteNeon\NeonFormatEncoder;
use Orisai\DataSources\Bridge\SymfonyYaml\YamlFormatEncoder;
use Orisai\DataSources\DefaultDataSource;
use Orisai\DataSources\DefaultFormatEncoderManager;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\DataSources\Exception\NotSupportedType;
use Orisai\DataSources\JsonFormatEncoder;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Message;
use PHPUnit\Framework\TestCase;
use Tests\Orisai\DataSources\Doubles\SerializeFormatEncoder;
use Tests\Orisai\DataSources\Doubles\StdClassChild;
use function fopen;
use function md5;

final class DefaultDataSourceTest extends TestCase
{

	public function testContent(): void
	{
		$manager = new DefaultFormatEncoderManager([
			new SerializeFormatEncoder(),
		]);
		$source = new DefaultDataSource($manager);

		$content = $source->toString(['foo' => 'bar'], 'serial');
		self::assertSame('a:1:{s:3:"foo";s:3:"bar";}', $content);

		self::assertSame(['foo' => 'bar'], $source->fromString($content, 'serial'));
	}

	public function testFiles(): void
	{
		$manager = new DefaultFormatEncoderManager([
			new SerializeFormatEncoder(),
		]);
		$source = new DefaultDataSource($manager);

		$dir = __DIR__ . '/../../var/tests/' . md5(self::class);
		$file = $dir . '/file.serial';

		$source->toFile($file, ['foo' => 'bar']);
		$content = FileSystem::read($file);
		self::assertSame('a:1:{s:3:"foo";s:3:"bar";}', $content);

		self::assertSame(['foo' => 'bar'], $source->fromFile($file));

		FileSystem::delete($dir);
	}

	public function testNoExtension(): void
	{
		$manager = new DefaultFormatEncoderManager([
			new SerializeFormatEncoder(),
		]);
		$source = new DefaultDataSource($manager);

		$this->expectException(NotSupportedType::class);
		$this->expectExceptionMessage("File '/foo' has no extension.");

		$source->toFile('/foo', ['foo' => 'bar']);
	}

	public function testEncodingFailure(): void
	{
		$manager = new DefaultFormatEncoderManager([
			new JsonFormatEncoder(),
		]);
		$source = new DefaultDataSource($manager);

		$this->expectException(EncodingFailure::class);
		$this->expectExceptionMessage(<<<'MSG'
Context: Encoding raw data into string of type 'json'.
Problem: Malformed UTF-8 characters, possibly incorrectly encoded
MSG);

		$source->toString(["utf\xFF"], 'json');
	}

	public function testDecodingFailure(): void
	{
		$manager = new DefaultFormatEncoderManager([
			new JsonFormatEncoder(),
		]);
		$source = new DefaultDataSource($manager);

		$this->expectException(EncodingFailure::class);
		$this->expectExceptionMessage(<<<'MSG'
Context: Decoding content of type 'json' into raw data.
Problem: Syntax error
MSG);

		$source->fromString('{', 'json');
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideEncoding
	 */
	public function testEncoding($data): void
	{
		$types = ['serial', 'neon', 'yaml', 'json'];
		$manager = new DefaultFormatEncoderManager([
			new SerializeFormatEncoder(),
			new NeonFormatEncoder(),
			new YamlFormatEncoder(),
			new JsonFormatEncoder(),
		]);
		$source = new DefaultDataSource($manager);

		foreach ($types as $type) {
			$content = $source->toString($data, $type);
			self::assertSame($data, $source->fromString($content, $type));
		}
	}

	/**
	 * @return Generator<array<mixed>>
	 */
	public function provideEncoding(): Generator
	{
		yield 'string' => [
			'string',
		];

		yield 'int' => [
			123,
		];

		yield 'float' => [
			123.456,
		];

		yield 'true' => [
			true,
		];

		yield 'false' => [
			false,
		];

		yield 'null' => [
			null,
		];

		yield 'array' => [
			'string',
			123,
			123.456,
			true,
			false,
			null,
			[
				'first' => ['foo' => 'bar'],
				2 => 'second',
			],
		];
	}

	public function testNoEncoder(): void
	{
		$manager = new DefaultFormatEncoderManager([
			new SerializeFormatEncoder(),
		]);
		$source = new DefaultDataSource($manager);

		$exception = null;
		try {
			$source->toString(['foo' => 'bar'], 'neon');
		} catch (NotSupportedType $exception) {
			// Handled below
		}

		self::assertInstanceOf(NotSupportedType::class, $exception);
		self::assertSame("No encoder is available for type 'neon'.", $exception->getMessage());
		self::assertSame('neon', $exception->getRequestedType());
		self::assertSame(
			['serial'],
			$exception->getSupportedTypes(),
		);
	}

	public function testSupportedContentTypes(): void
	{
		$manager = new DefaultFormatEncoderManager([
			new SerializeFormatEncoder(),
			new JsonFormatEncoder(),
		]);
		$source = new DefaultDataSource($manager);

		self::assertSame(
			[
				'text/serial',
				'application/json',
			],
			$source->getContentTypes(),
		);
	}

	public function testSupportedExtensions(): void
	{
		$manager = new DefaultFormatEncoderManager([
			new SerializeFormatEncoder(),
			new JsonFormatEncoder(),
		]);
		$source = new DefaultDataSource($manager);

		self::assertSame(
			[
				'serial',
				'json',
			],
			$source->getFileExtensions(),
		);
	}

	/**
	 * @param mixed $data
	 *
	 * @runInSeparateProcess
	 * @dataProvider provideUnsupportedData
	 */
	public function testUnsupportedData($data, string $unsupportedType): void
	{
		// Workaround - yielded resource is for some reason cast to 0
		if ($data === 'resource') {
			$data = fopen(__FILE__, 'r');
		}

		$manager = new DefaultFormatEncoderManager([
			new SerializeFormatEncoder(),
		]);
		$source = new DefaultDataSource($manager);

		$this->expectException(EncodingFailure::class);
		$this->expectExceptionMessage(
			<<<MSG
Context: Encoding raw data into string of type 'serial'.
Problem: Data contains PHP type '$unsupportedType', which is not allowed.
Solution: Change type to one of supported - scalar, null, array or stdClass.
MSG,
		);

		Message::$lineLength = 150;
		$source->toString($data, 'serial');
	}

	public function provideUnsupportedData(): Generator
	{
		yield [
			new StdClassChild(),
			StdClassChild::class,
		];

		yield [
			[
				'a' => 'b',
				'foo' => [
					'bar' => [
						new StdClassChild(),
					],
				],
			],
			StdClassChild::class,
		];

		yield [InvalidArgument::create(), InvalidArgument::class];

		yield [
			[
				'a' => 'b',
				'foo' => [
					'bar' => [
						InvalidArgument::create(),
					],
				],
			],
			InvalidArgument::class,
		];

		yield [
			'resource',
			'resource (stream)',
		];
	}

}
