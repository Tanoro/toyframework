
$(document).ready(function() {

	$('input.restoreBackup').click(function(){
		LOCAL.restoreBackup(this);
	});

	$('input.delBackup').click(function(){
		LOCAL.delBackupConfirm(this);
	});

	$('input[name="execMigrations"]').click(function(){
		LOCAL.execMigrations(this);
	});
});

var LOCAL = {

	// Restore a backup
	restoreBackup : function(buttonObj)
	{
		// Busy mouse cursor
		$('body').css('cursor', 'progress');

		var file = $(buttonObj).attr('row');

		$.post('/environment', {
			'do' : 'restoreBackup',
			'file' : file
		},
		function(data)
		{
			// Reset mouse cursor
			$('body').css('cursor', 'auto');

			if (!data.error)
			{
				jQgreyoutOn();
				var dialogue = drawDialogue({
					'title' : 'Backup Restored',
					'size' : 'small',
					'showClose' : false,
					'body' : 'The backup finished loading.'
				});
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
	delBackupConfirm : function(buttonObj)
	{
		var file = $(buttonObj).attr('row');

		jQgreyoutOn();

		var dialogue = drawDialogue({
			'dialogueID' : 'delBackupConfirm',
			'title' : 'Confirm Delete',
			'clipart' : images.errorWarning.src,
			'size' : 'small',
			'closeClass' : 'fadeDestroyDialogue',
			'body' : 'This cannot be undone. Are you sure?',
			'footer' : '<input type="button" name="delBackup" row="' + file + '" value="Delete" class="lgButton">'
		}, function() {

			$('#delBackupConfirm input[name="delBackup"]').click(function(){
				LOCAL.delBackup(this);
			});
		});
	},

	// Delete a backup
	delBackup : function(buttonObj)
	{
		// Busy mouse cursor
		$('body').css('cursor', 'progress');

		var file = $(buttonObj).attr('row');

		$.post('/environment', {
			'do' : 'delBackup',
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

	// Run database migrations
	execMigrations : function()
	{
		// Busy mouse cursor
		$('body').css('cursor', 'progress');

		$.post('/environment', {
			'do' : 'phinxMigrate'
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
