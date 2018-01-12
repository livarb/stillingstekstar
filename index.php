<?php
@include("settings.php");
?>
<!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>Søk i stillingstekstar frå NAV</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

    </head>
    <style>
		a {
			text-decoration: none;
		}    
		.highlight {
			background-color: yellow;
		}
		.stillingOverskrift {
			font-weight: bold;
		}

/*new css*/
body,html,*{
    box-sizing: border-box;
    padding: 0;
    margin: 0;
}
body{
    font-family: sans-serif;
    background-color: rgb(51,51,51);
    color: white;
}
body>p{
    background-color: black;
}
#searchResultStats{
    border-bottom: 1px solid white;
}
#searchResultStats,#searchForm{
    background-color: rgb(61,61,61);
    padding: 5px;
}
.highlight{
    background-color: inherit;
    color: yellow;
}
.searchString{
    font-weight: bold;
}
.stillingOverskrift{
    border-top: 1px dotted white;
    padding: 5px 10px;
}
.statistikkPeriode{
    color: pink;
}
.arbeidssted{
    text-decoration: underline;
}
.stillingTekst{
    padding: 5px 10px;
    margin-bottom: 10px;
}
a{
    text-decoration: underline;
    color: tomato;
}		
	</style>
    <body>
        <!--[if lte IE 9]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="https://browsehappy.com/">upgrade your browser</a> to improve your experience and security.</p>
        <![endif]-->

        <p>Viser treff på søkeord «<span class="searchString"></span>» i historiske stillingsutlysingstekstar <span id="yearsText"></span>. Viser maks <span class="maxNumEntries"></span> treff pr. år.</p>
        <p>Søk er sensitive på små og store bokstavar (gul markering i søkeresultata er ikkje det). Søk gir kun treff på heile ord ("data" gir ikkje treff på "dataforvaltning").</p>

        <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>

<form id="searchForm">
  <div>
    <label for="searchString">Søk i stillingsutlysingstekstar: </label>
    <input type="text" id="searchString" name="søkestreng">
    <button onclick="searchFromInput();">Søk</button>    
  </div>
</form>

		<div id="searchResultStats"></div>
        <div id="searchResults"></div>

<?php if (defined("ANALYTICS_ID") && (ANALYTICS_ID !== "")) { ?>
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo ANALYTICS_ID; ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', '<?php echo ANALYTICS_ID; ?>', { 'anonymize_ip': true });
</script>
<?php } ?>

        <script>
function strip(html) {
	var tmp = document.createElement("DIV");
	tmp.innerHTML = html;
	return tmp.textContent || tmp.innerText;
}

function outputResults(data, year) {
	var counter = 0;
	for (var i = 0; i < data.entries.length; i++) {
		var entry = data.entries[i];

		var str = entry.stillingsutlysning;
		var stillingsnr = entry.stillingsnummer;
		var periode = entry.statistikk_periode.slice(0, 4) + " " + entry.statistikk_periode.slice(4)

		// sjekkar om søkestreng er i resultatet
		// sidan datahotellet gir treff på fleire ord
		// e.g. søkestreng "åpne data" --> "åpne AND data"
		if (str.indexOf(searchString) == -1) {
			continue;
		}

		str = strip(str); // fjern HTML

		// Markering på søkestreng i teksten
		var pattern = new RegExp(searchString, 'gi');
		str = str.replace(pattern, function (match) {
		  return "<span class=\"highlight\">" + match + "</span>"  ;
		}
		 );

		$('#searchResults-' + year).append(
			"<p class=\"stillingOverskrift\" id=\"" 
			+ stillingsnr + "-overskrift"
			+ "\"><span class=\"statistikkPeriode\" title=\"statistikk periode (YYYY MM)\">" 
			+ periode
 			+ "</span> — <span title=\"stillingsnummer\">" 
 			+ stillingsnr
			+ "</span></p>\n"
			+ "<p class=\"stillingTekst\">" 
			+ str
			+ "</p>\n\n");

		// Fetch stillingsData
		fetchStillingData(entry.stillingsnummer, year);

		counter++;
		if (counter == maxNumEntries) {
			break;
		}
	}
	$("#searchResultStats-" + year).append(
		"<p>Søketreff for " + year + ": " 
		+ counter + "</p>\n");
}

function searchTexts() {
	$("#searchResultStats").html('');
	$("#searchResults").html('');

	var searchStringEncoded = encodeURI(searchString);
	$('.searchString').html(escapeHtml(searchString));	

	yearsToSearch.forEach(function(year) {
		var url = "https://hotell.difi.no/api/json/nav/stillingstekster/" + year + "?query=" + searchStringEncoded;

		$('#searchResults').append(
			"<div id=\"searchResults-" + year + "\"></div>");
		$('#searchResultStats').append(
			"<div id=\"searchResultStats-" + year + "\"></div>");

		$.getJSON(
			url, 
			(function(thisyear) {
				return function(data) {
					outputResults(data, thisyear);
				};
			}(year))
		);
	});
}

function searchFromInput() {
	searchString = $("#searchString").val();

	<?php if (defined("ANALYTICS_ID") && (ANALYTICS_ID !== "")) { ?>
	gtag('event', searchString, {
	  'event_category': 'search'
	  //,'event_label': labelName
	});
	<?php } ?>

	searchTexts();
}

function fetchStillingData(stillingsnummer, year) {
	var url = "https://hotell.difi.no/api/json/nav/ledige-stillinger/" 
		+ year 
		+ "?stillingsnummer=" 
		+ stillingsnummer;		
	$.getJSON( url, function( data ) {
		var stilling = data.entries[0];
		$("#" + stilling.stillingsnummer + "-overskrift").append(
			" — <span title=\"stillingstittel\">"
			+ stilling.stillingstittel
			+ "</span><br/>\n<span title=\"virksomhet navn\">" 
			+ stilling.virksomhet_navn
			+ "</span> — <span class=\"arbeidssted\" title=\"arbeidssted kommune\">"
			+ stilling.arbeidssted_kommune
			+ "</span> — <span title=\"yrke\">"
			+ stilling.yrke
			+ "</span>"
			);
	});	
}

// løysing oppgitt til å vere frå mustache.js
// https://stackoverflow.com/a/12034334
var entityMap = {
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#39;',
  '/': '&#x2F;',
  '`': '&#x60;',
  '=': '&#x3D;'
};

function escapeHtml (string) {
  return String(string).replace(/[&<>"'`=\/]/g, function (s) {
    return entityMap[s];
  });
}


// I siste del under blir script køyrt
// og globale variablar sett.

// Forhindre sida å laste inn på nytt når ein 
//  trykker på søkeknappen.
// https://stackoverflow.com/a/19454346
$("#searchForm").submit(function(e) {
    e.preventDefault();
});

// Maks antall viste datasett pr. år
// Er sett relativt lavt for å ikkje utløyse for mange API-kall
// Maks er 100 pga. paginering på datahotell-API-et
var maxNumEntries = 24;
$('.maxNumEntries').html(maxNumEntries);

// Hentar liste over år og køyrer resten av scriptet
var lookupURL = "https://hotell.difi.no/api/jsonp/nav/stillingstekster?callback=?";

// var yearsToSearch = ["2016", "2015"];
var yearsToSearch = [];
var searchString;
$.getJSON(lookupURL, function( data ) {
	console.log(data);
	data.forEach(function (dataset) {
		var location = dataset.location.split("/");
		var id = location[2];
		yearsToSearch.push(id);
	});

	yearsToSearch.sort((a, b) => a - b);
	yearsToSearch.reverse();

	$('#yearsText').text('(' + yearsToSearch.join(', ') + ')');

	// Utfører eksempel-søk.
	var exampleStrings = ['åpne data', 'informasjonsforvaltning', 'dataforvaltning', 'datadrevet', 'opne data', 'offentlige data'];
	searchString = exampleStrings[Math.floor(Math.random() * exampleStrings.length)];

	searchTexts();
});

<?php if (defined("ANALYTICS_ID") && (ANALYTICS_ID !== "")) { ?>
gtag('event', searchString, {
  'event_category': 'searchDefault'
  //,'event_label': labelName
});
<?php } ?>
        </script>

<p><a href="https://github.com/livarb/stillingstekstar">Kjeldekode</a> er tilgjengeleg på Github.</p>

<p><i>Denne webappen nyttar <a href="https://data.norge.no/organisasjoner/arbeids-og-velferdsetaten-nav">datasett «Utlysningstekster ledige stillinger meldt til NAV» og «Ledige stillinger meldt til NAV»</a> gjort tilgjengeleg av Arbeids- og velferdsetaten (NAV) under lisensen <a href="https://creativecommons.org/licenses/by/4.0/deed.no">CC-BY 4.0</a></i></p>

<?php if (defined("ANALYTICS_ID") && (ANALYTICS_ID !== "")) { ?>
<p>Nettsida brukar <a href="https://analytics.google.com">Google Analytics</a> for å statistikk på besøk og søketermer.</p>
<?php } ?>

    </body>
</html>