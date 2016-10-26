<?php
include_once (__DIR__ . "/ColourFromPercentageCalculator.class.php");

class RememberedStringGenerator
{
	private $site_url;
	private $showStreakWhenStreakLength = 4;
	
	function __construct($site_url) {
		$this->site_url = $site_url;
	}
	
	public function generate($questionsAnsweredResults) {

		if (!$questionsAnsweredResults) {
			return;
		}
		
		$questionsAnswered = count($questionsAnsweredResults);
		$questionsAnsweredCorrectly = $this->calculateCorrectCount($questionsAnsweredResults);
		$percentageCorrect = $this->calculatePercentage($questionsAnswered, $questionsAnsweredCorrectly);
		$percentageColour = ColourFromPercentageCalculator::calculate($percentageCorrect);
		$currentStreak = $this->calculateStreak($questionsAnsweredResults);
		
		if ($questionsAnswered <= 0) {
			return "";
		}
		
		$currentSuccessString = "Vous avez un pourcentage de réussite de <span style=\"font-weight:bold; color:" . $percentageColour . "\">" . $percentageCorrect . "%</span> (" . $questionsAnsweredCorrectly . " bonnes réponses " . $questionsAnswered . ")";
		
		$forgetString = " <a href=\"" . $this->site_url . "forget\">Remise à zéro</a>";
		
		if ($currentStreak > $this->showStreakWhenStreakLength) {
			$streakString = " dont <strong>" . $currentStreak . "</strong> bonnes réponses consécutives.";
		} else {
			if ($this->hasStreakEndedSpecification($questionsAnsweredResults)) {
				$priorStreakLength = $this->calculateStreak($questionsAnsweredResults, 1);
				$streakString = " <span style=\"color:#FF0000\">Vous avez mis fin à votre série de <strong>" . $priorStreakLength . " bonnes réponses."</strong></span>.";
			} else {
				$streakString = "";
			}
		}
		
		return $currentSuccessString . $streakString . $forgetString;
	}
	
	private function calculateCorrectCount($questionsAnsweredResults) {
		
		$correct_count = 0;
		
		if (count($questionsAnsweredResults) > 0) {
			foreach ($questionsAnsweredResults as $tmp_result) {
				if ($tmp_result) {
					$correct_count++;
				}
			}
		}
		
		return $correct_count;
	}
	
	private function calculatePercentage($total, $correct) {
		if ($correct > 0) {
			return round((($correct / $total) * 100) , 2);
		} else {
			return 0;
		}
	}
	
	private function calculateStreak($questionsAnsweredResults, $offset = 0) {
		
		$current_streak = 0;
		
		for ($i = count($questionsAnsweredResults) - 1 - $offset; $i >= 0; $i--) {
			if ($questionsAnsweredResults[$i] === false) {
				return $current_streak;
			}
			$current_streak++;
		}
		
		return $current_streak;
	}

	private function hasStreakEndedSpecification($questionsAnsweredResults) {

		$wasLastAnswerWrong = $questionsAnsweredResults[count($questionsAnsweredResults) -1] === false;

		$priorStreakLength = $this->calculateStreak($questionsAnsweredResults, 1);

		return ($priorStreakLength > $this->showStreakWhenStreakLength) && $wasLastAnswerWrong;
	}
}
