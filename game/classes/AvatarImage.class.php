<?php

/**
 * An image (partial SVG code stored in the DB) that can be a part of a player avatar.
 */
class AvatarImage extends Dbh {

    protected $id;
    protected $name;
    protected $svgCode; // ideally, we have a small string of SVG code that can be used to
    // compile an inline SVG for the player's avatar
    protected $filename; // alternately, a file can be set that will be layered on top of the SVG.
    protected $avatarImageTypeId;
    protected $avatarImageTypeName;
    protected $genderId;
    protected $colorQty; // The number of colors a player can choose for this image
    protected $raceId;
    protected $disabled;
    protected $layerIndex;

    /**
     * getItemCategories() returns array of item categories.
     */
    public static function getAvatarImageTypes() {
        $sql = "SELECT id, name, layer_index, display_seq, disabled FROM avatar_image_types
        ORDER BY display_seq;";
        $dbh = new Dbh();
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $dbh->connection = null;
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAvatarImageTypeColors($avatarImageTypeId)
    {
        $sql = "SELECT css_color FROM avatar_image_type_colors aitc
        JOIN avatar_image_types ait ON ait.id = aitc.avatar_image_type_id
        WHERE ait.id = ?
        ORDER BY aitc.display_seq;";

        $dbh = new Dbh();
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute([$avatarImageTypeId]);
        $colors = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $colors;

    }

    /**
     * getAvatarImages() returns array of items with specified avatar_image_type_id, gender_id, and race_id
     * @param categoryId - limit to specific category (avatar parts)
     */
    public static function getAvatarImages(int $avatarImageTypeId=null, int $genderId=null, int $raceId=null) {
        $sql = "SELECT ai.id, ai.name, ai.avatar_image_type_id, ait.name AS avatar_image_type_name, ait.layer_index,
            ai.filename, ai.svg_code, ai.gender_id, ai.race_id, ai.color_qty, ai.disabled
            FROM avatar_images ai
            LEFT JOIN avatar_image_types ait ON ai.avatar_image_type_id=ait.id
            WHERE ai.disabled=0 ";

        if (!is_null($avatarImageTypeId))
            $sql .= " AND ai.avatar_image_type_id=:avatarImageTypeId ";

        if (!is_null($genderId))
            $sql .= " AND (ai.gender_id=:genderId OR ai.gender_id IS NULL) ";

        if (!is_null($raceId))
        $sql .= " AND (ai.race_id=:raceId OR ai.race_id IS NULL)";

        $dbh = new Dbh();
        $stmt = $dbh->connect()->prepare($sql);
        if (!is_null($avatarImageTypeId))
            $stmt->bindParam('avatarImageTypeId', $avatarImageTypeId, PDO::PARAM_INT);

        if (!is_null($genderId))
            $stmt->bindParam('genderId', $genderId, PDO::PARAM_INT);

        if (!is_null($raceId))
            $stmt->bindParam('raceId', $raceId, PDO::PARAM_INT);

        $stmt->execute();
        $dbh->connection = null;
        // For now we are returning an array of db rows, but I'll probably want to return
        // an array of item objects

        $avatarImages = array();

        foreach ($stmt as $row) {
            array_push($avatarImages, new AvatarImage($row));
        }

        return $avatarImages;
    }


    /**
     * AvatarImage constructor. Needs an avatar_image_id.
     * @param arg - either id or an array containing all item values from an SQL query
     * @throws Exception
     */
    public function __construct($arg)
    {
        if(is_int($arg)) {

        $this->id=$arg;
        // Check that avatar_image exists and throw exception if not.
        $sql = "SELECT ai.id, ai.name, ai.avatar_image_type_id, ait.name AS avatar_image_type_name, ait.layer_index,
            ai.filename, ai.svg_code, ai.gender_id, ai.race_id, ai.color_qty, ai.disabled
            FROM avatar_images ai
            LEFT JOIN avatar_image_types ait ON ai.avatar_image_type_id=ait.id
            WHERE ai.id = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$arg]);
        $count = $stmt->rowCount();
        // I suppose here I can use a try/catch to make this more user-friendly
        if ($count == 0) throw new Exception($arg);
        $arg = $stmt->fetch();
        } else if (!is_array($arg)) {
            // if $arg isn't an array and it isn't an int, then the constructor wasn't passed valid args
            throw new Exception();
        }

        $this->id = (int)$arg['id'];
        $this->name = $arg['name'];
        $this->filename = $arg['filename'];
        $this->svgCode = $arg['svg_code'];
        $this->genderId = (int)$arg['gender_id'];
        $this->raceId = (int)$arg['race_id'];
        $this->colorQty = $arg['color_qty'];
        $this->avatarImageTypeId = $arg['avatar_image_type_id'];
        $this->avatarImageTypeName = $arg['avatar_image_type_name'];
        $this->layerIndex = $arg['layer_index'];
        $this->disabled = $arg['disabled'];
    }


    public function getId() {return $this->id;}
    public function getName() {return $this->name;}
    // Interesting choice to return the whole path. We'll come back to this.
    public function getFilename() {return Config::IMAGE_PATH.$this->filename;}
    public function getSvgCode() {return $this->svgCode;}
    public function getColorQty() {return $this->colorQty;}
    public function getAvatarImageTypeId() {return $this->avatarImageTypeId;}

}