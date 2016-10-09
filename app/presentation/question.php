<?php 		
// display the page
include("header.php");

$page_title = "Question au hasard";

if (!is_random_question()) {
	$page_title = "Question n&deg;" . $question->get_ID() . ":";
}

$question_text = htmlspecialchars(stripslashes($question->get_Text()));

if ($reportHasBeenFiled)
{
	echo "<h3 class=\"error_string\">Your report has been filed. Thanks very much for your help!</h3>";
}

?>

<h3><?php echo $page_title; ?></h3>

<p><?php echo $question_text; ?> <?php echo $question->get_ID(); ?></p>

<ol type="A">
	<?php 
	foreach ($answers as $answer) {
		$quick_answer[] = $answer->get_ID();

		$correct_class = "correct_answer_link";

		if (!$answer->is_correct()) {
			$correct_class = "wrong_answer_link";
		}
		
		echo "<li>
			<a class=\"mobilebutton $correct_class\"  onclick=\"select_answer(" . $answer->get_ID() . ");\">" . htmlspecialchars(stripslashes($answer->get_Text())) . "</a>";
		if ($answer->is_correct()) {
			$section_string = "";
			
			if ($question->get_WFTDA_Link())
			{
				$section_string .= "Voir r&egrave;gle " . htmlspecialchars(stripslashes($question->get_Section()));
			
				$section_string .= " (<a target=\"_blank\" href=\"" . $question->get_WFTDA_Link() . "\" title=\"Section officielle des r&egrave;gles\" >voir sur WFTDA.com</a>)";
			}
			
			
			echo " <span style=\"display:none;\" class=\"correct_answer_win\"><strong>Gagn&eacute;&nbsp;!</strong> " . $section_string . "</span><span style=\"display:none;\" class=\"correct_answer\"><strong> La bonne r&eacute;ponse.</strong> " . $section_string . "</span>";
		}
		else
		{
			echo " <span style=\"display:none;\" class=\"wrong_answer\" id=\"wrong_answer" . $answer->get_ID() . "\"><strong>Perdu&nbsp;!</strong></span>";
		}
		echo "</li>";
	}

	?>
</ol>

<?php if ($question->get_Notes()) {?>
	<p  style="display:none;" class="question_notes">Note: <?php echo htmlspecialchars(stripslashes($question->get_Notes())); ?></p>
<?php } ?>

<p>
	<a class="button mobilebutton" href="<?php echo get_site_URL(); ?>">Nouvelle Question</a>
</p>

<?php if ($question->get_Source()) {?>
	<p class="small_p" >Source: <?php echo htmlspecialchars(stripslashes($question->get_Source())); ?></p>
<?php } ?>

<script type="text/javascript">
	var answered = false;
	
	function select_answer(selected)
	{
		if (!answered)
		{
			// make sure we only answer once
			answered = true;

			// show what was right and what was wrong
			if (selected == <?php echo $correct_answer->get_ID()?>)
			{
				// correct!
				$(".correct_answer_win").show();
			}
			else
			{
				// wrong!
				$(".correct_answer").show();
				$("#wrong_answer" + selected).show();
			}

			<?php if ($question->get_Notes()) {?>
			// show the notes
			$(".question_notes").show();
			<?php } ?>

			// ajax save the response for stats tracking
			$.post("/ajax.php", { 
				call: "save_response", 
				question_ID: "<?php echo $question->get_ID(); ?>",
				response_ID: selected,
				return_remembered_questions_string: true},
				function(data) {
					$("#remembered_string").hide();
					$("#remembered_string").html(data);
					$("#remembered_string").fadeIn();
					$("#remembered_string_p").show();
				}
			);
		}
	}
	
	var allow_keypress = true;
	$(document).keypress(function(e) {
		if (allow_keypress)
		{
		    if((e.which == 78) || (e.which == 110))
			{
		    	window.location.reload();
		    }
		    <?php 
		    for ($i = 0; $i < count($answers); $i++)
		    {
			    ?>
			    if((e.which == <?php echo $i + 49 ?>) || (e.which == <?php echo $i + 65 ?>) || (e.which == <?php echo $i + 97 ?>))
				{
			    	select_answer(<?php echo $quick_answer[$i]; ?>);
			    }
			    <?php 
			}?>
		}
	});
	
</script>

<div class="report_form" id="hidden_report_form">
	
	<h3>Report this question:</h3>
	<p>You should report a question if you think it's incorrect or if it's poorly written (including spelling mistakes or bad grammar). If you think the question is wrong be sure to double check the wording of the question <i>and</i> the specific rule it references, which in this case is <strong><?php if ($question) { echo htmlspecialchars(stripslashes($question->get_Section())); } ?></strong>. Until the great robot uprising, we're only human so mistakes happen. Thanks for helping!</p>
	<p>In the text box below please let me know what it is that made you report this question.</p>
	
	<form name="formreport" method="post" action="<?php echo get_site_URL(); ?>report">	
	<p>
		<input type="hidden" id="report_question_ID" name="report_question_ID" value="<?php if ($question) echo $question->get_ID(); ?>" />
		<textarea name="report_text"  id="report_text" rows="10" cols="40"><?php 
		if ($_POST['report_text']) 
		{
			echo stripslashes(htmlspecialchars($_POST['report_text']));
		}
		else
		{
			echo "I'm reporting question #";
			if ($question) 
				echo $question->get_ID();
			echo " because ... ";
		}
		?></textarea>
	</p>
	<p>
		To prevent spam reports, please complete the following sentence:<br /> "Roller <input id="report_extra" name="report_extra" type="text" /> is an awesome sport."
	</p>

	<p>
		<a class="button" onClick="document.formreport.submit()">Submit Report</a> <a class="button" onClick="$('#hidden_report_form').slideUp()">Cancel</a> 
	</p>
	</form>
	
	<p>A small message from Sausage Roller (the guy who made the site): Thank you. No seriously, <i>thank you</i>. The reports I've gotten from people have been so useful, and really helped me improve, clarify and fix the questions. I'm sorry there's no easy way for me to show my gratitude, but the response from this report feature has reminded me, once again, why I love the global derby community.</p>
</div>
<?php 
include("footer.php");
?>
