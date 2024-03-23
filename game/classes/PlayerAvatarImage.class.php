<?php

/**
 * An avatar image that a player has, with color information. An AvatarImage is a partial
 * SVG that can itself have components that are one of four different colors.
 */
class PlayerAvatarImage extends AvatarImage {
    protected $color1;
    protected $color2;
    protected $color3;
    protected $color4;
    protected $ownerPlayerId;

    /**
     * Constructor. Needs an array or player and avatar_image_id.
     * @param $arg - Either the itemGUID or a query row with all data.
     * @throws Exception
     */
    public function __construct($arg)
    {
        if (!is_array($arg)) {
            // if $arg isn't an array and it isn't an int, then the constructor wasn't passed valid args
            throw new Exception();
        }

        // Create the base item
        parent::__construct($arg);

        // Add any properties specific to playerAvatarImage
        $this->ownerPlayerId = (int)$arg['player_id'];
        $this->color1 = $arg['color1'];
        $this->color2 = $arg['color2'];
        $this->color3 = $arg['color3'];
        $this->color4 = $arg['color4'];

    } // end constructor


    /**
     * @return int
     */
    public function getOwnerPlayerId()
    {
        return $this->ownerPlayerId;
    }

    /**
     * @return mixed
     */
    public function getColor1()
    {
        return $this->color1;
    }

    /**
     * @return mixed
     */
    public function getColor2()
    {
        return $this->color2;
    }

    /**
     * @return mixed
     */
    public function getColor3()
    {
        return $this->color3;
    }

    /**
     * @return mixed
     */
    public function getColor4()
    {
        return $this->color4;
    }

} // end PlayerAvatarImage
