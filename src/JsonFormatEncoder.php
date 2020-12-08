<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use JsonException;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\Utils\Dependencies\Dependencies;
use Orisai\Utils\Dependencies\Exception\ExtensionRequired;
use function in_array;
use function json_decode;
use function json_encode;
use const JSON_BIGINT_AS_STRING;
use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class JsonFormatEncoder implements FormatEncoder
{

	public function __construct()
	{
		if (($deps = Dependencies::getNotLoadedExtensions(['json'])) !== []) {
			throw ExtensionRequired::forClass($deps, self::class);
		}
	}

	public static function supportsType(string $fileType): bool
	{
		return in_array($fileType, [
			'json',
			'application/json',
		], true);
	}

	/**
	 * @return mixed
	 * @throws EncodingFailure
	 */
	public function decode(string $content)
	{
		try {
			return json_decode(
				$content,
				true,
				512,
				JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING,
			);
		} catch (JsonException $exception) {
			throw EncodingFailure::fromPrevious($exception);
		}
	}

	/**
	 * @param array<mixed> $content
	 * @throws EncodingFailure
	 */
	public function encode(array $content): string
	{
		try {
			return json_encode(
				$content,
				JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
			);
		} catch (JsonException $exception) {
			throw EncodingFailure::fromPrevious($exception);
		}
	}

}
