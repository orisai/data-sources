<?php declare(strict_types = 1);

namespace Orisai\DataSources;

use Orisai\DataSources\Exception\NotSupportedType;

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
	 * @return array<FormatEncoder>
	 */
	protected function getFormatEncoders(): array
	{
		return $this->encoders;
	}

	/**
	 * @throws NotSupportedType
	 */
	protected function getFormatEncoder(string $type): FormatEncoder
	{
		foreach ($this->encoders as $encoder) {
			if ($encoder::supportsType($type)) {
				return $encoder;
			}
		}

		throw NotSupportedType::create($type, $this->getSupportedTypes());
	}

}
