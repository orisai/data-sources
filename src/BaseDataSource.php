<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Logic\InvalidState;
use Orisai\Exceptions\Message;
use function file_get_contents;
use function file_put_contents;
use function get_debug_type;
use function is_array;
use function pathinfo;
use const PATHINFO_EXTENSION;

abstract class BaseDataSource implements DataSource
{

	/**
	 * @return array<mixed>
	 * @throws InvalidState
	 * @throws EncodingFailure
	 */
	public function fromContent(string $content, string $extension): array
	{
		$source = $this->getDataSource($extension);

		try {
			$data = $source->decode($content);
		} catch (EncodingFailure $exception) {
			$message = Message::create()
				->withContext("Trying to decode {$extension} into an array.")
				->withProblem($exception->getMessage());

			throw $exception
				->withMessage($message);
		}

		if (!is_array($data)) {
			$dataType = get_debug_type($data);

			throw EncodingFailure::create()
				->withMessage("Decoding ended with unexpected result. Expected array, {$dataType} given.");
		}

		return $data;
	}

	/**
	 * @return array<mixed>
	 * @throws InvalidState
	 * @throws EncodingFailure
	 */
	public function fromFile(string $file): array
	{
		$content = file_get_contents($file);

		if ($content === false) {
			throw InvalidState::create()
				->withMessage("File {$file} is not readable.");
		}

		return $this->fromContent(
			$content,
			$this->getFileExtension($file),
		);
	}

	/**
	 * @param array<mixed> $data
	 * @throws InvalidState
	 * @throws EncodingFailure
	 */
	public function toContent(array $data, string $extension): string
	{
		$source = $this->getDataSource($extension);

		try {
			return $source->encode($data);
		} catch (EncodingFailure $exception) {
			$message = Message::create()
				->withContext("Trying to encode array into {$extension}.")
				->withProblem($exception->getMessage());

			throw $exception
				->withMessage($message);
		}
	}

	/**
	 * @param array<mixed> $data
	 * @throws InvalidState
	 * @throws EncodingFailure
	 */
	public function toFile(string $file, array $data): void
	{
		$content = $this->toContent($data, $this->getFileExtension($file));

		if (file_put_contents($file, $content) === false) {
			throw InvalidState::create()
				->withMessage("File {$file} is not writable.");
		}
	}

	/**
	 * @throws InvalidState No encoder is available for given file type
	 */
	abstract protected function getDataSource(string $extension): FormatEncoder;

	protected function getFileExtension(string $file): string
	{
		$ext = pathinfo($file, PATHINFO_EXTENSION);

		if ($ext === '') {
			throw InvalidArgument::create()
				->withMessage("File {$file} has no extension.");
		}

		return $ext;
	}

}
