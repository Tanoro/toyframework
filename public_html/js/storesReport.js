
var ENV = {
	post : {
		'do' : null,
		'domain' : null,
		'IP' : null,
		'hostname' : null,
		'company_name' : null,
		'owner_name' : null,
		'owner_email' : null,
		'store_phone' : null,
		'store_email' : null,
		'manager_name' : null,
		'manager_email' : null,
		'whois_name' : null,
		'whois_phone' : null,
		'result' : null,
		'status' : null,
		'statusLock' : null,
		'regexDomain' : null,
		'scoreMin' : null,
		'scoreMax' : null,
		'brandScoreMin' : null,
		'brandScoreMax' : null,
		'excludeCampaigns' : null
	}
};

$(document).ready(function() {

	$('input#saveSearch').click(function(){
		LOCAL.saveSearch(this);
	});

	$('input[name="getReport"]').click(function(){
		LOCAL.getReport();
	});

	// When active is clicked, set to inactive
	$(document.body).on('click', 'span.setInactive', function(){
		LOCAL.setStatus(this, 2);
	});

	// When inactive is clicked, set to active
	$(document.body).on('click', 'span.setActive', function(){
		LOCAL.setStatus(this, 1);
	});

	$('input[name="setAllActive"]').click(function(){
		LOCAL.setAllActive();
	});

	$('input[name="setAllInactive"]').click(function(){
		LOCAL.setAllInactive();
	});

	$('input[name="delAll"]').click(function(){
		LOCAL.delAll();
	});

	$('img.delStore').click(function(){
		LOCAL.delStore(this);
	});

	$(document.body).on('click', 'img.unlockStatus', function(){
		LOCAL.setStatusLock(this, 1);
	});

	$(document.body).on('click', 'img.lockStatus', function(){
		LOCAL.setStatusLock(this, 2);
	});

	$('input[name="setAllLocked"]').click(function(){
		LOCAL.setAllLocked();
	});

	$('input[name="setAllUnlocked"]').click(function(){
		LOCAL.setAllUnlocked();
	});

	$('img.scrapeSite').click(function(){
		LOCAL.scrapeSite(this);
	});

	/* DEPRECATED!
	$('img.captchaSite').click(function(){
		LOCAL.captchaSite(this);
	});
	*/

	$('td.getContent').click(function(){
		LOCAL.getContent(this);
	});

	ENV.post.domain = $('input[name="domain"]').val();
	ENV.post.IP = $('input[name="IP"]').val();
	ENV.post.hostname = $('input[name="hostname"]').val();
	ENV.post.company_name = $('input[name="company_name"]').val();
	ENV.post.owner_name = $('input[name="owner_name"]').val();
	ENV.post.owner_email = $('input[name="owner_email"]').val();
	ENV.post.store_phone = $('input[name="store_phone"]').val();
	ENV.post.store_email = $('input[name="store_email"]').val();
	ENV.post.manager_name = $('input[name="manager_name"]').val();
	ENV.post.manager_email = $('input[name="manager_email"]').val();
	ENV.post.whois_name = $('input[name="whois_name"]').val();
	ENV.post.whois_phone = $('input[name="whois_phone"]').val();
	ENV.post.result = $('select[name="result"]').val();
	ENV.post.status = $('select[name="status"]').val();
	ENV.post.statusLock = $('select[name="statusLock"]').val();
	ENV.post.regexDomain = $('input[name="regexDomain"]').val();
	ENV.post.scoreMin = $('input[name="scoreMin"]').val();
	ENV.post.scoreMax = $('input[name="scoreMax"]').val();
	ENV.post.brandScoreMin = $('input[name="brandScoreMin"]').val();
	ENV.post.brandScoreMax = $('input[name="brandScoreMax"]').val();

	// The exclude campaigns selector is tricky
	var excludeCampaigns = $('select[name^="excludeCampaigns"] option:selected').map(function () {
		return $(this).val();
	}).get().join(',');

	ENV.post.excludeCampaigns = excludeCampaigns;
});

var LOCAL = {

	// Save search set
	saveSearch : function(buttonObj)
	{
		ENV.post.do = 'saveSearch';

		$.post('/environment', ENV.post,
		function(data)
		{
			if (!data.error)
			{
				$(buttonObj).val(data.output.n + ' Stores');
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},

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
					$(linkObj).html('Inactive').removeClass('setInactive').addClass('setActive');
				}
				else
				{
					// Setting status to active
					$(linkObj).html('Active').removeClass('setActive').addClass('setInactive');
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

	// Set all active
	setAllActive : function()
	{
		ENV.post.do = 'setAllActive';

		$.post('/environment', ENV.post,
		function(data)
		{
			if (!data.error)
			{
				$('#actionStatus').html('Action completed');
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},

	// Set all inactive
	setAllInactive : function()
	{
		ENV.post.do = 'setAllInactive';

		$.post('/environment', ENV.post,
		function(data)
		{
			if (!data.error)
			{
				$('#actionStatus').html('Action completed');
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},

	// Delete a single store record
	delStore : function(linkObj)
	{
		var id = $(linkObj).attr('rid');
		var tr = $(linkObj).parents('tr');

		$(tr).b

		$.post('/environment', {
			'do' : 'delStore',
			'id' : id
		},
		function(data)
		{
			if (!data.error)
			{
				// Delete the row
				$(tr).remove();
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},
	
	// Delete all selected records
	delAll : function()
	{
		// Busy mouse cursor
		$('body').css('cursor', 'progress');

		var throbber = drawThrobber();
		jQgreyoutOn();
		
		ENV.post.do = 'delAll';

		$.post('/environment', ENV.post,
		function(data)
		{
			$('#' + throbber).remove();
			jQgreyoutOff();

			// Reset mouse cursor
			$('body').css('cursor', 'auto');
			
			if (!data.error)
			{
				$('#actionStatus').html('Action completed');
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
					$(linkObj).attr('src', '/images/lock-icon.png').removeClass('lockStatus').addClass('unlockStatus');
				}
				else
				{
					// Setting status to unlocked
					$(linkObj).attr('src', '/images/unlock-icon.png').removeClass('unlockStatus').addClass('lockStatus');
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

	// Set all Locked
	setAllLocked : function()
	{
		ENV.post.do = 'setAllLocked';

		$.post('/environment', ENV.post,
		function(data)
		{
			if (!data.error)
			{
				$('#actionStatus').html('Action completed');
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},

	// Set all Locked
	setAllUnlocked : function()
	{
		ENV.post.do = 'setAllUnlocked';

		$.post('/environment', ENV.post,
		function(data)
		{
			if (!data.error)
			{
				$('#actionStatus').html('Action completed');
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},

	// Re-scrape and rescore a domain
	scrapeSite : function(linkObj)
	{
		var id = $(linkObj).attr('rid');
		var throbber = drawThrobber();

		jQgreyoutOn();

		$.post('/environment', {
			'do' : 'scrapeSite',
			'id' : id
		},
		function(data)
		{
			$('#' + throbber).remove();

			if (!data.error)
			{
				var str = data.output.pagesNum + ' pages scraped<br>';
				str += '<span style="font-size: 40px; color: green;">' + data.output.statusScore + '</span><br>';
				str += '+' + data.output.whiteScore + ', -' + data.output.blackScore;

				var dialogue = drawDialogue({
					'title' : 'Scrape Result',
					'size' : 'small',
					'closeClass' : 'fadeDestroyDialogue',
					'body' : str
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

	// Pop the site open in an iframe for manual cacheing
	/* DEPRECATED!
	captchaSite : function(linkObj)
	{
		var id = $(linkObj).attr('rid');

		$.post('/environment', {
			'do' : 'captchaSite',
			'id' : id
		},
		function(data)
		{
			if (!data.error)
			{
				if (!data.output.url)
				{
					var dialogue = drawErrorDialogue('An error occurred while connecting to the digital notary.');
					return false;
				}
				
				var winWidth = $(window).width();
				var dialogueWidth = (winWidth > 1024 ? 1024 : (winWidth * 0.8));

				var str = '<iframe id="captchaDiag" src="' + data.output.url + '" width="100%" frameborder="0" height="605" id="' + id + '"></iframe>';

				// Show the e-mail form
				var dialogue = drawDialogue({
					'dialogueID' : 'CaptchaDialogue',
					'title' : 'View Site',
					'greyout' : true,
					//'size' : 996,
					'size' : dialogueWidth,
					'closeClass' : 'fadeDestroyDialogue',
					'body' : str
				});
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error, {greyout: true, greyoutControl: true});
			}

		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},
	*/

	// Get the store record content and produce it in a popup
	getContent : function(linkObj)
	{
		var id = $(linkObj).attr('rid');
		var throbber = drawThrobber();

		jQgreyoutOn();

		$.post('/environment', {
			'do' : 'getContent',
			'id' : id
		},
		function(data)
		{
			$('#' + throbber).remove();

			if (!data.error)
			{
				var dialogue = drawDialogue({
					'title' : 'Content',
					'size' : 1200,
					'closeClass' : 'fadeDestroyDialogue',
					'body' : '<textarea style="width: 99%; min-height: 600px;">' + data.output.content + '</textarea>'
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
