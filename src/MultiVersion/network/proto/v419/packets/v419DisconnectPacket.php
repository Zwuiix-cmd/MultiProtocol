<?php

namespace MultiVersion\network\proto\v419\packets;

use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v419DisconnectPacket extends DisconnectPacket{

	public static function fromLatest(DisconnectPacket $pk) : self{
		$npk = new self();
		$npk->message = $pk->message;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$hideDisconnectionScreen = $in->getBool();
		if(!$hideDisconnectionScreen){
			$this->message = $in->getString();
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putBool($this->message === null);
		if($this->message !== null){
			$out->putString($this->message);
		}
	}
}