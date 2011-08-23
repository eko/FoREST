<?php

/**
 * foREST - a simple RESTful PHP API
 * 
 * @version 1.0
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */

namespace Forest;

use Forest\Core\Dispatcher as Dispatcher;
use Forest\Core\Exception as Exception;
use Forest\Core\Registry as Registry;

use Forest\Logger as Logger;

/**
 * Bootstrap
 */
class Bootstrap
{
    /**
     * Components loaded
     * @var array
     */
    private $_components = array();
    
    /**
     * Options availables
     * @var array
     */
    private $_options = array();
    
    /**
     * Total call duration (debug mode)
     * @var float
     */
    private $_duration = null;
    
    /**
     * Constructor
     * 
     * @param array $components
     */
    public function __construct($components = array()) {
        $start = microtime(true);
        
        $this->_components = $components;
        
        spl_autoload_register(__CLASS__ .'::autoload');
        
        $this->loadConfiguration();
        $this->loadResources();
        
        $dispatcher = new Dispatcher();
        $dispatcher->dispatch();
        
        $end = microtime(true);
        
        $this->_duration = ($end - $start);
    }
    
    /**
     * Autoload all classes in project
     * 
     * @param string $class
     */
    public function autoload($class) {
        $basedir = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..');
        
        $classfile = $basedir . DIRECTORY_SEPARATOR . str_replace(array('_', '\\'), '/', $class) . '.php';
        
        if (true === file_exists($classfile)) {
            include $classfile;
        }
    }
    
    /**
     * Load configuration
     */
    private function loadConfiguration() {
        if (false === array_key_exists('Yaml', $this->_components)) {
            throw new Exception("Yaml component isn't loaded. You need to specify it on Bootstrap constructor");
        }
        
        $basedir = realpath(dirname(__FILE__) . str_repeat(DIRECTORY_SEPARATOR . '..', 2));
        $config = $basedir . DIRECTORY_SEPARATOR . 'config/configuration.yml';
        
        if (false === file_exists($config)) {
            throw new Exception(sprintf('Configuration file does not exists at location: %s', $config));
        }
        
        $yaml = new $this->_components['Yaml']();
        $this->_options = $yaml->parse(file_get_contents($config));
    }
    
    /**
     * Load resources (mapping, queries) from /resources folder
     */
    private function loadResources() {
        $directory = realpath(dirname(__FILE__)
                    . str_repeat(DIRECTORY_SEPARATOR . '..', 2)
                    . DIRECTORY_SEPARATOR . 'resources'
        );
        
        $resources = $this->readDirectory($directory);
        
        foreach ($resources as $resource) {
            $resourcePath = $directory . DIRECTORY_SEPARATOR . $resource;
            $resourceFiles = $this->readDirectory($resourcePath);
            
            foreach ($resourceFiles as $file) {
                $file = $resourcePath . DIRECTORY_SEPARATOR . $file;
                include_once $file;
            }
        }
        
        Registry::set('mapping', $mapping);
        Registry::set('queries', $queries);
    }
    
    /**
     * Return directory items
     * 
     * @param string $directory
     * 
     * @return array $items
     */
    private function readDirectory($directory) {
        $items = array();
        
        $handle = opendir($directory);
        
        while (false !== ($item = readdir($handle))) {
            if (false === in_array($item, array('.', '..'))) {
                $items[] = $item;
            }
        }
        
        closedir($handle);
        
        return $items;
    }
    
    /**
     * Return total call duration
     * 
     * @throws Forest\Core\Exception
     * 
     * @return float $_duration
     */
    public function getDuration() {
        return (number_format($this->_duration, 5) . 'ms');
    }
}
?>