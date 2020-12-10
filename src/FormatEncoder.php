<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Orisai\DataSources\Exception\EncodingFailure;

interface FormatEncoder
{

	/**
	 * @return array<string>
	 */
	public static function getSupportedTypes(): array;

	public static function supportsType(string $type): bool;

	/**
	 * @return mixed
	 * @throws EncodingFailure
	 */
	public function decode(string $content);

	/**
	 * @param mixed $content
	 * @throws EncodingFailure
	 */
	public function encode($content): string;

}
