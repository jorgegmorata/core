<?php

namespace PSFS\base;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as Monolog;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\UidProcessor;
use PSFS\base\config\Config;
use PSFS\base\types\SingletonTrait;


if (!defined("LOG_DIR")) {
    Config::createDir(BASE_DIR . DIRECTORY_SEPARATOR . 'logs');
    define("LOG_DIR", BASE_DIR . DIRECTORY_SEPARATOR . 'logs');
}

/**
 * Class Logger
 * @package PSFS\base
 * Servicio de log
 */
class Logger
{
    const DEFAULT_NAMESPACE = 'PSFS';
    use SingletonTrait;
    /**
     * @var \Monolog\Logger
     */
    protected $logger;
    /**
     * @var resource
     */
    private $stream;
    /**
     * @var string
     */
    protected $log_level;

    /**
     * @internal param string $path
     */
    public function __construct()
    {
        $config = Config::getInstance();
        $args = func_get_args();
        list($logger, $debug, $path) = $this->setup($config, $args);
        $this->stream = fopen($path . DIRECTORY_SEPARATOR . date("Ymd") . ".log", "a+");
        $this->addPushLogger($logger, $debug, $config);
        $this->log_level = Config::getInstance()->get('log.level') ?: 'info';
    }

    /**
     * Destruye el recurso
     */
    public function __destroy()
    {
        fclose($this->stream);
    }

    /**
     * Default log method
     * @param string $msg
     * @param array $context
     * @return bool
     */
    public function defaultLog($msg = '', $context = [])
    {
        return $this->logger->addNotice($msg, $this->addMinimalContext($context));
    }

    /**
     * Método que escribe un log de información
     * @param string $msg
     * @param array $context
     *
     * @return bool
     */
    public function infoLog($msg = '', $context = [])
    {
        return $this->logger->addInfo($msg, $this->addMinimalContext($context));
    }

    /**
     * Método que escribe un log de Debug
     * @param string $msg
     * @param array $context
     *
     * @return bool
     */
    public function debugLog($msg = '', $context = [])
    {
        return ($this->log_level === 'debug') ? $this->logger->addDebug($msg, $this->addMinimalContext($context)) : null;
    }

    /**
     * Método que escribe un log de Error
     * @param $msg
     * @param array $context
     *
     * @return bool
     */
    public function errorLog($msg, $context = [])
    {
        return $this->logger->addError($msg, $this->addMinimalContext($context));
    }

    /**
     * Método que escribe un log de Warning
     * @param $msg
     * @param array $context
     * @return bool
     */
    public function warningLog($msg, $context = [])
    {
        return $this->logger->addWarning($msg, $this->addMinimalContext($context));
    }

    /**
     * Método que añade los push processors
     * @param string $logger
     * @param boolean $debug
     * @param Config $config
     */
    private function addPushLogger($logger, $debug, Config $config)
    {
        $this->logger = new Monolog(strtoupper($logger));
        $this->logger->pushHandler($this->addDefaultStreamHandler($debug));
        if ($debug) {
            $phpFireLog = $config->get("logger.phpFire");
            if (!empty($phpFireLog)) {
                $this->logger->pushHandler(new FirePHPHandler());
            }
            $memoryLog = $config->get("logger.memory");
            if (!empty($memoryLog)) {
                $this->logger->pushProcessor(new MemoryUsageProcessor());
            }
        }
        $this->logger->pushProcessor(new UidProcessor());
    }

    /**
     * Método que inicializa el Logger
     * @param Config $config
     * @param array $args
     *
     * @return array
     */
    private function setup(Config $config, array $args = array())
    {
        $debug = $config->getDebugMode();
        $namespace = self::DEFAULT_NAMESPACE;
        if (0 !== count($args)) {
            if (array_key_exists(0, $args) && array_key_exists(0, $args[0])) {
                $namespace = $args[0][0];
            }
            if (array_key_exists(0, $args) && array_key_exists(1, $args[0])) {
                $debug = $args[0][1];
            }
        }
        $path = $this->createLoggerPath($config);
        return array($this->cleanLoggerName($namespace), $debug, $path);
    }

    /**
     * Método que construye el nombre del logger
     * @param Config $config
     *
     * @return string
     */
    private function setLoggerName(Config $config)
    {
        $logger = $config->get("platform_name") ?: self::DEFAULT_NAMESPACE;
        $logger = $this->cleanLoggerName($logger);

        return $logger;
    }

    /**
     * Método para limpiar el nombre del logger
     * @param $logger
     *
     * @return mixed
     */
    private function cleanLoggerName($logger)
    {
        $logger = str_replace(' ', '', $logger);
        $logger = preg_replace('/\\\/', ".", $logger);

        return $logger;
    }

    /**
     * Método que crea el path del logger
     * @param Config $config
     *
     * @return string
     */
    private function createLoggerPath(Config $config)
    {
        $logger = $this->setLoggerName($config);
        $path = LOG_DIR . DIRECTORY_SEPARATOR . $logger . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');
        Config::createDir($path);

        return $path;
    }

    /**
     * Static method to trace logs
     * @param string $msg
     * @param int $type
     * @param array $context
     */
    public static function log($msg, $type = LOG_DEBUG, $context = [])
    {
        switch ($type) {
            case LOG_DEBUG:
                Logger::getInstance()->debugLog($msg, $context);
                break;
            case LOG_WARNING:
                Logger::getInstance()->warningLog($msg, $context);
                break;
            case LOG_CRIT:
            case LOG_ERR:
                Logger::getInstance()->errorLog($msg, $context);
                break;
            case LOG_INFO:
                Logger::getInstance()->infoLog($msg, $context);
                break;
            default:
                Logger::getInstance()->defaultLog($msg, $context);
                break;
        }
    }

    /**
     * Add the default stream handler
     * @param bool $debug
     * @return StreamHandler
     */
    private function addDefaultStreamHandler($debug = false)
    {
        // the default date format is "Y-m-d H:i:s"
        $dateFormat = "Y-m-d H:i:s.u";
        // the default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
        $output = "[%datetime%] [%channel%:%level_name%]\t%message%\t%context%\t%extra%\n";
        // finally, create a formatter
        $formatter = new LineFormatter($output, $dateFormat);
        $stream = new StreamHandler($this->stream, $debug ? Monolog::DEBUG : Monolog::WARNING);
        $stream->setFormatter($formatter);
        return $stream;
    }

    /**
     * Add a minimum context to the log
     * @param array $context
     * @return array
     */
    private function addMinimalContext(array $context = [])
    {
        $context['uri'] = null !== $_SERVER && array_key_exists('REQUEST_URI', $_SERVER) ? $_SERVER['REQUEST_URI'] : 'Unknow';
        $context['method'] = null !== $_SERVER && array_key_exists('REQUEST_METHOD', $_SERVER) ? $_SERVER['REQUEST_METHOD'] : 'Unknow';
        return $context;
    }
}
