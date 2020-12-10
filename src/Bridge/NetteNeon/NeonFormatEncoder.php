<?php declare(strict_types = 1);

namespace Orisai\DataSources\Bridge\NetteNeon;

use Nette\Neon\Decoder;
use Nette\Neon\Encoder;
use Nette\Neon\Exception;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\DataSources\FormatEncoder;
use Orisai\Utils\Dependencies\Dependencies;
use Orisai\Utils\Dependencies\Exception\PackageRequired;
use function in_array;

final class NeonFormatEncoder implements FormatEncoder
{

	private Decoder $decoder;
	private Encoder $encoder;

	public function __construct()
	{
		if (($deps = Dependencies::getNotLoadedPackages(['nette/neon'])) !== []) {
			throw PackageRequired::forClass($deps, self::class);
		}

		$this->decoder = new Decoder();
		$this->encoder = new Encoder();
	}

	/**
	 * @return array<string>
	 */
	public static function getSupportedTypes(): array
	{
		return [
			'neon',
			'application/x-neon',
		];
	}

	public static function supportsType(string $type): bool
	{
		return in_array($type, self::getSupportedTypes(), true);
	}

	/**
	 * @return mixed
	 * @throws EncodingFailure
	 */
	public function decode(string $content)
	{
		try {
			return $this->decoder->decode($content);
		} catch (Exception $exception) {
			throw EncodingFailure::fromPrevious($exception);
		}
	}

	/**
	 * @param mixed $content
	 * @throws EncodingFailure
	 */
	public function encode($content): string
	{
		try {
			return $this->encoder->encode($content, $this->encoder::BLOCK);
		} catch (Exception $exception) {
			throw EncodingFailure::fromPrevious($exception);
		}
	}

}
