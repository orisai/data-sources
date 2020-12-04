<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\Exceptions\Logic\InvalidState;

interface DataSource
{

	/**
	 * @return array<mixed>
	 * @throws InvalidState No encoder is available for given file type
	 * @throws EncodingFailure Decoding failed due to unsupported or invalid data
	 */
	public function fromContent(string $content, string $extension): array;

	/**
	 * @return array<mixed>
	 * @throws InvalidState File is not readable or no encoder is available for given file type
	 * @throws EncodingFailure Decoding failed due to unsupported or invalid data
	 */
	public function fromFile(string $file): array;

	/**
	 * @param array<mixed> $data
	 * @throws InvalidState No encoder is available for given file type
	 * @throws EncodingFailure Encoding failed due to unsupported or invalid data
	 */
	public function toContent(array $data, string $extension): string;

	/**
	 * @param array<mixed> $data
	 * @throws InvalidState File is not writable or no encoder is available for given file type
	 * @throws EncodingFailure Encoding failed due to unsupported or invalid data
	 */
	public function toFile(string $file, array $data): void;

}
