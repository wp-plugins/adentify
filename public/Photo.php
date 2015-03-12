<?php
/**
 * Created by PhpStorm.
 * User: pierrickmartos
 * Date: 23/09/2014
 * Time: 16:09
 */

class Photo
{
    protected $client;
    protected $id;
    protected $json;
    protected $caption;
    protected $smallUrl;

    public function __construct($id = null)
    {
        $this->id = $id;
        $this->client = new GuzzleHttp\Client();
    }

    public function load()
    {
        $photo = APIManager::getInstance()->getPhoto($this->id);
        if (!empty($photo)) {
            $this->setJson($photo);
        }
    }

    public function render($renderWithTags = true, $zIndex = 0)
    {
        return $this->getJson() ? Twig::render('photo.html.twig', array(
            'photoId' => $this->getId(),
            'link' => $this->getLink(),
            'imageUrl' => $this->getImageUrl(),
            'caption' => $this->getCaption(),
            'tags' => $renderWithTags ? $this->getTags() : null,
            'tagShape' => get_option(unserialize(ADENTIFY__PLUGIN_SETTINGS)['TAGS_SHAPE']),
            'tagsVisibility' => get_option(unserialize(ADENTIFY__PLUGIN_SETTINGS)['TAGS_VISIBILITY']),
            'renderWithTags' => $renderWithTags,
            'googleMapsAPIKey' => get_option(unserialize(ADENTIFY__PLUGIN_SETTINGS)['GOOGLE_MAPS_KEY']),
            'zIndex' => $zIndex,
        )) : 'Can\'t load this image.';
    }

    /**
     * Serialize photo to an array
     *
     * @return array
     */
    public function serialize($data = array())
    {
        $photo = array(
            'source' => 'wordpress',
	        'visibility_scope' => get_option(unserialize(ADENTIFY__PLUGIN_SETTINGS)['IS_PRIVATE']) ? 'public' : 'private',
        );
        if ($this->caption)
            $photo['caption'] = $this->caption;
        $photo = array_merge($photo, $data);
        return $photo;
    }

    public function getLink()
    {
        // TODO: add language config
        return sprintf('https://adentify.com/en/app/photo/%s/', $this->getId());
    }

    public function getImageUrl()
    {
        return $this->getJson()->large_url;
    }

    public function getCaption()
    {
        return isset($this->getJson()->caption) ? $this->getJson()->caption : null;
    }

    /**
     * @param mixed $caption
     */
    public function setCaption($caption)
    {
        $this->caption = $caption;
        return $this;
    }

    public function getTags()
    {
        $tags = $this->getJson()->tags;
        if (count($tags) > 0) {
            foreach($tags as $tag) {
                $tag->style = sprintf('left: %s%%; top: %s%%; margin-left: %spx; margin-top: %spx', ($tag->x_position * 100), ($tag->y_position * 100),  '-15', '-15');
            }
        }

        return $tags;
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
    }

    /**
     * @return mixed
     */
    public function getJson()
    {
        return $this->json;
    }

    /**
     * @param mixed $json
     */
    public function setJson($json)
    {
        $this->json = json_decode($json);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVisibilityScope()
    {
        return $this->visibilityScope;
    }

    /**
     * @param mixed $visibilityScope
     */
    public function setVisibilityScope($visibilityScope)
    {
        $this->visibilityScope = $visibilityScope;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSmallUrl()
    {
        return $this->smallUrl;
    }

    /**
     * @param mixed $smallUrl
     */
    public function setSmallUrl($smallUrl)
    {
        $this->smallUrl = $smallUrl;
        return $this;
    }
}