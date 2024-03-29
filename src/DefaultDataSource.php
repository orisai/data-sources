<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Nette\IOException;
use Nette\Utils\FileSystem;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\DataSources\Exception\NotSupportedType;
use Orisai\Exceptions\Message;
use stdClass;
use function array_merge;
use function get_class;
use function get_debug_type;
use function in_array;
use function is_array;
use function is_object;
use function is_resource;
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
			if (in_array($type, $encoder::getContentTypes(), true)) {
				return $encoder;
			}
		}

		throw NotSupportedType::forUnknownType($type, $this->getContentTypes());
	}

	/**
	 * @throws NotSupportedType
	 */
	private function getFormatEncoderForFileExtension(string $extension): FormatEncoder
	{
		foreach ($this->encoderManager->getAll() as $encoder) {
			if (in_array($extension, $encoder::getFileExtensions(), true)) {
				return $encoder;
			}
		}

		throw NotSupportedType::forUnknownType($extension, $this->getFileExtensions());
	}

	public function getContentTypes(): array
	{
		$typesByEncoder = [];

		foreach ($this->encoderManager->getAll() as $encoder) {
			$typesByEncoder[] = $encoder::getContentTypes();
		}

		return array_merge(...$typesByEncoder);
	}

	public function supportsContentType(string $type): bool
	{
		return in_array($type, $this->getContentTypes(), true);
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

	public function supportsFileExtension(string $extension): bool
	{
		return in_array($extension, $this->getFileExtensions(), true);
	}

	/**
	 * @return mixed
	 * @throws NotSupportedType
	 * @throws EncodingFailure
	 */
	public function decode(string $content, string $typeOrExtension)
	{
		$isMediaType = str_contains($typeOrExtension, '/');
		$source = $isMediaType
			? $this->getFormatEncoderForMediaType($typeOrExtension)
			: $this->getFormatEncoderForFileExtension($typeOrExtension);

		try {
			$data = $source->decode($content);
		} catch (EncodingFailure $exception) {
			$message = Message::create()
				->withContext("Decoding content of type '$typeOrExtension' into raw data.")
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
	public function decodeFromFile(string $file)
	{
		return $this->decode(
			FileSystem::read($file),
			$this->getFileExtension($file),
		);
	}

	/**
	 * @param mixed $data
	 * @throws NotSupportedType
	 * @throws EncodingFailure
	 */
	public function encode($data, string $typeOrExtension): string
	{
		$isMediaType = str_contains($typeOrExtension, '/');
		$source = $isMediaType
			? $this->getFormatEncoderForMediaType($typeOrExtension)
			: $this->getFormatEncoderForFileExtension($typeOrExtension);

		$this->checkData($data, $typeOrExtension);

		try {
			return $source->encode($data);
		} catch (EncodingFailure $exception) {
			$message = Message::create()
				->withContext("Encoding raw data into string of type '$typeOrExtension'.")
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
	public function encodeToFile(string $file, $data): void
	{
		FileSystem::write(
			$file,
			$this->encode(
				$data,
				$this->getFileExtension($file),
			),
		);
	}

	private function getFileExtension(string $file): string
	{
		$ext = pathinfo($file, PATHINFO_EXTENSION);

		if ($ext === '') {
			throw NotSupportedType::forNoFileExtension($file, $this->getFileExtensions());
		}

		return $ext;
	}

	/**
	 * @param mixed $data
	 * @throws EncodingFailure
	 */
	private function checkData($data, string $typeOrExtension): void
	{
		if ($data instanceof stdClass && get_class($data) === stdClass::class) {
			$data = (array) $data;
		}

		if (is_array($data)) {
			foreach ($data as $value) {
				$this->checkData($value, $typeOrExtension);
			}

			return;
		}

		if (is_object($data) || is_resource($data)) {
			$type = get_debug_type($data);
			$message = Message::create()
				->withContext("Encoding raw data into string of type '$typeOrExtension'.")
				->withProblem("Data contains PHP type '$type', which is not allowed.")
				->withSolution('Change type to one of supported - scalar, null, array or stdClass.');

			throw EncodingFailure::create()
				->withMessage($message);
		}
	}

}
