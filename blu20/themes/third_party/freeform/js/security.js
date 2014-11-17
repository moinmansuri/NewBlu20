//	psuedo copy of codeigniter's security lib
//	borrowed heavily on the work of
//  https://github.com/chriso/node-validator/

//	This is derived from the Codeigniter Security library.
//	Codeigniter is Copyright (c) 2008 - 2011, EllisLab, Inc.
//	and the license for the version derived from can be found
//	here: https://github.com/EllisLab/CodeIgniter/blob/v2.1.0/license.txt

;(function() {

	//es5 strict mode
	"use strict";

	//JSHint globals
	/*global exports:true */

	// -------------------------------------
	//	root finding method borrowed from
	//	underscore.js
	// -------------------------------------

	// Establish the root object
	var global = (
		(typeof exports !== 'undefined') ?
			exports : (
				(typeof window !== 'undefined') ?
					window :
					this
			)
	);

	var Security = global.Security = global.Security || {};

	var entities = {
		'&nbsp;': '\u00a0',
		'&iexcl;': '\u00a1',
		'&cent;': '\u00a2',
		'&pound;': '\u00a3',
		'&curren;': '\u20ac',
		'&yen;': '\u00a5',
		'&brvbar;': '\u0160',
		'&sect;': '\u00a7',
		'&uml;': '\u0161',
		'&copy;': '\u00a9',
		'&ordf;': '\u00aa',
		'&laquo;': '\u00ab',
		'&not;': '\u00ac',
		'&shy;': '\u00ad',
		'&reg;': '\u00ae',
		'&macr;': '\u00af',
		'&deg;': '\u00b0',
		'&plusmn;': '\u00b1',
		'&sup2;': '\u00b2',
		'&sup3;': '\u00b3',
		'&acute;': '\u017d',
		'&micro;': '\u00b5',
		'&para;': '\u00b6',
		'&middot;': '\u00b7',
		'&cedil;': '\u017e',
		'&sup1;': '\u00b9',
		'&ordm;': '\u00ba',
		'&raquo;': '\u00bb',
		'&frac14;': '\u0152',
		'&frac12;': '\u0153',
		'&frac34;': '\u0178',
		'&iquest;': '\u00bf',
		'&Agrave;': '\u00c0',
		'&Aacute;': '\u00c1',
		'&Acirc;': '\u00c2',
		'&Atilde;': '\u00c3',
		'&Auml;': '\u00c4',
		'&Aring;': '\u00c5',
		'&AElig;': '\u00c6',
		'&Ccedil;': '\u00c7',
		'&Egrave;': '\u00c8',
		'&Eacute;': '\u00c9',
		'&Ecirc;': '\u00ca',
		'&Euml;': '\u00cb',
		'&Igrave;': '\u00cc',
		'&Iacute;': '\u00cd',
		'&Icirc;': '\u00ce',
		'&Iuml;': '\u00cf',
		'&ETH;': '\u00d0',
		'&Ntilde;': '\u00d1',
		'&Ograve;': '\u00d2',
		'&Oacute;': '\u00d3',
		'&Ocirc;': '\u00d4',
		'&Otilde;': '\u00d5',
		'&Ouml;': '\u00d6',
		'&times;': '\u00d7',
		'&Oslash;': '\u00d8',
		'&Ugrave;': '\u00d9',
		'&Uacute;': '\u00da',
		'&Ucirc;': '\u00db',
		'&Uuml;': '\u00dc',
		'&Yacute;': '\u00dd',
		'&THORN;': '\u00de',
		'&szlig;': '\u00df',
		'&agrave;': '\u00e0',
		'&aacute;': '\u00e1',
		'&acirc;': '\u00e2',
		'&atilde;': '\u00e3',
		'&auml;': '\u00e4',
		'&aring;': '\u00e5',
		'&aelig;': '\u00e6',
		'&ccedil;': '\u00e7',
		'&egrave;': '\u00e8',
		'&eacute;': '\u00e9',
		'&ecirc;': '\u00ea',
		'&euml;': '\u00eb',
		'&igrave;': '\u00ec',
		'&iacute;': '\u00ed',
		'&icirc;': '\u00ee',
		'&iuml;': '\u00ef',
		'&eth;': '\u00f0',
		'&ntilde;': '\u00f1',
		'&ograve;': '\u00f2',
		'&oacute;': '\u00f3',
		'&ocirc;': '\u00f4',
		'&otilde;': '\u00f5',
		'&ouml;': '\u00f6',
		'&divide;': '\u00f7',
		'&oslash;': '\u00f8',
		'&ugrave;': '\u00f9',
		'&uacute;': '\u00fa',
		'&ucirc;': '\u00fb',
		'&uuml;': '\u00fc',
		'&yacute;': '\u00fd',
		'&thorn;': '\u00fe',
		'&yuml;': '\u00ff',
		'&quot;': '\u0022',
		'&lt;': '\u003c',
		'&gt;': '\u003e',
		'&apos;': '\u0027',
		'&minus;': '\u2212',
		'&circ;': '\u02c6',
		'&tilde;': '\u02dc',
		'&Scaron;': '\u0160',
		'&lsaquo;': '\u2039',
		'&OElig;': '\u0152',
		'&lsquo;': '\u2018',
		'&rsquo;': '\u2019',
		'&ldquo;': '\u201c',
		'&rdquo;': '\u201d',
		'&bull;': '\u2022',
		'&ndash;': '\u2013',
		'&mdash;': '\u2014',
		'&trade;': '\u2122',
		'&scaron;': '\u0161',
		'&rsaquo;': '\u203a',
		'&oelig;': '\u0153',
		'&Yuml;': '\u0178',
		'&fnof;': '\u0192',
		'&Alpha;': '\u0391',
		'&Beta;': '\u0392',
		'&Gamma;': '\u0393',
		'&Delta;': '\u0394',
		'&Epsilon;': '\u0395',
		'&Zeta;': '\u0396',
		'&Eta;': '\u0397',
		'&Theta;': '\u0398',
		'&Iota;': '\u0399',
		'&Kappa;': '\u039a',
		'&Lambda;': '\u039b',
		'&Mu;': '\u039c',
		'&Nu;': '\u039d',
		'&Xi;': '\u039e',
		'&Omicron;': '\u039f',
		'&Pi;': '\u03a0',
		'&Rho;': '\u03a1',
		'&Sigma;': '\u03a3',
		'&Tau;': '\u03a4',
		'&Upsilon;': '\u03a5',
		'&Phi;': '\u03a6',
		'&Chi;': '\u03a7',
		'&Psi;': '\u03a8',
		'&Omega;': '\u03a9',
		'&alpha;': '\u03b1',
		'&beta;': '\u03b2',
		'&gamma;': '\u03b3',
		'&delta;': '\u03b4',
		'&epsilon;': '\u03b5',
		'&zeta;': '\u03b6',
		'&eta;': '\u03b7',
		'&theta;': '\u03b8',
		'&iota;': '\u03b9',
		'&kappa;': '\u03ba',
		'&lambda;': '\u03bb',
		'&mu;': '\u03bc',
		'&nu;': '\u03bd',
		'&xi;': '\u03be',
		'&omicron;': '\u03bf',
		'&pi;': '\u03c0',
		'&rho;': '\u03c1',
		'&sigmaf;': '\u03c2',
		'&sigma;': '\u03c3',
		'&tau;': '\u03c4',
		'&upsilon;': '\u03c5',
		'&phi;': '\u03c6',
		'&chi;': '\u03c7',
		'&psi;': '\u03c8',
		'&omega;': '\u03c9',
		'&thetasym;': '\u03d1',
		'&upsih;': '\u03d2',
		'&piv;': '\u03d6',
		'&ensp;': '\u2002',
		'&emsp;': '\u2003',
		'&thinsp;': '\u2009',
		'&zwnj;': '\u200c',
		'&zwj;': '\u200d',
		'&lrm;': '\u200e',
		'&rlm;': '\u200f',
		'&sbquo;': '\u201a',
		'&bdquo;': '\u201e',
		'&dagger;': '\u2020',
		'&Dagger;': '\u2021',
		'&hellip;': '\u2026',
		'&permil;': '\u2030',
		'&prime;': '\u2032',
		'&Prime;': '\u2033',
		'&oline;': '\u203e',
		'&frasl;': '\u2044',
		'&euro;': '\u20ac',
		'&image;': '\u2111',
		'&weierp;': '\u2118',
		'&real;': '\u211c',
		'&alefsym;': '\u2135',
		'&larr;': '\u2190',
		'&uarr;': '\u2191',
		'&rarr;': '\u2192',
		'&darr;': '\u2193',
		'&harr;': '\u2194',
		'&crarr;': '\u21b5',
		'&lArr;': '\u21d0',
		'&uArr;': '\u21d1',
		'&rArr;': '\u21d2',
		'&dArr;': '\u21d3',
		'&hArr;': '\u21d4',
		'&forall;': '\u2200',
		'&part;': '\u2202',
		'&exist;': '\u2203',
		'&empty;': '\u2205',
		'&nabla;': '\u2207',
		'&isin;': '\u2208',
		'&notin;': '\u2209',
		'&ni;': '\u220b',
		'&prod;': '\u220f',
		'&sum;': '\u2211',
		'&lowast;': '\u2217',
		'&radic;': '\u221a',
		'&prop;': '\u221d',
		'&infin;': '\u221e',
		'&ang;': '\u2220',
		'&and;': '\u2227',
		'&or;': '\u2228',
		'&cap;': '\u2229',
		'&cup;': '\u222a',
		'&int;': '\u222b',
		'&there4;': '\u2234',
		'&sim;': '\u223c',
		'&cong;': '\u2245',
		'&asymp;': '\u2248',
		'&ne;': '\u2260',
		'&equiv;': '\u2261',
		'&le;': '\u2264',
		'&ge;': '\u2265',
		'&sub;': '\u2282',
		'&sup;': '\u2283',
		'&nsub;': '\u2284',
		'&sube;': '\u2286',
		'&supe;': '\u2287',
		'&oplus;': '\u2295',
		'&otimes;': '\u2297',
		'&perp;': '\u22a5',
		'&sdot;': '\u22c5',
		'&lceil;': '\u2308',
		'&rceil;': '\u2309',
		'&lfloor;': '\u230a',
		'&rfloor;': '\u230b',
		'&lang;': '\u2329',
		'&rang;': '\u232a',
		'&loz;': '\u25ca',
		'&spades;': '\u2660',
		'&clubs;': '\u2663',
		'&hearts;': '\u2665',
		'&diams;': '\u2666'
	};

	var decode = function (str)
	{
		if (str.indexOf('&') == -1)
		{
			return str;
		}

		//Decode literal entities
		for (var i in entities)
		{
			if (entities.hasOwnProperty(i))
			{
				str = str.replace(new RegExp(i, 'g'), entities[i]);
			}
		}

		//Decode hex entities
		str = str.replace(/&#x(0*[0-9a-f]{2,5});?/gi, function (m, code) {
			return String.fromCharCode(parseInt(+code, 16));
		});

		//Decode numeric entities
		str = str.replace(/&#([0-9]{2,4});?/gi, function (m, code) {
			return String.fromCharCode(+code);
		});

		str = str.replace(/&amp;/g, '&');

		return str;
	};

	var encode = function (str)
	{
		str = str.replace(/&/g, '&amp;');

		//IE doesn't accept &apos;
		str = str.replace(/'/g, '&#39;');

		//Encode literal entities
		for (var i in entities)
		{
			if (entities.hasOwnProperty(i))
			{
				str = str.replace(new RegExp(entities[i], 'g'), i);
			}
		}

		return str;
	};

	Security.entities = {
		encode : encode,
		decode : decode
	};

	//This module is adapted from the CodeIgniter framework
	//The license is available at http://codeigniter.com/

	var neverAllowedStr = {
		'document.cookie'				: '[removed]',
		'document.write'				: '[removed]',
		'.parentNode'					: '[removed]',
		'.innerHTML'					: '[removed]',
		'window.location'				: '[removed]',
		'-moz-binding'					: '[removed]',
		'<!--'							: '&lt;!--',
		'-->'							: '--&gt;',
		'<![CDATA['						: '&lt;![CDATA['
	};

	var neverAllowedRegex = {
		'javascript\\s*:'				: '[removed]',
		'expression\\s*(\\(|&\\#40;)'	: '[removed]',
		'vbscript\\s*:'					: '[removed]',
		'Redirect\\s+302'				: '[removed]',
		"([\"'])?data\\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?" : '[removed]'
	};

	var nonDisplayables = [
		/%0[0-8bcef]/g,			// url encoded 00-08, 11, 12, 14, 15
		/%1[0-9a-f]/g,			// url encoded 16-31
		/[\x00-\x08]/g,			// 00-08
		/\x0b/g, /\x0c/g,		// 11,12
		/[\x0e-\x1f]/g			// 14-31
	];

	var compactWords = [
		'javascript', 'expression', 'vbscript',
		'script', 'applet', 'alert', 'document',
		'write', 'cookie', 'window', 'base64'
	];

	var isArray = function (arr)
	{
		return (typeof Array.isArray !== 'undefined') ?
				Array.isArray(arr) :
				Object.prototype.toString.apply(arr) === '[object Array]';
	};

	var xssClean = Security.xssClean = function (str, isImage)
	{
		var i;

		// -------------------------------------
		//	is object or array?
		// -------------------------------------

		if (isArray(str))
		{
			i = str.length;

			while (i--)
			{
				str[i] = Security.xssClean(str[i]);
			}

			return str;
		}

		if (typeof str === 'object')
		{
			for (i in str)
			{
				if (str.hasOwnProperty(i))
				{
					str[i] = Security.xssClean(str[i]);
				}
			}

			return str;
		}

		// -------------------------------------
		//	begin cleaning
		// -------------------------------------

		//Remove invisible characters
		str = removeInvisibleChars(str);

		//Protect query string variables in URLs => 901119URL5918AMP18930PROTECT8198
		str = str.replace(/\&([a-z\_0-9]+)\=([a-z\_0-9]+)/i, xssHash() + '$1=$2');

		//Validate standard character entities - add a semicolon if missing.  We do this to enable
		//the conversion of entities to ASCII later.
		str = str.replace(/(&\#?[0-9a-z]{2,})([\x00-\x20])*;?/i, '$1;$2');

		//Validate UTF16 two byte encoding (x00) - just as above, adds a semicolon if missing.
		str = str.replace(/(&\#x?)([0-9A-F]+);?/i, '$1;$2');

		//Un-protect query string variables
		str = str.replace(xssHash(), '&');

		//Decode just in case stuff like this is submitted:
		//<a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
		str = decodeURIComponent(str);

		//Convert character entities to ASCII - this permits our tests below to work reliably.
		//We only convert entities that are within tags since these are the ones that will pose security problems.
		str = str.replace(/[a-z]+=([\'\"]).*?\\1/gi, function(m, match) {
			return m.replace(match, convertAttribute(match));
		});

		str = str.replace(/<\w+.*?(?=>|<|$)/gi, function(m) {
			return m.replace(m, decode(m));
		});

		//Remove invisible characters again
		str = removeInvisibleChars(str);

		//Convert tabs to spaces
		str = str.replace('\t', ' ');

		//Captured the converted string for later comparison
		var converted_string = str;

		//Remove strings that are never allowed
		for (i in neverAllowedStr)
		{
			if (neverAllowedStr.hasOwnProperty(i))
			{
				str = str.replace(i, neverAllowedStr[i]);
			}
		}

		//Remove regex patterns that are never allowed
		for (i in neverAllowedRegex)
		{
			if (neverAllowedRegex.hasOwnProperty(i))
			{
				str = str.replace(new RegExp(i, 'i'), neverAllowedRegex[i]);
			}
		}

		//Compact any exploded words like:  j a v a s c r i p t
		// We only want to do this when it is followed by a non-word character
		for (i in compactWords)
		{
			if (compactWords.hasOwnProperty(i))
			{
				var spacified = compactWords[i].split('').join('\\s*')+'\\s*';

				str = str.replace(new RegExp('('+spacified+')(\\W)', 'ig'), function(m, compat, after) {
					return compat.replace(/\s+/g, '') + after;
				});
			}
		}

		//Remove disallowed Javascript in links or img tags
		var original;

		do {
			original = str;

			if (str.match(/<a/i)) {
				str = str.replace(/<a\\s+([^>]*?)(>|$)/gi, function(m, attributes, end_tag) {
					attributes = filterAttributes(attributes.replace('<','').replace('>',''));
					return m.replace(attributes, attributes.replace(/href=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\\s*,)/gi, ''));
				});
			}

			if (str.match(/<img/i)) {
				str = str.replace(/<img\\s+([^>]*?)(\\s?\/?>|$)/gi, function(m, attributes, end_tag) {
					attributes = filterAttributes(attributes.replace('<','').replace('>',''));
					return m.replace(attributes, attributes.replace(/src=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\\s*,)/gi, ''));
				});
			}

			if (str.match(/script/i) || str.match(/xss/i)) {
				str = str.replace(/<(\/*)(script|xss)(.*?)\>/gi, '[removed]');
			}

		} while(original != str);

		str = removeEvilAttributes(str, isImage);

		//Sanitize naughty HTML elements
		//If a tag containing any of the words in the list
		//below is found, the tag gets converted to entities.
		//So this: <blink>
		//Becomes: &lt;blink&gt;
		var naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
		str = str.replace(new RegExp('<(/*\\s*)('+naughty+')([^><]*)([><]*)', 'gi'), function(m, a, b, c, d) {
			return '&lt;' + a + b + c + d.replace('>','&gt;').replace('<','&lt;');
		});

		//Sanitize naughty scripting elements Similar to above, only instead of looking for
		//tags it looks for PHP and JavaScript commands that are disallowed.  Rather than removing the
		//code, it simply converts the parenthesis to entities rendering the code un-executable.
		//For example:  eval('some code')
		//Becomes:      eval&#40;'some code'&#41;
		str = str.replace(/(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\\s*)\((.*?)\)/gi, '$1$2&#40;$3&#41;');

		//This adds a bit of extra precaution in case something got through the above filters
		for (i in neverAllowedStr)
		{
			if (neverAllowedStr.hasOwnProperty(i))
			{
				str = str.replace(i, neverAllowedStr[i]);
			}
		}

		for (i in neverAllowedRegex)
		{
			if (neverAllowedRegex.hasOwnProperty(i))
			{
				str = str.replace(new RegExp(i, 'i'), neverAllowedRegex[i]);
			}
		}

		//Images are handled in a special way
		if (isImage)
		{
			return (str !== converted_string);
		}

		return str;
	};

	function removeEvilAttributes (str, isImage)
	{
		var evilAttributes = ['on\\w*', 'style', 'xmlns', 'formaction'];

		//Adobe Photoshop puts XML metadata into JFIF images, including namespacing,
		//so we have to allow this for images
		if ( ! isImage)
		{
			evilAttributes.push('xmlns');
		}

		var count;
		var attributes;
		var result;
		var quoteStringMatch	= new RegExp("(" + evilAttributes.join('|') + ")\\s*=\\s*([^\\s>]*)", 'gi');
		var octQuoteStringMatch	= new RegExp("(" + evilAttributes.join('|') + ")\\s*=\\s*(\\042|\\047)([^\\2]*?)(\\2)", 'gi');

		do {
			count		= 0;
			attributes	= [];
			result		= null;

			do {

				if (result)
				{
					attributes.push(regQuote(result[0], '/'));
				}

				result = quoteStringMatch.exec(str);
			}
			while (result);

			do {

				if (result)
				{
					attributes.push(regQuote(result[0], '/'));
				}

				result = octQuoteStringMatch.exec(str);
			}
			while (result);

			if (attributes.length > 0)
			{
				str = str.replace(
					new RegExp(
						"<(\\/?[^><]+?)([^A-Za-z<>\\-])(.*?)(" +
							attributes.join('|') +
						")(.*?)([\\s><])([><]*)",
						'i'
					),
					function (m, a, b, c, d, e, f, g){
						count++;
						return '<' + a + ' ' + c + e + f + g;
					}
				);
			}

		} while (count);

		return str;
	}

	//END removeEvilAttributes

	function removeInvisibleChars (str)
	{
		for (var i in nonDisplayables)
		{
			if (nonDisplayables.hasOwnProperty(i))
			{
				str = str.replace(nonDisplayables[i], '');
			}
		}

		return str;
	}

	function regQuote (str, delimiter)
	{
		// Quote regular expression characters plus an optional character
		return (str + '').replace(
			new RegExp(
				'[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\' + (delimiter || '') + '-]',
				'g'
			),
			'\\$&'
		);
	}

	function xssHash()
	{
		//TODO: Create a random hash
		return 'beaf6cec1bee7eb442629516b41d5453';
	}

	function convertAttribute (str)
	{
		return str.replace('>','&gt;').replace('<','&lt;').replace('\\','\\\\');
	}

	//Filter Attributes - filters tag attributes for consistency and safety
	function filterAttributes (str)
	{
		var out = '';

		str.replace(/\\s*[a-z\-]+\\s*=\\s*(?:\042|\047)(?:[^\\1]*?)\\1/gi, function(m) {
			out += m.replace(/\/\*.*?\*\//g, '');
		});

		return out;
	}

}());
