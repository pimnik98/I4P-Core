#!/usr/bin/php
<?php
# Инициализация
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('/usr/local/ispmgr/lib/php/isp4private/core.php');
$isp = new PiminoffISP("i4p_core",true,3,1);
$p = $isp->getParams();
$form = new XMLData();
$form->addNode($form->root,"metadata");

# Кодинг
switch($isp->func){
	case "i4p.core.list":{
		$form->mode = "table";
		$form->addArray($form->root->metadata,$isp->getMetaData());
		$files = glob("/usr/local/ispmgr/etc/isp4private/i4p_*.ini", GLOB_BRACE);
		foreach($files as $file){
		    $pi = pathinfo($file);
		    $conf = new INIStorage($file,false);
		    $elem = $form->addNode($form->root,"elem");
		    $form->addNode($elem,"id",$pi['filename']);
    		    $form->addNode($elem,"plugin",$conf->get("Name","Plugin"));
    		    $form->addNode($elem,"version",$conf->get("Version","Plugin"));
    		    $form->addNode($elem,"v","Плагин активирован");
    		    if ($conf->get("Configurate","Plugin") == "true"){
        		    $form->addNode($elem,"configs","Плагин поддерживает настройку.");
    		    }	    
		}
		$form->PrintXML();
		break;
	}
	case "i4p.plugins.config":{
		$isp->logs->WriteLog(1,"Step 0x00101");
		$form->mode = "table";
		$form->addArray($form->root->metadata,$isp->getMetaData());
		if (!isset($p["elid"]) || $p["elid"] == ""){
			$isp->err->InternalError("Не указан плагин.");
		}
		$file = "/usr/local/ispmgr/etc/isp4private/".$p["elid"].".ini";
		if (!is_file($file)) $isp->err->InternalError("Не удалось найти файл конфигурации плагина.");
		$conf = new INIStorage($file,false);
		$arr = $conf->toArray(true);
		foreach($arr as $key => $value){
		    $elem = $form->addNode($form->root,"elem");
		    $form->addNode($elem,"id",$p["elid"]."::".$key);
    		$form->addNode($elem,"cat",$conf->def($isp->env->lang."_cat_".$key,"ru_cat_".$key,"Lang"));
    		$form->addNode($elem,"msg",$conf->def($isp->env->lang."_msg_".$key,"ru_msg_".$key,"Lang"));
    		$form->addNode($elem,"count",count($value));
		}
		$isp->logs->WriteLog(1,json_encode($p));
		$conf->save(1);
		$form->PrintXML();
		break;
	}
	case "i4p.core.test":{
		$isp->logs->WriteLog(1,"Step 0x0020");
		$form->mode = "form";
		$form->addArray($form->root->metadata,$isp->getMetaData());
		
		$form->addNode($form->root,"elid",$isp->env->distribution." ".$isp->env->mgr_version);
		$form->addNode($form->root,"version",$isp->env->version);
		$form->addNode($form->root,"login",$isp->env->login);
		$form->addNode($form->root,"authid",$isp->env->authid);
		$form->addNode($form->root,"licid",$isp->env->licid);
		$form->addNode($form->root,"mgr_panel",$isp->env->mgr_panel);
		$form->addNode($form->root,"mgr_version",$isp->env->mgr_version);
		$form->addNode($form->root,"distribution",$isp->env->distribution);
		$form->addNode($form->root,"osname",$isp->env->osname);
		$form->addNode($form->root,"recordlimit",$isp->env->recordlimit);
		$form->addNode($form->root,"access",$isp->env->access);
		$form->addNode($form->root,"lang",$isp->env->lang);
		$form->addNode($form->root,"server_name",$isp->env->server_name);
		$form->addNode($form->root,"port",$isp->env->port);
		$form->addNode($form->root,"author","ISP4Private");
		$form->addNode($form->root,"help","https://isp4private.ru/");
		//$form->addNode($form->root,"ok");
		$form->PrintXML();
		//$form->PrintXML();
		break;
	}
	case "i4p.core.ispmgr":{
		$isp->logs->WriteLog(1,json_encode($p));
		if (isset($p["sok"]) && $p["sok"] == "ok"){
			$isp->logs->WriteLog(1,"OK");
			if (isset($p["cache"])){
				$isp->logs->WriteLog(1,"CACHE");
				@`sleep 1 && rm -rf /usr/local/ispmgr/var/.xmlcache/ispmgr &`;
			}
			if (isset($p["logs"])){
				$isp->logs->WriteLog(1,"LOGS");
				$files = glob("/usr/local/ispmgr/etc/isp4private/logs/*.log", GLOB_BRACE);
				foreach($files as $file){
  					#$isp->logs->WriteLog(1,$file); 
					unlink($file);
				}
				$files = glob("/usr/local/ispmgr/var/*.log", GLOB_BRACE);
				foreach($files as $file){
					#$isp->logs->WriteLog(1,$file);
					unlink($file);
				}
				$isp->logs->WriteLog(1,"Logs has been deleted");
				@`sleep 1 && killall ispmgr &`;
			}
			$form->addNode($form->root,"ok","ok");
		}
		$form->mode = "form";
		$form->addArray($form->root->metadata,$isp->getMetaData());
		$form->addNode($form->root,"elid",$isp->env->distribution." ".$isp->env->mgr_version);
		$form->PrintXML();
	}
	case "i4p.plugins.update":{
		$form->mode = "form";
		$form->addArray($form->root->metadata,$isp->getMetaData());
		$form->addNode($form->root,"elid",$isp->env->distribution." ".$isp->env->mgr_version);
		$isp->logs->WriteLog(1,$form->PXML());
		$form->PrintXML();
	}
	case "i4p.plugins.config.open":{
		$isp->logs->WriteLog(1,"Step 0x00301");
		$form->mode = "table";
		$form->addArray($form->root->metadata,$isp->getMetaData());
		$ex = explode("::",$p["elid"],2);
		if (count($ex)!=2) $isp->err->InternalError("Не правильно переданы аргументы.");
		$file = "/usr/local/ispmgr/etc/isp4private/".$ex[0].".ini";
		if (!is_file($file)) $isp->err->InternalError("Не удалось найти файл конфигурации плагина.");
		$conf = new INIStorage($file,false);
		$arr = $conf->toArray(true);
		if (!isset($arr[$ex[1]])) $isp->err->InternalError("Категория не найдена.");
		foreach($arr[$ex[1]] as $key => $value){
		    $elem = $form->addNode($form->root,"elem");
		    $form->addNode($elem,"id",$p["elid"]."::".$key);
		    $form->addNode($elem,"key",$key);
		    $form->addNode($elem,"desc",($ex[1] == "Lang"?$isp->config->def($isp->env->lang."_SYSTEM","[SERVICE RECORD]","Lang"):$conf->def($isp->env->lang."_".$ex[1]."_".$key,$isp->env->lang."_".$ex[1]."_".$key,"Lang")));
		    $form->addNode($elem,"value",$value);
			#$isp->logs->WriteLog(1,json_encode([$key=>$value]));
		}
		$isp->logs->WriteLog(1,json_encode($p));
		$conf->save(1);
		$form->PrintXML();
		break;
	}
	case "i4p.plugins.config.edit":{
		$isp->logs->WriteLog(1,json_encode($p));
		$form->mode = "form";
		$form->addArray($form->root->metadata,$isp->getMetaData());
		if (isset($p["elid"])){
			$ex = explode("::",$p["elid"],3);
			if (count($ex)!=3) $isp->err->InternalError("Arguments passed incorrectly.");
			$file = "/usr/local/ispmgr/etc/isp4private/".$ex[0].".ini";
			if (!is_file($file)) $isp->err->InternalError("Could not find plugin configuration file.");
			$conf = new INIStorage($file,false);
			$form->addNode($form->root,"elid",$p["elid"]);
			$form->addNode($form->root,"key",$ex[2]);
			$form->addNode($form->root,"value",$conf->get($ex[2],$ex[1]));
			if (isset($p["sok"]) && $p["sok"] == "ok"){
				$conf->set($ex[2],$p["value"],$ex[1]);
				$conf->save(1);
				$form->addNode($form->root,"ok","ok");
			}
		}  else {
			$isp->err->InternalError("Creation is not currently supported.");
		}
		$isp->logs->WriteLog(1,json_encode($p));
		$form->PrintXML();
		break;
	}
	default:{
		$isp->err->DirectError("The function (".$isp->func.") is not detected or has not yet been implemented in this version of the plugin.");
	}
}
?>
