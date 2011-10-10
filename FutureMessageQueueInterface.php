<?php
/**
 * Interface for the Futures message queue
 *
 * @author Sean Crystal <sean.crystal@gmail.com>
 * @copyright 2011 Sean Crystal
 * @license http://www.opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @link https://github.com/spiralout/Futures
 */
interface FutureMessageQueueInterface
{
    function init();
    function shutdown();
    function receive($messageType);
    function receiveNoWait($messageType);
    function send($message, $messageType);
    function getLastError();
}
