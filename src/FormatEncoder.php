<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Orisai\DataSources\Exception\EncodingFailure;

interface FormatEncoder
{

	/**
	 * @return list<string>
	 */
	public static function getContentTypes(): array;

	public static function supportsContentType(string $type): bool;

	/**
	 * @return list<string>
	 */
	public static function getFileExtensions(): array;

	public static function supportsFileExtension(string $extension): bool;

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
