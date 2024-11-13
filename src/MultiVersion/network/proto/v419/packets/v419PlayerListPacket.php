<?php

namespace MultiVersion\network\proto\v419\packets;

use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;

class v419PlayerListPacket extends PlayerListPacket{

	public static function fromLatest(PlayerListPacket $pk) : self{
		$npk = new self();
		$npk->type = $pk->type;
		$npk->entries = $pk->entries;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->type = $in->getByte();
		$count = $in->getUnsignedVarInt();
		for($i = 0; $i < $count; ++$i){
			$entry = new PlayerListEntry();

			if($this->type === self::TYPE_ADD){
				$entry->uuid = $in->getUUID();
				$entry->actorUniqueId = $in->getActorUniqueId();
				$entry->username = $in->getString();
				$entry->xboxUserId = $in->getString();
				$entry->platformChatId = $in->getString();
				$entry->buildPlatform = $in->getLInt();
				$entry->skinData = $in->getSkin();
				$entry->isTeacher = $in->getBool();
				$entry->isHost = $in->getBool();
				$entry->isSubClient = false;
			}else{
				$entry->uuid = $in->getUUID();
			}

			$this->entries[$i] = $entry;
		}
		if($this->type === self::TYPE_ADD){
			for($i = 0; $i < $count; ++$i){
				$this->entries[$i]->skinData->setVerified($in->getBool());
			}
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putByte($this->type);
		$out->putUnsignedVarInt(count($this->entries));
		foreach($this->entries as $entry){
			if($this->type === self::TYPE_ADD){
				$out->putUUID($entry->uuid);
				$out->putActorUniqueId($entry->actorUniqueId);
				$out->putString($entry->username);
				$out->putString($entry->xboxUserId);
				$out->putString($entry->platformChatId);
				$out->putLInt($entry->buildPlatform);
				$out->putSkin($entry->skinData);
				$out->putBool($entry->isTeacher);
				$out->putBool($entry->isHost);
			}else{
				$out->putUUID($entry->uuid);
			}
		}
		if($this->type === self::TYPE_ADD){
			foreach($this->entries as $entry){
				$out->putBool($entry->skinData->isVerified());
			}
		}
	}
}