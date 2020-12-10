<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Nette\IOException;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\Exceptions\Logic\InvalidState;

interface DataSource
{

	/**
	 * @return mixed
	 * @throws InvalidState No encoder is available for given file type
	 * @throws EncodingFailure Decoding failed due to unsupported or invalid data
	 */
	public function fromContent(string $content, string $fileType);

	/**
	 * @return mixed
	 * @throws InvalidState No encoder is available for given file type
	 * @throws IOException File is not readable
	 * @throws EncodingFailure Decoding failed due to unsupported or invalid data
	 */
	public function fromFile(string $file);

	/**
	 * @param mixed $data
	 * @throws InvalidState No encoder is available for given file type
	 * @throws EncodingFailure Encoding failed due to unsupported or invalid data
	 */
	public function toContent($data, string $fileType): string;

	/**
	 * @param mixed $data
	 * @throws InvalidState No encoder is available for given file type
	 * @throws IOException File is not writable
	 * @throws EncodingFailure Encoding failed due to unsupported or invalid data
	 */
	public function toFile(string $file, $data): void;

}
