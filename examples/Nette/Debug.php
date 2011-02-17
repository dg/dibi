<?php
/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004, 2011 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
*
 * For more information please see http://nette.org
 * @package    Nette
 */

final
class
Debug{public
static$productionMode;public
static$consoleMode;public
static$time;private
static$firebugDetected;private
static$ajaxDetected;public
static$source;public
static$maxDepth=3;public
static$maxLen=150;public
static$showLocation=FALSE;const
DEVELOPMENT=FALSE,PRODUCTION=TRUE,DETECT=NULL;public
static$strictMode=FALSE;public
static$scream=FALSE;public
static$onFatalError=array();public
static$logDirectory;public
static$email;public
static$mailer=array(__CLASS__,'defaultMailer');public
static$emailSnooze=172800;public
static$editor='editor://open/?file=%file&line=%line';private
static$enabled=FALSE;private
static$lastError=FALSE;public
static$showBar=TRUE;private
static$panels=array();private
static$errors;const
DEBUG='debug',INFO='info',WARNING='warning',ERROR='error',CRITICAL='critical';final
public
function
__construct(){throw
new
LogicException("Cannot instantiate static class ".get_class($this));}public
static
function
_init(){self::$time=microtime(TRUE);self::$consoleMode=PHP_SAPI==='cli';self::$productionMode=self::DETECT;if(self::$consoleMode){self::$source=empty($_SERVER['argv'])?'cli':'cli: '.$_SERVER['argv'][0];}else{self::$firebugDetected=isset($_SERVER['HTTP_X_FIRELOGGER']);self::$ajaxDetected=isset($_SERVER['HTTP_X_REQUESTED_WITH'])&&$_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';if(isset($_SERVER['REQUEST_URI'])){self::$source=(isset($_SERVER['HTTPS'])&&strcasecmp($_SERVER['HTTPS'],'off')?'https://':'http://').(isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:(isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:'')).$_SERVER['REQUEST_URI'];}}$tab=array('DebugHelpers','renderTab');$panel=array('DebugHelpers','renderPanel');self::addPanel(new
DebugPanel('time',$tab,$panel));self::addPanel(new
DebugPanel('memory',$tab,$panel));self::addPanel($tmp=new
DebugPanel('errors',$tab,$panel));$tmp->data=&self::$errors;self::addPanel(new
DebugPanel('dumps',$tab,$panel));}public
static
function
enable($mode=NULL,$logDirectory=NULL,$email=NULL){error_reporting(E_ALL|E_STRICT);if(is_bool($mode)){self::$productionMode=$mode;}elseif(is_string($mode)){$mode=preg_split('#[,\s]+#',"$mode 127.0.0.1 ::1");}if(is_array($mode)){self::$productionMode=!isset($_SERVER['REMOTE_ADDR'])||!in_array($_SERVER['REMOTE_ADDR'],$mode,TRUE);}if(self::$productionMode===self::DETECT){if(class_exists('Environment')){self::$productionMode=Environment::isProduction();}elseif(isset($_SERVER['SERVER_ADDR'])||isset($_SERVER['LOCAL_ADDR'])){$addrs=array();if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){$addrs=preg_split('#,\s*#',$_SERVER['HTTP_X_FORWARDED_FOR']);}if(isset($_SERVER['REMOTE_ADDR'])){$addrs[]=$_SERVER['REMOTE_ADDR'];}$addrs[]=isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:$_SERVER['LOCAL_ADDR'];self::$productionMode=FALSE;foreach($addrs
as$addr){$oct=explode('.',$addr);if($addr!=='::1'&&(count($oct)!==4||($oct[0]!=='10'&&$oct[0]!=='127'&&($oct[0]!=='172'||$oct[1]<16||$oct[1]>31)&&($oct[0]!=='169'||$oct[1]!=='254')&&($oct[0]!=='192'||$oct[1]!=='168')))){self::$productionMode=TRUE;break;}}}else{self::$productionMode=!self::$consoleMode;}}if(is_string($logDirectory)){self::$logDirectory=realpath($logDirectory);if(self::$logDirectory===FALSE){throw
new
DirectoryNotFoundException("Directory '$logDirectory' is not found.");}}elseif($logDirectory===FALSE){self::$logDirectory=FALSE;}else{self::$logDirectory=defined('APP_DIR')?APP_DIR.'/../log':getcwd().'/log';}if(self::$logDirectory){ini_set('error_log',self::$logDirectory.'/php_error.log');}if(function_exists('ini_set')){ini_set('display_errors',!self::$productionMode);ini_set('html_errors',FALSE);ini_set('log_errors',FALSE);}elseif(ini_get('display_errors')!=!self::$productionMode&&ini_get('display_errors')!==(self::$productionMode?'stderr':'stdout')){throw
new
NotSupportedException('Function ini_set() must be enabled.');}if($email){if(!is_string($email)){throw
new
InvalidArgumentException('Email address must be a string.');}self::$email=$email;}if(!defined('E_DEPRECATED')){define('E_DEPRECATED',8192);}if(!defined('E_USER_DEPRECATED')){define('E_USER_DEPRECATED',16384);}if(!self::$enabled){register_shutdown_function(array(__CLASS__,'_shutdownHandler'));set_exception_handler(array(__CLASS__,'_exceptionHandler'));set_error_handler(array(__CLASS__,'_errorHandler'));self::$enabled=TRUE;}}public
static
function
isEnabled(){return
self::$enabled;}public
static
function
log($message,$priority=self::INFO){if(self::$logDirectory===FALSE){return;}elseif(!self::$logDirectory){throw
new
InvalidStateException('Logging directory is not specified in Debug::$logDirectory.');}elseif(!is_dir(self::$logDirectory)){throw
new
DirectoryNotFoundException("Directory '".self::$logDirectory."' is not found or is not directory.");}if($message
instanceof
Exception){$exception=$message;$message="PHP Fatal error: ".($message
instanceof
FatalErrorException?$exception->getMessage():"Uncaught exception ".get_class($exception)." with message '".$exception->getMessage()."'")." in ".$exception->getFile().":".$exception->getLine();}error_log(@date('[Y-m-d H-i-s] ').trim($message).(self::$source?'  @  '.self::$source:'').PHP_EOL,3,self::$logDirectory.'/'.strtolower($priority).'.log');if(($priority===self::ERROR||$priority===self::CRITICAL)&&self::$email&&@filemtime(self::$logDirectory.'/email-sent')+self::$emailSnooze<time()&&@file_put_contents(self::$logDirectory.'/email-sent','sent')){call_user_func(self::$mailer,$message);}if(isset($exception)){$hash=md5($exception.(method_exists($exception,'getPrevious')?$exception->getPrevious():(isset($exception->previous)?$exception->previous:'')));foreach(new
DirectoryIterator(self::$logDirectory)as$entry){if(strpos($entry,$hash)){$skip=TRUE;break;}}if(empty($skip)&&$logHandle=@fopen(self::$logDirectory."/exception ".@date('Y-m-d H-i-s')." $hash.html",'w')){ob_start();ob_start(create_function('$buffer','extract(NClosureFix::$vars['.NClosureFix::uses(array('logHandle'=>$logHandle)).'], EXTR_REFS); fwrite($logHandle, $buffer); '),1);DebugHelpers::renderBlueScreen($exception);ob_end_flush();ob_end_clean();fclose($logHandle);}}}public
static
function
_shutdownHandler(){static$types=array(E_ERROR=>1,E_CORE_ERROR=>1,E_COMPILE_ERROR=>1,E_PARSE=>1);$error=error_get_last();if(isset($types[$error['type']])){self::_exceptionHandler(new
FatalErrorException($error['message'],0,$error['type'],$error['file'],$error['line'],NULL));return;}if(self::$showBar&&!self::$productionMode&&!self::$ajaxDetected&&!self::$consoleMode&&(!preg_match('#^Content-Type: (?!text/html)#im',implode("\n",headers_list())))){DebugHelpers::renderDebugBar(self::$panels);}}public
static
function
_exceptionHandler(Exception$exception){if(!headers_sent()){header('HTTP/1.1 500 Internal Server Error');}$htmlMode=!self::$ajaxDetected&&!preg_match('#^Content-Type: (?!text/html)#im',implode("\n",headers_list()));try{if(self::$productionMode){self::log($exception,self::ERROR);if(self::$consoleMode){echo"ERROR: the server encountered an internal error and was unable to complete your request.\n";}elseif($htmlMode){echo"<!DOCTYPE html><meta http-equiv='Content-Type' content='text/html; charset=utf-8'><meta name=robots content=noindex><meta name=generator content='Nette Framework'>\n\n";echo"<style>body{color:#333;background:white;width:500px;margin:100px auto}h1{font:bold 47px/1.5 sans-serif;margin:.6em 0}p{font:21px/1.5 Georgia,serif;margin:1.5em 0}small{font-size:70%;color:gray}</style>\n\n";echo"<title>Server Error</title>\n\n<h1>Server Error</h1>\n\n<p>We're sorry! The server encountered an internal error and was unable to complete your request. Please try again later.</p>\n\n<p><small>error 500</small></p>";}}else{if(self::$consoleMode){echo"$exception\n";}elseif($htmlMode){DebugHelpers::renderBlueScreen($exception);}elseif(!self::fireLog($exception,self::ERROR)){self::log($exception);}}foreach(self::$onFatalError
as$handler){call_user_func($handler,$exception);}}catch(Exception$e){echo"\nNette\\Debug FATAL ERROR: thrown ",get_class($e),': ',$e->getMessage(),"\nwhile processing ",get_class($exception),': ',$exception->getMessage(),"\n";exit;}}public
static
function
_errorHandler($severity,$message,$file,$line,$context){if(self::$lastError!==FALSE){self::$lastError=new
ErrorException($message,0,$severity,$file,$line);return
NULL;}if(self::$scream){error_reporting(E_ALL|E_STRICT);}if($severity===E_RECOVERABLE_ERROR||$severity===E_USER_ERROR){throw
new
FatalErrorException($message,0,$severity,$file,$line,$context);}elseif(($severity&error_reporting())!==$severity){return
FALSE;}elseif(self::$strictMode&&!self::$productionMode){self::_exceptionHandler(new
FatalErrorException($message,0,$severity,$file,$line,$context));exit;}static$types=array(E_WARNING=>'Warning',E_COMPILE_WARNING=>'Warning',E_USER_WARNING=>'Warning',E_NOTICE=>'Notice',E_USER_NOTICE=>'Notice',E_STRICT=>'Strict standards',E_DEPRECATED=>'Deprecated',E_USER_DEPRECATED=>'Deprecated');$message='PHP '.(isset($types[$severity])?$types[$severity]:'Unknown error').": $message";$count=&self::$errors["$message|$file|$line"];if($count++){return
NULL;}elseif(self::$productionMode){self::log("$message in $file:$line",self::ERROR);return
NULL;}else{$ok=self::fireLog(new
ErrorException($message,0,$severity,$file,$line),self::WARNING);return
self::$consoleMode||(!self::$showBar&&!$ok)?FALSE:NULL;}return
FALSE;}public
static
function
processException(Exception$exception){trigger_error(__METHOD__.'() is deprecated; use '.__CLASS__.'::log($exception, Debug::ERROR) instead.',E_USER_WARNING);self::log($exception,self::ERROR);}public
static
function
toStringException(Exception$exception){if(self::$enabled){self::_exceptionHandler($exception);}else{trigger_error($exception->getMessage(),E_USER_ERROR);}exit;}public
static
function
tryError(){if(!self::$enabled&&self::$lastError===FALSE){set_error_handler(array(__CLASS__,'_errorHandler'));}self::$lastError=NULL;}public
static
function
catchError(&$error){if(!self::$enabled&&self::$lastError!==FALSE){restore_error_handler();}$error=self::$lastError;self::$lastError=FALSE;return(bool)$error;}private
static
function
defaultMailer($message){$host=isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:(isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:'');$parts=str_replace(array("\r\n","\n"),array("\n",PHP_EOL),array('headers'=>"From: noreply@$host\nX-Mailer: Nette Framework\n",'subject'=>"PHP: An error occurred on the server $host",'body'=>"[".@date('Y-m-d H:i:s')."] $message"));mail(self::$email,$parts['subject'],$parts['body'],$parts['headers']);}public
static
function
dump($var,$return=FALSE){if(!$return&&self::$productionMode){return$var;}$output="<pre class=\"nette-dump\">".DebugHelpers::htmlDump($var)."</pre>\n";if(!$return&&self::$showLocation){$trace=debug_backtrace();$i=isset($trace[1]['class'])&&$trace[1]['class']===__CLASS__?1:0;if(isset($trace[$i]['file'],$trace[$i]['line'])){$output=substr_replace($output,' <small>'.htmlspecialchars("in file {$trace[$i]['file']} on line {$trace[$i]['line']}",ENT_NOQUOTES).'</small>',-8,0);}}if(self::$consoleMode){$output=htmlspecialchars_decode(strip_tags($output),ENT_NOQUOTES);}if($return){return$output;}else{echo$output;return$var;}}public
static
function
timer($name=NULL){static$time=array();$now=microtime(TRUE);$delta=isset($time[$name])?$now-$time[$name]:0;$time[$name]=$now;return$delta;}public
static
function
addPanel(IDebugPanel$panel){self::$panels[]=$panel;}public
static
function
barDump($var,$title=NULL){if(!self::$productionMode){$dump=array();foreach((is_array($var)?$var:array(''=>$var))as$key=>$val){$dump[$key]=DebugHelpers::clickableDump($val);}self::$panels[3]->data[]=array('title'=>$title,'dump'=>$dump);}return$var;}public
static
function
fireLog($message){if(self::$productionMode){return;}elseif(!self::$firebugDetected||headers_sent()){return
FALSE;}static$payload=array('logs'=>array());$item=array('name'=>'PHP','level'=>'debug','order'=>count($payload['logs']),'time'=>str_pad(number_format((microtime(TRUE)-self::$time)*1000,1,'.',' '),8,'0',STR_PAD_LEFT).' ms','template'=>'','message'=>'','style'=>'background:#767ab6');$args=func_get_args();if(isset($args[0])&&is_string($args[0])){$item['template']=array_shift($args);}if(isset($args[0])&&$args[0]instanceof
Exception){$e=array_shift($args);$trace=$e->getTrace();if(isset($trace[0]['class'])&&$trace[0]['class']===__CLASS__&&($trace[0]['function']==='_shutdownHandler'||$trace[0]['function']==='_errorHandler')){unset($trace[0]);}$item['exc_info']=array($e->getMessage(),$e->getFile(),array());$item['exc_frames']=array();foreach($trace
as$frame){$frame+=array('file'=>NULL,'line'=>NULL,'class'=>NULL,'type'=>NULL,'function'=>NULL,'object'=>NULL,'args'=>NULL);$item['exc_info'][2][]=array($frame['file'],$frame['line'],"$frame[class]$frame[type]$frame[function]",$frame['object']);$item['exc_frames'][]=$frame['args'];}$file=str_replace(dirname(dirname(dirname($e->getFile()))),"\xE2\x80\xA6",$e->getFile());$item['template']=($e
instanceof
ErrorException?'':get_class($e).': ').$e->getMessage().($e->getCode()?' #'.$e->getCode():'').' in '.$file.':'.$e->getLine();array_unshift($trace,array('file'=>$e->getFile(),'line'=>$e->getLine()));}else{$trace=debug_backtrace();if(isset($trace[0]['class'])&&$trace[0]['class']===__CLASS__&&($trace[0]['function']==='_shutdownHandler'||$trace[0]['function']==='_errorHandler')){unset($trace[0]);}}if(isset($args[0])&&in_array($args[0],array(self::DEBUG,self::INFO,self::WARNING,self::ERROR,self::CRITICAL),TRUE)){$item['level']=array_shift($args);}$item['args']=$args;foreach($trace
as$frame){if(isset($frame['file'])&&is_file($frame['file'])){$item['pathname']=$frame['file'];$item['lineno']=$frame['line'];break;}}$payload['logs'][]=DebugHelpers::jsonDump($item,-1);foreach(str_split(base64_encode(@json_encode($payload)),4990)as$k=>$v){header("FireLogger-de11e-$k:$v");}return
TRUE;}}class
FatalErrorException
extends
Exception{private$severity;public
function
__construct($message,$code,$severity,$file,$line,$context){parent::__construct($message,$code);$this->severity=$severity;$this->file=$file;$this->line=$line;$this->context=$context;}public
function
getSeverity(){return$this->severity;}}interface
IDebugPanel{function
getTab();function
getPanel();function
getId();}class
DebugPanel
implements
IDebugPanel{private$id;private$tabCb;private$panelCb;public$data;public
function
__construct($id,$tabCb,$panelCb){$this->id=$id;$this->tabCb=$tabCb;$this->panelCb=$panelCb;}public
function
getId(){return$this->id;}public
function
getTab(){ob_start();call_user_func($this->tabCb,$this->id,$this->data);return
ob_get_clean();}public
function
getPanel(){ob_start();call_user_func($this->panelCb,$this->id,$this->data);return
ob_get_clean();}}final
class
DebugHelpers{public
static
function
renderBlueScreen(Exception$exception){if(class_exists('Environment',FALSE)){$application=Environment::getContext()->hasService('Nette\\Application\\Application',TRUE)?Environment::getContext()->getService('Nette\\Application\\Application'):NULL;}static$errorTypes=array(E_ERROR=>'Fatal Error',E_USER_ERROR=>'User Error',E_RECOVERABLE_ERROR=>'Recoverable Error',E_CORE_ERROR=>'Core Error',E_COMPILE_ERROR=>'Compile Error',E_PARSE=>'Parse Error',E_WARNING=>'Warning',E_CORE_WARNING=>'Core Warning',E_COMPILE_WARNING=>'Compile Warning',E_USER_WARNING=>'User Warning',E_NOTICE=>'Notice',E_USER_NOTICE=>'User Notice',E_STRICT=>'Strict',E_DEPRECATED=>'Deprecated',E_USER_DEPRECATED=>'User Deprecated');$title=($exception
instanceof
FatalErrorException&&isset($errorTypes[$exception->getSeverity()]))?$errorTypes[$exception->getSeverity()]:get_class($exception);$expandPath=NETTE_DIR.DIRECTORY_SEPARATOR;$counter=0;if(headers_sent()){echo'</pre></xmp></table>';}?><!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="robots" content="noindex,noarchive">
	<meta name="generator" content="Nette Framework">

	<title><?php echo
htmlspecialchars($title)?></title><!-- <?php
$ex=$exception;echo$ex->getMessage(),($ex->getCode()?' #'.$ex->getCode():'');while((method_exists($ex,'getPrevious')&&$ex=$ex->getPrevious())||(isset($ex->previous)&&$ex=$ex->previous))echo'; caused by ',get_class($ex),' ',$ex->getMessage(),($ex->getCode()?' #'.$ex->getCode():'');?> -->

	<style type="text/css" class="nette">html{overflow-y:scroll}body{margin:0 0 2em;padding:0}#netteBluescreen{font:9pt/1.5 Verdana,sans-serif;background:white;color:#333;position:absolute;left:0;top:0;width:100%;z-index:23178;text-align:left}#netteBluescreen *{font:inherit;color:inherit;background:transparent;border:none;margin:0;padding:0;text-align:inherit;text-indent:0}#netteBluescreen b{font-weight:bold}#netteBluescreen i{font-style:italic}#netteBluescreen a{text-decoration:none;color:#328ADC;padding:2px 4px;margin:-2px -4px}#netteBluescreen a:hover,#netteBluescreen a:active,#netteBluescreen a:focus{color:#085AA3}#netteBluescreen a abbr{font-family:sans-serif;color:#BBB}#netteBluescreenIcon{position:absolute;right:.5em;top:.5em;z-index:23179;text-decoration:none;background:#CD1818;padding:3px}#netteBluescreenError{background:#CD1818;color:white;font:13pt/1.5 Verdana,sans-serif!important;display:block}#netteBluescreenError #netteBsSearch{color:#CD1818;font-size:.7em}#netteBluescreenError:hover #netteBsSearch{color:#ED8383}#netteBluescreen h1{font-size:18pt;font-weight:normal;text-shadow:1px 1px 0 rgba(0,0,0,.4);margin:.7em 0}#netteBluescreen h2{font:14pt/1.5 sans-serif!important;color:#888;margin:.6em 0}#netteBluescreen h3{font:bold 10pt/1.5 Verdana,sans-serif!important;margin:1em 0;padding:0}#netteBluescreen p,#netteBluescreen pre{margin:.8em 0}#netteBluescreen pre,#netteBluescreen code,#netteBluescreen table{font:9pt/1.5 Consolas,monospace!important}#netteBluescreen pre,#netteBluescreen table{background:#FDF5CE;padding:.4em .7em;border:1px dotted silver;overflow:auto}#netteBluescreen table pre{padding:0;margin:0;border:none}#netteBluescreen pre.nette-dump span{color:#C22}#netteBluescreen pre.nette-dump a{color:#333}#netteBluescreen div.panel{padding:1px 25px}#netteBluescreen div.inner{background:#F4F3F1;padding:.1em 1em 1em;border-radius:8px;-moz-border-radius:8px;-webkit-border-radius:8px}#netteBluescreen table{border-collapse:collapse;width:100%}#netteBluescreen .outer{overflow:auto}#netteBluescreen td,#netteBluescreen th{vertical-align:top;text-align:left;padding:2px 6px;border:1px solid #e6dfbf}#netteBluescreen th{width:10%;font-weight:bold}#netteBluescreen tr:nth-child(2n),#netteBluescreen tr:nth-child(2n) pre{background-color:#F7F0CB}#netteBluescreen ol{margin:1em 0;padding-left:2.5em}#netteBluescreen ul{font:7pt/1.5 Verdana,sans-serif!important;padding:2em 4em;margin:1em 0 0;color:#777;background:#F6F5F3 url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFEAAAAjCAMAAADbuxbOAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAADBQTFRF/fz24d7Y7Onj5uLd9vPu3drUzMvG09LN39zW8e7o2NbQ3NnT29jS0M7J1tXQAAAApvmsFgAAABB0Uk5T////////////////////AOAjXRkAAAKlSURBVHja7FbbsqQgDAwENEgc//9vN+SCWDtbtXPmZR/Wc6o02mlC58LA9ckFAOszvMV8xNgyUjyXhojfMVKvRL0ZHavxXYy5JrmchMdzou8YlTClxajtK8ZGGpWRoBr1+gFjKfHkJPbizabLgzE3pH7Iu4K980xgFvlrVzMZoVBWhtvouCDdcTDmTgMCJdVxJ9MKO6XxnliM7hxi5lbj2ZVM4l8DqYyKoNLYcfqBB1/LpHYxEcfVG6ZpMDgyFUVWY/Q1sSYPpIdSAKWqLWL0XqWiMWc4hpH0OQOMOAgdycY4N9Sb7wWANQs3rsDSdLAYiuxi5siVfOhBWIrtH0G3kNaF/8Q4kCPE1kMucG/ZMUBUCOgiKJkPuWWTLGVgLGpwns1DraUayCtoBqERyaYtVsm85NActRooezvSLO/sKZP/nq8n4+xcyjNsRu8zW6KWpdb7wjiQd4WrtFZYFiKHENSmWp6xshh96c2RQ+c7Lt+qbijyEjHWUJ/pZsy8MGIUuzNiPySK2Gqoh6ZTRF6ko6q3nVTkaA//itIrDpW6l3SLo8juOmqMXkYknu5FdQxWbhCfKHEGDhxxyTVaXJF3ZjSl3jMksjSOOKmne9pI+mcG5QvaUJhI9HpkmRo2NpCrDJvsktRhRE2MM6F2n7dt4OaMUq8bCctk0+PoMRzL+1l5PZ2eyM/Owr86gf8z/tOM53lom5+nVcFuB+eJVzlXwAYy9TZ9s537tfqcsJWbEU4nBngZo6FfO9T9CdhfBtmk2dLiAy8uS4zwOpMx2HqYbTC+amNeAYTpsP4SIgvWfUBWXxn3CMHW3ffd7k3+YIkx7w0t/CVGvcPejoeOlzOWzeGbawOHqXQGUTMZRcfj4XPCgW9y/fuvVn8zD9P1QHzv80uAAQA0i3Jer7Jr7gAAAABJRU5ErkJggg==') 99% 10px no-repeat;border-top:1px solid #DDD}#netteBluescreen .highlight{background:#CD1818;color:white;font-weight:bold;font-style:normal;display:block;padding:0 .4em;margin:0 -.4em}#netteBluescreen .line{color:#9F9C7F;font-weight:normal;font-style:normal}#netteBluescreen a[href^=editor\:]{color:inherit;border-bottom:1px dotted #C1D2E1}</style>
</head>



<body>
<div id="netteBluescreen">
	<a id="netteBluescreenIcon" href="#" rel="next"><abbr>&#x25bc;</abbr></a

	><div>
		<div id="netteBluescreenError" class="panel">
			<h1><?php echo
htmlspecialchars($title),($exception->getCode()?' #'.$exception->getCode():'')?></h1>

			<p><?php echo
htmlspecialchars($exception->getMessage())?> <a href="http://www.google.cz/search?sourceid=nette&amp;q=<?php echo
urlencode($title.' '.preg_replace('#\'.*\'|".*"#Us','',$exception->getMessage()))?>" id="netteBsSearch">search&#x25ba;</a></p>
		</div>



		<?php $ex=$exception;$level=0;?>
		<?php do{?>

			<?php if($level++):?>
			<div class="panel">
			<h2><a href="#" rel="netteBsPnl<?php echo++$counter?>">Caused by <abbr><?php echo($collapsed=$level>2)?'&#x25ba;':'&#x25bc;'?></abbr></a></h2>

			<div id="netteBsPnl<?php echo$counter?>" class="<?php echo$collapsed?'nette-collapsed ':''?>inner">
				<div class="panel">
					<h1><?php echo
htmlspecialchars(get_class($ex)),($ex->getCode()?' #'.$ex->getCode():'')?></h1>

					<p><b><?php echo
htmlspecialchars($ex->getMessage())?></b></p>
				</div>
			<?php endif?>



			<?php if($ex
instanceof
IDebugPanel&&($tab=$ex->getTab())&&($panel=$ex->getPanel())):?>
			<div class="panel">
				<h2><a href="#" rel="netteBsPnl<?php echo++$counter?>"><?php echo
htmlSpecialChars($tab)?> <abbr>&#x25bc;</abbr></a></h2>

				<div id="netteBsPnl<?php echo$counter?>" class="inner">
				<?php echo$panel?>
			</div></div>
			<?php endif?>



			<?php $stack=$ex->getTrace();$expanded=NULL?>
			<?php if(strpos($ex->getFile(),$expandPath)===0){foreach($stack
as$key=>$row){if(isset($row['file'])&&strpos($row['file'],$expandPath)!==0){$expanded=$key;break;}}}?>
			<?php if(is_file($ex->getFile())):?>
			<div class="panel">
			<h2><a href="#" rel="netteBsPnl<?php echo++$counter?>">Source file <abbr><?php echo($collapsed=$expanded!==NULL)?'&#x25ba;':'&#x25bc;'?></abbr></a></h2>

			<div id="netteBsPnl<?php echo$counter?>" class="<?php echo$collapsed?'nette-collapsed ':''?>inner">
				<p><b>File:</b> <?php if(Debug::$editor)echo'<a href="',htmlspecialchars(DebugHelpers::editorLink($ex->getFile(),$ex->getLine())),'">'?>
				<?php echo
htmlspecialchars($ex->getFile()),(Debug::$editor?'</a>':'')?> &nbsp; <b>Line:</b> <?php echo$ex->getLine()?></p>
				<pre><?php echo
DebugHelpers::highlightFile($ex->getFile(),$ex->getLine())?></pre>
			</div></div>
			<?php endif?>



			<?php if(isset($stack[0]['class'])&&$stack[0]['class']==='Debug'&&($stack[0]['function']==='_shutdownHandler'||$stack[0]['function']==='_errorHandler'))unset($stack[0])?>
			<?php if($stack):?>
			<div class="panel">
				<h2><a href="#" rel="netteBsPnl<?php echo++$counter?>">Call stack <abbr>&#x25bc;</abbr></a></h2>

				<div id="netteBsPnl<?php echo$counter?>" class="inner">
				<ol>
					<?php foreach($stack
as$key=>$row):?>
					<li><p>

					<?php if(isset($row['file'])&&is_file($row['file'])):?>
						<?php echo
Debug::$editor?'<a href="'.htmlspecialchars(DebugHelpers::editorLink($row['file'],$row['line'])).'"':'<span';?> title="<?php echo
htmlSpecialChars($row['file'])?>">
						<?php echo
htmlSpecialChars(basename(dirname($row['file']))),'/<b>',htmlSpecialChars(basename($row['file'])),'</b>',(Debug::$editor?'</a>':'</span>'),' (',$row['line'],')'?>
					<?php else:?>
						<i>inner-code</i><?php if(isset($row['line']))echo' (',$row['line'],')'?>
					<?php endif?>

					<?php if(isset($row['file'])&&is_file($row['file'])):?><a href="#" rel="netteBsSrc<?php echo"$level-$key"?>">source <abbr>&#x25ba;</abbr></a>&nbsp; <?php endif?>

					<?php if(isset($row['class']))echo$row['class'].$row['type']?>
					<?php echo$row['function']?>

					(<?php if(!empty($row['args'])):?><a href="#" rel="netteBsArgs<?php echo"$level-$key"?>">arguments <abbr>&#x25ba;</abbr></a><?php endif?>)
					</p>

					<?php if(!empty($row['args'])):?>
						<div class="nette-collapsed outer" id="netteBsArgs<?php echo"$level-$key"?>">
						<table>
						<?php

try{$r=isset($row['class'])?new
ReflectionMethod($row['class'],$row['function']):new
ReflectionFunction($row['function']);$params=$r->getParameters();}catch(Exception$e){$params=array();}foreach($row['args']as$k=>$v){echo'<tr><th>',(isset($params[$k])?'$'.$params[$k]->name:"#$k"),'</th><td>';echo
DebugHelpers::clickableDump($v);echo"</td></tr>\n";}?>
						</table>
						</div>
					<?php endif?>


					<?php if(isset($row['file'])&&is_file($row['file'])):?>
						<pre <?php if($expanded!==$key)echo'class="nette-collapsed"';?> id="netteBsSrc<?php echo"$level-$key"?>"><?php echo
DebugHelpers::highlightFile($row['file'],$row['line'])?></pre>
					<?php endif?>

					</li>
					<?php endforeach?>
				</ol>
			</div></div>
			<?php endif?>



			<?php if(isset($ex->context)&&is_array($ex->context)):?>
			<div class="panel">
			<h2><a href="#" rel="netteBsPnl<?php echo++$counter?>">Variables <abbr>&#x25ba;</abbr></a></h2>

			<div id="netteBsPnl<?php echo$counter?>" class="nette-collapsed inner">
			<div class="outer">
			<table>
			<?php

foreach($ex->context
as$k=>$v){echo'<tr><th>$',htmlspecialchars($k),'</th><td>',DebugHelpers::clickableDump($v),"</td></tr>\n";}?>
			</table>
			</div>
			</div></div>
			<?php endif?>

		<?php }while((method_exists($ex,'getPrevious')&&$ex=$ex->getPrevious())||(isset($ex->previous)&&$ex=$ex->previous));?>
		<?php while(--$level)echo'</div></div>'?>



		<?php if(!empty($application)):?>
		<div class="panel">
		<h2><a href="#" rel="netteBsPnl<?php echo++$counter?>">Nette Application <abbr>&#x25ba;</abbr></a></h2>

		<div id="netteBsPnl<?php echo$counter?>" class="nette-collapsed inner">
			<h3>Requests</h3>
			<?php echo
DebugHelpers::clickableDump($application->getRequests())?>

			<h3>Presenter</h3>
			<?php echo
DebugHelpers::clickableDump($application->getPresenter())?>
		</div></div>
		<?php endif?>



		<div class="panel">
		<h2><a href="#" rel="netteBsPnl<?php echo++$counter?>">Environment <abbr>&#x25ba;</abbr></a></h2>

		<div id="netteBsPnl<?php echo$counter?>" class="nette-collapsed inner">
			<?php
$list=get_defined_constants(TRUE);if(!empty($list['user'])):?>
			<h3><a href="#" rel="netteBsPnl-env-const">Constants <abbr>&#x25bc;</abbr></a></h3>
			<div class="outer">
			<table id="netteBsPnl-env-const">
			<?php

foreach($list['user']as$k=>$v){echo'<tr><th>',htmlspecialchars($k),'</th>';echo'<td>',DebugHelpers::clickableDump($v),"</td></tr>\n";}?>
			</table>
			</div>
			<?php endif?>


			<h3><a href="#" rel="netteBsPnl-env-files">Included files <abbr>&#x25ba;</abbr></a> (<?php echo
count(get_included_files())?>)</h3>
			<div class="outer">
			<table id="netteBsPnl-env-files" class="nette-collapsed">
			<?php

foreach(get_included_files()as$v){echo'<tr><td>',htmlspecialchars($v),"</td></tr>\n";}?>
			</table>
			</div>


			<h3>$_SERVER</h3>
			<?php if(empty($_SERVER)):?>
			<p><i>empty</i></p>
			<?php else:?>
			<div class="outer">
			<table>
			<?php

foreach($_SERVER
as$k=>$v)echo'<tr><th>',htmlspecialchars($k),'</th><td>',DebugHelpers::clickableDump($v),"</td></tr>\n";?>
			</table>
			</div>
			<?php endif?>
		</div></div>



		<div class="panel">
		<h2><a href="#" rel="netteBsPnl<?php echo++$counter?>">HTTP request <abbr>&#x25ba;</abbr></a></h2>

		<div id="netteBsPnl<?php echo$counter?>" class="nette-collapsed inner">
			<?php if(function_exists('apache_request_headers')):?>
			<h3>Headers</h3>
			<div class="outer">
			<table>
			<?php

foreach(apache_request_headers()as$k=>$v)echo'<tr><th>',htmlspecialchars($k),'</th><td>',htmlspecialchars($v),"</td></tr>\n";?>
			</table>
			</div>
			<?php endif?>


			<?php foreach(array('_GET','_POST','_COOKIE')as$name):?>
			<h3>$<?php echo$name?></h3>
			<?php if(empty($GLOBALS[$name])):?>
			<p><i>empty</i></p>
			<?php else:?>
			<div class="outer">
			<table>
			<?php

foreach($GLOBALS[$name]as$k=>$v)echo'<tr><th>',htmlspecialchars($k),'</th><td>',DebugHelpers::clickableDump($v),"</td></tr>\n";?>
			</table>
			</div>
			<?php endif?>
			<?php endforeach?>
		</div></div>



		<div class="panel">
		<h2><a href="#" rel="netteBsPnl<?php echo++$counter?>">HTTP response <abbr>&#x25ba;</abbr></a></h2>

		<div id="netteBsPnl<?php echo$counter?>" class="nette-collapsed inner">
			<h3>Headers</h3>
			<?php if(headers_list()):?>
			<pre><?php

foreach(headers_list()as$s)echo
htmlspecialchars($s),'<br>';?></pre>
			<?php else:?>
			<p><i>no headers</i></p>
			<?php endif?>
		</div></div>


		<ul>
			<li>Report generated at <?php echo@date('Y/m/d H:i:s',Debug::$time)?></li>
			<?php if(preg_match('#^https?://#',Debug::$source)):?>
				<li><a href="<?php echo
htmlSpecialChars(Debug::$source)?>"><?php echo
htmlSpecialChars(Debug::$source)?></a></li>
			<?php elseif(Debug::$source):?>
				<li><?php echo
htmlSpecialChars(Debug::$source)?></li>
			<?php endif?>
			<li>PHP <?php echo
htmlSpecialChars(PHP_VERSION)?></li>
			<?php if(isset($_SERVER['SERVER_SOFTWARE'])):?><li><?php echo
htmlSpecialChars($_SERVER['SERVER_SOFTWARE'])?></li><?php endif?>
			<?php if(class_exists('Framework')):?><li><?php echo
htmlSpecialChars('Nette Framework '.Framework::VERSION)?> <i>(revision <?php echo
htmlSpecialChars(Framework::REVISION)?>)</i></li><?php endif?>
		</ul>
	</div>
</div>

<script type="text/javascript">/*<![CDATA[*/var bs=document.getElementById("netteBluescreen");document.body.appendChild(bs);document.onkeyup=function(b){b=b||window.event;b.keyCode==27&&!b.shiftKey&&!b.altKey&&!b.ctrlKey&&!b.metaKey&&bs.onclick({target:document.getElementById("netteBluescreenIcon")})};
for(var i=0,styles=document.styleSheets;i<styles.length;i++)if((styles[i].owningElement||styles[i].ownerNode).className!=="nette"){styles[i].oldDisabled=styles[i].disabled;styles[i].disabled=true}else styles[i].addRule?styles[i].addRule(".nette-collapsed","display: none"):styles[i].insertRule(".nette-collapsed { display: none }",0);
bs.onclick=function(b){b=b||window.event;for(var a=b.target||b.srcElement;a&&a.tagName&&a.tagName.toLowerCase()!=="a";)a=a.parentNode;if(!a||!a.rel)return true;for(var d=a.getElementsByTagName("abbr")[0],c=a.rel==="next"?a.nextSibling:document.getElementById(a.rel);c.nodeType!==1;)c=c.nextSibling;b=c.currentStyle?c.currentStyle.display=="none":getComputedStyle(c,null).display=="none";try{d.innerHTML=String.fromCharCode(b?9660:9658)}catch(e){}c.style.display=b?c.tagName.toLowerCase()==="code"?"inline":
"block":"none";if(a.id==="netteBluescreenIcon"){a=0;for(d=document.styleSheets;a<d.length;a++)if((d[a].owningElement||d[a].ownerNode).className!=="nette")d[a].disabled=b?true:d[a].oldDisabled}return false};/*]]>*/</script>
</body>
</html><?php }public
static
function
renderDebugBar($panels){foreach($panels
as$key=>$panel){$panels[$key]=array('id'=>preg_replace('#[^a-z0-9]+#i','-',$panel->getId()),'tab'=>$tab=(string)$panel->getTab(),'panel'=>$tab?(string)$panel->getPanel():NULL);}?>



<!-- Nette Debug Bar -->

<?php ob_start()?>
&nbsp;

<style id="nette-debug-style" class="nette">#nette-debug{display:none}body#nette-debug{margin:5px 5px 0;display:block}#nette-debug *{font:inherit;color:inherit;background:transparent;margin:0;padding:0;border:none;text-align:inherit;list-style:inherit}#nette-debug .nette-fixed-coords{position:fixed;_position:absolute;right:0;bottom:0}#nette-debug a{color:#125EAE;text-decoration:none}#nette-debug .nette-panel a{color:#125EAE;text-decoration:none}#nette-debug a:hover,#nette-debug a:active,#nette-debug a:focus{background-color:#125EAE;color:white}#nette-debug .nette-panel h2,#nette-debug .nette-panel h3,#nette-debug .nette-panel p{margin:.4em 0}#nette-debug .nette-panel table{border-collapse:collapse;background:#FDF5CE}#nette-debug .nette-panel tr:nth-child(2n) td{background:#F7F0CB}#nette-debug .nette-panel td,#nette-debug .nette-panel th{border:1px solid #E6DFBF;padding:2px 5px;vertical-align:top;text-align:left}#nette-debug .nette-panel th{background:#F4F3F1;color:#655E5E;font-size:90%;font-weight:bold}#nette-debug .nette-panel pre,#nette-debug .nette-panel code{font:9pt/1.5 Consolas,monospace}#nette-debug table .nette-right{text-align:right}.nette-hidden,.nette-collapsed{display:none}#nette-debug-bar{font:normal normal 12px/21px Tahoma,sans-serif;color:#333;border:1px solid #c9c9c9;background:#EDEAE0 url('data:image/png;base64,R0lGODlhAQAVALMAAOTh1/Px6eHe1fHv5e/s4vLw6Ofk2u3q4PPw6PPx6PDt5PLw5+Dd1OXi2Ojm3Orn3iH5BAAAAAAALAAAAAABABUAAAQPMISEyhpYkfOcaQAgCEwEADs=') repeat-x bottom;position:relative;height:1.75em;min-height:21px;_float:left;min-width:50px;white-space:nowrap;z-index:23181;opacity:.9;border-radius:3px;-moz-border-radius:3px;box-shadow:1px 1px 10px rgba(0,0,0,.15);-moz-box-shadow:1px 1px 10px rgba(0,0,0,.15);-webkit-box-shadow:1px 1px 10px rgba(0,0,0,.15)}#nette-debug-bar:hover{opacity:1}#nette-debug-bar ul{list-style:none none;margin-left:4px}#nette-debug-bar li{float:left}#nette-debug-bar img{vertical-align:middle;position:relative;top:-1px;margin-right:3px}#nette-debug-bar li a{color:#000;display:block;padding:0 4px}#nette-debug-bar li a:hover{color:black;background:#c3c1b8}#nette-debug-bar li .nette-warning{color:#D32B2B;font-weight:bold}#nette-debug-bar li>span{padding:0 4px}#nette-debug-logo{background:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAC0AAAAPCAYAAABwfkanAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABiFJREFUSMe1VglPlGcQ5i+1xjZNqxREtGq8ahCPWsVGvEDBA1BBRQFBDjkE5BYUzwpovRBUREBEBbl3OVaWPfj2vi82eTrvbFHamLRJ4yYTvm+u95mZZ96PoKAv+LOatXBYZ+Bx6uFy6DGnt1m0EOKwSmQzwmHTgX5B/1W+yM9GYJ02CX6/B/5ZF+w2A4x6FYGTYDVp4PdY2Tbrs5N+mnRa2Km4/wV6rhPzQQj5fDc1mJM5nd0iYdZtQWtrCxobGnDpUiledTynbuvg99mgUMhw924Trl2rR01NNSTNJE9iDpTV8innv4K2kZPLroPXbYLHZeSu2K1aeF0muJ2GvwGzmNSwU2E+svm8ZrgdBliMaha/34Vx+RAKCgpwpa4OdbW1UE/L2cc/68WtWzdRVlaG6uoqtD1/BA/pA1MIxLvtes7pc5vhoDOE/rOgbVSdf9aJWa8dBp0Kyg+jdLiTx2vQKWEyqGmcNkqg4iTC1+dzQatWkK+cJqPD7KyFaKEjvRuNjY24fLkGdXW1ePjwAeX4QHonDNI0A75+/RpqqqshH+6F2UAUMaupYXouykV0mp6SQ60coxgL8Z4aMg/4x675/V60v3jKB+Xl5WJibIC4KPEIS0qKqWv5GOh7BZ/HSIk9kA33o7y8DOfPZ6GQOipkXDZAHXKxr4ipqqpkKS6+iIrycgz2dyMnJxtVlZUsotNZWZmor79KBbvgpdjm5sfIzc1hv4L8fKJPDTfJZZc+gRYKr8sAEy2DcBRdEEk62ltx9uwZ5qNILoDU1l6mbrvx5EkzUlKSuTiR7PHjR3x4fv4FyIbeIic7G5WVFUyN+qtX+Lnt2SPcvn2LfURjhF7kE4WPDr+Bx+NEUVEhkpNPoImm5CSOl5aUIC3tLOMR59gtAY4HidGIzj14cB8ZGRkM8kJeHk6cOI4xWR8vSl5uLlJTT6O74xnT5lB8PM6cSYXVqILb5UBWZiYSExMYkE4zzjqX00QHG+h9AjPqMei0k3ywy2khMdNiq6BVCf04T6ekuBgJCUdRUVHOBQwPvkNSUiLjaGi4Q/5qFgYtHgTXRJdTT59GenoaA5gY64deq0Bc3EGuNj4+DnppEheLijhZRkY6SktLsGPHdi6irOwSFTRAgO04deokTSIFsbExuHfvLnFSx8DevelAfFwcA0lJTqZi5PDS9aci/sbE7Oe4wsICbtD27b/ye1NTI3FeSX4W2gdFALRD3A4eM44ePcKViuD79/8gnZP5Kg4+cCAW2dnnqUM2Lujw4UM4ePAA2ztfPsHIYA/sdOt43A50d7UFCjkUj+joXVBMDJDeDhcVk08cjd61C3v37uFYp8PKXX3X8xJRUTtw7FgSn3Xzxg10d7ZCqRjkM+02C7pettDNogqAFjzxuI3YHR2Nffv2coXy0V44HGZERm7kJNu2/cK8bW9rwbp1axnMnj27uUijQQOb1QyTcYZ3YMOGn/Hbzp1crAAvaDfY38O5hW3//n0ce+TIYWiUcub1xo0R2Lp1y8cYsUMWM125VhPe93Zj7do1vEPi26GfUdBFbhK8tGHrli1YsWwpgoOD0dXRQqAtXMCy8DBs3rwJoSGLsWrVclylBdoUGYlVK1dg9eqVCFsSSs8/4btvvmUwEnE0KTERISE/IiIiAsGLF2HhwgU8qbc97QgPX8qFr1mzGgu+/opzdL5o5l1aEhqC9evXYWlYKFYsD6e/YVj0w/dMGZVyBDMqeaDTRuKpkxYjIz2dOyeup6H3r2kkOuJ1H3N5Z1QUzp3LQF9vJ4xGLQYHXiM9LY0pEhsTg+PHj9HNcJu4OcL3uaQZY86LiZw8mcJTkmhBTUYJbU8fcoygobgWR4Z6iKtTPLE7d35HYkICT1dIZuY59HQ9412StBPQTMvw8Z6WaMNFxy3Gab4TeQT0M9IHwUT/G0i0MGIJ9CTiJjBIH+iQaQbC7+QnfEXiQL6xgF09TjETHCt8RbeMuil+D8RNsV1LHdQoZfR/iJJzCZuYmEE/Bd3MJNs/+0UURgFWJJ//aQ8k+CsxVTqnVytHObkQrUoG8T4/bs4u4ubbxLPwFzYNPc8HI2zijLm84l39Dx8hfwJenFezFBKKQwAAAABJRU5ErkJggg==') 0 50% no-repeat;min-width:45px;cursor:move}#nette-debug-logo span{display:none}#nette-debug-bar-bgl,#nette-debug-bar-bgx,#nette-debug-bar-bgr{position:absolute;z-index:-1;top:-7px;height:37px}#nette-debug .nette-panel{font:normal normal 12px/1.5 sans-serif;background:white;color:#333}#nette-debug h1{font:normal normal 23px/1.4 Tahoma,sans-serif;color:#575753;background:#EDEAE0;margin:-5px -5px 5px;padding:0 25px 5px 5px}#nette-debug .nette-mode-peek .nette-inner,#nette-debug .nette-mode-float .nette-inner{max-width:700px;max-height:500px;overflow:auto}#nette-debug .nette-panel .nette-icons{display:none}#nette-debug .nette-mode-peek{display:none;position:relative;z-index:23180;padding:5px;min-width:150px;min-height:50px;border:5px solid #EDEAE0;border-radius:5px;-moz-border-radius:5px}#nette-debug .nette-mode-peek h1{cursor:move}#nette-debug .nette-mode-float{position:relative;z-index:23179;padding:5px;min-width:150px;min-height:50px;border:5px solid #EDEAE0;border-radius:5px;-moz-border-radius:5px;opacity:.9;box-shadow:1px 1px 6px #666;-moz-box-shadow:1px 1px 6px rgba(0,0,0,.45);-webkit-box-shadow:1px 1px 6px #666}#nette-debug .nette-focused{z-index:23180;opacity:1}#nette-debug .nette-mode-float h1{cursor:move}#nette-debug .nette-mode-float .nette-icons{display:block;position:absolute;top:0;right:0;font-size:18px}#nette-debug .nette-icons a{color:#575753}#nette-debug .nette-icons a:hover{color:white}</style>

<!--[if lt IE 8]><style class="nette">#nette-debug-bar img{display:none}#nette-debug-bar li{border-left:1px solid #DCD7C8;padding:0 3px}#nette-debug-logo span{background:#edeae0;display:inline}</style><![endif]-->


<script id="nette-debug-script">/*<![CDATA[*/var Nette=Nette||{};
(function(){Nette.Class=function(a){var b=a.constructor||function(){},c,d=Object.prototype.hasOwnProperty;delete a.constructor;if(a.Extends){var f=function(){this.constructor=b};f.prototype=a.Extends.prototype;b.prototype=new f;delete a.Extends}if(a.Static){for(c in a.Static)if(d.call(a.Static,c))b[c]=a.Static[c];delete a.Static}for(c in a)if(d.call(a,c))b.prototype[c]=a[c];return b};Nette.Q=Nette.Class({Static:{factory:function(a){return new Nette.Q(a)},implement:function(a){var b,c=Nette.Q.implement,
d=Nette.Q.prototype,f=Object.prototype.hasOwnProperty;for(b in a)if(f.call(a,b)){c[b]=a[b];d[b]=function(i){return function(){return this.each(c[i],arguments)}}(b)}}},constructor:function(a){if(typeof a==="string")a=this._find(document,a);else if(!a||a.nodeType||a.length===undefined||a===window)a=[a];for(var b=0,c=a.length;b<c;b++)if(a[b])this[this.length++]=a[b]},length:0,find:function(a){return new Nette.Q(this._find(this[0],a))},_find:function(a,b){if(!a||!b)return[];else if(document.querySelectorAll)return a.querySelectorAll(b);
else if(b.charAt(0)==="#")return[document.getElementById(b.substring(1))];else{b=b.split(".");var c=a.getElementsByTagName(b[0]||"*");if(b[1]){for(var d=[],f=RegExp("(^|\\s)"+b[1]+"(\\s|$)"),i=0,k=c.length;i<k;i++)f.test(c[i].className)&&d.push(c[i]);return d}else return c}},dom:function(){return this[0]},each:function(a,b){for(var c=0,d;c<this.length;c++)if((d=a.apply(this[c],b||[]))!==undefined)return d;return this}});var h=Nette.Q.factory,e=Nette.Q.implement;e({bind:function(a,b){if(document.addEventListener&&
(a==="mouseenter"||a==="mouseleave")){var c=b;a=a==="mouseenter"?"mouseover":"mouseout";b=function(g){for(var j=g.relatedTarget;j;j=j.parentNode)if(j===this)return;c.call(this,g)}}var d=e.data.call(this);d=d.events=d.events||{};if(!d[a]){var f=this,i=d[a]=[],k=e.bind.genericHandler=function(g){if(!g.target)g.target=g.srcElement;if(!g.preventDefault)g.preventDefault=function(){g.returnValue=false};if(!g.stopPropagation)g.stopPropagation=function(){g.cancelBubble=true};g.stopImmediatePropagation=function(){this.stopPropagation();
j=i.length};for(var j=0;j<i.length;j++)i[j].call(f,g)};if(document.addEventListener)this.addEventListener(a,k,false);else document.attachEvent&&this.attachEvent("on"+a,k)}d[a].push(b)},addClass:function(a){this.className=this.className.replace(/^|\s+|$/g," ").replace(" "+a+" "," ")+" "+a},removeClass:function(a){this.className=this.className.replace(/^|\s+|$/g," ").replace(" "+a+" "," ")},hasClass:function(a){return this.className.replace(/^|\s+|$/g," ").indexOf(" "+a+" ")>-1},show:function(){var a=
e.show.display=e.show.display||{},b=this.tagName;if(!a[b]){var c=document.body.appendChild(document.createElement(b));a[b]=e.css.call(c,"display")}this.style.display=a[b]},hide:function(){this.style.display="none"},css:function(a){return this.currentStyle?this.currentStyle[a]:window.getComputedStyle?document.defaultView.getComputedStyle(this,null).getPropertyValue(a):undefined},data:function(){return this.nette?this.nette:this.nette={}},val:function(){var a;if(!this.nodeName){a=0;for(len=this.length;a<
len;a++)if(this[a].checked)return this[a].value;return null}if(this.nodeName.toLowerCase()==="select"){a=this.selectedIndex;var b=this.options;if(a<0)return null;else if(this.type==="select-one")return b[a].value;a=0;values=[];for(len=b.length;a<len;a++)b[a].selected&&values.push(b[a].value);return values}if(this.type==="checkbox")return this.checked;return this.value.replace(/^\s+|\s+$/g,"")},_trav:function(a,b,c){for(b=b.split(".");a&&!(a.nodeType===1&&(!b[0]||a.tagName.toLowerCase()===b[0])&&(!b[1]||
e.hasClass.call(a,b[1])));)a=a[c];return h(a)},closest:function(a){return e._trav(this,a,"parentNode")},prev:function(a){return e._trav(this.previousSibling,a,"previousSibling")},next:function(a){return e._trav(this.nextSibling,a,"nextSibling")},offset:function(a){for(var b=this,c=a?{left:-a.left||0,top:-a.top||0}:e.position.call(b);b=b.offsetParent;){c.left+=b.offsetLeft;c.top+=b.offsetTop}if(a)e.position.call(this,{left:-c.left,top:-c.top});else return c},position:function(a){if(a){this.nette&&
this.nette.onmove&&this.nette.onmove.call(this,a);this.style.left=(a.left||0)+"px";this.style.top=(a.top||0)+"px"}else return{left:this.offsetLeft,top:this.offsetTop,width:this.offsetWidth,height:this.offsetHeight}},draggable:function(a){var b=h(this),c=document.documentElement,d;a=a||{};h(a.handle||this).bind("mousedown",function(f){f.preventDefault();f.stopPropagation();if(e.draggable.binded)return c.onmouseup(f);var i=b[0].offsetLeft-f.clientX,k=b[0].offsetTop-f.clientY;e.draggable.binded=true;
d=false;c.onmousemove=function(g){g=g||event;if(!d){a.draggedClass&&b.addClass(a.draggedClass);a.start&&a.start(g,b);d=true}b.position({left:g.clientX+i,top:g.clientY+k});return false};c.onmouseup=function(g){if(d){a.draggedClass&&b.removeClass(a.draggedClass);if(a.stop)a.stop(g||event,b)}e.draggable.binded=c.onmousemove=c.onmouseup=null;return false}}).bind("click",function(f){if(d){f.stopImmediatePropagation();preventClick=false}})}})})();
(function(){Nette.Debug={};var h=Nette.Q.factory,e=Nette.Debug.Panel=Nette.Class({Extends:Nette.Q,Static:{PEEK:"nette-mode-peek",FLOAT:"nette-mode-float",WINDOW:"nette-mode-window",FOCUSED:"nette-focused",factory:function(a){return new e(a)},_toggle:function(a){var b=a.rel;b=b.charAt(0)==="#"?h(b):h(a)[b==="prev"?"prev":"next"](b.substring(4));if(b.css("display")==="none"){b.show();a.innerHTML=a.innerHTML.replace("►","▼")}else{b.hide();a.innerHTML=a.innerHTML.replace("▼","►")}}},constructor:function(a){Nette.Q.call(this,
"#nette-debug-panel-"+a.replace("nette-debug-panel-",""))},reposition:function(){if(this.hasClass(e.WINDOW))window.resizeBy(document.documentElement.scrollWidth-document.documentElement.clientWidth,document.documentElement.scrollHeight-document.documentElement.clientHeight);else{this.position(this.position());if(this.position().width)document.cookie=this.dom().id+"="+this.position().left+":"+this.position().top+"; path=/"}},focus:function(){if(this.hasClass(e.WINDOW))this.data().win.focus();else{clearTimeout(this.data().blurTimeout);
this.addClass(e.FOCUSED).show()}},blur:function(){this.removeClass(e.FOCUSED);if(this.hasClass(e.PEEK)){var a=this;this.data().blurTimeout=setTimeout(function(){a.hide()},50)}},toFloat:function(){this.removeClass(e.WINDOW).removeClass(e.PEEK).addClass(e.FLOAT).show().reposition();return this},toPeek:function(){this.removeClass(e.WINDOW).removeClass(e.FLOAT).addClass(e.PEEK).hide();document.cookie=this.dom().id+"=; path=/"},toWindow:function(){var a=this,b,c;c=this.offset();var d=this.dom().id;c.left+=
typeof window.screenLeft==="number"?window.screenLeft:window.screenX+10;c.top+=typeof window.screenTop==="number"?window.screenTop:window.screenY+50;if(b=window.open("",d.replace(/-/g,"_"),"left="+c.left+",top="+c.top+",width="+c.width+",height="+(c.height+15)+",resizable=yes,scrollbars=yes")){c=b.document;c.write('<!DOCTYPE html><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><style>'+h("#nette-debug-style").dom().innerHTML+"</style><script>"+h("#nette-debug-script").dom().innerHTML+
'<\/script><body id="nette-debug">');c.body.innerHTML='<div class="nette-panel nette-mode-window" id="'+d+'">'+this.dom().innerHTML+"</div>";b.Nette.Debug.Panel.factory(d).initToggler().reposition();c.title=a.find("h1").dom().innerHTML;h([b]).bind("unload",function(){a.toPeek();b.close()});h(c).bind("keyup",function(f){f.keyCode===27&&!f.shiftKey&&!f.altKey&&!f.ctrlKey&&!f.metaKey&&b.close()});document.cookie=d+"=window; path=/";this.hide().removeClass(e.FLOAT).removeClass(e.PEEK).addClass(e.WINDOW).data().win=
b}},init:function(){var a=this,b;a.data().onmove=function(c){var d=document,f=window.innerHeight||d.documentElement.clientHeight||d.body.clientHeight;c.left=Math.max(Math.min(c.left,0.8*this.offsetWidth),0.2*this.offsetWidth-(window.innerWidth||d.documentElement.clientWidth||d.body.clientWidth));c.top=Math.max(Math.min(c.top,0.8*this.offsetHeight),this.offsetHeight-f)};h(window).bind("resize",function(){a.reposition()});a.draggable({handle:a.find("h1"),stop:function(){a.toFloat()}}).bind("mouseenter",
function(){a.focus()}).bind("mouseleave",function(){a.blur()});this.initToggler();a.find(".nette-icons").find("a").bind("click",function(c){this.rel==="close"?a.toPeek():a.toWindow();c.preventDefault()});if(b=document.cookie.match(RegExp(a.dom().id+"=(window|(-?[0-9]+):(-?[0-9]+))")))b[2]?a.toFloat().position({left:b[2],top:b[3]}):a.toWindow();else a.addClass(e.PEEK)},initToggler:function(){var a=this;this.bind("click",function(b){var c=h(b.target).closest("a").dom();if(c&&c.rel){e._toggle(c);b.preventDefault();
a.reposition()}});return this}});Nette.Debug.Bar=Nette.Class({Extends:Nette.Q,constructor:function(){Nette.Q.call(this,"#nette-debug-bar")},init:function(){var a=this,b;a.data().onmove=function(c){var d=document,f=window.innerHeight||d.documentElement.clientHeight||d.body.clientHeight;c.left=Math.max(Math.min(c.left,0),this.offsetWidth-(window.innerWidth||d.documentElement.clientWidth||d.body.clientWidth));c.top=Math.max(Math.min(c.top,0),this.offsetHeight-f)};h(window).bind("resize",function(){a.position(a.position())});
a.draggable({draggedClass:"nette-dragged",stop:function(){document.cookie=a.dom().id+"="+a.position().left+":"+a.position().top+"; path=/"}});a.find("a").bind("click",function(c){if(this.rel==="close"){h("#nette-debug").hide();window.opera&&h("body").show()}else if(this.rel){var d=e.factory(this.rel);if(c.shiftKey)d.toFloat().toWindow();else if(d.hasClass(e.FLOAT)){var f=h(this).offset();d.offset({left:f.left-d.position().width+f.width+4,top:f.top-d.position().height-4}).toPeek()}else d.toFloat().position({left:d.position().left-
Math.round(Math.random()*100)-20,top:d.position().top-Math.round(Math.random()*100)-20}).reposition()}c.preventDefault()}).bind("mouseenter",function(){if(!(!this.rel||this.rel==="close"||a.hasClass("nette-dragged"))){var c=e.factory(this.rel);c.focus();if(c.hasClass(e.PEEK)){var d=h(this).offset();c.offset({left:d.left-c.position().width+d.width+4,top:d.top-c.position().height-4})}}}).bind("mouseleave",function(){!this.rel||this.rel==="close"||a.hasClass("nette-dragged")||e.factory(this.rel).blur()});
if(b=document.cookie.match(RegExp(a.dom().id+"=(-?[0-9]+):(-?[0-9]+)")))a.position({left:b[1],top:b[2]});a.find("a").each(function(){!this.rel||this.rel==="close"||e.factory(this.rel).init()})}})})();/*]]>*/</script>


<?php foreach($panels
as$id=>$panel):?>
<div class="nette-fixed-coords">
	<div class="nette-panel" id="nette-debug-panel-<?php echo$panel['id']?>">
		<div id="nette-debug-<?php echo$panel['id']?>"><?php echo$panel['panel']?></div>
		<div class="nette-icons">
			<a href="#" title="open in window">&curren;</a>
			<a href="#" rel="close" title="close window">&times;</a>
		</div>
	</div>
</div>
<?php endforeach?>

<div class="nette-fixed-coords">
	<div id="nette-debug-bar">
		<ul>
			<li id="nette-debug-logo" title="PHP <?php echo
htmlSpecialChars(PHP_VERSION." |\n".(isset($_SERVER['SERVER_SOFTWARE'])?$_SERVER['SERVER_SOFTWARE']." |\n":'').(class_exists('Framework')?'Nette Framework '.Framework::VERSION.' ('.substr(Framework::REVISION,8).')':''))?>">&nbsp;<span>Nette Framework</span></li>
			<?php foreach($panels
as$panel):if(!$panel['tab'])continue;?>
			<li><?php if($panel['panel']):?><a href="#" rel="<?php echo$panel['id']?>"><?php echo
trim($panel['tab'])?></a><?php else:echo'<span>',trim($panel['tab']),'</span>';endif?></li>
			<?php endforeach?>
			<li><a href="#" rel="close" title="close debug bar">&times;</a></li>
		</ul>
	</div>
</div>
<?php $output=ob_get_clean();?>


<div id="nette-debug"></div>

<script>
(function (onloadOrig) {
	window.onload = function() {
		if (typeof onloadOrig === 'function') onloadOrig();
		var debug = document.getElementById('nette-debug');
		document.body.appendChild(debug);
		debug.innerHTML = <?php echo
json_encode(@iconv('UTF-16','UTF-8//IGNORE',iconv('UTF-8','UTF-16//IGNORE',$output)))?>;
		for (var i = 0, scripts = debug.getElementsByTagName('script'); i < scripts.length; i++) eval(scripts[i].innerHTML);
		(new Nette.Debug.Bar).init();
		Nette.Q.factory(debug).show();
	};
})(window.onload);
</script>

<!-- /Nette Debug Bar -->
<?php }public
static
function
renderTab($id,$data){switch($id){case'time':?>
<span title="Execution time"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAJ6SURBVDjLjZO7T1NhGMY7Mji6uJgYt8bElTjof6CDg4sMSqIxJsRGB5F4TwQSIg1QKC0KWmkZEEsKtEcSxF5ohV5pKSicXqX3aqGn957z+PUEGopiGJ583/A+v3znvPkJAAjWR0VNJG0kGhKahCFhXcN3YBFfx8Kry6ym4xIzce88/fbWGY2k5WRb77UTTbWuYA9gDGg7EVmSIOF4g5T7HZKuMcSW5djWDyL0uRf0dCc8inYYxTcw9fAiCMBYB3gVj1z7gLhNTjKCqHkYP79KENC9Bq3uxrrqORzy+9D3tPAAccspVx1gWg0KbaZFbGllWFM+xrKkFQudV0CeDfJsjN4+C2nracjunoPq5VXIBrowMK4V1gG1LGyWdbZwCalsBYUyh2KFQzpXxVqkAGswD3+qBDpZwow9iYE5v26/VwfUQnnznyhvjguQYabIIpKpYD1ahI8UTT92MUSFuP5Z/9TBTgOgFrVjp3nakaG/0VmEfpX58pwzjUEquNk362s+PP8XYD/KpYTBHmRg9Wch0QX1R80dCZhYipudYQY2Auib8RmODVCa4hfUK4ngaiiLNFNFdKeCWWscXZMbWy9Unv9/gsIQU09a4pwvUeA3Uapy2C2wCKXL0DqTePLexbWPOv79E8f0UWrencZ2poxciUWZlKssB4bcHeE83NsFuMgpo2iIpMuNa1TNu4XjhggWvb+R2K3wZdLlAZl8Fd9jRb5sD+Xx0RJBx5gdom6VsMEFDyWF0WyCeSOFcDKPnRxZYTQL5Rc/nn1w4oFsBaIhC3r6FRh5erPRhYMyHdeFw4C6zkRhmijM7CnMu0AUZonCDCnRJBqSus5/ABD6Ba5CkQS8AAAAAElFTkSuQmCC"
/><?php echo
number_format((microtime(TRUE)-Debug::$time)*1000,1,'.',' ')?> ms</span>
<?php
return;case'memory':?>
<span title="The peak of allocated memory"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAGvSURBVDjLpZO7alZREEbXiSdqJJDKYJNCkPBXYq12prHwBezSCpaidnY+graCYO0DpLRTQcR3EFLl8p+9525xgkRIJJApB2bN+gZmqCouU+NZzVef9isyUYeIRD0RTz482xouBBBNHi5u4JlkgUfx+evhxQ2aJRrJ/oFjUWysXeG45cUBy+aoJ90Sj0LGFY6anw2o1y/mK2ZS5pQ50+2XiBbdCvPk+mpw2OM/Bo92IJMhgiGCox+JeNEksIC11eLwvAhlzuAO37+BG9y9x3FTuiWTzhH61QFvdg5AdAZIB3Mw50AKsaRJYlGsX0tymTzf2y1TR9WwbogYY3ZhxR26gBmocrxMuhZNE435FtmSx1tP8QgiHEvj45d3jNlONouAKrjjzWaDv4CkmmNu/Pz9CzVh++Yd2rIz5tTnwdZmAzNymXT9F5AtMFeaTogJYkJfdsaaGpyO4E62pJ0yUCtKQFxo0hAT1JU2CWNOJ5vvP4AIcKeao17c2ljFE8SKEkVdWWxu42GYK9KE4c3O20pzSpyyoCx4v/6ECkCTCqccKorNxR5uSXgQnmQkw2Xf+Q+0iqQ9Ap64TwAAAABJRU5ErkJggg=="
/><?php echo
function_exists('memory_get_peak_usage')?number_format(memory_get_peak_usage()/1000000,2,'.',' '):'n/a';?> MB</span>
<?php
return;case'dumps':if(!$data)return;?>
<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAIASURBVDjLpVPPaxNREJ6Vt01caH4oWk1T0ZKlGIo9RG+BUsEK4kEP/Q8qPXnpqRdPBf8A8Wahhx7FQ0GF9FJ6UksqwfTSBDGyB5HkkphC9tfb7jfbtyQQTx142byZ75v5ZnZWC4KALmICPy+2DkvKIX2f/POz83LxCL7nrz+WPNcll49DrhM9v7xdO9JW330DuXrrqkFSgig5iR2Cfv3t3gNxOnv5BwU+eZ5HuON5/PMPJZKJ+yKQfpW0S7TxdC6WJaWkyvff1LDaFRAeLZj05MHsiPTS6hua0PUqtwC5sHq9zv9RYWl+nu5cETcnJ1M0M5WlWq3GsX6/T+VymRzHDluZiGYAAsw0TQahV8uyyGq1qFgskm0bHIO/1+sx1rFtchJhArwEyIQ1Gg2WD2A6nWawHQJVDIWgIJfLhQowTIeE9D0mKAU8qPC0220afsWFQoH93W6X7yCDJ+DEBeBmsxnPIJVKxWQVUwry+XyUwBlKMKwA8jqdDhOVCqVAzQDVvXAXhOdGBFgymYwrGoZBmUyGjxCCdF0fSahaFdgoTHRxfTveMCXvWfkuE3Y+f40qhgT/nMitupzApdvT18bu+YeDQwY9Xl4aG9/d/URiMBhQq/dvZMeVghtT17lSZW9/rAKsvPa/r9Fc2dw+Pe0/xI6kM9mT5vtXy+Nw2kU/5zOGRpvuMIu0YAAAAABJRU5ErkJggg==" />variables
<?php
return;case'errors':if(!$data)return;?>
<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAIsSURBVDjLpVNLSJQBEP7+h6uu62vLVAJDW1KQTMrINQ1vPQzq1GOpa9EppGOHLh0kCEKL7JBEhVCHihAsESyJiE4FWShGRmauu7KYiv6Pma+DGoFrBQ7MzGFmPr5vmDFIYj1mr1WYfrHPovA9VVOqbC7e/1rS9ZlrAVDYHig5WB0oPtBI0TNrUiC5yhP9jeF4X8NPcWfopoY48XT39PjjXeF0vWkZqOjd7LJYrmGasHPCCJbHwhS9/F8M4s8baid764Xi0Ilfp5voorpJfn2wwx/r3l77TwZUvR+qajXVn8PnvocYfXYH6k2ioOaCpaIdf11ivDcayyiMVudsOYqFb60gARJYHG9DbqQFmSVNjaO3K2NpAeK90ZCqtgcrjkP9aUCXp0moetDFEeRXnYCKXhm+uTW0CkBFu4JlxzZkFlbASz4CQGQVBFeEwZm8geyiMuRVntzsL3oXV+YMkvjRsydC1U+lhwZsWXgHb+oWVAEzIwvzyVlk5igsi7DymmHlHsFQR50rjl+981Jy1Fw6Gu0ObTtnU+cgs28AKgDiy+Awpj5OACBAhZ/qh2HOo6i+NeA73jUAML4/qWux8mt6NjW1w599CS9xb0mSEqQBEDAtwqALUmBaG5FV3oYPnTHMjAwetlWksyByaukxQg2wQ9FlccaK/OXA3/uAEUDp3rNIDQ1ctSk6kHh1/jRFoaL4M4snEMeD73gQx4M4PsT1IZ5AfYH68tZY7zv/ApRMY9mnuVMvAAAAAElFTkSuQmCC"
/><span class="nette-warning"><?php echo
array_sum($data)?> errors</span>
<?php }}public
static
function
renderPanel($id,$data){switch($id){case'dumps':?>
<style>#nette-debug-dumps h2{font:11pt/1.5 sans-serif;margin:0;padding:2px 8px;background:#3484d2;color:white}#nette-debug-dumps table{width:100%}#nette-debug #nette-debug-dumps a{color:#333;background:transparent}#nette-debug-dumps a abbr{font-family:sans-serif;color:#999}#nette-debug-dumps pre.nette-dump span{color:#c16549}</style>


<h1>Dumped variables</h1>

<div class="nette-inner">
<?php foreach($data
as$item):?>
	<?php if($item['title']):?>
	<h2><?php echo
htmlspecialchars($item['title'])?></h2>
	<?php endif?>

	<table>
	<?php $i=0?>
	<?php foreach($item['dump']as$key=>$dump):?>
	<tr class="<?php echo$i++%
2?'nette-alt':''?>">
		<th><?php echo
htmlspecialchars($key)?></th>
		<td><?php echo$dump?></td>
	</tr>
	<?php endforeach?>
	</table>
<?php endforeach?>
</div>
<?php
return;case'errors':?>
<h1>Errors</h1>

<?php $relative=isset($_SERVER['SCRIPT_FILENAME'])?strtr(dirname(dirname($_SERVER['SCRIPT_FILENAME'])),'/',DIRECTORY_SEPARATOR):NULL?>

<div class="nette-inner">
<table>
<?php $i=0?>
<?php foreach($data
as$item=>$count):list($message,$file,$line)=explode('|',$item)?>
<tr class="<?php echo$i++%
2?'nette-alt':''?>">
	<td class="nette-right"><?php echo$count?"$count\xC3\x97":''?></td>
	<td><pre><?php echo
htmlspecialchars($message),' in ',(Debug::$editor?'<a href="'.DebugHelpers::editorLink($file,$line).'">':''),htmlspecialchars(($relative?str_replace($relative,"...",$file):$file)),':',$line,(Debug::$editor?'</a>':'')?></pre></td>
</tr>
<?php endforeach?>
</table>
</div><?php }}public
static
function
highlightFile($file,$line,$count=15){if(function_exists('ini_set')){ini_set('highlight.comment','#999; font-style: italic');ini_set('highlight.default','#000');ini_set('highlight.html','#06B');ini_set('highlight.keyword','#D24; font-weight: bold');ini_set('highlight.string','#080');}$start=max(1,$line-floor($count/2));$source=@file_get_contents($file);if(!$source)return;$source=explode("\n",highlight_string($source,TRUE));$spans=1;$out=$source[0];$source=explode('<br />',$source[1]);array_unshift($source,NULL);$i=$start;while(--$i>=1){if(preg_match('#.*(</?span[^>]*>)#',$source[$i],$m)){if($m[1]!=='</span>'){$spans++;$out.=$m[1];}break;}}$source=array_slice($source,$start,$count,TRUE);end($source);$numWidth=strlen((string)key($source));foreach($source
as$n=>$s){$spans+=substr_count($s,'<span')-substr_count($s,'</span');$s=str_replace(array("\r","\n"),array('',''),$s);preg_match_all('#<[^>]+>#',$s,$tags);if($n===$line){$out.=sprintf("<span class='highlight'>%{$numWidth}s:    %s\n</span>%s",$n,strip_tags($s),implode('',$tags[0]));}else{$out.=sprintf("<span class='line'>%{$numWidth}s:</span>    %s\n",$n,$s);}}return$out.str_repeat('</span>',$spans).'</code>';}public
static
function
editorLink($file,$line){return
strtr(Debug::$editor,array('%file'=>rawurlencode($file),'%line'=>$line));}public
static
function
htmlDump(&$var,$level=0){static$tableUtf,$tableBin,$reBinary='#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u';if($tableUtf===NULL){foreach(range("\x00","\xFF")as$ch){if(ord($ch)<32&&strpos("\r\n\t",$ch)===FALSE)$tableUtf[$ch]=$tableBin[$ch]='\\x'.str_pad(dechex(ord($ch)),2,'0',STR_PAD_LEFT);elseif(ord($ch)<127)$tableUtf[$ch]=$tableBin[$ch]=$ch;else{$tableUtf[$ch]=$ch;$tableBin[$ch]='\\x'.dechex(ord($ch));}}$tableBin["\\"]='\\\\';$tableBin["\r"]='\\r';$tableBin["\n"]='\\n';$tableBin["\t"]='\\t';$tableUtf['\\x']=$tableBin['\\x']='\\\\x';}if(is_bool($var)){return($var?'TRUE':'FALSE')."\n";}elseif($var===NULL){return"NULL\n";}elseif(is_int($var)){return"$var\n";}elseif(is_float($var)){$var=(string)$var;if(strpos($var,'.')===FALSE)$var.='.0';return"$var\n";}elseif(is_string($var)){if(Debug::$maxLen&&strlen($var)>Debug::$maxLen){$s=htmlSpecialChars(substr($var,0,Debug::$maxLen),ENT_NOQUOTES).' ... ';}else{$s=htmlSpecialChars($var,ENT_NOQUOTES);}$s=strtr($s,preg_match($reBinary,$s)||preg_last_error()?$tableBin:$tableUtf);$len=strlen($var);return"\"$s\"".($len>1?" ($len)":"")."\n";}elseif(is_array($var)){$s="<span>array</span>(".count($var).") ";$space=str_repeat($space1='   ',$level);$brackets=range(0,count($var)-1)===array_keys($var)?"[]":"{}";static$marker;if($marker===NULL)$marker=uniqid("\x00",TRUE);if(empty($var)){}elseif(isset($var[$marker])){$brackets=$var[$marker];$s.="$brackets[0] *RECURSION* $brackets[1]";}elseif($level<Debug::$maxDepth||!Debug::$maxDepth){$s.="<code>$brackets[0]\n";$var[$marker]=$brackets;foreach($var
as$k=>&$v){if($k===$marker)continue;$k=is_int($k)?$k:'"'.strtr($k,preg_match($reBinary,$k)||preg_last_error()?$tableBin:$tableUtf).'"';$s.="$space$space1$k => ".self::htmlDump($v,$level+1);}unset($var[$marker]);$s.="$space$brackets[1]</code>";}else{$s.="$brackets[0] ... $brackets[1]";}return$s."\n";}elseif(is_object($var)){$arr=(array)$var;$s="<span>".get_class($var)."</span>(".count($arr).") ";$space=str_repeat($space1='   ',$level);static$list=array();if(empty($arr)){}elseif(in_array($var,$list,TRUE)){$s.="{ *RECURSION* }";}elseif($level<Debug::$maxDepth||!Debug::$maxDepth){$s.="<code>{\n";$list[]=$var;foreach($arr
as$k=>&$v){$m='';if($k[0]==="\x00"){$m=$k[1]==='*'?' <span>protected</span>':' <span>private</span>';$k=substr($k,strrpos($k,"\x00")+1);}$k=strtr($k,preg_match($reBinary,$k)||preg_last_error()?$tableBin:$tableUtf);$s.="$space$space1\"$k\"$m => ".self::htmlDump($v,$level+1);}array_pop($list);$s.="$space}</code>";}else{$s.="{ ... }";}return$s."\n";}elseif(is_resource($var)){return"<span>".get_resource_type($var)." resource</span>\n";}else{return"<span>unknown type</span>\n";}}public
static
function
jsonDump(&$var,$level=0){if(is_bool($var)||is_null($var)||is_int($var)||is_float($var)){return$var;}elseif(is_string($var)){if(Debug::$maxLen&&strlen($var)>Debug::$maxLen){$var=substr($var,0,Debug::$maxLen)." \xE2\x80\xA6 ";}return@iconv('UTF-16','UTF-8//IGNORE',iconv('UTF-8','UTF-16//IGNORE',$var));}elseif(is_array($var)){static$marker;if($marker===NULL)$marker=uniqid("\x00",TRUE);if(isset($var[$marker])){return"\xE2\x80\xA6RECURSION\xE2\x80\xA6";}elseif($level<Debug::$maxDepth||!Debug::$maxDepth){$var[$marker]=TRUE;$res=array();foreach($var
as$k=>&$v){if($k!==$marker)$res[self::jsonDump($k)]=self::jsonDump($v,$level+1);}unset($var[$marker]);return$res;}else{return" \xE2\x80\xA6 ";}}elseif(is_object($var)){$arr=(array)$var;static$list=array();if(in_array($var,$list,TRUE)){return"\xE2\x80\xA6RECURSION\xE2\x80\xA6";}elseif($level<Debug::$maxDepth||!Debug::$maxDepth){$list[]=$var;$res=array("\x00"=>'(object) '.get_class($var));foreach($arr
as$k=>&$v){if($k[0]==="\x00"){$k=substr($k,strrpos($k,"\x00")+1);}$res[self::jsonDump($k)]=self::jsonDump($v,$level+1);}array_pop($list);return$res;}else{return" \xE2\x80\xA6 ";}}elseif(is_resource($var)){return"resource ".get_resource_type($var);}else{return"unknown type";}}public
static
function
clickableDump($dump){return'<pre class="nette-dump">'.preg_replace_callback('#^( *)((?>[^(]{1,200}))\((\d+)\) <code>#m',create_function('$m','
				return "$m[1]<a href=\'#\' rel=\'next\'>$m[2]($m[3]) " . (trim($m[1]) || $m[3] < 7 ? \'<abbr>&#x25bc;</abbr> </a><code>\' : \'<abbr>&#x25ba;</abbr> </a><code class="nette-collapsed">\');
			'),self::htmlDump($dump)).'</pre>';}}Debug::_init();if(!function_exists('dump')){function
dump($var){foreach($args=func_get_args()as$arg)Debug::dump($arg);return$var;}}