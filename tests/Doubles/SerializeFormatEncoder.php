<?php declare(strict_types = 1);

namespace Tests\Orisai\DataSources\Doubles;

use Orisai\DataSources\FormatEncoder;
use function in_array;
use function serialize;
use function unserialize;

final class SerializeFormatEncoder implements FormatEncoder
{

	/** @var array<string> */
	private static array $types = [
		'serial',
	];

	/**
	 * @return array<string>
	 */
	public static function getSupportedTypes(): array
	{
		return self::$types;
	}

	public static function supportsType(string $type): bool
	{
		return in_array($type, self::getSupportedTypes(), true);
	}

	public static function addSupportedType(string $type): void
	{
		self::$types[] = $type;
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
