<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Orisai\Exceptions\Logic\InvalidState;

final class DefaultDataSource extends BaseDataSource
{

	/** @var array<FormatEncoder> */
	private array $dataSources;

	/**
	 * @param array<FormatEncoder> $dataSources
	 */
	public function __construct(array $dataSources)
	{
		$this->dataSources = $dataSources;
	}

	protected function getDataSource(string $extension): FormatEncoder
	{
		foreach ($this->dataSources as $source) {
			if ($source::supportsFileType($extension)) {
				return $source;
			}
		}

		throw InvalidState::create()
			->withMessage("No encoder is available for file type {$extension}.");
	}

}
