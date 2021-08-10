
var ENV = {
	_get : []
};

$(document).ready(function() {

	// Get store ID
	var arr = window.location.pathname.split('/');
	ENV._get['id'] = arr[ arr.length - 1 ];

	$('.addAddress').click(function() {
		LOCAL.addAddressDialogue();
	});

	// Edit button per row
	$(document.body).on('click', '.editAddress', function() {
		LOCAL.editAddress(this);
	});

	// Delete button per row
	$(document.body).on('click', '.delAddress', function() {
		LOCAL.delAddressConfirm(this);
	});

	$('input[name="addressPhoneScrape"]').click(function() {
		LOCAL.addressPhoneScrape();
	});

	$('td.editable').click(function() {
		LOCAL.insertEditable(this);
	});
});

// Make elements draggable
$(function() {
	// Make merge buttons draggable
	$("img.mergeAddress").draggable({
		containment: "table#tblAddresses tbody",
		revertDuration: 100,
		revert: function(event, ui) {
			// If you drop the merge handle within its own row, revert it back to its original position
			var handle = parseInt($(this).attr('rid'));
			var zone = parseInt($(event.context).find('img.mergeAddress').attr('rid'));

			$(this).data("uiDraggable").originalPosition = {
                top : 0,
                left : 0
            };
            // return boolean
            return ( handle == zone ? true : false );
        }
	});

	// Make table rows droppable
	$("table#tblAddresses tbody tr").droppable({
		drop: function(event, ui) {
			var sourceID = $(ui.draggable[0]).attr('rid');
			var destID = $(this).find('img.mergeAddress').attr('rid');

			if (sourceID != destID)
			{
				// Ready to merge records
				LOCAL.mergeAddress(sourceID, destID, this);

				// Remove the draggable and its parent row
				$(ui.draggable).parents('tr').remove();
			}
		}
	});
});

var LOCAL = {

	addAddressDialogue : function()
	{
		var str = '<table>';
		str += '<tbody>';
		str += '<tr>';
		str += '<td>Store Name</td>';
		str += '<td><input type="text" name="company_name" size="50" value="" /></td>';
		str += '</tr>';
		str += '<tr>';
		str += '<td>Address</td>';
		str += '<td><input type="text" name="address1" size="50" value="" /></td>';
		str += '</tr>';
		str += '<tr>';
		str += '<td>City</td>';
		str += '<td><input type="text" name="city" size="50" value="" /></td>';
		str += '</tr>';
		str += '<tr>';
		str += '<td>State</td>';
		str += '<td><input type="text" name="state" size="2" value="" /></td>';
		str += '</tr>';
		str += '<tr>';
		str += '<td>Zip</td>';
		str += '<td><input type="text" name="zip" size="10" value="" /></td>';
		str += '</tr>';
		str += '<tr>';
		str += '<td>Store Phone</td>';
		str += '<td><input type="text" name="store_phone" size="50" value="" /></td>';
		str += '</tr>';
		str += '<tr>';
		str += '<td>Store Fax Phone</td>';
		str += '<td><input type="text" name="store_fax_num" size="50" value="" /></td>';
		str += '</tr>';
		str += '<tr>';
		str += '<td>Contact Name</td>';
		str += '<td><input type="text" name="contact_name" size="50" value="" /></td>';
		str += '</tr>';
		str += '<tr>';
		str += '<td>Store E-mail</td>';
		str += '<td><input type="text" name="store_email" size="50" value="" /></td>';
		str += '</tr>';
		str += '<tr>';
		str += '<td>Owner E-mail</td>';
		str += '<td><input type="text" name="owner_email" size="50" value="" /></td>';
		str += '</tr>';
		str += '<tr>';
		str += '<td>Manager E-mail</td>';
		str += '<td><input type="text" name="manager_email" size="50" value="" /></td>';
		str += '</tr>';
		str += '</tbody>';
		str += '</table>';

		jQgreyoutOn();

		// Draw the edit form in a dialogue
		var dialogue = drawDialogue({
			'dialogueID' : 'addAddressForm',
			'title' : 'Add Address',
			'size' : 'small',
			'closeClass' : 'fadeDestroyDialogue',
			'body' : str,
			'footer' : '<input type="button" rid="' + ENV._get['id'] + '" value="Save" class="lgButton saveAddress">'
		}, function() {

			$('.saveAddress').click(function(){
				LOCAL.addAddress();
			});
		});
	},

	// Add a new address
	addAddress : function()
	{
		var post = {
			company_name : $('#addAddressForm input[name="company_name"]').val(),
			address1 : $('#addAddressForm input[name="address1"]').val(),
			city : $('#addAddressForm input[name="city"]').val(),
			state : $('#addAddressForm input[name="state"]').val(),
			zip : $('#addAddressForm input[name="zip"]').val(),
			store_phone : $('#addAddressForm input[name="store_phone"]').val(),
			store_fax_num : $('#addAddressForm input[name="store_fax_num"]').val(),
			contact_name : $('#addAddressForm input[name="contact_name"]').val(),
			store_email : $('#addAddressForm input[name="store_email"]').val(),
			owner_email : $('#addAddressForm input[name="owner_email"]').val(),
			manager_email : $('#addAddressForm input[name="manager_email"]').val()
		};

		$.post('/environment', {
			'do' : 'addAddress',
			'storeid' : ENV._get['id'],
			'company_name' : post.company_name,
			'address1' : post.address1,
			'city' : post.city,
			'state' : post.state,
			'zip' : post.zip,
			'store_phone' : post.store_phone,
			'store_fax_num' : post.store_fax_num,
			'contact_name' : post.contact_name,
			'store_email' : post.store_email,
			'owner_email' : post.owner_email,
			'manager_email' : post.manager_email
		},
		function(data)
		{
			if (!data.error)
			{
				$('#addAddressForm').remove();

				jQgreyoutOff();

				var phone = post.store_phone + '<br>' + (post.store_fax_num ? 'Fax: ' + post.store_fax_num : '');
				//var contact = post.contact_name + (post.store_email ? '<br><span style="float: right;">' + post.store_email + '</span>' : '');

				// Assemble the tr
				var tr = '<tr valign="top" row="' + data.output.insert_id + '">';
				tr += '<td align="center">';
				tr += '<img src="/images/edit-icon.png" class="pseudobutton editAddress" rid="' + data.output.insert_id + '" /> ';
				tr += '<img src="/images/delete-icon.png" class="pseudobutton delAddress" rid="' + data.output.insert_id + '" />';
				tr += '<img src="/images/merge-icon.png" class="pseudobutton mergeAddress" rid="' + data.output.insert_id + '" />';
				tr += '</td>';
				tr += '<td>' + post.company_name + '</td>';
				tr += '<td>' + post.contact_name + '</td>';
				tr += '<td>' + post.store_email + '</td>';
				tr += '<td>' + post.address1 + '</td>';
				tr += '<td>' + post.address2 + '</td>';
				tr += '<td>' + post.city + '</td>';
				tr += '<td>' + post.state + '</td>';
				tr += '<td>' + post.zip + '</td>';
				tr += '<td>' + phone + '</td>';
				tr += '<td align="center">' + data.output.dateStr + '<br>Manual</td>';
				tr += '<td>Unverified</td>';
				tr += '</tr>';
				$('#tblAddresses tbody').append(tr);
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},

	// Display the edit address form
	editAddress : function(buttonObj)
	{
		var id = $(buttonObj).attr('rid');

		jQgreyoutOn();

		$.post('/environment', {
			'do' : 'getAddress',
			'id' : id
		},
		function(data)
		{
			if (!data.error)
			{
				var str = '<table>';
				str += '<tbody>';
				str += '<tr>';
				str += '<td>Store Name</td>';
				str += '<td><input type="text" name="company_name" size="50" value="' + data.output.row.company_name + '" /></td>';
				str += '</tr>';
				str += '<tr>';
				str += '<td>Address</td>';
				str += '<td><input type="text" name="address1" size="50" value="' + data.output.row.address1 + '" /></td>';
				str += '</tr>';
				str += '<tr>';
				str += '<td>City</td>';
				str += '<td><input type="text" name="city" size="50" value="' + data.output.row.city + '" /></td>';
				str += '</tr>';
				str += '<tr>';
				str += '<td>State</td>';
				str += '<td><input type="text" name="state" size="2" value="' + data.output.row.state + '" /></td>';
				str += '</tr>';
				str += '<tr>';
				str += '<td>Zip</td>';
				str += '<td><input type="text" name="zip" size="10" value="' + data.output.row.zip + '" /></td>';
				str += '</tr>';
				str += '<tr>';
				str += '<td>Store Phone</td>';
				str += '<td><input type="text" name="store_phone" size="50" value="' + data.output.row.store_phone + '" /></td>';
				str += '</tr>';
				str += '<tr>';
				str += '<td>Store Fax Phone</td>';
				str += '<td><input type="text" name="store_fax_num" size="50" value="' + data.output.row.store_fax_num + '" /></td>';
				str += '</tr>';
				str += '<tr>';
				str += '<td>Contact Name</td>';
				str += '<td><input type="text" name="contact_name" size="50" value="' + data.output.row.contact_name + '" /></td>';
				str += '</tr>';
				str += '<tr>';
				str += '<td>Store E-mail</td>';
				str += '<td><input type="text" name="store_email" size="50" value="' + data.output.row.store_email + '" /></td>';
				str += '</tr>';
				str += '<tr>';
				str += '<td>Owner E-mail</td>';
				str += '<td><input type="text" name="owner_email" size="50" value="' + data.output.row.owner_email + '" /></td>';
				str += '</tr>';
				str += '<tr>';
				str += '<td>Manager E-mail</td>';
				str += '<td><input type="text" name="manager_email" size="50" value="' + data.output.row.manager_email + '" /></td>';
				str += '</tr>';
				str += '</tbody>';
				str += '</table>';

				// Draw the edit form in a dialogue
				var dialogue = drawDialogue({
					'dialogueID' : 'editAddressForm',
					'title' : 'Edit Address',
					'size' : 'small',
					'closeClass' : 'fadeDestroyDialogue',
					'body' : str,
					'footer' : '<input type="button" rid="' + id + '" value="Save" class="lgButton saveAddress">'
				}, function() {

					$('.saveAddress').click(function() {
						LOCAL.saveAddress(this);
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
	saveAddress : function(buttonObj)
	{
		var id = $(buttonObj).attr('rid');

		var post = {
			company_name : $('#editAddressForm input[name="company_name"]').val(),
			address1 : $('#editAddressForm input[name="address1"]').val(),
			city : $('#editAddressForm input[name="city"]').val(),
			state : $('#editAddressForm input[name="state"]').val(),
			zip : $('#editAddressForm input[name="zip"]').val(),
			store_phone : $('#editAddressForm input[name="store_phone"]').val(),
			store_fax_num : $('#editAddressForm input[name="store_fax_num"]').val(),
			contact_name : $('#editAddressForm input[name="contact_name"]').val(),
			store_email : $('#editAddressForm input[name="store_email"]').val(),
			owner_email : $('#addAddressForm input[name="owner_email"]').val(),
			manager_email : $('#addAddressForm input[name="manager_email"]').val()
		};

		$.post('/environment', {
			'do' : 'saveAddress',
			'company_name' : post.company_name,
			'address1' : post.address1,
			'city' : post.city,
			'state' : post.state,
			'zip' : post.zip,
			'store_phone' : post.store_phone,
			'store_fax_num' : post.store_fax_num,
			'contact_name' : post.contact_name,
			'store_email' : post.store_email,
			'owner_email' : post.owner_email,
			'manager_email' : post.manager_email,
			'id' : id
		},
		function(data)
		{
			if (!data.error)
			{
				jQgreyoutOff();

				$('#editAddressForm').remove();

				var phone = post.store_phone + '<br>' + (post.store_fax_num ? 'Fax: ' + post.store_fax_num + '<br>' : '');

				// Update the table row accordingly
				$('tr[row="' + id + '"]').find('td:eq(1)').html(post.company_name);
				$('tr[row="' + id + '"]').find('td:eq(2)').html(post.contact_name);
				$('tr[row="' + id + '"]').find('td:eq(3)').html(post.store_email);
				$('tr[row="' + id + '"]').find('td:eq(4)').html(post.address1);
				$('tr[row="' + id + '"]').find('td:eq(5)').html(post.address2);
				$('tr[row="' + id + '"]').find('td:eq(6)').html(post.city);
				$('tr[row="' + id + '"]').find('td:eq(7)').html(post.state);
				$('tr[row="' + id + '"]').find('td:eq(8)').html(post.zip);
				$('tr[row="' + id + '"]').find('td:eq(9)').html(phone);
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
	delAddressConfirm : function(buttonObj)
	{
		var id = $(buttonObj).attr('rid');

		jQgreyoutOn();

		var dialogue = drawDialogue({
			'dialogueID' : 'delAddressConfirm',
			'title' : 'Confirm Delete',
			'clipart' : '/js/dialogues/images/error-warning.png',
			'size' : 'small',
			'closeClass' : 'fadeDestroyDialogue',
			'body' : 'This cannot be undone. Are you sure?',
			'footer' : '<input type="button" name="delAddress" rid="' + id + '" value="Delete" class="lgButton">'
		}, function() {

			$('#delAddressConfirm input[name="delAddress"]').click(function(){
				LOCAL.delAddress(this);
			});
		});
	},

	// Execute a delete
	delAddress : function(buttonObj)
	{
		var id = $(buttonObj).attr('rid');

		$.post('/environment', {
			'do' : 'delAddress',
			'id' : id
		},
		function(data)
		{
			if (!data.error)
			{
				jQgreyoutOff();

				$('#delAddressConfirm').remove();
				$('tr[row="' + id + '"]').remove();
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},

	// Merge addresses
	mergeAddress : function(sourceID, destID, destRow)
	{
		$.post('/environment', {
			'do' : 'mergeAddresses',
			'sourceID' : sourceID,
			'destID' : destID
		},
		function(data)
		{
			if (!data.error)
			{
				// Update the destination row
				var result = data.output.result;
				$(destRow).find('td:eq(1)').html(result.company_name);
				$(destRow).find('td:eq(2)').html(result.contact_name);
				$(destRow).find('td:eq(3)').html(result.store_email);
				$(destRow).find('td:eq(4)').html(result.address1);
				$(destRow).find('td:eq(5)').html(result.address2);
				$(destRow).find('td:eq(6)').html(result.city);
				$(destRow).find('td:eq(7)').html(result.state);
				$(destRow).find('td:eq(8)').html(result.zip);
				$(destRow).find('td:eq(9)').html(result.store_phone);
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},

	// Scrape page
	addressPhoneScrape : function()
	{
		var throbber = drawThrobber();

		$.post('/environment', {
			'do' : 'addressPhoneScrape',
			'id' : ENV._get['id'],
			'url' : $('input[name="scrapeURL"]').val()
		},
		function(data)
		{
			$('#' + throbber).remove();

			if (!data.error)
			{
				//location.reload();
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var error = '<h2>' + data.status + ' - ' + data.statusText + '</h2>';
			error += 'An Ajax error has occurred. Please, contact the website developers for assistance.';
			var dialogue = drawErrorDialogue(error, data.responseText);
		});
	},

	// Create editable field
	insertEditable : function(td)
	{
		var content = $(td).html().trim();
		var size = parseInt(content.length);
		var sourceID = parseInt($(td).parents('tr').attr('row'));
		var name = $(td).attr('column');

		var input = $('<input />').attr({
			type: 'text',
			name: name,
			rid: sourceID,
			size: size,
			value: content
		});

		// Add keypress event to this element
		$(document.body).on('keypress', 'input[name="' + name + '"]', function(e) {
			if (e.which == 13)
			{
				LOCAL.saveField(this, td);
			}
		});

		$(td).html(input[0].outerHTML);
		$(td).find('input').select();

		// Disable the cick action on the TD elements until we are finished here
		$('td.editable').off('click');
	},

	// Save an address field
	saveField : function(textObj, td)
	{
		var sourceID = $(textObj).attr('rid');
		var field = $(textObj).attr('name');
		var content = $(textObj).val().trim();

		$.post('/environment', {
			'do' : 'saveField',
			'field' : field,
			'value' : content,
			'id' : sourceID
		},
		function(data)
		{
			if (!data.error)
			{
				// Return the interface to normal
				$('input[name="' + field + '"]').off('keypress');

				// Disengage the text field
				$(td).html(content);

				// Re-enable the editable event on all other editable content
				$('td.editable').click(function() {
					LOCAL.insertEditable(this);
				});
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
