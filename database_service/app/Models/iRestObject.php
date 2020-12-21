<?php

namespace RestAPI;

interface iRestObject
{
    public static function addOne($obj): bool;
    public static function getOne(string $id);
    public static function updateOne(string $id): bool;
    public static function deleteOne(string $id): bool;
    public static function getAll(): array;
}