
$(document).ready(function() {

	$('input[name="switchJob"]').click(function(){
		LOCAL.switchProfile(this);
	});

	$('input.delProfile').click(function(){
		LOCAL.delProfileConfirm(this);
	});
});

var LOCAL = {

	// Restore a backup
	switchProfile : function(inputObj)
	{
		jQgreyoutOn();

		var throbber = drawThrobber();

		// Busy mouse cursor
		$('body').css('cursor', 'progress');

		var profile = $(inputObj).val();

		$.post('/environment', {
			'do' : 'switchProfile',
			'profile' : profile
		},
		function(data)
		{
			// Reset mouse cursor
			$('body').css('cursor', 'auto');

			jQgreyoutOff();

			$('#' + throbber).remove();

			if (!data.error)
			{

			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},

	// Show confirm delete dialogue
	delProfileConfirm : function(buttonObj)
	{
		var hook = $(buttonObj).attr('hook');

		jQgreyoutOn();

		var dialogue = drawDialogue({
			'dialogueID' : 'delProfileConfirm',
			'title' : 'Confirm Delete',
			'clipart' : images.errorWarning.src,
			'size' : 'small',
			'closeClass' : 'fadeDestroyDialogue',
			'body' : 'This cannot be undone. Are you sure?',
			'footer' : '<input type="button" name="delProfile" hook="' + hook + '" value="Delete" class="lgButton">'
		}, function() {

			$('#delProfileConfirm input[name="delProfile"]').click(function(){
				LOCAL.delProfile(this);
			});
		});
	},

	// Delete a profile
	delProfile : function(buttonObj)
	{
		// Busy mouse cursor
		$('body').css('cursor', 'progress');

		$.post('/environment', {
			'do' : 'delProfile',
			'hook' : $(buttonObj).attr('hook')
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
	}
};
