
<?php

class Cinema
{
    public string $id;
    public string $owner;
    public string $name;

    const ID_PREFIX = "c";

    public function __construct($owner, $name)
    {
        $this->generateID();
        $this->owner = $owner;
        $this->name = $name;
    }

    public function addToDB():bool
    {
        if (empty($this->id))
        {
            logger("ID was empty");
            return false;
        }

        if (empty($this->name))
        {
            logger("Cinema name was empty");
            return false;
        }

        if ($this->checkIfAlreadyExists())
            logger("Cinema already exists!");

        $conn = OpenCon(true);

        $sql_str = "INSERT INTO Cinemas VALUES(?, ?, ?)";

        $stmt = $conn->prepare($sql_str);

        if (!$stmt->bind_param("sss", $id,$user_id, $movie_id))
            logger("Binding error while Adding Cinema");

        $id = $this->id;
        $user_id = $this->owner;
        $movie_id = $this->name;

        if (!$stmt->execute())
        {
            logger("Add Cinema failed: " . $stmt->error);
            $stmt->close();
            CloseCon($conn);
            return false;
        }
        else
        {
            logger("Added Cinema successfully.");
            $stmt->close();
            CloseCon($conn);
            return true;
        }
    }

    private function generateID()
    {
        do {
            $this->id = getRandomString(9, $this::ID_PREFIX);
        } while($this->checkIfUniqueID() === false);
    }

    public function checkIfUniqueID():bool
    {
        $conn = OpenCon(true);

        $sql_str = "SELECT ID FROM Cinemas WHERE id=?";
        $stmt = $conn->prepare($sql_str);
        $stmt->bind_param("s",$id);
        $id = $this->id;

        if (!$stmt->execute())
            logger("Check Cinemas ID failed " . $stmt->error);

        if ($stmt->affected_rows === 1)
            return false;
        else
            return true;
    }

    public function checkIfAlreadyExists():bool
    {
        $conn = OpenCon(true);

        $sql_str = "SELECT ID FROM Cinemas WHERE ID=?";
        $stmt = $conn->prepare($sql_str);
        $stmt->bind_param("s",$this->id);

        if (!$stmt->execute())
            logger("Check for duplicate Cinema failed " . $stmt->error);

        if ($stmt->affected_rows === 1)
            return false;
        else
            return true;
    }

    public static function CreateExistingCinemaObj($id, $owner, $name):Cinema
    {
        $cinema = new Cinema( $owner, $name);
        $cinema->id = $id;
        return $cinema;
    }

    public static function EditCinema(string $id, string $name):bool
    {
        $conn = OpenCon(true);

        $sql_str = "UPDATE Cinemas SET NAME=? WHERE ID=?";
        $stmt = $conn->prepare($sql_str);
        $stmt->bind_param("ss", $name, $id);

        if (!$stmt->execute())
        {
            logger("Edit Cinema failed " . $stmt->error);
            $success = false;
        }
        else
        {
            logger("Edited Cinema successfully!");
            $success = true;
        }

        // Cleanup
        $stmt->close();
        CloseCon($conn);

        return $success;
    }

    public static function DeleteCinema(string $id):bool
    {
        $conn = OpenCon(true);

        $sql_str = "DELETE FROM Cinemas WHERE ID=?";
        $stmt = $conn->prepare($sql_str);
        $stmt->bind_param("s",$id);

        if (!$stmt->execute())
        {
            logger("Remove Cinema failed " . $stmt->error);
            $success = false;
        }
        else
        {
            logger("Removed Cinema successfully!");
            $success = true;
        }

        // Cleanup
        $stmt->close();
        CloseCon($conn);

        return $success;
    }

    public static function GetAllOwnerCinemas(string $user_id):array
    {
        $conn = OpenCon(true);

        $sql_str = "SELECT * FROM Cinemas WHERE OWNER=?";
        $stmt = $conn->prepare($sql_str);
        $stmt->bind_param("s", $id);

        $id = $user_id;

        if (!$stmt->execute())
            logger("Get Cinemas failed " . $stmt->error);

        $result = $stmt->get_result();

        $num_of_rows = $result->num_rows;
        logger("Found " . $num_of_rows . " cinemas.");

        $ret_array = array();
        while ($row = $result->fetch_assoc()) {

            // Create object and append to return array
            $cinema = Cinema::CreateExistingCinemaObj($row['ID'], $row['OWNER'], $row['NAME']);
            $ret_array[] = $cinema;
        }

        $stmt->free_result();
        $stmt->close();

        CloseCon($conn);

        return $ret_array;
    }

}
