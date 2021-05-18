<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Nette\IOException;
use Nette\Utils\FileSystem;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\DataSources\Exception\NotSupportedType;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Message;
use function array_merge;
use function pathinfo;
use const PATHINFO_EXTENSION;

abstract class BaseDataSource implements DataSource
{

	/**
	 * @throws NotSupportedType No encoder is available for given file type
	 */
	abstract protected function getFormatEncoder(string $type): FormatEncoder;

	/**
	 * @return array<FormatEncoder>
	 */
	abstract protected function getFormatEncoders(): array;

	/**
	 * @return array<string>
	 */
	public function getSupportedTypes(): array
	{
		$typesByEncoder = [];
		$encoders = $this->getFormatEncoders();

		foreach ($encoders as $encoder) {
			$typesByEncoder[] = $encoder::getSupportedTypes();
		}

		return array_merge(...$typesByEncoder);
	}

	/**
	 * @return mixed
	 * @throws NotSupportedType
	 * @throws EncodingFailure
	 */
	public function fromString(string $content, string $type)
	{
		$source = $this->getFormatEncoder($type);

		try {
			$data = $source->decode($content);
		} catch (EncodingFailure $exception) {
			$message = Message::create()
				->withContext("Trying to decode {$type} into data.")
				->withProblem($exception->getMessage());

			throw $exception
				->withMessage($message);
		}

		return $data;
	}

	/**
	 * @return mixed
	 * @throws NotSupportedType
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
	 * @throws NotSupportedType
	 * @throws EncodingFailure
	 */
	public function toString($data, string $type): string
	{
		$source = $this->getFormatEncoder($type);

		try {
			return $source->encode($data);
		} catch (EncodingFailure $exception) {
			$message = Message::create()
				->withContext("Trying to encode data into {$type}.")
				->withProblem($exception->getMessage());

			throw $exception
				->withMessage($message);
		}
	}

	/**
	 * @param mixed $data
	 * @throws NotSupportedType
	 * @throws IOException
	 * @throws EncodingFailure
	 */
	public function toFile(string $file, $data): void
	{
		$content = $this->toString($data, $this->getFileExtension($file));
		FileSystem::write($file, $content);
	}

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
