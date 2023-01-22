# Data sources

Load and save data from and to various data formats

## Content

- [Setup](#setup)
- [Data source](#data-source)
- [Format encoders](#format-encoders)
	- [Json](#json)
	- [Yaml](#yaml)
	- [Neon](#neon)
- [Accepted data types](#accepted-data-types)

## Setup

Install with [Composer](https://getcomposer.org)

```sh
composer require orisai/data-sources
```

```php
use Orisai\DataSources\Bridge\SymfonyYaml\YamlFormatEncoder;
use Orisai\DataSources\DefaultDataSource;
use Orisai\DataSources\DefaultFormatEncoderManager;
use Orisai\DataSources\JsonFormatEncoder;

$manager = new DefaultFormatEncoderManager();
$manager->addEncoder(new JsonFormatEncoder());
$manager->addEncoder(new YamlFormatEncoder()); // requires symfony/yaml

$dataSource = new DefaultDataSource($manager);
```

### Data source

Decode and encode raw data and files

```php
use Orisai\DataSources\DataSource;
use Orisai\DataSources\Exception\NotSupportedType;

final class Example
{

	private DataSource $dataSource;

	public function __construct(DataSource $dataSource)
	{
		$this->dataSource = $dataSource;
	}

	public function encode(): void
	{
		$this->dataSource->encode(
			['raw', 'data'],
			'json',
		); // string

		$this->dataSource->encode(
			['raw', 'data'],
			'application/json',
		); // string
	}

	public function decode(): void
	{
		$this->dataSource->decode('["raw","data"]', 'json'); // mixed
		$this->dataSource->decode('["raw","data"]', 'application/json'); // mixed
	}

	public function encodeFile(): void
	{
		// Create json file
		$this->dataSource->encodeToFile(__DIR__ . '/file.json', ['raw', 'data']);
	}

	public function decodeFile(): void
	{
		$this->dataSource->decodeFromFile(__DIR__ . '/file.json'); // mixed
	}

	public function checkAvailableFormats(): void
	{
		$this->dataSource->supportsContentType('application/json'); // bool
		$this->dataSource->getContentTypes(); // list<string>, e.g. ['application/json', 'application/x-neon']

		$this->dataSource->supportsFileExtension('json'); // bool
		$this->dataSource->getFileExtensions(); // list<string>, e.g. ['json', 'neon']

		try {
			$this->dataSource->encode('data', 'unsupported/type');
		} catch (NotSupportedType $exception) {
			$exception->getRequestedType(); // string, 'unsupported/type'
			$exception->getSupportedTypes(); // content types or file extensions, depending on what was requested
		}
	}

}
```

### Format encoders

Encoder is responsible for encoding and decoding of one specific format. Whether format is supported is detected from
provided content types and file extensions.

#### Json

Json format encoder

```php
use Orisai\DataSources\JsonFormatEncoder;

$encoder = new JsonFormatEncoder();
```

Supports:

- file extensions - `json`
- media types - `application/json`

#### Yaml

Yaml format encoder

- requires `symfony/yaml` to be installed

```php
use Orisai\DataSources\Bridge\SymfonyYaml\YamlFormatEncoder;

$encoder = new YamlFormatEncoder();
```

Supports:

- file extensions - `yml` and `yaml`
- media types - `application/x-yml` and `application/x-yaml`

Some format-specific features are not supported for compatibility with other formats:

- features generally [unsupported by symfony/yaml](https://symfony.com/doc/current/components/yaml.html#unsupported-yaml-features)
- PHP constant/enum/object tags (symfony/yaml feature)
- Custom YAML tags

#### Neon

[Neon](https://github.com/nette/neon) format encoder

- requires `nette/neon` to be installed

```php
use Orisai\DataSources\Bridge\NetteNeon\NeonFormatEncoder;

$encoder = new NeonFormatEncoder();
```

Supports:

- file extensions - `neon`
- media types - `application/x-neon`

Some format-specific features are not supported for compatibility with other formats:

- neon entities
  - e.g. `example()` or `example(foo: bar)`
  - except `object()` which is used to represent stdClass

#### Custom

Custom encoder must implement `FormatEncoder` interface

Decoding must return data equal to data that were encoded. Only exception are numeric string keys in arrays, which are
impossible to have in PHP arrays.

```php
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\DataSources\FormatEncoder;

final class ExampleFormatEncoder implements FormatEncoder
{

	public static function getContentTypes(): array
	{
		return ['application/x-example'];
	}

	public static function getFileExtensions(): array
	{
		return ['example'];
	}

	public function decode(string $content)
	{
		// If decoding failed, EncodingFailure must be thrown
		try {
			return ExampleDecoder::decode($content);
		} catch (ExampleException $exception) {
			throw EncodingFailure::fromPrevious($exception);
		}
	}

	public function encode($content): string
	{
		// If encoding failed, EncodingFailure must be thrown
		try {
			return ExampleEncoder::encode($content);
		} catch (ExampleException $exception) {
			throw EncodingFailure::fromPrevious($exception);
		}
	}

}
```

#### Accepted data types

To make all encoders compatible and decoders output more reliable, only following PHP types are supported:

- `array` (of following types, including array)
- `stdClass` (cannot be extended)
- `scalar` (`int`, `float`, `string`, `bool`)
- `null`

Encoded data validity is ensured by [`DataSource`](#data-source).

Decoded data validity must be ensured by each [`FormatEncoder`](#format-encoders).
