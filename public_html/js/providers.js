
$(document).ready(function() {

	$('.getProvider').click(function(){
		LOCAL.getProvider(this);
	});

	$('.delProvider').click(function(){
		LOCAL.delProviderConfirm(this);
	});
});

var LOCAL = {

	// Draw the edit provider form
	getProvider : function(buttonObj)
	{
		var id = $(buttonObj).attr('rid');

		jQgreyoutOn();

		$.post('/environment', {
			'do' : 'getProvider',
			'id' : id
		},
		function(data)
		{
			if (!data.error)
			{
				var str = '<table>';
				str += '<tbody>';
				str += '<tr>';
				str += '<td>Name</td>';
				str += '<td><input type="text" name="name" size="50" value="' + data.output.row.name + '" tabindex="1" /></td>';
				str += '</tr>';
				str += '<tr>';
				str += '<td>IP Address</td>';
				str += '<td><input type="text" name="ipaddress" size="50" value="' + data.output.row.ipaddress + '" placeholder="0.0.0.0" tabindex="2" /></td>';
				str += '</tr>';
				str += '<tr>';
				str += '<td>URL</td>';
				str += '<td><input type="text" name="url" size="50" value="' + data.output.row.url + '" placeholder="http://" tabindex="3" /></td>';
				str += '</tr>';
				str += '<tr valign="top">';
				str += '<td>Notes</td>';
				str += '<td><textarea name="notes" cols="40" rows="5" tabindex="4">' + data.output.row.notes + '</textarea></td>';
				str += '</tr>';
				str += '</tbody>';
				str += '</table>';

				// Draw the edit form in a dialogue
				var dialogue = drawDialogue({
					'dialogueID' : 'editProviderForm',
					'title' : 'Edit Provider',
					'size' : 'small',
					'closeClass' : 'fadeDestroyDialogue',
					'body' : str,
					'footer' : '<input type="button" rid="' + id + '" value="Save" class="lgButton saveProvider">'
				}, function() {

					$('.saveProvider').click(function(){
						LOCAL.saveProvider(this);
					});
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

	// Submit record for saving
	saveProvider : function(buttonObj)
	{
		var id = $(buttonObj).attr('rid');

		$.post('/environment', {
			'do' : 'saveProvider',
			'ipaddress' : $('#editProviderForm input[name="ipaddress"]').val(),
			'name' : $('#editProviderForm input[name="name"]').val(),
			'url' : $('#editProviderForm input[name="url"]').val(),
			'notes' : $('#editProviderForm textarea[name="notes"]').val(),
			'id' : id
		},
		function(data)
		{
			if (!data.error)
			{
				jQgreyoutOff();

				$('#editProviderForm').remove();

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

	// Show confirm delete dialogue
	delProviderConfirm : function(buttonObj)
	{
		var id = $(buttonObj).attr('rid');

		jQgreyoutOn();

		var dialogue = drawDialogue({
			'dialogueID' : 'delProviderConfirm',
			'title' : 'Confirm Delete',
			'clipart' : images.errorWarning.src,
			'size' : 'small',
			'closeClass' : 'fadeDestroyDialogue',
			'body' : 'This cannot be undone. Are you sure?',
			'footer' : '<input type="button" name="delProvider" rid="' + id + '" value="Delete" class="lgButton">'
		}, function() {

			$('#delProviderConfirm input[name="delProvider"]').click(function(){
				LOCAL.delProvider(this);
			});
		});
	},

	// Execute a delete
	delProvider : function(buttonObj)
	{
		var id = $(buttonObj).attr('rid');

		$.post('/environment', {
			'do' : 'delProvider',
			'id' : id
		},
		function(data)
		{
			if (!data.error)
			{
				jQgreyoutOff();

				$('#delProviderConfirm').remove();

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
