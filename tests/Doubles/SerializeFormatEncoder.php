<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Doubles;

use Orisai\DataSources\FormatEncoder;
use function serialize;
use function unserialize;

final class SerializeFormatEncoder implements FormatEncoder
{

	/** @var list<string> */
	private static array $types = [
		'text/serial',
	];

	/** @var list<string> */
	private static array $extensions = [
		'serial',
	];

	public static function getContentTypes(): array
	{
		return self::$types;
	}

	public static function addSupportedContentType(string $type): void
	{
		self::$types[] = $type;
	}

	public static function getFileExtensions(): array
	{
		return self::$extensions;
	}

	public static function addSupportedFileExtensions(string $extension): void
	{
		self::$extensions[] = $extension;
	}

	/**
	 * @return mixed
	 */
	public function decode(string $content)
	{
		return unserialize($content);
	}

	/**
	 * @param mixed $content
	 */
	public function encode($content): string
	{
		return serialize($content);
	}

}
