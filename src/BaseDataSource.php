<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Nette\IOException;
use Nette\Utils\FileSystem;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Logic\InvalidState;
use Orisai\Exceptions\Message;
use function pathinfo;
use const PATHINFO_EXTENSION;

abstract class BaseDataSource implements DataSource
{

	/**
	 * @return mixed
	 * @throws InvalidState
	 * @throws EncodingFailure
	 */
	public function fromString(string $content, string $fileType)
	{
		$source = $this->getDataSource($fileType);

		try {
			$data = $source->decode($content);
		} catch (EncodingFailure $exception) {
			$message = Message::create()
				->withContext("Trying to decode {$fileType} into data.")
				->withProblem($exception->getMessage());

			throw $exception
				->withMessage($message);
		}

		return $data;
	}

	/**
	 * @return mixed
	 * @throws InvalidState
	 * @throws EncodingFailure
	 */
	public function fromFile(string $file)
	{
		$content = FileSystem::read($file);

		return $this->fromString(
			$content,
			$this->getFileExtension($file),
		);
	}

	/**
	 * @param mixed $data
	 * @throws InvalidState
	 * @throws IOException
	 * @throws EncodingFailure
	 */
	public function toString($data, string $fileType): string
	{
		$source = $this->getDataSource($fileType);

		try {
			return $source->encode($data);
		} catch (EncodingFailure $exception) {
			$message = Message::create()
				->withContext("Trying to encode data into {$fileType}.")
				->withProblem($exception->getMessage());

			throw $exception
				->withMessage($message);
		}
	}

	/**
	 * @param mixed $data
	 * @throws InvalidState
	 * @throws IOException
	 * @throws EncodingFailure
	 */
	public function toFile(string $file, $data): void
	{
		$content = $this->toString($data, $this->getFileExtension($file));
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
