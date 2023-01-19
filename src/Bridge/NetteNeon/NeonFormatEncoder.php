<?php declare(strict_types = 1);

namespace Orisai\DataSources\Bridge\NetteNeon;

use Nette\Neon\Exception;
use Nette\Neon\Neon;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\DataSources\FormatEncoder;
use Orisai\Utils\Dependencies\Dependencies;
use Orisai\Utils\Dependencies\Exception\PackageRequired;

final class NeonFormatEncoder implements FormatEncoder
{

	public function __construct()
	{
		if (($deps = Dependencies::getNotLoadedPackages(['nette/neon'])) !== []) {
			throw PackageRequired::forClass($deps, self::class);
		}
	}

	public static function getContentTypes(): array
	{
		return [
			'application/x-neon',
		];
	}

	public static function getFileExtensions(): array
	{
		return [
			'neon',
		];
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
