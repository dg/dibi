<?php //netteloader=
/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 * @package Nette\Diagnostics
 */

define('NETTE_DIR',dirname(__FILE__));interface
IBarPanel{function
getTab();function
getPanel();}class
NDebugBar{private$panels=array();public
function
addPanel(IBarPanel$panel,$id=NULL){if($id===NULL){$c=0;do{$id=get_class($panel).($c++?"-$c":'');}while(isset($this->panels[$id]));}$this->panels[$id]=$panel;return$this;}public
function
getPanel($id){return
isset($this->panels[$id])?$this->panels[$id]:NULL;}public
function
render(){$obLevel=ob_get_level();$panels=array();foreach($this->panels
as$id=>$panel){try{$panels[]=array('id'=>preg_replace('#[^a-z0-9]+#i','-',$id),'tab'=>$tab=(string)$panel->getTab(),'panel'=>$tab?(string)$panel->getPanel():NULL);}catch(Exception$e){$panels[]=array('id'=>"error-".preg_replace('#[^a-z0-9]+#i','-',$id),'tab'=>"Error in $id",'panel'=>'<h1>Error: '.$id.'</h1><div class="nette-inner">'.nl2br(htmlSpecialChars($e)).'</div>');while(ob_get_level()>$obLevel){ob_end_clean();}}}@session_start();$session=&$_SESSION['__NF']['debuggerbar'];if(preg_match('#^Location:#im',implode("\n",headers_list()))){$session[]=$panels;return;}foreach(array_reverse((array)$session)as$reqId=>$oldpanels){$panels[]=array('tab'=>'<span title="Previous request before redirect">previous</span>','panel'=>NULL,'previous'=>TRUE);foreach($oldpanels
as$panel){$panel['id'].='-'.$reqId;$panels[]=$panel;}}$session=NULL;?>



<!-- Nette Debug Bar -->

<?php ob_start()?>
&nbsp;

<style id="nette-debug-style" class="nette-debug">#nette-debug{display:none}body#nette-debug{margin:5px 5px 0;display:block}#nette-debug *{font:inherit;color:inherit;background:transparent;margin:0;padding:0;border:none;text-align:inherit;list-style:inherit;opacity:1;border-radius:0;box-shadow:none}#nette-debug b,#nette-debug strong{font-weight:bold}#nette-debug i,#nette-debug em{font-style:italic}#nette-debug a{color:#125EAE;text-decoration:none}#nette-debug .nette-panel a{color:#125EAE;text-decoration:none}#nette-debug a:hover,#nette-debug a:active,#nette-debug a:focus{background-color:#125EAE;color:white}#nette-debug .nette-panel h2,#nette-debug .nette-panel h3,#nette-debug .nette-panel p{margin:.4em 0}#nette-debug .nette-panel table{border-collapse:collapse;background:#FDF5CE}#nette-debug .nette-panel tr:nth-child(2n) td{background:#F7F0CB}#nette-debug .nette-panel td,#nette-debug .nette-panel th{border:1px solid #E6DFBF;padding:2px 5px;vertical-align:top;text-align:left}#nette-debug .nette-panel th{background:#F4F3F1;color:#655E5E;font-size:90%;font-weight:bold}#nette-debug .nette-panel pre,#nette-debug .nette-panel code{font:9pt/1.5 Consolas,monospace}#nette-debug table .nette-right{text-align:right}#nette-debug-bar{font:normal normal 12px/21px Tahoma,sans-serif;color:#333;border:1px solid #c9c9c9;background:#EDEAE0 url('data:image/png;base64,R0lGODlhAQAVALMAAOTh1/Px6eHe1fHv5e/s4vLw6Ofk2u3q4PPw6PPx6PDt5PLw5+Dd1OXi2Ojm3Orn3iH5BAAAAAAALAAAAAABABUAAAQPMISEyhpYkfOcaQAgCEwEADs=') top;position:fixed;right:0;bottom:0;overflow:auto;min-height:21px;_float:left;min-width:50px;white-space:nowrap;z-index:30000;opacity:.9;border-radius:3px;box-shadow:1px 1px 10px rgba(0,0,0,.15)}#nette-debug-bar:hover{opacity:1}#nette-debug-bar ul{list-style:none none;margin-left:4px;clear:left}#nette-debug-bar li{float:left}#nette-debug-bar ul.nette-previous li{font-size:90%;opacity:.6;background:#F5F3EE}#nette-debug-bar ul.nette-previous li:first-child{width:45px}#nette-debug-bar img{vertical-align:middle;position:relative;top:-1px;margin-right:3px}#nette-debug-bar li a{color:#000;display:block;padding:0 4px}#nette-debug-bar li a:hover{color:black;background:#c3c1b8}#nette-debug-bar li .nette-warning{color:#D32B2B;font-weight:bold}#nette-debug-bar li>span{padding:0 4px}#nette-debug-logo{background:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAC0AAAAPCAYAAABwfkanAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABiFJREFUSMe1VglPlGcQ5i+1xjZNqxREtGq8ahCPWsVGvEDBA1BBRQFBDjkE5BYUzwpovRBUREBEBbl3OVaWPfj2vi82eTrvbFHamLRJ4yYTvm+u95mZZ96PoKAv+LOatXBYZ+Bx6uFy6DGnt1m0EOKwSmQzwmHTgX5B/1W+yM9GYJ02CX6/B/5ZF+w2A4x6FYGTYDVp4PdY2Tbrs5N+mnRa2Km4/wV6rhPzQQj5fDc1mJM5nd0iYdZtQWtrCxobGnDpUiledTynbuvg99mgUMhw924Trl2rR01NNSTNJE9iDpTV8innv4K2kZPLroPXbYLHZeSu2K1aeF0muJ2GvwGzmNSwU2E+svm8ZrgdBliMaha/34Vx+RAKCgpwpa4OdbW1UE/L2cc/68WtWzdRVlaG6uoqtD1/BA/pA1MIxLvtes7pc5vhoDOE/rOgbVSdf9aJWa8dBp0Kyg+jdLiTx2vQKWEyqGmcNkqg4iTC1+dzQatWkK+cJqPD7KyFaKEjvRuNjY24fLkGdXW1ePjwAeX4QHonDNI0A75+/RpqqqshH+6F2UAUMaupYXouykV0mp6SQ60coxgL8Z4aMg/4x675/V60v3jKB+Xl5WJibIC4KPEIS0qKqWv5GOh7BZ/HSIk9kA33o7y8DOfPZ6GQOipkXDZAHXKxr4ipqqpkKS6+iIrycgz2dyMnJxtVlZUsotNZWZmor79KBbvgpdjm5sfIzc1hv4L8fKJPDTfJZZc+gRYKr8sAEy2DcBRdEEk62ltx9uwZ5qNILoDU1l6mbrvx5EkzUlKSuTiR7PHjR3x4fv4FyIbeIic7G5WVFUyN+qtX+Lnt2SPcvn2LfURjhF7kE4WPDr+Bx+NEUVEhkpNPoImm5CSOl5aUIC3tLOMR59gtAY4HidGIzj14cB8ZGRkM8kJeHk6cOI4xWR8vSl5uLlJTT6O74xnT5lB8PM6cSYXVqILb5UBWZiYSExMYkE4zzjqX00QHG+h9AjPqMei0k3ywy2khMdNiq6BVCf04T6ekuBgJCUdRUVHOBQwPvkNSUiLjaGi4Q/5qFgYtHgTXRJdTT59GenoaA5gY64deq0Bc3EGuNj4+DnppEheLijhZRkY6SktLsGPHdi6irOwSFTRAgO04deokTSIFsbExuHfvLnFSx8DevelAfFwcA0lJTqZi5PDS9aci/sbE7Oe4wsICbtD27b/ye1NTI3FeSX4W2gdFALRD3A4eM44ePcKViuD79/8gnZP5Kg4+cCAW2dnnqUM2Lujw4UM4ePAA2ztfPsHIYA/sdOt43A50d7UFCjkUj+joXVBMDJDeDhcVk08cjd61C3v37uFYp8PKXX3X8xJRUTtw7FgSn3Xzxg10d7ZCqRjkM+02C7pettDNogqAFjzxuI3YHR2Nffv2coXy0V44HGZERm7kJNu2/cK8bW9rwbp1axnMnj27uUijQQOb1QyTcYZ3YMOGn/Hbzp1crAAvaDfY38O5hW3//n0ce+TIYWiUcub1xo0R2Lp1y8cYsUMWM125VhPe93Zj7do1vEPi26GfUdBFbhK8tGHrli1YsWwpgoOD0dXRQqAtXMCy8DBs3rwJoSGLsWrVclylBdoUGYlVK1dg9eqVCFsSSs8/4btvvmUwEnE0KTERISE/IiIiAsGLF2HhwgU8qbc97QgPX8qFr1mzGgu+/opzdL5o5l1aEhqC9evXYWlYKFYsD6e/YVj0w/dMGZVyBDMqeaDTRuKpkxYjIz2dOyeup6H3r2kkOuJ1H3N5Z1QUzp3LQF9vJ4xGLQYHXiM9LY0pEhsTg+PHj9HNcJu4OcL3uaQZY86LiZw8mcJTkmhBTUYJbU8fcoygobgWR4Z6iKtTPLE7d35HYkICT1dIZuY59HQ9412StBPQTMvw8Z6WaMNFxy3Gab4TeQT0M9IHwUT/G0i0MGIJ9CTiJjBIH+iQaQbC7+QnfEXiQL6xgF09TjETHCt8RbeMuil+D8RNsV1LHdQoZfR/iJJzCZuYmEE/Bd3MJNs/+0UURgFWJJ//aQ8k+CsxVTqnVytHObkQrUoG8T4/bs4u4ubbxLPwFzYNPc8HI2zijLm84l39Dx8hfwJenFezFBKKQwAAAABJRU5ErkJggg==') 0 50% no-repeat;min-width:45px;cursor:move}#nette-debug-logo span{display:none}#nette-debug .nette-panel{font:normal normal 12px/1.5 sans-serif;background:white;color:#333;text-align:left}#nette-debug h1{font:normal normal 23px/1.4 Tahoma,sans-serif;color:#575753;margin:-5px -5px 5px;padding:0 25px 5px 5px}#nette-debug .nette-mode-peek .nette-inner,#nette-debug .nette-mode-float .nette-inner{max-width:700px;max-height:500px;overflow:auto}#nette-debug .nette-panel .nette-icons{display:none}#nette-debug .nette-mode-peek{display:none;position:fixed;right:0;bottom:0;padding:10px;min-width:150px;min-height:50px;border-radius:5px;box-shadow:1px 1px 20px rgba(102,102,102,0.36);border:1px solid rgba(0,0,0,0.1)}#nette-debug .nette-mode-peek h1{cursor:move}#nette-debug .nette-mode-float{position:fixed;right:0;bottom:0;padding:10px;min-width:150px;min-height:50px;border-radius:5px;opacity:.95;box-shadow:1px 1px 30px rgba(102,102,102,0.36);border:1px solid rgba(0,0,0,0.1)}#nette-debug .nette-focused{opacity:1}#nette-debug .nette-mode-float h1{cursor:move}#nette-debug .nette-mode-float .nette-icons{display:block;position:absolute;top:0;right:5px;font-size:18px}#nette-debug .nette-icons a{color:#575753}#nette-debug .nette-icons a:hover{color:white}.nette-collapsed{display:none}.nette-toggle,.nette-toggle-collapsed{cursor:pointer}.nette-toggle:after{content:" ▼";opacity:.4}.nette-toggle-collapsed:after{content:" ►";opacity:.4}pre.nette-dump{color:#444;background:white}pre.nette-dump div,#nette-debug pre.nette-dump div{padding-left:3ex}pre.nette-dump div div,#nette-debug pre.nette-dump div div{border-left:1px solid rgba(0,0,0,.1);margin-left:.5ex}#nette-debug pre.nette-dump{background:#FDF5CE;padding:.4em .7em;border:1px dotted silver;overflow:auto}#nette-debug table pre.nette-dump{padding:0;margin:0;border:none}.nette-dump-array,.nette-dump-object,#nette-debug .nette-dump-array,#nette-debug .nette-dump-object{color:#C22}.nette-dump-string,#nette-debug .nette-dump-string{color:#35D}.nette-dump-number,#nette-debug .nette-dump-number{color:#090}.nette-dump-null,.nette-dump-bool,#nette-debug .nette-dump-null,#nette-debug .nette-dump-bool{color:#850}.nette-dump-visibility,#nette-debug .nette-dump-visibility{font-size:85%;color:#999}.nette-dump-indent,#nette-debug .nette-dump-indent{display:none}@media print{#nette-debug *{display:none}}</style>

<!--[if lt IE 8]><style class="nette-debug">#nette-debug-bar img{display:none}#nette-debug-bar li{border-left:1px solid #DCD7C8;padding:0 3px}#nette-debug-logo span{background:#edeae0;display:inline}</style><![endif]-->


<script id="nette-debug-script">/*<![CDATA[*/var Nette=Nette||{};
(function(){var b=Nette.Query=function(a){if("string"===typeof a)a=this._find(document,a);else if(!a||a.nodeType||void 0===a.length||a===window)a=[a];for(var f=0,b=a.length;f<b;f++)a[f]&&(this[this.length++]=a[f])};b.factory=function(a){return new b(a)};b.prototype.length=0;b.prototype.find=function(a){return new b(this._find(this[0],a))};b.prototype._find=function(a,f){if(!a||!f)return[];if(document.querySelectorAll)return a.querySelectorAll(f);if("#"===f.charAt(0))return[document.getElementById(f.substring(1))];var f=
f.split("."),b=a.getElementsByTagName(f[0]||"*");if(f[1]){for(var d=[],c=RegExp("(^|\\s)"+f[1]+"(\\s|$)"),g=0,h=b.length;g<h;g++)c.test(b[g].className)&&d.push(b[g]);return d}return b};b.prototype.dom=function(){return this[0]};b.prototype.each=function(a){for(var b=0;b<this.length&&!1!==a.apply(this[b]);b++);return this};b.prototype.bind=function(a,b){if(document.addEventListener&&("mouseenter"===a||"mouseleave"===a))var e=b,a="mouseenter"===a?"mouseover":"mouseout",b=function(a){for(var b=a.relatedTarget;b;b=
b.parentNode)if(b===this)return;e.call(this,a)};return this.each(function(){var d=this,c=d.nette?d.nette:d.nette={},c=c.events=c.events||{};if(!c[a]){var g=c[a]=[],h=function(a){a.target||(a.target=a.srcElement);a.preventDefault||(a.preventDefault=function(){a.returnValue=!1});a.stopPropagation||(a.stopPropagation=function(){a.cancelBubble=!0});a.stopImmediatePropagation=function(){this.stopPropagation();b=g.length};for(var b=0;b<g.length;b++)g[b].call(d,a)};document.addEventListener?d.addEventListener(a,
h,!1):document.attachEvent&&d.attachEvent("on"+a,h)}c[a].push(b)})};b.prototype.addClass=function(a){return this.each(function(){this.className=this.className.replace(/^|\s+|$/g," ").replace(" "+a+" "," ")+" "+a})};b.prototype.removeClass=function(a){return this.each(function(){this.className=this.className.replace(/^|\s+|$/g," ").replace(" "+a+" "," ")})};b.prototype.hasClass=function(a){return this[0]&&-1<this[0].className.replace(/^|\s+|$/g," ").indexOf(" "+a+" ")};b.prototype.show=function(){b.displays=
b.displays||{};return this.each(function(){var a=this.tagName;b.displays[a]||(b.displays[a]=(new b(document.body.appendChild(document.createElement(a)))).css("display"));this.style.display=b.displays[a]})};b.prototype.hide=function(){return this.each(function(){this.style.display="none"})};b.prototype.css=function(a){if(this[0]&&this[0].currentStyle)return this[0].currentStyle[a];if(this[0]&&window.getComputedStyle)return document.defaultView.getComputedStyle(this[0],null).getPropertyValue(a)};b.prototype.data=
function(){if(this[0])return this[0].nette?this[0].nette:this[0].nette={}};b.prototype.val=function(){var a=this[0];if(a)if(a.nodeName){if("select"===a.nodeName.toLowerCase()){var b=a.selectedIndex,e=a.options;if(0>b)return null;if("select-one"===a.type)return e[b].value;b=0;a=[];for(d=e.length;b<d;b++)e[b].selected&&a.push(e[b].value);return a}if("checkbox"===a.type)return a.checked;if(a.value)return a.value.replace(/^\s+|\s+$/g,"")}else{for(var b=0,d=a.length;b<d;b++)if(this[b].checked)return this[b].value;
return null}};b.prototype._trav=function(a,f,e){for(f=f.split(".");a&&!(1===a.nodeType&&(!f[0]||a.tagName.toLowerCase()===f[0])&&(!f[1]||(new b(a)).hasClass(f[1])));)a=a[e];return new b(a||[])};b.prototype.closest=function(a){return this._trav(this[0],a,"parentNode")};b.prototype.prev=function(a){return this._trav(this[0]&&this[0].previousSibling,a,"previousSibling")};b.prototype.next=function(a){return this._trav(this[0]&&this[0].nextSibling,a,"nextSibling")};b.prototype.offset=function(a){if(a)return this.each(function(){for(var d=
this,b=-a.left||0,g=-a.top||0;d=d.offsetParent;)b+=d.offsetLeft,g+=d.offsetTop;this.style.left=-b+"px";this.style.top=-g+"px"});if(this[0]){for(var b=this[0],e={left:b.offsetLeft,top:b.offsetTop};b=b.offsetParent;)e.left+=b.offsetLeft,e.top+=b.offsetTop;return e}};b.prototype.position=function(a){if(a)return this.each(function(){this.nette&&this.nette.onmove&&this.nette.onmove.call(this,a);for(var b in a)this.style[b]=a[b]+"px"});if(this[0])return{left:this[0].offsetLeft,top:this[0].offsetTop,right:this[0].style.right?
parseInt(this[0].style.right,10):0,bottom:this[0].style.bottom?parseInt(this[0].style.bottom,10):0,width:this[0].offsetWidth,height:this[0].offsetHeight}};b.prototype.draggable=function(a){var f=this[0],e=document.documentElement,d,a=a||{};(a.handle?new b(a.handle):this).bind("mousedown",function(c){var g=new b(a.handle?f:this);c.preventDefault();c.stopPropagation();if(b.dragging)return e.onmouseup(c);var h=g.position(),j=a.rightEdge?h.right+c.clientX:h.left-c.clientX,i=a.bottomEdge?h.bottom+c.clientY:
h.top-c.clientY;b.dragging=!0;d=!1;e.onmousemove=function(b){b=b||event;d||(a.draggedClass&&g.addClass(a.draggedClass),a.start&&a.start(b,g),d=!0);var c={};c[a.rightEdge?"right":"left"]=a.rightEdge?j-b.clientX:b.clientX+j;c[a.bottomEdge?"bottom":"top"]=a.bottomEdge?i-b.clientY:b.clientY+i;g.position(c);return!1};e.onmouseup=function(c){d&&(a.draggedClass&&g.removeClass(a.draggedClass),a.stop&&a.stop(c||event,g));b.dragging=e.onmousemove=e.onmouseup=null;return!1}}).bind("click",function(a){d&&a.stopImmediatePropagation()});
return this}})();
(function(){var b=Nette.Query.factory,a=Nette.DebugPanel=function(a){this.id="nette-debug-panel-"+a;this.elem=b("#"+this.id)};a.PEEK="nette-mode-peek";a.FLOAT="nette-mode-float";a.WINDOW="nette-mode-window";a.FOCUSED="nette-focused";a.zIndex=2E4;a.prototype.init=function(){var a=this;this.elem.data().onmove=function(b){a.moveConstrains(this,b)};this.elem.draggable({rightEdge:!0,bottomEdge:!0,handle:this.elem.find("h1"),stop:function(){a.toFloat()}}).bind("mouseenter",function(){a.focus()}).bind("mouseleave",function(){a.blur()});
this.elem.find(".nette-icons").find("a").bind("click",function(b){"close"===this.rel?a.toPeek():a.toWindow();b.preventDefault()});this.restorePosition()};a.prototype.is=function(a){return this.elem.hasClass(a)};a.prototype.focus=function(){var b=this.elem;this.is(a.WINDOW)?b.data().win.focus():(clearTimeout(b.data().blurTimeout),b.addClass(a.FOCUSED).show(),b[0].style.zIndex=a.zIndex++)};a.prototype.blur=function(){var b=this.elem;b.removeClass(a.FOCUSED);this.is(a.PEEK)&&(b.data().blurTimeout=setTimeout(function(){b.hide()},
50))};a.prototype.toFloat=function(){this.elem.removeClass(a.WINDOW).removeClass(a.PEEK).addClass(a.FLOAT).show();this.reposition()};a.prototype.toPeek=function(){this.elem.removeClass(a.WINDOW).removeClass(a.FLOAT).addClass(a.PEEK).hide();document.cookie=this.id+"=; path=/"};a.prototype.toWindow=function(){var d=this.elem.offset();d.left+="number"===typeof window.screenLeft?window.screenLeft:window.screenX+10;d.top+="number"===typeof window.screenTop?window.screenTop:window.screenY+50;var c=window.open("",
this.id.replace(/-/g,"_"),"left="+d.left+",top="+d.top+",width="+d.width+",height="+(d.height+15)+",resizable=yes,scrollbars=yes");if(c){d=c.document;d.write('<!DOCTYPE html><meta charset="utf-8"><style>'+b("#nette-debug-style").dom().innerHTML+"</style><script>"+b("#nette-debug-script").dom().innerHTML+'<\/script><body id="nette-debug">');d.body.innerHTML='<div class="nette-panel nette-mode-window" id="'+this.id+'">'+this.elem.dom().innerHTML+"</div>";var g=c.Nette.Debug.getPanel(this.id);c.Nette.Debug.initToggle();
g.reposition();d.title=this.elem.find("h1").dom().innerHTML;var h=this;b([c]).bind("unload",function(){h.toPeek();c.close()});b(d).bind("keyup",function(a){27===a.keyCode&&(!a.shiftKey&&!a.altKey&&!a.ctrlKey&&!a.metaKey)&&c.close()});document.cookie=this.id+"=window; path=/";this.elem.hide().removeClass(a.FLOAT).removeClass(a.PEEK).addClass(a.WINDOW).data().win=c}};a.prototype.reposition=function(){if(this.is(a.WINDOW)){var b=document.documentElement;window.resizeBy(b.scrollWidth-b.clientWidth,b.scrollHeight-
b.clientHeight)}else b=this.elem.position(),b.width&&(this.elem.position({right:b.right,bottom:b.bottom}),document.cookie=this.id+"="+b.right+":"+b.bottom+"; path=/")};a.prototype.moveConstrains=function(a,b){var g=window.innerWidth||document.documentElement.clientWidth||document.body.clientWidth,h=window.innerHeight||document.documentElement.clientHeight||document.body.clientHeight;b.right=Math.min(Math.max(b.right,-0.2*a.offsetWidth),g-0.8*a.offsetWidth);b.bottom=Math.min(Math.max(b.bottom,-0.2*
a.offsetHeight),h-a.offsetHeight)};a.prototype.restorePosition=function(){var b=document.cookie.match(RegExp(this.id+"=(window|(-?[0-9]+):(-?[0-9]+))"));b&&b[2]?(this.elem.position({right:b[2],bottom:b[3]}),this.toFloat()):b?this.toWindow():this.elem.addClass(a.PEEK)};var f=Nette.DebugBar=function(){};f.prototype.id="nette-debug-bar";f.prototype.init=function(){var d=b("#"+this.id),c=this;d.data().onmove=function(a){c.moveConstrains(this,a)};d.draggable({rightEdge:!0,bottomEdge:!0,draggedClass:"nette-dragged",
stop:function(){c.savePosition()}});d.find("a").bind("click",function(b){if("close"===this.rel)c.close();else if(this.rel){var d=e.getPanel(this.rel);b.shiftKey?(d.toFloat(),d.toWindow()):d.is(a.FLOAT)?d.toPeek():(d.toFloat(),d.elem.position({right:d.elem.position().right+Math.round(100*Math.random())+20,bottom:d.elem.position().bottom+Math.round(100*Math.random())+20}))}b.preventDefault()}).bind("mouseenter",function(){if(this.rel&&"close"!==this.rel&&!d.hasClass("nette-dragged")){var c=e.getPanel(this.rel),
f=b(this);c.focus();c.is(a.PEEK)&&c.elem.position({right:c.elem.position().right-f.offset().left+c.elem.position().width-f.position().width-4+c.elem.offset().left,bottom:c.elem.position().bottom-d.offset().top+c.elem.position().height+4+c.elem.offset().top})}}).bind("mouseleave",function(){this.rel&&("close"!==this.rel&&!d.hasClass("nette-dragged"))&&e.getPanel(this.rel).blur()});this.restorePosition()};f.prototype.close=function(){b("#nette-debug").hide();window.opera&&b("body").show()};f.prototype.moveConstrains=
function(a,b){var f=window.innerWidth||document.documentElement.clientWidth||document.body.clientWidth,e=window.innerHeight||document.documentElement.clientHeight||document.body.clientHeight;b.right=Math.min(Math.max(b.right,0),f-a.offsetWidth);b.bottom=Math.min(Math.max(b.bottom,0),e-a.offsetHeight)};f.prototype.savePosition=function(){var a=b("#"+this.id).position();document.cookie=this.id+"="+a.right+":"+a.bottom+"; path=/"};f.prototype.restorePosition=function(){var a=document.cookie.match(RegExp(this.id+
"=(-?[0-9]+):(-?[0-9]+)"));a&&b("#"+this.id).position({right:a[1],bottom:a[2]})};var e=Nette.Debug={};e.init=function(){e.initToggle();e.initResize();(new f).init();b(".nette-panel").each(function(){e.getPanel(this.id).init()})};e.getPanel=function(b){return new a(b.replace("nette-debug-panel-",""))};e.initToggle=function(){b(document.body).bind("click",function(a){for(var c=a.target;c&&(!c.tagName||0>c.className.indexOf("nette-toggle"));c=c.parentNode);if(c){var f=b(c).hasClass("nette-toggle-collapsed"),
e=c.getAttribute("data-ref")||c.getAttribute("href"),j=e&&"#"!==e?b(e):b(c).next(""),e=b(c).closest(".nette-panel"),i=e.position();c.className="nette-toggle"+(f?"":"-collapsed");j[f?"show":"hide"]();a.preventDefault();a=e.position();e.position({right:a.right-a.width+i.width,bottom:a.bottom-a.height+i.height})}})};e.initResize=function(){b(window).bind("resize",function(){var a=b("#"+f.prototype.id);a.position({right:a.position().right,bottom:a.position().bottom});b(".nette-panel").each(function(){e.getPanel(this.id).reposition()})})}})();/*]]>*/</script>


<?php foreach($panels
as$id=>$panel):if(!$panel['panel'])continue;?>
	<div class="nette-panel" id="nette-debug-panel-<?php echo$panel['id']?>">
		<?php echo$panel['panel']?>
		<div class="nette-icons">
			<a href="#" title="open in window">&curren;</a>
			<a href="#" rel="close" title="close window">&times;</a>
		</div>
	</div>
<?php endforeach?>

<div id="nette-debug-bar">
	<ul>
		<li id="nette-debug-logo" title="PHP <?php echo
htmlSpecialChars(PHP_VERSION." |\n".(isset($_SERVER['SERVER_SOFTWARE'])?$_SERVER['SERVER_SOFTWARE']." |\n":'').(class_exists('NFramework')?'Nette Framework '.NFramework::VERSION.' ('.substr(NFramework::REVISION,8).')':''))?>">&nbsp;<span>Nette Framework</span></li>
		<?php foreach($panels
as$panel):if(!$panel['tab'])continue;?>
		<?php if(!empty($panel['previous']))echo'</ul><ul class="nette-previous">';?>
		<li><?php if($panel['panel']):?><a href="#" rel="<?php echo$panel['id']?>"><?php echo
trim($panel['tab'])?></a><?php else:echo'<span>',trim($panel['tab']),'</span>';endif?></li>
		<?php endforeach?>
		<li><a href="#" rel="close" title="close debug bar">&times;</a></li>
	</ul>
</div>
<?php $output=ob_get_clean();?>


<script>
(function(onloadOrig) {
	window.onload = function() {
		if (typeof onloadOrig === 'function') onloadOrig();
		var debug = document.body.appendChild(document.createElement('div'));
		debug.id = 'nette-debug';
		debug.innerHTML = <?php echo
json_encode(@iconv('UTF-16','UTF-8//IGNORE',iconv('UTF-8','UTF-16//IGNORE',$output)))?>;
		for (var i = 0, scripts = debug.getElementsByTagName('script'); i < scripts.length; i++) eval(scripts[i].innerHTML);
		Nette.Debug.init();
		debug.style.display = 'block';
	};
})(window.onload);
</script>

<!-- /Nette Debug Bar -->
<?php }}class
NDebugBlueScreen{private$panels=array();public$collapsePaths=array();public
function
addPanel($panel){if(!in_array($panel,$this->panels,TRUE)){$this->panels[]=$panel;}return$this;}public
function
render(Exception$exception){$panels=$this->panels;static$errorTypes=array(E_ERROR=>'Fatal Error',E_USER_ERROR=>'User Error',E_RECOVERABLE_ERROR=>'Recoverable Error',E_CORE_ERROR=>'Core Error',E_COMPILE_ERROR=>'Compile Error',E_PARSE=>'Parse Error',E_WARNING=>'Warning',E_CORE_WARNING=>'Core Warning',E_COMPILE_WARNING=>'Compile Warning',E_USER_WARNING=>'User Warning',E_NOTICE=>'Notice',E_USER_NOTICE=>'User Notice',E_STRICT=>'Strict');if(PHP_VERSION_ID>=50300){$errorTypes+=array(E_DEPRECATED=>'Deprecated',E_USER_WARNING=>'User Deprecated');}$title=($exception
instanceof
FatalErrorException&&isset($errorTypes[$exception->getSeverity()]))?$errorTypes[$exception->getSeverity()]:get_class($exception);$counter=0;?><!DOCTYPE html><!-- "' --></script></style></pre></xmp></table>
<html>
<head>
	<meta charset="utf-8">
	<meta name="robots" content="noindex">
	<meta name="generator" content="Nette Framework">

	<title><?php echo
htmlspecialchars($title)?></title><!-- <?php
$ex=$exception;echo
htmlspecialchars($ex->getMessage().($ex->getCode()?' #'.$ex->getCode():''));while((method_exists($ex,'getPrevious')&&$ex=$ex->getPrevious())||(isset($ex->previous)&&$ex=$ex->previous))echo
htmlspecialchars('; caused by '.get_class($ex).' '.$ex->getMessage().($ex->getCode()?' #'.$ex->getCode():''));?> -->

	<style type="text/css" class="nette-debug">html{overflow-y:scroll}body{margin:0 0 2em;padding:0}#netteBluescreen{font:9pt/1.5 Verdana,sans-serif;background:white;color:#333;position:absolute;left:0;top:0;width:100%;text-align:left}#netteBluescreen *{font:inherit;color:inherit;background:transparent;border:none;margin:0;padding:0;text-align:inherit;text-indent:0}#netteBluescreen b{font-weight:bold}#netteBluescreen i{font-style:italic}#netteBluescreen a{text-decoration:none;color:#328ADC;padding:2px 4px;margin:-2px -4px}#netteBluescreen a:hover,#netteBluescreen a:active,#netteBluescreen a:focus{color:#085AA3}#netteBluescreenIcon{position:absolute;right:.5em;top:.5em;z-index:20000;text-decoration:none;background:#CD1818;color:white!important;padding:3px}#netteBluescreenError{background:#CD1818;color:white;font:13pt/1.5 Verdana,sans-serif!important;display:block}#netteBluescreenError #netteBsSearch{color:#CD1818;font-size:.7em}#netteBluescreenError:hover #netteBsSearch{color:#ED8383}#netteBluescreen h1{font-size:18pt;font-weight:normal;text-shadow:1px 1px 0 rgba(0,0,0,.4);margin:.7em 0}#netteBluescreen h2{font:14pt/1.5 sans-serif!important;color:#888;margin:.6em 0}#netteBluescreen h3{font:bold 10pt/1.5 Verdana,sans-serif!important;margin:1em 0;padding:0}#netteBluescreen p,#netteBluescreen pre{margin:.8em 0}#netteBluescreen pre,#netteBluescreen code,#netteBluescreen table{font:9pt/1.5 Consolas,monospace!important}#netteBluescreen pre,#netteBluescreen table{background:#FDF5CE;padding:.4em .7em;border:1px dotted silver;overflow:auto}#netteBluescreen table pre{padding:0;margin:0;border:none}#netteBluescreen table{border-collapse:collapse;width:100%}#netteBluescreen td,#netteBluescreen th{vertical-align:top;text-align:left;padding:2px 6px;border:1px solid #e6dfbf}#netteBluescreen th{font-weight:bold}#netteBluescreen tr>:first-child{width:20%}#netteBluescreen tr:nth-child(2n),#netteBluescreen tr:nth-child(2n) pre{background-color:#F7F0CB}#netteBluescreen ol{margin:1em 0;padding-left:2.5em}#netteBluescreen ul{font:7pt/1.5 Verdana,sans-serif!important;padding:2em 4em;margin:1em 0 0;color:#777;background:#F6F5F3 url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFEAAAAjCAMAAADbuxbOAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAADBQTFRF/fz24d7Y7Onj5uLd9vPu3drUzMvG09LN39zW8e7o2NbQ3NnT29jS0M7J1tXQAAAApvmsFgAAABB0Uk5T////////////////////AOAjXRkAAAKlSURBVHja7FbbsqQgDAwENEgc//9vN+SCWDtbtXPmZR/Wc6o02mlC58LA9ckFAOszvMV8xNgyUjyXhojfMVKvRL0ZHavxXYy5JrmchMdzou8YlTClxajtK8ZGGpWRoBr1+gFjKfHkJPbizabLgzE3pH7Iu4K980xgFvlrVzMZoVBWhtvouCDdcTDmTgMCJdVxJ9MKO6XxnliM7hxi5lbj2ZVM4l8DqYyKoNLYcfqBB1/LpHYxEcfVG6ZpMDgyFUVWY/Q1sSYPpIdSAKWqLWL0XqWiMWc4hpH0OQOMOAgdycY4N9Sb7wWANQs3rsDSdLAYiuxi5siVfOhBWIrtH0G3kNaF/8Q4kCPE1kMucG/ZMUBUCOgiKJkPuWWTLGVgLGpwns1DraUayCtoBqERyaYtVsm85NActRooezvSLO/sKZP/nq8n4+xcyjNsRu8zW6KWpdb7wjiQd4WrtFZYFiKHENSmWp6xshh96c2RQ+c7Lt+qbijyEjHWUJ/pZsy8MGIUuzNiPySK2Gqoh6ZTRF6ko6q3nVTkaA//itIrDpW6l3SLo8juOmqMXkYknu5FdQxWbhCfKHEGDhxxyTVaXJF3ZjSl3jMksjSOOKmne9pI+mcG5QvaUJhI9HpkmRo2NpCrDJvsktRhRE2MM6F2n7dt4OaMUq8bCctk0+PoMRzL+1l5PZ2eyM/Owr86gf8z/tOM53lom5+nVcFuB+eJVzlXwAYy9TZ9s537tfqcsJWbEU4nBngZo6FfO9T9CdhfBtmk2dLiAy8uS4zwOpMx2HqYbTC+amNeAYTpsP4SIgvWfUBWXxn3CMHW3ffd7k3+YIkx7w0t/CVGvcPejoeOlzOWzeGbawOHqXQGUTMZRcfj4XPCgW9y/fuvVn8zD9P1QHzv80uAAQA0i3Jer7Jr7gAAAABJRU5ErkJggg==') 99% 10px no-repeat;border-top:1px solid #DDD}#netteBluescreen div.panel{padding:1px 25px}#netteBluescreen div.inner{background:#F4F3F1;padding:.1em 1em 1em;border-radius:8px;-moz-border-radius:8px;-webkit-border-radius:8px}#netteBluescreen .outer{overflow:auto}#netteBluescreen pre.php div{min-width:100%;float:left;_float:none;white-space:pre}#netteBluescreen .highlight{background:#CD1818;color:white;font-weight:bold;font-style:normal;display:block;padding:0 .4em;margin:0 -.4em}#netteBluescreen .line{color:#9F9C7F;font-weight:normal;font-style:normal}#netteBluescreen a[href^=editor\:]{color:inherit;border-bottom:1px dotted #C1D2E1}html.js #netteBluescreen .nette-collapsed{display:none}#netteBluescreen .nette-toggle,#netteBluescreen .nette-toggle-collapsed{cursor:pointer}#netteBluescreen .nette-toggle:after{content:" ▼";opacity:.4}#netteBluescreen .nette-toggle-collapsed:after{content:" ►";opacity:.4}#netteBluescreen .nette-dump-array,#netteBluescreen .nette-dump-object{color:#C22}#netteBluescreen .nette-dump-string{color:#35D}#netteBluescreen .nette-dump-number{color:#090}#netteBluescreen .nette-dump-null,#netteBluescreen .nette-dump-bool{color:#850}#netteBluescreen .nette-dump-visibility{font-size:85%;color:#998}#netteBluescreen .nette-dump-indent{display:none}#netteBluescreen pre.nette-dump div{padding-left:3ex}#netteBluescreen pre.nette-dump div div{border-left:1px solid rgba(0,0,0,.1);margin-left:.5ex}</style>
	<script>document.body.className+=" js";</script>
</head>



<body>
<div id="netteBluescreen">
	<a id="netteBluescreenIcon" href="#" class="nette-toggle"></a>
	<div>
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
			<h2><a href="#netteBsPnl<?php echo++$counter?>" class="nette-toggle<?php echo($collapsed=$level>2)?'-collapsed':''?>">Caused by</a></h2>

			<div id="netteBsPnl<?php echo$counter?>" class="<?php echo$collapsed?'nette-collapsed ':''?>inner">
				<div class="panel">
					<h1><?php echo
htmlspecialchars(get_class($ex).($ex->getCode()?' #'.$ex->getCode():''))?></h1>

					<p><b><?php echo
htmlspecialchars($ex->getMessage())?></b></p>
				</div>
			<?php endif?>



			<?php foreach($panels
as$panel):?>
			<?php $panel=call_user_func($panel,$ex);if(empty($panel['tab'])||empty($panel['panel']))continue;?>
			<?php if(!empty($panel['bottom'])){continue;}?>
			<div class="panel">
				<h2><a href="#netteBsPnl<?php echo++$counter?>" class="nette-toggle"><?php echo
htmlSpecialChars($panel['tab'])?></a></h2>

				<div id="netteBsPnl<?php echo$counter?>" class="inner">
				<?php echo$panel['panel']?>
			</div></div>
			<?php endforeach?>



			<?php $stack=$ex->getTrace();$expanded=NULL?>
			<?php if($this->isCollapsed($ex->getFile())){foreach($stack
as$key=>$row){if(isset($row['file'])&&!$this->isCollapsed($row['file'])){$expanded=$key;break;}}}?>

			<div class="panel">
			<h2><a href="#netteBsPnl<?php echo++$counter?>" class="nette-toggle<?php echo($collapsed=$expanded!==NULL)?'-collapsed':''?>">Source file</a></h2>

			<div id="netteBsPnl<?php echo$counter?>" class="<?php echo$collapsed?'nette-collapsed ':''?>inner">
				<p><b>File:</b> <?php echo
NDebugHelpers::editorLink($ex->getFile(),$ex->getLine())?></p>
				<?php if(is_file($ex->getFile())):?><?php echo
self::highlightFile($ex->getFile(),$ex->getLine(),15,isset($ex->context)?$ex->context:NULL)?><?php endif?>
			</div></div>



			<?php if(isset($stack[0]['class'])&&$stack[0]['class']==='NDebugger'&&($stack[0]['function']==='_shutdownHandler'||$stack[0]['function']==='_errorHandler'))unset($stack[0])?>
			<?php if($stack):?>
			<div class="panel">
				<h2><a href="#netteBsPnl<?php echo++$counter?>" class="nette-toggle">Call stack</a></h2>

				<div id="netteBsPnl<?php echo$counter?>" class="inner">
				<ol>
					<?php foreach($stack
as$key=>$row):?>
					<li><p>

					<?php if(isset($row['file'])&&is_file($row['file'])):?>
						<?php echo
NDebugHelpers::editorLink($row['file'],$row['line'])?>
					<?php else:?>
						<i>inner-code</i><?php if(isset($row['line']))echo':',$row['line']?>
					<?php endif?>

					<?php if(isset($row['file'])&&is_file($row['file'])):?><a href="#netteBsSrc<?php echo"$level-$key"?>" class="nette-toggle-collapsed">source</a>&nbsp; <?php endif?>

					<?php if(isset($row['class']))echo
htmlspecialchars($row['class'].$row['type'])?>
					<?php echo
htmlspecialchars($row['function'])?>

					(<?php if(!empty($row['args'])):?><a href="#netteBsArgs<?php echo"$level-$key"?>" class="nette-toggle-collapsed">arguments</a><?php endif?>)
					</p>

					<?php if(!empty($row['args'])):?>
						<div class="nette-collapsed outer" id="netteBsArgs<?php echo"$level-$key"?>">
						<table>
						<?php

try{$r=isset($row['class'])?new
ReflectionMethod($row['class'],$row['function']):new
ReflectionFunction($row['function']);$params=$r->getParameters();}catch(Exception$e){$params=array();}foreach($row['args']as$k=>$v){echo'<tr><th>',htmlspecialchars(isset($params[$k])?'$'.$params[$k]->name:"#$k"),'</th><td>';echo
NDebugDumper::toHtml($v);echo"</td></tr>\n";}?>
						</table>
						</div>
					<?php endif?>


					<?php if(isset($row['file'])&&is_file($row['file'])):?>
						<div <?php if($expanded!==$key)echo'class="nette-collapsed"';?> id="netteBsSrc<?php echo"$level-$key"?>"><?php echo
self::highlightFile($row['file'],$row['line'])?></div>
					<?php endif?>

					</li>
					<?php endforeach?>
				</ol>
			</div></div>
			<?php endif?>



			<?php if(isset($ex->context)&&is_array($ex->context)):?>
			<div class="panel">
			<h2><a href="#netteBsPnl<?php echo++$counter?>" class="nette-toggle-collapsed">Variables</a></h2>

			<div id="netteBsPnl<?php echo$counter?>" class="nette-collapsed inner">
			<div class="outer">
			<table>
			<?php

foreach($ex->context
as$k=>$v){echo'<tr><th>$',htmlspecialchars($k),'</th><td>',NDebugDumper::toHtml($v),"</td></tr>\n";}?>
			</table>
			</div>
			</div></div>
			<?php endif?>

		<?php }while((method_exists($ex,'getPrevious')&&$ex=$ex->getPrevious())||(isset($ex->previous)&&$ex=$ex->previous));?>
		<?php while(--$level)echo'</div></div>'?>



		<?php $bottomPanels=array()?>
		<?php foreach($panels
as$panel):?>
		<?php $panel=call_user_func($panel,NULL);if(empty($panel['tab'])||empty($panel['panel']))continue;?>
		<?php if(!empty($panel['bottom'])){$bottomPanels[]=$panel;continue;}?>
		<div class="panel">
			<h2><a href="#netteBsPnl<?php echo++$counter?>" class="nette-toggle-collapsed"><?php echo
htmlSpecialChars($panel['tab'])?></a></h2>

			<div id="netteBsPnl<?php echo$counter?>" class="nette-collapsed inner">
			<?php echo$panel['panel']?>
		</div></div>
		<?php endforeach?>



		<div class="panel">
		<h2><a href="#netteBsPnl<?php echo++$counter?>" class="nette-toggle-collapsed">Environment</a></h2>

		<div id="netteBsPnl<?php echo$counter?>" class="nette-collapsed inner">
			<h3><a href="#netteBsPnl<?php echo++$counter?>" class="nette-toggle">$_SERVER</a></h3>
			<div id="netteBsPnl<?php echo$counter?>" class="outer">
			<table>
			<?php

foreach($_SERVER
as$k=>$v)echo'<tr><th>',htmlspecialchars($k),'</th><td>',NDebugDumper::toHtml($v),"</td></tr>\n";?>
			</table>
			</div>


			<h3><a href="#netteBsPnl<?php echo++$counter?>" class="nette-toggle">$_SESSION</a></h3>
			<div id="netteBsPnl<?php echo$counter?>" class="outer">
			<?php if(empty($_SESSION)):?>
			<p><i>empty</i></p>
			<?php else:?>
			<table>
			<?php

foreach($_SESSION
as$k=>$v)echo'<tr><th>',htmlspecialchars($k),'</th><td>',$k==='__NF'?'<i>Nette Session</i>':NDebugDumper::toHtml($v),"</td></tr>\n";?>
			</table>
			<?php endif?>
			</div>


			<?php if(!empty($_SESSION['__NF']['DATA'])):?>
			<h3><a href="#netteBsPnl<?php echo++$counter?>" class="nette-toggle">Nette Session</a></h3>
			<div id="netteBsPnl<?php echo$counter?>" class="outer">
			<table>
			<?php

foreach($_SESSION['__NF']['DATA']as$k=>$v)echo'<tr><th>',htmlspecialchars($k),'</th><td>',NDebugDumper::toHtml($v),"</td></tr>\n";?>
			</table>
			</div>
			<?php endif?>


			<?php
$list=get_defined_constants(TRUE);if(!empty($list['user'])):?>
			<h3><a href="#netteBsPnl<?php echo++$counter?>" class="nette-toggle-collapsed">Constants</a></h3>
			<div id="netteBsPnl<?php echo$counter?>" class="outer nette-collapsed">
			<table>
			<?php

foreach($list['user']as$k=>$v){echo'<tr><th>',htmlspecialchars($k),'</th>';echo'<td>',NDebugDumper::toHtml($v),"</td></tr>\n";}?>
			</table>
			</div>
			<?php endif?>


			<h3><a href="#netteBsPnl<?php echo++$counter?>" class="nette-toggle-collapsed">Included files</a> (<?php echo
count(get_included_files())?>)</h3>
			<div id="netteBsPnl<?php echo$counter?>" class="outer nette-collapsed">
			<table>
			<?php

foreach(get_included_files()as$v){echo'<tr><td>',htmlspecialchars($v),"</td></tr>\n";}?>
			</table>
			</div>


			<h3><a href="#netteBsPnl<?php echo++$counter?>" class="nette-toggle-collapsed">Configuration options</a></h3>
			<div id="netteBsPnl<?php echo$counter?>" class="outer nette-collapsed">
			<?php ob_start();@phpinfo(INFO_CONFIGURATION|INFO_MODULES);echo
preg_replace('#^.+<body>|</body>.+\z#s','',ob_get_clean())?>
			</div>
		</div></div>



		<div class="panel">
		<h2><a href="#netteBsPnl<?php echo++$counter?>" class="nette-toggle-collapsed">HTTP request</a></h2>

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
			<h3>$<?php echo
htmlspecialchars($name)?></h3>
			<?php if(empty($GLOBALS[$name])):?>
			<p><i>empty</i></p>
			<?php else:?>
			<div class="outer">
			<table>
			<?php

foreach($GLOBALS[$name]as$k=>$v)echo'<tr><th>',htmlspecialchars($k),'</th><td>',NDebugDumper::toHtml($v),"</td></tr>\n";?>
			</table>
			</div>
			<?php endif?>
			<?php endforeach?>
		</div></div>



		<div class="panel">
		<h2><a href="#netteBsPnl<?php echo++$counter?>" class="nette-toggle-collapsed">HTTP response</a></h2>

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



		<?php foreach($bottomPanels
as$panel):?>
		<div class="panel">
			<h2><a href="#netteBsPnl<?php echo++$counter?>" class="nette-toggle"><?php echo
htmlSpecialChars($panel['tab'])?></a></h2>

			<div id="netteBsPnl<?php echo$counter?>" class="inner">
			<?php echo$panel['panel']?>
		</div></div>
		<?php endforeach?>



		<ul>
			<li>Report generated at <?php echo@date('Y/m/d H:i:s',NDebugger::$time)?></li>
			<?php if(preg_match('#^https?://#',NDebugger::$source)):?>
				<li><a href="<?php echo
htmlSpecialChars(NDebugger::$source)?>"><?php echo
htmlSpecialChars(NDebugger::$source)?></a></li>
			<?php elseif(NDebugger::$source):?>
				<li><?php echo
htmlSpecialChars(NDebugger::$source)?></li>
			<?php endif?>
			<li>PHP <?php echo
htmlSpecialChars(PHP_VERSION)?></li>
			<?php if(isset($_SERVER['SERVER_SOFTWARE'])):?><li><?php echo
htmlSpecialChars($_SERVER['SERVER_SOFTWARE'])?></li><?php endif?>
			<?php if(class_exists('NFramework')):?><li><?php echo
htmlSpecialChars('Nette Framework '.NFramework::VERSION)?> <i>(revision <?php echo
htmlSpecialChars(NFramework::REVISION)?>)</i></li><?php endif?>
		</ul>
	</div>
</div>

<script type="text/javascript">/*<![CDATA[*/var bs=document.getElementById("netteBluescreen");document.body.appendChild(bs);document.onkeyup=function(b){b=b||window.event;27==b.keyCode&&(!b.shiftKey&&!b.altKey&&!b.ctrlKey&&!b.metaKey)&&document.getElementById("netteBluescreenIcon").click()};
for(var i=0,styles=document.styleSheets;i<styles.length;i++)"nette-debug"!==(styles[i].owningElement||styles[i].ownerNode).className?(styles[i].oldDisabled=styles[i].disabled,styles[i].disabled=!0):styles[i].addRule?styles[i].addRule(".nette-collapsed","display: none"):styles[i].insertRule(".nette-collapsed { display: none }",0);
bs.onclick=function(b){for(var b=b||window.event,a=b.target||b.srcElement;a&&(!a.tagName||0>a.className.indexOf("nette-toggle"));a=a.parentNode);if(!a)return!0;var d=-1<a.className.indexOf("nette-toggle-collapsed"),c=a.getAttribute("data-ref")||a.getAttribute("href");if(c&&"#"!==c)dest=document.getElementById(c.substring(1));else for(dest=a.nextSibling;dest&&1!==dest.nodeType;dest=dest.nextSibling);a.className="nette-toggle"+(d?"":"-collapsed");dest.style.display=d?"div"===dest.tagName.toLowerCase()?
"block":"inline":"none";if("netteBluescreenIcon"===a.id){a=0;for(c=document.styleSheets;a<c.length;a++)if("nette-debug"!==(c[a].owningElement||c[a].ownerNode).className)c[a].disabled=d?!0:c[a].oldDisabled}b.preventDefault?b.preventDefault():b.returnValue=!1;b.stopPropagation?b.stopPropagation():b.cancelBubble=!0};/*]]>*/</script>
</body>
</html>
<?php }public
static
function
highlightFile($file,$line,$lines=15,$vars=array()){$source=@file_get_contents($file);if($source){return
self::highlightPhp($source,$line,$lines,$vars);}}public
static
function
highlightPhp($source,$line,$lines=15,$vars=array()){if(function_exists('ini_set')){ini_set('highlight.comment','#998; font-style: italic');ini_set('highlight.default','#000');ini_set('highlight.html','#06B');ini_set('highlight.keyword','#D24; font-weight: bold');ini_set('highlight.string','#080');}$source=str_replace(array("\r\n","\r"),"\n",$source);$source=explode("\n",highlight_string($source,TRUE));$spans=1;$out=$source[0];$source=explode('<br />',$source[1]);array_unshift($source,NULL);$start=$i=max(1,$line-floor($lines*2/3));while(--$i>=1){if(preg_match('#.*(</?span[^>]*>)#',$source[$i],$m)){if($m[1]!=='</span>'){$spans++;$out.=$m[1];}break;}}$source=array_slice($source,$start,$lines,TRUE);end($source);$numWidth=strlen((string)key($source));foreach($source
as$n=>$s){$spans+=substr_count($s,'<span')-substr_count($s,'</span');$s=str_replace(array("\r","\n"),array('',''),$s);preg_match_all('#<[^>]+>#',$s,$tags);if($n==$line){$out.=sprintf("<span class='highlight'>%{$numWidth}s:    %s\n</span>%s",$n,strip_tags($s),implode('',$tags[0]));}else{$out.=sprintf("<span class='line'>%{$numWidth}s:</span>    %s\n",$n,$s);}}$out.=str_repeat('</span>',$spans).'</code>';$out=preg_replace_callback('#">\$(\w+)(&nbsp;)?</span>#',create_function('$m','extract($GLOBALS[0]['.array_push($GLOBALS[0],array('vars'=>$vars)).'-1], EXTR_REFS);
			return isset($vars[$m[1]])
				? \'" title="\' . str_replace(\'"\', \'&quot;\', strip_tags(NDebugDumper::toHtml($vars[$m[1]]))) . $m[0]
				: $m[0];
		'),$out);return"<pre class='php'><div>$out</div></pre>";}public
function
isCollapsed($file){foreach($this->collapsePaths
as$path){if(strpos(strtr($file,'\\','/'),strtr("$path/",'\\','/'))===0){return
TRUE;}}return
FALSE;}}class
NDebugDumper{const
DEPTH='depth',TRUNCATE='truncate',COLLAPSE='collapse',COLLAPSE_COUNT='collapsecount',LOCATION='location';public
static$terminalColors=array('bool'=>'1;33','null'=>'1;33','number'=>'1;32','string'=>'1;36','array'=>'1;31','key'=>'1;37','object'=>'1;31','visibility'=>'1;30','resource'=>'1;37','indent'=>'1;30');public
static$resources=array('stream'=>'stream_get_meta_data','stream-context'=>'stream_context_get_options','curl'=>'curl_getinfo');public
static
function
dump($var,array$options=NULL){if(preg_match('#^Content-Type: text/html#im',implode("\n",headers_list()))){echo
self::toHtml($var,$options);}elseif(self::$terminalColors&&substr(getenv('TERM'),0,5)==='xterm'){echo
self::toTerminal($var,$options);}else{echo
self::toText($var,$options);}return$var;}public
static
function
toHtml($var,array$options=NULL){list($file,$line,$code)=empty($options[self::LOCATION])?NULL:self::findLocation();return'<pre class="nette-dump"'.($file?' title="'.htmlspecialchars("$code\nin file $file on line $line").'">':'>').self::dumpVar($var,(array)$options+array(self::DEPTH=>4,self::TRUNCATE=>150,self::COLLAPSE=>FALSE,self::COLLAPSE_COUNT=>7)).($file?'<small>in <a href="editor://open/?file='.rawurlencode($file)."&amp;line=$line\">".htmlspecialchars($file).":$line</a></small>":'')."</pre>\n";}public
static
function
toText($var,array$options=NULL){return
htmlspecialchars_decode(strip_tags(self::toHtml($var,$options)),ENT_QUOTES);}public
static
function
toTerminal($var,array$options=NULL){return
htmlspecialchars_decode(strip_tags(preg_replace_callback('#<span class="nette-dump-(\w+)">|</span>#',create_function('$m','
			return "\\033[" . (isset($m[1], NDebugDumper::$terminalColors[$m[1]]) ? NDebugDumper::$terminalColors[$m[1]] : \'0\') . "m";
		'),self::toHtml($var,$options))),ENT_QUOTES);}private
static
function
dumpVar(&$var,array$options,$level=0){if(method_exists(__CLASS__,$m='dump'.gettype($var))){return
call_user_func_array(array(__CLASS__,$m),array(&$var,$options,$level));}else{return"<span>unknown type</span>\n";}}private
static
function
dumpNull(){return"<span class=\"nette-dump-null\">NULL</span>\n";}private
static
function
dumpBoolean(&$var){return'<span class="nette-dump-bool">'.($var?'TRUE':'FALSE')."</span>\n";}private
static
function
dumpInteger(&$var){return"<span class=\"nette-dump-number\">$var</span>\n";}private
static
function
dumpDouble(&$var){$var=var_export($var,TRUE);return'<span class="nette-dump-number">'.$var.(strpos($var,'.')===FALSE?'.0':'')."</span>\n";}private
static
function
dumpString(&$var,$options){return'<span class="nette-dump-string">'.self::encodeString($options[self::TRUNCATE]&&strlen($var)>$options[self::TRUNCATE]?substr($var,0,$options[self::TRUNCATE]).' ... ':$var).'</span>'.(strlen($var)>1?' ('.strlen($var).')':'')."\n";}private
static
function
dumpArray(&$var,$options,$level){static$marker;if($marker===NULL){$marker=uniqid("\x00",TRUE);}$out='<span class="nette-dump-array">array</span> (';if(empty($var)){return$out."0)\n";}elseif(isset($var[$marker])){return$out.(count($var)-1).") [ <i>RECURSION</i> ]\n";}elseif(!$options[self::DEPTH]||$level<$options[self::DEPTH]){$collapsed=$level?count($var)>=$options[self::COLLAPSE_COUNT]:$options[self::COLLAPSE];$out='<span class="nette-toggle'.($collapsed?'-collapsed">':'">').$out.count($var).")</span>\n<div".($collapsed?' class="nette-collapsed"':'').">";$var[$marker]=TRUE;foreach($var
as$k=>&$v){if($k!==$marker){$out.='<span class="nette-dump-indent">   '.str_repeat('|  ',$level).'</span>'.'<span class="nette-dump-key">'.(preg_match('#^\w+\z#',$k)?$k:self::encodeString($k)).'</span> => '.self::dumpVar($v,$options,$level+1);}}unset($var[$marker]);return$out.'</div>';}else{return$out.count($var).") [ ... ]\n";}}private
static
function
dumpObject(&$var,$options,$level){if($var
instanceof
Closure){$rc=new
ReflectionFunction($var);$fields=array();foreach($rc->getParameters()as$param){$fields[]='$'.$param->getName();}$fields=array('file'=>$rc->getFileName(),'line'=>$rc->getStartLine(),'parameters'=>implode(', ',$fields));}else{$fields=(array)$var;}static$list=array();$out='<span class="nette-dump-object">'.get_class($var)."</span> (".count($fields).')';if(empty($fields)){return$out."\n";}elseif(in_array($var,$list,TRUE)){return$out." { <i>RECURSION</i> }\n";}elseif(!$options[self::DEPTH]||$level<$options[self::DEPTH]||$var
instanceof
Closure){$collapsed=$level?count($fields)>=$options[self::COLLAPSE_COUNT]:$options[self::COLLAPSE];$out='<span class="nette-toggle'.($collapsed?'-collapsed">':'">').$out."</span>\n<div".($collapsed?' class="nette-collapsed"':'').">";$list[]=$var;foreach($fields
as$k=>&$v){$vis='';if($k[0]==="\x00"){$vis=' <span class="nette-dump-visibility">'.($k[1]==='*'?'protected':'private').'</span>';$k=substr($k,strrpos($k,"\x00")+1);}$out.='<span class="nette-dump-indent">   '.str_repeat('|  ',$level).'</span>'.'<span class="nette-dump-key">'.(preg_match('#^\w+\z#',$k)?$k:self::encodeString($k))."</span>$vis => ".self::dumpVar($v,$options,$level+1);}array_pop($list);return$out.'</div>';}else{return$out." { ... }\n";}}private
static
function
dumpResource(&$var,$options,$level){$type=get_resource_type($var);$out='<span class="nette-dump-resource">'.htmlSpecialChars($type).' resource</span>';if(isset(self::$resources[$type])){$out="<span class=\"nette-toggle-collapsed\">$out</span>\n<div class=\"nette-collapsed\">";foreach(call_user_func(self::$resources[$type],$var)as$k=>$v){$out.='<span class="nette-dump-indent">   '.str_repeat('|  ',$level).'</span>'.'<span class="nette-dump-key">'.htmlSpecialChars($k)."</span> => ".self::dumpVar($v,$options,$level+1);}return$out.'</div>';}return"$out\n";}private
static
function
encodeString($s){static$utf,$binary;if($utf===NULL){foreach(range("\x00","\xFF")as$ch){if(ord($ch)<32&&strpos("\r\n\t",$ch)===FALSE){$utf[$ch]=$binary[$ch]='\\x'.str_pad(dechex(ord($ch)),2,'0',STR_PAD_LEFT);}elseif(ord($ch)<127){$utf[$ch]=$binary[$ch]=$ch;}else{$utf[$ch]=$ch;$binary[$ch]='\\x'.dechex(ord($ch));}}$binary["\\"]='\\\\';$binary["\r"]='\\r';$binary["\n"]='\\n';$binary["\t"]='\\t';$utf['\\x']=$binary['\\x']='\\\\x';}$s=strtr($s,preg_match('#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u',$s)||preg_last_error()?$binary:$utf);return'"'.htmlSpecialChars($s,ENT_NOQUOTES).'"';}private
static
function
findLocation(){foreach(PHP_VERSION_ID<50205?debug_backtrace():debug_backtrace(FALSE)as$item){if(isset($item['file'])&&strpos($item['file'],dirname(__FILE__))===0){continue;}elseif(!isset($item['file'],$item['line'])||!is_file($item['file'])){break;}else{$lines=file($item['file']);$line=$lines[$item['line']-1];return
array($item['file'],$item['line'],preg_match('#\w*dump(er::\w+)?\(.*\)#i',$line,$m)?$m[0]:$line);}}}}final
class
NDefaultBarPanel
implements
IBarPanel{private$id;public$data;public
function
__construct($id){$this->id=$id;}public
function
getTab(){ob_start();$data=$this->data;if($this->id==='time'){?>
<span title="Execution time"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAJ6SURBVDjLjZO7T1NhGMY7Mji6uJgYt8bElTjof6CDg4sMSqIxJsRGB5F4TwQSIg1QKC0KWmkZEEsKtEcSxF5ohV5pKSicXqX3aqGn957z+PUEGopiGJ583/A+v3znvPkJAAjWR0VNJG0kGhKahCFhXcN3YBFfx8Kry6ym4xIzce88/fbWGY2k5WRb77UTTbWuYA9gDGg7EVmSIOF4g5T7HZKuMcSW5djWDyL0uRf0dCc8inYYxTcw9fAiCMBYB3gVj1z7gLhNTjKCqHkYP79KENC9Bq3uxrrqORzy+9D3tPAAccspVx1gWg0KbaZFbGllWFM+xrKkFQudV0CeDfJsjN4+C2nracjunoPq5VXIBrowMK4V1gG1LGyWdbZwCalsBYUyh2KFQzpXxVqkAGswD3+qBDpZwow9iYE5v26/VwfUQnnznyhvjguQYabIIpKpYD1ahI8UTT92MUSFuP5Z/9TBTgOgFrVjp3nakaG/0VmEfpX58pwzjUEquNk362s+PP8XYD/KpYTBHmRg9Wch0QX1R80dCZhYipudYQY2Auib8RmODVCa4hfUK4ngaiiLNFNFdKeCWWscXZMbWy9Unv9/gsIQU09a4pwvUeA3Uapy2C2wCKXL0DqTePLexbWPOv79E8f0UWrencZ2poxciUWZlKssB4bcHeE83NsFuMgpo2iIpMuNa1TNu4XjhggWvb+R2K3wZdLlAZl8Fd9jRb5sD+Xx0RJBx5gdom6VsMEFDyWF0WyCeSOFcDKPnRxZYTQL5Rc/nn1w4oFsBaIhC3r6FRh5erPRhYMyHdeFw4C6zkRhmijM7CnMu0AUZonCDCnRJBqSus5/ABD6Ba5CkQS8AAAAAElFTkSuQmCC"
/><?php echo
number_format((microtime(TRUE)-NDebugger::$time)*1000,1,'.',' ')?> ms</span>
<?php }elseif($this->id==='memory'){?>
<span title="The peak of allocated memory"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAGvSURBVDjLpZO7alZREEbXiSdqJJDKYJNCkPBXYq12prHwBezSCpaidnY+graCYO0DpLRTQcR3EFLl8p+9525xgkRIJJApB2bN+gZmqCouU+NZzVef9isyUYeIRD0RTz482xouBBBNHi5u4JlkgUfx+evhxQ2aJRrJ/oFjUWysXeG45cUBy+aoJ90Sj0LGFY6anw2o1y/mK2ZS5pQ50+2XiBbdCvPk+mpw2OM/Bo92IJMhgiGCox+JeNEksIC11eLwvAhlzuAO37+BG9y9x3FTuiWTzhH61QFvdg5AdAZIB3Mw50AKsaRJYlGsX0tymTzf2y1TR9WwbogYY3ZhxR26gBmocrxMuhZNE435FtmSx1tP8QgiHEvj45d3jNlONouAKrjjzWaDv4CkmmNu/Pz9CzVh++Yd2rIz5tTnwdZmAzNymXT9F5AtMFeaTogJYkJfdsaaGpyO4E62pJ0yUCtKQFxo0hAT1JU2CWNOJ5vvP4AIcKeao17c2ljFE8SKEkVdWWxu42GYK9KE4c3O20pzSpyyoCx4v/6ECkCTCqccKorNxR5uSXgQnmQkw2Xf+Q+0iqQ9Ap64TwAAAABJRU5ErkJggg=="
/><?php echo
function_exists('memory_get_peak_usage')?number_format(memory_get_peak_usage()/1000000,2,'.',' '):'n/a';?> MB</span>
<?php }elseif($this->id==='dumps'&&$this->data){?>
<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAIASURBVDjLpVPPaxNREJ6Vt01caH4oWk1T0ZKlGIo9RG+BUsEK4kEP/Q8qPXnpqRdPBf8A8Wahhx7FQ0GF9FJ6UksqwfTSBDGyB5HkkphC9tfb7jfbtyQQTx142byZ75v5ZnZWC4KALmICPy+2DkvKIX2f/POz83LxCL7nrz+WPNcll49DrhM9v7xdO9JW330DuXrrqkFSgig5iR2Cfv3t3gNxOnv5BwU+eZ5HuON5/PMPJZKJ+yKQfpW0S7TxdC6WJaWkyvff1LDaFRAeLZj05MHsiPTS6hua0PUqtwC5sHq9zv9RYWl+nu5cETcnJ1M0M5WlWq3GsX6/T+VymRzHDluZiGYAAsw0TQahV8uyyGq1qFgskm0bHIO/1+sx1rFtchJhArwEyIQ1Gg2WD2A6nWawHQJVDIWgIJfLhQowTIeE9D0mKAU8qPC0220afsWFQoH93W6X7yCDJ+DEBeBmsxnPIJVKxWQVUwry+XyUwBlKMKwA8jqdDhOVCqVAzQDVvXAXhOdGBFgymYwrGoZBmUyGjxCCdF0fSahaFdgoTHRxfTveMCXvWfkuE3Y+f40qhgT/nMitupzApdvT18bu+YeDQwY9Xl4aG9/d/URiMBhQq/dvZMeVghtT17lSZW9/rAKsvPa/r9Fc2dw+Pe0/xI6kM9mT5vtXy+Nw2kU/5zOGRpvuMIu0YAAAAABJRU5ErkJggg==" />variables
<?php }elseif($this->id==='errors'&&$this->data){?>
<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAIsSURBVDjLpVNLSJQBEP7+h6uu62vLVAJDW1KQTMrINQ1vPQzq1GOpa9EppGOHLh0kCEKL7JBEhVCHihAsESyJiE4FWShGRmauu7KYiv6Pma+DGoFrBQ7MzGFmPr5vmDFIYj1mr1WYfrHPovA9VVOqbC7e/1rS9ZlrAVDYHig5WB0oPtBI0TNrUiC5yhP9jeF4X8NPcWfopoY48XT39PjjXeF0vWkZqOjd7LJYrmGasHPCCJbHwhS9/F8M4s8baid764Xi0Ilfp5voorpJfn2wwx/r3l77TwZUvR+qajXVn8PnvocYfXYH6k2ioOaCpaIdf11ivDcayyiMVudsOYqFb60gARJYHG9DbqQFmSVNjaO3K2NpAeK90ZCqtgcrjkP9aUCXp0moetDFEeRXnYCKXhm+uTW0CkBFu4JlxzZkFlbASz4CQGQVBFeEwZm8geyiMuRVntzsL3oXV+YMkvjRsydC1U+lhwZsWXgHb+oWVAEzIwvzyVlk5igsi7DymmHlHsFQR50rjl+981Jy1Fw6Gu0ObTtnU+cgs28AKgDiy+Awpj5OACBAhZ/qh2HOo6i+NeA73jUAML4/qWux8mt6NjW1w599CS9xb0mSEqQBEDAtwqALUmBaG5FV3oYPnTHMjAwetlWksyByaukxQg2wQ9FlccaK/OXA3/uAEUDp3rNIDQ1ctSk6kHh1/jRFoaL4M4snEMeD73gQx4M4PsT1IZ5AfYH68tZY7zv/ApRMY9mnuVMvAAAAAElFTkSuQmCC"
/><span class="nette-warning"><?php echo
array_sum($data)?> errors</span>
<?php }return
ob_get_clean();}public
function
getPanel(){ob_start();$data=$this->data;if($this->id==='dumps'){?>
<style class="nette-debug">#nette-debug .nette-DumpPanel h2{font:11pt/1.5 sans-serif;margin:0;padding:2px 8px;background:#3484d2;color:white}#nette-debug .nette-DumpPanel table{width:100%}</style>


<h1>Dumped variables</h1>

<div class="nette-inner nette-DumpPanel">
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
<?php }elseif($this->id==='errors'){?>
<h1>Errors</h1>

<div class="nette-inner">
<table>
<?php $i=0?>
<?php foreach($data
as$item=>$count):list($message,$file,$line)=explode('|',$item)?>
<tr class="<?php echo$i++%
2?'nette-alt':''?>">
	<td class="nette-right"><?php echo$count?"$count\xC3\x97":''?></td>
	<td><pre><?php echo
htmlspecialchars($message),' in ',NDebugHelpers::editorLink($file,$line)?></pre></td>
</tr>
<?php endforeach?>
</table>
</div>
<?php }return
ob_get_clean();}}class
NFireLogger{const
DEBUG='debug',INFO='info',WARNING='warning',ERROR='error',CRITICAL='critical';private
static$payload=array('logs'=>array());public
static
function
log($message,$priority=self::DEBUG){if(!isset($_SERVER['HTTP_X_FIRELOGGER'])||headers_sent()){return
FALSE;}$item=array('name'=>'PHP','level'=>$priority,'order'=>count(self::$payload['logs']),'time'=>str_pad(number_format((microtime(TRUE)-NDebugger::$time)*1000,1,'.',' '),8,'0',STR_PAD_LEFT).' ms','template'=>'','message'=>'','style'=>'background:#767ab6');$args=func_get_args();if(isset($args[0])&&is_string($args[0])){$item['template']=array_shift($args);}if(isset($args[0])&&$args[0]instanceof
Exception){$e=array_shift($args);$trace=$e->getTrace();if(isset($trace[0]['class'])&&$trace[0]['class']==='NDebugger'&&($trace[0]['function']==='_shutdownHandler'||$trace[0]['function']==='_errorHandler')){unset($trace[0]);}$file=str_replace(dirname(dirname(dirname($e->getFile()))),"\xE2\x80\xA6",$e->getFile());$item['template']=($e
instanceof
ErrorException?'':get_class($e).': ').$e->getMessage().($e->getCode()?' #'.$e->getCode():'').' in '.$file.':'.$e->getLine();$item['pathname']=$e->getFile();$item['lineno']=$e->getLine();}else{$trace=debug_backtrace();if(isset($trace[1]['class'])&&$trace[1]['class']==='NDebugger'&&($trace[1]['function']==='fireLog')){unset($trace[0]);}foreach($trace
as$frame){if(isset($frame['file'])&&is_file($frame['file'])){$item['pathname']=$frame['file'];$item['lineno']=$frame['line'];break;}}}$item['exc_info']=array('','',array());$item['exc_frames']=array();foreach($trace
as$frame){$frame+=array('file'=>NULL,'line'=>NULL,'class'=>NULL,'type'=>NULL,'function'=>NULL,'object'=>NULL,'args'=>NULL);$item['exc_info'][2][]=array($frame['file'],$frame['line'],"$frame[class]$frame[type]$frame[function]",$frame['object']);$item['exc_frames'][]=$frame['args'];}if(isset($args[0])&&in_array($args[0],array(self::DEBUG,self::INFO,self::WARNING,self::ERROR,self::CRITICAL),TRUE)){$item['level']=array_shift($args);}$item['args']=$args;self::$payload['logs'][]=self::jsonDump($item,-1);foreach(str_split(base64_encode(@json_encode(self::$payload)),4990)as$k=>$v){header("FireLogger-de11e-$k:$v");}return
TRUE;}private
static
function
jsonDump(&$var,$level=0){if(is_bool($var)||is_null($var)||is_int($var)||is_float($var)){return$var;}elseif(is_string($var)){if(NDebugger::$maxLen&&strlen($var)>NDebugger::$maxLen){$var=substr($var,0,NDebugger::$maxLen)." \xE2\x80\xA6 ";}return@iconv('UTF-16','UTF-8//IGNORE',iconv('UTF-8','UTF-16//IGNORE',$var));}elseif(is_array($var)){static$marker;if($marker===NULL){$marker=uniqid("\x00",TRUE);}if(isset($var[$marker])){return"\xE2\x80\xA6RECURSION\xE2\x80\xA6";}elseif($level<NDebugger::$maxDepth||!NDebugger::$maxDepth){$var[$marker]=TRUE;$res=array();foreach($var
as$k=>&$v){if($k!==$marker){$res[self::jsonDump($k)]=self::jsonDump($v,$level+1);}}unset($var[$marker]);return$res;}else{return" \xE2\x80\xA6 ";}}elseif(is_object($var)){$arr=(array)$var;static$list=array();if(in_array($var,$list,TRUE)){return"\xE2\x80\xA6RECURSION\xE2\x80\xA6";}elseif($level<NDebugger::$maxDepth||!NDebugger::$maxDepth){$list[]=$var;$res=array("\x00"=>'(object) '.get_class($var));foreach($arr
as$k=>&$v){if($k[0]==="\x00"){$k=substr($k,strrpos($k,"\x00")+1);}$res[self::jsonDump($k)]=self::jsonDump($v,$level+1);}array_pop($list);return$res;}else{return" \xE2\x80\xA6 ";}}elseif(is_resource($var)){return"resource ".get_resource_type($var);}else{return"unknown type";}}}final
class
NDebugHelpers{public
static
function
editorLink($file,$line){if(NDebugger::$editor&&is_file($file)){$dir=dirname(strtr($file,'/',DIRECTORY_SEPARATOR));$base=isset($_SERVER['SCRIPT_FILENAME'])?dirname(dirname(strtr($_SERVER['SCRIPT_FILENAME'],'/',DIRECTORY_SEPARATOR))):dirname($dir);if(substr($dir,0,strlen($base))===$base){$dir='...'.substr($dir,strlen($base));}return
NHtml::el('a')->href(strtr(NDebugger::$editor,array('%file'=>rawurlencode($file),'%line'=>$line)))->title("$file:$line")->setHtml(htmlSpecialChars(rtrim($dir,DIRECTORY_SEPARATOR)).DIRECTORY_SEPARATOR.'<b>'.htmlSpecialChars(basename($file)).'</b>'.($line?":$line":''));}else{return
NHtml::el('span')->setText($file.($line?":$line":''));}}public
static
function
findTrace(array$trace,$method,&$index=NULL){$m=explode('::',$method);foreach($trace
as$i=>$item){if(isset($item['function'])&&$item['function']===end($m)&&isset($item['class'])===isset($m[1])&&(!isset($item['class'])||$item['class']===$m[0]||$m[0]==='*'||is_subclass_of($item['class'],$m[0]))){$index=$i;return$item;}}}public
static
function
htmlDump($var){trigger_error(__METHOD__.'() is deprecated; use NDebugDumper::toHtml() instead.',E_USER_WARNING);return
NDebugDumper::toHtml($var);}public
static
function
clickableDump($var){trigger_error(__METHOD__.'() is deprecated; use NDebugDumper::toHtml() instead.',E_USER_WARNING);return
NDebugDumper::toHtml($var);}public
static
function
textDump($var){trigger_error(__METHOD__.'() is deprecated; use NDebugDumper::toText() instead.',E_USER_WARNING);return
NDebugDumper::toText($var);}}class
NLogger{const
DEBUG='debug',INFO='info',WARNING='warning',ERROR='error',CRITICAL='critical';public
static$emailSnooze=172800;public$mailer=array(__CLASS__,'defaultMailer');public$directory;public$email;public
function
log($message,$priority=self::INFO){if(!is_dir($this->directory)){throw
new
DirectoryNotFoundException("Directory '$this->directory' is not found or is not directory.");}if(is_array($message)){$message=implode(' ',$message);}$res=error_log(trim($message).PHP_EOL,3,$this->directory.'/'.strtolower($priority).'.log');if(($priority===self::ERROR||$priority===self::CRITICAL)&&$this->email&&$this->mailer&&@filemtime($this->directory.'/email-sent')+self::$emailSnooze<time()&&@file_put_contents($this->directory.'/email-sent','sent')){call_user_func($this->mailer,$message,$this->email);}return$res;}public
static
function
defaultMailer($message,$email){$host=php_uname('n');foreach(array('HTTP_HOST','SERVER_NAME','HOSTNAME')as$item){if(isset($_SERVER[$item])){$host=$_SERVER[$item];break;}}$parts=str_replace(array("\r\n","\n"),array("\n",PHP_EOL),array('headers'=>implode("\n",array("From: noreply@$host",'X-Mailer: Nette Framework','Content-Type: text/plain; charset=UTF-8','Content-Transfer-Encoding: 8bit'))."\n",'subject'=>"PHP: An error occurred on the server $host",'body'=>"[".@date('Y-m-d H:i:s')."] $message"));mail($email,$parts['subject'],$parts['body'],$parts['headers']);}}class
NHtml
implements
ArrayAccess,Countable{private$name;private$isEmpty;public$attrs=array();protected$children=array();public
static$xhtml=TRUE;public
static$emptyElements=array('img'=>1,'hr'=>1,'br'=>1,'input'=>1,'meta'=>1,'area'=>1,'embed'=>1,'keygen'=>1,'source'=>1,'base'=>1,'col'=>1,'link'=>1,'param'=>1,'basefont'=>1,'frame'=>1,'isindex'=>1,'wbr'=>1,'command'=>1);public
static
function
el($name=NULL,$attrs=NULL){$el=new
self;$parts=explode(' ',$name,2);$el->setName($parts[0]);if(is_array($attrs)){$el->attrs=$attrs;}elseif($attrs!==NULL){$el->setText($attrs);}return$el;}final
public
function
setName($name,$isEmpty=NULL){if($name!==NULL&&!is_string($name)){throw
new
InvalidArgumentException("Name must be string or NULL, ".gettype($name)." given.");}$this->name=$name;$this->isEmpty=$isEmpty===NULL?isset(self::$emptyElements[$name]):(bool)$isEmpty;return$this;}final
public
function
getName(){return$this->name;}final
public
function
isEmpty(){return$this->isEmpty;}public
function
addAttributes(array$attrs){$this->attrs=$attrs+$this->attrs;return$this;}final
public
function
__set($name,$value){$this->attrs[$name]=$value;}final
public
function&__get($name){return$this->attrs[$name];}final
public
function
__unset($name){unset($this->attrs[$name]);}final
public
function
__call($m,$args){$p=substr($m,0,3);if($p==='get'||$p==='set'||$p==='add'){$m=substr($m,3);$m[0]=$m[0]|"\x20";if($p==='get'){return
isset($this->attrs[$m])?$this->attrs[$m]:NULL;}elseif($p==='add'){$args[]=TRUE;}}if(count($args)===0){}elseif(count($args)===1){$this->attrs[$m]=$args[0];}elseif((string)$args[0]===''){$tmp=&$this->attrs[$m];}elseif(!isset($this->attrs[$m])||is_array($this->attrs[$m])){$this->attrs[$m][$args[0]]=$args[1];}else{$this->attrs[$m]=array($this->attrs[$m],$args[0]=>$args[1]);}return$this;}final
public
function
href($path,$query=NULL){if($query){$query=http_build_query($query,NULL,'&');if($query!==''){$path.='?'.$query;}}$this->attrs['href']=$path;return$this;}final
public
function
setHtml($html){if($html===NULL){$html='';}elseif(is_array($html)){throw
new
InvalidArgumentException("Textual content must be a scalar, ".gettype($html)." given.");}else{$html=(string)$html;}$this->removeChildren();$this->children[]=$html;return$this;}final
public
function
getHtml(){$s='';foreach($this->children
as$child){if(is_object($child)){$s.=$child->render();}else{$s.=$child;}}return$s;}final
public
function
setText($text){if(!is_array($text)){$text=htmlspecialchars((string)$text,ENT_NOQUOTES);}return$this->setHtml($text);}final
public
function
getText(){return
html_entity_decode(strip_tags($this->getHtml()),ENT_QUOTES,'UTF-8');}final
public
function
add($child){return$this->insert(NULL,$child);}final
public
function
create($name,$attrs=NULL){$this->insert(NULL,$child=self::el($name,$attrs));return$child;}public
function
insert($index,$child,$replace=FALSE){if($child
instanceof
NHtml||is_scalar($child)){if($index===NULL){$this->children[]=$child;}else{array_splice($this->children,(int)$index,$replace?1:0,array($child));}}else{throw
new
InvalidArgumentException("Child node must be scalar or Html object, ".(is_object($child)?get_class($child):gettype($child))." given.");}return$this;}final
public
function
offsetSet($index,$child){$this->insert($index,$child,TRUE);}final
public
function
offsetGet($index){return$this->children[$index];}final
public
function
offsetExists($index){return
isset($this->children[$index]);}public
function
offsetUnset($index){if(isset($this->children[$index])){array_splice($this->children,(int)$index,1);}}final
public
function
count(){return
count($this->children);}public
function
removeChildren(){$this->children=array();}final
public
function
getChildren(){return$this->children;}final
public
function
render($indent=NULL){$s=$this->startTag();if(!$this->isEmpty){if($indent!==NULL){$indent++;}foreach($this->children
as$child){if(is_object($child)){$s.=$child->render($indent);}else{$s.=$child;}}$s.=$this->endTag();}if($indent!==NULL){return"\n".str_repeat("\t",$indent-1).$s."\n".str_repeat("\t",max(0,$indent-2));}return$s;}final
public
function
__toString(){return$this->render();}final
public
function
startTag(){if($this->name){return'<'.$this->name.$this->attributes().(self::$xhtml&&$this->isEmpty?' />':'>');}else{return'';}}final
public
function
endTag(){return$this->name&&!$this->isEmpty?'</'.$this->name.'>':'';}final
public
function
attributes(){if(!is_array($this->attrs)){return'';}$s='';foreach($this->attrs
as$key=>$value){if($value===NULL||$value===FALSE){continue;}elseif($value===TRUE){if(self::$xhtml){$s.=' '.$key.'="'.$key.'"';}else{$s.=' '.$key;}continue;}elseif(is_array($value)){if($key==='data'){foreach($value
as$k=>$v){if($v!==NULL&&$v!==FALSE){$s.=' data-'.$k.'="'.htmlspecialchars((string)$v).'"';}}continue;}$tmp=NULL;foreach($value
as$k=>$v){if($v!=NULL){$tmp[]=$v===TRUE?$k:(is_string($k)?$k.':'.$v:$v);}}if($tmp===NULL){continue;}$value=implode($key==='style'||!strncmp($key,'on',2)?';':' ',$tmp);}else{$value=(string)$value;}$s.=' '.$key.'="'.htmlspecialchars($value).'"';}$s=str_replace('@','&#64;',$s);return$s;}public
function
__clone(){foreach($this->children
as$key=>$value){if(is_object($value)){$this->children[$key]=clone$value;}}}}function
debug(){NDebugger::$strictMode=TRUE;NDebugger::enable(NDebugger::DEVELOPMENT);}function
dump($var){foreach(func_get_args()as$arg){NDebugger::dump($arg);}return$var;}function
dlog($var=NULL){if(func_num_args()===0){NDebugger::log(new
Exception,'dlog');}foreach(func_get_args()as$arg){NDebugger::log($arg,'dlog');}return$var;}final
class
NDebugger{public
static$productionMode;public
static$consoleMode;public
static$time;public
static$source;public
static$editor='editor://open/?file=%file&line=%line';public
static$browser;public
static$maxDepth=3;public
static$maxLen=150;public
static$showLocation=FALSE;public
static$consoleColors;const
DEVELOPMENT=FALSE,PRODUCTION=TRUE,DETECT=NULL;public
static$blueScreen;public
static$strictMode=FALSE;public
static$scream=FALSE;public
static$onFatalError=array();private
static$enabled=FALSE;private
static$lastError=FALSE;public
static$logger;public
static$fireLogger;public
static$logDirectory;public
static$email;public
static$mailer;public
static$emailSnooze;public
static$bar;private
static$errorPanel;private
static$dumpPanel;const
DEBUG='debug',INFO='info',WARNING='warning',ERROR='error',CRITICAL='critical';final
public
function
__construct(){throw
new
NStaticClassException;}public
static
function
_init(){self::$time=isset($_SERVER['REQUEST_TIME_FLOAT'])?$_SERVER['REQUEST_TIME_FLOAT']:microtime(TRUE);self::$productionMode=self::DETECT;if(isset($_SERVER['REQUEST_URI'])){self::$source=(isset($_SERVER['HTTPS'])&&strcasecmp($_SERVER['HTTPS'],'off')?'https://':'http://').(isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:(isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:'')).$_SERVER['REQUEST_URI'];}else{self::$source=empty($_SERVER['argv'])?'CLI':'CLI: '.implode(' ',$_SERVER['argv']);}self::$consoleColors=&NDebugDumper::$terminalColors;self::$logger=new
NLogger;self::$logDirectory=&self::$logger->directory;self::$email=&self::$logger->email;self::$mailer=&self::$logger->mailer;self::$emailSnooze=&NLogger::$emailSnooze;self::$fireLogger=new
NFireLogger;self::$blueScreen=new
NDebugBlueScreen;self::$blueScreen->collapsePaths[]=NETTE_DIR;self::$blueScreen->addPanel(create_function('$e','
			if ($e instanceof NTemplateException) {
				return array(
					\'tab\' => \'Template\',
					\'panel\' => \'<p><b>File:</b> \' . NDebugHelpers::editorLink($e->sourceFile, $e->sourceLine) . \'</p>\'
					. ($e->sourceLine ? NDebugBlueScreen::highlightFile($e->sourceFile, $e->sourceLine) : \'\')
				);
			} elseif ($e instanceof NNeonException && preg_match(\'#line (\\d+)#\', $e->getMessage(), $m)) {
				if ($item = NDebugHelpers::findTrace($e->getTrace(), \'NConfigNeonAdapter::load\')) {
					return array(
						\'tab\' => \'NEON\',
						\'panel\' => \'<p><b>File:</b> \' . NDebugHelpers::editorLink($item[\'args\'][0], $m[1]) . \'</p>\'
							. NDebugBlueScreen::highlightFile($item[\'args\'][0], $m[1])
					);
				} elseif ($item = NDebugHelpers::findTrace($e->getTrace(), \'NNeon::decode\')) {
					return array(
						\'tab\' => \'NEON\',
						\'panel\' => NDebugBlueScreen::highlightPhp($item[\'args\'][0], $m[1])
					);
				}
			}
		'));self::$bar=new
NDebugBar;self::$bar->addPanel(new
NDefaultBarPanel('time'));self::$bar->addPanel(new
NDefaultBarPanel('memory'));self::$bar->addPanel(self::$errorPanel=new
NDefaultBarPanel('errors'));self::$bar->addPanel(self::$dumpPanel=new
NDefaultBarPanel('dumps'));}public
static
function
enable($mode=NULL,$logDirectory=NULL,$email=NULL){error_reporting(E_ALL|E_STRICT);if(is_bool($mode)){self::$productionMode=$mode;}elseif($mode!==self::DETECT||self::$productionMode===NULL){$list=is_string($mode)?preg_split('#[,\s]+#',$mode):(array)$mode;if(!isset($_SERVER['HTTP_X_FORWARDED_FOR'])){$list[]='127.0.0.1';$list[]='::1';}self::$productionMode=!in_array(isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:php_uname('n'),$list,TRUE);}if(is_string($logDirectory)){self::$logDirectory=realpath($logDirectory);if(self::$logDirectory===FALSE){echo
__METHOD__."() error: Log directory is not found or is not directory.\n";exit(254);}}elseif($logDirectory===FALSE){self::$logDirectory=FALSE;}elseif(self::$logDirectory===NULL){self::$logDirectory=defined('APP_DIR')?APP_DIR.'/../log':getcwd().'/log';}if(self::$logDirectory){ini_set('error_log',self::$logDirectory.'/php_error.log');}if(function_exists('ini_set')){ini_set('display_errors',!self::$productionMode);ini_set('html_errors',FALSE);ini_set('log_errors',FALSE);}elseif(ini_get('display_errors')!=!self::$productionMode&&ini_get('display_errors')!==(self::$productionMode?'stderr':'stdout')){echo
__METHOD__."() error: Unable to set 'display_errors' because function ini_set() is disabled.\n";exit(254);}if($email){if(!is_string($email)){echo
__METHOD__."() error: Email address must be a string.\n";exit(254);}self::$email=$email;}if(!self::$enabled){register_shutdown_function(array(__CLASS__,'_shutdownHandler'));set_exception_handler(array(__CLASS__,'_exceptionHandler'));set_error_handler(array(__CLASS__,'_errorHandler'));self::$enabled=TRUE;}}public
static
function
isEnabled(){return
self::$enabled;}public
static
function
log($message,$priority=self::INFO){if(self::$logDirectory===FALSE){return;}elseif(!self::$logDirectory){throw
new
InvalidStateException('Logging directory is not specified in NDebugger::$logDirectory.');}$exceptionFilename=NULL;if($message
instanceof
Exception){$exception=$message;$message=($message
instanceof
FatalErrorException?'Fatal error: '.$exception->getMessage():get_class($exception).": ".$exception->getMessage())." in ".$exception->getFile().":".$exception->getLine();$hash=md5($exception.(method_exists($exception,'getPrevious')?$exception->getPrevious():(isset($exception->previous)?$exception->previous:'')));$exceptionFilename="exception-".@date('Y-m-d-H-i-s')."-$hash.html";foreach(new
DirectoryIterator(self::$logDirectory)as$entry){if(strpos($entry,$hash)){$exceptionFilename=$entry;$saved=TRUE;break;}}}elseif(!is_string($message)){$message=NDebugDumper::toText($message);}if($exceptionFilename){$exceptionFilename=self::$logDirectory.'/'.$exceptionFilename;if(empty($saved)&&$logHandle=@fopen($exceptionFilename,'w')){ob_start();ob_start(create_function('$buffer','extract($GLOBALS[0]['.array_push($GLOBALS[0],array('logHandle'=>$logHandle)).'-1], EXTR_REFS); fwrite($logHandle, $buffer); '),4096);self::$blueScreen->render($exception);ob_end_flush();ob_end_clean();fclose($logHandle);}}self::$logger->log(array(@date('[Y-m-d H-i-s]'),trim($message),self::$source?' @  '.self::$source:NULL,$exceptionFilename?' @@  '.basename($exceptionFilename):NULL),$priority);return$exceptionFilename?strtr($exceptionFilename,'\\/',DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR):NULL;}public
static
function
_shutdownHandler(){if(!self::$enabled){return;}static$types=array(E_ERROR=>1,E_CORE_ERROR=>1,E_COMPILE_ERROR=>1,E_PARSE=>1);$error=error_get_last();if(isset($types[$error['type']])){self::_exceptionHandler(new
FatalErrorException($error['message'],0,$error['type'],$error['file'],$error['line'],NULL));}if(!connection_aborted()&&self::$bar&&!self::$productionMode&&self::isHtmlMode()){self::$bar->render();}}public
static
function
_exceptionHandler(Exception$exception){if(!headers_sent()){$protocol=isset($_SERVER['SERVER_PROTOCOL'])?$_SERVER['SERVER_PROTOCOL']:'HTTP/1.1';header($protocol.' 500',TRUE,500);}try{if(self::$productionMode){try{self::log($exception,self::ERROR);}catch(Exception$e){echo'FATAL ERROR: unable to log error';}if(self::isHtmlMode()){?>
<!DOCTYPE html>
<meta charset="utf-8">
<meta name=robots content=noindex>
<meta name=generator content="Nette Framework">
<style>body{color:#333;background:white;width:500px;margin:100px auto}h1{font:bold 47px/1.5 sans-serif;margin:.6em 0}p{font:21px/1.5 Georgia,serif;margin:1.5em 0}small{font-size:70%;color:gray}</style>

<title>Server Error</title>

<h1>Server Error</h1>

<p>We're sorry! The server encountered an internal error and was unable to complete your request. Please try again later.</p>

<p><small>error 500</small></p>
<?php }else{echo"ERROR: the server encountered an internal error and was unable to complete your request.\n";}}else{if(!connection_aborted()&&self::isHtmlMode()){self::$blueScreen->render($exception);if(self::$bar){self::$bar->render();}}elseif(connection_aborted()||!self::fireLog($exception)){$file=self::log($exception,self::ERROR);if(!headers_sent()){header("X-Nette-Error-Log: $file");}echo"$exception\n".($file?"(stored in $file)\n":'');if(self::$browser){exec(self::$browser.' '.escapeshellarg($file));}}}foreach(self::$onFatalError
as$handler){call_user_func($handler,$exception);}}catch(Exception$e){if(self::$productionMode){echo
self::isHtmlMode()?'<meta name=robots content=noindex>FATAL ERROR':'FATAL ERROR';}else{echo"FATAL ERROR: thrown ",get_class($e),': ',$e->getMessage(),"\nwhile processing ",get_class($exception),': ',$exception->getMessage(),"\n";}}self::$enabled=FALSE;exit(254);}public
static
function
_errorHandler($severity,$message,$file,$line,$context){if(self::$scream){error_reporting(E_ALL|E_STRICT);}if(self::$lastError!==FALSE&&($severity&error_reporting())===$severity){self::$lastError=new
ErrorException($message,0,$severity,$file,$line);return
NULL;}if($severity===E_RECOVERABLE_ERROR||$severity===E_USER_ERROR){if(NDebugHelpers::findTrace(PHP_VERSION_ID<50205?debug_backtrace():debug_backtrace(FALSE),'*::__toString')){$previous=isset($context['e'])&&$context['e']instanceof
Exception?$context['e']:NULL;self::_exceptionHandler(new
FatalErrorException($message,0,$severity,$file,$line,$context,$previous));}throw
new
FatalErrorException($message,0,$severity,$file,$line,$context);}elseif(($severity&error_reporting())!==$severity){return
FALSE;}elseif(!self::$productionMode&&(is_bool(self::$strictMode)?self::$strictMode:((self::$strictMode&$severity)===$severity))){self::_exceptionHandler(new
FatalErrorException($message,0,$severity,$file,$line,$context));}static$types=array(E_WARNING=>'Warning',E_COMPILE_WARNING=>'Warning',E_USER_WARNING=>'Warning',E_NOTICE=>'Notice',E_USER_NOTICE=>'Notice',E_STRICT=>'Strict standards');if(PHP_VERSION_ID>=50300){$types+=array(E_DEPRECATED=>'Deprecated',E_USER_WARNING=>'Deprecated');}$message='PHP '.(isset($types[$severity])?$types[$severity]:'Unknown error').": $message";$count=&self::$errorPanel->data["$message|$file|$line"];if($count++){return
NULL;}elseif(self::$productionMode){self::log("$message in $file:$line",self::ERROR);return
NULL;}else{$ok=self::fireLog(new
ErrorException($message,0,$severity,$file,$line));return!self::isHtmlMode()||(!self::$bar&&!$ok)?FALSE:NULL;}return
FALSE;}public
static
function
toStringException(Exception$exception){if(self::$enabled){self::_exceptionHandler($exception);}else{trigger_error($exception->getMessage(),E_USER_ERROR);}}public
static
function
tryError(){trigger_error(__METHOD__.'() is deprecated; use own error handler instead.',E_USER_WARNING);if(!self::$enabled&&self::$lastError===FALSE){set_error_handler(array(__CLASS__,'_errorHandler'));}self::$lastError=NULL;}public
static
function
catchError(&$error){trigger_error(__METHOD__.'() is deprecated; use own error handler instead.',E_USER_WARNING);if(!self::$enabled&&self::$lastError!==FALSE){restore_error_handler();}$error=self::$lastError;self::$lastError=FALSE;return(bool)$error;}public
static
function
dump($var,$return=FALSE){if($return){ob_start();NDebugDumper::dump($var,array(NDebugDumper::DEPTH=>self::$maxDepth,NDebugDumper::TRUNCATE=>self::$maxLen));return
ob_get_clean();}elseif(!self::$productionMode){NDebugDumper::dump($var,array(NDebugDumper::DEPTH=>self::$maxDepth,NDebugDumper::TRUNCATE=>self::$maxLen,NDebugDumper::LOCATION=>self::$showLocation));}return$var;}public
static
function
timer($name=NULL){static$time=array();$now=microtime(TRUE);$delta=isset($time[$name])?$now-$time[$name]:0;$time[$name]=$now;return$delta;}public
static
function
barDump($var,$title=NULL){if(!self::$productionMode){$dump=array();foreach((is_array($var)?$var:array(''=>$var))as$key=>$val){$dump[$key]=NDebugDumper::toHtml($val);}self::$dumpPanel->data[]=array('title'=>$title,'dump'=>$dump);}return$var;}public
static
function
fireLog($message){if(!self::$productionMode){return
self::$fireLogger->log($message);}}private
static
function
isHtmlMode(){return
empty($_SERVER['HTTP_X_REQUESTED_WITH'])&&preg_match('#^Content-Type: text/html#im',implode("\n",headers_list()));}public
static
function
addPanel(IBarPanel$panel,$id=NULL){return
self::$bar->addPanel($panel,$id);}}class
FatalErrorException
extends
Exception{private$severity;public
function
__construct($message,$code,$severity,$file,$line,$context){parent::__construct($message,$code);$this->severity=$severity;$this->file=$file;$this->line=$line;$this->context=$context;}public
function
getSeverity(){return$this->severity;}}$GLOBALS[0]=array();NDebugger::_init();
