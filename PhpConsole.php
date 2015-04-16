<?php

namespace app\components;
use Yii;
use PhpConsole\Connector;
use PhpConsole\Helper;
use PhpConsole\Handler;
use yii\base\Component;
/**
 * Integrates YII2 with Google Chrome extension PHP Console
 *
 * You need to install Google Chrome extension:
 * https://chrome.google.com/webstore/detail/php-console/nfhmhhlpfleoednkpnnnkolmclajemef
 *
 * @package PhpConsoleYii2
 * @version 1.0
 * @link https://github.com/nilsburg/php-console-yii2
 * @author Nils van der Burg http://linkedin.com/in/nilsvanderburg
 * @copyright © Nils van der Burg, 2015. All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
 */
class PhpConsole extends Component{
    /** @var bool Is PHP Console server enabled */
    public $isEnabled = true;
    /** @var string Path to PhpConsole classes directory */
    public $phpConsolePathAlias = '@app/vendor/php-console/src/PhpConsole';
    /** @var string Base path of all project sources to strip in errors source paths */
    public $sourcesBasePath;
    /** @var bool Register PhpConsole\Helper that allows short debug calls like PC::debug($var, 'ta.g.s') */
    public $registerHelper = true;

    /** @var string|null Server internal encoding */
    public $serverEncoding;
    /** @var int|null Set headers size limit for your web-server. You can detect headers size limit by /PhpConsole/examples/utils/detect_headers_limit.php */
    public $headersLimit;
    /** @var string|null Protect PHP Console connection by password */
    public $password;
    /** @var bool Force connection by SSL for clients with PHP Console installed */
    public $enableSslOnlyMode = false;
    /** @var array Set IP masks of clients that will be allowed to connect to PHP Console lie: array('192.168.*.*', '10.2.12*.*', '127.0.0.1') */
    public $ipMasks = array();

    /** @var bool Enable errors handling */
    public $handleErrors = true;
    /** @var bool Enable exceptions handling */
    public $handleExceptions = true;

    /** @var int Maximum dumped vars array or object nested dump level */
    public $dumperLevelLimit = 5;
    /** @var int Maximum dumped var same level array items or object properties number */
    public $dumperItemsCountLimit = 100;
    /** @var int Maximum length of any string or dumped array item */
    public $dumperItemSizeLimit = 50000;
    /** @var int Maximum approximate size of dumped vars result formatted in JSON */
    public $dumperDumpSizeLimit = 500000;
    /** @var bool Convert callback items in dumper vars to (callback SomeClass::someMethod) strings */
    public $dumperDetectCallbacks = true;
    /** @var bool */
    public $detectDumpTraceAndSource = false;

    /**
     * @var bool Enable eval request to be handled by eval dispatcher. Must be called after all Connector configurations.
     * $this->password is required to be set
     * use $this->ipMasks & $this->enableSslOnlyMode for additional protection
     */
    public $isEvalEnabled = false;

    /** @var  Handler|null */
    protected $handler;
    public function init(){
        if(!class_exists('PhpConsole\Connector')){
            /** @noinspection PhpIncludeInspection */
            require_once(Yii::getAlias($this->phpConsolePathAlias) . '/__autoload.php');
        }
        if($this->registerHelper) {
            Helper::register();
        }

        if(!$this->isEnabled || !Connector::getInstance()->isActiveClient()) {
            return;
        }

        $handler = Handler::getInstance();
        $handler->setHandleErrors($this->handleErrors);
        $handler->setHandleErrors($this->handleExceptions);
        $handler->start();
        $this->handler = $handler;

        $connector = Connector::getInstance();
        if($this->sourcesBasePath) {
            $connector->setSourcesBasePath($this->sourcesBasePath);
        }
        if($this->serverEncoding) {
            $connector->setServerEncoding($this->serverEncoding);
        }
        if($this->password) {
            $connector->setPassword($this->password);
        }
        if($this->enableSslOnlyMode) {
            $connector->enableSslOnlyMode();
        }
        if($this->ipMasks) {
            $connector->setAllowedIpMasks($this->ipMasks);
        }
        if($this->headersLimit) {
            $connector->setHeadersLimit($this->headersLimit);
        }

        if($this->detectDumpTraceAndSource) {
            $connector->getDebugDispatcher()->detectTraceAndSource = true;
        }

        $dumper = $connector->getDumper();
        $dumper->levelLimit = $this->dumperLevelLimit;
        $dumper->itemsCountLimit = $this->dumperItemsCountLimit;
        $dumper->itemSizeLimit = $this->dumperItemSizeLimit;
        $dumper->dumpSizeLimit = $this->dumperDumpSizeLimit;
        $dumper->detectCallbacks = $this->dumperDetectCallbacks;

        if($this->isEvalEnabled) {
            $connector->startEvalRequestsListener();
        }
    }
}