<?php
namespace MultiVersion\network\proto\v486\packets\types\inventory\stackrequest\trait;
use MultiVersion\network\proto\v486\packets\types\inventory\stackrequest\v486ItemStackRequestSlotInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

trait v486TakeOrPlaceStackRequestActionTrait{
	final public function __construct(
		private int $count,
		private v486ItemStackRequestSlotInfo $source,
		private v486ItemStackRequestSlotInfo $destination
	){}

	final public function getCount() : int{ return $this->count; }

	final public function getSource() : v486ItemStackRequestSlotInfo{ return $this->source; }

	final public function getDestination() : v486ItemStackRequestSlotInfo{ return $this->destination; }

	public static function read(PacketSerializer $in) : self{
		$count = $in->getByte();
		$src = v486ItemStackRequestSlotInfo::read($in);
		$dst = v486ItemStackRequestSlotInfo::read($in);
		return new self($count, $src, $dst);
	}

	public function write(PacketSerializer $out) : void{
		$out->putByte($this->count);
		$this->source->write($out);
		$this->destination->write($out);
	}
}