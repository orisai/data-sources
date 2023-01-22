<?php declare(strict_types = 1);

namespace Orisai\DataSources\Bridge\NetteNeon;

use Nette\Neon\Entity;
use Nette\Neon\Exception;
use Nette\Neon\Neon;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\DataSources\FormatEncoder;
use Orisai\Utils\Dependencies\Dependencies;
use Orisai\Utils\Dependencies\Exception\PackageRequired;
use stdClass;
use function is_array;

final class NeonFormatEncoder implements FormatEncoder
{

	private const StdClassEntity = 'object';

	public function __construct()
	{
		if (($deps = Dependencies::getNotLoadedPackages(['nette/neon'])) !== []) {
			throw PackageRequired::forClass($deps, self::class);
		}
	}

	public static function getContentTypes(): array
	{
		return [
			'application/x-neon',
		];
	}

	public static function getFileExtensions(): array
	{
		return [
			'neon',
		];
	}

	/**
	 * @return mixed
	 */
	public function decode(string $content)
	{
		try {
			return $this->decodeIncompatible(Neon::decode($content));
		} catch (Exception $exception) {
			throw EncodingFailure::fromPrevious($exception);
		}
	}

	/**
	 * @param mixed $data
	 * @return mixed $data
	 * @throws EncodingFailure
	 */
	private function decodeIncompatible($data)
	{
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$data[$key] = $this->decodeIncompatible($value);
			}

			return $data;
		}

		if ($data instanceof Entity) {
			if ($data->value !== self::StdClassEntity) {
				throw EncodingFailure::create()
					->withMessage("Only entity with name '" . self::StdClassEntity . "' is allowed.");
			}

			$object = new stdClass();
			foreach ($data->attributes as $key => $value) {
				$object->$key = $this->decodeIncompatible($value);
			}

			return $object;
		}

		return $data;
	}

	/**
	 * @param mixed $data
	 * @return mixed
	 */
	private function encodeIncompatible($data)
	{
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$data[$key] = $this->encodeIncompatible($value);
			}

			return $data;
		}

		if ($data instanceof stdClass) {
			$values = (array) $data;

			foreach ($values as $key => $value) {
				$values[$key] = $this->encodeIncompatible($value);
			}

			return new Entity(self::StdClassEntity, $values);
		}

		return $data;
	}

	/**
	 * @param mixed $content
	 */
	public function encode($content): string
	{
		return Neon::encode($this->encodeIncompatible($content), true);
	}

}
