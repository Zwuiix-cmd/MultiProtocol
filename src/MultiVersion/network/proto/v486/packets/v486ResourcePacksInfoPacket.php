<?php

namespace MultiVersion\network\proto\v486\packets;

use MultiVersion\network\proto\v486\packets\types\resource\v486BehaviorPackInfoEntry;
use MultiVersion\network\proto\v486\packets\types\resource\v486ResourcePackInfoEntry;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
class v486ResourcePacksInfoPacket extends ResourcePacksInfoPacket{
    private array $_behaviorPackEntries = [];
    protected bool $_forceServerPacks;

	public static function fromLatest(ResourcePacksInfoPacket $pk) : self{
		$npk = new self();
		$npk->mustAccept = $pk->mustAccept;
		$npk->hasScripts = $pk->hasScripts;
		$npk->_forceServerPacks = true;
		$npk->_behaviorPackEntries = [];
		$npk->resourcePackEntries = $pk->resourcePackEntries;
		return $npk;
	}

	protected function decodePayload(PacketSerializer $in) : void{
		$this->mustAccept = $in->getBool();
		$this->hasScripts = $in->getBool();
		$this->_forceServerPacks = $in->getBool();
		$behaviorPackCount = $in->getLShort();
		while($behaviorPackCount-- > 0){
			$this->_behaviorPackEntries[] = v486BehaviorPackInfoEntry::read($in);
		}

		$resourcePackCount = $in->getLShort();
		while($resourcePackCount-- > 0){
			$this->resourcePackEntries[] = v486ResourcePackInfoEntry::read($in);
		}
	}

	protected function encodePayload(PacketSerializer $out) : void{
		$out->putBool($this->mustAccept);
		$out->putBool($this->hasScripts);
		$out->putBool($this->_forceServerPacks);
		$out->putLShort(count($this->_behaviorPackEntries));
		foreach($this->_behaviorPackEntries as $entry){
			$e = new v486BehaviorPackInfoEntry($entry->getPackId(), $entry->getVersion(), $entry->getSizeBytes(), $entry->getEncryptionKey(), $entry->getSubPackName(), $entry->getContentId());
			$e->write($out);
		}
		$out->putLShort(count($this->resourcePackEntries));
		foreach($this->resourcePackEntries as $entry){
			$e = new v486ResourcePackInfoEntry($entry->getPackId(), $entry->getVersion(), $entry->getSizeBytes(), $entry->getEncryptionKey(), $entry->getSubPackName(), $entry->getContentId(), $entry->hasScripts());
			$e->write($out);
		}
	}
}