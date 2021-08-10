$(document).ready(function() {

	$('input[name="sendAPI"]').click(function(){
		LOCAL.sendAPI();
	});
});

var LOCAL = {

	sendAPI : function()
	{
		$.post('/environment', {
			'do' : 'sendAPI',
			'q' : $('textarea[name="query"]').val()
		},
		function(data)
		{
			if (!data.error)
			{
				// Setting status to run
				var str = 'Address: ' + data.output.intent['address1'] + "\n";
				str += 'City: ' + data.output.intent['geo-city-gb'] + "\n";
				str += 'State: ' + data.output.intent['geo-state-us'] + "\n";
				str += 'Zip: ' + data.output.intent['zip-code'];

				$('textarea[name="intent"]').val(str);
				$('textarea[name="raw"]').val(data.output.raw);
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
