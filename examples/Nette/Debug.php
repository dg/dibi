<?php
/**
 * Nette Framework
 *
 * Copyright (c) 2004, 2010 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license" that is bundled
 * with this package in the file license.txt, and/or GPL license.
 *
 * For more information please see http://nettephp.com
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @category   Nette
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
static$maxDepth=3;public
static$maxLen=150;public
static$showLocation=FALSE;const
DEVELOPMENT=FALSE;const
PRODUCTION=TRUE;const
DETECT=NULL;public
static$strictMode=FALSE;public
static$onFatalError=array();public
static$mailer=array(__CLASS__,'defaultMailer');public
static$emailSnooze=172800;private
static$enabled=FALSE;private
static$logFile;private
static$logHandle;private
static$sendEmails;private
static$emailHeaders=array('To'=>'','From'=>'noreply@%host%','X-Mailer'=>'Nette Framework','Subject'=>'PHP: An error occurred on the server %host%','Body'=>'[%date%] %message%');private
static$enabledBar=TRUE;private
static$panels=array();private
static$dumps;private
static$errors;public
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
_init(){self::$time=microtime(TRUE);self::$consoleMode=PHP_SAPI==='cli';self::$productionMode=self::DETECT;self::$firebugDetected=isset($_SERVER['HTTP_USER_AGENT'])&&strpos($_SERVER['HTTP_USER_AGENT'],'FirePHP/');self::$ajaxDetected=isset($_SERVER['HTTP_X_REQUESTED_WITH'])&&$_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';self::addPanel(new
DebugPanel('time',array(__CLASS__,'getDefaultPanel')));self::addPanel(new
DebugPanel('memory',array(__CLASS__,'getDefaultPanel')));self::addPanel(new
DebugPanel('errors',array(__CLASS__,'getDefaultPanel')));self::addPanel(new
DebugPanel('dumps',array(__CLASS__,'getDefaultPanel')));register_shutdown_function(array(__CLASS__,'_shutdownHandler'));}public
static
function
_shutdownHandler(){static$types=array(E_ERROR=>1,E_CORE_ERROR=>1,E_COMPILE_ERROR=>1,E_PARSE=>1);$error=error_get_last();if(self::$enabled&&isset($types[$error['type']])){if(!headers_sent()){header('HTTP/1.1 500 Internal Server Error');}if(ini_get('html_errors')){$error['message']=html_entity_decode(strip_tags($error['message']),ENT_QUOTES,'UTF-8');}self::processException(new
FatalErrorException($error['message'],0,$error['type'],$error['file'],$error['line'],NULL),TRUE);}if(self::$enabledBar&&!self::$productionMode&&!self::$ajaxDetected){foreach(headers_list()as$header){if(strncasecmp($header,'Content-Type:',13)===0){if(substr($header,14,9)==='text/html'){break;}return;}}$panels=array();foreach(self::$panels
as$panel){$panels[]=array('id'=>preg_replace('#[^a-z0-9]+#i','-',$panel->getId()),'tab'=>$tab=(string)$panel->getTab(),'panel'=>$tab?(string)$panel->getPanel():NULL);}?>

<style type="text/css" id="nette-debug-style">
/* <![CDATA[ */

	/* common styles */
	#nette-debug {
		display: none;
	}

	body#nette-debug {
		margin: 5px 5px 0;
		display: block;
	}

	#nette-debug * {
		font-family: inherit;
		color: inherit;
		background: transparent;
		margin: 0;
		padding: 0;
		border: none;
		text-align: inherit;
	}

	#nette-debug .nette-fixed-coords {
		position: fixed;
		_position: absolute;
		right: 0;
		bottom: 0;
	}

	#nette-debug .nette-panel h2, #nette-debug .nette-panel h3, #nette-debug .nette-panel p {
		margin: .4em 0;
	}

	#nette-debug .nette-panel a {
		color: #125EAE;
	}

	#nette-debug .nette-panel a:hover, #nette-debug .nette-panel a:active, #nette-debug .nette-panel a:focus {
		background-color: #125EAE;
		text-decoration: none;
		color: white;
	}

	#nette-debug .nette-panel table {
		border-collapse: collapse;
		background: #fcfae5;
	}

	#nette-debug .nette-panel table .nette-alt td {
		background: #f9f6e0;
	}

	#nette-debug .nette-panel table td, #nette-debug .nette-panel table th {
		border: 1px solid #DCD7C8;
		padding: 2px 5px;
		vertical-align: top;
		text-align: left;
	}

	#nette-debug .nette-panel table th {
		background: #f0eee6;
		color: #655E5E;
		font-size: 90%;
		font-weight: bold;
	}

	#nette-debug .nette-panel pre, #nette-debug .nette-panel code {
		font: 9pt/1.5 Consolas, monospace;
	}

	#nette-hidden {
		display: none;
	}



	/* bar */
	#nette-debug-bar {
		cursor: move;
		font: normal normal 12px/21px Tahoma, sans-serif;
		position: relative;
		top: -5px;
		left: -5px;

		min-width: 50px;
		white-space: nowrap;

		z-index: 23178;

		opacity: .9;
		=filter: alpha(opacity=90);
	}

	#nette-debug-bar:hover {
		opacity: 1;
		=filter: none;
	}

	#nette-debug-bar ul {
		list-style: none none;
	}

	#nette-debug-bar li {
		display: inline-block;
	}

	#nette-debug-bar img {
		vertical-align: middle;
		position: relative;
		top: -1px;
		margin-right: 3px;
	}

	#nette-debug-bar li a {
		color: #3d3d3d;
		text-decoration: none;
		display: block;
		padding: 0 4px;
	}

	#nette-debug-bar li a:hover {
		color: black;
		background: #c3c1b8;
	}

	#nette-debug-bar li .nette-warning {
		color: #d32b2b;
		font-weight: bold;
	}

	#nette-debug-bar li div {
		padding: 0 4px;
	}



	/* panels */
	#nette-debug .nette-panel {
		font: normal normal 12px/1.5 sans-serif;
		background: white;
		color: #333;
	}

	#nette-debug h1 {
		font: normal normal 23px/1.4 Tahoma, sans-serif;
		color: #575753;
		background: #edeae0;
		margin: -5px -5px 5px;
		padding: 0 25px 5px 5px;
	}

	#nette-debug .nette-mode-peek .nette-inner, #nette-debug .nette-mode-float .nette-inner {
		max-width: 700px;
		max-height: 500px;
		overflow: auto;
	}

	#nette-debug .nette-panel .nette-icons {
		display: none;
	}

	#nette-debug .nette-mode-peek {
		display: none;
		position: relative;
		z-index: 23179;
		padding: 5px;
		min-width: 150px;
		min-height: 50px;
		border: 5px solid #edeae0;
		border-radius: 5px;
		-moz-border-radius: 5px;
	}

	#nette-debug .nette-mode-peek h1 {
		cursor: move;
	}

	#nette-debug .nette-mode-float {
		position: relative;
		z-index: 23170;
		padding: 5px;
		min-width: 150px;
		min-height: 50px;
		border: 5px solid #edeae0;
		border-radius: 5px;
		-moz-border-radius: 5px;
		opacity: .9;
		=filter: alpha(opacity=90);
	}

	#nette-debug .nette-focused {
		z-index: 23179;
		opacity: 1;
		=filter: none;
	}

	#nette-debug .nette-mode-float h1 {
		cursor: move;
	}

	#nette-debug .nette-mode-float .nette-icons {
		display: block;
		position: absolute;
		top: 0;
		right: 0;
	}

	#nette-debug a.nette-icon-window {
		display: inline-block;
		background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAcAAAAHCAYAAADEUlfTAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAACtJREFUeNpiZGBgKGbAAZgY8AAWJHYvEruYeJ3///+HCzIyMkJosh0EEGAA4BwE90s3fqAAAAAASUVORK5CYII=') no-repeat; /* window.png */
		width: 7px;
		height: 7px;
	}

	#nette-debug a.nette-icon-close {
		display: inline-block;
		background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAcAAAAHAQMAAAD+nMWQAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAAZQTFRF////AAAAVcLTfgAAAAJ0Uk5TAHO/n+2+AAAAFUlEQVQIHWM8xviOsYbRAojfMR4DACBuBKBO17DlAAAAAElFTkSuQmCC') no-repeat; /* cross.png */
		width: 7px;
		height: 7px;
	}


/* ]]> */
</style>



<script type="text/javascript" id="nette-debug-script">
/* <![CDATA[ */
<?php ?>/**
 * NetteJs
 *
 * @copyright  Copyright (c) 2010 David Grudl
 * @license    http://nettephp.com/license  Nette license
 */

var Nette = Nette || {};

(function(){

// simple class builder
Nette.Class = function(def) {
	var cl = def.constructor || function(){}, nm;
	delete def.constructor;

	if (def.Extends) {
		var foo = function() { this.constructor = cl };
		foo.prototype = def.Extends.prototype;
		cl.prototype = new foo;
		delete def.Extends;
	}

	if (def.Static) {
		for (nm in def.Static) cl[nm] = def.Static[nm];
		delete def.Static;
	}

	for (nm in def) cl.prototype[nm] = def[nm];
	return cl;
};


// supported cross-browser selectors: #id  |  div  |  div.class  |  .class
Nette.Q = Nette.Class({

	Static: {
		factory: function(selector) {
			return new Nette.Q(selector)
		},

		implement: function(methods) {
			var nm, fn = Nette.Q.implement, prot = Nette.Q.prototype;
			for (nm in methods) {
				fn[nm] = methods[nm];
				prot[nm] = (function(nm){
					return function() { return this.each(fn[nm], arguments) }
				}(nm));
			}
		}
	},

	constructor: function(selector) {
		if (typeof selector === "string") {
			selector = this._find(document, selector);

		} else if (!selector || selector.nodeType || selector.length === void 0 || selector === window) {
			selector = [selector];
		}

		for (var i = 0, len = selector.length; i < len; i++) {
			if (selector[i]) this[this.length++] = selector[i];
		}
	},

	length: 0,

	find: function(selector) {
		return new Nette.Q(this._find(this[0], selector));
	},

	_find: function(context, selector) {
		if (!context || !selector) {
			return [];

		} else if (document.querySelectorAll) {
			return context.querySelectorAll(selector);

		} else if (selector.charAt(0) === '#') { // #id
			return [document.getElementById(selector.substring(1))];

		} else { // div  |  div.class  |  .class
			selector = selector.split('.');
			var elms = context.getElementsByTagName(selector[0] || '*');

			if (selector[1]) {
				var list = [], pattern = new RegExp('(^|\\s)' + selector[1] + '(\\s|$)');
				for (var i = 0, len = elms.length; i < len; i++) {
					if (pattern.test(elms[i].className)) list.push(elms[i]);
				}
				return list;
			} else {
				return elms;
			}
		}
	},

	dom: function() {
		return this[0];
	},

	each: function(callback, args) {
		for (var i = 0, res; i < this.length; i++) {
			if ((res = callback.apply(this[i], args || [])) !== void 0) { return res; }
		}
		return this;
	}
});


var $ = Nette.Q.factory, fn = Nette.Q.implement;

fn({
	// cross-browser event attach
	bind: function(event, handler) {
		if (document.addEventListener && (event === 'mouseenter' || event === 'mouseleave')) { // simulate mouseenter & mouseleave using mouseover & mouseout
			var old = handler;
			event = event === 'mouseenter' ? 'mouseover' : 'mouseout';
			handler = function(e) {
				for (var target = e.relatedTarget; target; target = target.parentNode) {
					if (target === this) return; // target must not be inside this
				}
				old.call(this, e);
			};
		}

		var data = fn.data.call(this),
			events = data.events = data.events || {}; // use own handler queue

		if (!events[event]) {
			var el = this, // fixes 'this' in iE
				handlers = events[event] = [],
				generic = fn.bind.genericHandler = function(e) { // dont worry, 'e' is passed in IE
					if (!e.preventDefault) e.preventDefault = function() { e.returnValue = false }; // emulate preventDefault()
					if (!e.stopPropagation) e.stopPropagation = function() { e.cancelBubble = true }; // emulate stopPropagation()
					e.stopImmediatePropagation = function() { this.stopPropagation(); i = handlers.length };
					for (var i = 0; i < handlers.length; i++) {
						handlers[i].call(el, e);
					}
				};

			if (document.addEventListener) { // non-IE
				this.addEventListener(event, generic, false);
			} else if (document.attachEvent) { // IE < 9
				this.attachEvent('on' + event, generic);
			}
		}

		events[event].push(handler);
	},

	// adds class to element
	addClass: function(className) {
		this.className = this.className.replace(/^|\s+|$/g, ' ').replace(' '+className+' ', ' ') + ' ' + className;
	},

	// removes class from element
	removeClass: function(className) {
		this.className = this.className.replace(/^|\s+|$/g, ' ').replace(' '+className+' ', ' ');
	},

	// tests whether element has given class
	hasClass: function(className) {
		return this.className.replace(/^|\s+|$/g, ' ').indexOf(' '+className+' ') > -1;
	},

	show: function() {
		this.style.display = 'block';
	},

	hide: function() {
		this.style.display = 'none';
	},

	toggle: function(arrow) {
		var h = fn.css.call(this, 'display') === 'none';
		this.style.display = h ? 'block' : 'none';
		if (arrow) $(arrow).dom().innerHTML = String.fromCharCode(h ? 0x25bc : 0x25ba);
	},

	css: function(property) {
		return this.currentStyle ? this.currentStyle[property]
			: (window.getComputedStyle ? document.defaultView.getComputedStyle(this, null).getPropertyValue(property) : void 0);
	},

	data: function() {
		return this.nette = this.nette || {};
	},

	_trav: function(el, selector, fce) {
		selector = selector.split('.');
		while (el && !(el.nodeType === 1 && (!selector[0] || el.tagName.toLowerCase() === selector[0]) && (!selector[1] || fn.hasClass.call(el, selector[1])))) el = el[fce];
		return $(el);
	},

	closest: function(selector) {
		return fn._trav(this, selector, 'parentNode');
	},

	prev: function(selector) {
		return fn._trav(this.prevSibling, selector, 'prevSibling');
	},

	next: function(selector) {
		return fn._trav(this.nextSibling, selector, 'nextSibling');
	},

	// returns total offset for element
	offset: function(coords) {
		var el = this, ofs = coords ? {left: -coords.left || 0, top: -coords.top || 0} : fn.position.call(el);
		while (el = el.offsetParent) { ofs.left += el.offsetLeft; ofs.top += el.offsetTop; }

		if (coords) {
			fn.position.call(this, {left: -ofs.left, top: -ofs.top});
		} else {
			return ofs;
		}
	},

	// returns current position or move to new position
	position: function(coords) {
		if (coords) {
			this.nette && this.nette.onmove && this.nette.onmove.call(this, coords);
			this.style.left = (coords.left || 0) + 'px';
			this.style.top = (coords.top || 0) + 'px';
		} else {
			return {left: this.offsetLeft, top: this.offsetTop, width: this.offsetWidth, height: this.offsetHeight};
		}
	},

	// makes element draggable
	draggable: function(options) {
		var $el = $(this), dE = document.documentElement, started, options = options || {};

		$(options.handle || this).bind('mousedown', function(e) {
			e.preventDefault();
			e.stopPropagation();

			if (fn.draggable.binded) { // missed mouseup out of window?
				return dE.onmouseup(e);
			}

			var deltaX = $el[0].offsetLeft - e.clientX, deltaY = $el[0].offsetTop - e.clientY;
			fn.draggable.binded = true;
			started = false;

			dE.onmousemove = function(e) {
				e = e || event;
				if (!started) {
					options.draggedClass && $el.addClass(options.draggedClass);
					options.start && options.start(e, $el);
					started = true;
				}
				$el.position({left: e.clientX + deltaX, top: e.clientY + deltaY});
				return false;
			};

			dE.onmouseup = function(e) {
				if (started) {
					options.draggedClass && $el.removeClass(options.draggedClass);
					options.stop && options.stop(e || event, $el);
				}
				fn.draggable.binded = dE.onmousemove = dE.onmouseup = null;
				return false;
			};

		}).bind('click', function(e) {
			if (started) {
				e.stopImmediatePropagation();
				preventClick = false;
			}
		});
	}
});

})();

(function(){
Nette.Debug = {};

var $ = Nette.Q.factory;

var Panel = Nette.Debug.Panel = Nette.Class({
	Extends: Nette.Q,

	Static: {
		PEEK: 'nette-mode-peek',
		FLOAT: 'nette-mode-float',
		WINDOW: 'nette-mode-window',
		FOCUSED: 'nette-focused',

		factory: function(selector) {
			return new Panel(selector)
		}
	},

	constructor: function(selector) {
		Nette.Q.call(this, '#nette-debug-panel-' + selector);
	},

	reposition: function() {
		this.position(this.position());
	},

	focus: function() {
		if (this.hasClass(Panel.WINDOW)) {
			this.data().win.focus();
		} else {
			clearTimeout(this.data().blurTimeout);
			this.addClass(Panel.FOCUSED).show();
		}
	},

	blur: function() {
		this.removeClass(Panel.FOCUSED);
		if (this.hasClass(Panel.PEEK)) {
			var panel = this;
			this.data().blurTimeout = setTimeout(function() {
				panel.hide();
			}, 50);
		}
	},

	toFloat: function() {
		this.removeClass(Panel.WINDOW).removeClass(Panel.PEEK).addClass(Panel.FLOAT).show().reposition();
		if (this.position().width) { // is visible?
			document.cookie = this.dom().id + '=' + this.position().left + ':' + this.position().top + '; path=/';
		}
		return this;
	},

	toPeek: function() {
		this.removeClass(Panel.WINDOW).removeClass(Panel.FLOAT).addClass(Panel.PEEK).hide();
		document.cookie = this.dom().id + '=; path=/'; // delete position
	},

	toWindow: function() {
		var panel = this, win, offset = this.offset(), id = this.dom().id;

		offset.left += typeof window.screenLeft === 'number' ? window.screenLeft : (window.screenX + 10);
		offset.top += typeof window.screenTop === 'number' ? window.screenTop : (window.screenY + 50);

		win = window.open('', id.replace(/-/g, '_'), 'left='+offset.left+',top='+offset.top+',width='+offset.width+',height='+(offset.height+15)+',resizable=yes,scrollbars=yes');
		if (!win) return;

		var d = win.document;
		d.write('<!DOCTYPE html><meta http-equiv="Content-Type" content="text\/html; charset=utf-8"><style>' + $('#nette-debug-style').dom().innerHTML + '<\/style><script>' + $('#nette-debug-script').dom().innerHTML + '<\/script><body id="nette-debug"><div class="nette-panel nette-mode-window" id="' + id + '">' + this.dom().innerHTML + '<\/div>');
		d.close();
		d.title = panel.find('h1').dom().innerHTML;

		$([win]).bind('unload', function() {
			panel.toPeek();
			win.close(); // forces closing, can be invoked by F5
		});

		$(d).bind('keyup', function(e) {
			if (e.keyCode === 27) win.close();
		});

		win.resizeBy(d.documentElement.scrollWidth - d.documentElement.clientWidth, d.documentElement.scrollHeight - d.documentElement.clientHeight);

		document.cookie = id + '=window; path=/'; // save position
		this.hide().removeClass(Panel.FLOAT).removeClass(Panel.PEEK).addClass(Panel.WINDOW).data().win = win;
	},

	toggle: function(mode) {
		if (mode === Panel.WINDOW) {
			this.toFloat().toWindow();
		} else if (this.hasClass(Panel.FLOAT)) {
			this.toPeek();
		} else {
			this.toFloat().position({left: this.position().left - Math.round(Math.random() * 100) - 20, top: this.position().top - Math.round(Math.random() * 100) - 20});
		}
	},

	init: function() {
		var panel = this, pos;

		panel.data().onmove = function(coords) { // forces constrained inside window
			var d = document, width = d.documentElement.clientWidth || d.body.clientWidth, height = d.documentElement.clientHeight || d.body.clientHeight;
			coords.left = Math.max(Math.min(coords.left, .8 * this.offsetWidth), .2 * this.offsetWidth - width);
			coords.top = Math.max(Math.min(coords.top, .8 * this.offsetHeight), this.offsetHeight - height);
		};

		$(window).bind('resize', function() {
			panel.reposition();
		});

		panel.draggable({
			handle: panel.find('h1'),
			stop: function() {
				panel.toFloat();
			}

		}).bind('mouseenter', function(e) {
			panel.focus();

		}).bind('mouseleave', function(e) {
			panel.blur();
		});

		panel.find('.nette-icon-window').bind('click', function(e) {
			panel.toWindow();
			e.preventDefault();
		});

		panel.find('.nette-icon-close').bind('click', function(e) {
			panel.toPeek();
			e.preventDefault();
		});

		// restore saved position
		if (pos = document.cookie.match(new RegExp(panel.dom().id + '=(window|(-?[0-9]+):(-?[0-9]+))'))) {
			if (pos[2]) {
				panel.toFloat().position({left: pos[2], top: pos[3]});
			} else {
				panel.toWindow();
			}
		} else {
			panel.addClass(Panel.PEEK);
		}
	}

});



Nette.Debug.Bar = Nette.Class({
	Extends: Nette.Q,

	constructor: function() {
		Nette.Q.call(this, '#nette-debug-bar');
	},

	init: function() {
		var bar = this, pos;

		bar.data().onmove = function(coords) { // forces constrained inside window
			var d = document, width = d.documentElement.clientWidth || d.body.clientWidth, height = d.documentElement.clientHeight || d.body.clientHeight;
			coords.left = Math.max(Math.min(coords.left, 0), this.offsetWidth - width);
			coords.top = Math.max(Math.min(coords.top, 0), this.offsetHeight - height);
		};

		$(window).bind('resize', function() {
			bar.position(bar.position());
		});

		bar.draggable({
			draggedClass: 'nette-dragged',
			stop: function() {
				document.cookie = bar.dom().id + '=' + bar.position().left + ':' + bar.position().top + '; path=/';
			}
		});

		bar.find('a').bind('click', function(e) {
			if (!this.rel) return;
			Panel.factory(this.rel).toggle(e.shiftKey ? Panel.WINDOW : Panel.FLOAT);
			e.preventDefault();

		}).bind('mouseenter', function(e) {
			if (!this.rel) return;
			if (bar.hasClass('nette-dragged')) return;
			var panel = Panel.factory(this.rel);
			panel.focus();
			if (panel.hasClass(Panel.PEEK)) {
				var offset = $(this).offset();
				panel.offset({left: offset.left - panel.position().width + offset.width + 4, top: offset.top - panel.position().height - 4});
			}

		}).bind('mouseleave', function(e) {
			if (!this.rel) return;
			Panel.factory(this.rel).blur();
		});

		bar.find('.nette-icon-close').bind('click', function(e) {
			bar.hide();
			e.preventDefault();
		});

		// restore saved position
		if (pos = document.cookie.match(new RegExp(bar.dom().id + '=(-?[0-9]+):(-?[0-9]+)'))) {
			bar.position({left: pos[1], top: pos[2]}); // TODO
		}

		bar.find('a').each(function() {
			if (!this.rel) return;
			Panel.factory(this.rel).init();
		});
	}

});


})();

/* ]]> */
</script>



<div id="nette-debug">

<div class="nette-fixed-coords">
	<div id="nette-debug-bar">
		<ul>
			<?php foreach($panels
as$panel):if(!$panel['tab'])continue;?>
			<li><?php if($panel['panel']):?><a href="#" rel="<?php echo$panel['id']?>"><?php echo
trim($panel['tab'])?></a><?php else:echo'<div>',trim($panel['tab']),'</div>';endif?></li>
			<?php endforeach?>
			<li><a href="#" class="nette-icon-close" title="close debug bar"></a></li>
		</ul>

		<div style="position:absolute; z-index:-1; background: url('data:image/png;base64,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') no-repeat; left: -56px; top: -7px; width: 56px; height: 37px"></div>
		<div style="position:absolute; z-index:-1; background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAlCAYAAACDKIOpAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAF5JREFUeNpUjjEOgDAMA932DXynA/8X6sAnGBErjYlLW8Fy8jlRFAAI0RH/SC9yziuu86AUDjroiR+ldA6a0hDNHLUqSWmj6yvo2i6rS1vZi2xRd0/UNL4ypSDgEWAAvWU1L9Te5UYAAAAASUVORK5CYII=') repeat-x; left: 0; right: 7px; top: -7px; height: 37px"></div>
		<div style="position:absolute; z-index:-1; background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA0AAAAlCAYAAACZFGMnAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAb5JREFUeNq8Vb1OwzAQtlNTIoRYYIhEFfUVGLp2YykDA48FG8/AUvEKqFIGQDwEA20CKv9UUFWkPuzUTi/pOW0XTjolvfrL951/PnPGGGelaLfbwIiIoih7aoBX+g9cH7AgHcJkzaRnkuNU4BygC3XEAI73ggqB5EFpsFOylYVB0vEBMMqgprR2wjDcD4JAy/wZjUayiiWLz7cBPD/dv9xe97pHnc5Jo9HYU+Utlb7pV6AJ4jno41VnH+5uepetVutAlbcNcFPlBuo9m0kPYC692QwPfd8P0AAPLb6d/uwLTAN1CiF2OOd1MxhQ8xz3JixgxlhYPypn6ySlzAEI55mpJ8MwsWVMuA6KybKQG5uXnohpcf04AZj3BCCrmKp6Wh2Qz966IdZlMSD2P0yeA0Qdd8Ant2r2KJ9YykQd+ZmpWGCapl9qCX6RT9A94R8P/fhqPB4PCSZY6EkxvA/ix+j07PwiSZLYMLlcKXPOYy1pMpkM4zhWmORb1acGNEW2lkvWO3cXFaQjCzK1vJQwSnIw7ild0WELtjwldoGsugwEMpBVbo2C68hSPwvy8OUmCKuCZVepoOhdd66NPwEGACivHvMuwfIKAAAAAElFTkSuQmCC') no-repeat; right: -6px; top: -7px; width: 13px; height: 37px"></div>
	</div>
</div>

<?php foreach($panels
as$id=>$panel):?>
<div class="nette-fixed-coords">
	<div class="nette-panel" id="nette-debug-panel-<?php echo$panel['id']?>">
		<div id="nette-debug-<?php echo$panel['id']?>"><?php echo$panel['panel']?></div>
		<div class="nette-icons">
			<a href="#" class="nette-icon-window" title="open in window"></a>
			<a href="#" class="nette-icon-close" title="close"></a>
		</div>
	</div>
</div>
<?php endforeach?>
</div>



<script type="text/javascript" id="nette-debug-script">
/* <![CDATA[ */

(new Nette.Debug.Bar).init();
document.body.appendChild( Nette.Q.factory('#nette-debug').show().dom() );

/* ]]> */
</script>
<?php }}public
static
function
dump($var,$return=FALSE){if(!$return&&self::$productionMode){return$var;}$output="<pre class=\"dump\">".self::_dump($var,0)."</pre>\n";if(self::$showLocation){$trace=debug_backtrace();$i=isset($trace[1]['class'])&&$trace[1]['class']===__CLASS__?1:0;if(isset($trace[$i]['file'],$trace[$i]['line'])){$output=substr_replace($output,' <small>'.htmlspecialchars("in file {$trace[$i]['file']} on line {$trace[$i]['line']}",ENT_NOQUOTES).'</small>',-8,0);}}if(self::$consoleMode){$output=htmlspecialchars_decode(strip_tags($output),ENT_NOQUOTES);}if($return){return$output;}else{echo$output;return$var;}}public
static
function
barDump($var,$title=NULL){if(!self::$productionMode){$dump=array();foreach((is_array($var)?$var:array(''=>$var))as$key=>$val){$dump[$key]=self::dump($val,TRUE);}self::$dumps[]=array('title'=>$title,'dump'=>$dump);}return$var;}private
static
function
_dump(&$var,$level){static$tableUtf,$tableBin,$re='#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]#u';if($tableUtf===NULL){foreach(range("\x00","\xFF")as$ch){if(ord($ch)<32&&strpos("\r\n\t",$ch)===FALSE)$tableUtf[$ch]=$tableBin[$ch]='\\x'.str_pad(dechex(ord($ch)),2,'0',STR_PAD_LEFT);elseif(ord($ch)<127)$tableUtf[$ch]=$tableBin[$ch]=$ch;else{$tableUtf[$ch]=$ch;$tableBin[$ch]='\\x'.dechex(ord($ch));}}$tableUtf['\\x']=$tableBin['\\x']='\\\\x';}if(is_bool($var)){return"<span>bool</span>(".($var?'TRUE':'FALSE').")\n";}elseif($var===NULL){return"<span>NULL</span>\n";}elseif(is_int($var)){return"<span>int</span>($var)\n";}elseif(is_float($var)){return"<span>float</span>($var)\n";}elseif(is_string($var)){if(self::$maxLen&&strlen($var)>self::$maxLen){$s=htmlSpecialChars(substr($var,0,self::$maxLen),ENT_NOQUOTES).' ... ';}else{$s=htmlSpecialChars($var,ENT_NOQUOTES);}$s=strtr($s,preg_match($re,$s)||preg_last_error()?$tableBin:$tableUtf);return"<span>string</span>(".strlen($var).") \"$s\"\n";}elseif(is_array($var)){$s="<span>array</span>(".count($var).") ";$space=str_repeat($space1='   ',$level);static$marker;if($marker===NULL)$marker=uniqid("\x00",TRUE);if(empty($var)){}elseif(isset($var[$marker])){$s.="{\n$space$space1*RECURSION*\n$space}";}elseif($level<self::$maxDepth||!self::$maxDepth){$s.="<code>{\n";$var[$marker]=0;foreach($var
as$k=>&$v){if($k===$marker)continue;$k=is_int($k)?$k:'"'.strtr($k,preg_match($re,$k)||preg_last_error()?$tableBin:$tableUtf).'"';$s.="$space$space1$k => ".self::_dump($v,$level+1);}unset($var[$marker]);$s.="$space}</code>";}else{$s.="{\n$space$space1...\n$space}";}return$s."\n";}elseif(is_object($var)){$arr=(array)$var;$s="<span>object</span>(".get_class($var).") (".count($arr).") ";$space=str_repeat($space1='   ',$level);static$list=array();if(empty($arr)){$s.="{}";}elseif(in_array($var,$list,TRUE)){$s.="{\n$space$space1*RECURSION*\n$space}";}elseif($level<self::$maxDepth||!self::$maxDepth){$s.="<code>{\n";$list[]=$var;foreach($arr
as$k=>&$v){$m='';if($k[0]==="\x00"){$m=$k[1]==='*'?' <span>protected</span>':' <span>private</span>';$k=substr($k,strrpos($k,"\x00")+1);}$k=strtr($k,preg_match($re,$k)||preg_last_error()?$tableBin:$tableUtf);$s.="$space$space1\"$k\"$m => ".self::_dump($v,$level+1);}array_pop($list);$s.="$space}</code>";}else{$s.="{\n$space$space1...\n$space}";}return$s."\n";}elseif(is_resource($var)){return"<span>resource of type</span>(".get_resource_type($var).")\n";}else{return"<span>unknown type</span>\n";}}public
static
function
timer($name=NULL){static$time=array();$now=microtime(TRUE);$delta=isset($time[$name])?$now-$time[$name]:0;$time[$name]=$now;return$delta;}public
static
function
enable($mode=NULL,$logFile=NULL,$email=NULL){error_reporting(E_ALL|E_STRICT);if(is_bool($mode)){self::$productionMode=$mode;}elseif(is_string($mode)){$mode=preg_split('#[,\s]+#',$mode);}if(is_array($mode)){self::$productionMode=!isset($_SERVER['REMOTE_ADDR'])||!in_array($_SERVER['REMOTE_ADDR'],$mode,TRUE);}if(self::$productionMode===self::DETECT){if(class_exists('Environment')){self::$productionMode=Environment::isProduction();}elseif(isset($_SERVER['SERVER_ADDR'])||isset($_SERVER['LOCAL_ADDR'])){$addr=isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:$_SERVER['LOCAL_ADDR'];$oct=explode('.',$addr);self::$productionMode=$addr!=='::1'&&(count($oct)!==4||($oct[0]!=='10'&&$oct[0]!=='127'&&($oct[0]!=='172'||$oct[1]<16||$oct[1]>31)&&($oct[0]!=='169'||$oct[1]!=='254')&&($oct[0]!=='192'||$oct[1]!=='168')));}else{self::$productionMode=!self::$consoleMode;}}if(self::$productionMode&&$logFile!==FALSE){self::$logFile='log/php_error.log';if(class_exists('Environment')){if(is_string($logFile)){self::$logFile=Environment::expand($logFile);}else
try{self::$logFile=Environment::expand('%logDir%/php_error.log');}catch(InvalidStateException$e){}}elseif(is_string($logFile)){self::$logFile=$logFile;}ini_set('error_log',self::$logFile);}if(function_exists('ini_set')){ini_set('display_errors',!self::$productionMode);ini_set('html_errors',!self::$logFile&&!self::$consoleMode);ini_set('log_errors',FALSE);}elseif(ini_get('display_errors')!=!self::$productionMode&&ini_get('display_errors')!==(self::$productionMode?'stderr':'stdout')){throw
new
NotSupportedException('Function ini_set() must be enabled.');}self::$sendEmails=self::$logFile&&$email;if(self::$sendEmails){if(is_string($email)){self::$emailHeaders['To']=$email;}elseif(is_array($email)){self::$emailHeaders=$email+self::$emailHeaders;}}if(!defined('E_DEPRECATED')){define('E_DEPRECATED',8192);}if(!defined('E_USER_DEPRECATED')){define('E_USER_DEPRECATED',16384);}set_exception_handler(array(__CLASS__,'_exceptionHandler'));set_error_handler(array(__CLASS__,'_errorHandler'));self::$enabled=TRUE;}public
static
function
isEnabled(){return
self::$enabled;}public
static
function
log($message){error_log(@date('[Y-m-d H-i-s] ').trim($message).PHP_EOL,3,self::$logFile);}public
static
function
_exceptionHandler(Exception$exception){if(!headers_sent()){header('HTTP/1.1 500 Internal Server Error');}self::processException($exception,TRUE);exit;}public
static
function
_errorHandler($severity,$message,$file,$line,$context){if($severity===E_RECOVERABLE_ERROR||$severity===E_USER_ERROR){throw
new
FatalErrorException($message,0,$severity,$file,$line,$context);}elseif(($severity&error_reporting())!==$severity){return
NULL;}elseif(self::$strictMode){self::_exceptionHandler(new
FatalErrorException($message,0,$severity,$file,$line,$context),TRUE);}static$types=array(E_WARNING=>'Warning',E_USER_WARNING=>'Warning',E_NOTICE=>'Notice',E_USER_NOTICE=>'Notice',E_STRICT=>'Strict standards',E_DEPRECATED=>'Deprecated',E_USER_DEPRECATED=>'Deprecated');$message='PHP '.(isset($types[$severity])?$types[$severity]:'Unknown error').": $message in $file:$line";if(self::$logFile){if(self::$sendEmails){self::sendEmail($message);}self::log($message);return
NULL;}elseif(!self::$productionMode){self::$errors[]=$message;if(self::$firebugDetected&&!headers_sent()){self::fireLog(strip_tags($message),self::ERROR);}return
NULL;}return
FALSE;}public
static
function
processException(Exception$exception,$outputAllowed=FALSE){if(!self::$enabled){return;}elseif(self::$logFile){try{$hash=md5($exception.(method_exists($exception,'getPrevious')?$exception->getPrevious():(isset($exception->previous)?$exception->previous:'')));self::log("PHP Fatal error: Uncaught ".str_replace("Stack trace:\n".$exception->getTraceAsString(),'',$exception));foreach(new
DirectoryIterator(dirname(self::$logFile))as$entry){if(strpos($entry,$hash)){$skip=TRUE;break;}}$file='compress.zlib://'.dirname(self::$logFile)."/exception ".@date('Y-m-d H-i-s')." $hash.html.gz";if(empty($skip)&&self::$logHandle=@fopen($file,'w')){ob_start();ob_start(array(__CLASS__,'_writeFile'),1);self::_paintBlueScreen($exception);ob_end_flush();ob_end_clean();fclose(self::$logHandle);}if(self::$sendEmails){self::sendEmail((string)$exception);}}catch(Exception$e){if(!headers_sent()){header('HTTP/1.1 500 Internal Server Error');}echo'Nette\Debug fatal error: ',get_class($e),': ',($e->getCode()?'#'.$e->getCode().' ':'').$e->getMessage(),"\n";exit;}}elseif(self::$productionMode){}elseif(self::$consoleMode){if($outputAllowed){echo"$exception\n";}}elseif(self::$firebugDetected&&self::$ajaxDetected&&!headers_sent()){self::fireLog($exception,self::EXCEPTION);}elseif($outputAllowed){if(!headers_sent()){@ob_end_clean();while(ob_get_level()&&@ob_end_clean());if(in_array('Content-Encoding: gzip',headers_list()))header('Content-Encoding: identity',TRUE);}self::_paintBlueScreen($exception);}elseif(self::$firebugDetected&&!headers_sent()){self::fireLog($exception,self::EXCEPTION);}foreach(self::$onFatalError
as$handler){call_user_func($handler,$exception);}}public
static
function
toStringException(Exception$exception){if(self::$enabled){self::_exceptionHandler($exception);}else{trigger_error($exception->getMessage(),E_USER_ERROR);}}public
static
function
_paintBlueScreen(Exception$exception){$internals=array();foreach(array('Object','ObjectMixin')as$class){if(class_exists($class,FALSE)){$rc=new
ReflectionClass($class);$internals[$rc->getFileName()]=TRUE;}}if(class_exists('Environment',FALSE)){$application=Environment::getServiceLocator()->hasService('Nette\Application\Application',TRUE)?Environment::getServiceLocator()->getService('Nette\Application\Application'):NULL;}if(!function_exists('_netteDebugPrintCode')){function
_netteDebugPrintCode($file,$line,$count=15){if(function_exists('ini_set')){ini_set('highlight.comment','#999; font-style: italic');ini_set('highlight.default','#000');ini_set('highlight.html','#06b');ini_set('highlight.keyword','#d24; font-weight: bold');ini_set('highlight.string','#080');}$start=max(1,$line-floor($count/2));$source=@file_get_contents($file);if(!$source)return;$source=explode("\n",highlight_string($source,TRUE));$spans=1;echo$source[0];$source=explode('<br />',$source[1]);array_unshift($source,NULL);$i=$start;while(--$i>=1){if(preg_match('#.*(</?span[^>]*>)#',$source[$i],$m)){if($m[1]!=='</span>'){$spans++;echo$m[1];}break;}}$source=array_slice($source,$start,$count,TRUE);end($source);$numWidth=strlen((string)key($source));foreach($source
as$n=>$s){$spans+=substr_count($s,'<span')-substr_count($s,'</span');$s=str_replace(array("\r","\n"),array('',''),$s);if($n===$line){printf("<span class='highlight'>Line %{$numWidth}s:    %s\n</span>%s",$n,strip_tags($s),preg_replace('#[^>]*(<[^>]+>)[^<]*#','$1',$s));}else{printf("<span class='line'>Line %{$numWidth}s:</span>    %s\n",$n,$s);}}echo
str_repeat('</span>',$spans),'</code>';}function
_netteDump($dump){return'<pre class="dump">'.preg_replace_callback('#(^|\s+)?(.*)\((\d+)\) <code>#','_netteDumpCb',$dump).'</pre>';}function
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
htmlspecialchars($title)?></title><!-- <?php echo$exception->getMessage(),($exception->getCode()?' #'.$exception->getCode():'')?> -->

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
				<?php _netteOpenPanel('Caused by',$level>2)?>
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
_netteDump(self::_dump($v,0));echo"</td></tr>\n";}?>
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
IDebugPanel&&$panel=$ex->getPanel()):?>
			<?php _netteOpenPanel($ex->getTab(),FALSE)?>
				<?php echo$panel?>
			<?php _netteClosePanel()?>
			<?php endif?>



			<?php if(isset($ex->context)&&is_array($ex->context)):?>
			<?php _netteOpenPanel('Variables',TRUE)?>
			<table>
			<?php

foreach($ex->context
as$k=>$v){echo'<tr><th>$',htmlspecialchars($k),'</th><td>',_netteDump(self::_dump($v,0)),"</td></tr>\n";}?>
			</table>
			<?php _netteClosePanel()?>
			<?php endif?>

		<?php }while((method_exists($ex,'getPrevious')&&$ex=$ex->getPrevious())||(isset($ex->previous)&&$ex=$ex->previous));?>
		<?php while(--$level)_netteClosePanel()?>



		<?php if(!empty($application)):?>
		<?php _netteOpenPanel('Nette Application',TRUE)?>
			<h3>Requests</h3>
			<?php $tmp=$application->getRequests();echo
_netteDump(self::_dump($tmp,0))?>

			<h3>Presenter</h3>
			<?php $tmp=$application->getPresenter();echo
_netteDump(self::_dump($tmp,0))?>
		<?php _netteClosePanel()?>
		<?php endif?>



		<?php _netteOpenPanel('Environment',TRUE)?>
			<?php
$list=get_defined_constants(TRUE);if(!empty($list['user'])):?>
			<h3><a href="#" onclick="return !netteToggle(this, 'pnl-env-const')">Constants <abbr>&#x25bc;</abbr></a></h3>
			<table id="pnl-env-const">
			<?php

foreach($list['user']as$k=>$v){echo'<tr'.($rn++%2?' class="odd"':'').'><th>',htmlspecialchars($k),'</th>';echo'<td>',_netteDump(self::_dump($v,0)),"</td></tr>\n";}?>
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
as$k=>$v)echo'<tr'.($rn++%2?' class="odd"':'').'><th>',htmlspecialchars($k),'</th><td>',_netteDump(self::_dump($v,0)),"</td></tr>\n";?>
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

foreach($GLOBALS[$name]as$k=>$v)echo'<tr'.($rn++%2?' class="odd"':'').'><th>',htmlspecialchars($k),'</th><td>',_netteDump(self::_dump($v,0)),"</td></tr>\n";?>
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
			<li>Report generated at <?php echo@date('Y/m/d H:i:s',self::$time)?></li>
			<?php if(isset($_SERVER['HTTP_HOST'],$_SERVER['REQUEST_URI'])):?>
				<li><a href="<?php $url=(isset($_SERVER['HTTPS'])&&strcasecmp($_SERVER['HTTPS'],'off')?'https://':'http://').htmlSpecialChars($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'])?>"><?php echo$url?></a></li>
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

<script type="text/javascript">
	document.body.appendChild(document.getElementById('netteBluescreen'));
</script>
</body>
</html><?php }public
static
function
_writeFile($buffer){fwrite(self::$logHandle,$buffer);}private
static
function
sendEmail($message){$monitorFile=self::$logFile.'.monitor';if(@filemtime($monitorFile)+self::$emailSnooze<time()&&@file_put_contents($monitorFile,'sent')){call_user_func(self::$mailer,$message);}}private
static
function
defaultMailer($message){$host=isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:(isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:'');$headers=str_replace(array('%host%','%date%','%message%'),array($host,@date('Y-m-d H:i:s',self::$time),$message),self::$emailHeaders);$subject=$headers['Subject'];$to=$headers['To'];$body=$headers['Body'];unset($headers['Subject'],$headers['To'],$headers['Body']);$header='';foreach($headers
as$key=>$value){$header.="$key: $value\r\n";}$body=str_replace("\r\n","\n",$body);if(PHP_OS!='Linux')$body=str_replace("\n","\r\n",$body);mail($to,$subject,$body,$header);}public
static
function
enableBar(){self::$enabledBar=TRUE;}public
static
function
disableBar(){self::$enabledBar=FALSE;}public
static
function
addPanel(IDebugPanel$panel){self::$panels[]=$panel;}public
static
function
getDefaultPanel($id){switch($id){case'time:tab':?><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAJ6SURBVDjLjZO7T1NhGMY7Mji6uJgYt8bElTjof6CDg4sMSqIxJsRGB5F4TwQSIg1QKC0KWmkZEEsKtEcSxF5ohV5pKSicXqX3aqGn957z+PUEGopiGJ583/A+v3znvPkJAAjWR0VNJG0kGhKahCFhXcN3YBFfx8Kry6ym4xIzce88/fbWGY2k5WRb77UTTbWuYA9gDGg7EVmSIOF4g5T7HZKuMcSW5djWDyL0uRf0dCc8inYYxTcw9fAiCMBYB3gVj1z7gLhNTjKCqHkYP79KENC9Bq3uxrrqORzy+9D3tPAAccspVx1gWg0KbaZFbGllWFM+xrKkFQudV0CeDfJsjN4+C2nracjunoPq5VXIBrowMK4V1gG1LGyWdbZwCalsBYUyh2KFQzpXxVqkAGswD3+qBDpZwow9iYE5v26/VwfUQnnznyhvjguQYabIIpKpYD1ahI8UTT92MUSFuP5Z/9TBTgOgFrVjp3nakaG/0VmEfpX58pwzjUEquNk362s+PP8XYD/KpYTBHmRg9Wch0QX1R80dCZhYipudYQY2Auib8RmODVCa4hfUK4ngaiiLNFNFdKeCWWscXZMbWy9Unv9/gsIQU09a4pwvUeA3Uapy2C2wCKXL0DqTePLexbWPOv79E8f0UWrencZ2poxciUWZlKssB4bcHeE83NsFuMgpo2iIpMuNa1TNu4XjhggWvb+R2K3wZdLlAZl8Fd9jRb5sD+Xx0RJBx5gdom6VsMEFDyWF0WyCeSOFcDKPnRxZYTQL5Rc/nn1w4oFsBaIhC3r6FRh5erPRhYMyHdeFw4C6zkRhmijM7CnMu0AUZonCDCnRJBqSus5/ABD6Ba5CkQS8AAAAAElFTkSuQmCC"
><?php echo
number_format((microtime(TRUE)-self::$time)*1000,1,'.',' ')?>ms
<?php 
return;case'memory:tab':?><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAGvSURBVDjLpZO7alZREEbXiSdqJJDKYJNCkPBXYq12prHwBezSCpaidnY+graCYO0DpLRTQcR3EFLl8p+9525xgkRIJJApB2bN+gZmqCouU+NZzVef9isyUYeIRD0RTz482xouBBBNHi5u4JlkgUfx+evhxQ2aJRrJ/oFjUWysXeG45cUBy+aoJ90Sj0LGFY6anw2o1y/mK2ZS5pQ50+2XiBbdCvPk+mpw2OM/Bo92IJMhgiGCox+JeNEksIC11eLwvAhlzuAO37+BG9y9x3FTuiWTzhH61QFvdg5AdAZIB3Mw50AKsaRJYlGsX0tymTzf2y1TR9WwbogYY3ZhxR26gBmocrxMuhZNE435FtmSx1tP8QgiHEvj45d3jNlONouAKrjjzWaDv4CkmmNu/Pz9CzVh++Yd2rIz5tTnwdZmAzNymXT9F5AtMFeaTogJYkJfdsaaGpyO4E62pJ0yUCtKQFxo0hAT1JU2CWNOJ5vvP4AIcKeao17c2ljFE8SKEkVdWWxu42GYK9KE4c3O20pzSpyyoCx4v/6ECkCTCqccKorNxR5uSXgQnmQkw2Xf+Q+0iqQ9Ap64TwAAAABJRU5ErkJggg=="
><?php echo
number_format(memory_get_peak_usage()/1000,1,'.',' ')?> kB
<?php 
return;case'dumps:tab':if(!Debug::$dumps)return;?><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAIASURBVDjLpVPPaxNREJ6Vt01caH4oWk1T0ZKlGIo9RG+BUsEK4kEP/Q8qPXnpqRdPBf8A8Wahhx7FQ0GF9FJ6UksqwfTSBDGyB5HkkphC9tfb7jfbtyQQTx142byZ75v5ZnZWC4KALmICPy+2DkvKIX2f/POz83LxCL7nrz+WPNcll49DrhM9v7xdO9JW330DuXrrqkFSgig5iR2Cfv3t3gNxOnv5BwU+eZ5HuON5/PMPJZKJ+yKQfpW0S7TxdC6WJaWkyvff1LDaFRAeLZj05MHsiPTS6hua0PUqtwC5sHq9zv9RYWl+nu5cETcnJ1M0M5WlWq3GsX6/T+VymRzHDluZiGYAAsw0TQahV8uyyGq1qFgskm0bHIO/1+sx1rFtchJhArwEyIQ1Gg2WD2A6nWawHQJVDIWgIJfLhQowTIeE9D0mKAU8qPC0220afsWFQoH93W6X7yCDJ+DEBeBmsxnPIJVKxWQVUwry+XyUwBlKMKwA8jqdDhOVCqVAzQDVvXAXhOdGBFgymYwrGoZBmUyGjxCCdF0fSahaFdgoTHRxfTveMCXvWfkuE3Y+f40qhgT/nMitupzApdvT18bu+YeDQwY9Xl4aG9/d/URiMBhQq/dvZMeVghtT17lSZW9/rAKsvPa/r9Fc2dw+Pe0/xI6kM9mT5vtXy+Nw2kU/5zOGRpvuMIu0YAAAAABJRU5ErkJggg==">variables
<?php 
return;case'dumps:panel':if(!Debug::$dumps)return;if(!function_exists('_netteDumpCb2')){function
_netteDumpCb2($m){return"$m[1]<a href='#'>$m[2]($m[3]) ".($m[3]<7?'<abbr>&#x25bc;</abbr> </a><code>':'<abbr>&#x25ba;</abbr> </a><code class="nette-hidden">');}}?>
<style type="text/css">
/* <![CDATA[ */

	#nette-debug-dumps h2 {
		font: 11pt/1.5 sans-serif;
		margin: 0;
		padding: 2px 8px;
		background: #3484d2;
		color: white;
	}

	#nette-debug #nette-debug-dumps a {
		text-decoration: none;
		color: #333;
		background: transparent;
	}

	#nette-debug-dumps a abbr {
		font-family: sans-serif;
		color: #999;
	}

	#nette-debug-dumps pre.nette-dump span {
		color: #c16549;
	}

	#nette-debug-dumps table {
		width: 100%;
	}

/* ]]> */
</style>


<h1>Dumped variables</h1>

<div class="nette-inner">
<?php foreach(self::$dumps
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
		<td><?php echo
preg_replace_callback('#(<pre class="nette-dump">|\s+)?(.*)\((\d+)\) <code>#','_netteDumpCb2',$dump)?></td>
	</tr>
	<?php endforeach?>
	</table>
<?php endforeach?>
</div>

<script type="text/javascript">
/* <![CDATA[ */

(function(){
	var $ = Nette.Q.factory, $p = Nette.Debug.Panel.factory;

	$('pre').bind('click', function(e) {
		var link = $(e.target || e.srcElement).closest('a');
		link.next('code').toggle(link.find('abbr'));

		$p('dumps').reposition();

		e.preventDefault();
	});
})();

/* ]]> */
</script><?php 
return;case'errors:tab':if(!Debug::$errors)return;?><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAIsSURBVDjLpVNLSJQBEP7+h6uu62vLVAJDW1KQTMrINQ1vPQzq1GOpa9EppGOHLh0kCEKL7JBEhVCHihAsESyJiE4FWShGRmauu7KYiv6Pma+DGoFrBQ7MzGFmPr5vmDFIYj1mr1WYfrHPovA9VVOqbC7e/1rS9ZlrAVDYHig5WB0oPtBI0TNrUiC5yhP9jeF4X8NPcWfopoY48XT39PjjXeF0vWkZqOjd7LJYrmGasHPCCJbHwhS9/F8M4s8baid764Xi0Ilfp5voorpJfn2wwx/r3l77TwZUvR+qajXVn8PnvocYfXYH6k2ioOaCpaIdf11ivDcayyiMVudsOYqFb60gARJYHG9DbqQFmSVNjaO3K2NpAeK90ZCqtgcrjkP9aUCXp0moetDFEeRXnYCKXhm+uTW0CkBFu4JlxzZkFlbASz4CQGQVBFeEwZm8geyiMuRVntzsL3oXV+YMkvjRsydC1U+lhwZsWXgHb+oWVAEzIwvzyVlk5igsi7DymmHlHsFQR50rjl+981Jy1Fw6Gu0ObTtnU+cgs28AKgDiy+Awpj5OACBAhZ/qh2HOo6i+NeA73jUAML4/qWux8mt6NjW1w599CS9xb0mSEqQBEDAtwqALUmBaG5FV3oYPnTHMjAwetlWksyByaukxQg2wQ9FlccaK/OXA3/uAEUDp3rNIDQ1ctSk6kHh1/jRFoaL4M4snEMeD73gQx4M4PsT1IZ5AfYH68tZY7zv/ApRMY9mnuVMvAAAAAElFTkSuQmCC"
><span class="nette-warning"><?php echo
count(self::$errors)?> errors</span>
<?php 
return;case'errors:panel':if(!Debug::$errors)return;?><h1>Errors</h1>

<?php $relative=isset($_SERVER['SCRIPT_FILENAME'])?strtr(dirname(dirname($_SERVER['SCRIPT_FILENAME'])),'/',DIRECTORY_SEPARATOR):NULL?>

<table class="nette-inner">
<?php foreach(self::$errors
as$i=>$item):?>
<tr class="<?php echo$i++%
2?'nette-alt':''?>">
	<td><pre><?php echo$relative?str_replace($relative,"\xE2\x80\xA6",$item):$item?></pre></td>
</tr>
<?php endforeach?>
</table>
<?php 
return;}}public
static
function
fireLog($message,$priority=self::LOG,$label=NULL){if($message
instanceof
Exception){if($priority!==self::EXCEPTION&&$priority!==self::TRACE){$priority=self::TRACE;}$message=array('Class'=>get_class($message),'Message'=>$message->getMessage(),'File'=>$message->getFile(),'Line'=>$message->getLine(),'Trace'=>$message->getTrace(),'Type'=>'','Function'=>'');foreach($message['Trace']as&$row){if(empty($row['file']))$row['file']='?';if(empty($row['line']))$row['line']='?';}}elseif($priority===self::GROUP_START){$label=$message;$message=NULL;}return
self::fireSend('FirebugConsole/0.1',self::replaceObjects(array(array('Type'=>$priority,'Label'=>$label),$message)));}private
static
function
fireSend($struct,$payload){if(self::$productionMode)return
NULL;if(headers_sent())return
FALSE;header('X-Wf-Protocol-nette: http://meta.wildfirehq.org/Protocol/JsonStream/0.2');header('X-Wf-nette-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.2.0');static$structures;$index=isset($structures[$struct])?$structures[$struct]:($structures[$struct]=count($structures)+1);header("X-Wf-nette-Structure-$index: http://meta.firephp.org/Wildfire/Structure/FirePHP/$struct");$payload=json_encode($payload);static$counter;foreach(str_split($payload,4990)as$s){$num=++$counter;header("X-Wf-nette-$index-1-n$num: |$s|\\");}header("X-Wf-nette-$index-1-n$num: |$s|");return
TRUE;}static
private
function
replaceObjects($val){if(is_object($val)){return'object '.get_class($val).'';}elseif(is_string($val)){return@iconv('UTF-16','UTF-8//IGNORE',iconv('UTF-8','UTF-16//IGNORE',$val));}elseif(is_array($val)){foreach($val
as$k=>$v){unset($val[$k]);$k=@iconv('UTF-16','UTF-8//IGNORE',iconv('UTF-8','UTF-16//IGNORE',$k));$val[$k]=self::replaceObjects($v);}}return$val;}}interface
IDebugPanel{function
getTab();function
getPanel();function
getId();}class
DebugPanel
implements
IDebugPanel{private$id;private$callback;public
function
__construct($id,$callback){$this->id=$id;$this->callback=$callback;}public
function
getId(){return$this->id;}public
function
getTab(){ob_start();call_user_func($this->callback,"$this->id:tab");return
ob_get_clean();}public
function
getPanel(){ob_start();call_user_func($this->callback,"$this->id:panel");return
ob_get_clean();}}Debug::_init();if(!function_exists('dump')){function
dump($var){foreach($args=func_get_args()as$arg)Debug::dump($arg);return$var;}}class
FatalErrorException
extends
Exception{private$severity;public
function
__construct($message,$code,$severity,$file,$line,$context){parent::__construct($message,$code);$this->severity=$severity;$this->file=$file;$this->line=$line;$this->context=$context;}public
function
getSeverity(){return$this->severity;}}