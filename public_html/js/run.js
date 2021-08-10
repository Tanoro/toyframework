
var helpDialogue = {
	"DomainScrape": "This scraper inputs search terms into Google and extracts a set of website domains with which to build store records. Must be run first!",
	"AddressScraper": "This scraper searches Google with a store domain and attempts to locate mailing addresses from the business listing which may appear in the margin.",
	"StatusScraper": "This scraper iterates all store records, navigating numerous keyworded pages (i.e. Brands, Product, Gallery, Shop) throughout the site. The content is then compared against the status_white_checks and status_black_checks to calculate a relevancy score.",
	"RenaissanceScraper": "",
	"DealerScraper": "",
	"RetailScraper": "",
	"MicroDScraper": "",
	"PhoneScraper": "A robust scraper that hits \"Contact\" and \"Location\" pages on the store domain and attempts to parse out phone numbers, e-mail addresses, mailing addresses, and company names.",
	"BingScraper": "As of #2183, this scraper inputs a formatted phone number into Bing and attempts to extract the correct company name, correcting all address records accordingly.",
	"MelissaScraper": "This scraper queries Melissa Data with phone numbers and mailing addresses. Melissa Data will return the phone number and mailing address, helping to pair the two, fill in what is missing, or checking for accuracy.",
	"GoogleAddressScraper": "This scraper queries Google with the full mailing address and attempts to extract company name and phone number.",
	"BingAddressScraper": "This scraper queries Bing with the mailing address and attempts to extract a proper company name and phone number.",
	"indexBrands": "More of a tool than a scraper, this tool compares cached content from store page hits against the brands list and updates the store with a brand score.",
	"ScrapeTest": "This is only a test scraper for trying out the scraper environment and functionality. No records are changed."
};

$(document).ready(function() {

	$('input.runEvent').click(function() {
		LOCAL.setRun(this);
	});

	$('button.stopEvent').click(function() {
		LOCAL.setStop(this);
	});

	$('button.getHelp').click(function() {
		LOCAL.getHelp(this);
	});
});

var LOCAL = {

	setRun : function(buttonObj)
	{
		$.post('/environment', {
			'do' : 'setRun',
			'file' : $(buttonObj).attr('file')
		},
		function(data)
		{
			if (!data.error)
			{
				// Setting status to run
				$(buttonObj).val('Queued...').unbind('click');
				$(buttonObj).next('button').find('img').removeClass('fade');
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},

	setStop : function(buttonObj)
	{
		$.post('/environment', {
			'do' : 'setStop',
			'file' : $(buttonObj).attr('file')
		},
		function(data)
		{
			if (!data.error)
			{
				// Setting status to run
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

	getHelp : function(buttonObj)
	{
		var i = $(buttonObj).attr('file');

		jQgreyoutOn();

		var dialogue = drawDialogue({
			'dialogueID' : 'getHelpDialogue',
			'title' : 'Help',
			'size' : 'small',
			'closeClass' : 'fadeDestroyDialogue',
			'body' : helpDialogue[ i ]
		});
	}
};
