var table_count = 0;

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
		var average = get_value("average");
		url += "&mean=" + encodeURIComponent(average);
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
		url += "&table=" + encodeURIComponent(gettablecontent());
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
	if(document.getElementById(id) == null) return '';
	else return document.getElementById(id).value;
}
function onactionchange(){
	var action = get_value("action");
	var distribution = get_value("distribution"); 
	var newhtml = '';
	if(action == "distribution"){
			newhtml = generate_input_tag("average", "Average depth-coverage: ");
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
		document.getElementById("distributionparams").innerHTML = 
			'<label for="size">Dispersion parameter:</label>' + 
			'<input type="text" id="size" /><br/>';
		document.getElementById("opt_distribution").disabled = '';
	}
	else if(distribution == "table"){
		table_count = 10;
		var table_html = '<table id="table"><tr><th>Average depth-coverage</th><th>Power</th></tr>';
		for(var i = 1; i <= table_count; i++)
			table_html += '<tr><td><input type="text" id="table_' + i + '_0" /></td><td><input type="text" id="table_' + i + '_1" /></td></tr>';
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

function moretable(){
	table_count ++;
	var table_html = '<table id="table"><tr><th>Average depth-coverage</th><th>Power</th></tr>';
	for(var i = 1; i <= table_count; i++)
		table_html += '<tr><td><input type="text" id="table_' + i + '_0" value="'+ get_value("table_" + i + "_0") + '" />'
		 + '</td><td><input type="text" id="table_' + i + '_1" value="'+ get_value("table_" + i + "_1") + '" /></td></tr>';
	table_html += "</table>";
	table_html += '<br/> <input type="button" onclick="moretable()" value="More" />';
	document.getElementById("distributionparams").innerHTML = table_html;
}

function gettablecontent(){
	var ret = '';
	for(var i = 1; i<= table_count; i++){
		var x = get_value('table_' + i + '_0');
		var y = get_value('table_' + i + '_1');
		if(x != '' && y != ''){
			ret += x + ',' + y + ',';
		}
	}
	return ret;
}
function generate_input_tag(id, label){
	var val = get_value(id);
	return '<label for="' + id +'">' + label + '</label><input type="text" id="' + id + '" value="'+ val + '" /><br/>'; 
}