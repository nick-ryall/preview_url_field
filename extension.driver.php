<?php
	
	require_once(TOOLKIT . '/class.entrymanager.php');
	require_once(TOOLKIT . '/class.datasourcemanager.php');
	require_once(TOOLKIT . '/class.fieldmanager.php');
	
	class Extension_Preview_URL_Field extends Extension {
		
		protected static $fields = array();
		
		/**
		 * @var DatasourceManager
		 */
		public static $datasourceManager = null;
		
		/**
		 * @var FieldManager
		 */
		public static $fieldManager = null;
		
		/**
		 * @var dsContents
		 */
		public $dsContents;
		
		/**
		 * @var dsFilter
		 */
		public $dsFilter;

		public function about() {
			return array(
				'name'			=> 'Field: Preview URL',
				'version'		=> '1.1',
				'release-date'	=> '2011-02-07',
				'author'		=> array(
					'name'			=> 'Nick Ryall',
					'website'		=> 'http://randb.com.au/'
				),
				'description' => 'Add an temporary hyperlink in the backend to view an entry page/URL in the frontend'
			);
		}
		
		public function uninstall() {
		
			//CREATE THE HORRIBLE FILTER STRING
			$this->dsFilter = '			//PREVIEW LINK EXTENSION: Remove Filters'.PHP_EOL;
			$this->dsFilter .= '			if(sha1($_GET["entryid"]) == $_GET["key"]) {'.PHP_EOL;
			$this->dsFilter .= '				$filters = $this->dsParamFILTERS;'.PHP_EOL;
			$this->dsFilter .= '				foreach($filters as $key=>$filter) {'.PHP_EOL;
			$this->dsFilter .= '					unset($this->dsParamFILTERS[$key]);'.PHP_EOL;
			$this->dsFilter .= '				}'.PHP_EOL;
			$this->dsFilter .= '           		$this->dsParamFILTERS["id"] = $_GET["entryid"];'.PHP_EOL;
			$this->dsFilter .= '			}';
		
			//Loop through all datasources and remove the preview code if present.
			if(!isset(self::$datasourceManager)) {
				self::$datasourceManager = new DatasourceManager(Symphony::Engine());
			}
			
			$dses = self::$datasourceManager->listAll();
			foreach($dses as $ds) {
				$file = self::$datasourceManager->__getDriverPath($ds['handle']);
	
				//FIRST GET FILE CONTENTS
				$current_content = file_get_contents($file);
				
				//THEN REMOVE FILTER CODE
				$new_content = str_replace(PHP_EOL.PHP_EOL.$this->dsFilter, '', $current_content);
				
				//THEN REPALCE FILE CONTENTS
				file_put_contents($file, $new_content);
			}
			
			//DROP THE DB TABLE
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_preview_url`");
		}
		
		public function install() {
			$this->_Parent->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_preview_url` (
					`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`field_id` INT(11) UNSIGNED NOT NULL,
					`anchor_label` VARCHAR(255) DEFAULT NULL,
					`expression` VARCHAR(255) DEFAULT NULL,				
					`new_window` ENUM('yes', 'no') DEFAULT 'no',
					`hide` ENUM('yes', 'no') DEFAULT 'no',
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				)
			");
			
			return true;
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/publish/new/',
					'delegate'	=> 'EntryPostCreate',
					'callback'	=> 'compileBackendFields'
				),
				array(
					'page'		=> '/publish/edit/',
					'delegate'	=> 'EntryPostEdit',
					'callback'	=> 'compileBackendFields'
				),
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'EventPostSaveFilter',
					'callback'	=> 'compileFrontendFields'
				),
				array(
					'page'		=> '/blueprints/datasources/',
					'delegate'	=> 'DatasourcePreCreate',
					'callback'	=> 'removeDSFilters'
				),
				array(
					'page'		=> '/blueprints/datasources/',
					'delegate'	=> 'DatasourcePreEdit',
					'callback'	=> 'removeDSFilters'
				),
				array(
					'page'		=> '/blueprints/datasources/',
					'delegate'	=> 'DatasourcePostCreate',
					'callback'	=> 'rewriteDS'
				),
				array(
					'page'		=> '/blueprints/datasources/',
					'delegate'	=> 'DatasourcePostEdit',
					'callback'	=> 'rewriteDS'
				),
				
			);
		}
	
	/*-------------------------------------------------------------------------
		Backend:
	-------------------------------------------------------------------------*/
			
		public function removeDSFilters($context) {	
			//Check if there is a preview link field attached.
			$has_preview_link = false;
			if(!isset(self::$fieldManager)) {
				self::$fieldManager = new fieldManager(Symphony::Engine());
			}
			
			foreach($context['elements'] as $element) {
				$field_id = self::$fieldManager->fetchFieldIDFromElementName($element);
				$field_type = self::$fieldManager->fetchFieldTypeFromID($field_id);
				if($field_type == 'preview_url') {
					$has_preview_link = true;
				}
			}
			
			$contents = $context['contents']; 
			$first_line = '$result = new XMLElement($this->dsParamROOTELEMENT);';
			
			//CREATE THE HORRIBLE FILTER STRING
			$this->dsFilter = '			//PREVIEW LINK EXTENSION: Remove Filters'.PHP_EOL;
			$this->dsFilter .= '			if (!strpos($_SERVER[‘HTTP_USER_AGENT’],"Googlebot")) {'.PHP_EOL;
			$this->dsFilter .= '				if(sha1($_GET["entryid"]) == $_GET["key"]) {'.PHP_EOL;
			$this->dsFilter .= '					$filters = $this->dsParamFILTERS;'.PHP_EOL;
			$this->dsFilter .= '					foreach($filters as $key=>$filter) {'.PHP_EOL;
			$this->dsFilter .= '						unset($this->dsParamFILTERS[$key]);'.PHP_EOL;
			$this->dsFilter .= '					}'.PHP_EOL;
			$this->dsFilter .= '           			$this->dsParamFILTERS["id"] = $_GET["entryid"];'.PHP_EOL;
			$this->dsFilter .= '				}'.PHP_EOL;;
			$this->dsFilter .= '			}';
			
			if($has_preview_link) {
				$contents = str_replace($first_line, $first_line.PHP_EOL.PHP_EOL.$this->dsFilter, $contents);
			} else {
				$contents = str_replace($this->dsFilter, '', $contents);
			}	
			$this->dsContents = $contents;
		}
		
		public function rewriteDS($context) {
			$file = $context['file'];
			file_put_contents($file, $this->dsContents);
		}
		
	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/
	
		
		public function getXPath($entry) {
			$entry_xml = new XMLElement('entry');
			$section_id = $entry->get('section_id');
			$data = $entry->getData(); $fields = array();
			
			$entry_xml->setAttribute('id', $entry->get('id'));
			
			$associated = $entry->fetchAllAssociatedEntryCounts();
			
			if (is_array($associated) and !empty($associated)) {
				foreach ($associated as $section => $count) {
					$handle = Symphony::Database()->fetchVar('handle', 0, "
						SELECT
							s.handle
						FROM
							`tbl_sections` AS s
						WHERE
							s.id = '{$section}'
						LIMIT 1
					");
					
					$entry_xml->setAttribute($handle, (string)$count);
				}
			}
			
			// Add fields:
			$fm = new FieldManager(Administration::instance());
			foreach ($data as $field_id => $values) {
				if (empty($field_id)) continue;
				
				$field =& $fm->fetch($field_id);
				$field->appendFormattedElement($entry_xml, $values, false);
			}
			
			$xml = new XMLElement('data');
			$xml->appendChild($entry_xml);
			
			$dom = new DOMDocument();
			$dom->loadXML($xml->generate(true));
			
			return new DOMXPath($dom);
		}
		
	/*-------------------------------------------------------------------------
		Fields:
	-------------------------------------------------------------------------*/
		
		public function registerField($field) {
			self::$fields[] = $field;
		}
		
		public function compileBackendFields($context) {
			foreach (self::$fields as $field) {
				$field->compile($context['entry']);
			}
		}
		
		public function compileFrontendFields($context) {
			foreach (self::$fields as $field) {
				$field->compile($context['entry']);
			}
		}
	}