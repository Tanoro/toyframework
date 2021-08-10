/**
* Dialogue Library
* Write and interact with popup dialogues on-screen.
* jQuery required
**/


// Preload images
var images = {
	closeButton: new Image(),
	closeButtonDisabled: new Image(),
	throbber: new Image(),
	errorWarning: new Image(),
	greenCheck: new Image(),
	caution: new Image()
};

images.closeButton.src = '/js/dialogues/images/closeBtn.png';
images.closeButtonDisabled.src = '/js/dialogues/images/greyBtn.png';
images.throbber.src = '/js/dialogues/images/throbber2.gif';
images.errorWarning.src = '/js/dialogues/images/error-warning.png';
images.greenCheck.src = '/js/dialogues/images/green-check.png';
images.caution.src = '/js/dialogues/images/caution.png';

var pageUnload = false;

$(document).ready(function() {
	$( window ).on('unload', function() {
		pageUnload = true;
	});
});

// Center a dialogue
jQuery.fn.center = function(offset)
{
	if (!offset)
	{
		// Default the offset to zero
		offset = 0;
	}

	var top = Math.max(0, ($(window).height() - $(this).outerHeight()) / 2) + $(window).scrollTop() + offset;
	var left = Math.max(0, ($(window).width() - $(this).outerWidth()) / 2) + offset;

	this.css('position','absolute');
	this.css('top', top + 'px');
	this.css('left', left + 'px');

	return this;
}


// Get random string for generating IDs
function randomString(length)
{
	var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz'.split('');
	var str = '';

	length = (length ? length : 8);

	for (var i = 0; i < length; i++)
	{
		str += chars[Math.floor(Math.random() * chars.length)];
	}

	return str;
}


// Turn on the background grey-out
function jQgreyoutOn(zIndex)
{
	if (!zIndex)
	{
		// Default to 4, since the default dialogue z-index is 5
		zIndex = 4;
	}
	else
	{
		zIndex = (zIndex - 1);
	}

	if ($('#greyout').length)
	{
		// Remove existing greyout backgrounds
		//$('#greyout').remove();
		return;
	}

	var greyout = $('<div></div>').attr('id', 'greyout').css({
		'width' : '100%',
		'height' : '100%',
		'z-index': zIndex
	}).hide();

	$('body').append(greyout);
	$(greyout).fadeIn();

	$('body').css('overflow', 'auto');
}

// Turn off the background grey-out
function jQgreyoutOff()
{
	$('#greyout').fadeOut('slow', function(){
		$('#greyout').remove();
		$('body').css('overflow', 'visible');
	});
}


/**
* Draw an object type dialogue
* @param	{string}	containerObj	The object in which to place the popup dialogue in the DOM. Defaults to the body tag.
* @param	{string}	closeClass		The class name that will close this dialogue. This must correspond to the jQuery events.
*										Defaults to closeDialogue, a simple disappearance of the dialogue.
* @param	{string}	dialogueID		Set the id attribute of the dialogue. Otherwise, a random ID will be generated.
* @param	{boolean}	showClose		Set to false to show no close button.
* @param	{boolean}	greyout			Does this dialogue call upon its own background greyout? The greyout function will
*										automatically prevent duplicates.
* @param	{boolean}	greyoutControl	Closing this dialogue closes the background too. Defaults to true
* @param	{string}	*title			The string that will be put into the title header of the dialogue
* @param	{string}	*body			The primary content to be inserted into the body of the dialogue
* @param	{string}	clipart			The path to a graphic that may be included in the dialogue
* @param	{string}	footer			The text, if any, to be inserted into the footer
* @param	{mixed}		size			The class name that sets the pixel width of the dialogue OR pixel width of the dialogue
* @param	{integer}	zIndex			The z-index on which to draw this dialogue; defaults to 5
*
* @return	{string}					The dialogue ID number so that future events can recapture and interact with it
**/
function drawDialogue(options, callback)
{
	// Preload the clipart before doing anything else
	if (options.clipart)
	{
		images.clipart = new Image();
		images.clipart.src = options.clipart;

		$(images.clipart).addClass('clipart');
	}

	var winHeight = $(window).height();
	var winWidth = $(window).width();

	// Generate a unique ID number for this dialogue
	var dialogueID = (options.hasOwnProperty('dialogueID') ? options.dialogueID : 'dialogue' + randomString());

	// Set default options
	options.containerObj = (options.hasOwnProperty('containerObj') ? options.containerObj : 'body');
	options.closeClass = (options.hasOwnProperty('closeClass') ? options.closeClass : 'closeDialogue');
	options.showClose = (options.hasOwnProperty('showClose') ? options.showClose : true);
	options.greyout = (options.hasOwnProperty('greyout') ? options.greyout : false);
	options.greyoutControl = (options.hasOwnProperty('greyoutControl') ? options.greyoutControl : true);
	options.zIndex = (options.hasOwnProperty('zIndex') ? options.zIndex : 20);


	var output = '';
	var rank = $('.dialogueContainer').length;

	if (options.title)
	{
		// Get close button
		$(images.closeButton).addClass(options.closeClass);

		var header = $('<div></div>').addClass('header').html(options.title + (options.showClose ? images.closeButton.outerHTML : ''));
		output += header[0].outerHTML;
	}

	// Prepend the clipart to the body content, if applicable
	if (options.clipart)
	{
		options.body = images.clipart.outerHTML + options.body;
	}

	body = $('<div></div>').addClass('body').html(options.body);
	output += body[0].outerHTML;

	if (options.footer)
	{
		// We have a footer to append
		footer = $('<div></div>').addClass('foot').html(options.footer);
		output += footer[0].outerHTML;
	}

	// Start tying together the dialogue and drawing it into the DOM
	var dialogueContainer = $('<div></div>').addClass('dialogueContainer draggable').attr('id', dialogueID).css({'z-index': options.zIndex}).html(output);

	// Set the size of the dialogue container
	if (options.size)
	{
		// Height
		if (winHeight < 650)
		{
			$(dialogueContainer).css({'max-height': winHeight});
		}

		// Width
		if (isNaN(options.size))
		{
			// These are recognized size by keywords
			var keywords = {
				small : 500,
				medium : 745,
				GoogleMap : 816,
				max : winWidth
			};

			if (keywords[options.size] > winWidth)
			{
				// Do not over-extend the width
				options.size = winWidth;
				$(dialogueContainer).width(options.size);
			}
			else
			{
				$(dialogueContainer).width(keywords[options.size]);
			}
		}
		else
		{
			// This is a pixel width
			if (options.size > winWidth)
			{
				// Do not over-extend the width
				options.size = winWidth;
			}

			$(dialogueContainer).width(options.size);
		}

		// Mobile adjustments
		var dialogueWidth = $(dialogueContainer).width();

		if (winWidth < 400)
		{
			// We must be on a tiny screen, so let's give them more room
			$(dialogueContainer).css({'font-size' : '1.1em', 'max-height' : winHeight});
			$(dialogueContainer).width('100%');
			$(dialogueContainer).height('100%');
		}
	}


	// Are we drawing a greyout?
	if (options.greyout)
	{
		jQgreyoutOn(options.zIndex);
	}

	// Draw a floating container and append the dialogue
	$(options.containerObj).append(dialogueContainer[0].outerHTML);

	// Special effects and closing events
	$('#' + dialogueID).fadeIn();

	// Center the container in the window with a dynamic offset
	var offset = rank * 40;

	$('#' + dialogueID).center(offset);

	// If we have a callback function, execute it
	if (options.hasOwnProperty('complete'))
	{
		options.complete();
	}

	if (winWidth >= 400)
	{
		// Ensure popups stay centered on resizing for large screens
		$( window ).resize(function() {
			$('#' + dialogueID).center();
		});

		$( window ).scroll(function() {
			$('#' + dialogueID).center();
		});
	}


	/*
	* Options for closing the dialogue
	*/

	if (options.showClose)
	{
		// Simple hide of the dialogue, but keep it in the DOM
		if (options.closeClass == 'closeDialogue')
		{
			closeDialogue(dialogueID, options);
		}

		// Simple destruction of the dialogue
		if (options.closeClass == 'destroyDialogue')
		{
			destroyDialogue(dialogueID, options);
		}

		// Fadeout hide of the dialogue
		if (options.closeClass == 'fadeCloseDialogue')
		{
			fadeCloseDialogue(dialogueID, options);
		}

		// Fadeout destruction of the dialogue
		if (options.closeClass == 'fadeDestroyDialogue')
		{
			fadeDestroyDialogue(dialogueID, options);
		}
	}

	if (callback)
	{
		callback(dialogueID);
	}

	return dialogueID;
}

// Draw a large widget throbber with small dialogue
function drawThrobber()
{
	return drawDialogue({
		'size' : 'small',
		'body' : '<p align="center">Please Wait...<br>' + images.throbber.outerHTML + '</p>'
	});
}

// Draw a confirm dialogue with OK/Cancel buttons
function jqConfirm(options)
{
	options.closeClass = (options.closeClass ? options.closeClass : 'closeDialogue');

	var body = options.body;
	body += '<br><br>';
	body += '<div class="controllers">';
	body += '<input type="submit" name="' + options.buttonName + '" value="' + (options.goBtn ? options.goBtn : 'OK') + '" /> ';
	body += '<input type="button" value="Cancel" class="' + options.closeClass + '" />';
	body += '</div>';

	return drawDialogue({
		'title' : options.title,
		'size' : 'small',
		'greyoutControl' : false,
		'body' : body
	});
}

// Draw a standard popup error dialogue
function drawErrorDialogue(string, arg1, arg2)
{
	if (pageUnload)
	{
		// Never show an error happening during page unload
		return;
	}

	var techInfo = '', opts = {};

	if (typeof arg1 == 'string')
	{
		techInfo = htmlEntities(arg1);
	}
	else if (typeof arg1 == 'object')
	{
		opts = arg1;
	}

	if (typeof arg2 == 'string')
	{
		techInfo = htmlEntities(arg2);
	}
	else if (typeof arg2 == 'object')
	{
		opts = arg2;
	}


	var body = '<img src="' + images.errorWarning.src + '" class="clipart" />';
	body += string;
	//body += '<p><a href="/tech-support" target="_blank">Technical Issues? Report it!</a></p>';

	footer = '<div class="controls">';
	footer += '<form action="/tech-support" method="post" target="_blank">';
	footer += '<input type="submit" class="activeButton" value="Technical Issues? Report it!" />';
	footer += '<input type="hidden" name="errorMsg" value="' + string + '" />';
	footer += '<input type="hidden" name="techInfo" value="' + techInfo + '" />';
	footer += '<input type="hidden" name="errorUrl" value="' + document.URL + '" />';
	footer += '</form>';
	footer += '</div>';

	var options = {
		'size' : (opts.hasOwnProperty('size') ? opts.size : 'small'),
		'greyout' : (opts.hasOwnProperty('greyout') ? opts.greyout : true),
		'greyoutControl' : (opts.hasOwnProperty('greyoutControl') ? opts.greyoutControl : true),
		'title' : (opts.hasOwnProperty('title') ? opts.title : 'Error!'),
		'body' : body,
		'footer' : footer,
		'closeClass' : (opts.hasOwnProperty('closeClass') ? opts.closeClass : 'fadeDestroyDialogue')
	};

	return drawDialogue(options);
}


// Draw a standard popup success dialogue
function drawSuccessDialogue(string, arg1)
{
	var opts = {};

	if (typeof arg1 == 'object')
	{
		opts = arg1;
	}


	var body = '<img src="' + images.greenCheck.src + '" class="clipart" />';
	body += string;

	var options = {
		'size' : (opts.hasOwnProperty('size') ? opts.size : 'small'),
		'greyout' : (opts.hasOwnProperty('greyout') ? opts.greyout : true),
		'greyoutControl' : (opts.hasOwnProperty('greyoutControl') ? opts.greyoutControl : true),
		'title' : (opts.hasOwnProperty('title') ? opts.title : 'Success!'),
		'body' : body,
		'closeClass' : (opts.hasOwnProperty('closeClass') ? opts.closeClass : 'fadeDestroyDialogue')
	};

	return drawDialogue(options);
}


// Show console dialogue
function drawConsole(string)
{
	//<div id="console"><img src="/admin/images/xBtn.png" class="right pointer close" /><strong>Console:</strong><br>' . $message . '</div>
	var dialogue = $('<div></div>').attr('id', 'console').html(string);

	$('body').append(dialogue[0].outerHTML);

	return dialogue;
}



/*
* Mobile devices need other considerations, so we need to detect them
* http://stackoverflow.com/questions/11381673/javascript-solution-to-detect-mobile-browser
*/
window.mobilecheck = function()
{
var check = false;
(function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4)))check = true})(navigator.userAgent||navigator.vendor||window.opera);
return check;
}


/*
* Dialogue Actions
*/

// Jitter a dialogue back and forth
function jitterDialogue(dialogueID)
{
	var position = $('#' + dialogueID).position();
	var left = position.left;
	var speed = 100;

	//console.log('Left: ' + left);

	$('#' + dialogueID).animate({left: (left + 10) + 'px'}, speed, function() {
		$('#' + dialogueID).animate({left: (left - 10) + 'px'}, speed, function() {
			$('#' + dialogueID).animate({left: (left + 10) + 'px'}, speed, function() {
				$('#' + dialogueID).animate({left: (left - 10) + 'px'}, speed, function() {
					$('#' + dialogueID).animate({left: left + 'px'}, speed);
				});
			});
		});
	});
}

/*
* Dialogue Destroyers
*/

// Close dialogue, maintaining it in the DOM
function closeDialogue(dialogueID, options)
{
	$('#' + dialogueID).find('.' + options.closeClass).one('click', function() {
		$('#' + dialogueID).hide();

		if (options.greyoutControl)
		{
			jQgreyoutOff();
		}
	});

	if (options.greyoutControl)
	{
		// If this dialogue has greyout control, put an onclick close on the greyout
		$('#greyout').one('click', function() {
			$('#' + dialogueID).hide();

			if (options.greyoutControl)
			{
				jQgreyoutOff();
			}
		});
	}
}

// Remove the dialogue from the DOM
function destroyDialogue(dialogueID, options)
{
	$('#' + dialogueID).find('.' + options.closeClass).one('click', function() {
		$('#' + dialogueID).remove();

		if (options.greyoutControl)
		{
			jQgreyoutOff();
		}
	});

	if (options.greyoutControl)
	{
		// If this dialogue has greyout control, put an onclick close on the greyout
		$('#greyout').one('click', function() {
			$('#' + dialogueID).remove();

			if (options.greyoutControl)
			{
				jQgreyoutOff();
			}
		});
	}
}

// Fade out dialogue and close it
function fadeCloseDialogue(dialogueID, options)
{
	$('#' + dialogueID).find('.' + options.closeClass).one('click', function() {
		$('#' + dialogueID).fadeOut('fast', function(){
			$('#' + dialogueID).hide();
		});

		if (options.greyoutControl)
		{
			jQgreyoutOff();
		}
	});

	if (options.greyoutControl)
	{
		// If this dialogue has greyout control, put an onclick close on the greyout
		$('#greyout').one('click', function() {
			$('#' + dialogueID).fadeOut('fast', function(){
				$('#' + dialogueID).hide();
			});

			if (options.greyoutControl)
			{
				jQgreyoutOff();
			}
		});
	}
}

// Fade out dialogue and remove it from the DOM
function fadeDestroyDialogue(dialogueID, options)
{
	$('#' + dialogueID).find('.' + options.closeClass).one('click', function() {
		$('#' + dialogueID).fadeOut('fast', function(){
			$('#' + dialogueID).remove();
		});

		if (options.greyoutControl)
		{
			jQgreyoutOff();
		}
	});

	if (options.greyoutControl)
	{
		// If this dialogue has greyout control, put an onclick close on the greyout
		$('#greyout').one('click', function() {
			$('#' + dialogueID).fadeOut('fast', function(){
				$('#' + dialogueID).remove();
			});

			if (options.greyoutControl)
			{
				jQgreyoutOff();
			}
		});
	}
}


/*
* String functions that may be needed
*/
function htmlEntities(str)
{
	if (!str)
	{
		return;
	}

	return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}



/*
* DEPRECATED!
* Simple dialogue function
*/
function drawDialogue1(options)
{
	var dialogueID = drawDialogue({
		'title' : options.title,
		'size' : options.size,
		'closeClass' : 'destroyDialogue',
		'body' : options.body
	});

	return '#' + dialogueID;
}
