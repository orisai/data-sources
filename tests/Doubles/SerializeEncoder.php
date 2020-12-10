<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Doubles;

use Orisai\DataSources\FormatEncoder;
use function in_array;
use function serialize;
use function unserialize;

final class SerializeEncoder implements FormatEncoder
{

	/** @var array<string> */
	private static array $types = [
		'serial',
	];

	public static function addSupportedType(string $type): void
	{
		self::$types[] = $type;
	}

	public static function supportsType(string $fileType): bool
	{
		return in_array($fileType, self::$types, true);
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
