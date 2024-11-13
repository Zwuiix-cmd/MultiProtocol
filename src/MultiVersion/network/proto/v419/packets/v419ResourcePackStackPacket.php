<?php

namespace MultiVersion\network\proto\v419\packets;

use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackStackEntry;

class v419ResourcePackStackPacket extends ResourcePackStackPacket{

	public static function fromLatest(ResourcePackStackPacket $pk) : self{
		$npk = new self();
		$npk->resourcePackStack = $pk->resourcePackStack;
		$npk->behaviorPackStack = $pk->behaviorPackStack;
		$npk->mustAccept = $pk->mustAccept;
		$npk->baseGameVersion = $pk->baseGameVersion;
		$npk->experiments = $pk->experiments;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->mustAccept = $in->getBool();
		$behaviorPackCount = $in->getUnsignedVarInt();
		while($behaviorPackCount-- > 0){
			$this->behaviorPackStack[] = ResourcePackStackEntry::read($in);
		}

		$resourcePackCount = $in->getUnsignedVarInt();
		while($resourcePackCount-- > 0){
			$this->resourcePackStack[] = ResourcePackStackEntry::read($in);
		}

		$this->baseGameVersion = $in->getString();
		$this->experiments = Experiments::read($in);
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putBool($this->mustAccept);

		$out->putUnsignedVarInt(count($this->behaviorPackStack));
		foreach($this->behaviorPackStack as $entry){
			$entry->write($out);
		}

		$out->putUnsignedVarInt(count($this->resourcePackStack));
		foreach($this->resourcePackStack as $entry){
			$entry->write($out);
		}

		$out->putString($this->baseGameVersion);
		$this->experiments->write($out);
	}
}
