<?php

namespace MultiVersion\network\proto\batch;

use MultiVersion\network\proto\PacketTranslator;
use MultiVersion\network\proto\utils\ReflectionUtils;
use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\CompressBatchTask;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\protocol\types\CompressionAlgorithm;
use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\timings\TimingsHandler;

final class MVBatch{

	public static function prepareBatch(string $buffer, PacketTranslator $translator, Compressor $compressor, ?bool $sync = null, ?TimingsHandler $timings = null) : CompressBatchPromise|string{
		$server = Server::getInstance();

		$timings ??= Timings::$playerNetworkSendCompress;
		try{
			$timings->startTiming();

			$threshold = $compressor->getCompressionThreshold();
			if(($threshold === null || strlen($buffer) < $compressor->getCompressionThreshold()) && !$translator::OLD_COMPRESSION){
				$compressionType = CompressionAlgorithm::NONE;
				$compressed = $buffer;

			}else{
				$sync ??= !ReflectionUtils::getProperty(Server::class, $server, "networkCompressionAsync");

				if(!$sync && strlen($buffer) >= ReflectionUtils::getProperty(Server::class, $server, "networkCompressionAsyncThreshold")){
					$promise = new CompressBatchPromise();
					$task = new MVCompressBatchTask($buffer, $promise, $compressor, $translator::OLD_COMPRESSION);
					$server->getAsyncPool()->submitTask($task);
					return $promise;
				}

				$compressionType = $compressor->getNetworkId();
				$compressed = $compressor->compress($buffer);
			}

			return (!$translator::OLD_COMPRESSION ? chr($compressionType) : '') . $compressed;
		}finally{
			$timings->stopTiming();
		}
	}
}