<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Unit;

use Nette\Utils\FileSystem;
use Orisai\DataSources\DefaultDataSource;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\DataSources\JsonFormatEncoder;
use Orisai\Exceptions\Logic\InvalidArgument;
use PHPStan\Testing\TestCase;
use Tests\Orisai\DataSources\Doubles\SerializeEncoder;
use function md5;

final class BaseDataSourceTest extends TestCase
{

	public function testContent(): void
	{
		$encoders = [
			new SerializeEncoder(),
		];
		$source = new DefaultDataSource($encoders);

		$content = $source->toContent(['foo' => 'bar'], 'serial');
		self::assertSame('a:1:{s:3:"foo";s:3:"bar";}', $content);

		self::assertSame(['foo' => 'bar'], $source->fromContent($content, 'serial'));
	}

	public function testFiles(): void
	{
		$encoders = [
			new SerializeEncoder(),
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
			new SerializeEncoder(),
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
Context: Trying to encode array into json.
Problem: Malformed UTF-8 characters, possibly incorrectly encoded
MSG);

		$source->toContent(["utf\xFF"], 'json');
	}

	public function testDecodingFailure(): void
	{
		$encoders = [
			new JsonFormatEncoder(),
		];
		$source = new DefaultDataSource($encoders);

		$this->expectException(EncodingFailure::class);
		$this->expectExceptionMessage(<<<'MSG'
Context: Trying to decode json into an array.
Problem: Syntax error
MSG);

		$source->fromContent('{', 'json');
	}

}
