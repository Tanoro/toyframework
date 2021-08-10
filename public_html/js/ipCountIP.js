
$(document).ready(function() {

	// Toggle checklist checkboxes
	$('input[name="setChecks"]').click(function(){
		if ($(this).is(':checked'))
		{
			// Set all to checked
			$('table tbody input:checkbox').attr('checked', 'yes');
		}
		else
		{
			// Set all to unchecked
			$('table tbody input:checkbox').removeAttr('checked');
		}
	});

	$(document.body).on('click', 'span.setInactive', function(){
		LOCAL.setStatus(this, 2);
	});

	$(document.body).on('click', 'span.setActive', function(){
		LOCAL.setStatus(this, 1);
	});

	$('img.unlockStatus').click(function(){
		LOCAL.setStatusLock(this, 1);
	});

	$('img.lockStatus').click(function(){
		LOCAL.setStatusLock(this, 2);
	});
});

var LOCAL = {

	// Toggle the store status
	setStatus : function(linkObj, status)
	{
		var id = $(linkObj).attr('rid');

		$.post('/environment', {
			'do' : 'setStatus',
			'id' : id,
			'status' : status
		},
		function(data)
		{
			if (!data.error)
			{
				if (status == 2)
				{
					// Setting status to inactive
					$(linkObj).removeClass('setInactive').addClass('setActive').html('Inactive');
				}
				else
				{
					// Setting status to inactive
					$(linkObj).removeClass('setActive').addClass('setInactive').html('Active');
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

	// Set status lock
	setStatusLock : function(linkObj, statusLock)
	{
		var id = $(linkObj).attr('rid');

		$.post('/environment', {
			'do' : 'setStatusLock',
			'id' : id,
			'statusLock' : statusLock
		},
		function(data)
		{
			if (!data.error)
			{
				if (statusLock == 2)
				{
					// Setting status to locked
					$(linkObj).attr('src', '/images/lock-icon.png').removeClass('unlockStatus').addClass('lockStatus').unbind('click').click(function(){
						LOCAL.setStatusLock(this, 2);
					});
				}
				else
				{
					// Setting status to unlocked
					$(linkObj).attr('src', '/images/unlock-icon.png').removeClass('lockStatus').addClass('unlockStatus').unbind('click').click(function(){
						LOCAL.setStatusLock(this, 1);
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
