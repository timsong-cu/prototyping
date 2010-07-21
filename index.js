var table_count = 0;
var table_upload = false;
var table_raw = false;
var currenttable = "";
var table_default = [
[0, 0],
[1, 0.095775462963],
[2, 0.320770135864],
[3, 0.524652957916],
[4, 0.672666705555],
[5, 0.7734375],
[6, 0.84108134526],
[7, 0.886710293826],
[8, 0.917876545194],
[9, 0.939494232162],
[10, 0.954732510288],
[11, 0.965645838529],
[12, 0.973581273925],
[13, 0.979434477244],
[14, 0.983809897585],
[15, 0.987121582031],
[16, 0.989657296365],
[17, 0.991619837273]
];
var table_default_raw = '';

function doplot(){
	// Fairly straightforward. Plotapi does the actual plotting, as usual.
	var distribution = get_value("distribution");
	var action = get_value("action");
	var url = "index.php?action=plot&function=" + distribution +"-" + action;
	var xfrom = get_value("xfrom");
	var xto = get_value("xto");
	var yfrom = get_value("yfrom");
	var yto = get_value("yto");
	var step = get_value("step");
	
	if(action == "power"){
		var minreads = get_value("minreads");
		url += "&minreads=" + encodeURIComponent(minreads);
	}
	else if(action == "distribution"){
		var mean = get_value("mean");
		url += "&mean=" + encodeURIComponent(mean);
	}
	else if(action == "mincarrier"){
		var minreads = get_value("minreads");
		var controls = get_value("controls");
		var budget = get_value("budget");
		var overhead = get_value("overhead");
		var sequencecost = get_value("sequencecost");
		var cutoff = get_value("cutoff");
		url += "&minreads=" + encodeURIComponent(minreads) + "&controls=" + encodeURIComponent(controls)
			+ "&cutoff=" + encodeURIComponent(cutoff) + "&budget=" + encodeURIComponent(budget) 
			+ "&overhead=" + encodeURIComponent(overhead) + "&sequencecost=" + encodeURIComponent(sequencecost);
	}
	else if(action == "power-from-case-frequency"){
		var minreads = get_value("minreads");
		var controls = get_value("controls");
		var budget = get_value("budget");
		var overhead = get_value("overhead");
		var sequencecost = get_value("sequencecost");
		var cutoff = get_value("cutoff");
		var frequency = get_value("frequency");
		url += "&minreads=" + encodeURIComponent(minreads) + "&controls=" + encodeURIComponent(controls)
			+ "&cutoff=" + encodeURIComponent(cutoff) + "&budget=" + encodeURIComponent(budget) 
			+ "&overhead=" + encodeURIComponent(overhead) + "&sequencecost=" + encodeURIComponent(sequencecost);
		url += "&frequency=" + encodeURIComponent(frequency);
	}
	else if(action == "power-from-control-frequency"){
		var minreads = get_value("minreads");
		var controls = get_value("controls");
		var budget = get_value("budget");
		var overhead = get_value("overhead");
		var sequencecost = get_value("sequencecost");
		var cutoff = get_value("cutoff");
		var frequency = get_value("frequency");
		var oddsratio = get_value("oddsratio");
		url += "&minreads=" + encodeURIComponent(minreads) + "&controls=" + encodeURIComponent(controls)
			+ "&cutoff=" + encodeURIComponent(cutoff) + "&budget=" + encodeURIComponent(budget) 
			+ "&overhead=" + encodeURIComponent(overhead) + "&sequencecost=" + encodeURIComponent(sequencecost);
		url += "&frequency=" + encodeURIComponent(frequency) + "&oddsratio=" + oddsratio;
	}
	else if(action == "mincarrier-both"){
		var minreads = get_value("minreads");
		var ratio = get_value("ratio");
		var budget = get_value("budget");
		var overhead = get_value("overhead");
		var sequencecost = get_value("sequencecost");
		var cutoff = get_value("cutoff");
		url += "&minreads=" + encodeURIComponent(minreads) + "&ratio=" + encodeURIComponent(ratio)
			+ "&cutoff=" + encodeURIComponent(cutoff) + "&budget=" + encodeURIComponent(budget) 
			+ "&overhead=" + encodeURIComponent(overhead) + "&sequencecost=" + encodeURIComponent(sequencecost);
	}
	else if(action == "power-from-case-frequency-both"){
		var minreads = get_value("minreads");
		var ratio = get_value("ratio");
		var budget = get_value("budget");
		var overhead = get_value("overhead");
		var sequencecost = get_value("sequencecost");
		var cutoff = get_value("cutoff");
		var frequency = get_value("frequency");
		url += "&minreads=" + encodeURIComponent(minreads) + "&ratio=" + encodeURIComponent(ratio)
			+ "&cutoff=" + encodeURIComponent(cutoff) + "&budget=" + encodeURIComponent(budget) 
			+ "&overhead=" + encodeURIComponent(overhead) + "&sequencecost=" + encodeURIComponent(sequencecost);
		url += "&frequency=" + encodeURIComponent(frequency);
	}
	else if(action == "power-from-control-frequency-both"){
		var minreads = get_value("minreads");
		var ratio = get_value("ratio");
		var budget = get_value("budget");
		var overhead = get_value("overhead");
		var sequencecost = get_value("sequencecost");
		var cutoff = get_value("cutoff");
		var frequency = get_value("frequency");
		var oddsratio = get_value("oddsratio");
		url += "&minreads=" + encodeURIComponent(minreads) + "&ratio=" + encodeURIComponent(ratio)
			+ "&cutoff=" + encodeURIComponent(cutoff) + "&budget=" + encodeURIComponent(budget) 
			+ "&overhead=" + encodeURIComponent(overhead) + "&sequencecost=" + encodeURIComponent(sequencecost);
		url += "&frequency=" + encodeURIComponent(frequency) + "&oddsratio=" + oddsratio;

	}
	if(distribution == "negativebinomial"){
		var size = get_value("size");
		url += "&size=" + encodeURIComponent(size);
	}
	else if(distribution == 'table'){
		// upload the table by POST first.
		var posttext = "table=";
		if(table_raw){
			if(get_value('tableraw') == '')
				posttext += encodeURIComponent(table_default_raw);
			else
				posttext += encodeURIComponent(get_value('tableraw')); 
		}
		else
			posttext += encodeURIComponent(gettablecontent());
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.open("POST","index.php?action=tableupload",false);
		xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xmlhttp.setRequestHeader("Content-length", posttext.length);
		xmlhttp.setRequestHeader("Connection", "close");
		xmlhttp.send(posttext);
		var ret = eval('(' + xmlhttp.responseText + ')');
		url += "&usesavedtable=" + encodeURIComponent(ret['token']);
	}
	if(xfrom != "")
		url+= "&xfrom=" + encodeURIComponent(xfrom);
	if(xto != "")
		url+= "&xto=" + encodeURIComponent(xto);
	if(yfrom != "")
		url+= "&yfrom=" + encodeURIComponent(yfrom);
	if(yto != "")
		url+= "&yto=" + encodeURIComponent(yto);
	if(step != "")
		url+= "&step=" + encodeURIComponent(step);
	
	document.getElementById("Plotpending").innerHTML = 
		'<img src="loading.gif" alt="" />';
	document.getElementById("Plot").innerHTML = 
		'<img src="' + url +'" alt="" onload="plot_onload()" />';
	document.getElementById("Plotlink").innerHTML = 
		'<a href="' + url.replace("action=plot", "action=data")  +'">Download data</a>';
}

function plot_onload(){
	document.getElementById("Plotpending").innerHTML = 
		'';
}
function get_value(id){
	if(document.getElementById(id) == null || document.getElementById(id).className == 'hint') return '';
	else return document.getElementById(id).value;
}
function onactionchange(){
	var action = get_value("action");
	var distribution = get_value("distribution"); 
	var newhtml = '';
	if(action == "distribution"){
			newhtml = generate_input_tag("mean", "Average depth-coverage: ");
	}
	else {
		if(distribution != 'table')
			newhtml = generate_input_tag("minreads", "Minimum number of reads required: ");
	    if (action == "mincarrier"){
			newhtml += 
				generate_input_tag("controls", "Number of controls: ") +
				generate_input_tag("budget", "Budget (if more than one, separate by space or comma): $") +
				generate_input_tag("overhead", "Overhead cost per sample: $") +
				generate_input_tag("sequencecost", "Cost of sequencing per 1X diploid genome: $") +
				generate_input_tag("cutoff", "Cutoff for p-value: 1E");
		}
		else if (action == "mincarrier-both"){
			newhtml +=  
				generate_input_tag("ratio", "Ratio of all samples to cases: ") +
				generate_input_tag("budget", "Budget (if more than one, separate by space or comma): $") +
				generate_input_tag("overhead", "Overhead cost per sample: $") +
				generate_input_tag("sequencecost", "Cost of sequencing per 1X diploid genome: $") +
				generate_input_tag("cutoff", "Cutoff for p-value: 1E");
		}
		else if (action == "power-from-case-frequency"){
			newhtml +=  
				generate_input_tag("controls", "Number of controls: ") +
				generate_input_tag("budget", "Budget (if more than one, separate by space or comma): $") +
				generate_input_tag("overhead", "Overhead cost per sample: $") +
				generate_input_tag("sequencecost", "Cost of sequencing per 1X diploid genome: $") +
				generate_input_tag("cutoff", "Cutoff for p-value: 1E") +
				generate_input_tag("frequency","Frequency of variant in cases (if more than one, separate by space or comma): "); 
		}
		else if (action == "power-from-control-frequency"){
			newhtml +=  
				generate_input_tag("controls", "Number of controls: ") +
				generate_input_tag("budget", "Budget (if more than one, separate by space or comma): $") +
				generate_input_tag("overhead", "Overhead cost per sample: $") +
				generate_input_tag("sequencecost", "Cost of sequencing per 1X diploid genome: $") +
				generate_input_tag("cutoff", "Cutoff for p-value: 1E") +
				generate_input_tag("frequency","Frequency of variant in controls (if more than one, separate by space or comma): ") +
				generate_input_tag("oddsratio", "Odds ratio: ");
		}
	
		else if (action == "power-from-case-frequency-both"){
			newhtml +=  
				generate_input_tag("ratio", "Ratio of all samples to cases: ") +
				generate_input_tag("budget", "Budget (if more than one, separate by space or comma): $") +
				generate_input_tag("overhead", "Overhead cost per sample: $") +
				generate_input_tag("sequencecost", "Cost of sequencing per 1X diploid genome: $") +
				generate_input_tag("cutoff", "Cutoff for p-value: 1E") +
				generate_input_tag("frequency","Frequency of variant in cases (if more than one, separate by space or comma): "); 
		}
		else if (action == "power-from-control-frequency-both"){
			newhtml +=  
				generate_input_tag("ratio", "Ratio of all samples to cases: ") +
				generate_input_tag("budget", "Budget (if more than one, separate by space or comma): $") +
				generate_input_tag("overhead", "Overhead cost per sample: $") +
				generate_input_tag("sequencecost", "Cost of sequencing per 1X diploid genome: $") +
				generate_input_tag("cutoff", "Cutoff for p-value: 1E") +
				generate_input_tag("frequency","Frequency of variant in controls (if more than one, separate by space or comma): ") +
				generate_input_tag("oddsratio", "Odds ratio: ");
		}
	}
	document.getElementById("actionparams").innerHTML = newhtml;
}

function ondistributionchange(){
	var distribution = get_value("distribution");
	if(distribution == "poisson"){
		document.getElementById("distributionparams").innerHTML = "";
		document.getElementById("opt_distribution").disabled = '';
	}
	else if(distribution == "negativebinomial"){
		document.getElementById("distributionparams").innerHTML = generate_input_tag("size", "Dispersion parameter: ");
		document.getElementById("opt_distribution").disabled = '';
	}
	else if(distribution == "table"){
		table_count = 10;
		table_raw = false;
		var table_html = '<input type="button" onclick="switchtable()" value="Enter raw table content"/><br/>';
		table_html += '<table id="table"><tr><th>Average depth-coverage</th><th>Power</th></tr>';
		for(var i = 1; i <= table_count; i++){
			table_html += '<tr><td><input type="text" id="table_' + i + '_0" class="hint" onfocus="table_cell_onfocus()" onblur="table_cell_onblur()" value="'+ table_default[i-1][0] + '"/></td>'
			    + '<td><input type="text" id="table_' + i + '_1" class="hint" onfocus="table_cell_onfocus()" onblur="table_cell_onblur()" value="'+ table_default[i-1][1] + '"/></td></tr>';
		}
		table_html += "</table>";
		table_html += '<br/> <input type="button" onclick="moretable()" value="More" />';
		document.getElementById("distributionparams").innerHTML = table_html;
		document.getElementById("opt_distribution").disabled = 'disabled';
		if(get_value('action') == 'distribution'){
			document.getElementById('action').value = 'power';
		}
	}
	onactionchange();
}

function switchtable(){
	if(table_raw == false){
		var newhtml = '<input type="button" onclick="switchtable()" value="Enter table content in cells"/><br>' +
		'<label for="tableraw">Enter the content of the table, in X, Y coordinate pairs, separated by any non-numeric character:</label>';
		if(showtablehint()){
			// use default values
			if(table_default_raw == ''){
				for(var i = 0; i < table_default.length; i++)
					table_default_raw += table_default[i][0] + "\t" + table_default[i][1] + "\n";
			}
			newhtml += '<br/> <textarea rows="15" cols="100" class="hint" onfocus="table_raw_onfocus()" onblur="table_raw_onblur()" id="tableraw" name="tableraw">' + table_default_raw + '</textarea>'; 
		}
		else{
			var newtext = '';
			for(var i = 1; i<= table_count; i++){
				var x = get_value('table_' + i + '_0');
				var y = get_value('table_' + i + '_1');
				if(x != '' && y != ''){
					newtext += x + '\t' + y + '\n';
				}
			}
			newhtml += '<br/> <textarea rows="15" cols="100" onfocus="table_raw_onfocus()" onblur="table_raw_onblur()" id="tableraw" name="tableraw">' + newtext + '</textarea>';
		}
		document.getElementById("distributionparams").innerHTML = newhtml;
		table_raw = true;
	}
	else{
		var table_text = get_value('tableraw');
		var table_html = '<input type="button" onclick="switchtable()" value="Enter raw table content"/><br/>';
		table_html += '<table id="table"><tr><th>Average depth-coverage</th><th>Power</th></tr>';
		if(table_text != ''){
			table_split =table_text.split(/[^\d\.]+/g);
			table_count = table_split.length / 2 > 10 ? table_split.length / 2 : 10;
			for(var i = 1; i <= table_count; i++){
				if(2 * i - 2 < table_split.length)
					table_html += '<tr><td><input type="text" id="table_' + i + '_0" onfocus="table_cell_onfocus()" onblur="table_cell_onblur()" value="'+ table_split[2 * i - 2] + '"/></td>';
				else
					table_html += '<tr><td><input type="text" id="table_' + i + '_0" onfocus="table_cell_onfocus()" onblur="table_cell_onblur()" value=""/></td>';
				if(2 * i - 1 < table_split.length)
					table_html += '<td><input type="text" id="table_' + i + '_1" onfocus="table_cell_onfocus()" onblur="table_cell_onblur()" value="'+ table_split[2 * i - 1] + '"/></td></tr>';
				else
					table_html += '<td><input type="text" id="table_' + i + '_1" onfocus="table_cell_onfocus()" onblur="table_cell_onblur()" value=""/></td></tr>';
				
			}
		}
		table_html += "</table>";
		table_html += '<br/> <input type="button" onclick="moretable()" value="More" />';
		document.getElementById("distributionparams").innerHTML = table_html;
		table_cell_onblur(); // set default values as necessary;
		table_raw = false;
	}
}

function table_raw_onfocus(){
	if(document.getElementById("tableraw").className == 'hint'){
		document.getElementById("tableraw").className = '';
		document.getElementById("tableraw").value = '';
	}
}

function table_raw_onblur(){
	if(document.getElementById("tableraw").value == ''){
		document.getElementById("tableraw").className = 'hint';
		document.getElementById("tableraw").value = table_default_raw;
	}
}

function moretable(){
	var table_html = '<input type="button" onclick="switchtable()" value="Enter raw table content"/><br/>';
	table_html += '<table id="table"><tr><th>Average depth-coverage</th><th>Power</th></tr>';
	if(!showtablehint()){
		table_count ++;
		for(var i = 1; i <= table_count; i++)
			table_html += '<tr><td><input type="text" id="table_' + i + '_0" onfocus="table_cell_onfocus()" onblur="table_cell_onblur()" value="'+ get_value("table_" + i + "_0") + '" />'
			+ '</td><td><input type="text" id="table_' + i + '_1" onfocus="table_cell_onfocus()" onblur="table_cell_onblur()" value="'+ get_value("table_" + i + "_1") + '" /></td></tr>';
	}
	else {
		table_count ++;
		for(var i = 1; i <= table_count; i++){
			if(i <= table_default.length){
				table_html += '<tr><td><input type="text" id="table_' + i + '_0" class="hint" onfocus="table_cell_onfocus()" onblur="table_cell_onblur()" value="'+ table_default[i-1][0] + '"/></td>'
				    + '<td><input type="text" id="table_' + i + '_1" class="hint" onfocus="table_cell_onfocus()" onblur="table_cell_onblur()" value="'+ table_default[i-1][1] + '"/></td></tr>';
			}
			else{
				table_html += '<tr><td><input type="text" id="table_' + i + '_0" class="hint" onfocus="table_cell_onfocus()" onblur="table_cell_onblur()" /></td>'
					+ '<td><input type="text" id="table_' + i + '_1" class="hint" onfocus="table_cell_onfocus()" onblur="table_cell_onblur()"/></td></tr>';
			}
		}
	}
	table_html += "</table>";
	table_html += '<br/> <input type="button" onclick="moretable()" value="More" />';
	document.getElementById("distributionparams").innerHTML = table_html;
}

function table_cell_onfocus(){
	for(var i = 1; i <= table_count; i++){
		if(document.getElementById("table_" + i + "_0").className == 'hint'){
			document.getElementById("table_" + i + "_0").className = '';
			document.getElementById("table_" + i + "_0").value = '';
		}
		if(document.getElementById("table_" + i + "_1").className == 'hint'){
			document.getElementById("table_" + i + "_1").className = '';
			document.getElementById("table_" + i + "_1").value = '';
		}
	}
}

function table_cell_onblur(){
	if(showtablehint()){
		for(var i = 1; i <= table_count && i < table_default.length; i++){
			document.getElementById("table_" + i + "_0").className = 'hint';
			document.getElementById("table_" + i + "_0").value = table_default[i-1][0];
			document.getElementById("table_" + i + "_1").className = 'hint';
			document.getElementById("table_" + i + "_1").value = table_default[i-1][1];
		}
	}
}

function showtablehint(){
	for(var i = 1; i <= table_count; i++){
		if((document.getElementById("table_" + i + "_0").className != 'hint' && document.getElementById("table_" + i + "_0").value != '')
				|| (document.getElementById("table_" + i + "_1").className != 'hint' && document.getElementById("table_" + i + "_1").value != ''))
			return false;
	}
	return true;
}

function gettablecontent(){
	var ret = '';
	if(showtablehint()){
		// use default values
		for(var i = 0; i < table_default.length; i++)
			ret += table_default[i][0] + "," + table_default[i][1] + ",";
	}
	else{
		for(var i = 1; i<= table_count; i++){
			var x = get_value('table_' + i + '_0');
			var y = get_value('table_' + i + '_1');
			if(x != '' && y != ''){
				ret += x + ',' + y + ',';
			}
		}
	}
	return ret;
}
function generate_input_tag(id, label){
	var val = get_value(id);
	if(val == '' && typeof(defaults[id]) != 'undefined')
		return '<label for="' + id +'">' + label + '</label><input type="text" id="' + id + '" class="hint" value="'+ defaults[id] 
		+ '" onfocus="input_onfocus(\'' + id + '\')" onblur="input_onblur(\'' + id + '\')" /><br/>';
	else
		return '<label for="' + id +'">' + label + '</label><input type="text" id="' + id + '" value="'+ val
		+ '" onfocus="input_onfocus(\'' + id + '\')" onblur="input_onblur(\'' + id + '\')" /><br/>'; 
}
function input_onfocus(id){
	if(document.getElementById(id).className == 'hint'){
		document.getElementById(id).className = '';
		document.getElementById(id).value='';
	}
}

function input_onblur(id){
	if(document.getElementById(id).value == '' && typeof(defaults[id]) != 'undefined'){
		document.getElementById(id).className = 'hint';
		document.getElementById(id).value = defaults[id];
	}
}