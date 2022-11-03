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

	public static function getContentTypes(): array
	{
		return [
			'application/x-yml',
			'application/x-yaml',
		];
	}

	public static function supportsContentType(string $type): bool
	{
		return in_array($type, self::getContentTypes(), true);
	}

	public static function getFileExtensions(): array
	{
		return [
			'yml',
			'yaml',
		];
	}

	public static function supportsFileExtension(string $extension): bool
	{
		return in_array($extension, self::getFileExtensions(), true);
	}

	/**
	 * @return mixed
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
