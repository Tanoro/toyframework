
$(document).ready(function() {

	$('#runDedupe').click(function(){
		LOCAL.runDedupe(this);
	});
});

var LOCAL = {

	runDedupe : function()
	{
		jQgreyoutOn();

		var runDialogue = drawDialogue({
			'dialogueID' : 'runDialogue',
			'title' : 'Running',
			'size' : 'small',
			'closeClass' : 'fadeDestroyDialogue',
			'body' : 'Please, wait while the address data is processed.'
		});

		$.post('/environment', {
			'do' : 'runDedupe',
			'backup' : $('input[name="backup"]:checked').val()
		},
		function(data)
		{
			if (!data.error)
			{
				// Next step
				LOCAL.standardizeAddress();
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},

	// Standardize addresses
	standardizeAddress : function()
	{
		$.post('/environment', {
			'do' : 'standardizeAddr'
		},
		function(data)
		{
			if (!data.error)
			{
				// Next step
				LOCAL.removeEmptyRows();
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},

	removeEmptyRows : function()
	{
		$.post('/environment', {
			'do' : 'removeEmptyRows'
		},
		function(data)
		{
			if (!data.error)
			{
				if (data.affected_rows && data.affected_rows > 0)
				{
					// We have more rows
					LOCAL.removeEmptyRows();
				}
				else
				{
					// Next step
					LOCAL.removeDupeAddresses();
				}
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},

	// Remove the duplicate addresses
	removeDupeAddresses : function()
	{
		$.post('/environment', {
			'do' : 'removeDupeAddresses'
		},
		function(data)
		{
			if (!data.error)
			{
				if (data.affected_rows && data.affected_rows > 0)
				{
					// We have more rows
					LOCAL.removeDupeAddresses();
				}
				else
				{
					// Next step
					LOCAL.removeDupePhone();
				}
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},

	// Remove the duplicate phone numbers
	removeDupePhone : function()
	{
		$.post('/environment', {
			'do' : 'removeDupePhone'
		},
		function(data)
		{
			if (!data.error)
			{
				if (data.affected_rows && data.affected_rows > 0)
				{
					// We have more rows
					LOCAL.removeDupePhone();
				}
				else
				{
					// We're finished
					$('#runDialogue').remove();

					// Show complete dialogue
					var done = 'Duplicate addresses deleted. ';
					done += 'Please, note that duplicate addresses were not deleted if their record contained unique ';
					done += 'phone numbers and/or e-mail addresses.';

					var dialogue = drawDialogue({
						'title' : 'Finished',
						'size' : 'small',
						'closeClass' : 'fadeDestroyDialogue',
						'body' : done
					});
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
