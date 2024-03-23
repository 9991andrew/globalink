<?php

/**
 * A generic item. This class is extended by these classes:
 * - PlayerItem for items a player has in their inventory
 * - QuestItem for items required by quests
 * - RewardItem for items awarded for completing quests
 * - NpcItem for items given or sold by NPCs
 */
class Item extends Dbh {

    protected $id;
    protected $name;
    protected $categoryId;
    protected $categoryName;
    protected $iconId;
    protected $amount;
    protected $description;
    protected $effectId;
    protected $effectParameters;
    protected $weight;
    protected $requiredLevel;
    protected $level;
    protected $price;
    protected $maxAmount;
    protected $imageExt;
    protected $imageFilename; //pad iconId to four digits, prepend ‘/images/item’, append fileExt;
    protected $itemVariables;


    /**
     * getItemCategories() returns array of item categories.
     */
    public static function getItemCategories() {
        $sql = "SELECT * FROM item_categories";
        $dbh = new Dbh();
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $dbh->connection = null;
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * getItems() returns array of items with specified categoryid
     * @param categoryId - limit to specific category (avatar parts)
     */
    public static function getItems(int $categoryId=null) {
        $sql = "SELECT i.id, i.name, i.item_category_id, ic.name AS item_category_name, i.description,
            i.item_effect_id, i.effect_parameters, i.weight, i.required_level, i.level, i.price, i.max_amount,
            i.item_icon_id, ico.name AS icon_name FROM items i
            LEFT JOIN item_categories ic ON i.item_category_id=ic.id
            LEFT JOIN item_icons ico ON i.item_icon_id=ico.id
            -- LEFT JOIN icon_extensions itemext ON i.icon_extension_id=itemext.id
            WHERE i.disabled=0 ";

        if (!is_null($categoryId))
            $sql .= " AND i.item_category_id=:categoryId ";



        $dbh = new Dbh();
        $stmt = $dbh->connect()->prepare($sql);
        if (!is_null($categoryId))
            $stmt->bindParam('categoryId', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        $dbh->connection = null;
        // For now we are returning an array of db rows, but I'll probably want to return
        // an array of item objects

        $items = array();

        foreach ($stmt as $row) {
            array_push($items, new Item($row));
        }

        return $items;
    }


    /**
     * Item constructor. Needs an item_id.
     * @param arg - either id or an array containing all item values from an SQL query
     * @throws Exception
     */
    public function __construct($arg)
    {
        if(is_int($arg)) {
            $this->id=$arg;
            // Look up information about given item.

            // Check that item exists and throw exception if not.
            $sql = "SELECT i.id, i.name, i.item_category_id, ic.name AS item_category_name, i.description,
                i.item_effect_id, i.effect_parameters, i.weight, i.required_level, i.level, i.price, i.max_amount,
                i.item_icon_id, ico.name AS icon_name
                FROM items i
                LEFT JOIN item_categories ic ON i.item_category_id=ic.id
                LEFT JOIN item_icons ico ON i.item_icon_id=ico.id
                -- LEFT JOIN icon_extensions itemext ON i.icon_extension_id=itemext.id
                WHERE i.id = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$arg]);
            $count = $stmt->rowCount();
            // I suppose here I can use a try/catch to make this more user-friendly
            if ($count == 0) throw new InvalidItemException($arg);
            $arg = $stmt->fetch();
        } else if (!is_array($arg)) {
        // if $arg isn't an array and it isn't an int, then the constructor wasn't passed valid args
        throw new InvalidItemException();
        }

        $this->id = (int)$arg['id'];
        $this->name = $arg['name'];
        $this->categoryId = is_null($arg['item_category_id'])?null:$arg['item_category_id'];
        $this->categoryName = $arg['item_category_name'];
        if (!is_null($arg['item_icon_id'])) {
            $this->iconId = (int)$arg['item_icon_id'];
        }
        $this->description = $arg['description'];
        $this->effectId = $arg['item_effect_id']!=null?$arg['item_effect_id']:null;
        $this->effectParameters = $arg['effect_parameters'];
        $this->weight = (int)$arg['weight'];
        $this->requiredLevel = (int)$arg['required_level'];
        $this->level = (int)$arg['level'];
        $this->price = (int)$arg['price'];
        $this->maxAmount = (int)$arg['max_amount'];
        $this->setImageFilename();

        $this->itemVariables = array();
        // Create array of variables used by item
        $sql = "SELECT * FROM item_variables WHERE item_id = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$this->id]);

        while ($row = $stmt->fetch()) {
            array_push($this->itemVariables, new ItemVariable($row));
        }

    }

    /**
     * setImageFilename()
     * This is run on construction 
     */
    protected function setImageFilename() {
        $filename = Config::IMAGE_PATH;
        if (is_integer($this->iconId) && $this->iconId > 0) {
           $filename.='item/item'.sprintf('%04d', $this->iconId);
        } else {
            $filename.= 'items/'.abs($this->id);
        }
        if ( strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false || !empty($_COOKIE['useWebP']) )
        {
            $filename.='.webp';
        } else {
            $filename.='.png';
        }

       $this->imageFilename = $filename;
    }

    public function getImageFilename() {return $this->imageFilename;}
    public function getId() {return $this->id;}
    public function getName() {return $this->name;}
    public function getAmount() {return $this->amount;}
    public function getDescription() {return $this->description;}
    public function getPrice() {return $this->price;}
    public function getWeight() {return $this->weight;}
    public function getMaxAmount() {return $this->maxAmount;}
    public function getLevel() {return $this->level;}
    public function getRequiredLevel() {return $this->requiredLevel;}
    public function getCategoryId() {return $this->categoryId;}
    public function getEffectId() {return $this->effectId;}
    public function getEffectParameters() {return $this->effectParameters;}
    public function getItemVariables() {return $this->itemVariables;}

    /**
     * This is intended to show a button that is customized for each type of effect.
     * If localized, the text can be replaced with UIStrings
     */
    public function getEffectUseString() {
        switch ($this->effectId) {
            case 1: // eat
                return '<i class="fas fa-utensils"></i><br>'._('Eat This Item');
            case 2: // activate_quest
                return '<i class="fas fa-map-signs"></i><br>'._('Accept Quest');
            case 3: // dig
                return '<i class="fas fa-map-marked-alt"></i><br>'._('Dig at This Location');
            default:
                return _('Use This Item');
        }
    }



}