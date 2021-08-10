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
});