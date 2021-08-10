
var ENV = {
	map : null,
	markers : []
};

$(document).ready(function() {

	LOCAL.initMap();

	/*
	var options = {
		enableHighAccuracy: true,
		timeout: 5000,
		maximumAge: 0
	};

	navigator.geolocation.getCurrentPosition(loadMap, throwError, options);
	*/
});


var LOCAL = {
	initMap : function()
	{
		var pos = {lat: 32.513466, lng: -93.7485652};

		ENV.map = new google.maps.Map(document.getElementById("mapContainer"), {
			zoom: 13,
			center: pos, //center: new google.maps.LatLng(crd.latitude, crd.longitude),
			mapTypeId: google.maps.MapTypeId.HYBRID
		});

		// Prepopulate markers
		LOCAL.getStores(pos);

		// This event listener will get stores when the map is clicked.
		ENV.map.addListener('click', function(event) {
			LOCAL.getStores({lat: event.latLng.lat(), lng: event.latLng.lng()});
		});
	},

	// Get nearby stores from coordinates and load map markers
	getStores : function(pos)
	{
		// Busy mouse cursor
		$('body').css('cursor', 'progress');

		// Erase existing markers
		LOCAL.clearMarkers();

		$.post('/environment', {
			'do' : 'getStores',
			'lat' : pos.lat,
			'lon' : pos.lng,
			'brand' : $('select[name="brandsArr"]').val()
		},
		function(data)
		{
			if (!data.error)
			{
				var content = '';

				$.each(data.output.stores, function() {
					LOCAL.addMarker(this);

					content += '<tr valign="top">';
					content += '<td><a href="/editstore.php?id=' + this.storeid + '" target="_blank">' + this.domain + '</a></td>';
					content += '<td>' + this.company_name + '</td>';
					content += '<td>' + (this.contact_name ? this.contact_name : '') + '</td>';
					content += '<td>' + (this.store_email ? this.store_email : '') + '</td>';
					content += '<td>';
					content += this.address1 + '<br>';

					if (this.address2)
					{
						content += this.address2 + '<br>';
					}

					content += this.city + ', ' + this.state + '&nbsp;&nbsp;' + this.zip;
					content += '</td>';
					content += '<td>' + (this.store_phone ? this.store_phone : '') + '</td>';
					content += '<td align="center">' + this.fdate + '</td>';
					content += '</tr>';
				});

				$('table#tblAddresses tbody').html(content);

				// Reset mouse cursor
				$('body').css('cursor', 'auto');
			}
			else
			{
				var dialogue = drawErrorDialogue(data.error);
			}
		}, 'json').fail(function(data) {
			var dialogue = drawErrorDialogue('An Ajax error has occurred. Please, contact the website developers for assistance.', data.responseText);
		});
	},

	// Add a marker
	addMarker : function(p)
	{
		var infowindow = new google.maps.InfoWindow({
			content: '<strong>' + p.company_name + '</strong><hr>' + p.address1 + '<br>' + p.city + ', ' + p.state + ' &nbsp;' + p.zip
		});

		var marker = new google.maps.Marker({
			position: new google.maps.LatLng(p.lat, p.lng),
			title: p.company_name,
			map: ENV.map
		});

		marker.addListener('click', function() {
			infowindow.open(ENV.map, marker);
		});

		// Add this marker to a global array so it can be removed later if needed
		ENV.markers.push(marker);
	},

	// Clear all markers
	clearMarkers : function()
	{
		if (ENV.markers.length == 0)
		{
			return;
		}

		for (var i = 0; i < ENV.markers.length; i++)
		{
			ENV.markers[i].setMap(null);
		}

		ENV.markers = [];
	}
};



// Throw geolocation error using standard dialogues
function throwError(err)
{
	var dialogue = drawErrorDialogue('(' + err.code + ') ' + err.message);

	// Load map without preselections
	var map = $('#googleMap').gmap({
		zoom: 13,
		center: new google.maps.LatLng(32.513466, -93.7485652),
		mapTypeId: google.maps.MapTypeId.HYBRID
	});
}

