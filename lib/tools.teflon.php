<?php

	/*
		Teflon is a JSON database
		@author Matvey Pravosudov
	*/
	
	class Tools extends Teflon {
		public function packIn () {
			$result = array ();
			foreach (glob ($this->path . $this->ds . '*') as $table) {
				$result[basename ($table)] = array ();
				foreach (glob ($this->path . $this->ds . basename ($table) . $this->ds . '*.json') as $name) {
					$result[basename ($table)][$this->bn($name)] = file_get_contents ($this->path . $this->ds . basename ($table) . $this->ds . basename ($name));
				}
			}
			return $result;
		}
		
		public function packOut ($pack) {
			foreach ($pack as $table) {
				mkdir ($this->path . $this->ds . $table);
				foreach ($pack[$table] as $name) {
					file_put_contents ($this->path . $this->ds . $table . $this->ds . $name, $pack[$table][$name] . '.json');
				}
			}
		}
	}
