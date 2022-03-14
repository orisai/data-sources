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
use PHPUnit\Framework\TestCase;
use Tests\Orisai\DataSources\Doubles\SerializeFormatEncoder;
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

		$this->expectException(InvalidArgument::class);
		$this->expectExceptionMessage('File /foo has no extension.');

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
Context: Trying to encode data into json.
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
Context: Trying to decode json into data.
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
		self::assertSame('No encoder is available for type neon.', $exception->getMessage());
		self::assertSame('neon', $exception->getExpectedType());
		self::assertSame(
			['serial'],
			$exception->getSupportedTypes(),
		);
	}

	public function testSupportedTypes(): void
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
				'application/json',
			],
			$source->getSupportedTypes(),
		);
	}

}
