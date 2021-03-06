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
	echo "<h3 class=\"error_string\">Merci d'avoir pris le temps de signaler la question, cela nous a été transmis.</h3>";
}

?>


<div class="question-box">
	<span class="question-block"><?=  $question->get_ID() ?> - <?= $page_title ?></span>
	<p class="question-text"><?= $question_text ?></p>
</div>
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
	<p  style="display:none;" class="question_notes">Note : <?php echo htmlspecialchars(stripslashes($question->get_Notes())); ?></p>
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

	<h3>Signaler cette question :</h3>
	<p>Il convient de reporter une question si vous pensez que la réponse donnée n'est pas la bonne ou si la forme de la question (ou d'une réponse) est mal écrite (y compris des fautes d'orthographe ou de grammaire). Si vous pensez que la réponse donnée n'est pas la bonne, merci de vérifier à deux fois dans les rèlges (dans ce cas la règle <strong><?php if ($question) { echo htmlspecialchars(stripslashes($question->get_Section())); } ?></strong>. . Malgré tous nos efforts, les erreurs arrivent. Ne pas hésiter et merci de votre aide !</p>
	<p>Entrez ici le détail de votre rapport...</p>

	<form name="formreport" method="post" action="<?php echo get_site_URL(); ?>report">
	<p>
		<input type="hidden" id="report_question_ID" name="report_question_ID" value="<?php if ($question) echo $question->get_ID(); ?>" />
		<textarea name="report_text"
			id="report_text"
			rows="10"
			cols="40"
			placeholder="Entrez ici le détail de votre rapport..."
		><?php if ($_POST['report_text']) {
		echo stripslashes(htmlspecialchars($_POST['report_text']));
		} ?></textarea>
	</p>
	<p>
		<a class="button" onClick="document.formreport.submit()">Soumettre le rapport</a>
		<a class="button" onClick="$('#hidden_report_form').slideUp()">Annuler</a>
	</p>
	</form>
	<b>MERCI !</b>
</div>
<?php include("footer.php"); ?>
