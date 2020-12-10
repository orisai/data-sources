<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Unit;

use Generator;
use Nette\Utils\FileSystem;
use Orisai\DataSources\Bridge\NetteNeon\NeonFormatEncoder;
use Orisai\DataSources\Bridge\SymfonyYaml\YamlFormatEncoder;
use Orisai\DataSources\DefaultDataSource;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\DataSources\JsonFormatEncoder;
use Orisai\Exceptions\Logic\InvalidArgument;
use PHPStan\Testing\TestCase;
use Tests\Orisai\DataSources\Doubles\SerializeFormatEncoder;
use function md5;

final class BaseDataSourceTest extends TestCase
{

	public function testContent(): void
	{
		$encoders = [
			new SerializeFormatEncoder(),
		];
		$source = new DefaultDataSource($encoders);

		$content = $source->toString(['foo' => 'bar'], 'serial');
		self::assertSame('a:1:{s:3:"foo";s:3:"bar";}', $content);

		self::assertSame(['foo' => 'bar'], $source->fromString($content, 'serial'));
	}

	public function testFiles(): void
	{
		$encoders = [
			new SerializeFormatEncoder(),
		];
		$source = new DefaultDataSource($encoders);

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
		$encoders = [
			new SerializeFormatEncoder(),
		];
		$source = new DefaultDataSource($encoders);

		$this->expectException(InvalidArgument::class);
		$this->expectExceptionMessage('File /foo has no extension.');

		$source->toFile('/foo', ['foo' => 'bar']);
	}

	public function testEncodingFailure(): void
	{
		$encoders = [
			new JsonFormatEncoder(),
		];
		$source = new DefaultDataSource($encoders);

		$this->expectException(EncodingFailure::class);
		$this->expectExceptionMessage(<<<'MSG'
Context: Trying to encode data into json.
Problem: Malformed UTF-8 characters, possibly incorrectly encoded
MSG);

		$source->toString(["utf\xFF"], 'json');
	}

	public function testDecodingFailure(): void
	{
		$encoders = [
			new JsonFormatEncoder(),
		];
		$source = new DefaultDataSource($encoders);

		$this->expectException(EncodingFailure::class);
		$this->expectExceptionMessage(<<<'MSG'
Context: Trying to decode json into data.
Problem: Syntax error
MSG);

		$source->fromString('{', 'json');
	}

	/**
	 * @param mixed $data
	 * @dataProvider encodingProvider
	 */
	public function testEncoding($data): void
	{
		$types = ['serial', 'neon', 'yaml', 'json'];
		$encoders = [
			new SerializeFormatEncoder(),
			new NeonFormatEncoder(),
			new YamlFormatEncoder(),
			new JsonFormatEncoder(),
		];
		$source = new DefaultDataSource($encoders);

		foreach ($types as $type) {
			$content = $source->toString($data, $type);
			self::assertSame($data, $source->fromString($content, $type));
		}
	}

	/**
	 * @return Generator<array<mixed>>
	 */
	public function encodingProvider(): Generator
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

}
