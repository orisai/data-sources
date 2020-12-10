<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Orisai\Exceptions\Logic\InvalidState;

final class DefaultDataSource extends BaseDataSource
{

	/** @var array<FormatEncoder> */
	private array $encoders;

	/**
	 * @param array<FormatEncoder> $encoders
	 */
	public function __construct(array $encoders)
	{
		$this->encoders = $encoders;
	}

	/**
	 * @throws InvalidState
	 */
	protected function getDataSource(string $fileType): FormatEncoder
	{
		foreach ($this->encoders as $encoder) {
			if ($encoder::supportsType($fileType)) {
				return $encoder;
			}
		}

		throw InvalidState::create()
			->withMessage("No encoder is available for file type {$fileType}.");
	}

}
