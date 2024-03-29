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
use stdClass;
use Tests\Orisai\DataSources\Doubles\SerializeFormatEncoder;
use Tests\Orisai\DataSources\Doubles\StdClassChild;
use function fopen;
use function md5;

final class DefaultDataSourceTest extends TestCase
{

	public function testContent(): void
	{
		$manager = new DefaultFormatEncoderManager();
		$manager->addEncoder(new SerializeFormatEncoder());
		$source = new DefaultDataSource($manager);

		$content = $source->encode(['foo' => 'bar'], 'serial');
		self::assertSame('a:1:{s:3:"foo";s:3:"bar";}', $content);

		self::assertSame(['foo' => 'bar'], $source->decode($content, 'serial'));
	}

	public function testFiles(): void
	{
		$manager = new DefaultFormatEncoderManager();
		$manager->addEncoder(new SerializeFormatEncoder());
		$source = new DefaultDataSource($manager);

		$dir = __DIR__ . '/../../var/tests/' . md5(self::class);
		$file = $dir . '/file.serial';

		$source->encodeToFile($file, ['foo' => 'bar']);
		$content = FileSystem::read($file);
		self::assertSame('a:1:{s:3:"foo";s:3:"bar";}', $content);

		self::assertSame(['foo' => 'bar'], $source->decodeFromFile($file));

		FileSystem::delete($dir);
	}

	public function testNoExtension(): void
	{
		$manager = new DefaultFormatEncoderManager();
		$manager->addEncoder(new SerializeFormatEncoder());
		$source = new DefaultDataSource($manager);

		$this->expectException(NotSupportedType::class);
		$this->expectExceptionMessage("File '/foo' has no extension.");

		$source->encodeToFile('/foo', ['foo' => 'bar']);
	}

	public function testEncodingFailure(): void
	{
		$manager = new DefaultFormatEncoderManager();
		$manager->addEncoder(new JsonFormatEncoder());
		$source = new DefaultDataSource($manager);

		$this->expectException(EncodingFailure::class);
		$this->expectExceptionMessage(
			<<<'MSG'
Context: Encoding raw data into string of type 'json'.
Problem: Malformed UTF-8 characters, possibly incorrectly encoded
MSG,
		);

		$source->encode(["utf\xFF"], 'json');
	}

	public function testDecodingFailure(): void
	{
		$manager = new DefaultFormatEncoderManager();
		$manager->addEncoder(new JsonFormatEncoder());
		$source = new DefaultDataSource($manager);

		$this->expectException(EncodingFailure::class);
		$this->expectExceptionMessage(
			<<<'MSG'
Context: Decoding content of type 'json' into raw data.
Problem: Syntax error
MSG,
		);

		$source->decode('{', 'json');
	}

	/**
	 * @param mixed $data
	 *
	 * @dataProvider provideEncoding
	 */
	public function testEncoding($data): void
	{
		$types = [
			'serial',
			'text/serial',
			'neon',
			'application/x-neon',
			'yaml',
			'application/x-yaml',
			'json',
			'application/json',
		];
		$manager = new DefaultFormatEncoderManager();
		$manager->addEncoder(new SerializeFormatEncoder());
		$manager->addEncoder(new NeonFormatEncoder());
		$manager->addEncoder(new YamlFormatEncoder());
		$manager->addEncoder(new JsonFormatEncoder());
		$source = new DefaultDataSource($manager);

		foreach ($types as $type) {
			$content = $source->encode($data, $type);
			self::assertEquals(
				$data,
				$source->decode($content, $type),
				"encode/decode equality of $type",
			);
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

		yield 'empty-stdClass' => [
			new stdClass(),
		];

		yield 'stdClass' => [
			(object) [
				'foo' => 1,
				'bar' => 2,
			],
		];
	}

	/**
	 * @param list<string> $supported
	 *
	 * @dataProvider provideNoEncoderTypes
	 */
	public function testNoEncoder(string $type, array $supported): void
	{
		$manager = new DefaultFormatEncoderManager();
		$manager->addEncoder(new SerializeFormatEncoder());
		$source = new DefaultDataSource($manager);

		$exception = null;
		try {
			$source->encode(['foo' => 'bar'], $type);
		} catch (NotSupportedType $exception) {
			// Handled below
		}

		self::assertInstanceOf(NotSupportedType::class, $exception);
		self::assertSame("No encoder is available for type '$type'.", $exception->getMessage());
		self::assertSame($type, $exception->getRequestedType());
		self::assertSame(
			$supported,
			$exception->getSupportedTypes(),
		);
	}

	public function provideNoEncoderTypes(): Generator
	{
		$supportedMediaTypes = ['text/serial'];
		$supportedExtensions = ['serial'];

		yield ['application/x-neon', $supportedMediaTypes];
		yield ['application/x-yaml', $supportedMediaTypes];
		yield ['application/json', $supportedMediaTypes];

		yield ['neon', $supportedExtensions];
		yield ['yaml', $supportedExtensions];
		yield ['json', $supportedExtensions];
	}

	public function testSupportedContentTypes(): void
	{
		$manager = new DefaultFormatEncoderManager();
		$manager->addEncoder(new JsonFormatEncoder());
		$manager->addEncoder(new YamlFormatEncoder());
		$source = new DefaultDataSource($manager);

		self::assertTrue($source->supportsContentType('application/json'));
		self::assertTrue($source->supportsContentType('application/x-yml'));
		self::assertTrue($source->supportsContentType('application/x-yaml'));

		self::assertFalse($source->supportsContentType('text/csv'));

		self::assertSame(
			[
				'application/json',
				'application/x-yml',
				'application/x-yaml',
			],
			$source->getContentTypes(),
		);
	}

	public function testSupportedExtensions(): void
	{
		$manager = new DefaultFormatEncoderManager();
		$manager->addEncoder(new JsonFormatEncoder());
		$manager->addEncoder(new YamlFormatEncoder());
		$source = new DefaultDataSource($manager);

		self::assertTrue($source->supportsFileExtension('json'));
		self::assertTrue($source->supportsFileExtension('yml'));
		self::assertTrue($source->supportsFileExtension('yaml'));

		self::assertFalse($source->supportsFileExtension('csv'));

		self::assertSame(
			[
				'json',
				'yml',
				'yaml',
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

		$manager = new DefaultFormatEncoderManager();
		$manager->addEncoder(new SerializeFormatEncoder());
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
		$source->encode($data, 'serial');
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
