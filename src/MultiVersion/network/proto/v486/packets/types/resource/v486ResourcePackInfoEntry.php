<?php
namespace MultiVersion\network\proto\v486\packets\types\resource;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackInfoEntry;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v486ResourcePackInfoEntry extends ResourcePackInfoEntry{
	public function __construct(private string $packId, private string $version, private int $sizeBytes, private string $encryptionKey = "", private string $subPackName = "", private string $contentId = "", private bool $hasScripts = false, private bool $isAddonPack = false, private bool $isRtxCapable = false){
		parent::__construct($packId, $this->version, $this->sizeBytes, $this->encryptionKey, $this->subPackName, $this->contentId, $this->hasScripts, $this->isAddonPack, $this->isRtxCapable);
	}

	public function write(PacketSerializer $out) : void{
		$out->putString($this->packId);
		$out->putString($this->version);
		$out->putLLong($this->sizeBytes);
		$out->putString($this->encryptionKey);
		$out->putString($this->subPackName);
		$out->putString($this->contentId);
		$out->putBool($this->hasScripts);
		$out->putBool($this->isRtxCapable);
	}

	public static function read(PacketSerializer $in) : self{
		$uuid = $in->getString();
		$version = $in->getString();
		$sizeBytes = $in->getLLong();
		$encryptionKey = $in->getString();
		$subPackName = $in->getString();
		$contentId = $in->getString();
		$hasScripts = $in->getBool();
		$rtxCapable = $in->getBool();
		return new self($uuid, $version, $sizeBytes, $encryptionKey, $subPackName,$contentId, $hasScripts, false, $rtxCapable);
	}
}