<?php

namespace MultiVersion\network\proto\v419\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;

class v419SetActorMotionPacket extends SetActorMotionPacket{

	public static function fromLatest(SetActorMotionPacket $pk) : self{
		$npk = new self();
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->motion = $pk->motion;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->motion = $in->getVector3();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putVector3($this->motion);
	}
}