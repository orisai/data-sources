<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Orisai\DataSources\Exception\EncodingFailure;

interface FormatEncoder
{

	public static function supportsType(string $fileType): bool;

	/**
	 * @return mixed
	 * @throws EncodingFailure
	 */
	public function decode(string $content);

	/**
	 * @param array<mixed> $content
	 * @throws EncodingFailure
	 */
	public function encode(array $content): string;

}
