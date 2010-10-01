<?php
/*----------------------------------------------------------------*/
	
	class Password {
		protected $password;
		protected $length;
		protected $min;
		protected $max;
		protected $log = array();
		
		public function __construct($password) {
			$this->password = $password;
			$this->length = strlen($password);
			$this->min = $this->max = $this->length;
		}
		
		/*
		* Lower the score if the password is shorter than the desired length.
		* 
		* @param $length	integer		The ideal length for a password.
		*/
		public function checkLength($length = 8) {
			if ($this->length == 0) return;
			
			$this->change(
				$this->max * max($length / $this->length, 1),
				"Password length test at %d characters",
				array($this->length)
			);
		}
		
		/*
		* Lower the score if the password contains a poor mix of characters or
		* too many repeated characters.
		* 
		* @param $adjust	float		Change how much this test affect the total score.
		*/
		public function checkCharacters($adjust = 1) {
			if ($this->length == 0) return;
			
			$mixture = array(
				'/[a-z]+/'			=> 1.3,
				'/[A-Z]+/'			=> 1.3,
				'/[0-9]+/'			=> 1.1,
				'/[^a-zA-Z0-9]+/'	=> 1.5
			);
			$repeat = array(
				'/(.)(?:\1+)/'		=> 1.1,
				'/([a-z])(?:\1+)/i'	=> 1.3
			);
			
			foreach ($mixture as $expression => $factor) {
				if (preg_match($expression, $this->password)) continue;
				
				$this->change(
					$this->max * max($factor * $adjust, 1),
					"Failed a character mixture test"
				);
			}
			
			foreach ($repeat as $expression => $factor) {
				if (!preg_match($expression, $this->password, $match)) continue;
				
				$this->change(
					($this->max + strlen($match[0])) * max($factor * $adjust, 1),
					"Found repeating characters '%s'",
					array($match[0])
				);
			}
		}
		
		/*
		* Lower the score if the password is a word, or contains words that
		* make up a large portion of the password.
		* 
		* @param $adjust	float		Change how much this test affect the total score.
		* @param $length	integer		Ignore words shorter than this.
		* @param $percent	float		Ignore words that are less than this percentage of the password.
		*/
		public function checkWords($adjust = 1, $length = 4, $percent = 0.2) {
			if ($this->length == 0) return;
			
			$dict = pspell_new('en', null, null, 'utf-8', PSPELL_FAST);
			
			preg_match_all('/[a-z]+/i', $this->password, $words);
			
			foreach ($words[0] as $word) {
				if (strlen($word) < $length) continue;
				if (strlen($word) / $this->length < $percent) continue;
				if (!pspell_check($dict, $word)) continue;
				
				$this->change(
					$this->max * max(1.5 + strlen($word) / $this->length / 2 * $adjust, 1),
					"Found the word '%s'",
					array($word)
				);
			}
		}
		
		public function score($decimal_places = 3) {
			if ($this->length == 0) return 0;
			
			return round($this->min / $this->max, $decimal_places);
		}
		
		public function log() {
			return $this->log;
		}
		
		protected function change($score, $message, array $arguments = array()) {
			$old = $this->min / $this->max;
			$new = $this->min / $score;
			
			$arguments[] = 100 * ($old / $new - 1);
			
			$this->log[] = vsprintf($message . ' (%d percent loss).', $arguments);
			
			$this->max = $score;
		}
	}
	
/*----------------------------------------------------------------*/
?>