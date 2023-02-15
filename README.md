<h1 align="center">
	<img src="https://github.com/orisai/.github/blob/main/images/repo_title.png?raw=true" alt="Orisai"/>
	<br/>
	Data Sources
</h1>

<p align="center">
    Load and save data from and to various data formats
</p>

<p align="center">
	📄 Check out our <a href="docs/README.md">documentation</a>.
</p>

<p align="center">
	💸 If you like Orisai, please <a href="https://orisai.dev/sponsor">make a donation</a>. Thank you!
</p>

<p align="center">
	<a href="https://github.com/orisai/data-sources/actions?query=workflow%3ACI">
		<img src="https://github.com/orisai/data-sources/workflows/CI/badge.svg">
	</a>
	<a href="https://coveralls.io/r/orisai/data-sources">
		<img src="https://badgen.net/coveralls/c/github/orisai/data-sources/v1.x?cache=300">
	</a>
	<a href="https://dashboard.stryker-mutator.io/reports/github.com/orisai/data-sources/v1.x">
		<img src="https://badge.stryker-mutator.io/github.com/orisai/data-sources/v1.x">
	</a>
	<a href="https://packagist.org/packages/orisai/data-sources">
		<img src="https://badgen.net/packagist/dt/orisai/data-sources?cache=3600">
	</a>
	<a href="https://packagist.org/packages/orisai/data-sources">
		<img src="https://badgen.net/packagist/v/orisai/data-sources?cache=3600">
	</a>
	<a href="https://choosealicense.com/licenses/mpl-2.0/">
		<img src="https://badgen.net/badge/license/MPL-2.0/blue?cache=3600">
	</a>
<p>

##

```php
use Orisai\DataSources\DataSource;
use Orisai\DataSources\Exception\EncodingFailure;
use Orisai\DataSources\Exception\NotSupportedType;

try {
	$dataSource->encode('data', 'application/json' /* or just 'json' */); // json-encoded string
} catch (NotSupportedType $exception) {
	// Requested type is not supported
	$exception->getRequestedType(); // 'application/json'
	$exception->getSupportedTypes(); // content types or file extensions, depending on what was requested
} catch (EncodingFailure $exception) {
	// Encoding failed
}
```
