<?php
/*----------------------------------------------------------------*/
	
	class JsonLog {
		protected $message;
		protected $items;
		protected $values;
		protected $current;
		
		public function __construct() {
			$this->items = array();
			$this->values = array();
		}
		
		public function __isset($name) {
			return isset($this->values[$name]);
		}
		
		public function __get($name) {
			return $this->values[$name];
		}
		
		public function __set($name, $value) {
			$this->values[$name] = $value;
		}
		
		public function __unset($name) {
			unset($this->values[$name]);
		}
		
		public function __toString() {
			$items = $values = array();
			
			foreach ($this->items as $key => $value) {
				$key = strtr($key, '-', '_');
				$items[$key] = $value;
			}
			
			foreach ($this->values as $key => $value) {
				$key = strtr($key, '-', '_');
				$values[$key] = $value;
			}
			
			$data = array(
				'failed'	=> $this->failed(),
				'message'	=> $this->message,
				'items'		=> $items,
				'values'	=> $values
			);
			
			return json_encode($data);
		}
		
		/**
		* Mark a state as started.
		* 
		* @param string $name
		*/
		public function begin($name) {
			$this->items[$name] = array(
				'attempted'		=> true,
				'completed'		=> false
			);
		}
		
		/**
		* Define a new state.
		* 
		* @param string $name
		*/
		public function create($name) {
			$this->items[$name] = array(
				'attempted'		=> false,
				'completed'		=> false
			);
		}
		
		/**
		* Mark a state as completed.
		* 
		* @param string $name
		*/
		public function end($name) {
			$this->items[$name] = array(
				'attempted'		=> true,
				'completed'		=> true
			);
		}
		
		/**
		* Check if anything has failed.
		*/
		public function failed() {
			foreach ($this->items as $item) {
				if (($item['attempted'] && $item['completed']) || !$item['attempted'])  continue;
				
				return true;
			}
			
			return false;
		}
		
		/**
		* Set a message explaining success or failure.
		* 
		* @param string $message
		*/
		public function message($message) {
			$this->message = $message;
		}
	}
	
/*----------------------------------------------------------------*/
?>