<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Orisai\DataSources\Exception\EncodingFailure;

interface FormatEncoder
{

	/**
	 * @return list<string>
	 */
	public static function getContentTypes(): array;

	/**
	 * @return list<string>
	 */
	public static function getFileExtensions(): array;

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
