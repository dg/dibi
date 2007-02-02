<?php
 /**
 * dibi - Database Abstraction Layer according to dgx
 * --------------------------------------------------
 *
 * For PHP 5.0.3 or newer
 *
 * This source file is subject to the GNU GPL license.
 *
 * @author     David Grudl aka -dgx- <dave@dgx.cz>
 * @link       http://dibi.texy.info/
 * @copyright  Copyright (c) 2005-2007 David Grudl
 * @license    GNU GENERAL PUBLIC LICENSE v2
 * @package    dibi
 * @category   Database
 * @version    0.7c $Revision: 27 $ $Date: 2007-01-30 22:50:04 +0100 (út, 30 I 2007) $
 */


define('DIBI','Version 0.7c $Revision: 27 $');if(version_compare(PHP_VERSION,'5.0.3','<'))die('dibi needs PHP 5.0.3 or newer');abstract
class
DibiDriver{protected$config;public$formats=array('TRUE'=>"1",'FALSE'=>"0",'date'=>"'Y-m-d'",'datetime'=>"'Y-m-d H:i:s'",);static
public
function
connect($config){}protected
function
__construct($config){$this->config=$config;}public
function
getConfig(){return$this->config;}abstract
public
function
query($sql);abstract
public
function
affectedRows();abstract
public
function
insertId();abstract
public
function
begin();abstract
public
function
commit();abstract
public
function
rollback();abstract
public
function
errorInfo();abstract
public
function
escape($value,$appendQuotes=FALSE);abstract
public
function
quoteName($value);abstract
public
function
getMetaData();abstract
public
function
applyLimit(&$sql,$limit,$offset=0);function
__get($nm){throw
new
Exception("Undefined property '".get_class($this)."::$$nm'");}function
__set($nm,$val){$this->__get($nm);}private
function
__unset($nm){$this->__get($nm);}private
function
__isset($nm){$this->__get($nm);}}if(!interface_exists('Countable',false)){interface
Countable{function
count();}}abstract
class
DibiResult
implements
IteratorAggregate,Countable{protected$convert;static
private$meta=array(dibi::FIELD_TEXT=>'string',dibi::FIELD_BINARY=>'string',dibi::FIELD_BOOL=>'bool',dibi::FIELD_INTEGER=>'int',dibi::FIELD_FLOAT=>'float',dibi::FIELD_COUNTER=>'int',);abstract
public
function
seek($row);abstract
public
function
rowCount();abstract
public
function
getFields();abstract
public
function
getMetaData($field);abstract
protected
function
detectTypes();abstract
protected
function
free();abstract
protected
function
doFetch();final
public
function
fetch(){$rec=$this->doFetch();if(!is_array($rec))return
FALSE;if($t=$this->convert){foreach($rec
as$key=>$value){if(isset($t[$key]))$rec[$key]=$this->convert($value,$t[$key]);}}return$rec;}final
function
fetchSingle(){$rec=$this->doFetch();if(!is_array($rec))return
FALSE;if($t=$this->convert){$value=reset($rec);$key=key($rec);return
isset($t[$key])?$this->convert($value,$t[$key]):$value;}return
reset($rec);}final
function
fetchAll(){@$this->seek(0);$rec=$this->fetch();if(!$rec)return
array();$arr=array();if(count($rec)==1){$key=key($rec);do{$arr[]=$rec[$key];}while($rec=$this->fetch());}else{do{$arr[]=$rec;}while($rec=$this->fetch());}return$arr;}final
function
fetchAssoc($assocBy){@$this->seek(0);$rec=$this->fetch();if(!$rec)return
array();$assocBy=func_get_args();foreach($assocBy
as$n=>$assoc)if(!array_key_exists($assoc,$rec))unset($assocBy[$n]);$arr=array();do{foreach($assocBy
as$n=>$assoc){$val[$n]=$rec[$assoc];unset($rec[$assoc]);}foreach($assocBy
as$n=>$assoc){if($n==0)$tmp=&$arr[$val[$n]];else$tmp=&$tmp[$assoc][$val[$n]];if($tmp===NULL)$tmp=$rec;}}while($rec=$this->fetch());return$arr;}final
function
fetchPairs($key,$value){@$this->seek(0);$rec=$this->fetch();if(!$rec)return
array();if(!array_key_exists($key,$rec)||!array_key_exists($value,$rec))return
FALSE;$arr=array();do{$arr[$rec[$key]]=$rec[$value];}while($rec=$this->fetch());return$arr;}public
function
__destruct(){@$this->free();}public
function
setType($field,$type=NULL){if($field===TRUE)$this->detectTypes();elseif(is_array($field))$this->convert=$field;else$this->convert[$field]=$type;}public
function
getType($field){return
isset($this->convert[$field])?$this->convert[$field]:NULL;}public
function
convert($value,$type){if($value===NULL||$value===FALSE)return$value;if(isset(self::$meta[$type])){settype($value,self::$meta[$type]);return$value;}if($type==dibi::FIELD_DATE)return
strtotime($value);if($type==dibi::FIELD_DATETIME)return
strtotime($value);return$value;}public
function
getIterator($offset=NULL,$count=NULL){return
new
DibiResultIterator($this,$offset,$count);}public
function
count(){return$this->rowCount();}function
__get($nm){throw
new
Exception("Undefined property '".get_class($this)."::$$nm'");}function
__set($nm,$val){$this->__get($nm);}private
function
__unset($nm){$this->__get($nm);}private
function
__isset($nm){$this->__get($nm);}}class
DibiResultIterator
implements
Iterator{private$result,$offset,$count,$record,$row;public
function
__construct(DibiResult$result,$offset=NULL,$count=NULL){$this->result=$result;$this->offset=(int)$offset;$this->count=$count===NULL?2147483647:(int)$count;}public
function
rewind(){$this->row=0;@$this->result->seek($this->offset);$this->record=$this->result->fetch();}public
function
key(){return$this->row;}public
function
current(){return$this->record;}public
function
next(){$this->record=$this->result->fetch();$this->row++;}public
function
valid(){return
is_array($this->record)&&($this->row<$this->count);}}class
DibiTranslator{private$driver,$subK,$subV,$modifier,$hasError,$comment,$ifLevel,$ifLevelStart;public$sql;public
function
__construct($driver,$subst){$this->driver=$driver;$this->subK=array_keys($subst);$this->subV=array_values($subst);}public
function
translate($args){$this->hasError=FALSE;$command=null;$mod=&$this->modifier;$mod=FALSE;$this->ifLevel=$this->ifLevelStart=0;$comment=&$this->comment;$comment=FALSE;$sql=array();foreach($args
as$arg){if('if'==$mod){$mod=FALSE;$this->ifLevel++;if(!$comment&&!$arg){$sql[]="\0";$this->ifLevelStart=$this->ifLevel;$comment=TRUE;}continue;}if(is_string($arg)&&(!$mod||'sql'==$mod)){$mod=FALSE;$sql[]=$this->formatValue($arg,'sql');continue;}if(!$mod&&is_array($arg)&&is_string(key($arg))){if(!$command)$command=strtoupper(substr(ltrim($args[0]),0,6));$mod=('INSERT'==$command||'REPLAC'==$command)?'v':'a';}if(!$comment)$sql[]=$this->formatValue($arg,$mod);$mod=FALSE;}if($comment)$sql[]="\0";$sql=implode(' ',$sql);$sql=preg_replace('#\x00.*?\x00#s','',$sql);$this->sql=$sql;return!$this->hasError;}private
function
formatValue($value,$modifier){if(is_array($value)){$vx=$kx=array();switch($modifier){case'a':foreach($value
as$k=>$v){$pair=explode('%',$k,2);$vx[]=$this->quote($pair[0]).'='.$this->formatValue($v,isset($pair[1])?$pair[1]:FALSE);}return
implode(', ',$vx);case'v':foreach($value
as$k=>$v){$pair=explode('%',$k,2);$kx[]=$this->quote($pair[0]);$vx[]=$this->formatValue($v,isset($pair[1])?$pair[1]:FALSE);}return'('.implode(', ',$kx).') VALUES ('.implode(', ',$vx).')';default:foreach($value
as$v)$vx[]=$this->formatValue($v,$modifier);return
implode(', ',$vx);}}if($modifier){if($value===NULL)return'NULL';if($value
instanceof
IDibiVariable)return$value->toSql($this->driver,$modifier);if(!is_scalar($value)){$this->hasError=TRUE;return'**Unexpected '.gettype($value).'**';}switch($modifier){case's':return$this->driver->escape($value,TRUE);case'sn':return$value==''?'NULL':$this->driver->escape($value,TRUE);case'b':return$value?$this->driver->formats['TRUE']:$this->driver->formats['FALSE'];case'i':case'u':return(string)(int)$value;case'f':return(string)(float)$value;case'd':return
date($this->driver->formats['date'],is_string($value)?strtotime($value):$value);case't':return
date($this->driver->formats['datetime'],is_string($value)?strtotime($value):$value);case'n':return$this->quote($value);case'sql':case'p':$value=(string)$value;$toSkip=strcspn($value,'`[\'"%');if(strlen($value)==$toSkip)return$value;return
substr($value,0,$toSkip).preg_replace_callback('/(?=`|\[|\'|"|%)(?:`(.+?)`|\[(.+?)\]|(\')((?:\'\'|[^\'])*)\'|(")((?:""|[^"])*)"|%(else|end)|%([a-zA-Z]{1,3})$|(\'|"))/s',array($this,'cb'),substr($value,$toSkip));case'a':case'v':$this->hasError=TRUE;return"**Unexpected ".gettype($value)."**";case'if':$this->hasError=TRUE;return"**The %$modifier is not allowed here**";default:$this->hasError=TRUE;return"**Unknown modifier %$modifier**";}}if(is_string($value))return$this->driver->escape($value,TRUE);if(is_int($value)||is_float($value))return(string)$value;if(is_bool($value))return$value?$this->driver->formats['TRUE']:$this->driver->formats['FALSE'];if($value===NULL)return'NULL';if($value
instanceof
IDibiVariable)return$value->toSql($this->driver);$this->hasError=TRUE;return'**Unexpected '.gettype($value).'**';}private
function
cb($matches){if(!empty($matches[7])){if(!$this->ifLevel){$this->hasError=TRUE;return"**Unexpected condition $matches[7]**";}if('end'==$matches[7]){$this->ifLevel--;if($this->ifLevelStart==$this->ifLevel+1){$this->ifLevelStart=0;$this->comment=FALSE;return"\0";}return'';}if($this->ifLevelStart==$this->ifLevel){$this->ifLevelStart=0;$this->comment=FALSE;return"\0";}elseif(!$this->comment){$this->ifLevelStart=$this->ifLevel;$this->comment=TRUE;return"\0";}}if(!empty($matches[8])){$this->modifier=$matches[8];return'';}if($this->comment)return'';if($matches[1])return$this->quote($matches[1]);if($matches[2])return$this->quote($matches[2]);if($matches[3])return$this->driver->escape(str_replace("''","'",$matches[4]),TRUE);if($matches[5])return$this->driver->escape(str_replace('""','"',$matches[6]),TRUE);if($matches[9]){$this->hasError=TRUE;return'**Alone quote**';}die('this should be never executed');}private
function
quote($value){if($this->subK&&(strpos($value,':')!==FALSE))return
str_replace($this->subK,$this->subV,$value);return$this->driver->quoteName($value);}function
__get($nm){throw
new
Exception("Undefined property '".get_class($this)."::$$nm'");}function
__set($nm,$val){$this->__get($nm);}private
function
__unset($nm){$this->__get($nm);}private
function
__isset($nm){$this->__get($nm);}}class
DibiException
extends
Exception{private$sql,$dbError;public
function
__construct($message,$dbError=NULL,$sql=NULL){$this->dbError=$dbError;$this->sql=$sql;parent::__construct($message);}public
function
getSql(){return$this->sql;}public
function
getDbError(){return$this->dbError;}public
function
__toString(){$s=parent::__toString();if($this->dbError){$s.="\nERROR: ";if(isset($this->dbError['code']))$s.="[".$this->dbError['code']."] ";$s.=$this->dbError['message'];}if($this->sql)$s.="\nSQL: ".$this->sql;return$s;}} 
interface
IDibiVariable{public
function
toSQL($driver,$modifier=NULL);}class
dibi{const
FIELD_TEXT='s',FIELD_BINARY='S',FIELD_BOOL='b',FIELD_INTEGER='i',FIELD_FLOAT='f',FIELD_DATE='d',FIELD_DATETIME='t',FIELD_UNKNOWN='?',FIELD_COUNTER='c';static
private$registry=array();static
private$conn;static
public$sql;static
public$logFile;static
public$logMode='a';static
public$logAll=FALSE;static
public$throwExceptions=FALSE;static
private$substs=array();private
function
__construct(){}static
public
function
connect($config,$name='1'){if(is_string($config))parse_str($config,$config);if(empty($config['driver']))throw
new
DibiException('Driver is not specified.');$className="Dibi$config[driver]Driver";if(!class_exists($className)){include_once
dirname(__FILE__)."/drivers/$config[driver].php";if(!class_exists($className))throw
new
DibiException("Unable to create instance of dibi driver class '$className'.");}self::$conn=self::$registry[$name]=call_user_func(array($className,'connect'),$config);if(dibi::$logAll)dibi::log("OK: connected to DB '$config[driver]'");}static
public
function
isConnected(){return(bool)self::$conn;}static
public
function
getConnection(){if(!self::$conn)throw
new
DibiException('Dibi is not connected to database');return
self::$conn;}static
public
function
activate($name){if(!isset(self::$registry[$name]))throw
new
DibiException("There is no connection named '$name'.");self::$conn=self::$registry[$name];}static
public
function
query($args){$conn=self::getConnection();if(!is_array($args))$args=func_get_args();$trans=new
DibiTranslator($conn,self::$substs);if(!$trans->translate($args)){if(self::$logFile)self::log("ERROR: SQL generate error"."\n-- SQL: ".$trans->sql.";\n-- ".date('Y-m-d H:i:s '));if(dibi::$throwExceptions)throw
new
DibiException('SQL generate error',NULL,$trans->sql);else{trigger_error("dibi: SQL generate error: $trans->sql",E_USER_WARNING);return
FALSE;}}self::$sql=$trans->sql;$timer=-microtime(true);$res=$conn->query(self::$sql);if($res===FALSE){if(self::$logFile){$info=$conn->errorInfo();if($info['code'])$info['message']="[$info[code]] $info[message]";self::log("ERROR: $info[message]"."\n-- SQL: ".self::$sql.";\n-- ".date('Y-m-d H:i:s '));}if(dibi::$throwExceptions){$info=$conn->errorInfo();throw
new
DibiException('Query error',$info,self::$sql);}else{$info=$conn->errorInfo();if($info['code'])$info['message']="[$info[code]] $info[message]";trigger_error("dibi: $info[message]",E_USER_WARNING);return
FALSE;}}if(self::$logFile&&self::$logAll){$timer+=microtime(true);$msg=$res
instanceof
DibiResult?'object('.get_class($res).') rows: '.$res->rowCount():'OK';self::log("OK: ".self::$sql.";\n-- result: $msg"."\n-- takes: ".sprintf('%0.3f',$timer*1000).' ms'."\n-- ".date('Y-m-d H:i:s '));}return$res;}static
public
function
test($args){if(!is_array($args))$args=func_get_args();$trans=new
DibiTranslator(self::getConnection(),self::$substs);$ok=$trans->translate($args);if(!$ok)echo'ERROR: ';self::dump($trans->sql);return$ok;}static
public
function
insertId(){return
self::getConnection()->insertId();}static
public
function
affectedRows(){return
self::getConnection()->affectedRows();}static
private
function
dumpHighlight($matches){if(!empty($matches[1]))return'<em style="color:gray">'.$matches[1].'</em>';if(!empty($matches[2]))return'<strong style="color:red">'.$matches[2].'</strong>';if(!empty($matches[3]))return'<strong style="color:blue">'.$matches[3].'</strong>';if(!empty($matches[4]))return'<strong style="color:green">'.$matches[4].'</strong>';}static
public
function
dump($sql,$return=FALSE){static$keywords2='ALL|DISTINCT|AS|ON|INTO|AND|OR|AS';static$keywords1='SELECT|UPDATE|INSERT|DELETE|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN';$sql=preg_replace("#\\b(?:$keywords1)\\b#","\n\$0",$sql);$sql=trim($sql);$sql=preg_replace('# {2,}#',' ',$sql);$sql=wordwrap($sql,100);$sql=htmlSpecialChars($sql);$sql=preg_replace("#\n{2,}#","\n",$sql);$sql=preg_replace_callback("#(/\*.+?\*/)|(\*\*.+?\*\*)|\\b($keywords1)\\b|\\b($keywords2)\\b#",array('dibi','dumpHighlight'),$sql);$sql='<pre class="dump">'.$sql."</pre>\n";if(!$return)echo$sql;return$sql;}static
public
function
dumpResult(DibiResult$res){echo'<table class="dump"><tr>';echo'<th>#row</th>';foreach($res->getFields()as$field)echo'<th>'.$field.'</th>';echo'</tr>';foreach($res
as$row=>$fields){echo'<tr><th>',$row,'</th>';foreach($fields
as$field){if(is_object($field))$field=$field->__toString();echo'<td>',htmlSpecialChars($field),'</td>';}echo'</tr>';}echo'</table>';}static
public
function
addSubst($expr,$subst){self::$substs[':'.$expr.':']=$subst;}static
public
function
removeSubst($expr){unset(self::$substs[':'.$expr.':']);}static
public
function
log($message){if(self::$logFile==NULL||self::$logMode==NULL)return;$f=fopen(self::$logFile,self::$logMode);if(!$f)return;flock($f,LOCK_EX);fwrite($f,$message."\n\n");fclose($f);}}class
DibiMySqlDriver
extends
DibiDriver{private$conn,$insertId=FALSE,$affectedRows=FALSE;public$formats=array('TRUE'=>"1",'FALSE'=>"0",'date'=>"'Y-m-d'",'datetime'=>"'Y-m-d H:i:s'",);public
static
function
connect($config){if(!extension_loaded('mysql'))throw
new
DibiException("PHP extension 'mysql' is not loaded");foreach(array('username','password','protocol')as$var)if(!isset($config[$var]))$config[$var]=NULL;if(empty($config['host']))$config['host']='localhost';if($config['protocol']==='unix')$host=':'.$config['host'];else$host=$config['host'].(empty($config['port'])?'':':'.$config['port']);if(function_exists('ini_set'))$save=ini_set('track_errors',TRUE);$php_errormsg='';if(empty($config['persistent']))$conn=@mysql_connect($host,$config['username'],$config['password']);else$conn=@mysql_pconnect($host,$config['username'],$config['password']);if(function_exists('ini_set'))ini_set('track_errors',$save);if(!is_resource($conn))throw
new
DibiException("Connecting error",array('message'=>mysql_error()?mysql_error():$php_errormsg,'code'=>mysql_errno(),));if(!empty($config['charset'])){$succ=@mysql_query("SET NAMES '".$config['charset']."'",$conn);}if(!empty($config['database'])){if(!@mysql_select_db($config['database'],$conn))throw
new
DibiException("Connecting error",array('message'=>mysql_error($conn),'code'=>mysql_errno($conn),));}$obj=new
self($config);$obj->conn=$conn;return$obj;}public
function
query($sql){$this->insertId=$this->affectedRows=FALSE;$res=@mysql_query($sql,$this->conn);if($res===FALSE)return
FALSE;if(is_resource($res))return
new
DibiMySqlResult($res);$this->affectedRows=mysql_affected_rows($this->conn);if($this->affectedRows<0)$this->affectedRows=FALSE;$this->insertId=mysql_insert_id($this->conn);if($this->insertId<1)$this->insertId=FALSE;return
TRUE;}public
function
affectedRows(){return$this->affectedRows;}public
function
insertId(){return$this->insertId;}public
function
begin(){return
mysql_query('BEGIN',$this->conn);}public
function
commit(){return
mysql_query('COMMIT',$this->conn);}public
function
rollback(){return
mysql_query('ROLLBACK',$this->conn);}public
function
errorInfo(){return
array('message'=>mysql_error($this->conn),'code'=>mysql_errno($this->conn),);}public
function
escape($value,$appendQuotes=FALSE){return$appendQuotes?"'".mysql_real_escape_string($value,$this->conn)."'":mysql_real_escape_string($value,$this->conn);}public
function
quoteName($value){return'`'.str_replace('.','`.`',$value).'`';}public
function
getMetaData(){trigger_error('Meta is not implemented yet.',E_USER_WARNING);}public
function
applyLimit(&$sql,$limit,$offset=0){if($limit<0&&$offset<1)return;$sql.=' LIMIT '.($limit<0?'18446744073709551615':(int)$limit).($offset>0?' OFFSET '.(int)$offset:'');}}class
DibiMySqlResult
extends
DibiResult{private$resource,$meta;public
function
__construct($resource){$this->resource=$resource;}public
function
rowCount(){return
mysql_num_rows($this->resource);}protected
function
doFetch(){return
mysql_fetch_assoc($this->resource);}public
function
seek($row){return
mysql_data_seek($this->resource,$row);}protected
function
free(){mysql_free_result($this->resource);}public
function
getFields(){if($this->meta===NULL)$this->createMeta();return
array_keys($this->meta);}protected
function
detectTypes(){if($this->meta===NULL)$this->createMeta();}public
function
getMetaData($field){if($this->meta===NULL)$this->createMeta();return
isset($this->meta[$field])?$this->meta[$field]:FALSE;}private
function
createMeta(){static$types=array('ENUM'=>dibi::FIELD_TEXT,'SET'=>dibi::FIELD_TEXT,'CHAR'=>dibi::FIELD_TEXT,'VARCHAR'=>dibi::FIELD_TEXT,'STRING'=>dibi::FIELD_TEXT,'TINYTEXT'=>dibi::FIELD_TEXT,'TEXT'=>dibi::FIELD_TEXT,'MEDIUMTEXT'=>dibi::FIELD_TEXT,'LONGTEXT'=>dibi::FIELD_TEXT,'BINARY'=>dibi::FIELD_BINARY,'VARBINARY'=>dibi::FIELD_BINARY,'TINYBLOB'=>dibi::FIELD_BINARY,'BLOB'=>dibi::FIELD_BINARY,'MEDIUMBLOB'=>dibi::FIELD_BINARY,'LONGBLOB'=>dibi::FIELD_BINARY,'DATE'=>dibi::FIELD_DATE,'DATETIME'=>dibi::FIELD_DATETIME,'TIMESTAMP'=>dibi::FIELD_DATETIME,'TIME'=>dibi::FIELD_DATETIME,'BIT'=>dibi::FIELD_BOOL,'YEAR'=>dibi::FIELD_INTEGER,'TINYINT'=>dibi::FIELD_INTEGER,'SMALLINT'=>dibi::FIELD_INTEGER,'MEDIUMINT'=>dibi::FIELD_INTEGER,'INT'=>dibi::FIELD_INTEGER,'INTEGER'=>dibi::FIELD_INTEGER,'BIGINT'=>dibi::FIELD_INTEGER,'FLOAT'=>dibi::FIELD_FLOAT,'DOUBLE'=>dibi::FIELD_FLOAT,'REAL'=>dibi::FIELD_FLOAT,'DECIMAL'=>dibi::FIELD_FLOAT,'NUMERIC'=>dibi::FIELD_FLOAT,);$count=mysql_num_fields($this->resource);$this->meta=$this->convert=array();for($index=0;$index<$count;$index++){$info['native']=$native=strtoupper(mysql_field_type($this->resource,$index));$info['flags']=explode(' ',mysql_field_flags($this->resource,$index));$info['length']=mysql_field_len($this->resource,$index);$info['table']=mysql_field_table($this->resource,$index);if(in_array('auto_increment',$info['flags']))$info['type']=dibi::FIELD_COUNTER;else{$info['type']=isset($types[$native])?$types[$native]:dibi::FIELD_UNKNOWN;}$name=mysql_field_name($this->resource,$index);$this->meta[$name]=$info;$this->convert[$name]=$info['type'];}}}class
DibiMySqliDriver
extends
DibiDriver{private$conn,$insertId=FALSE,$affectedRows=FALSE;public$formats=array('TRUE'=>"1",'FALSE'=>"0",'date'=>"'Y-m-d'",'datetime'=>"'Y-m-d H:i:s'",);public
static
function
connect($config){if(!extension_loaded('mysqli'))throw
new
DibiException("PHP extension 'mysqli' is not loaded");if(empty($config['host']))$config['host']='localhost';foreach(array('username','password','database','port')as$var)if(!isset($config[$var]))$config[$var]=NULL;$conn=@mysqli_connect($config['host'],$config['username'],$config['password'],$config['database'],$config['port']);if(!$conn)throw
new
DibiException("Connecting error",array('message'=>mysqli_connect_error(),'code'=>mysqli_connect_errno(),));if(!empty($config['charset']))mysqli_query($conn,"SET NAMES '".$config['charset']."'");$obj=new
self($config);$obj->conn=$conn;return$obj;}public
function
query($sql){$this->insertId=$this->affectedRows=FALSE;$res=@mysqli_query($this->conn,$sql);if($res===FALSE)return
FALSE;if(is_object($res))return
new
DibiMySqliResult($res);$this->affectedRows=mysqli_affected_rows($this->conn);if($this->affectedRows<0)$this->affectedRows=FALSE;$this->insertId=mysqli_insert_id($this->conn);if($this->insertId<1)$this->insertId=FALSE;return
TRUE;}public
function
affectedRows(){return$this->affectedRows;}public
function
insertId(){return$this->insertId;}public
function
begin(){return
mysqli_autocommit($this->conn,FALSE);}public
function
commit(){$ok=mysqli_commit($this->conn);mysqli_autocommit($this->conn,TRUE);return$ok;}public
function
rollback(){$ok=mysqli_rollback($this->conn);mysqli_autocommit($this->conn,TRUE);return$ok;}public
function
errorInfo(){return
array('message'=>mysqli_error($this->conn),'code'=>mysqli_errno($this->conn),);}public
function
escape($value,$appendQuotes=FALSE){return$appendQuotes?"'".mysqli_real_escape_string($this->conn,$value)."'":mysqli_real_escape_string($this->conn,$value);}public
function
quoteName($value){return'`'.str_replace('.','`.`',$value).'`';}public
function
getMetaData(){trigger_error('Meta is not implemented yet.',E_USER_WARNING);}public
function
applyLimit(&$sql,$limit,$offset=0){if($limit<0&&$offset<1)return;$sql.=' LIMIT '.($limit<0?'18446744073709551615':(int)$limit).($offset>0?' OFFSET '.(int)$offset:'');}}class
DibiMySqliResult
extends
DibiResult{private$resource,$meta;public
function
__construct($resource){$this->resource=$resource;}public
function
rowCount(){return
mysqli_num_rows($this->resource);}protected
function
doFetch(){return
mysqli_fetch_assoc($this->resource);}public
function
seek($row){return
mysqli_data_seek($this->resource,$row);}protected
function
free(){mysqli_free_result($this->resource);}public
function
getFields(){if($this->meta===NULL)$this->createMeta();return
array_keys($this->meta);}protected
function
detectTypes(){if($this->meta===NULL)$this->createMeta();}public
function
getMetaData($field){if($this->meta===NULL)$this->createMeta();return
isset($this->meta[$field])?$this->meta[$field]:FALSE;}private
function
createMeta(){static$types=array(MYSQLI_TYPE_FLOAT=>dibi::FIELD_FLOAT,MYSQLI_TYPE_DOUBLE=>dibi::FIELD_FLOAT,MYSQLI_TYPE_DECIMAL=>dibi::FIELD_FLOAT,MYSQLI_TYPE_TINY=>dibi::FIELD_INTEGER,MYSQLI_TYPE_SHORT=>dibi::FIELD_INTEGER,MYSQLI_TYPE_LONG=>dibi::FIELD_INTEGER,MYSQLI_TYPE_LONGLONG=>dibi::FIELD_INTEGER,MYSQLI_TYPE_INT24=>dibi::FIELD_INTEGER,MYSQLI_TYPE_YEAR=>dibi::FIELD_INTEGER,MYSQLI_TYPE_GEOMETRY=>dibi::FIELD_INTEGER,MYSQLI_TYPE_DATE=>dibi::FIELD_DATE,MYSQLI_TYPE_NEWDATE=>dibi::FIELD_DATE,MYSQLI_TYPE_TIMESTAMP=>dibi::FIELD_DATETIME,MYSQLI_TYPE_TIME=>dibi::FIELD_DATETIME,MYSQLI_TYPE_DATETIME=>dibi::FIELD_DATETIME,MYSQLI_TYPE_ENUM=>dibi::FIELD_TEXT,MYSQLI_TYPE_SET=>dibi::FIELD_TEXT,MYSQLI_TYPE_STRING=>dibi::FIELD_TEXT,MYSQLI_TYPE_VAR_STRING=>dibi::FIELD_TEXT,MYSQLI_TYPE_TINY_BLOB=>dibi::FIELD_BINARY,MYSQLI_TYPE_MEDIUM_BLOB=>dibi::FIELD_BINARY,MYSQLI_TYPE_LONG_BLOB=>dibi::FIELD_BINARY,MYSQLI_TYPE_BLOB=>dibi::FIELD_BINARY,);$count=mysqli_num_fields($this->resource);$this->meta=$this->convert=array();for($index=0;$index<$count;$index++){$info=(array)mysqli_fetch_field_direct($this->resource,$index);$native=$info['native']=$info['type'];if($info['flags']&MYSQLI_AUTO_INCREMENT_FLAG)$info['type']=dibi::FIELD_COUNTER;else{$info['type']=isset($types[$native])?$types[$native]:dibi::FIELD_UNKNOWN;}$this->meta[$info['name']]=$info;$this->convert[$info['name']]=$info['type'];}}}class
DibiOdbcDriver
extends
DibiDriver{private$conn,$affectedRows=FALSE;public$formats=array('TRUE'=>"-1",'FALSE'=>"0",'date'=>"#m/d/Y#",'datetime'=>"#m/d/Y H:i:s#",);public
static
function
connect($config){if(!extension_loaded('odbc'))throw
new
DibiException("PHP extension 'odbc' is not loaded");if(!isset($config['username']))throw
new
DibiException("Username must be specified");if(!isset($config['password']))throw
new
DibiException("Password must be specified");if(empty($config['persistent']))$conn=@odbc_connect($config['database'],$config['username'],$config['password']);else$conn=@odbc_pconnect($config['database'],$config['username'],$config['password']);if(!is_resource($conn))throw
new
DibiException("Connecting error",array('message'=>odbc_errormsg(),'code'=>odbc_error(),));$obj=new
self($config);$obj->conn=$conn;return$obj;}public
function
query($sql){$this->affectedRows=FALSE;$res=@odbc_exec($this->conn,$sql);if($res===FALSE)return
FALSE;if(is_resource($res))return
new
DibiOdbcResult($res);$this->affectedRows=odbc_num_rows($this->conn);if($this->affectedRows<0)$this->affectedRows=FALSE;return
TRUE;}public
function
affectedRows(){return$this->affectedRows;}public
function
insertId(){return
FALSE;}public
function
begin(){return
odbc_autocommit($this->conn,FALSE);}public
function
commit(){$ok=odbc_commit($this->conn);odbc_autocommit($this->conn,TRUE);return$ok;}public
function
rollback(){$ok=odbc_rollback($this->conn);odbc_autocommit($this->conn,TRUE);return$ok;}public
function
errorInfo(){return
array('message'=>odbc_errormsg($this->conn),'code'=>odbc_error($this->conn),);}public
function
escape($value,$appendQuotes=FALSE){$value=str_replace("'","''",$value);return$appendQuotes?"'".$value."'":$value;}public
function
quoteName($value){return'['.str_replace('.','].[',$value).']';}public
function
getMetaData(){trigger_error('Meta is not implemented yet.',E_USER_WARNING);}public
function
applyLimit(&$sql,$limit,$offset=0){if($limit>=0)$sql='SELECT TOP '.(int)$limit.' * FROM ('.$sql.')';}}class
DibiOdbcResult
extends
DibiResult{private$resource,$meta,$row=0;public
function
__construct($resource){$this->resource=$resource;}public
function
rowCount(){return
odbc_num_rows($this->resource);}protected
function
doFetch(){return
odbc_fetch_array($this->resource,$this->row++);}public
function
seek($row){$this->row=$row;}protected
function
free(){odbc_free_result($this->resource);}public
function
getFields(){if($this->meta===NULL)$this->createMeta();return
array_keys($this->meta);}protected
function
detectTypes(){if($this->meta===NULL)$this->createMeta();}public
function
getMetaData($field){if($this->meta===NULL)$this->createMeta();return
isset($this->meta[$field])?$this->meta[$field]:FALSE;}private
function
createMeta(){if($this->meta!==NULL)return$this->meta;static$types=array('CHAR'=>dibi::FIELD_TEXT,'COUNTER'=>dibi::FIELD_COUNTER,'VARCHAR'=>dibi::FIELD_TEXT,'LONGCHAR'=>dibi::FIELD_TEXT,'INTEGER'=>dibi::FIELD_INTEGER,'DATETIME'=>dibi::FIELD_DATETIME,'CURRENCY'=>dibi::FIELD_FLOAT,'BIT'=>dibi::FIELD_BOOL,'LONGBINARY'=>dibi::FIELD_BINARY,'SMALLINT'=>dibi::FIELD_INTEGER,'BYTE'=>dibi::FIELD_INTEGER,'BIGINT'=>dibi::FIELD_INTEGER,'INT'=>dibi::FIELD_INTEGER,'TINYINT'=>dibi::FIELD_INTEGER,'REAL'=>dibi::FIELD_FLOAT,'DOUBLE'=>dibi::FIELD_FLOAT,'DECIMAL'=>dibi::FIELD_FLOAT,'NUMERIC'=>dibi::FIELD_FLOAT,'MONEY'=>dibi::FIELD_FLOAT,'SMALLMONEY'=>dibi::FIELD_FLOAT,'FLOAT'=>dibi::FIELD_FLOAT,'YESNO'=>dibi::FIELD_BOOL,);$count=odbc_num_fields($this->resource);$this->meta=$this->convert=array();for($index=1;$index<=$count;$index++){$native=strtoupper(odbc_field_type($this->resource,$index));$name=odbc_field_name($this->resource,$index);$this->meta[$name]=array('type'=>isset($types[$native])?$types[$native]:dibi::FIELD_UNKNOWN,'native'=>$native,'length'=>odbc_field_len($this->resource,$index),'scale'=>odbc_field_scale($this->resource,$index),'precision'=>odbc_field_precision($this->resource,$index),);$this->convert[$name]=$this->meta[$name]['type'];}}}class
DibiPostgreDriver
extends
DibiDriver{private$conn,$affectedRows=FALSE;public$formats=array('TRUE'=>"1",'FALSE'=>"0",'date'=>"'Y-m-d'",'datetime'=>"'Y-m-d H:i:s'",);public
static
function
connect($config){if(!extension_loaded('pgsql'))throw
new
DibiException("PHP extension 'pgsql' is not loaded");if(empty($config['string']))throw
new
DibiException("Connection string must be specified");if(empty($config['type']))$config['type']=NULL;$errorMsg='';if(isset($config['persistent']))$conn=@pg_connect($config['string'],$config['type']);else$conn=@pg_pconnect($config['string'],$config['type']);if(!is_resource($conn))throw
new
DibiException("Connecting error",array('message'=>pg_last_error(),));if(!empty($config['charset'])){$succ=@pg_set_client_encoding($conn,$config['charset']);}$obj=new
self($config);$obj->conn=$conn;return$obj;}public
function
query($sql){$this->affectedRows=FALSE;$errorMsg='';$res=@pg_query($this->conn,$sql);if($res===FALSE)return
FALSE;if(is_resource($res))return
new
DibiPostgreResult($res);$this->affectedRows=pg_affected_rows($this->conn);if($this->affectedRows<0)$this->affectedRows=FALSE;return
TRUE;}public
function
affectedRows(){return$this->affectedRows;}public
function
insertId(){return
FALSE;}public
function
begin(){return
pg_query($this->conn,'BEGIN');}public
function
commit(){return
pg_query($this->conn,'COMMIT');}public
function
rollback(){return
pg_query($this->conn,'ROLLBACK');}public
function
errorInfo(){return
array('message'=>pg_last_error($this->conn),'code'=>NULL,);}public
function
escape($value,$appendQuotes=FALSE){return$appendQuotes?"'".pg_escape_string($value)."'":pg_escape_string($value);}public
function
quoteName($value){return$value;}public
function
getMetaData(){trigger_error('Meta is not implemented yet.',E_USER_WARNING);}public
function
applyLimit(&$sql,$limit,$offset=0){if($limit>=0)$sql.=' LIMIT '.(int)$limit;if($offset>0)$sql.=' OFFSET '.(int)$offset;}}class
DibiPostgreResult
extends
DibiResult{private$resource,$meta;public
function
__construct($resource){$this->resource=$resource;}public
function
rowCount(){return
pg_num_rows($this->resource);}protected
function
doFetch(){return
pg_fetch_array($this->resource,NULL,PGSQL_ASSOC);}public
function
seek($row){return
pg_result_seek($this->resource,$row);}protected
function
free(){pg_free_result($this->resource);}public
function
getFields(){if($this->meta===NULL)$this->createMeta();return
array_keys($this->meta);}protected
function
detectTypes(){if($this->meta===NULL)$this->createMeta();}public
function
getMetaData($field){if($this->meta===NULL)$this->createMeta();return
isset($this->meta[$field])?$this->meta[$field]:FALSE;}private
function
createMeta(){static$types=array('bool'=>dibi::FIELD_BOOL,'int2'=>dibi::FIELD_INTEGER,'int4'=>dibi::FIELD_INTEGER,'int8'=>dibi::FIELD_INTEGER,'numeric'=>dibi::FIELD_FLOAT,'float4'=>dibi::FIELD_FLOAT,'float8'=>dibi::FIELD_FLOAT,'timestamp'=>dibi::FIELD_DATETIME,'date'=>dibi::FIELD_DATE,'time'=>dibi::FIELD_DATETIME,'varchar'=>dibi::FIELD_TEXT,'bpchar'=>dibi::FIELD_TEXT,'inet'=>dibi::FIELD_TEXT,'money'=>dibi::FIELD_FLOAT,);$count=pg_num_fields($this->resource);$this->meta=$this->convert=array();for($index=0;$index<$count;$index++){$info['native']=$native=pg_field_type($this->resource,$index);$info['length']=pg_field_size($this->resource,$index);$info['table']=pg_field_table($this->resource,$index);$info['type']=isset($types[$native])?$types[$native]:dibi::FIELD_UNKNOWN;$name=pg_field_name($this->resource,$index);$this->meta[$name]=$info;$this->convert[$name]=$info['type'];}}}class
DibiSqliteDriver
extends
DibiDriver{private$conn,$insertId=FALSE,$affectedRows=FALSE,$errorMsg;public$formats=array('TRUE'=>"1",'FALSE'=>"0",'date'=>"'Y-m-d'",'datetime'=>"'Y-m-d H:i:s'",);public
static
function
connect($config){if(!extension_loaded('sqlite'))throw
new
DibiException("PHP extension 'sqlite' is not loaded");if(empty($config['database']))throw
new
DibiException("Database must be specified");if(!isset($config['mode']))$config['mode']=0666;$errorMsg='';if(empty($config['persistent']))$conn=@sqlite_open($config['database'],$config['mode'],$errorMsg);else$conn=@sqlite_popen($config['database'],$config['mode'],$errorMsg);if(!$conn)throw
new
DibiException("Connecting error",array('message'=>$errorMsg,));$obj=new
self($config);$obj->conn=$conn;return$obj;}public
function
query($sql){$this->insertId=$this->affectedRows=FALSE;$errorMsg='';$res=@sqlite_query($this->conn,$sql,SQLITE_ASSOC,$this->errorMsg);if($res===FALSE)return
FALSE;if(is_resource($res))return
new
DibiSqliteResult($res);$this->affectedRows=sqlite_changes($this->conn);if($this->affectedRows<0)$this->affectedRows=FALSE;$this->insertId=sqlite_last_insert_rowid($this->conn);if($this->insertId<1)$this->insertId=FALSE;return
TRUE;}public
function
affectedRows(){return$this->affectedRows;}public
function
insertId(){return$this->insertId;}public
function
begin(){return
sqlite_query($this->conn,'BEGIN');}public
function
commit(){return
sqlite_query($this->conn,'COMMIT');}public
function
rollback(){return
sqlite_query($this->conn,'ROLLBACK');}public
function
errorInfo(){return
array('message'=>$this->errorMsg,'code'=>NULL,);}public
function
escape($value,$appendQuotes=FALSE){return$appendQuotes?"'".sqlite_escape_string($value)."'":sqlite_escape_string($value);}public
function
quoteName($value){return'['.$value.']';}public
function
getMetaData(){trigger_error('Meta is not implemented yet.',E_USER_WARNING);}public
function
applyLimit(&$sql,$limit,$offset=0){if($limit<0&&$offset<1)return;$sql.=' LIMIT '.$limit.($offset>0?' OFFSET '.(int)$offset:'');}}class
DibiSqliteResult
extends
DibiResult{private$resource,$meta;public
function
__construct($resource){$this->resource=$resource;}public
function
rowCount(){return
sqlite_num_rows($this->resource);}protected
function
doFetch(){return
sqlite_fetch_array($this->resource,SQLITE_ASSOC);}public
function
seek($row){return
sqlite_seek($this->resource,$row);}protected
function
free(){}public
function
getFields(){if($this->meta===NULL)$this->createMeta();return
array_keys($this->meta);}protected
function
detectTypes(){if($this->meta===NULL)$this->createMeta();}public
function
getMetaData($field){if($this->meta===NULL)$this->createMeta();return
isset($this->meta[$field])?$this->meta[$field]:FALSE;}private
function
createMeta(){$count=sqlite_num_fields($this->resource);$this->meta=$this->convert=array();for($index=0;$index<$count;$index++){$name=sqlite_field_name($this->resource,$index);$this->meta[$name]=array('type'=>dibi::FIELD_UNKNOWN);$this->convert[$name]=dibi::FIELD_UNKNOWN;}}}