
$(document).ready(function() {

	// Hide the checks
	$('.okCheck').hide();

	$('textarea.saveSetting').keyup(function(){
		LOCAL.saveSettings(this);
	});

	$('#addSettingForm input[name="addSetting"]').click(function(){
		LOCAL.addSetting(this);
	});
});

var LOCAL = {
	saveSettings : function(textObj)
	{
		var hook = $(textObj).attr('name');
		var ok = $('img[ok="' + hook + '"]');

		$.post('/environment', {
			'do' : 'saveSettings',
			'hook' : hook,
			'data' : $(textObj).val()
		},
		function(data)
		{
			if (data.error)
			{
				var dialogue = drawErrorDialogue(data.error);
			}

			// Show the check box
			$(ok).show();
			setTimeout(
				function()
				{
					$(ok).hide();
				}, 750
			);

		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},

	addSetting : function()
	{
		$.post('/environment', {
				'do' : 'addSetting',
				'setting' : $('#addSettingForm input[name="setting"]').val(),
				'descrip' : $('#addSettingForm input[name="descrip"]').val(),
				'hook' : $('#addSettingForm input[name="hook"]').val(),
				'data' : $('#addSettingForm *[name="data"]').val(),
			},
			function(data)
			{
				if (!data.error)
				{
					$('#addSettingForm input[name="setting"],#addSettingForm input[name="descrip"],#addSettingForm input[name="hook"]').val('');
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
