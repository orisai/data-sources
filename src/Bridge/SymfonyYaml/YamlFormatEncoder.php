<?php declare(strict_types = 1);

namespace Orisai\DataSources\Bridge\SymfonyYaml;

use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\DataSources\FormatEncoder;
use Orisai\Utils\Dependencies\Dependencies;
use Orisai\Utils\Dependencies\Exception\PackageRequired;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use function in_array;

final class YamlFormatEncoder implements FormatEncoder
{

	private Parser $parser;

	private Dumper $dumper;

	public function __construct()
	{
		if (($deps = Dependencies::getNotLoadedPackages(['symfony/yaml'])) !== []) {
			throw PackageRequired::forClass($deps, self::class);
		}

		$this->parser = new Parser();
		$this->dumper = new Dumper(2);
	}

	/**
	 * @return array<string>
	 */
	public static function getSupportedTypes(): array
	{
		return [
			'yml',
			'yaml',
			'application/x-yaml',
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
			return $this->parser->parse($content);
		} catch (ParseException $exception) {
			throw EncodingFailure::fromPrevious($exception);
		}
	}

	/**
	 * @param mixed $content
	 */
	public function encode($content): string
	{
		return $this->dumper->dump($content, 512);
	}

}
