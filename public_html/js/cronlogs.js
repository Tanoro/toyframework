
$(document).ready(function() {

	// Check for cron errors
	LOCAL.getCronError();

	$('input.delCronlog').click(function(){
		LOCAL.delCronlogConfirm(this);
	});
});

var LOCAL = {

	// Show confirm delete dialogue
	delCronlogConfirm : function(buttonObj)
	{
		var file = $(buttonObj).attr('row');

		jQgreyoutOn();

		var dialogue = drawDialogue({
			'dialogueID' : 'delCronlogConfirm',
			'title' : 'Confirm Delete',
			'clipart' : images.errorWarning.src,
			'size' : 'small',
			'closeClass' : 'fadeDestroyDialogue',
			'body' : 'This cannot be undone. Are you sure?',
			'footer' : '<input type="button" name="delCronlog" row="' + file + '" value="Delete" class="lgButton">'
		}, function() {

			$('#delCronlogConfirm input[name="delCronlog"]').click(function(){
				LOCAL.delCronlog(this);
			});
		});
	},

	// Delete a backup
	delCronlog : function(buttonObj)
	{
		// Busy mouse cursor
		$('body').css('cursor', 'progress');

		var file = $(buttonObj).attr('row');

		$.post('/environment', {
			'do' : 'delCronlog',
			'file' : file
		},
		function(data)
		{
			// Reset mouse cursor
			$('body').css('cursor', 'auto');

			if (!data.error)
			{
				location.reload();
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},

	// Check for cron-related fatal errors
	getCronError : function(buttonObj)
	{
		$.post('/environment', {
			'do' : 'getCronError'
		},
		function(data)
		{
			if (!data.error)
			{
				if (data.output.content)
				{
					// Show an error
					var str = 'ERROR! A cron has thrown the following error and aborted: <br><br><strong>' + data.output.content + '</strong> ';
					str += '<br><br>Please, note this error for troubleshooting and stop the running scraper.';

					var dialogue = drawErrorDialogue(str);
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
