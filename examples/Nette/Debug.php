<?php
/**
 * Nette Framework
 *
 * Copyright (c) 2004, 2009 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://nettephp.com
 *
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @category   Nette
 * @package    Nette
 * @version    $Id$
 */

if(version_compare(PHP_VERSION,'5.2.0','<')){throw
new
Exception('Nette Framework requires PHP 5.2.0 or newer.');}@set_magic_quotes_runtime(FALSE);if(version_compare(PHP_VERSION,'5.2.2','<')){function
fixCallback(&$callback){if(is_object($callback)){$callback=array($callback,'__invoke');return;}if(is_string($callback)&&strpos($callback,':')){$callback=explode('::',$callback);}if(is_array($callback)&&is_string($callback[0])&&$a=strrpos($callback[0],'\\')){$callback[0]=substr($callback[0],$a+1);}}}else{function
fixCallback(&$callback){if(is_object($callback)){$callback=array($callback,'__invoke');}elseif(is_string($callback)&&$a=strrpos($callback,'\\')){$callback=substr($callback,$a+1);}elseif(is_array($callback)&&is_string($callback[0])&&$a=strrpos($callback[0],'\\')){$callback[0]=substr($callback[0],$a+1);}}}function
fixNamespace(&$class){if($a=strrpos($class,'\\')){$class=substr($class,$a+1);}}class
ArgumentOutOfRangeException
extends
InvalidArgumentException{}class
InvalidStateException
extends
RuntimeException{function
__construct($message='',$code=0,Exception$previous=NULL){if(version_compare(PHP_VERSION,'5.3','<')){$this->previous=$previous;parent::__construct($message,$code);}else{parent::__construct($message,$code,$previous);}}}class
NotImplementedException
extends
LogicException{}class
NotSupportedException
extends
LogicException{}class
DeprecatedException
extends
NotSupportedException{}class
MemberAccessException
extends
LogicException{}class
IOException
extends
RuntimeException{}class
FileNotFoundException
extends
IOException{}class
DirectoryNotFoundException
extends
IOException{}class
FatalErrorException
extends
Exception{private$severity;public
function
__construct($message,$code,$severity,$file,$line,$context){parent::__construct($message,$code);$this->severity=$severity;$this->file=$file;$this->line=$line;$this->context=$context;}public
function
getSeverity(){return$this->severity;}}final
class
Framework{const
NAME='Nette Framework';const
VERSION='0.9';const
REVISION='424 released on 2009/07/15 12:03:47';const
PACKAGE='PHP 5.2';final
public
function
__construct(){throw
new
LogicException("Cannot instantiate static class ".get_class($this));}public
static
function
compareVersion($version){return
version_compare($version,self::VERSION);}public
static
function
promo($xhtml=TRUE){echo'<a href="http://nettephp.com/" title="Nette Framework - The Most Innovative PHP Framework"><img ','src="http://nettephp.com/images/nette-powered.gif" alt="Powered by Nette Framework" width="80" height="15"',($xhtml?' />':'>'),'</a>';}}final
class
Debug{public
static$productionMode;public
static$consoleMode;public
static$time;private
static$firebugDetected;private
static$ajaxDetected;private
static$consoleData;public
static$maxDepth=3;public
static$maxLen=150;public
static$showLocation=FALSE;const
DEVELOPMENT=FALSE;const
PRODUCTION=TRUE;const
DETECT=NULL;public
static$strictMode=FALSE;public
static$onFatalError=array();public
static$mailer=array(__CLASS__,'defaultMailer');private
static$enabled=FALSE;private
static$logFile;private
static$logHandle;private
static$sendEmails;private
static$emailHeaders=array('To'=>'','From'=>'noreply@%host%','X-Mailer'=>'Nette Framework','Subject'=>'PHP: An error occurred on the server %host%','Body'=>'[%date%] %message%');private
static$colophons=array(array(__CLASS__,'getDefaultColophons'));private
static$enabledProfiler=FALSE;public
static$counters=array();const
LOG='LOG';const
INFO='INFO';const
WARN='WARN';const
ERROR='ERROR';const
TRACE='TRACE';const
EXCEPTION='EXCEPTION';const
GROUP_START='GROUP_START';const
GROUP_END='GROUP_END';final
public
function
__construct(){throw
new
LogicException("Cannot instantiate static class ".get_class($this));}public
static
function
init(){self::$time=microtime(TRUE);self::$consoleMode=PHP_SAPI==='cli';self::$productionMode=self::DETECT;self::$firebugDetected=isset($_SERVER['HTTP_USER_AGENT'])&&strpos($_SERVER['HTTP_USER_AGENT'],'FirePHP/');self::$ajaxDetected=isset($_SERVER['HTTP_X_REQUESTED_WITH'])&&$_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';register_shutdown_function(array(__CLASS__,'shutdownHandler'));}public
static
function
shutdownHandler(){static$types=array(E_ERROR=>1,E_CORE_ERROR=>1,E_COMPILE_ERROR=>1,E_PARSE=>1);$error=error_get_last();if(isset($types[$error['type']])){if(!headers_sent()){header('HTTP/1.1 500 Internal Server Error');}if(ini_get('html_errors')){$error['message']=html_entity_decode(strip_tags($error['message']));}self::processException(new
FatalErrorException($error['message'],0,$error['type'],$error['file'],$error['line'],NULL),TRUE);}if(self::$productionMode){return;}foreach(headers_list()as$header){if(strncasecmp($header,'Content-Type:',13)===0){if(substr($header,14,9)==='text/html'){break;}return;}}if(self::$enabledProfiler){if(self::$firebugDetected){self::fireLog('Nette profiler',self::GROUP_START);foreach(self::$colophons
as$callback){foreach((array)call_user_func($callback,'profiler')as$line)self::fireLog(strip_tags($line));}self::fireLog(NULL,self::GROUP_END);}if(!self::$ajaxDetected){$colophons=self::$colophons;?>

<style type="text/css">
/* <![CDATA[ */
	#netteProfilerContainer {
		position: fixed;
		_position: absolute;
		right: 5px;
		bottom: 5px;
		z-index: 23178;
	}

	#netteProfiler {
		font: normal normal 11px/1.4 Consolas, Arial;
		position: relative;
		padding: 1px;
		color: black;
		background: #EEE;
		border: 1px dotted gray;
		cursor: move;
		opacity: .70;
		=filter: alpha(opacity=70);
	}

	#netteProfiler * {
		color: inherit;
		background: inherit;
		text-align: inherit;
	}

	#netteProfilerIcon {
		position: absolute;
		right: 0;
		top: 0;
		line-height: 1;
		padding: 4px;
		color: black;
		text-decoration: none;
	}

	#netteProfiler:hover {
		opacity: 1;
		=filter: none;
	}

	#netteProfiler ul {
		margin: 0;
		padding: 0;
		width: 350px;
	}

	#netteProfiler li {
		margin: 0;
		padding: 1px;
		text-align: left;
		list-style: none;
	}

	#netteProfiler span[title] {
		border-bottom: 1px dotted gray;
		cursor: help;
	}

	#netteProfiler strong {
		color: red;
	}
/* ]]> */
</style>


<div id="netteProfilerContainer">
<div id="netteProfiler">
	<a id="netteProfilerIcon" href="#"><abbr>&#x25bc;</abbr></a
	><ul>
	<?php foreach($colophons
as$callback):?>
	<?php foreach((array)call_user_func($callback,'profiler')as$line):?><li><?php echo$line,"\n"?></li><?php endforeach?>
	<?php endforeach?>
	</ul>
</div>
</div>


<script type="text/javascript">
/* <![CDATA[ */
document.getElementById('netteProfiler').onmousedown = function(e) {
	e = e || event;
	this.posX = parseInt(this.style.left + '0');
	this.posY = parseInt(this.style.top + '0');
	this.mouseX = e.clientX;
	this.mouseY = e.clientY;

	var thisObj = this;

	document.documentElement.onmousemove = function(e) {
		e = e || event;
		thisObj.style.left = (e.clientX - thisObj.mouseX + thisObj.posX) + "px";
		thisObj.style.top = (e.clientY - thisObj.mouseY + thisObj.posY) + "px";
		return false;
	};

	document.documentElement.onmouseup = function(e) {
		document.documentElement.onmousemove = null;
		document.documentElement.onmouseup = null;
		return false;
	};
};

document.getElementById('netteProfilerIcon').onclick = function(e) {
	var arrow = this.getElementsByTagName('abbr')[0];
	var panel = this.nextSibling;
	var collapsed = panel.currentStyle ? panel.currentStyle.display == 'none' : getComputedStyle(panel, null).display == 'none';

	arrow.innerHTML = collapsed ? String.fromCharCode(0x25bc) : 'Profiler ' + String.fromCharCode(0x25ba);
	panel.style.display = collapsed ? 'block' : 'none';
	arrow.parentNode.style.position = collapsed ? 'absolute' : 'static';
	return false;
}

document.body.appendChild(document.getElementById('netteProfilerContainer'));
/* ]]> */
</script>
<?php }}if(self::$consoleData){$payload=self::$consoleData;if(!function_exists('_netteDumpCb2')){function
_netteDumpCb2($m){return"$m[1]<a href='#' onclick='return !netteToggle(this)'>$m[2]($m[3]) ".($m[3]<7?'<abbr>&#x25bc;</abbr> </a><code>':'<abbr>&#x25ba;</abbr> </a><code class="collapsed">');}}ob_start();?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="robots" content="noindex,noarchive">
	<meta name="generator" content="Nette Framework">

	<title>Nette Debug Console</title>

	<style type="text/css">
	/* <![CDATA[ */
		body {
			margin: 0;
			padding: 0;
			font: 9pt/1.5 Verdana, sans-serif;
			background: white;
			color: #333;
		}

		h1 {
			font-size: 13pt;
			margin: 0;
			padding: 2px 8px;
			background: black;
			color: white;
			border-bottom: 1px solid black;
		}

		h2 {
			font: 11pt/1.5 sans-serif;
			margin: 0;
			padding: 2px 8px;
			background: #3484d2;
			color: white;
		}

		a {
			text-decoration: none;
			color: #4197E3;
		}

		a abbr {
			font-family: sans-serif;
			color: #999;
		}

		p {
			margin: .8em 0
		}

		pre, code, table {
			font: 9pt/1.5 Consolas, monospace;
		}

		pre, table {
			background: #fffbcc;
			padding: .4em .7em;
			border: 1px dotted silver;
		}

		table pre {
			padding: 0;
			margin: 0;
			border: none;
		}

		pre.dump span {
			color: #c16549;
		}

		pre.dump a {
			color: #333;
		}

		table {
			border-collapse: collapse;
			width: 100%;
		}

		td, th {
			vertical-align: top;
			text-align: left;
			border: 1px solid #eeeebb;
		}

		th {
			width: 10;
			padding: 2px 3px 2px 8px;
			font-weight: bold;
		}

		td {
			padding: 2px 8px 2px 3px;
		}

		.odd, .odd pre {
			background: #faf5c3;
		}

	/* ]]> */
	</style>


	<script type="text/javascript">
	/* <![CDATA[ */
		document.write('<style> .collapsed { display: none; } <\/style>');

		function netteToggle(link, panelId)
		{
			var arrow = link.getElementsByTagName('abbr')[0];
			var panel = panelId ? document.getElementById(panelId) : link.nextSibling;
			while (panel.nodeType !== 1) panel = panel.nextSibling;
			var collapsed = panel.currentStyle ? panel.currentStyle.display == 'none' : getComputedStyle(panel, null).display == 'none';

			arrow.innerHTML = String.fromCharCode(collapsed ? 0x25bc : 0x25ba);
			panel.style.display = collapsed ? (panel.tagName.toLowerCase() === 'code' ? 'inline' : 'block') : 'none';

			return true;
		}
	/* ]]> */
	</script>
</head>



<body>
	<h1>Nette Debug Console</h1>
</body>
</html>
<?php $document=ob_get_clean()?>

<?php ob_start()?>
<?php foreach($payload
as$item):?>
	<?php if($item['title']):?>
	<h2><?php echo
htmlspecialchars($item['title'])?></h2>
	<?php endif?>

	<table>
	<?php $i=0?>
	<?php foreach((is_array($item['var'])?$item['var']:array(''=>$item['var']))as$key=>$val):?>
	<tr class="<?php echo$i++%
2?'odd':'even'?>">
		<th><?php echo
htmlspecialchars($key)?></th>
		<td><?php echo
preg_replace_callback('#(<pre class="dump">|\s+)?(.*)\((\d+)\) <code>#','_netteDumpCb2',Debug::dump($val,TRUE))?></td>
	</tr>
	<?php endforeach?>
	</table>
<?php endforeach?>
<?php $body=ob_get_clean()?>

<script type="text/javascript">
/* <![CDATA[ */
if (typeof _netteConsole === 'undefined') {
	_netteConsole = window.open('','_netteConsole','width=700,height=700,resizable,scrollbars=yes');
	_netteConsole.document.write(<?php echo
json_encode(preg_replace('#\s+#',' ',$document))?>);
	_netteConsole.document.close();
	_netteConsole.document.onkeyup = function(e) {
		e = e || _netteConsole.event;
		if (e.keyCode == 27) _netteConsole.close();
	}
	_netteConsole.document.body.focus();
}
_netteConsole.document.body.innerHTML = _netteConsole.document.body.innerHTML + <?php echo
json_encode($body)?>;
/* ]]> */
</script>
<?php }}public
static
function
dump($var,$return=FALSE){if(!$return&&self::$productionMode){return$var;}$output="<pre class=\"dump\">".self::_dump($var,0)."</pre>\n";if(self::$showLocation){$trace=debug_backtrace();if(isset($trace[0]['file'],$trace[0]['line'])){$output=substr_replace($output,' <small>'.htmlspecialchars("in file {$trace[0]['file']} on line {$trace[0]['line']}",ENT_NOQUOTES).'</small>',-8,0);}}if(self::$consoleMode){$output=htmlspecialchars_decode(strip_tags($output),ENT_NOQUOTES);}if($return){return$output;}else{echo$output;return$var;}}public
static
function
consoleDump($var,$title=NULL){if(!self::$productionMode){self::$consoleData[]=array('title'=>$title,'var'=>$var);}return$var;}private
static
function
_dump(&$var,$level){if(is_bool($var)){return"<span>bool</span>(".($var?'TRUE':'FALSE').")\n";}elseif($var===NULL){return"<span>NULL</span>\n";}elseif(is_int($var)){return"<span>int</span>($var)\n";}elseif(is_float($var)){return"<span>float</span>($var)\n";}elseif(is_string($var)){if(self::$maxLen&&strlen($var)>self::$maxLen){$s=htmlSpecialChars(substr($var,0,self::$maxLen),ENT_NOQUOTES).' ... ';}else{$s=htmlSpecialChars($var,ENT_NOQUOTES);}return"<span>string</span>(".strlen($var).") \"$s\"\n";}elseif(is_array($var)){$s="<span>array</span>(".count($var).") ";$space=str_repeat($space1='   ',$level);static$marker;if($marker===NULL)$marker=uniqid("\x00",TRUE);if(empty($var)){}elseif(isset($var[$marker])){$s.="{\n$space$space1*RECURSION*\n$space}";}elseif($level<self::$maxDepth||!self::$maxDepth){$s.="<code>{\n";$var[$marker]=0;foreach($var
as$k=>&$v){if($k===$marker)continue;$s.="$space$space1".(is_int($k)?$k:"\"$k\"")." => ".self::_dump($v,$level+1);}unset($var[$marker]);$s.="$space}</code>";}else{$s.="{\n$space$space1...\n$space}";}return$s."\n";}elseif(is_object($var)){$arr=(array)$var;$s="<span>object</span>(".get_class($var).") (".count($arr).") ";$space=str_repeat($space1='   ',$level);static$list=array();if(empty($arr)){$s.="{}";}elseif(in_array($var,$list,TRUE)){$s.="{\n$space$space1*RECURSION*\n$space}";}elseif($level<self::$maxDepth||!self::$maxDepth){$s.="<code>{\n";$list[]=$var;foreach($arr
as$k=>&$v){$m='';if($k[0]==="\x00"){$m=$k[1]==='*'?' <span>protected</span>':' <span>private</span>';$k=substr($k,strrpos($k,"\x00")+1);}$s.="$space$space1\"$k\"$m => ".self::_dump($v,$level+1);}array_pop($list);$s.="$space}</code>";}else{$s.="{\n$space$space1...\n$space}";}return$s."\n";}elseif(is_resource($var)){return"<span>resource of type</span>(".get_resource_type($var).")\n";}else{return"<span>unknown type</span>\n";}}public
static
function
timer($name=NULL){static$time=array();$now=microtime(TRUE);$delta=isset($time[$name])?$now-$time[$name]:0;$time[$name]=$now;return$delta;}public
static
function
enable($mode=NULL,$logFile=NULL,$email=NULL){error_reporting(E_ALL|E_STRICT);if(is_bool($mode)){self::$productionMode=$mode;}if(self::$productionMode===self::DETECT){if(class_exists('Environment')){self::$productionMode=Environment::isProduction();}elseif(isset($_SERVER['SERVER_ADDR'])||isset($_SERVER['LOCAL_ADDR'])){$addr=isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:$_SERVER['LOCAL_ADDR'];$oct=explode('.',$addr);self::$productionMode=$addr!=='::1'&&(count($oct)!==4||($oct[0]!=='10'&&$oct[0]!=='127'&&($oct[0]!=='172'||$oct[1]<16||$oct[1]>31)&&($oct[0]!=='169'||$oct[1]!=='254')&&($oct[0]!=='192'||$oct[1]!=='168')));}else{self::$productionMode=!self::$consoleMode;}}if(self::$productionMode&&$logFile!==FALSE){self::$logFile='log/php_error.log';if(class_exists('Environment')){if(is_string($logFile)){self::$logFile=Environment::expand($logFile);}else
try{self::$logFile=Environment::expand('%logDir%/php_error.log');}catch(InvalidStateException$e){}}elseif(is_string($logFile)){self::$logFile=$logFile;}ini_set('error_log',self::$logFile);}if(function_exists('ini_set')){ini_set('display_errors',!self::$productionMode);ini_set('html_errors',!self::$logFile&&!self::$consoleMode);ini_set('log_errors',(bool)self::$logFile);}elseif(ini_get('log_errors')!=(bool)self::$logFile||(ini_get('display_errors')!=!self::$productionMode&&ini_get('display_errors')!==(self::$productionMode?'stderr':'stdout'))){throw
new
NotSupportedException('Function ini_set() must be enabled.');}self::$sendEmails=self::$logFile&&$email;if(self::$sendEmails){if(is_string($email)){self::$emailHeaders['To']=$email;}elseif(is_array($email)){self::$emailHeaders=$email+self::$emailHeaders;}}if(!defined('E_DEPRECATED')){define('E_DEPRECATED',8192);}if(!defined('E_USER_DEPRECATED')){define('E_USER_DEPRECATED',16384);}set_exception_handler(array(__CLASS__,'exceptionHandler'));set_error_handler(array(__CLASS__,'errorHandler'));self::$enabled=TRUE;}public
static
function
isEnabled(){return
self::$enabled;}public
static
function
exceptionHandler(Exception$exception){if(!headers_sent()){header('HTTP/1.1 500 Internal Server Error');}self::processException($exception,TRUE);exit;}public
static
function
errorHandler($severity,$message,$file,$line,$context){if($severity===E_RECOVERABLE_ERROR||$severity===E_USER_ERROR){throw
new
FatalErrorException($message,0,$severity,$file,$line,$context);}elseif(($severity&error_reporting())!==$severity){return
NULL;}elseif(self::$strictMode){self::processException(new
FatalErrorException($message,0,$severity,$file,$line,$context),TRUE);exit;}static$types=array(E_WARNING=>'Warning',E_USER_WARNING=>'Warning',E_NOTICE=>'Notice',E_USER_NOTICE=>'Notice',E_STRICT=>'Strict standards',E_DEPRECATED=>'Deprecated',E_USER_DEPRECATED=>'Deprecated');$type=isset($types[$severity])?$types[$severity]:'Unknown error';if(self::$logFile){if(self::$sendEmails){self::sendEmail("$type: $message in $file on line $line");}return
FALSE;}elseif(!self::$productionMode&&self::$firebugDetected&&!headers_sent()){$message=strip_tags($message);self::fireLog("$type: $message in $file on line $line",self::ERROR);return
NULL;}return
FALSE;}public
static
function
processException(Exception$exception,$outputAllowed=FALSE){if(self::$logFile){error_log("PHP Fatal error:  Uncaught $exception");$file=@strftime('%d-%b-%Y %H-%M-%S ',Debug::$time).strstr(number_format(Debug::$time,4,'~',''),'~');$file=dirname(self::$logFile)."/exception $file.html";self::$logHandle=@fopen($file,'x');if(self::$logHandle){ob_start(array(__CLASS__,'writeFile'),1);self::paintBlueScreen($exception);ob_end_flush();fclose(self::$logHandle);}if(self::$sendEmails){self::sendEmail((string)$exception);}}elseif(self::$productionMode){}elseif(self::$consoleMode){if($outputAllowed){echo"$exception\n";foreach(self::$colophons
as$callback){foreach((array)call_user_func($callback,'bluescreen')as$line)echo
strip_tags($line)."\n";}}}elseif(self::$firebugDetected&&self::$ajaxDetected&&!headers_sent()){self::fireLog($exception,self::EXCEPTION);}elseif($outputAllowed){if(!headers_sent()){@ob_end_clean();while(ob_get_level()&&@ob_end_clean());header('Content-Encoding:',TRUE);}self::paintBlueScreen($exception);}elseif(self::$firebugDetected&&!headers_sent()){self::fireLog($exception,self::EXCEPTION);}foreach(self::$onFatalError
as$handler){fixCallback($handler);call_user_func($handler,$exception);}}public
static
function
paintBlueScreen(Exception$exception){$internals=array();foreach(array('Object','ObjectMixin')as$class){if(class_exists($class,FALSE)){$rc=new
ReflectionClass($class);$internals[$rc->getFileName()]=TRUE;}}$colophons=self::$colophons;if(!function_exists('_netteDebugPrintCode')){function
_netteDebugPrintCode($file,$line,$count=15){if(function_exists('ini_set')){ini_set('highlight.comment','#999; font-style: italic');ini_set('highlight.default','#000');ini_set('highlight.html','#06b');ini_set('highlight.keyword','#d24; font-weight: bold');ini_set('highlight.string','#080');}$start=max(1,$line-floor($count/2));$source=@file_get_contents($file);if(!$source)return;$source=explode("\n",highlight_string($source,TRUE));$spans=1;echo$source[0];$source=explode('<br />',$source[1]);array_unshift($source,NULL);$i=$start;while(--$i>=1){if(preg_match('#.*(</?span[^>]*>)#',$source[$i],$m)){if($m[1]!=='</span>'){$spans++;echo$m[1];}break;}}$source=array_slice($source,$start,$count,TRUE);end($source);$numWidth=strlen((string)key($source));foreach($source
as$n=>$s){$spans+=substr_count($s,'<span')-substr_count($s,'</span');$s=str_replace(array("\r","\n"),array('',''),$s);if($n===$line){printf("<span class='highlight'>Line %{$numWidth}s:    %s\n</span>%s",$n,strip_tags($s),preg_replace('#[^>]*(<[^>]+>)[^<]*#','$1',$s));}else{printf("<span class='line'>Line %{$numWidth}s:</span>    %s\n",$n,$s);}}echo
str_repeat('</span>',$spans),'</code>';}function
_netteDump($var){return
preg_replace_callback('#(<pre class="dump">|\s+)?(.*)\((\d+)\) <code>#','_netteDumpCb',Debug::dump($var,TRUE));}function
_netteDumpCb($m){return"$m[1]<a href='#' onclick='return !netteToggle(this)'>$m[2]($m[3]) ".(trim($m[1])||$m[3]<7?'<abbr>&#x25bc;</abbr> </a><code>':'<abbr>&#x25ba;</abbr> </a><code class="collapsed">');}function
_netteOpenPanel($name,$collapsed){static$id;$id++;?>
	<div class="panel">
		<h2><a href="#" onclick="return !netteToggle(this, 'pnl<?php echo$id?>')"><?php echo
htmlSpecialChars($name)?> <abbr><?php echo$collapsed?'&#x25ba;':'&#x25bc;'?></abbr></a></h2>

		<div id="pnl<?php echo$id?>" class="<?php echo$collapsed?'collapsed ':''?>inner">
	<?php
}function
_netteClosePanel(){?>
		</div>
	</div>
	<?php
}}static$errorTypes=array(E_ERROR=>'Fatal Error',E_USER_ERROR=>'User Error',E_RECOVERABLE_ERROR=>'Recoverable Error',E_CORE_ERROR=>'Core Error',E_COMPILE_ERROR=>'Compile Error',E_PARSE=>'Parse Error',E_WARNING=>'Warning',E_CORE_WARNING=>'Core Warning',E_COMPILE_WARNING=>'Compile Warning',E_USER_WARNING=>'User Warning',E_NOTICE=>'Notice',E_USER_NOTICE=>'User Notice',E_STRICT=>'Strict',E_DEPRECATED=>'Deprecated',E_USER_DEPRECATED=>'User Deprecated');$title=($exception
instanceof
FatalErrorException&&isset($errorTypes[$exception->getSeverity()]))?$errorTypes[$exception->getSeverity()]:get_class($exception);$rn=0;if(headers_sent()){echo'</pre></xmp></table>';}?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="robots" content="noindex,noarchive">
	<meta name="generator" content="Nette Framework">

	<title><?php echo
htmlspecialchars($title)?></title>

	<style type="text/css">
	/* <![CDATA[ */
		body {
			margin: 0 0 2em;
			padding: 0;
		}

		#netteBluescreen {
			font: 9pt/1.5 Verdana, sans-serif;
			background: white;
			color: #333;
			position: absolute;
			left: 0;
			top: 0;
			width: 100%;
			z-index: 23178;
			text-align: left;
		}

		#netteBluescreen * {
			color: inherit;
			background: inherit;
			text-align: inherit;
		}

		#netteBluescreenIcon {
			position: absolute;
			right: .5em;
			top: .5em;
			z-index: 23179;
			text-decoration: none;
			background: red;
			padding: 3px;
		}

		#netteBluescreenIcon abbr {
			color: black !important;
		}

		#netteBluescreen h1 {
			font: 18pt/1.5 Verdana, sans-serif !important;
			margin: .6em 0;
		}

		#netteBluescreen h2 {
			font: 14pt/1.5 sans-serif !important;
			color: #888;
			margin: .6em 0;
		}

		#netteBluescreen a {
			text-decoration: none;
			color: #4197E3;
		}

		#netteBluescreen a abbr {
			font-family: sans-serif;
			color: #999;
		}

		#netteBluescreen h3 {
			font: bold 10pt/1.5 Verdana, sans-serif !important;
			margin: 1em 0;
			padding: 0;
		}

		#netteBluescreen p {
			margin: .8em 0
		}

		#netteBluescreen pre, #netteBluescreen code, #netteBluescreen table {
			font: 9pt/1.5 Consolas, monospace !important;
		}

		#netteBluescreen pre, #netteBluescreen table {
			background: #fffbcc;
			padding: .4em .7em;
			border: 1px dotted silver;
		}

		#netteBluescreen table pre {
			padding: 0;
			margin: 0;
			border: none;
		}

		#netteBluescreen pre.dump span {
			color: #c16549;
		}

		#netteBluescreen pre.dump a {
			color: #333;
		}

		#netteBluescreen div.panel {
			border-bottom: 1px solid #eee;
			padding: 1px 2em;
		}

		#netteBluescreen div.inner {
			padding: 0.1em 1em 1em;
			background: #f5f5f5;
		}

		#netteBluescreen table {
			border-collapse: collapse;
			width: 100%;
		}

		#netteBluescreen td, #netteBluescreen th {
			vertical-align: top;
			text-align: left;
			padding: 2px 3px;
			border: 1px solid #eeeebb;
		}

		#netteBluescreen th {
			width: 10%;
			font-weight: bold;
		}

		#netteBluescreen .odd, #netteBluescreen .odd pre {
			background-color: #faf5c3;
		}

		#netteBluescreen ul {
			font: 7pt/1.5 Verdana, sans-serif !important;
			padding: 1em 2em 50px;
		}

		#netteBluescreen .highlight, #netteBluescreenError {
			background: red;
			color: white;
			font-weight: bold;
			font-style: normal;
			display: block;
		}

		#netteBluescreen .line {
			color: #9e9e7e;
			font-weight: normal;
			font-style: normal;
		}

	/* ]]> */
	</style>


	<script type="text/javascript">
	/* <![CDATA[ */
		document.write('<style> .collapsed { display: none; } <\/style>');

		function netteToggle(link, panelId)
		{
			var arrow = link.getElementsByTagName('abbr')[0];
			var panel = panelId ? document.getElementById(panelId) : link.nextSibling;
			while (panel.nodeType !== 1) panel = panel.nextSibling;
			var collapsed = panel.currentStyle ? panel.currentStyle.display == 'none' : getComputedStyle(panel, null).display == 'none';

			arrow.innerHTML = String.fromCharCode(collapsed ? 0x25bc : 0x25ba);
			panel.style.display = collapsed ? (panel.tagName.toLowerCase() === 'code' ? 'inline' : 'block') : 'none';

			return true;
		}
	/* ]]> */
	</script>
</head>



<body>
<div id="netteBluescreen">
	<a id="netteBluescreenIcon" href="#" onclick="return !netteToggle(this)"><abbr>&#x25bc;</abbr></a

	><div>
		<div id="netteBluescreenError" class="panel">
			<h1><?php echo
htmlspecialchars($title),($exception->getCode()?' #'.$exception->getCode():'')?></h1>

			<p><?php echo
htmlspecialchars($exception->getMessage())?></p>
		</div>



		<?php $ex=$exception;$level=0;?>
		<?php do{?>

			<?php if($level++):?>
				<?php _netteOpenPanel('Caused by',TRUE)?>
				<div class="panel">
					<h1><?php echo
htmlspecialchars(get_class($ex)),($ex->getCode()?' #'.$ex->getCode():'')?></h1>

					<p><?php echo
htmlspecialchars($ex->getMessage())?></p>
				</div>
			<?php endif?>

			<?php $collapsed=isset($internals[$ex->getFile()]);?>
			<?php if(is_file($ex->getFile())):?>
			<?php _netteOpenPanel('Source file',$collapsed)?>
				<p><strong>File:</strong> <?php echo
htmlspecialchars($ex->getFile())?> &nbsp; <strong>Line:</strong> <?php echo$ex->getLine()?></p>
				<pre><?php _netteDebugPrintCode($ex->getFile(),$ex->getLine())?></pre>
			<?php _netteClosePanel()?>
			<?php endif?>



			<?php _netteOpenPanel('Call stack',FALSE)?>
				<ol>
					<?php foreach($ex->getTrace()as$key=>$row):?>
					<li><p>

					<?php if(isset($row['file'])):?>
						<span title="<?php echo
htmlSpecialChars($row['file'])?>"><?php echo
htmlSpecialChars(basename(dirname($row['file']))),'/<b>',htmlSpecialChars(basename($row['file'])),'</b></span> (',$row['line'],')'?>
					<?php else:?>
						&lt;PHP inner-code&gt;
					<?php endif?>

					<?php if(isset($row['file'])&&is_file($row['file'])):?><a href="#" onclick="return !netteToggle(this, 'src<?php echo"$level-$key"?>')">source <abbr>&#x25ba;</abbr></a>&nbsp; <?php endif?>

					<?php if(isset($row['class']))echo$row['class'].$row['type']?>
					<?php echo$row['function']?>

					(<?php if(!empty($row['args'])):?><a href="#" onclick="return !netteToggle(this, 'args<?php echo"$level-$key"?>')">arguments <abbr>&#x25ba;</abbr></a><?php endif?>)
					</p>

					<?php if(!empty($row['args'])):?>
						<div class="collapsed" id="args<?php echo"$level-$key"?>">
						<table>
						<?php

try{$r=isset($row['class'])?new
ReflectionMethod($row['class'],$row['function']):new
ReflectionFunction($row['function']);$params=$r->getParameters();}catch(Exception$e){$params=array();}foreach($row['args']as$k=>$v){echo'<tr><th>',(isset($params[$k])?'$'.$params[$k]->name:"#$k"),'</th><td>';echo
_netteDump($v);echo"</td></tr>\n";}?>
						</table>
						</div>
					<?php endif?>


					<?php if(isset($row['file'])&&is_file($row['file'])):?>
						<pre <?php if(!$collapsed||isset($internals[$row['file']]))echo'class="collapsed"';else$collapsed=FALSE?> id="src<?php echo"$level-$key"?>"><?php _netteDebugPrintCode($row['file'],$row['line'])?></pre>
					<?php endif?>

					</li>
					<?php endforeach?>

					<?php if(!isset($row)):?>
					<li><i>empty</i></li>
					<?php endif?>
				</ol>
			<?php _netteClosePanel()?>



			<?php if($ex
instanceof
IDebuggable):?>
			<?php foreach($ex->getPanels()as$name=>$panel):?>
			<?php _netteOpenPanel($name,empty($panel['expanded']))?>
				<?php echo$panel['content']?>
			<?php _netteClosePanel()?>
			<?php endforeach?>
			<?php endif?>



			<?php if(isset($ex->context)&&is_array($ex->context)):?>
			<?php _netteOpenPanel('Variables',TRUE)?>
			<table>
			<?php

foreach($ex->context
as$k=>$v){echo'<tr><th>$',htmlspecialchars($k),'</th><td>',_netteDump($v),"</td></tr>\n";}?>
			</table>
			<?php _netteClosePanel()?>
			<?php endif?>

		<?php }while((method_exists($ex,'getPrevious')&&$ex=$ex->getPrevious())||(isset($ex->previous)&&$ex=$ex->previous));?>
		<?php while(--$level)_netteClosePanel()?>



		<?php _netteOpenPanel('Environment',TRUE)?>
			<?php
$list=get_defined_constants(TRUE);if(!empty($list['user'])):?>
			<h3><a href="#" onclick="return !netteToggle(this, 'pnl-env-const')">Constants <abbr>&#x25bc;</abbr></a></h3>
			<table id="pnl-env-const">
			<?php

foreach($list['user']as$k=>$v){echo'<tr'.($rn++%2?' class="odd"':'').'><th>',htmlspecialchars($k),'</th>';echo'<td>',_netteDump($v),"</td></tr>\n";}?>
			</table>
			<?php endif?>


			<h3><a href="#" onclick="return !netteToggle(this, 'pnl-env-files')">Included files <abbr>&#x25ba;</abbr></a>(<?php echo
count(get_included_files())?>)</h3>
			<table id="pnl-env-files" class="collapsed">
			<?php

foreach(get_included_files()as$v){echo'<tr'.($rn++%2?' class="odd"':'').'><td>',htmlspecialchars($v),"</td></tr>\n";}?>
			</table>


			<h3>$_SERVER</h3>
			<?php if(empty($_SERVER)):?>
			<p><i>empty</i></p>
			<?php else:?>
			<table>
			<?php

foreach($_SERVER
as$k=>$v)echo'<tr'.($rn++%2?' class="odd"':'').'><th>',htmlspecialchars($k),'</th><td>',_netteDump($v),"</td></tr>\n";?>
			</table>
			<?php endif?>
		<?php _netteClosePanel()?>



		<?php _netteOpenPanel('HTTP request',TRUE)?>
			<?php if(function_exists('apache_request_headers')):?>
			<h3>Headers</h3>
			<table>
			<?php

foreach(apache_request_headers()as$k=>$v)echo'<tr'.($rn++%2?' class="odd"':'').'><th>',htmlspecialchars($k),'</th><td>',htmlspecialchars($v),"</td></tr>\n";?>
			</table>
			<?php endif?>


			<?php foreach(array('_GET','_POST','_COOKIE')as$name):?>
			<h3>$<?php echo$name?></h3>
			<?php if(empty($GLOBALS[$name])):?>
			<p><i>empty</i></p>
			<?php else:?>
			<table>
			<?php

foreach($GLOBALS[$name]as$k=>$v)echo'<tr'.($rn++%2?' class="odd"':'').'><th>',htmlspecialchars($k),'</th><td>',_netteDump($v),"</td></tr>\n";?>
			</table>
			<?php endif?>
			<?php endforeach?>
		<?php _netteClosePanel()?>



		<?php _netteOpenPanel('HTTP response',TRUE)?>
			<h3>Headers</h3>
			<?php if(headers_list()):?>
			<pre><?php

foreach(headers_list()as$s)echo
htmlspecialchars($s),'<br>';?></pre>
			<?php else:?>
			<p><i>no headers</i></p>
			<?php endif?>
		<?php _netteClosePanel()?>


		<ul>
			<?php foreach($colophons
as$callback):?>
			<?php foreach((array)call_user_func($callback,'bluescreen')as$line):?><li><?php echo$line,"\n"?></li><?php endforeach?>
			<?php endforeach?>
		</ul>
	</div>
</div>

<script type="text/javascript">
	document.body.appendChild(document.getElementById('netteBluescreen'));
</script>
</body>
</html><?php }public
static
function
writeFile($buffer){fwrite(self::$logHandle,$buffer);}private
static
function
sendEmail($message){$monitorFile=self::$logFile.'.monitor';if(!is_file($monitorFile)){if(@file_put_contents($monitorFile,'e-mail has been sent')){call_user_func(self::$mailer,$message);}}}private
static
function
defaultMailer($message){$host=isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:(isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:'');$headers=str_replace(array('%host%','%date%','%message%'),array($host,@date('Y-m-d H:i:s',Debug::$time),$message),self::$emailHeaders);$subject=$headers['Subject'];$to=$headers['To'];$body=$headers['Body'];unset($headers['Subject'],$headers['To'],$headers['Body']);$header='';foreach($headers
as$key=>$value){$header.="$key: $value\r\n";}$body=str_replace("\r\n","\n",$body);if(PHP_OS!='Linux')$body=str_replace("\n","\r\n",$body);mail($to,$subject,$body,$header);}public
static
function
enableProfiler(){self::$enabledProfiler=TRUE;}public
static
function
disableProfiler(){self::$enabledProfiler=FALSE;}public
static
function
addColophon($callback){fixCallback($callback);if(!is_callable($callback)){$able=is_callable($callback,TRUE,$textual);throw
new
InvalidArgumentException("Colophon handler '$textual' is not ".($able?'callable.':'valid PHP callback.'));}if(!in_array($callback,self::$colophons,TRUE)){self::$colophons[]=$callback;}}public
static
function
getDefaultColophons($sender){if($sender==='profiler'){$arr[]='Elapsed time: <b>'.number_format((microtime(TRUE)-Debug::$time)*1000,1,'.',' ').'</b> ms | Allocated memory: <b>'.number_format(memory_get_peak_usage()/1000,1,'.',' ').'</b> kB';foreach((array)self::$counters
as$name=>$value){if(is_array($value))$value=implode(', ',$value);$arr[]=htmlSpecialChars($name).' = <strong>'.htmlSpecialChars($value).'</strong>';}$autoloaded=class_exists('AutoLoader',FALSE)?AutoLoader::$count:0;$s='<span>'.count(get_included_files()).'/'.$autoloaded.' files</span>, ';$exclude=array('stdClass','Exception','ErrorException','Traversable','IteratorAggregate','Iterator','ArrayAccess','Serializable','Closure');foreach(get_loaded_extensions()as$ext){$ref=new
ReflectionExtension($ext);$exclude=array_merge($exclude,$ref->getClassNames());}$classes=array_diff(get_declared_classes(),$exclude);$intf=array_diff(get_declared_interfaces(),$exclude);$func=get_defined_functions();$func=(array)@$func['user'];$consts=get_defined_constants(TRUE);$consts=array_keys((array)@$consts['user']);foreach(array('classes','intf','func','consts')as$item){$s.='<span '.($$item?'title="'.implode(", ",$$item).'"':'').'>'.count($$item).' '.$item.'</span>, ';}$arr[]=$s;}if($sender==='bluescreen'){$arr[]='Report generated at '.@date('Y/m/d H:i:s',Debug::$time);if(isset($_SERVER['HTTP_HOST'],$_SERVER['REQUEST_URI'])){$url=(isset($_SERVER['HTTPS'])&&strcasecmp($_SERVER['HTTPS'],'off')?'https://':'http://').htmlSpecialChars($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);$arr[]='<a href="'.$url.'">'.$url.'</a>';}$arr[]='PHP '.htmlSpecialChars(PHP_VERSION);if(isset($_SERVER['SERVER_SOFTWARE']))$arr[]=htmlSpecialChars($_SERVER['SERVER_SOFTWARE']);$arr[]=htmlSpecialChars(Framework::NAME.' '.Framework::VERSION).' <i>(revision '.htmlSpecialChars(Framework::REVISION).')</i>';}return$arr;}public
static
function
fireDump($var,$key){self::fireSend(2,array((string)$key=>$var));return$var;}public
static
function
fireLog($message,$priority=self::LOG,$label=NULL){if($message
instanceof
Exception){if($priority!==self::EXCEPTION&&$priority!==self::TRACE){$priority=self::TRACE;}$message=array('Class'=>get_class($message),'Message'=>$message->getMessage(),'File'=>$message->getFile(),'Line'=>$message->getLine(),'Trace'=>$message->getTrace(),'Type'=>'','Function'=>'');foreach($message['Trace']as&$row){if(empty($row['file']))$row['file']='?';if(empty($row['line']))$row['line']='?';}}elseif($priority===self::GROUP_START){$label=$message;$message=NULL;}return
self::fireSend(1,self::replaceObjects(array(array('Type'=>$priority,'Label'=>$label),$message)));}private
static
function
fireSend($index,$payload){if(self::$productionMode)return
NULL;if(headers_sent())return
FALSE;header('X-Wf-Protocol-nette: http://meta.wildfirehq.org/Protocol/JsonStream/0.2');header('X-Wf-nette-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.2.0');if($index===1){header('X-Wf-nette-Structure-1: http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1');}elseif($index===2){header('X-Wf-nette-Structure-2: http://meta.firephp.org/Wildfire/Structure/FirePHP/Dump/0.1');}$payload=json_encode($payload);static$counter;foreach(str_split($payload,4990)as$s){$num=++$counter;header("X-Wf-nette-$index-1-n$num: |$s|\\");}header("X-Wf-nette-$index-1-n$num: |$s|");return
TRUE;}static
private
function
replaceObjects($val){if(is_object($val)){return'object '.get_class($val).'';}elseif(is_string($val)){return@iconv('UTF-16','UTF-8//IGNORE',iconv('UTF-8','UTF-16//IGNORE',$val));}elseif(is_array($val)){foreach($val
as$k=>$v){unset($val[$k]);$k=@iconv('UTF-16','UTF-8//IGNORE',iconv('UTF-8','UTF-16//IGNORE',$k));$val[$k]=self::replaceObjects($v);}}return$val;}}Debug::init();