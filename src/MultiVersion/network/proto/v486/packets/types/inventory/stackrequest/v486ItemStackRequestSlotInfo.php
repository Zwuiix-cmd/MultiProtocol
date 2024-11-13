<?php
namespace MultiVersion\network\proto\v486\packets\types\inventory\stackrequest;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

final class v486ItemStackRequestSlotInfo{
	public function __construct(
		private int $containerId,
		private int $slotId,
		private int $stackId
	){}

	public function getContainerId() : int{ return $this->containerId; }

	public function getSlotId() : int{ return $this->slotId; }

	public function getStackId() : int{ return $this->stackId; }

	public static function read(PacketSerializer $in) : self{
		$containerId = $in->getByte();
		$slotId = $in->getByte();
		$stackId = $in->readItemStackNetIdVariant();
		return new self($containerId, $slotId, $stackId);
	}

	public function write(PacketSerializer $out) : void{
		$out->putByte($this->containerId);
		$out->putByte($this->slotId);
		$out->writeItemStackNetIdVariant($this->stackId);
	}
}