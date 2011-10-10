<?php
/**
 * Future
 *
 * PHP Version 5.3 
 *
 * @author Sean Crystal <sean.crystal@gmail.com>
 * @copyright 2011 Sean Crystal
 * @license http://www.opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @link https://github.com/spiralout/Futures
 */

require 'FutureMessageQueueInterface.php';

class Future
{
    /**
     * Constructor
     *
     * @var Closure $func
     * @var bool $autoStart
     */
    function __construct(Closure $func, FutureMessageQueueInterface $queue, $autoStart = true)
    {
        self::$futureCount++;

        $this->func = $func;        
        $this->queue = $queue;
        $this->autoStart = $autoStart;

        $this->initMessageQueue();

        $autoStart and $this->doFork();
    }

    /**
     * Retrieve the value of this Future. Blocks until value is available
     *
     * @return mixed
     */
    function __invoke()
    {
        return $this->get();
    }

    /**
     * Get the value of this Future. Blocks until the value is available
     *
     * @return mixed
     */
    function get()
    {           
        if (!$this->completed) {
            if (!($this->value = $this->queue->receive($this->messageType))) {
                return false;
            }

            $this->cleanUp();
            $this->completed = true;
        }

        return $this->value;
    }

    /**
     * Get the value of this Future if available, but do not block. Returns false if the
     * value is not yet available
     *
     * @return mixed|false
     */
    function getNoWait()
    {
        if (!$this->completed) { 
            if (!($this->value = $this->queue->receiveNoWait($this->messageType))) {
                return false;
            }

            $this->completed = true;
        }

        return $this->value;
    }

    /**
     * Start the Future computation if it was not autostarted
     */
    function start()
    {
        $this->autoStart or $this->doFork();
    }

    /**
     * Check if this Future is finished computing its value
     *
     * @return bool
     */
    function isCompleted()
    {
        return $this->completed;
    }

    /**
     * Fork a child process to compute the value of this Future
     */
    private function doFork()
    {
        if ($this->pid = pcntl_fork()) {  // parent
            /* nop */
        } else {  //child
            $func = $this->func;

            try {
                $value = $func();
            } catch (Exception $e) {
                $value = $e;
            }
        
            if (!$this->queue->send($value, $this->messageType)) {
                $this->cleanUp();
            }
            
            exit(0);
        } 
    }

    /**
     * Cleanup any resources acquired
     */
    private function cleanUp()
    {
        self::$futureCount--;

        if (self::$futureCount == 0) {
            $this->queue->shutdown();
        }
    }

    /**
     * Generate a unique message type key
     * has to be an int
     *
     * @return int
     */
    private function getMessageType()
    {
        return floor(microtime(true) * 100000 + rand(0, 1000));
    }

    /**
     * Create a message queue to return the result of this Future to the creator
     */
    private function initMessageQueue()
    {
        $this->messageType = $this->getMessageType();
        $this->queue->init();
    }

    /** @var FutureMessageQueueInterface */
    private $queue;

    /** @var bool */
    private $completed = false;

    /** @var bool */
    private $autoStart = true;

    /** @var mixed */
    private $value;

    /** @var int */
    private $pid;

    /** @var Closure */
    private $func;

    /** @var int */
    private $messageType;

    /** @staticvar $int */
    static $futureCount = 0;
}   



