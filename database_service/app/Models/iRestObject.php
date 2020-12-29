<?php

namespace RestAPI;

/**
 * Small helper class:
 * For methods that return a success/fail but also
 * sometimes require a feedback message
 *
 */
class Result {
    public string $msg;
    public bool $success;

    public function __construct($msg, $success)
    {
        $this->msg = $msg;
        $this->success = $success;
    }

    /**
     * Returns a Result object while also logging the given message
     * @param string $msg Message to return with object and Log to file
     * @param bool $success True if success, false otherwise
     * @return Result Result object with given parameters
     */
    static public function withLogMsg(bool $success, string $msg = null ): Result
    {
        if (!empty($msg))
        {
            logger($msg);
            return new Result($msg,$success);
        }
        return new Result("",$success);
    }
}


/**
 * Interface for REST objects to Database Objects
 */
interface iRestObject
{
    public static function addOne($obj): Result;
    public static function getOne(string $id);
    public static function updateOne(string $id, $obj): Result;
    public static function deleteOne(string $id): Result;
    public static function getAll(): array;
}