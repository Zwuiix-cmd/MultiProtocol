<?php

namespace MultiVersion\network\proto;

use MultiVersion\network\MVNetworkSession;
use MultiVersion\network\MVPacketBroadcaster;
use MultiVersion\network\proto\static\MVTypeConverter;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\StandardEntityEventBroadcaster;

abstract class  PacketTranslator{

	public const PROTOCOL_VERSION = null;
	public const RAKNET_VERSION = null;
	public const OLD_COMPRESSION = false;
	public const ENCRYPTION_CONTEXT = true;

	protected MVPacketBroadcaster $broadcaster;

	protected StandardEntityEventBroadcaster $entityEventBroadcaster;

	public function __construct(protected MVTypeConverter $typeConverter, protected PacketSerializerFactory $packetSerializerFactory, protected PacketPool $packetPool, protected Compressor $compressor){
		$this->broadcaster = new MVPacketBroadcaster($this);
		$this->entityEventBroadcaster = new StandardEntityEventBroadcaster($this->broadcaster, $this->typeConverter);
	}

	public function getTypeConverter() : MVTypeConverter{
		return $this->typeConverter;
	}

	public function getPacketSerializerFactory() : PacketSerializerFactory{
		return $this->packetSerializerFactory;
	}

	public function getPacketPool() : PacketPool{
		return $this->packetPool;
	}

	public function getCompressor() : Compressor{
		return $this->compressor;
	}

	public function getBroadcaster() : MVPacketBroadcaster{
		return $this->broadcaster;
	}

	public function getEntityEventBroadcaster() : StandardEntityEventBroadcaster{
		return $this->entityEventBroadcaster;
	}

	abstract public function handleInGame(MVNetworkSession $session) : ?InGamePacketHandler;

	abstract public function handleIncoming(ServerboundPacket $pk) : ?ServerboundPacket;

	abstract public function handleOutgoing(ClientboundPacket $pk) : ?ClientboundPacket;

	abstract public function injectClientData(array &$data) : void;

	abstract public function getCraftingDataCache() : string;
}