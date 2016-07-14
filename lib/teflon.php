<?php

	/*
		Teflon is a JSON database
		@author Matvey Pravosudov
	*/
	
	class Teflon {
		public
			$status_code = 0,
			$status_msg = array ();
		
		protected
			$path,
			$ds = DIRECTORY_SEPARATOR,
			$version = '1.0',
			$lock = array ();
		
		// set path, secure Teflon
		public function __construct ($path) {
			$this->path = rtrim ($path, '\\/');
			$this->secure();
		}
		
		// clean data
		public function clean ($data) {
			if (is_array ($data)) {
				$result = array ();
				foreach ($data as $key => $value) {
					$result[$key] = escapeshellcmd (addslashes (trim ($value)));
				}
			}
			else if (is_string ($data)) {
				$result = escapeshellcmd (addslashes (trim ($value)));
			}
			else {$result = $data;}
			return $result;
		}
		
		// creat new table (directory)
		public function create ($table) {
			$this->statusCODE = 0;
			$this->statusMSG = array ();
			
			if ( ! file_exists ($this->path . $this->ds . $table)) {
				mkdir ($this->path . $this->ds . $table);
				
				$config = array (
					'created' => date ('Y-m-d h:i:s')
				);
				
				return file_put_contents ($this->path . $this->ds . $table . $this->ds . 'config.ds.json', json_encode ($config));
			}
			else {
				$this->status_code = 101;
				$this->status_msg[] = 'Table "' . $table . '" already exists.';
				return false;
			}
		}
		
		// delete item (directory)
		public function delete ($item, $table) {
			$this->statusCODE = 0;
			$this->statusMSG = array ();
			
			if (file_exists ($this->path . $this->ds . $table . $this->ds . $item . '.json')) {
				return unlink ($this->path . $this->ds . $table . $this->ds . $item . '.json');
			}
			else {return false;}
		}
		
		// delete table (directory)
		public function drop ($table) {
			$this->statusCODE = 0;
			$this->statusMSG = array ();
			
			if (file_exists ($this->path . $this->ds . $table)) {
				foreach (glob ($this->path . $this->ds . $table . $this->ds . "*.json") as $item) {
					unlink ($this->path . $this->ds . $table . $this->ds . basename ($item));
				}
				return rmdir ($this->path . $this->ds . $table);
			}
			else {
				$this->status_code = 102;
				$this->status_msg[] = 'Table "' . $table . '" doesn\'t exists.';
				return false;
			}
		}
		
		// check for exists
		public function exists ($name, $type = 'table') {
			if ($type == 'item' && is_array ($name)) {
				if (file_exists ($this->path . $this->ds . $name[0] . $this->ds . $name[1] . '.json')) {return true;}
				else {return false;}
			}
			else if ($type == 'table') {
				if (file_exists ($this->path . $this->ds . $name)) {return true;}
				else {return false;}
			}
			else {return false;}
		}
		
		// get data from item (directory)
		public function get ($select = '*', $item, $table) {
			$this->statusCODE = 0;
			$this->statusMSG = array ();
			
			if (file_exists ($this->path . $this->ds . $table . $this->ds . $item . '.json')) {
				
				/* Locking on */
				$this->lock($table . DS . $item, 'on');
				
				$file = file_get_contents ($this->path . $this->ds . $table . $this->ds . $item . '.json');
				$data = json_decode ($file, true);
				
				if ($select || $select == '*') {
					$result = array ();
					foreach ($select as $key) {
						$result[$key] = $data[$key];
					}
					
					/* Locking off */
					$this->lock($table . DS . $item, 'off');
					
					return $result;
				}
				else {
					/* Locking off */
					$this->lock($table . DS . $item, 'off');
					
					return $data;
				}
			}
			else {
				$this->status_code = 103;
				$this->status_msg[] = 'Item "' . $item . '" doesn\'t exists.';
				return false;
			}
		}
		
		// get config from table (directory)
		public function getConfig ($table) {
			$this->statusCODE = 0;
			$this->statusMSG = array ();
			
			if (file_exists ($this->path . $this->ds . $table)) {
				$file = file_get_contents ($this->path . $this->ds . $table . $this->ds . 'config.ds.json');
				return json_decode ($file, true);
			}
			else {
				$this->status_code = 102;
				$this->status_msg[] = 'Table "' . $table . '" doesn\'t exists.';
				return false;
			}
		}
		
		// merge tables (directories)
		public function merge ($tables = array (), $name = false) {
			$this->statusCODE = 0;
			$this->statusMSG = array ();
			
			$items = array ();
			foreach ($tables as $table) {
				if (file_exists ($this->path . $this->ds . $table)) {
					foreach (glob ($this->path . $this->ds . $table . $this->ds . '*.json') as $item) {
						$items[$this->bn($item)] = file_get_contents ($item);
					}
					$this->drop($table);
				}
				else {
					$this->status_code = 102;
					$this->status_msg[] = 'Table "' . $table . '" doesn\'t exists.';
					return false;
				}
			}
			
			if ($name) {
				if ( ! file_exists ($this->path . $this->ds . $name)) {
					$this->create($name);
                    unset ($items['config.ds']);
					foreach ($items as $key => $value) {
						file_put_contents ($this->path . $this->ds . $name . $this->ds . $key . '.json', $value);
					}
				}
				else {
					$this->status_code = 101;
					$this->status_msg[] = 'Table "' . $name . '" already exists.';
					return false;
				}
			}
			else {
				mkdir ($this->path . $this->ds . $tables[0]);
				foreach ($items as $key => $value) {
					file_put_contents ($this->path . $this->ds . $tables[0] . $this->ds . $key . '.json', $value);
				}
			}
		}
		
		// put data to item (directory)
		public function put ($data, $item, $table) {
			$this->statusCODE = 0;
			$this->statusMSG = array ();
			
			if ( ! file_exists ($this->path . $this->ds . $table . $this->ds . $item . '.json')) {
				$this->lock($table . DS . $item, 'on');
				
				$result = json_encode ($data);
				
				$this->lock($table . DS . $item, 'off');
				return file_put_contents ($this->path . $this->ds . $table . $this->ds . $item . '.json', $result);
			}
			else {
				$this->status_code = 104;
				$this->status_msg[] = 'Item "' . $item . '" already exists.';
				return false;
			}
		}
		
		// search data from table (directory)
		public function search ($needle, $table) {
			$result = array ();
			foreach (glob ($this->path . $this->ds . $table . $this->ds . "*.json") as $item) {
				$file = file_get_contents ($this->path . $this->ds . $table . $this->ds . basename ($item));
				if (strpos ($file, $needle)) {$result[] = $this->bn($item);}
			}
			return $result;
		}
		
		// set data in item (directory)
		public function set ($data, $item, $table) {
			if (file_exists ($this->path . $this->ds . $table . $this->ds . $item . '.json')) {
				
				// lock
				$this->lock($table . DS . $item, 'on');
				
				$file = file_get_contents ($this->path . $this->ds . $table . $this->ds . $item . '.json');
				$result = array_merge (json_decode ($file, true), $data);
				
				// unlock
				$this->lock($table . DS . $item, 'off');
				
				return file_put_contents ($this->path . $this->ds . $table . $this->ds . $item . '.json', json_encode ($result));
			}
			else {return false;}
		}
		
		/*
		 * Get status of last request.
		 * $flag - true or false.
		 * if true - return text message
		 * if fasle - return status code.
		 *
		 * Status codes:
		 * 0 - All OK.
		 * 101 - Table already exists.
		 * 102 - Table doesn't exists.
		 * 103 - Item already exists.
		 * 104 - Item doesn't exists.  
		*/
		public function status ($flag = false){
			return ( ! $flag) ? $this->status_code : implode ('; ', $this->status_msg);	
		}
		
		// truncate table (directory)
		public function truncate ($table) {
			if (file_exists ($this->path . $this->ds . $table)) {
				foreach (glob ($this->path . $this->ds . $table . $this->ds . '*.json') as $item) {
					unlink ($this->path . $this->ds . $table . $this->ds . basename ($item));
				}
			}
			else {return false;}
		}
		
		// return version
		public function version () {
			return $this->version;
		}
		
		// basename
		protected function bn ($name) {
			if (is_array ($name)) {
				$result = array ();
				foreach ($name as $item) {
					$result[$item] = str_replace ('.json', '', basename ($item));
				}
				return $result;
			}
			else if (is_string ($name)) {return str_replace ('.json', '', basename ($name));}
			else {return $name;}
		}
		
		// lock
		protected function lock ($name, $type = 'on') {
			if ($type === 'on') {
				if (in_array ($name, $this->lock)) {
					sleep (1);
					$this->lock($name, $type);
				}
				else {
					$this->lock[] = $name;
					return true;
				}
			}
			else if ($type === 'off') {
				$count = 0;
				foreach ($this->lock as $item) {
					if ($item == $name) {
						unset ($this->lock[$count]);
						return true;
					}
					$count++;
				}
			}
		}
		
		// secure Teflon by .htaccess
		private function secure () {
			if ( ! file_exists ($this->path . $this->ds . '.htaccess')) {
				$data = "<Files *>\n\tOrder Allow,Deny\n\tDeny from All\n</Files>";
				file_put_contents ($this->path . $this->ds . '.htaccess', $data);
			}
			else {return false;}
		}
	}
