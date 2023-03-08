<?php
/// Базировано на основе старых разработок плагинов
require_once '/usr/local/ispmgr/lib/php/isp4private/libs/INIStorage.php';
date_default_timezone_set('Europe/Moscow');
class PiminoffISP{
	private $module = "basic";
	private $input;
	public $env;
	public $err;
	public $logs;
	public $config;
	public $tools;
	protected $params = array();
	protected $metadata = array();
	public $elid = false;
	public $plid = false;
	public $func = false;
	public function __construct($module,$logs=false,$lvllog=1,$ceoff=0){
		$cli_devel = 0;
		$this->module = $module;
		$this->logs = new LogsISP($module,$logs,$lvllog);
		$this->env = new EnvironmentISP($module);
		$this->err = new ErrorISP($module);
		$this->tools = new I4PFunc($this);
		if (!is_dir("/usr/local/ispmgr/etc/isp4private/logs/")){
	        if(!mkdir("/usr/local/ispmgr/etc/isp4private/logs/", 0700,true)){
	            $this->err->InternalError("Failed to create a technical folder to work with plugins.");
	        }
	    }
		$this->config = new INIStorage("/usr/local/ispmgr/etc/isp4private/".$module.".ini",true);
		$this->config->def("Name",$module,"Plugin");                                                        # Название модуля
		$this->config->def("Version","1.0","Plugin");                                                       # Версия модуля
		$this->config->def("VersionCode","100","Plugin");                                                   # Версия (код), должна быть больше на сервере, чтобы появилось уведомление об доступном обновлении.
		$this->config->def("Author","ISP4Private","Plugin");                                                # Автор
		$this->config->def("Update","http://download.isp4private.ru/plugins/".$module.".ver","Plugin");     # Ссылка, где будет версия-код
		$this->config->def("Configurate","true","Plugin");                                                  # Поддерживает настройку?
		$this->config->save(1);
		$this->env->login = @$_SERVER["REMOTE_USER"];
		$this->env->authid = @$_SERVER["AUTHID"];
		$this->env->licid = @$_SERVER["LICID"];
		$this->env->mgr_panel = @$_SERVER["MGR"];
		$this->env->mgr_version = @$_SERVER["MGR_VERSION"];
		$this->env->distribution = @$_SERVER["MGR_DISTRIBUTION"];
		$this->env->osname = @$_SERVER["MGR_OSNAME"];
		$this->env->recordlimit = @$_SERVER["RECORDLIMIT"];
		$this->env->access = @$_SERVER["SESSION_LEVEL"];
		$this->env->lang = @$_SERVER["SESSION_LANG"];
		$this->env->server_name = @$_SERVER["SERVER_NAME"];
		$this->env->port = @$_SERVER["SERVER_PORT"];
		if ($ceoff == 1 && $this->env->mgr_panel == "" && $this->env->osname == ""){
			$this->logs->WriteLog(3,'ATTENTION!!! The handler was not launched through the control panel. ');
			$cli_devel = 1;
		} elseif ($this->env->mgr_panel == "" || $this->env->osname == ""){
			$this->logs->WriteLog(3,'The control panel and operating system are not defined. Sorry, the app will be closed.');
			$this->err->InternalError("The control panel and operating system are not defined. Sorry, the app will be closed.");
		}
		$this->logs->WriteLog(1,'Module: '.$module);
		$this->logs->WriteLog(1,'Panel: ['.$this->env->mgr_panel.'] '.$this->env->distribution.' '.$this->env->mgr_version);
		$this->logs->WriteLog(1,'OS: '.$this->env->osname);
		if ($cli_devel == 0){
			if (empty($this->input)){
				$t = time();
				while (!feof(STDIN) && time() - $t <= 5){
					$this->input .= fgets(STDIN, 1024);
				}
			}
			$data = $this->checkDebug($this->input);
			if ($data[0] == "cli"){
				$this->logs->WriteLog(3,'[CLI] [ISP] '.$data[1]);
			} elseif ($data[0] == "xml"){
				$this->logs->WriteLog(3,'[XML] [ISP] '.$data[1]);
				$doc = new SimpleXMLElement($data[1]);
				if ($this->env->login != (string) $doc["user"]){
					$this->err->InternalError("User identification errors: Code #0");
				} elseif ($this->env->access != (int) $doc["level"]){
					$this->err->InternalError("User identification errors: Code #1");
				}
				$this->logs->WriteLog(3,'[XML] [ISP] User Verification: '.$doc["user"]);
				foreach($doc->params as $rp_k => $rp_v){
					$this->params[$rp_k] = $rp_v;
				}
				foreach($doc->metadata as $rp_k => $rp_v){
					$this->metadata[$rp_k] = $rp_v;
				}
				$this->logs->WriteLog(3,'[XML] [ISP] The parameters were processed');
			} else {
				$this->err->InternalError("Error reading data. Sorry, the app will be closed.");
			}
		}

	}
	# Позволяет проверить есть ли, библиотека или нет.
	# Учтите, что на валидность библиотеки нет проверки.
	# Если нет, файла конфигурации он будет создан автоматический
	function checkLib($name){
	    $this->logs->WriteLog(3,'[checkLib] Checking the availability of the "'.$name.'" library...');
	    if (!is_dir("/usr/local/ispmgr/etc/isp4private/libs/")){
	        if(!mkdir("/usr/local/ispmgr/etc/isp4private/libs/", 0700,true)){
	            $this->logs->WriteLog(3,'[checkLib] Plugins folder not available');
	            $this->err->InternalError("Plugins folder not available");
	        }
	    }
	    if (!is_file("/usr/local/ispmgr/lib/php/isp4private/libs/".$name.".php")){
	         $this->logs->WriteLog(3,'[checkLib] Plugin "'.$name.'" is not available.');
	         $this->err->InternalError("The \"".$name."\" library is not available. You may need to install it, if you have problems, contact the library developer.");
	         return false;
	    }
	    $conf = new INIStorage("/usr/local/ispmgr/etc/isp4private/".$name.".ini",true);
	    $conf->def("Name",$name,"Library");                         // Название плагина
	    $conf->def("Version","1.0","Library");                      // Версия плагина
	    $conf->def("Author","ISP4Private.Ru","Library");            // Автор плагина
	    $conf->def("Dependencies","","Library");                    // Через запятую, необходимые библиотеки
	    $conf->save(1);

	    foreach(explode(",",$conf->get("Dependencies","Plugin")) as $dep){
	        if (!$this->checkLib($dep)){
	            $this->err->InternalError("The \"".$dep."\" library is not available. You may need to install it, if you have problems, contact the library developer.");
	        }
	    }
	    $this->logs->WriteLog(3,'[checkLib] The "'.$name.'" plugin is available for connection.');
	    return true;
	}

	function ArrayFormat($ar) {
		$result = array();
		foreach($ar as $k => $v) {
			if (is_array($v)){
				$result[$k] = $this->ArrayFormat($v);
			} else {
				$result[$k] = $v;
			}
		}
		return $result;
	}

	function getMetaData(){
		$js = json_decode(json_encode($this->metadata),true);
		return (isset($js["metadata"])?$js["metadata"]:array());
	}

	public final function getParams(){
		$js = json_decode(json_encode($this->params),true);
		$this->elid = (isset($js["params"]["elid"])?$js["params"]["elid"]:false);
		$this->plid = (isset($js["params"]["plid"])?$js["params"]["plid"]:false);
		$this->func = (isset($js["params"]["func"])?$js["params"]["func"]:false);
		return (isset($js["params"])?$js["params"]:array());
	}

	protected final function checkDebug($input){
		global $argc, $argv;
		return $argc > 1 && $argv[1] != 'before' && $argv[1] != 'after' && $argv[1] != 'final' ? array("cli",$argv[1]) : array("xml",$input);
	}

	public final function checkAccess($access){
		if ($this->env->access < $access){
			$this->err->AccessDenied();
		}
	}
}



class LogsISP {
	private $logs = false;
	private $levellog = 1;
	private $handle_log;

	public function __construct($module,$logs=false,$lvllog=1){
		$this->logs = $logs;
		if ($this->logs){
			$this->handle_log = fopen('/usr/local/ispmgr/etc/isp4private/logs/'.$module.'.log', 'a');
			if ($this->handle_log){
				$this->levellog = $lvllog;
			}
		}
	}

	public function WriteLog($lvl=1,$text){
		if ($this->handle_log && $this->levellog >= $lvl){
			fwrite($this->handle_log, "[".date("Y-m-d H:i:s") . "]\t" . $text . "\n");
		}
	}

	function __destruct() {
		if ($this->logs && $this->handle_log)
		{
			fclose($this->handle_log);
			$this->handle_log = NULL;
		}
	}
}

class EnvironmentISP extends PiminoffISP {
	public $version = "1.0.1";
	public $login = "root"; // REMOTE_USER
	public $authid = 0; // AUTHID
	public $licid = 0; // LICID
	public $mgr_panel = "ispmgr4"; // MGR
	public $mgr_version = "4.0"; // MGR_DISTRIBUTION
	public $distribution = "isp4mgr"; // MGR_OSNAME
	public $osname = "unknown"; // MGR_OSNAME
	public $recordlimit = 1000; // RECORDLIMIT
	public $access = 0; // SESSION_LEVEL
	public $lang = "ru"; // SESSION_LANG
	public $server_name = "localhost"; // SERVER_NAME
	public $port = 1500; // SERVER_PORT
	public function __construct(){
	}
	public function toArray(){
		return array(
		"login" => $this->login,
		"authid" => $this->authid,
		"licid" => $this->licid,
		"mgr_panel" => $this->mgr_panel,
		"mgr_version" => $this->mgr_version,
		"distribution" => $this->distribution,
		"osname" => $this->osname,
		"recordlimit" => $this->recordlimit,
		"access" => $this->access,
		"lang" => $this->lang,
		"server_name" => $this->server_name,
		"port" => $this->port);
	}
}

class XMLData {
	protected $xml;
	public $root;
	public $mode = "table";
	public function __construct($string='<doc></doc>'){
		$this->xml = new SimpleXMLElement($string);
		$this->root = $this->xml;
	}
	public function getXML(){
		return $this->xml;
	}

	public function addNode($parent,$name,$value=null){
		if ($value==null){
			return $parent->addChild($name);
		} else {
			return $parent->addChild($name,$value);
		}
	}

	public function addAttribute($parent,$name,$value){
		$parent->addAttribute($name,$value);
	}

	public function PrintXML(){
		print($this->xml->asXML());
        exit();
	}

	public function PXML(){
		return $this->xml->asXML();
	}

	public function addArray($parent,$array){
		foreach ($array as $k => $v){
			switch ($k){
				case "@attributes":{
                    if (!isset($array["@attributes"])){
                        break;
                    }
					foreach ($array["@attributes"] as $ak => $av){
						$this->addAttribute($parent,$ak,$av);
					}
					break;
				}
				default:{
				    if (is_array($v)){
				        $current = current($v);
				        if (isset($current["@attributes"])){
				            $back = $this->addNode($parent,$k);
				            foreach ($v as $vk => $vv){
				                $attre = $this->addNode($back,$vk);
				                foreach ($vv["@attributes"] as $akk => $avv){
            						$this->addAttribute($attre,$akk,$avv);
            					}
            					unset($vv["@attributes"]);
            					foreach($vv as $akk => $avv){
            					    $vttre = $this->addNode($attre,$akk);
            					    foreach ($avv["@attributes"] as $skk => $svv){
            					        $this->addAttribute($vttre,$skk,$svv);
            					    }
            					}
				            }
				        } else {
				            if ($this->mode == "table"){
				                //echo "detect on\n";
    				            $back = $this->addNode($parent,$k);
    				            $ss = current($v);
    				            //var_dump($k,$ss);
    				            foreach ($ss as $sk => $sv){
    				                $vb = $this->addNode($back,key($v));
    				                foreach ($sv["@attributes"] as $akk => $avv){
                						$this->addAttribute($vb,$akk,$avv);
                					}
                					unset($sv["@attributes"]);
                					foreach($sv as $akk => $avv){
                					    //$vttre = $this->addNode($vb,$akk);
                					    if (is_array($avv)){
                					        $gas = current($avv);
                					        if (isset($gas["@attributes"])){
                					            foreach($avv as $zxkk => $zxvv){
                            					    $cttre = $this->addNode($vb,$akk);
                            					    foreach ($zxvv["@attributes"] as $skk => $svv){
                            					        $this->addAttribute($cttre,$skk,$svv);
                            					    }
                            					}
                					        }
                					    } else {
                                            foreach ($avv["@attributes"] as $skk => $svv){
                    					        $this->addAttribute($vttre,$skk,$svv);
                    					    }
                					    }
                					}
    				            }
				            } else {
				                //echo "detect on\n";
    				            $back = $this->addNode($parent,$k);
    				            $ss = current($v);
    				            //var_dump($k,$ss);
    				            foreach ($ss as $sk => $sv){
    				                $vb = $this->addNode($back,key($v));
    				                foreach ($sv["@attributes"] as $akk => $avv){
                						$this->addAttribute($vb,$akk,$avv);

                					}
                					unset($sv["@attributes"]);
                					foreach($sv as $akk => $avv){
                					    $vttre = $this->addNode($vb,$akk);
                					    foreach ($avv["@attributes"] as $skk => $svv){
                					        $this->addAttribute($vttre,$skk,$svv);
                					    }
                					}
    				            }
				            }
				        }
				        break;
				    }
				}
			}
		}
	}
}

class ErrorISP{
	private $logs;
	protected $xml;
	public function __construct($module){
		$this->xml = new SimpleXMLElement('<doc></doc>');
		$this->logs = new LogsISP($module,true,3);
	}

	protected function getXML(){
		return $this->xml;
	}

	public function AccessDenied($obj){
		$this->logs->WriteLog(1,"[ERROR:6] AccessDenied: ".$obj);
		$xml = $this->getXML();
		$err = $xml->addChild('error');
		$err->addAttribute("code",6);
		$err->addAttribute("obj",$obj);
		print($xml->asXML());
        exit();
	}

	public function AlreadyExists($obj){
		$this->logs->WriteLog(1,"[ERROR:2] AlreadyExists: ".$obj);
		$xml = $this->getXML();
		$err = $xml->addChild('error');
		$err->addAttribute("code",2);
		$err->addAttribute("obj",$obj);
		print($xml->asXML());
        exit();
	}

	public function DirectError($text){
		$this->logs->WriteLog(1,"[ERROR:9] DirectError: ".$text);
		$xml = $this->getXML();
		$err = $xml->addChild('error',$text);
		$err->addAttribute("code",9);
		print($xml->asXML());
        exit();
	}

	public function InternalError($text){
		$this->logs->WriteLog(1,"[ERROR:1] InternalError: ".$text);
		$xml = $this->getXML();
		$err = $xml->addChild('error',$text);
		$err->addAttribute("code",1);
		print($xml->asXML());
        exit();
	}

	public function InvalidValue($val){
		$this->logs->WriteLog(1,"[ERROR:4] InvalidValue: ".$val);
		$xml = $this->getXML();
		$err = $xml->addChild('error');
		$err->addAttribute("val",$val);
		$err->addAttribute("code",4);

		print($xml->asXML());
        exit();
	}

	public function LimitExceed($val){
		$this->logs->WriteLog(1,"[ERROR:5] LimitExceed: ".$val);
		$xml = $this->getXML();
		$err = $xml->addChild('error');
		$err->addAttribute("code",5);
		$err->addAttribute("val",$val);
		print($xml->asXML());
        exit();
	}
	public function LicenceProblem(){
		$this->logs->WriteLog(1,"[ERROR:7] LicenceProblem");
		$xml = $this->getXML();
		$err = $xml->addChild('error');
		$err->addAttribute("code",7);
		print($xml->asXML());
        exit();
	}

	public function NotEnoughtMoney(){
		$this->logs->WriteLog(1,"[ERROR:11] NotEnoughtMoney");
		$xml = $this->getXML();
		$err = $xml->addChild('error');
		$err->addAttribute("code",11);
		print($xml->asXML());
        exit();
	}

	public function NotExists($obj){
		$this->logs->WriteLog(1,"[ERROR:3] NotExists: ".$obj);
		$xml = $this->getXML();
		$err = $xml->addChild('error');
		$err->addAttribute("code",3);
		$err->addAttribute("obj",$obj);
		print($xml->asXML());
        exit();
	}

	public function MessageError($obj,$text="?"){
		$this->logs->WriteLog(1,"[ERROR:8] MessageError: ".$obj." ".$text);
		$xml = $this->getXML();
		$err = $xml->addChild('error',$text);
		$err->addAttribute("code",8);
		$err->addAttribute("obj",$obj);
		print($xml->asXML());
        exit();
	}

	public function FormError($func="i4p.core.error",$text="Message",$elid=false){
	    $this->logs->WriteLog(1,"[ERROR:FE] [".$func."] ".($elid?'['.$elid.']':null)." Form Error: ".$text);
	    echo '<?xml version="1.0" encoding="UTF-8"?><doc user="root" level="7"><metadata name="'.$func.'" type="form"><form nosubmit="yes"><field name="cr_err"><textdata type="data" name="msg"/></field></form></metadata>'.($elid?'<elid>'.$elid.'</elid>':null).'<msg>'.$text.'</msg></doc>';
	    die();
	}
}

class I4PFunc{
	private $isp = false;
	public function __construct($isp){
		$this->isp = $isp;
	}
	# Тестовая функция
	public function test(){
		$this->isp->logs->WriteLog(1,"I4PFunc Hello");
	}

	# Фильтруем строку
	public function filterStr($str){
		return trim(htmlspecialchars($str));
	}
}

?>
