<?php
function return_stats_user_totals() {
    
    //TODO this is a duplicate of the Remembered String
    global $user, $mydb;
    
    $user_responses = return_user_responses();
    
    if (!$user_responses) {
        return "<p>Vous n'avez pas encore répondu à de questions tout en étant connecté. Une fois connecté, vos statistiques apparaîtront ici.</p>";
    }
    
    // total questions answered
    $total_response_count = count($user_responses);
    
    $total_response_count_string = number_format($total_response_count);
    $out.= "<p>Vous avez répondu à un total de <strong>" . $total_response_count_string . "</strong> questions";
    if ($total_response_count != 1) {
        $out.= "s";
    }
    $out.= ".</p>";
    
    // total correct %
    foreach ($user_responses as $user_response) {
        if ($user_response->get_Correct()) {
            $all_time_correct_count++;
        } else {
            $all_time_incorrect_count++;
        }
    }
    
    if ($total_response_count > 0) {
        $perc_value = round(($all_time_correct_count * 100) / $total_response_count);
    } else {
        $perc_value = 0;
    }
    
    $perc_colour = ColourFromPercentageCalculator::calculate($perc_value);
    $out.= "<p>Vous avez un pourcentage de bonnes réponses de <span style=\"font-weight:bold; color:" . $perc_colour . "\">" . $perc_value . "%</span> (" . number_format($all_time_correct_count) . " correct out of " . $total_response_count_string . ").</p>";
    
    // recent questions answered
    
    // recent correct %
    return $out;
}

function process_sections_responses_into_data($recent_responses, $section_array) {
    
    // create the data to chart
    // for each of the responses
    
    foreach ($recent_responses as $response) {
        $section_number = intval($section_array[$response->get_Question_ID() ][0]);
        if ($section_number) {
            if (($section_number == 1) && ($section_array[$response->get_Question_ID() ][1] == "0")) {
                $section_number = 10;
            }
            
            // get the first two values
            if ($response->get_Correct()) {
                $section_counts[$section_number]["correct"]++;
            } else {
                $section_counts[$section_number]["wrong"]++;
            }
        }
    }
    
    ksort($section_counts);
    
    foreach ($section_counts as $id => $section_count) {
        $percentage = round(($section_count['correct'] * 100) / ($section_count['correct'] + $section_count['wrong']));
        $data_array[$id] = $percentage;
    }
    
    return $data_array;
}

function return_chart_section_percentages_all() {
    $fileCache = new FileCache();
    
    $data_array = $fileCache->get("last_10000_sections");
    
    if ($data_array) {
        return return_chart_section_percentages($data_array);
    } else {
        return "Cache not found";
    }
}

function return_stats_user_section_totals() {
    global $user;
    
    if (!return_user_responses()) {
        return;
    }
    
    if ($user) {
        $user_call_string = ", User_ID: " . $user->get_ID();
    }
    
    $drawChart_string = '

	var jsonData_user_section_totals = $.ajax({
            url: "/ajax.php",
            type: "POST",
            data: {call: "stats_user_section_totals" ' . $user_call_string . '},
            dataType:"json",
            async: false
            }).responseText;
    
	if (JSON.parse(jsonData_user_section_totals).rows.length > 0)
	{
		data_user_section_totals = new google.visualization.DataTable(jsonData_user_section_totals);
	    
        options_user_section_totals = {
          colors: [\'#0000FF\', \'#BBBBFF\'],
          focusTarget: \'category\',
          titlePosition: \'none\',
          hAxis: {titlePosition: \'none\',},
          chartArea: {left:30, width: \'80%\', height: \'80%\', top: 10},
          legend: {position: \'' . $legends . '\'},
          vAxis: {minValue: 0, maxValue: 100, gridlines: {count: 11}}
        };
	
	    var chart_user_section_totals = new google.visualization.ColumnChart(document.getElementById(\'chart_section_breakdown\'));
	    chart_user_section_totals.draw(data_user_section_totals, options_user_section_totals);	
	}
	else
	{
		$("#chart_section_breakdown").html("You need to answer more questions to generate this graph.");
	}
    ';
    
    add_google_chart_drawChart($drawChart_string);
    
    $out.= '<p>Section breakdown:</p><div id="chart_section_breakdown" style="width: 100%; height: 400px;">Loading ...</div>';
    
    return $out;
}

function return_chart_section_percentages($data_array, $data_array2 = false) {
    
    foreach ($data_array as $id => $percentage) {
        if ($data_array2) {
            $data_string_array[] = "['Section " . $id . "',  " . $percentage . ",  " . $data_array2[$id] . "]";
        } else {
            $data_string_array[] = "['Section " . $id . "',  " . $percentage . "]";
        }
    }
    
    $data_string = implode(", ", $data_string_array);
    
    if ($data_array2) {
        $data_string_text = "['Section', 'You', 'Average'],";
        $legend = "right";
    } else {
        $data_string_text = "['Section', 'Percentage Correct'],";
        $legend = "none";
    }
    
    $drawChart_string = '
		var data = google.visualization.arrayToDataTable([
          ' . $data_string_text . '
          ' . $data_string . '
        ]);

        var options = {
          colors: [\'#0000FF\', \'#BBBBFF\'],
          focusTarget: \'category\',
          titlePosition: \'none\',
          hAxis: {titlePosition: \'none\',},
          chartArea: {left:30, width: \'80%\', height: \'80%\', top: 10},
          legend: {position: \'' . $legends . '\'},
          vAxis: {minValue: 0, maxValue: 100, gridlines: {count: 11}}
        };

        var chart = new google.visualization.ColumnChart(document.getElementById(\'chart_section_breakdown\'));
        chart.draw(data, options);';
    
    add_google_chart_drawChart($drawChart_string);
    
    $out.= '<p>Section breakdown:</p><div id="chart_section_breakdown" style="width: 100%; height: 400px;">Loading ...</div>';
    
    return $out;
}

function return_stats_user_progress($user = false) {
    $user_responses = return_user_responses();
    if (!$user_responses) {
        return;
    }
    
    if ($user) {
        $user_call_string = ", User_ID: " . $user->get_ID();
    }
    
    $drawChart_string = '

	
	var jsonData_user_progress = $.ajax({
            url: "/ajax.php",
            type: "POST",
            data: {call: "stats_user_progress" ' . $user_call_string . '},
            dataType:"json",
            async: false
            }).responseText;
    
	if (JSON.parse(jsonData_user_progress).rows.length > 0)
	{
		data_stats_user_progress = new google.visualization.DataTable(jsonData_user_progress);
		
	    options_stats_user_progress = {
	    	  vAxis: {viewWindow:{max:100, min:0},gridlines: {count: 6}, viewWindowMode: \'explicit\'},
	          titlePosition: \'none\',
	          hAxis: {titlePosition: \'none\', textPosition: \'none\'},
          	  chartArea: {left:30, width: \'80%\', height: \'90%\', top: 10},
	          legend: {position: \'none\'},
	          colors: [\'#0000FF\'],
	          curveType: \'function\',
	          enableInteractivity: false
	    };
	
	    var chart_stats_user_progress = new google.visualization.LineChart(document.getElementById(\'chart_progress\'));
	    chart_stats_user_progress.draw(data_stats_user_progress, options_stats_user_progress);	
	}
	else
	{
		$("#chart_progress").html("You need to answer more questions to generate this graph.");
	}
    ';
    
    add_google_chart_drawChart($drawChart_string);
    
    $out.= '<p>Your average success rate:</p><div id="chart_progress' . $user_ID . '" style="width: 100%; height: 200px;">Loading ...</div>';
    
    return $out;
}

function return_chart_24hour_responses() {
    global $mydb;
    $fileCache = new FileCache();
    
    // get the raw values
    $raw_data = $fileCache->get("stats_hourly_posts");
    if (!$raw_data) {
        return "No stats_hourly_posts cache found";
    }
    
    $current_minute = date('i');
    $percentage_hour_complete = $current_minute / 60;
    
    // make the final data point the current per hour rate
    $raw_data[24] = $fileCache->get("response_count_last_hour");
    if (!$raw_data[24]) {
        return "No response_count_last_hour cache found";
    }
    
    // merge it all into one array
    foreach ($raw_data as $id => $response_count) {
        $hour_count = 24 - $id;
        if ($hour_count > 1) {
            $hour_string = $hour_count . " hours ago";
        } elseif ($hour_count == 1) {
            $hour_string = $hour_count . " hour ago";
        } elseif ($hour_count == 0) {
            $hour_string = "This hour";
        }
        $data_string_array[] = "
			['" . $hour_string . "',  " . (integer)$raw_data[$id] . "]";
    }
    
    $data_string = implode(", ", $data_string_array);
    
    $drawChart_string = '
		var data = google.visualization.arrayToDataTable([
	          [\'Hour\', \'Responses\'],
	          ' . $data_string . '
	        ]);
	
	    var options = {
	          titlePosition: \'none\',
	          hAxis: {titlePosition: \'none\', textPosition: \'none\'},
	          chartArea: {width: \'90%\', height: \'90%\', top: 10},
	          legend: {position: \'none\'},
	          colors: [\'#0000FF\']
	    };
	    ';
    
    $drawChart_string.= '

        var chart = new google.visualization.LineChart(document.getElementById(\'chart_24responses\'));
        chart.draw(data, options);';
    
    add_google_chart_drawChart($drawChart_string);
    
    $out.= '<div id="chart_24responses" style="width: 100%; height: 200px;"></div>';
    
    return $out;
}
function return_user_responses() {
    global $user, $mydb;
    global $user_responses, $fetched_user_responses;
    
    $fileCache = new FileCache();
    
    if ($fetched_user_responses) {
        return $user_responses;
    } else {
        $cached_user_responses = $fileCache->get("user_responses_" . $user->get_ID());
        if (!$cached_user_responses) {
            $user_responses = $mydb->get_responses_from_User_ID($user->get_ID(), true);
            $fetched_user_responses = true;
            $cached_user_responses = $user_responses;
            
            $fileCache->set("user_responses_" . $user->get_ID(), $user_responses, 600);
        }
        
        return $cached_user_responses;
    }
    
    return $user_responses;
}

function return_user_questions_sections($user_ID = false) {
    global $user, $mydb;
    global $user_questions_sections, $fetched_user_questions_sections;
    
    if ($fetched_user_questions_sections) {
        return $user_questions_sections;
    } else {
        $user_questions_sections = get_sections_array_from_User_ID($user->get_ID());
        $fetched_user_questions_sections = true;
    }
    
    return $user_questions_sections;
}
