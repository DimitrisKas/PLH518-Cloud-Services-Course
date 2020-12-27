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

    /** Create User object from document with user data
     * @param $doc array Document array with data
     */
    public function __construct($doc) {
        if ( !empty($doc['_id']) )
            $this->id = $doc['_id']->__toString();

        $this->username = $doc['username'];
        $this->password = $doc['password'];
        $this->name = $doc['name'];
        $this->surname = $doc['surname'];
        $this->email = $doc['email'];
        $this->role = $doc['role'];

        if ( !empty($doc['confirmed']))
            $this->confirmed = $doc['confirmed'] == '1';
        else
            $this->confirmed = false;
    }
}

class Movie
{
    public string $id;
    public string $title;

    public string $start_date;
    public string $end_date;
    public string $cinema_name;
    public string $category;
    public bool $favorite = false;

}

class Favorite
{
    public string $id;
    public string $user_id;
    public string $movie_id;

}

class Cinema
{
    public string $id;
    public string $owner;
    public string $name;


    /** Create Cinema object from document with cinema data
     * @param $doc array Document array with data
     * @param $owner string optional owner id string
     */
    public function __construct($doc, $owner = null) {
        if ( !empty($doc['_id']) )
            $this->id = $doc['_id']->__toString();

        if ($owner == null)
            $this->owner = $doc['owner'];
        else
            $this->owner = $owner;

        $this->name = $doc['name'];
    }
}