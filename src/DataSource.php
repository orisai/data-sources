<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Nette\IOException;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\DataSources\Exception\NotSupportedType;

interface DataSource
{

	/**
	 * @return list<string>
	 */
	public function getContentTypes(): array;

	/**
	 * @return list<string>
	 */
	public function getFileExtensions(): array;

	/**
	 * @return mixed
	 * @throws NotSupportedType No encoder is available for given file type
	 * @throws EncodingFailure Decoding failed due to unsupported or invalid data
	 */
	public function fromString(string $content, string $typeOrExtension);

	/**
	 * @return mixed
	 * @throws NotSupportedType No encoder is available for given file type
	 * @throws IOException File is not readable
	 * @throws EncodingFailure Decoding failed due to unsupported or invalid data
	 */
	public function fromFile(string $file);

	/**
	 * @param mixed $data
	 * @throws NotSupportedType No encoder is available for given file type
	 * @throws EncodingFailure Encoding failed due to unsupported or invalid data
	 */
	public function toString($data, string $typeOrExtension): string;

	/**
	 * @param mixed $data
	 * @throws NotSupportedType No encoder is available for given file type
	 * @throws IOException File is not writable
	 * @throws EncodingFailure Encoding failed due to unsupported or invalid data
	 */
	public function toFile(string $file, $data): void;

}
