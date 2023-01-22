<?php declare(strict_types = 1);

namespace Orisai\DataSources\Bridge\SymfonyYaml;

use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\DataSources\FormatEncoder;
use Orisai\Utils\Dependencies\Dependencies;
use Orisai\Utils\Dependencies\Exception\PackageRequired;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use function str_contains;
use function str_replace;

final class YamlFormatEncoder implements FormatEncoder
{

	public function __construct()
	{
		if (($deps = Dependencies::getNotLoadedPackages(['symfony/yaml'])) !== []) {
			throw PackageRequired::forClass($deps, self::class);
		}
	}

	public static function getContentTypes(): array
	{
		return [
			'application/x-yml',
			'application/x-yaml',
		];
	}

	public static function getFileExtensions(): array
	{
		return [
			'yml',
			'yaml',
		];
	}

	/**
	 * @return mixed
	 */
	public function decode(string $content)
	{
		try {
			return Yaml::parse(
				$content,
				Yaml::PARSE_OBJECT_FOR_MAP | Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE,
			);
		} catch (ParseException $exception) {
			$e = EncodingFailure::fromPrevious($exception);
			$this->replaceIrrelevantExceptionMessageParts($e);

			throw $e;
		}
	}

	/**
	 * @param mixed $content
	 */
	public function encode($content): string
	{
		return Yaml::dump(
			$content,
			512,
			2,
			Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK,
		);
	}

	private function replaceIrrelevantExceptionMessageParts(EncodingFailure $e): void
	{
		$message = $e->getMessage();

		if (str_contains(
			$message,
			$trashMsg = 'Did you forget to pass the "Yaml::PARSE_CONSTANT" flag to the parser? ',
		)) {
			$e->withMessage(str_replace(
				$trashMsg,
				'',
				$message,
			));

			return;
		}

		if (str_contains(
			$message,
			$trashMsg = 'Enable the "Yaml::PARSE_CUSTOM_TAGS" flag to use',
		)) {
			$e->withMessage(str_replace(
				$trashMsg,
				'Tag',
				$message,
			));
		}
	}

}
