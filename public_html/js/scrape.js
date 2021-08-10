
var ENV = {
	cacheFile : null
};

$(document).ready(function() {

	$('input[name="scrapeUrl"]').click(function(){
		LOCAL.scrapeUrl();
	});

	$('input[name="readXpath"]').click(function(){
		LOCAL.readXpath();
	});
});

var LOCAL = {
	// Get the source of the URL
	scrapeUrl : function()
	{
		// Busy mouse cursor
		$('body').css('cursor', 'progress');

		var url = $('input[name="url"]').val();

		$.post('/environment', {
			'do' : 'scrapeUrl',
			'url' : url
		},
		function(data)
		{
			// Reset mouse cursor
			$('body').css('cursor', 'auto');

			if (!data.error)
			{
				$('div#htmlOutput').html(data.output.source);
				$('div#curlOutput').html(data.output.curlinfo);
				ENV.cacheFile = data.output.cacheFile;

				// Enable the Xpath highlighter
				$('input[name="readXpath"]').removeAttr('disabled');
			}
			else
			{
				$('div#curlOutput').html(data.output.curlinfo);
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},

	readXpath : function()
	{
		// Busy mouse cursor
		$('body').css('cursor', 'progress');
		$('#xpathError').html('');

		var xpath = $('input[name="xpath"]').val();

		$.post('/environment', {
			'do' : 'readXpath',
			'cacheFile' : ENV.cacheFile,
			'xpath' : xpath
		},
		function(data)
		{
			// Reset mouse cursor
			$('body').css('cursor', 'auto');

			if (!data.error)
			{
				// Return the source with the Xpath highlighter inserted
				$('div#htmlOutput').html(data.output.source);

				if (data.output.failed)
				{
					$('#xpathError').html('Xpath query failed');
				}
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	}
};
