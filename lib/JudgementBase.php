<?php

require_once('../lib/DateRange.php');

// -----------------------------------------------------------------------------
// Judgement base : compiling & running submissions and reference implementations
//
// This class is used from the backend, not from the webserver.
//
// This class does all the heavy lifting for compiling and running submissions.
// The main entry point is prepare_and_compile(), which does as the name suggests.
// 
// -----------------------------------------------------------------------------

function make_file_readable($file) {
	chmod($file,0644);
}
function make_file_executable($file) {
	chmod($file,0755);
}
function make_file_writable($file) {
	touch($file);
	chmod($file,0666);
}

abstract class JudgementBase {
	protected $entity;
	// language of the submission
	private $language;
	// directory for temp files
	private $tempdir;
	// filenames of temporary files
	private $source_files; // array of temp names
	private $exe_file;
	
	// ---------------------------------------------------------------------
	// Abstract interface (derived classes should override)
	// ---------------------------------------------------------------------
	
	// Should return an array of ('filename' => 'contents) for all source files
	protected abstract function get_source_files();
	protected abstract function put_output_file_contents($file,$contents);
	
	
	// ---------------------------------------------------------------------
	// Wrapper functions
	// ---------------------------------------------------------------------
	
	function __construct($entity) {
		$this->entity = $entity;
	}
	
	function __destruct() {
		if (isset($this->tempdir)) {
			$this->tempdir->__destruct();
			unset($this->tempdir);
		}
	}
	
	// Prepare the submission for judging: compile it
	// returns 0 if success, otherwise return some Status code
	protected function prepare_and_compile() {
		// do we need to compile at all?
		if (!$this->entity->compile()) {
			return Status::PASSED_DEFAULT;
		}
		
		if (!$this->create_tempdir()) {
			throw new InternalException("Failed to create tempdir");
			return Status::FAILED_INTERNAL;
		}
		if (!$this->download_source()) {
			throw new Exception("Failed to download submission source");
			return Status::FAILED_INTERNAL;
		}
		if (!$this->determine_language()) {
			return Status::FAILED_LANGUAGE;
		}
		if (!$this->prepare_files()) {
			return Status::FAILED_INTERNAL;
		}
		if (!$this->compile()) {
			return Status::FAILED_COMPILE;
		}
		return 0;
	}
	
	public function get_and_keep_tempdir() {
		if (isset($this->tempdir)) {
			return $this->tempdir->get_and_keep();
		} else {
			return "";
		}
	}
	public function get_source_file_names() {
		return $this->source_files;
	}
	public function get_exe_file_name() {
		return $this->exe_file;
	}
	
	// ---------------------------------------------------------------------
	// Steps in the judging
	// ---------------------------------------------------------------------
	
	// What language is the source code in? store in $this->language
	protected function determine_language() {
		$this->language = $this->entity->language()->adapt_to_filenames($this->source_files);
		// not a known programming language -> failure
		return $this->language->is_code;
	}
	
	protected function create_tempdir() {
		// create temporary directory
		$this->tempdir = new Tempdir('','judge');
		if (!file_exists($this->tempdir->dir)) return false;
		chmod($this->tempdir->dir,0755);
		return true;
	}
	
	// Store source in tempdir
	protected function download_source() {
		$files = $this->get_source_files();
		if (empty($files)) {
			throw new Exception("Submission has no source files");
		}
		$this->source_files = array();
		foreach($files as $name => $contents) {
			$this->tempdir->create_parent_dirs($name);
			$temp_name = $this->tempdir->file($name);
			// write files
			if ($contents === false) return false;
			if (file_put_contents($temp_name, $contents) === false) {
				throw new InternalException("Failed to write submission source file");
			}
			$this->source_files []= $temp_name;
		}
		return true;
	}
	
	// Store other files in tempdir
	protected function prepare_files() {
		// copy provided files
		$files_to_copy = $this->entity->compiler_files();
		foreach ($files_to_copy as $filename) {
			$this->tempdir->create_parent_dirs($filename);
			$local_name = $this->tempdir->file($filename);
			// error if file can't be copied
			if (!copy($this->entity->data_path() . $filename, $local_name)) {
				throw new Exception("Failed to copy file: $filename");
			}
			echo "copying $local_name\n";
			make_file_readable($local_name);
		}
		
		// Make some files writable
		$files_to_touch = $this->entity->writable_files();
                foreach ($files_to_touch as $filename) {
                        $local_name = $this->tempdir->file($filename);
                        touch($local_name);
                        echo "making $local_name writable\n";
                        make_file_writable($local_name);
                }
                
                return true;
	}
	
	// Compile $source_files to $exe_file
	protected function compile() {
		// compiler script to use
		$compiler = $this->entity->compiler();
		if ($compiler == '') $compiler = $this->language->compiler;
		$compiler = getcwd() . "/compilers/$compiler.sh";
		// flags?
		$flags = $this->entity->compiler_flags();
		
		// which files to compile?
		$compiled_files = array();
		foreach ($this->source_files as $file) {
			if ($this->language->should_compile($file)) {
				$compiled_files []= $file;
			}
			// the compiler needs read permissions for all source files
			make_file_readable($file);
		}
		$compiled_files_list = implode(' ',$compiled_files); // space separated list of filenames
		
		// files produced by the compiler, they must be writable
		$this->exe_file = $this->tempdir->file('compiled_program.exe');
		$compile_err_file = $this->tempdir->file('compiler.err');
		
		// compile
		$limits = $this->entity->compile_limits();
		unset($limits['as nobody']); // run compiler under our own userid
		// the java compile script needs to know the runtime memory limit
		$run_limits = $this->entity->run_limits();
		$args = array($compiled_files_list, $this->exe_file, $compile_err_file, $flags, $run_limits['memory limit']);
		$result = SystemUtil::safe_command($this->tempdir->dir, $compiler, $args, $limits);
		
		// did compilation succeed?
		if ($result && !file_exists($this->exe_file)) {
			if (file_exists($this->exe_file . ".sh")) {
				// compiler scripts may decide to generate a .sh script instead
				// this is useful on windows, because there the file extension determines how the program is run
				echo "(Detecting windows shell script workaround: output renamed to .sh)\n";
				$this->exe_file .= ".sh";
			} else {
				// no executable was produced
				$result = false;
				file_put_contents($compile_err_file,"No executable file was generated.\n",FILE_APPEND);
			}
		}
		if (!$result) {
			$this->put_tempfile('compiler.err');
		} else {
			make_file_executable($this->exe_file);
		}
		return $result;
	}
	
	// Run with input from $case
	protected function run_case($case) {
		// runner
		$runner = $this->entity->runner();
		$runner = getcwd() . "/runners/$runner.sh";
		$flags = $this->entity->runner_flags();
		
		// copy case input, prepare output files
		$case_input  = $this->tempdir->file("$case.in");
		$case_output = $this->tempdir->file("$case.out");
		$case_error  = $this->tempdir->file("$case.err");
		$case_limit_error = $this->tempdir->file("$case.limit-err");
		copy($this->entity->testcase_input($case), $case_input);
		make_file_readable($case_input);
		make_file_writable($case_output);
		make_file_writable($case_error);
		make_file_writable($case_limit_error);
		
		// run program
		$limits = $this->entity->run_limits();
		$result = SystemUtil::safe_command($this->tempdir->dir, $runner, array($this->exe_file, $case_input, $case_output, $case_error, $flags), $limits, $case_limit_error);
		if (!file_exists($case_output)) {
			file_put_contents($case_output, "<<NO OUTPUT FILE CREATED>>");
			$result = false;
			echo "     No output file created\n";
		}
		if (!$result && file_exists($case_limit_error) && filesize($case_limit_error) > 0) {
			// use limit error message as error output
			copy($case_limit_error, $case_error);
		}
		
		// store results
		$this->put_tempfile("$case.out");
		$this->put_tempfile("$case.err");
		return $result;
	}
	
	// Compare the output of a testcase agains the reference output
	protected function check_case($case) {
		// checker
		$checker = $this->entity->checker();
		$checker = getcwd() . "/checkers/$checker.sh";
		$flags = $this->entity->checker_flags();
		
		// the files
		$case_ref  = $this->entity->testcase_reference_output($case);
		$case_my   = $this->tempdir->file("$case.out");
		$case_diff = $this->tempdir->file("$case.diff");
		make_file_writable($case_diff);
		if (!file_exists($case_ref)) {
			throw new Exception("Reference implementation does not exists:\n$case_ref");
		}
		
		// run checker
		$result = SystemUtil::run_command($this->tempdir->dir, $checker, array($case_my, $case_ref, $case_diff, $flags));
		if (!$result) {
			$this->put_tempfile("$case.diff");
			echo "     Output does not match\n";
		}
		return $result;
	}
	
	// Store a file from the tempdir
	protected function put_tempfile($file) {
		// read file
		$filename = $this->tempdir->file($file);
		// limit to use
		$max_file_size = intval($this->entity->filesize_limit());
		if ($max_file_size > GLOBAL_FILESIZE_LIMIT) $max_file_size = GLOBAL_FILESIZE_LIMIT;
		$read_limit = $this->should_truncate_files()
			? $max_file_size + 1 // read 1 more than max size, so we can detect files that are too large
			: GLOBAL_FILESIZE_LIMIT + 1; // *always* limit the filesize, to prevent out-of-memory errors
		// for some reason limiting the filesize convinces php to always allocate a buffer that large, so we hack around that
		$actual_file_size = filesize($filename);
		if ($actual_file_size < $read_limit) $read_limit = $actual_file_size;
		$contents = file_get_contents($filename, 0,null,0, $read_limit);
		// check the file size
		$content_size = strlen($contents);
		if ($content_size > $max_file_size) {
			// don't allow files to be too large
			$content_size = filesize($filename); // the actual contents size
			$contents = $this->truncate_file($file, $contents,$max_file_size,$content_size);
		}
		// store the file
		$this->put_output_file_contents($file,$contents);
		// try to clean up
		unset($file);
		unset($contents);
	}
	
	// Trim files that are too large
	protected function truncate_file($file, $contents, $max_file_size, $actual_file_size) {
		echo "Putting file $file of size: $actual_file_size, while max is $max_file_size\n";
		$contents = substr($contents,0,$max_file_size) . "\n[[FILE TOO LARGE]]";
	}
	protected function should_truncate_files() {
		return true;
	}
	
}
