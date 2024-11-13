<?php

declare(strict_types=1);

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\TextPacket;

class v486TextPacket extends TextPacket {
    public static function fromLatest(TextPacket $pk): self{
        $npk = new self();
        $npk->type = $pk->type;
        $npk->message = $pk->message;
        $npk->needsTranslation = $pk->needsTranslation;
        $npk->xboxUserId = $pk->xboxUserId;
        $npk->platformChatId = $pk->platformChatId;
        $npk->parameters = $pk->parameters;
        return $npk;
    }

    protected function decodePayload(PacketSerializer $in) : void{
        $this->type = $in->getByte();
        $this->needsTranslation = $in->getBool();
        switch($this->type){
            case self::TYPE_CHAT:
            case self::TYPE_WHISPER:
                /** @noinspection PhpMissingBreakStatementInspection */
            case self::TYPE_ANNOUNCEMENT:
                $this->sourceName = $in->getString();
            case self::TYPE_RAW:
            case self::TYPE_TIP:
            case self::TYPE_SYSTEM:
            case self::TYPE_JSON_WHISPER:
            case self::TYPE_JSON:
            case self::TYPE_JSON_ANNOUNCEMENT:
                $this->message = $in->getString();
                break;

            case self::TYPE_TRANSLATION:
            case self::TYPE_POPUP:
            case self::TYPE_JUKEBOX_POPUP:
                $this->message = $in->getString();
                $count = $in->getUnsignedVarInt();
                for($i = 0; $i < $count; ++$i){
                    $this->parameters[] = $in->getString();
                }
                break;
        }

        $this->xboxUserId = $in->getString();
        $this->platformChatId = $in->getString();
    }

    protected function encodePayload(PacketSerializer $out) : void{
        $out->putByte($this->type);
        $out->putBool($this->needsTranslation);
        switch($this->type){
            case self::TYPE_CHAT:
            case self::TYPE_WHISPER:
                /** @noinspection PhpMissingBreakStatementInspection */
            case self::TYPE_ANNOUNCEMENT:
                $out->putString($this->sourceName);
            case self::TYPE_RAW:
            case self::TYPE_TIP:
            case self::TYPE_SYSTEM:
            case self::TYPE_JSON_WHISPER:
            case self::TYPE_JSON:
            case self::TYPE_JSON_ANNOUNCEMENT:
                $out->putString($this->message);
                break;

            case self::TYPE_TRANSLATION:
            case self::TYPE_POPUP:
            case self::TYPE_JUKEBOX_POPUP:
                $out->putString($this->message);
                $out->putUnsignedVarInt(count($this->parameters));
                foreach($this->parameters as $p){
                    $out->putString($p);
                }
                break;
        }

        $out->putString($this->xboxUserId);
        $out->putString($this->platformChatId);
    }
}