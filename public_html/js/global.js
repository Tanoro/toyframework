
var GLOBAL = {

	// Read a page's GET URL variables and return them as an associative array.
	getUrlVars : function()
	{
		var vars = {}, hash;
		var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');

		for(var i = 0; i < hashes.length; i++)
		{
			hash = hashes[i].split('=');
			vars[hash[0]] = hash[1];
		}

		return vars;
	}
};