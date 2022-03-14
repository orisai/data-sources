<?php declare(strict_types = 1);

namespace Orisai\DataSources\Bridge\NetteNeon;

use Nette\Neon\Exception;
use Nette\Neon\Neon;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\DataSources\FormatEncoder;
use Orisai\Utils\Dependencies\Dependencies;
use Orisai\Utils\Dependencies\Exception\PackageRequired;
use function in_array;

final class NeonFormatEncoder implements FormatEncoder
{

	public function __construct()
	{
		if (($deps = Dependencies::getNotLoadedPackages(['nette/neon'])) !== []) {
			throw PackageRequired::forClass($deps, self::class);
		}
	}

	/**
	 * @return array<string>
	 */
	public static function getSupportedTypes(): array
	{
		return [
			'neon',
			'application/x-neon',
		];
	}

	public static function supportsType(string $type): bool
	{
		return in_array($type, self::getSupportedTypes(), true);
	}

	/**
	 * @return mixed
	 */
	public function decode(string $content)
	{
		try {
			return Neon::decode($content);
		} catch (Exception $exception) {
			throw EncodingFailure::fromPrevious($exception);
		}
	}

	/**
	 * @param mixed $content
	 */
	public function encode($content): string
	{
		return Neon::encode($content, true);
	}

}
