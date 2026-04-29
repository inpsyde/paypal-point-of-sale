<?php

namespace {

use cli\Colors;
use Mustangostang\Spyc;
use WP_CLI\Configurator;
use WP_CLI\Dispatcher;
use WP_CLI\Dispatcher\CommandAddition;
use WP_CLI\Dispatcher\CommandFactory;
use WP_CLI\Dispatcher\DisabledCommand;
use WP_CLI\Dispatcher\CommandNamespace;
use WP_CLI\Dispatcher\CompositeCommand;
use WP_CLI\Dispatcher\RootCommand;
use WP_CLI\DocParser;
use WP_CLI\ExitException;
use WP_CLI\FileCache;
use WP_CLI\Loggers\Execution;
use WP_CLI\Path;
use WP_CLI\Process;
use WP_CLI\ProcessRun;
use WP_CLI\Runner;
use WP_CLI\SynopsisParser;
use WP_CLI\Utils;
use WP_CLI\WpHttpCacheManager;

/**
 * Various utilities for WP-CLI commands.
 *
 * @phpstan-type GlobalConfig array{path: string|null, ssh: string|null, 'ssh-args': string[], http: string|null, url: string|null, user: string|null, 'skip-plugins': true|string[], 'skip-themes': true|string[], 'skip-packages': bool, require: string[], exec: string[], context: string, debug: string|true, prompt: false|string, quiet: bool, apache_modules: string[], 'assume-https': bool}
 *
 * @phpstan-type FlagParameter array{type: 'flag', name: string, description?: string, optional?: bool, repeating?: bool, aliases?: string[]}
 * @phpstan-type AssocParameter array{type: 'assoc', name: string, description?: string, options?: string[], default?: string, optional?: bool, value: array{optional: bool, name?: string}, repeating?: bool, aliases?: string[]}
 * @phpstan-type PositionalParameter array{type: 'positional', name: string, description?: string, optional?: bool, repeating?: bool}
 * @phpstan-type GenericParameter array{type: 'generic', optional?: bool, repeating?: bool}
 * @phpstan-type UnknownParameter array{type:'unknown', optional?: bool, repeating?: bool}
 * @phpstan-type CommandSynopsis FlagParameter|AssocParameter|PositionalParameter|GenericParameter|UnknownParameter
 */
class WP_CLI {

	/**
	 * Set the logger instance.
	 *
	 * @param object $logger Logger instance to set.
	 */
	public static function set_logger( $logger ) {
	}

	/**
	 * Get the logger instance.
	 *
	 * @return object $logger Logger instance.
	 */
	public static function get_logger() {
	}

	/**
	 * Get the Configurator instance
	 *
	 * @return Configurator
	 */
	public static function get_configurator() {
	}

	/**
	 * @return RootCommand
	 */
	public static function get_root_command() {
	}

	public static function get_runner() {
	}

	/**
	 * @return FileCache
	 */
	public static function get_cache() {
	}

	/**
	 * Set the context in which WP-CLI should be run
	 */
	public static function set_url( $url ) {
	}

	/**
	 * @return WpHttpCacheManager
	 */
	public static function get_http_cache_manager() {
	}

	/**
	 * Colorize a string for output.
	 *
	 * @access public
	 * @category Output
	 *
	 * @param string $string String to colorize for output, with color tokens.
	 * @return string Colorized string.
	 */
	public static function colorize( $string ) {
	}

	/**
	 * Schedule a callback to be executed at a certain point.
	 *
	 * @access public
	 * @category Registration
	 *
	 * @param string   $when     Identifier for the hook.
	 * @param callable $callback Callback to execute when hook is called.
	 * @return void
	 */
	public static function add_hook( $when, $callback ) {
	}

	/**
	 * Execute callbacks registered to a given hook.
	 *
	 * @access public
	 * @category Registration
	 *
	 * @param string $when    Identifier for the hook.
	 * @param mixed  ...$args Optional. Arguments that will be passed onto the
	 *                        callback provided by `WP_CLI::add_hook()`.
	 * @return null|mixed Returns the first optional argument if optional
	 *                    arguments were passed, otherwise returns null.
	 */
	public static function do_hook( $when, ...$args ) {
	}

	/**
	 * Add a callback to a WordPress action or filter.
	 *
	 * @access public
	 * @category Registration
	 *
	 * @param string   $tag             Named WordPress action or filter.
	 * @param callable $function_to_add Callable to execute when the action or filter is evaluated.
	 * @param integer  $priority        Priority to add the callback as.
	 * @param integer  $accepted_args   Number of arguments to pass to callback.
	 * @return true
	 */
	public static function add_wp_hook( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
	}

	/**
	 * Register a command to WP-CLI.
	 *
	 * @access public
	 * @category Registration
	 *
	 * @param string                          $name     Name for the command (e.g. "post list" or "site empty").
	 * @param callable|object|string|string[] $callable Command implementation as a class, function or closure.
	 * @param array                           $args {
	 *    Optional. An associative array with additional registration parameters.
	 *
	 *    @type callable $before_invoke Callback to execute before invoking the command.
	 *    @type callable $after_invoke  Callback to execute after invoking the command.
	 *    @type string   $shortdesc     Short description (80 char or less) for the command.
	 *    @type string   $longdesc      Description of arbitrary length for examples, etc.
	 *    @type string   $synopsis      The synopsis for the command (string or array).
	 *    @type string   $when          Execute callback on a named WP-CLI hook (e.g. before_wp_load).
	 *    @type bool     $is_deferred   Whether the command addition had already been deferred.
	 * }
	 * @return bool True on success, false if deferred, hard error if registration failed.
	 *
	 * @phpstan-param array{before_invoke?: callable, after_invoke?: callable, shortdesc?: string, longdesc?: string, synopsis?: string|CommandSynopsis[], when?: string, is_deferred?: bool} $args
	 */
	public static function add_command( $name, $callable, $args = [] ) {
	}

	/**
	 * Get the list of outstanding deferred command additions.
	 *
	 * @return array Array of outstanding command additions.
	 */
	public static function get_deferred_additions() {
	}

	/**
	 * Remove a command addition from the list of outstanding deferred additions.
	 */
	public static function remove_deferred_addition( $name ) {
	}

	/**
	 * Check if a command's arguments conflict with global arguments.
	 *
	 * @param string                $command_name The name of the command being registered.
	 * @param Dispatcher\Subcommand $command      The command object to check.
	 */
	public static function check_global_arg_conflicts( $command_name, $command ) {
	}

	/**
	 * Display informational message without prefix, and ignore `--quiet`.
	 *
	 * @access public
	 * @category Output
	 *
	 * @param string $message Message to display to the end user.
	 * @param bool   $newline Optional. Whether to append a newline to the end of the message. Default true.
	 * @return void
	 */
	public static function line( $message = '', $newline = true ) {
	}

	/**
	 * Display informational message without prefix.
	 *
	 * @access public
	 * @category Output
	 *
	 * @param string $message Message to write to STDOUT.
	 * @param bool   $newline Optional. Whether to append a newline to the end of the message. Default true.
	 */
	public static function log( $message, $newline = true ) {
	}

	/**
	 * Display success message prefixed with "Success: ".
	 *
	 * @access public
	 * @category Output
	 *
	 * @param string $message Message to write to STDOUT.
	 * @return void
	 */
	public static function success( $message ) {
	}

	/**
	 * Display debug message prefixed with "Debug: " when `--debug` is used.
	 *
	 * @access public
	 * @category Output
	 *
	 * @param string|WP_Error|Exception|Throwable $message Message to write to STDERR.
	 * @param string|bool $group Organize debug message to a specific group.
	 * Use `false` to not group the message.
	 * @return void
	 */
	public static function debug( $message, $group = false ) {
	}

	/**
	 * Display warning message prefixed with "Warning: ".
	 *
	 * @access public
	 * @category Output
	 *
	 * @param string|WP_Error|Exception|Throwable $message Message to write to STDERR.
	 * @return void
	 */
	public static function warning( $message ) {
	}

	/**
	 * Display error message prefixed with "Error: " and exit script.
	 *
	 * @access public
	 * @category Output
	 *
	 * @param string|WP_Error|Exception|Throwable $message Message to write to STDERR.
	 * @param boolean|int                         $exit    True defaults to exit(1).
	 * @return null
	 *
	 * @phpstan-return ($exit is true|positive-int ? never : void)
	 */
	public static function error( $message, $exit = true ) {
	}

	/**
	 * Halt script execution with a specific return code.
	 *
	 * @access public
	 * @category Output
	 *
	 * @param integer $return_code
	 * @return never
	 */
	public static function halt( $return_code ) {
	}

	/**
	 * Display a multi-line error message in a red box. Doesn't exit script.
	 *
	 * @access public
	 * @category Output
	 *
	 * @param array<string|\WP_Error|\Exception|\Throwable> $message_lines Multi-line error message to be displayed.
	 */
	public static function error_multi_line( $message_lines ) {
	}

	/**
	 * Ask for confirmation before running a destructive operation.
	 *
	 * @access public
	 * @category Input
	 *
	 * @param string $question Question to display before the prompt.
	 * @param array $assoc_args Skips prompt if 'yes' is provided.
	 */
	public static function confirm( $question, $assoc_args = [] ) {
	}

	/**
	 * Read value from a positional argument or from STDIN.
	 *
	 * @param array $args The list of positional arguments.
	 * @param int $index At which position to check for the value.
	 *
	 * @return string
	 */
	public static function get_value_from_arg_or_stdin( $args, $index ) {
	}

	/**
	 * Read a value, from various formats.
	 *
	 * @access public
	 * @category Input
	 *
	 * @param string $raw_value
	 * @param array $assoc_args
	 */
	public static function read_value( $raw_value, $assoc_args = [] ) {
	}

	/**
	 * Display a value, in various formats
	 *
	 * @param mixed $value Value to display.
	 * @param array $assoc_args Arguments passed to the command, determining format.
	 */
	public static function print_value( $value, $assoc_args = [] ) {
	}

	/**
	 * Convert a WP_Error or Exception into a string
	 *
	 * @param string|WP_Error|Exception|Throwable $errors
	 * @throws InvalidArgumentException
	 *
	 * @return string
	 */
	public static function error_to_string( $errors ) {
	}

	/**
	 * Launch an arbitrary external process that takes over I/O.
	 *
	 * @access public
	 * @category Execution
	 *
	 * @param string $command External process to launch.
	 * @param boolean $exit_on_error Whether to exit if the command returns an elevated return code.
	 * @param boolean $return_detailed Whether to return an exit status (default) or detailed execution results.
	 * @return int|ProcessRun The command exit status, or a ProcessRun object for full details.
	 *
	 * @phpstan-return ($return_detailed is true ? ProcessRun : int)
	 */
	public static function launch( $command, $exit_on_error = true, $return_detailed = false ) {
	}

	/**
	 * Run a WP-CLI command in a new process reusing the current runtime arguments.
	 *
	 * @access public
	 * @category Execution
	 *
	 * @param string $command WP-CLI command to call.
	 * @param array $args Positional arguments to include when calling the command.
	 * @param array $assoc_args Associative arguments to include when calling the command.
	 * @param bool $exit_on_error Whether to exit if the command returns an elevated return code.
	 * @param bool $return_detailed Whether to return an exit status (default) or detailed execution results.
	 * @param array $runtime_args Override one or more global args (path,url,user,allow-root)
	 * @return int|ProcessRun The command exit status, or a ProcessRun instance
	 *
	 * @phpstan-return ($return_detailed is false ? int : ProcessRun)
	 */
	public static function launch_self( $command, $args = [], $assoc_args = [], $exit_on_error = true, $return_detailed = false, $runtime_args = [] ) {
	}

	/**
	 * Get the path to the PHP binary used when executing WP-CLI.
	 *
	 * Note: moved to Utils, left for BC.
	 *
	 * @access public
	 * @category System
	 *
	 * @return string
	 */
	public static function get_php_binary() {
	}

	/**
	 * Confirm that a global configuration parameter does exist.
	 *
	 * @access public
	 * @category Input
	 *
	 * @param string $key Config parameter key to check.
	 *
	 * @return bool
	 *
	 * @phpstan-param key-of<GlobalConfig> $key
	 */
	public static function has_config( $key ) {
	}

	/**
	 * Get values of global configuration parameters.
	 *
	 * @access public
	 * @category Input
	 *
	 * @param string $key Get value for a specific global configuration parameter.
	 * @return mixed
	 *
	 * @phpstan-param key-of<GlobalConfig> $key
	 * @phpstan-return ($key is null ? GlobalConfig : value-of<GlobalConfig>)
	 */
	public static function get_config( $key = null ) {
	}

	/**
	 * Run a WP-CLI command.
	 *
	 * @access public
	 * @category Execution
	 *
	 * @param string $command WP-CLI command to run, including arguments.
	 * @param array  $options {
	 *     Configuration options for command execution.
	 *
	 *     @type bool        $launch       Launches a new process (true) or reuses the existing process (false). Default: true.
	 *     @type bool        $exit_error   Halts the script on error. Default: true.
	 *     @type bool|string $return       Returns output as an object when set to 'all' (string), return just the 'stdout', 'stderr', or 'return_code' (string) of command, or print directly to stdout/stderr (false). Default: false.
	 *     @type bool|string $parse        Parse returned output as 'json' (string); otherwise, output is unchanged (false). Default: false.
	 *     @type array       $command_args Contains additional command line arguments for the command. Each element represents a single argument. Default: empty array.
	 * }
	 * @return mixed
	 */
	public static function runcommand( $command, $options = [] ) {
	}

	/**
	 * Run a given command within the current process using the same global
	 * parameters.
	 *
	 * @access public
	 * @category Execution
	 *
	 * @param array $args Positional arguments including command name.
	 * @param array $assoc_args
	 */
	public static function run_command( $args, $assoc_args = [] ) {
	}

	// DEPRECATED STUFF.

	public static function add_man_dir() {
	}

	// back-compat.
	public static function out( $str ) {
	}

	// back-compat.
	public static function addCommand( $name, $class ) {
	}
}
}

namespace WP_CLI {

    use Exception;

    class ExitException extends Exception
    {
    }
}

namespace WP_CLI\Utils {

    use ArrayIterator;
    use cli;
    use cli\progress\Bar;
    use cli\Shell;
    use Closure;
    use Composer\Semver\Comparator;
    use Composer\Semver\Semver;
    use Exception;
    use Iterator;
    use Mustache\Engine as Mustache_Engine;
    use ReflectionFunction;
    use RuntimeException;
    use WP_CLI;
    use WP_CLI\ExitException;
    use WP_CLI\Formatter;
    use WP_CLI\Inflector;
    use WP_CLI\Iterators\Transform;
    use WP_CLI\NoOp;
    use WP_CLI\Path;
    use WP_CLI\Process;
    use WP_CLI\RequestsLibrary;
    use WpOrg\Requests\Response;

    /**
     * File stream wrapper prefix for Phar archives.
     *
     * @var string
     */
    const PHAR_STREAM_PREFIX = 'phar://';

    /**
     * Regular expression pattern to match __FILE__ and __DIR__ constants.
     *
     * We try to be smart and only replace the constants when they are not within quotes.
     * Regular expressions being stateless, this is probably not 100% correct for edge cases.
     *
     * @see https://regex101.com/r/9hXp5d/11
     * @see https://stackoverflow.com/a/171499/933065
     *
     * @var string
     */
    const FILE_DIR_PATTERN = '%(?>#.*?$)|(?>//.*?$)|(?>/\*.*?\*/)|(?>\'(?:(?=(\\\\?))\1.)*?\')|(?>"(?:(?=(\\\\?))\2.)*?")|(?<file>\b__FILE__\b)|(?<dir>\b__DIR__\b)%ms';

    /**
     * Check if a certain path is within a Phar archive.
     *
     * If no path is provided, the function checks whether the current WP_CLI instance is
     * running from within a Phar archive.
     *
     * @deprecated 2.13.0 Use Path::inside_phar() instead.
     *
     * @param string|null $path Optional. Path to check. Defaults to null, which checks WP_CLI_ROOT.
     * @return bool Whether path is within a Phar archive.
     */
    function inside_phar( $path = null ) {
    }

    /**
     * Extract a file from a Phar archive.
     *
     * Files that need to be read by external programs have to be extracted from the Phar archive.
     * If the file is not within a Phar archive, the function returns the path unchanged.
     *
     * @param string $path Path to the file to extract.
     * @return string Path to the extracted file.
     */
    function extract_from_phar( $path ) {
    }

    /**
     * Load dependencies.
     *
     * @return void|never
     */
    function load_dependencies() {
    }

    /**
     * Return vendor paths.
     *
     * @return array<string> List of paths.
     */
    function get_vendor_paths() {
    }

    /**
     * Load a file.
     *
     * Using require() directly inside a class grants access
     * to private methods to the loaded code, hence this wrapper helper.
     *
     * @param string $path
     * @return void
     */
    function load_file( $path ) {
    }

    /**
     * Load a command.
     *
     * @param string $name
     * @return void
     */
    function load_command( $name ) {
    }

    /**
     * Like array_map(), except it returns a new iterator, instead of a modified array.
     *
     * @param array|Iterator $it     Either a plain array or another iterator.
     * @param callable       ...$fns The function to apply to an element.
     * @return Iterator An iterator that applies the given callback(s).
     */
    function iterator_map( $it, ...$fns ) {
    }

    /**
     * Check if a path is within open_basedir restrictions.
     *
     * @param string $path The path to check (should be absolute).
     * @return bool True if the path is accessible (no open_basedir or within allowed paths), false otherwise.
     */
    function is_path_within_open_basedir( $path ) {
    }

    /**
     * Search for file by walking up the directory tree until the first file is found or until $stop_check($dir) returns true.
     *
     * @param string|array<string> $files      The files (or file) to search for.
     * @param string|null          $dir        The directory to start searching from; defaults to CWD.
     * @param callable             $stop_check Function which is passed the current dir each time a directory level is traversed.
     * @return null|string Null if the file was not found.
     */
    function find_file_upward( $files, $dir = null, $stop_check = null ) {
    }

    /**
     * Determine whether a path is absolute.
     *
     * @deprecated 2.13.0 Use Path::is_absolute() instead.
     *
     * @param string $path
     * @return bool
     */
    function is_path_absolute( $path ) {
    }

    /**
     * Expand tilde (~) in path to home directory.
     *
     * @deprecated 2.13.0 Use Path::expand_tilde() instead.
     *
     * @param string $path Path that may contain a tilde.
     * @return string Path with tilde expanded to home directory, or unchanged if tilde not at start or followed by username.
     */
    function expand_tilde_path( $path ) {
    }

    /**
     * Escape a shell argument while preserving tilde expansion.
     *
     * @param string $arg The argument to escape.
     * @return string The escaped argument.
     */
    function escapeshellarg_preserve_tilde( $arg ) {
    }

    /**
     * Composes positional arguments into a command string.
     *
     * @param array<string> $args Positional arguments to compose.
     * @return string
     */
    function args_to_str( $args ) {
    }

    /**
     * Composes associative arguments into a command string.
     *
     * @param array<string, mixed> $assoc_args Associative arguments to compose.
     * @param array<string> $sensitive_args Optional. Array of argument keys that should be masked.
     * @return string
     */
    function assoc_args_to_str( $assoc_args, $sensitive_args = [] ) {
    }

    /**
     * Given a template string and an arbitrary number of arguments,
     * returns the final command, with the parameters escaped.
     *
     * @param string $cmd
     * @param string ...$args
     */
    function esc_cmd( $cmd, ...$args ) {
    }

    /**
     * Gets path to WordPress configuration.
     *
     * @return string
     */
    function locate_wp_config() {
    }

    /**
     * Compare a WordPress version.
     *
     * @param string $since
     * @param string $operator
     * @return bool
     */
    function wp_version_compare( $since, $operator ) {
    }

    /**
     * Render a collection of items as an ASCII table, JSON, CSV, YAML, list of ids, or count.
     *
     * @access public
     * @category Output
     *
     * @param string       $format Format to use: 'table', 'json', 'csv', 'yaml', 'ids', 'count'.
     * @param array<mixed> $items  An array of items to output.
     * @param array<string>|string $fields Named fields for each item of data. Can be array or comma-separated list.
     */
    function format_items( $format, $items, $fields ) {
    }

    /**
     * Write data as CSV to a given file.
     *
     * @access public
     *
     * @param resource                 $fd      File descriptor.
     * @param array<string[]>|iterable $rows    Array of rows to output.
     * @param array<string>            $headers List of CSV columns (optional).
     */
    function write_csv( $fd, $rows, $headers = [] ) {
    }

    /**
     * Pick fields from an associative array or object.
     *
     * @param array<string, mixed>|object $item   Associative array or object to pick fields from.
     * @param array<string>               $fields List of fields to pick.
     * @return array<string, mixed>
     */
    function pick_fields( $item, $fields ) {
    }

    /**
     * Launch system's $EDITOR for the user to edit some text.
     *
     * @access public
     * @category Input
     *
     * @param string $input Some form of text to edit (e.g. post content).
     * @param string $title Title to display in the editor.
     * @param string $ext   Extension to use with the temp file.
     * @return string|bool  Edited text, if file is saved from editor; false, if no change to file.
     */
    function launch_editor_for_input( $input, $title = 'WP-CLI', $ext = 'tmp' ) {
    }

    /**
     * @param string $raw_host MySQL host string, as defined in wp-config.php.
     *
     * @return array<string, string|int>
     */
    function mysql_host_to_cli_args( $raw_host ) {
    }

    /**
     * Run a MySQL command and optionally return the output.
     *
     * @since v2.5.0 Deprecated $descriptors argument.
     *
     * @param string                $cmd           Command to run.
     * @param array<string, string> $assoc_args    Associative array of arguments to use.
     * @param mixed                 $_             Deprecated. Former $descriptors argument.
     * @param bool                  $send_to_shell Optional. Whether to send STDOUT and STDERR
     *                                             immediately to the shell. Defaults to true.
     * @param bool                  $interactive   Optional. Whether MySQL is meant to be
     *                                             executed as an interactive process. Defaults
     *                                             to false.
     *
     * @return array {
     *     Associative array containing STDOUT and STDERR output.
     *
     *     @type string $stdout    Output that was sent to STDOUT.
     *     @type string $stderr    Output that was sent to STDERR.
     *     @type int    $exit_code Exit code of the process.
     * }
     */
    function run_mysql_command( $cmd, $assoc_args, $_ = null, $send_to_shell = true, $interactive = false ) {
    }

    /**
     * Render PHP or other types of files using Mustache templates.
     *
     * IMPORTANT: Automatic HTML escaping is disabled!
     *
     * @param string               $template_name
     * @param array<string, mixed> $data
     */
    function mustache_render( $template_name, $data = [] ) {
    }

    /**
     * Create a progress bar to display percent completion of a given operation.
     *
     * @access public
     * @category Output
     *
     * @param string  $message  Text to display before the progress bar.
     * @param integer $count    Total number of ticks to be performed.
     * @param int     $interval Optional. The interval in milliseconds between updates. Default 100.
     * @return \cli\progress\Bar|\WP_CLI\NoOp
     */
    function make_progress_bar( $message, $count, $interval = 100 ) {
    }

    /**
     * Helper function to use wp_parse_url when available or fall back to PHP's
     * parse_url if not.
     *
     * @param string $url             The URL to parse.
     * @param int    $component       Optional. The specific component to retrieve.
     * @param bool   $auto_add_scheme Optional. Automatically add an http:// scheme if
     *                                none was found. Defaults to true.
     * @return mixed False on parse failure; Array of URL components on success;
     *               When a specific component has been requested: null if the
     *               component doesn't exist in the given URL; a string or - in the
     *               case of PHP_URL_PORT - integer when it does. See parse_url()'s
     *               return values.
     *
     * @phpstan-return ($component is non-negative-int ? string|null|int|false : array{scheme?: string, host?: string, port?: int, user?: string, pass?: string, query?: string, path?: string, fragment?: string})
     */
    function parse_url( $url, $component = - 1, $auto_add_scheme = true ) {
    }

    /**
     * Check if we're running in a Windows environment (cmd.exe).
     *
     * @return bool
     */
    function is_windows() {
    }

    /**
     * Replace magic constants in some PHP source code.
     *
     * @deprecated 2.13.0 Use Path::replace_path_consts() instead.
     *
     * @param string $source The PHP code to manipulate.
     * @param string $path The path to use instead of the magic constants.
     * @return string Adapted PHP code.
     */
    function replace_path_consts( $source, $path ) {
    }

    /**
     * Make a HTTP request to a remote URL.
     *
     * @access public
     *
     * @param string     $method  HTTP method (GET, POST, DELETE, etc.).
     * @param string     $url     URL to make the HTTP request to.
     * @param array|null $data    Data to send either as a query string for GET/HEAD requests,
     *                            or in the body for POST requests.
     * @param array      $headers Add specific headers to the request.
     * @param array      $options {
     *     Optional. An associative array of additional request options.
     *
     *     @type bool $halt_on_error Whether or not command execution should be halted on error. Default: true
     *     @type bool|string $verify A boolean to use enable/disable SSL verification
     *                               or string absolute path to CA cert to use.
     *                               Defaults to detected CA cert bundled with the Requests library.
     *     @type bool $insecure      Whether to retry automatically without certificate validation.
     *     @type int  $max_retries   Maximum number of retries of failed requests. Default 3.
     * }
     * @return \Requests_Response|Response
     * @throws RuntimeException If the request failed.
     * @throws ExitException If the request failed and $halt_on_error is true.
     *
     * @phpstan-param array{halt_on_error?: bool, verify?: bool|string, insecure?: bool} $options
     */
    function http_request( $method, $url, $data = null, $headers = [], $options = [] ) {
    }

    /**
     * Gets the full path to the default CA cert.
     *
     * @param bool $halt_on_error Whether or not command execution should be halted on error. Default: false
     * @return string Absolute path to the default CA cert.
     * @throws RuntimeException If unable to locate the cert.
     * @throws ExitException If unable to locate the cert and $halt_on_error is true.
     */
    function get_default_cacert( $halt_on_error = false ) {
    }

    /**
     * Increments a version string using the "x.y.z-pre" format.
     *
     * @param string $current_version
     * @param string $new_version
     * @return string
     */
    function increment_version( $current_version, $new_version ) {
    }

    /**
     * Compare two version strings to get the named semantic version.
     *
     * @access public
     *
     * @param string $new_version
     * @param string $original_version
     * @return string 'major', 'minor', 'patch'
     */
    function get_named_sem_ver( $new_version, $original_version ) {
    }

    /**
     * Return the flag value or, if it's not set, the $default value.
     *
     * @access public
     * @category Input
     *
     * @param array<string|int,string|bool> $assoc_args Arguments array.
     * @param string|int                    $flag       Flag to get the value.
     * @param string|bool|int|null          $default    Default value for the flag. Default: NULL.
     * @return string|bool|int|null
     */
    function get_flag_value( $assoc_args, $flag, $default = null ) {
    }

    /**
     * Get the home directory.
     *
     * @deprecated 2.13.0 Use Path::get_home_dir() instead.
     *
     * @access public
     * @category System
     *
     * @return string
     */
    function get_home_dir() {
    }

    /**
     * Appends a trailing slash.
     *
     * @deprecated 2.13.0 Use Path::trailingslashit() instead.
     *
     * @access public
     * @category System
     *
     * @param string $string What to add the trailing slash to.
     * @return string String with trailing slash added.
     */
    function trailingslashit( $string ) {
    }

    /**
     * Check if a path is a PHP stream URL.
     *
     * @deprecated 2.13.0 Use Path::is_stream() instead.
     *
     * @access public
     * @category System
     *
     * @param string $path The resource path or URL.
     * @return bool True if the path is a PHP stream URL, false otherwise.
     */
    function is_stream( $path ) {
    }

    /**
     * Normalize a filesystem path.
     *
     * @deprecated 2.13.0 Use Path::normalize() instead.
     *
     * @access public
     * @category System
     *
     * @param string $path Path to normalize.
     * @return string Normalized path.
     */
    function normalize_path( $path ) {
    }

    /**
     * Convert Windows EOLs to *nix.
     *
     * @param string $str String to convert.
     * @return string String with carriage return / newline pairs reduced to newlines.
     */
    function normalize_eols( $str ) {
    }

    /**
     * Get the system's temp directory. Warns user if it isn't writable.
     *
     * @access public
     * @category System
     *
     * @return string
     */
    function get_temp_dir() {
    }

    /**
     * Parse a SSH url for its host, port, and path.
     *
     * @access public
     *
     * @param string $url
     * @param int $component
     * @return mixed
     *
     * @phpstan-return ($component is non-negative-int ? string|null : array{scheme?: string, user?: string, host?: string, port?: string, path?: string})
     */
    function parse_ssh_url( $url, $component = -1 ) {
    }

    /**
     * Report the results of the same operation against multiple resources.
     *
     * @access public
     * @category Input
     *
     * @param string       $noun      Resource being affected (e.g. plugin).
     * @param string       $verb      Type of action happening to the noun (e.g. activate).
     * @param integer      $total     Total number of resource being affected.
     * @param integer      $successes Number of successful operations.
     * @param integer      $failures  Number of failures.
     * @param null|integer $skips     Optional. Number of skipped operations. Default null (don't show skips).
     * @return void
     */
    function report_batch_operation_results( $noun, $verb, $total, $successes, $failures, $skips = null ) {
    }

    /**
     * Parse a string of command line arguments into an $argv-esqe variable.
     *
     * @access public
     * @category Input
     *
     * @param string $arguments
     * @return array<string>
     */
    function parse_str_to_argv( $arguments ) {
    }

    /**
     * Locale-independent version of basename()
     *
     * @deprecated 2.13.0 Use Path::basename() instead.
     *
     * @access public
     *
     * @param string $path
     * @param string $suffix
     * @return string
     */
    function basename( $path, $suffix = '' ) {
    }

    /**
     * Checks whether the output of the current script is a TTY or a pipe / redirect
     *
     * @access public
     *
     * @return bool
     */
    function isPiped() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid -- Renaming would break BC.
    }

    /**
     * Expand within paths to their matching paths.
     *
     * @param string|array<string>  $paths Single path as a string, or an array of paths.
     * @param int|'default'         $flags Optional. Flags to pass to glob. Defaults to GLOB_BRACE.
     * @return array<string> Expanded paths.
     */
    function expand_globs( $paths, $flags = 'default' ) {
    }

    /**
     * Simulate a `glob()` with the `GLOB_BRACE` flag set.
     *
     * @param string $pattern     Filename pattern.
     * @param void   $dummy_flags Not used.
     * @return array<string> Array of paths.
     */
    function glob_brace( $pattern, $dummy_flags = null ) {
    }

    /**
     * Get the closest suggestion for a mistyped target term amongst a list of
     * options.
     *
     * @param string        $target    Target term to get a suggestion for.
     * @param array<string> $options   Array with possible options.
     * @param int           $threshold Threshold above which to return an empty string.
     * @return string
     */
    function get_suggestion( $target, array $options, $threshold = 2 ) {
    }

    /**
     * Get a Phar-safe version of a path.
     *
     * @deprecated 2.13.0 Use Path::phar_safe() instead.
     *
     * @param string $path An absolute path that might be within a Phar.
     * @return string A Phar-safe version of the path.
     */
    function phar_safe_path( $path ) {
    }

    /**
     * Maybe prefix command string with "/usr/bin/env".
     * Removes (if there) if Windows, adds (if not there) if not.
     *
     * @param string $command
     * @return string
     */
    function force_env_on_nix_systems( $command ) {
    }

    /**
     * Check that `proc_open()` and `proc_close()` haven't been disabled.
     *
     * @param string $context Optional. If set will appear in error message. Default null.
     * @param bool   $return  Optional. If set will return false rather than error out. Default false.
     * @return bool
     */
    function check_proc_available( $context = null, $return = false ) {
    }

    /**
     * Returns past tense of verb, with limited accuracy. Only regular verbs catered for, apart from "reset".
     *
     * @param string $verb Verb to return past tense of.
     * @return string
     */
    function past_tense_verb( $verb ) {
    }

    /**
     * Get the path to the PHP binary used when executing WP-CLI.
     *
     * @access public
     * @category System
     *
     * @return string
     */
    function get_php_binary() {
    }

    /**
     * Windows compatible `proc_open()`.
     *
     * @access public
     *
     * @param string                            $cmd            Command to execute.
     * @param array<int, list<string>|resource> $descriptorspec Indexed array of descriptor numbers and their values.
     * @param array<int, resource>              &$pipes         Indexed array of file pointers that correspond to PHP's end of any pipes that are created.
     * @param string                            $cwd            Initial working directory for the command.
     * @param array<string, string>             $env            Array of environment variables.
     * @param array<string, bool>|null          $other_options  Array of additional options (Windows only).
     * @return resource|false Command stripped of any environment variable settings, or false on failure.
     *
     * @param-out array<int, resource> $pipes
     */
    function proc_open_compat( $cmd, $descriptorspec, &$pipes, $cwd = null, $env = null, $other_options = null ) {
    }

    /**
     * First half of escaping for LIKE special characters % and _ before preparing for MySQL.
     *
     * @access public
     *
     * @param string $text The raw text to be escaped.
     * @return string Text in the form of a LIKE phrase.
     */
    function esc_like( $text ) {
    }

    /**
     * Escapes (backticks) MySQL identifiers (aka schema object names).
     *
     * @param  string|array<string> $idents A single identifier or an array of identifiers.
     * @return string|array<string> An escaped string if given a string, or an array of escaped strings if given an array of strings.
     *
     * @phpstan-return ($idents is string ? string : array<string>)
     */
    function esc_sql_ident( $idents ) {
    }

    /**
     * Check whether a given string is a valid JSON representation.
     *
     * @param mixed  $argument       String to evaluate.
     * @param bool   $ignore_scalars Optional. Whether to ignore scalar values.
     *                               Defaults to true.
     * @return bool Whether the provided string is a valid JSON representation.
     *
     * @phpstan-assert-if-true =non-empty-string $argument
     */
    function is_json( $argument, $ignore_scalars = true ) {
    }

    /**
     * Parse known shell arrays included in the $assoc_args array.
     *
     * @param array<string, string> $assoc_args      Associative array of arguments.
     * @param array<string>         $array_arguments Array of argument keys that should receive an
     *                                               array through the shell.
     * @return array<string, mixed>
     */
    function parse_shell_arrays( $assoc_args, $array_arguments ) {
    }

    /**
     * Describe a callable as a string.
     *
     * @param callable $callable The callable to describe.
     * @return string String description of the callable.
     */
    function describe_callable( $callable ) {
    }

    /**
     * Checks if the given class and method pair is a valid callable.
     *
     * @param array $pair The class and method pair to check.
     * @return bool
     */
    function is_valid_class_and_method_pair( $pair ) {
    }

    /**
     * Pluralizes a noun in a grammatically correct way.
     *
     * @param string   $noun  Noun to be pluralized. Needs to be in singular form.
     * @param int|null $count Optional. Count of the nouns, to decide whether to
     *                        pluralize. Will pluralize unconditionally if none
     *                        provided.
     * @return string Pluralized noun.
     */
    function pluralize( $noun, $count = null ) {
    }

    /**
     * Return the detected database type.
     *
     * @return string Database type.
     */
    function get_db_type() {
    }

    /**
     * Get the path to the MySQL or MariaDB binary.
     *
     * @since 2.12.0 Now also checks for MariaDB.
     *
     * @return string Path to the MySQL/MariaDB binary, or an empty string if not found.
     */
    function get_mysql_binary_path() {
    }

    /**
     * Get the version of the MySQL or MariaDB database.
     *
     * @since 2.12.0 Now also checks for MariaDB.
     *
     * @return string Version of the MySQL/MariaDB database,
     *                or an empty string if not found.
     */
    function get_mysql_version() {
    }

    /**
     * Returns the correct `dump` command based on the detected database type.
     *
     * @return string The appropriate dump command.
     */
    function get_sql_dump_command() {
    }

    /**
     * Returns the correct `check` command based on the detected database type.
     *
     * @return string The appropriate check command.
     */
    function get_sql_check_command() {
    }

    /**
     * Get the SQL modes of the MySQL session.
     *
     * @return string[] Array of SQL modes, or an empty array if they couldn't be
     *                  read.
     */
    function get_sql_modes() {
    }

    /**
     * Get an environment variable value, with config file fallback.
     *
     * @param string $name Environment variable name.
     * @return string|false The value of the environment variable, or false if not set.
     */
    function get_env_or_config( $name ) {
    }

    /**
     * Get the WP-CLI cache directory.
     *
     * @return string
     */
    function get_cache_dir() {
    }

    /**
     * Check whether any input is passed to STDIN.
     *
     * @return bool
     */
    function has_stdin() {
    }

    /**
     * Return description of WP_CLI hooks used in @when tag
     *
     * @param string $hook Name of WP_CLI hook
     *
     * @return string|null
     */
    function get_hook_description( $hook ) {
    }

    /**
     * Escape a value for CSV output.
     *
     * @param string $value Value to escape.
     * @return string Escaped value.
     */
    function escape_csv_value( $value ) {
    }

    /**
     * Convert a size in bytes to a human-readable format.
     *
     * @param int|float $bytes    Size in bytes.
     * @param int       $decimals Optional. Number of decimal places to round to. Default 0.
     * @param string    $unit     Optional. Specific unit to use. Default is auto-detect.
     * @return string Human-readable size.
     */
    function format_bytes_string( $bytes, $decimals = 0, $unit = '' ) {
    }
}
