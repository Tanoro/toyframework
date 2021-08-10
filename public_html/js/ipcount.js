
$(document).ready(function() {

	$('#refreshCounters').click(function(){
		LOCAL.refreshCounters(this);
	});

});

var LOCAL = {

	// Refresh counters
	refreshCounters : function(buttonObj)
	{
		$(buttonObj).attr('disabled', 'disabled').val('Running...');

		$.post('/environment', {
				'do' : 'refreshCounters'
			},
			function(data)
			{
				if (!data.error)
				{
					$(buttonObj).removeAttr('disabled').val('Refresh Counters');
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
