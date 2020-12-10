<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Nette\IOException;
use Nette\Utils\FileSystem;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Logic\InvalidState;
use Orisai\Exceptions\Message;
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
	public function fromContent(string $content, string $fileType): array
	{
		$source = $this->getDataSource($fileType);

		try {
			$data = $source->decode($content);
		} catch (EncodingFailure $exception) {
			$message = Message::create()
				->withContext("Trying to decode {$fileType} into an array.")
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
		$content = FileSystem::read($file);

		return $this->fromContent(
			$content,
			$this->getFileExtension($file),
		);
	}

	/**
	 * @param array<mixed> $data
	 * @throws InvalidState
	 * @throws IOException
	 * @throws EncodingFailure
	 */
	public function toContent(array $data, string $fileType): string
	{
		$source = $this->getDataSource($fileType);

		try {
			return $source->encode($data);
		} catch (EncodingFailure $exception) {
			$message = Message::create()
				->withContext("Trying to encode array into {$fileType}.")
				->withProblem($exception->getMessage());

			throw $exception
				->withMessage($message);
		}
	}

	/**
	 * @param array<mixed> $data
	 * @throws InvalidState
	 * @throws IOException
	 * @throws EncodingFailure
	 */
	public function toFile(string $file, array $data): void
	{
		$content = $this->toContent($data, $this->getFileExtension($file));
		FileSystem::write($file, $content);
	}

	/**
	 * @throws InvalidState No encoder is available for given file type
	 */
	abstract protected function getDataSource(string $fileType): FormatEncoder;

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
