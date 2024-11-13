<?php

namespace MultiVersion\network\proto\batch;

use MultiVersion\network\proto\PacketTranslator;
use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\scheduler\AsyncTask;
use pocketmine\thread\NonThreadSafeValue;

final class MVCompressBatchTask extends AsyncTask{

	private const TLS_KEY_PROMISE = "promise";

	/** @phpstan-var NonThreadSafeValue<Compressor> */
	private NonThreadSafeValue $compressor;

	public function __construct(
		private string $data,
		CompressBatchPromise $promise,
		Compressor $compressor,
		private bool $oldCompression
	){
		$this->compressor = new NonThreadSafeValue($compressor);
		$this->storeLocal(self::TLS_KEY_PROMISE, $promise);
	}

	public function onRun() : void{
		$compressor = $this->compressor->deserialize();
		$this->setResult((!$this->oldCompression ? chr($compressor->getNetworkId()) : '') . $compressor->compress($this->data));
	}

	public function onCompletion() : void{
		/** @var CompressBatchPromise $promise */
		$promise = $this->fetchLocal(self::TLS_KEY_PROMISE);
		$promise->resolve($this->getResult());
	}
}