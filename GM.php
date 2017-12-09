<?php
namespace GM;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;

use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

use pocketmine\event\player\PlayerLoginEvent;

use pocketmine\Server;
use pocketmine\Player;

class GM extends PluginBase implements Listener
{
    private $gdata, $ulist, $list;

    public function onEnable()
    {
        if (!file_exists($this->getDataFolder())) mkdir($this->getDataFolder());
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->gdata = new Config($this->getDataFolder(). "GildList.json", Config::JSON, array("max" => 5, "list" => array(), "user" => array()));
        $this->list = $this->gdata->get("list");
        $this->ulist = $this->gdata->get("user");
        $this->max = $this->gdata->get("max");
    }

    
    public function onDisable()
    {
        if (isset($this->gdata)){
            $this->gdata->setAll(array("max" => $this->max, "list" => $this->list, "user" => $this->ulist));
            $this->gdata->save();
        }
    }


    public function onCommand(CommandSender $p, Command $command, $label, array $args) //§a[Guild] §f
    {
        if (!$p instanceof Player) return $p->sendMessage("§c[エラー] このコマンドはゲーム内で使用してください");

        $n = $p->getName();
        $ndata = $this->ulist[$n];

            if(isset($args[0])){

                switch ($args[0]) {

                    case 'make':
                        if (isset($this->list[$n]) || !is_null($ndata["leader"]) ) return $p->sendMessage("§a[Guild] §f既にギルドを設立、または所属しています");

                        $sn = strtolower($n);
                        $this->list[$n][$sn] = "$n";
                        $this->ulist[$n]["leader"] = $n;

                        $p->sendMessage("§a[Guild] §bギルドの設立§fが完了しました。");
                    break;

                    case 'add':
                        if (!isset($this->list[$n]) || is_null($ndata["leader"]) ) return $p->sendMessage("§a[Guild] §bあなたはギルドリーダーではない\n§a[Guild] §b又はギルドに所属していないため使用できません");
                        if(count($this->list[$n]) >= $this->max) return $p->sendMessage("§a[Guild] §cメンバーが上限に達しています！");
                        if(!isset($args[1]) || !($tp = Server::getInstance()->getPlayer($args[1])) instanceof Player) return $p->sendMessage("§c[Guild] 正しくプレイヤーを指定してください");
                        $tn = $tp->getName();
                        $gtn = strtolower($tn);

                        if(isset($this->list[$n][$gtn])) return $p->sendMessage("§a[Guild] §e既にあなたのギルドに所属しています");

                        if($this->ulist[$tn]["request"] !== $n)
                            return $p->sendMessage("§a[Guild] §b".$tn."様§cはあなたのギルドに参加申請をしていません");
                            
                        $sn = strtolower($tn);
                        $this->list[$n][$sn] = "$tn";
                        $this->ulist[$tn]["leader"] = $n;
                        $this->ulist[$tn]["request"] = null;
                        $p->sendMessage("§a[Guild] §b".$tn."様§aあなたのギルドに参加しました");
                        $tp->sendMessage("§a[Guild] §b".$n."様§aが参加申請を許可しました");
                    break;

                    case 'list':
                        if (is_null($ndata["leader"])) return $p->sendMessage("§a[Guild] §cあなたはギルドに所属していません");

                        $online = [];

                        foreach (Server::getInstance()->getOnlinePlayers() as $ps) {
                            $online[] = $ps->getName();
                        }

                        $ln = $this->ulist[$n]["leader"];
                        $sn = strtolower($n);

                        if(!isset($this->list[$ln][$sn])) return $p->sendMessage("§a[Guild] §cあなたはこのギルドに所属していません");

                        $p->sendMessage("§a--- リスト ---");
                            foreach ($this->list[$ln] as $name => $Rname) {
                                $isonline = in_array($Rname, $online) ? "§aオンライン" : "§9オフライン";
                                $p->sendMessage("§a>> §b".$Rname." => ".$isonline);
                            }
                        $p->sendMessage("§a-----------");
                    break;

                    case 'kick':
                        if (!isset($this->list[$n])) return $p->sendMessage("§a[Guild] §cあなたはギルドリーダーではないため使用できません");
                        if(!isset($args[1])) return $p->sendMessage("§c[Guild] 正しくプレイヤーを指定してください");

                        if(!($tp = Server::getInstance()->getPlayer($args[1])) instanceof Player){
                            $gtn = strtolower($args[1]);

                        }else{

                            $tn = $tp->getName();
                            $gtn = strtolower($tn);
                        }


                        if(!isset($this->list[$n][$gtn])) return $p->sendMessage("§a[Guild] §c指定したプレイヤーはあなたのギルドに所属していません");

                        $Rname = $this->list[$n][$gtn];
                        $this->ulist[$Rname]["leader"] = null;
                        $p->sendMessage("§b".$Rname."§aをギルドから追放しました");
                        unset($this->list[$n][$gtn]);
                    break;

                    case 'lost':
                        if (!isset($this->list[$n])) return $p->sendMessage("§a[Guild]§bあなたはギルドリーダーではないため使用できません");


                        $online = [];
                        foreach (Server::getInstance()->getOnlinePlayers() as $ps) {
                            $online[] = $ps->getName();
                        }

                        foreach ($this->list[$n] as $name => $Rname) {
                            if(($tp = Server::getInstance()->getPlayer($Rname)) instanceof Player && $tp != $p) $tp->sendMessage("§a[Guild]§dギルドが解散されました");
                            $this->ulist[$Rname]["leader"] = null;
                        }

                        unset($this->list[$n]);
                        $this->ulist[$n]["leader"] = null;
                        $p->sendMessage("§a[Guild]§dギルドを解散しました");
                    break;

                    case 'join':
                        if (!is_null($ndata["leader"])) return $p->sendMessage("§bあなたは既にギルドに所属しています");
                        if(!isset($args[1]) || !($tp = Server::getInstance()->getPlayer($args[1])) instanceof Player) return $p->sendMessage("§c[Guild] 正しくプレイヤーを指定してください");
                        $tn = $tp->getName();

                        if(!isset($this->list[$tn])) return $p->sendMessage("§c指定したプレイヤーはギルドを所持していません");
                        if(count($this->list[$tn]) >= $this->max) return $p->sendMessage("§c希望したギルドのメンバーが".$this->max."人に達しているため参加することはできません");
                        // $gtn = strtolower($tn);
                            
                        $this->ulist[$n]["request"] = $tn;
                        $p->sendMessage("§a【申請】§b".$tn."様§aにギルドへの参加を申請しました");
                        $tp->sendMessage("§a【申請】§b".$n."様§aからギルドへの参加申請が届きました");
                    break;

                    case 'bye':
                        if (isset($this->list[$n])) return $p->sendMessage("§a[Guild]§ §cギルドリーダーがギルドを残したまま脱退することは出来ません。\n§a[Guild]§c /g lostで解散して下さい");
                        if (is_null($ndata["leader"])) return $p->sendMessage("§a[Guild]§ §cあなたはギルドに所属していません");
                            $lead = $this->ulist[$n]["leader"]; 
                            $sn = strtolower($n);

                            unset($this->list[$lead][$sn]);
                            $this->ulist[$n]["leader"] = null;
                            
                            $p->sendMessage("§a[Guild]§a【脱退】§bギルドから脱退しました");
                    break;

                    default:
                        $p->sendMessage("§b----- ギルドリーダーコマンド -----");
                        $p->sendMessage("§b/g make | add <name> | list | kick <name> | lost ");
                        $p->sendMessage("                                      ");
                        $p->sendMessage("§b----- ギルドメンバーコマンド -----");
                        $p->sendMessage("§b/g join <name> | bye | list");
                    break;
                }

            }else{
                $p->sendMessage("§b----- ギルドリーダーコマンド -----");
                $p->sendMessage("§b/g make | add | list | kick | lost ");
                $p->sendMessage("                                      ");
                $p->sendMessage("§b----- ギルドメンバーコマンド -----");
                $p->sendMessage("§b/g join | bye | list");
            }
    }


    public function Connect(PlayerLoginEvent $ev)
    {
        $n = $ev->getPlayer()->getName();
        if(!isset($this->ulist[$n])) $this->ulist[$n] = ["leader" => null, "request" => null];
    }

    public function getGuildList()
    {
        return $this->list;
    }

    public function getUserList()
    {
        return $this->ulist;
    }


}