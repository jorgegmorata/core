<?php
namespace PSFS\test\base;
use PSFS\base\config\Config;
use PSFS\base\Logger;

/**
 * Class DispatcherTest
 * @package PSFS\test\base
 */
class LoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test to check if the Logger has been created successful
     * @return Logger
     */
    public function getInstance()
    {
        $instance = Logger::getInstance();

        $this->assertNotNull($instance, 'Logger instance is null');
        $this->assertInstanceOf("\\PSFS\\base\\Logger", $instance, 'Instance is different than expected');
        return $instance;
    }

    /**
     * Test to check if the Singleton pattern works
     */
    public function testSingletonLoggerInstance()
    {
        $instance1 = $this->getInstance();
        $instance2 = $this->getInstance();

        $this->assertEquals($instance1, $instance2, 'Singleton instances are not equals');
    }

    /**
     * Test all the functionality for the logger class
     */
    public function testLogFunctions()
    {
        // Basic log
        Logger::log('Test normal log');
        // Warning log
        Logger::log('Test warning log', LOG_WARNING);
        // Info log
        Logger::log('Test info log', LOG_INFO);
        // Error log
        Logger::log('Test error log', LOG_ERR);
        // Critical log
        Logger::log('Test critical log', LOG_CRIT);
        // Debug log
        Logger::log('Test debug logs', LOG_DEBUG);
        // Other logs
        Logger::log('Test other logs', LOG_CRON);
    }

    /**
     * Test non default logger configurations set
     */
    public function testLogSetup()
    {
        // Add memory logger to test this functionality
        $config = Config::getInstance();
        $defaultConfig = $config->dumpConfig();
        Config::save(array_merge($defaultConfig, ['logger.memory'=>true]), []);

        // Create a new logger instance
        $logger = new Logger(['test', true]);
        $logger->debugLog('Test');
        $logger = null;
        unset($defaultConfig['logger.memory']);
        Config::save($defaultConfig, []);

    }
}