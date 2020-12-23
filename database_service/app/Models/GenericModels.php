<?php

/**
 * Project specific Models, database agnostic
 */
namespace Models\Generic;


class User
{
    public string $id;
    public string $name;
    public string $surname;
    public string $username;
    public string $password;
    public string $email;
    public string $role;
    public bool $confirmed;

    const ADMIN = "ADMIN";
    const CINEMAOWNER = "CINEMAOWNER";
    const USER = "USER";

    const ID_PREFIX = "u";

    public function __construct($obj) {
        $this->username = $obj['username'];
        $this->password = $obj['password'];
        $this->name = $obj['name'];
        $this->surname = $obj['surname'];
        $this->email = $obj['email'];
        $this->role = $obj['role'];
    }
}

class Movies
{
    public string $id;
    public string $title;

    public string $start_date;
    public string $end_date;
    public string $cinema_name;
    public string $category;
    public bool $favorite = false;

    const ID_PREFIX = "m";
}

class Favorites
{
    public string $id;
    public string $user_id;
    public string $movie_id;

    const ID_PREFIX = "f";
}

class Cinemas
{
    public string $id;
    public string $owner;
    public string $name;

    const ID_PREFIX = "c";
}