<?php

namespace MultiVersion\network\proto\v419\packets;

use MultiVersion\network\proto\v419\packets\types\resource\v419BehaviorPackInfoEntry;
use MultiVersion\network\proto\v419\packets\types\resourcepacks\v419ResourcePackInfoEntry;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\resourcepacks\BehaviorPackInfoEntry;

class v419ResourcePacksInfoPacket extends ResourcePacksInfoPacket{

    /** @var v419ResourcePackInfoEntry[] */
    public array $_resourcePackEntries = [];
    public array $_behaviorPackEntries = [];

    public static function fromLatest(ResourcePacksInfoPacket $pk) : self{
        $npk = new self();
        $npk->mustAccept = $pk->mustAccept;
        $npk->hasScripts = $pk->hasScripts;
        $npk->_resourcePackEntries = array_map([v419ResourcePackInfoEntry::class, "fromLatest"], $pk->resourcePackEntries);
        $npk->_behaviorPackEntries = [];
        return $npk;
    }

    protected function decodePayload(PacketSerializer $in) : void{
        $this->mustAccept = $in->getBool();
        $this->hasScripts = $in->getBool();
        $behaviorPackCount = $in->getLShort();
        while($behaviorPackCount-- > 0){
            $this->_behaviorPackEntries[] = v419BehaviorPackInfoEntry::read($in);
        }

        $resourcePackCount = $in->getLShort();
        while($resourcePackCount-- > 0){
            $this->_resourcePackEntries[] = v419ResourcePackInfoEntry::read($in);
        }
    }

    protected function encodePayload(PacketSerializer $out) : void{
        $out->putBool($this->mustAccept);
        $out->putBool($this->hasScripts);
        $out->putLShort(count($this->_behaviorPackEntries));
        foreach($this->_behaviorPackEntries as $entry){
            $entry->write($out);
        }
        $out->putLShort(count($this->_resourcePackEntries));
        foreach($this->_resourcePackEntries as $entry){
            $entry->write($out);
        }
    }
}