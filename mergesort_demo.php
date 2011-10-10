<?php
/**
 * A purely didactic implementation of merge sort using Futures
 *
 * @author Sean Crystal <sean.crystal@gmail.com>
 * @copyright 2011 Sean Crystal
 * @license http://www.opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @link https://github.com/spiralout/Futures
 */
require_once 'Future.php';
require_once 'IPCFutureMessageQueue.php';

// create shuffled array of numbers 1 - 100
$numbers = range(1, 100);
shuffle($numbers);

echo 'Shuffled:', PHP_EOL;
print_r($numbers);
                                           
/**
 * DI helper to create a Future with our chosen message queue implementation
 *
 * @param Closure $func
 * @return Future
 */
function future(Closure $func)
{
    return new Future($func, new IPCFutureMessageQueue);
}

/**
 * Merge sort using futures
 *
 * @param array $array
 * @return array
 */
function merge_sort(array $array) 
{
    if (count($array) < 2) {
        return $array;        
    }

    $split = ceil(count($array) / 2);

    $merge1 = future(function() use ($array, $split) { return merge_sort(array_slice($array, 0, $split)); });
    $merge2 = future(function() use ($array, $split) { return merge_sort(array_slice($array, $split)); });

    return merge($merge1(), $merge2());
}

/**
 * Merge part of merge sort
 * 
 * @param array $array1
 * @param array $array2
 * @return array
 */
function merge(array $array1, array $array2) 
{
    $merged = array();

    while (!empty($array1) || !empty($array2)) {
        if (empty($array1)) {
            $merged[] = array_shift($array2);
        } elseif (empty($array2)) {
            $merged[] = array_shift($array1);
        } elseif ($array1[0] < $array2[0]) {
            $merged[] = array_shift($array1);
        } else {
            $merged[] = array_shift($array2);
        }        
    }

    return $merged;
}

echo 'Sorted:', PHP_EOL;
print_r(merge_sort($numbers));
