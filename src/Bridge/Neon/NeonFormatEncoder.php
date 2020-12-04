<?php declare(strict_types = 1);

namespace Orisai\DataSources\Bridge\Neon;

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

	public static function supportsFileType(string $fileType): bool
	{
		return in_array($fileType, [
			'neon',
			'application/x-neon',
		], true);
	}

	/**
	 * @return array<mixed>
	 * @throws EncodingFailure
	 */
	public function decode(string $content): array
	{
		try {
			return $this->decoder->decode($content);
		} catch (Exception $exception) {
			throw EncodingFailure::fromPrevious($exception);
		}
	}

	/**
	 * @param array<mixed> $content
	 * @throws EncodingFailure
	 */
	public function encode(array $content): string
	{
		try {
			return $this->encoder->encode($content, $this->encoder::BLOCK);
		} catch (Exception $exception) {
			throw EncodingFailure::fromPrevious($exception);
		}
	}

}
