<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Nette\IOException;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\DataSources\Exception\NotSupportedType;

interface DataSource
{

	/**
	 * @return array<string>
	 */
	public function getSupportedTypes(): array;

	/**
	 * @return mixed
	 * @throws NotSupportedType No encoder is available for given file type
	 * @throws EncodingFailure Decoding failed due to unsupported or invalid data
	 */
	public function fromString(string $content, string $type);

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
	public function toString($data, string $type): string;

	/**
	 * @param mixed $data
	 * @throws NotSupportedType No encoder is available for given file type
	 * @throws IOException File is not writable
	 * @throws EncodingFailure Encoding failed due to unsupported or invalid data
	 */
	public function toFile(string $file, $data): void;

}
