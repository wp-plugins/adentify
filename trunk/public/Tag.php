<?php
/**
 * Created by PhpStorm.
 * User: pierrickmartos
 * Date: 10/10/2014
 * Time: 12:22
 */

class Tag
{
    const TYPE_PLACE =   "place";
    const TYPE_PRODUCT = "product";
    const TYPE_PERSON =  "person";

    const VALIDATION_NONE =    "none";
    const VALIDATION_WAITING = "waiting";
    const VALIDATION_DENIED =  "denied";
    const VALIDATION_GRANTED = "granted";

    private $id;

    private $type = self::TYPE_PRODUCT;

    private $title;

    private $description;

    private $link;

    private $xPosition;

    private $yPosition;

    private $photo;

    private $venue;

    private $product;

    private $productType;

    private $person;

    private $brand;

    public function serialize($data = array())
    {
        $tag = array(
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'link' => $this->link,
            'x_position' => $this->xPosition,
            'y_position' => $this->yPosition,
            'photo' => $this->photo,
        );

        if ($this->venue)
            $tag['venue'] = $this->venue;
        if ($this->product)
            $tag['product'] = $this->product;
        if ($this->productType)
            $tag['productType'] = $this->productType;
        if ($this->person)
            $tag['person'] = $this->person;
        if ($this->brand)
            $tag['brand'] = $this->brand;

        $tag = array_merge($tag, $data);
        return $tag;
    }

    static function loadPost($postArray)
    {
        $venue = null;
        $product = null;
        $brand = null;
        $person = null;
        if (array_key_exists('extraData', $postArray)) {
            if (array_key_exists('venue', $postArray['extraData'])) {
                $response = APIManager::getInstance()->postVenue($postArray['extraData']['venue']);
                if ($response)
                    $venue = json_decode($response->getBody());
            }
            if (array_key_exists('brand', $postArray['extraData'])) {
                $response = APIManager::getInstance()->postBrand($postArray['extraData']['brand']);
                if ($response)
                    $brand = json_decode($response->getBody());
            }
            if (array_key_exists('product', $postArray['extraData'])) {
                $brandId = ($brand) ? $brand->id : $postArray['brand'];
                $response = APIManager::getInstance()->postProduct($postArray['extraData']['product'], $brandId);
                if ($response) {
                    $product = json_decode($response->getBody());
                }
            }
            if (array_key_exists('person', $postArray['extraData'])) {
                $response = APIManager::getInstance()->postPerson($postArray['extraData']['person']);
                if ($response)
                    $person = json_decode($response->getBody());
            }

        }

        $tag = new Tag;
        $tag->setType($postArray['type']);
        $tag->setTitle($postArray['title']);
        $tag->setDescription($postArray['description']);
        $tag->setLink($postArray['link']);
        $tag->setXPosition($postArray['x_position']);
        $tag->setYPosition($postArray['y_position']);
        $tag->setPhoto($postArray['photo']);

        switch ($tag->getType()) {
            case 'product':
                $tag->setProductType((array_key_exists('productType', $postArray) ? $postArray['productType'] : null));
                $tag->setProduct((array_key_exists('product', $postArray) ? $postArray['product'] : ($product ? $product->id : null)));
                if ($product && $product->brand)
                    $tag->setBrand($product->brand->id);
                else if ($brand)
                    $tag->setBrand($brand->id);
                else if (array_key_exists('brand', $postArray))
                    $tag->setBrand($postArray['brand']);
                if ($tag->getProduct() && $tag->getBrand())
                    return $tag;
                break;
            case 'place':
                $tag->setVenue((array_key_exists('venue', $postArray) ? $postArray['venue'] : ($venue ? $venue->id : null)));
                if ($tag->getVenue())
                    return $tag;
                break;
            case 'person':
                $tag->setPerson((array_key_exists('person', $postArray) ? $postArray['person'] : ($person ? $person->id : null)));
                if ($tag->getPerson())
                    return $tag;
                break;
            default:
                break;
        }
        return array('error' => 0);
    }

    /**
     * @return mixed
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param mixed $brand
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param mixed $link
     */
    public function setLink($link)
    {
        $this->link = $link;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * @param mixed $person
     */
    public function setPerson($person)
    {
        $this->person = $person;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * @param mixed $photo
     */
    public function setPhoto($photo)
    {
        $this->photo = $photo;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param mixed $product
     */
    public function setProduct($product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProductType()
    {
        return $this->productType;
    }

    /**
     * @param mixed $productType
     */
    public function setProductType($productType)
    {
        $this->productType = $productType;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVenue()
    {
        return $this->venue;
    }

    /**
     * @param mixed $venue
     */
    public function setVenue($venue)
    {
        $this->venue = $venue;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getXPosition()
    {
        return $this->xPosition;
    }

    /**
     * @param mixed $xPosition
     */
    public function setXPosition($xPosition)
    {
        $this->xPosition = $xPosition;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getYPosition()
    {
        return $this->yPosition;
    }

    /**
     * @param mixed $yPosition
     */
    public function setYPosition($yPosition)
    {
        $this->yPosition = $yPosition;
        return $this;
    }
} 