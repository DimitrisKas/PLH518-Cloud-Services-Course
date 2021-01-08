<?php

/**
 * Project specific Models, database agnostic
 */
namespace Models\Generic;


class User
{
    /**
     * @var string User's MongoDB ID (as string). Do not confuse with $k_id
     */
    public string $id;

    /**
     * @var string User's Keystore ID. Do not confuse with $id
     */
    public string $k_id;

    public string $username;
    public string $email;
    public string $name;
    public string $surname;
    public string $role;

    /** @var array Array that holds Mongo IDs to favorite movies  */
    public array $favorites;

    const ADMIN = "ADMIN";
    const CINEMAOWNER = "CINEMAOWNER";
    const USER = "USER";

    /** Create User object from document with user data
     * @param $doc array Document array with data
     */
    public function __construct($doc) {
        if ( !empty($doc['_id']) )
            $this->id = $doc['_id']->__toString();

        if ( !empty($doc['k_id']) )
            $this->k_id = $doc['k_id'];

        $this->username = $doc['username'];
        $this->email = $doc['email'];

        $this->name = $doc['name'];
        $this->surname = $doc['surname'];
        $this->role = $doc['role'];
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
    public bool $isFavorite;


    /** Create Cinema object from document with cinema data
     * @param $doc array Document array with data
     * @param $owner string optional owner id string
     */
    public function __construct($doc) {
        if ( !empty($doc['_id']) )
            $this->id = $doc['_id']->__toString();

        $this->title = $doc['title'];
        $this->cinema_name = $doc['cinema_name'];
        $this->start_date = $doc['start_date'];
        $this->end_date = $doc['end_date'];
        $this->category = $doc['category'];

        if (isset($doc['isFavorite']))
        {
            $this->isFavorite = $doc['isFavorite'] == 'true';
        }
    }
}

class Favorite
{
    /**
     * @var string Current favorite unique ID
     */
    public string $id;
    /**
     * @var string User's Keystore id
     */
    public string $user_id;
    /**
     * @var string Movie's Mongo ID (as string)
     */
    public string $movie_id;

}

class Cinema
{
    public string $id;
    /**
     * @var string User's Keystore id that owns this cinema
     */
    public string $owner;
    public string $name;


    /** Create Cinema object from document with cinema data
     * @param $doc array Document array with data
     * @param $owner_k_id string optional owner id string.
     */
    public function __construct($doc, $owner_k_id = null) {
        if ( !empty($doc['_id']) )
            $this->id = $doc['_id']->__toString();

        if ($owner_k_id == null)
            $this->owner = $doc['owner'];
        else
            $this->owner = $owner_k_id;

        $this->name = $doc['name'];
    }
}