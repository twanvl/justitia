<?php

// -----------------------------------------------------------------------------
// Information on a programming language
// -----------------------------------------------------------------------------

class Language {
	// ---------------------------------------------------------------------
	// Information on the language
	// ---------------------------------------------------------------------
	
	public $name;     // user friendly full name of the language
	public $is_code;  // is this a known programming language?
	public $compiler; // filename of the default compiler script
	public $filename_regex; // regular expression to match filenames
	
	// If this is the magic 'any' language, then determines the actual language based on filename
	// returns a 'specialized' language object for these filenames
	public function adapt_to_filenames($filenames) {
		return $this;
	}
	
	// Should a source file with the given name be compiled?
	// can be used to exclude .h files from compilation for example
	public function should_compile($filename) {
		return true;
	}
	
	// ---------------------------------------------------------------------
	// Contruction
	// ---------------------------------------------------------------------
	
	function __construct($name, $compiler, $filename_regex, $is_code = true) {
		$this->name = $name;
		$this->compiler = $compiler;
		$this->filename_regex = $filename_regex;
		$this->is_code = $is_code;
	}
	
	// Get the language with the given name
	static function by_name($name, $log_info=NULL) {
		global $languages;
		if (isset($languages[$name])) {
			return $languages[$name];
		} else {
			LogEntry::log("Unknown language: \"$name\"", $log_info);
			return $languages['any'];
		}
	}
	
	// Get a language matching the given filename
	static function by_filename($filename) {
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		global $languages_by_extension;
		if (isset($languages_by_extension[$ext])) {
			return $languages_by_extension[$ext];
		} else {
			global $languages;
			return $languages['unknown'];
		}
	}
	
	// Get a language matching the given set of filenames
	static function by_filenames($filenames) {
		// TODO: if there are multiple source files, disambiguate somehow?
		$lang = Language::by_name('unknown');
		foreach($filenames as $filename) {
			$file_lang = Language::by_filename($filename);
			if (!$lang->is_code && $file_lang->is_code) {
				$lang = $file_lang;
			} elseif ($lang->is_code && $file_lang->is_code && $lang != $file_lang) {
				// a conflict: what to do?
			}
		}
		return $lang;
	}
}

// -----------------------------------------------------------------------------
// Specialized languages
// -----------------------------------------------------------------------------

class Language_c extends Language {
	public function __construct($name, $compiler, $filename_regex) {
		parent::__construct($name, $compiler, $filename_regex);
	}
	public function should_compile($filename) {
		// Don't compile header files
		return substr($filename,-2) != '.h'
		    && substr($filename,-3) != '.hh'
		    && substr($filename,-4) != '.hpp'
		    && substr($filename,-4) != '.hxx';
	}
}

class Language_any extends Language {
	public function __construct() {
		parent::__construct('any', '', '', false);
	}
	public function adapt_to_filenames($filenames) {
		// determine the actual language based on the filenames
		return Language::by_filenames($filenames);
	}
}

// -----------------------------------------------------------------------------
// Known languages
// -----------------------------------------------------------------------------

global $languages;
$languages = array(
	'c'       => new Language_c('c',       'c',       '.*\.(c|h)'),
	'c++'     => new Language_c('c++',     'c++',     '.*\.(c|h|c++|cc|cxx|cpp|h++|hh|hcc|hpp'),
	'java'    => new Language  ('java',    'java',    '.*\.(java)'),
	'haskell' => new Language  ('haskell', 'haskell', '.*\.(hs|lhs)'),
	'matlab-script'   => new Language('matlab (script)',   'matlab',  '.*\.(m)'),
	'matlab-function' => new Language('matlab (function)', 'matlab-function', '.*\.(m)'),
	// magic
	'any'     => new Language_any('any'),
	'unknown' => new Language('unknown', '', '', false),
);
$languages['']       = $languages['any'];
$languages['cpp']    = $languages['c++'];
$languages['matlab'] = $languages['matlab-script'];
$languages['octave'] = $languages['matlab-script'];
$languages['matlab (script)'] = $languages['matlab-script'];
$languages['matlab (function)'] = $languages['matlab-function'];

// extension -> language
global $languages_by_extension;
$languages_by_extension = array(
	'c'    => $languages['c'],
	'cc'   => $languages['c++'],
	'cxx'  => $languages['c++'],
	'cpp'  => $languages['c++'],
	'c++'  => $languages['c++'],
	'java' => $languages['java'],
	'hs'   => $languages['haskell'],
	'lhs'  => $languages['haskell'],
	'm'    => $languages['matlab'],
	//'zip'  => $languages['zip'],
);
