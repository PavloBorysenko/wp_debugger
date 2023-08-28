<?php
namespace WpHelper\Debugger;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * WpDebugger
 */
class WpDebugger {
       
    /**
     * getKey
     * The key at which you can display a dump
     * 
     * @var string
     */
    private $getKey = 'wp-debugger';

    /**
	 * list of timers
	 *
	 * @var array 
	 */ 
    private $timerStorage = [];
    
    /**
     * byteStorage
     *
     * @var array
     */
    private $byteStorage = [];
    
    /**
     * pageData
     *
     * @var array
     */
    private $pageData = [];
    
    /**
     * pageDataLogOption
     * 0 - dont log, 1 - to  log file, 2 - var dump on page
     * @var int
     */
    private $pageDataLogOption = 0;

    
    /**
     * doPing
     * 1- do ping
     * @var int
     */
    private $doPing = 0;
        
    /**
     * __construct
     *
     * @return void
     */
    public function __construct(int $pageData = 0, int $doPing = 0) {
        $this->timerStorage['init_wp_debugger'] = microtime(true);
        $this->byteStorage['init_wp_debugger'] = memory_get_usage();

        $this->pageDataLogOption = $pageData;
        $this->doPing = $doPing;

        if (isset($_GET[$this->getKey])) {

            if ('phpinfo' == $_GET[$this->getKey]) {
                add_action('init', array($this, 'phpinfo'), 9999);
            }
            
            if ('pagedata' == $_GET[$this->getKey]) {
                $this->pageDataLogOption = 2;
            }
        }
        
         


        if (0 != $this->pageDataLogOption ) {
            //plugins_loaded init wp_head wp_footer shutdown
            add_action('plugins_loaded', array($this, 'setPageData'));
            add_action('init', array($this, 'setPageData'));
            add_action('wp_head', array($this, 'setPageData'));
            add_action('wp_footer', array($this, 'setPageData'));
            add_action('admin_head', array($this, 'setPageData'));
            add_action('admin_footer', array($this, 'setPageData'));       
            add_action('shutdown', array($this, 'setPageData'));
        }

    }
        
    /**
     * setPageData
     *
     * @return void
     */
    public function setPageData () : void{
        $actionName = current_action();
       
        $lastKey = array_key_last($this->pageData);

        $this->pageData[$actionName] = array(
            'time' => $this->getTime('init_wp_debugger'),
            'diff_time' => microtime(true),
            'memory' => $this->readableBytes(memory_get_usage())
        );

        if (null != $lastKey &&  isset($this->pageData[$lastKey])) {
            $prevTime = $this->pageData[$lastKey]['time'];
        } else {
            $prevTime = 0;
        }

        $this->pageData[$actionName]['diff_time'] = $this->pageData[$actionName]['time'] - $prevTime;

        if ('shutdown' == $actionName) {
            if (1 == $this->pageDataLogOption) {
                $this->log($this->pageData);
            } elseif (2 == $this->pageDataLogOption) {
                $this->varDump($this->pageData);
            }
        }
    }
    
    /**
     * setTime
     *
     * @param  string $point
     * @return void
     */
    public function setTime (string $point) : void {
        $this->timerStorage[$point] = microtime(true);
    }
    
    /**
     * setByte
     *
     * @param  mixed $point
     * @return void
     */
    public function setByte (string $point) : void {
        $this->byteStorage[$point] = microtime(true);
    }   
    
    /**
     * getTime
     *
     * @param  mixed $point
     * @return int
     */
    public function getTime (string $point = 'init_wp_debugger') : ?float {
        $time = null;

        if (isset($this->timerStorage[$point]) &&  microtime(true) > $this->timerStorage[$point]) {
            $time = microtime(true) - $this->timerStorage[$point];
        }

        return $time;
    }    
    /**
     * getByte
     *
     * @param  mixed $point
     * @return int
     */
    public function getByte (string $point = 'init_wp_debugger') : ?int {
        $byte = null;

        if (isset($this->byteStorage[$point]) ) {
            $byte = microtime(true) - memory_get_usage();
        }

        return $byte;
    }
        
    /**
     * phpinfo
     *
     * @return void
     */
    public function phpinfo () : void {
        if (isset($_GET[$this->getKey])) {
            phpinfo();
            if ($this->doPing) {
                echo $this->ping(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1));
            }           
        }       
    }
    
    protected function ping ($backtrace) : string {

        if (isset($backtrace[0]['file']) && isset($backtrace[0]['line'])) {
            $file = $backtrace[0]['file'];
            $line = $backtrace[0]['line'];
            return printf("< L:%s  F:%s >", $line, $file);
        }
        return '';
    }

    /**
     * varDump
     *
     * @param  mixed $data
     * @return void
     */
    public function varDump ($data) : void {
        if (isset($_GET[$this->getKey])) {
            echo  "<pre>";
            var_dump($data);
            if ($this->doPing) {
                echo $this->ping(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1));
            }
            echo  "</pre>";
        }
    }

    /**
    * Converts a long string of bytes into a readable format e.g KB, MB, GB, TB, YB
    * 
    * @param {Int} num The number of bytes.
    */
    private function readableBytes ($bytes) {
       $i = floor(log($bytes) / log(1024));
       $sizes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
   
       return sprintf('%.02F', $bytes / pow(1024, $i)) * 1 . ' ' . $sizes[$i];
    }
        
    /**
     * log
     *
     * @param  mixed $data
     * @return void
     */
    public function log($data) : void {
        $message = '';

        if (is_array($data) || is_object($data)) {
            $message = print_r($data, true);
        } else {
            $message = $data;
        }
        if ($this->doPing) {
            $message.= "    " . $this->ping(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1));
        }

        $this->writeLog($message);
    }

    /**
     * writeLog
     *
     * @param  string $message
     * @return void
     */
    private function writeLog (string $message) : void {
        $path = WPDEBUG_LOG_PATH . '/error.log';
        $data_log = date("Y-m-d H:i:s") . " - " . $message . PHP_EOL;
        file_put_contents($path, $data_log, FILE_APPEND);
    }    
}