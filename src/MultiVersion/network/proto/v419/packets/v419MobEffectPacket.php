<?php

namespace MultiVersion\network\proto\v419\packets;

use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v419MobEffectPacket extends MobEffectPacket{

	public static function fromLatest(MobEffectPacket $pk) : self{
		$npk = new self();
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->eventId = $pk->eventId;
		$npk->effectId = $pk->effectId;
		$npk->amplifier = $pk->amplifier;
		$npk->particles = $pk->particles;
		$npk->duration = $pk->duration;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->eventId = $in->getByte();
		$this->effectId = $in->getVarInt();
		$this->amplifier = $in->getVarInt();
		$this->particles = $in->getBool();
		$this->duration = $in->getVarInt();
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putByte($this->eventId);
		$out->putVarInt($this->effectId);
		$out->putVarInt($this->amplifier);
		$out->putBool($this->particles);
		$out->putVarInt($this->duration);
	}
}