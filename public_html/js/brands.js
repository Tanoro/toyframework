
$(document).ready(function() {

	$('.addBrand').click(function() {
		LOCAL.addBrand();
	});

	$('.editBrand').click(function() {
		LOCAL.editBrandDialogue(this);
	});

	$('.delBrand').click(function() {
		LOCAL.delBrandDialogue(this);
	});

	$('.toggleImport').click(function() {
		LOCAL.toggleImport(this);
	});
});

var LOCAL = {

	// Add a brand
	addBrand : function()
	{
		$.post('/environment', {
			'do' : 'addBrand',
			'addBrandTxt' : $('input[name="addBrandTxt"]').val()
		},
		function(data)
		{
			if (!data.error)
			{
				$('input[name="addBrandTxt"]').val('');
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

	// Show edit brand dialogue
	editBrandDialogue : function(imgObj)
	{
		jQgreyoutOn();

		var id = $(imgObj).attr('rid');

		$.post('/environment', {
			'do' : 'getBrand',
			'id' : id
		},
		function(data)
		{
			if (!data.error)
			{
				var dialogue = drawDialogue({
					'dialogueID' : 'editBrandDialogue',
					'title' : 'Edit Brand',
					'size' : 'small',
					'closeClass' : 'fadeDestroyDialogue',
					'body' : '<input type="text" name="editBrandTxt" value="' + data.output.name + '" size="50" />',
					'footer' : '<input type="button" name="editBrand" rid="' + id + '" value="Save" class="lgButton">'
				}, function() {

					$('#editBrandDialogue input[name="editBrand"]').click(function(){
						LOCAL.editBrand(this);
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

	// Execute a brand edit
	editBrand : function(inputObj)
	{
		$.post('/environment', {
			'do' : 'editBrand',
			'name' : $('input[name="editBrandTxt"]').val(),
			'id' : $(inputObj).attr('rid')
		},
		function(data)
		{
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

	// Delete a brand dialogue
	delBrandDialogue : function(imgObj)
	{
		var dialogue = drawDialogue({
			'dialogueID' : 'delBrandDialogue',
			'title' : 'Delete Brand',
			'clipart' : images.caution.src,
			'greyout' : true,
			'size' : 'small',
			'closeClass' : 'fadeDestroyDialogue',
			'body' : 'Are you sure?',
			'footer' : '<input type="button" name="delBrand" rid="' + $(imgObj).attr('rid') + '" value="Delete" class="lgButton">'
		}, function() {

			$('#editBrandDialogue input[name="delBrand"]').click(function(){
				LOCAL.delBrand(this);
			});
		});
	},

	// Delete a brand
	delBrand : function(inputObj)
	{
		$.post('/environment', {
			'do' : 'delBrand',
			'id' : $(inputObj).attr('rid')
		},
		function(data)
		{
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

	// Toggle brand import
	toggleImport : function(imgObj)
	{
		$.post('/environment', {
			'do' : 'toggleImport',
			'id' : $(imgObj).attr('rid')
		},
		function(data)
		{
			if (!data.error)
			{
				$(imgObj).attr('src', data.output.src);
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
