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
use function str_contains;
use const PATHINFO_EXTENSION;

final class DefaultDataSource implements DataSource
{

	private FormatEncoderManager $encoderManager;

	public function __construct(FormatEncoderManager $encoderManager)
	{
		$this->encoderManager = $encoderManager;
	}

	/**
	 * @throws NotSupportedType
	 */
	private function getFormatEncoderForMediaType(string $type): FormatEncoder
	{
		foreach ($this->encoderManager->getAll() as $encoder) {
			if ($encoder::supportsContentType($type)) {
				return $encoder;
			}
		}

		throw NotSupportedType::create($type, $this->getContentTypes());
	}

	/**
	 * @throws NotSupportedType
	 */
	private function getFormatEncoderForFileExtension(string $extension): FormatEncoder
	{
		foreach ($this->encoderManager->getAll() as $encoder) {
			if ($encoder::supportsFileExtension($extension)) {
				return $encoder;
			}
		}

		throw NotSupportedType::create($extension, $this->getFileExtensions());
	}

	public function getContentTypes(): array
	{
		$typesByEncoder = [];

		foreach ($this->encoderManager->getAll() as $encoder) {
			$typesByEncoder[] = $encoder::getContentTypes();
		}

		return array_merge(...$typesByEncoder);
	}

	public function getFileExtensions(): array
	{
		$typesByEncoder = [];

		foreach ($this->encoderManager->getAll() as $encoder) {
			$typesByEncoder[] = $encoder::getFileExtensions();
		}

		/** @var list<string> */
		return array_merge(...$typesByEncoder);
	}

	/**
	 * @return mixed
	 * @throws NotSupportedType
	 * @throws EncodingFailure
	 */
	public function fromString(string $content, string $typeOrExtension)
	{
		$source = str_contains($typeOrExtension, '/')
			? $this->getFormatEncoderForMediaType($typeOrExtension)
			: $this->getFormatEncoderForFileExtension($typeOrExtension);

		try {
			$data = $source->decode($content);
		} catch (EncodingFailure $exception) {
			$message = Message::create()
				->withContext("Trying to decode {$typeOrExtension} into data.")
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
	public function toString($data, string $typeOrExtension): string
	{
		$source = str_contains($typeOrExtension, '/')
			? $this->getFormatEncoderForMediaType($typeOrExtension)
			: $this->getFormatEncoderForFileExtension($typeOrExtension);

		try {
			return $source->encode($data);
		} catch (EncodingFailure $exception) {
			$message = Message::create()
				->withContext("Trying to encode data into {$typeOrExtension}.")
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

	private function getFileExtension(string $file): string
	{
		$ext = pathinfo($file, PATHINFO_EXTENSION);

		if ($ext === '') {
			throw InvalidArgument::create()
				->withMessage("File {$file} has no extension.");
		}

		return $ext;
	}

}
