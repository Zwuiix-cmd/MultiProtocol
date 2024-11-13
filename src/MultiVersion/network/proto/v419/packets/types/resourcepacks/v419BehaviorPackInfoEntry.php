<?php
namespace MultiVersion\network\proto\v419\packets\types\resource;
use pocketmine\network\mcpe\protocol\types\resourcepacks\BehaviorPackInfoEntry;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
class v419BehaviorPackInfoEntry extends BehaviorPackInfoEntry {
	private string $packId;
	private string $version;
	private int $sizeBytes;
	private string $encryptionKey = "";
	private string $subPackName = "";
	private string $contentId = "";
	private bool $hasScripts = false;

	public function __construct(string $packId, string $version, int $sizeBytes, string $encryptionKey = "", string $subPackName = "", string $contentId = "", bool $hasScripts = false, bool $isAddonPack = false){
		parent::__construct($packId, $version, $sizeBytes, $encryptionKey, $subPackName, $contentId, $hasScripts, $isAddonPack);
	}

	public function write(PacketSerializer $out) : void{
		$out->putString($this->packId);
		$out->putString($this->version);
		$out->putLLong($this->sizeBytes);
		$out->putString($this->encryptionKey);
		$out->putString($this->subPackName);
		$out->putString($this->contentId);
		$out->putBool($this->hasScripts);
	}

	public static function read(PacketSerializer $in) : self{
		$uuid = $in->getString();
		$version = $in->getString();
		$sizeBytes = $in->getLLong();
		$encryptionKey = $in->getString();
		$subPackName = $in->getString();
		$contentId = $in->getString();
		$hasScripts = $in->getBool();
		return new self($uuid, $version, $sizeBytes, $encryptionKey, $subPackName, $contentId, $hasScripts);
	}
}