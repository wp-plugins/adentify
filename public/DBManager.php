<?php
/**
 * Created by PhpStorm.
 * User: pierrickmartos
 * Date: 27/10/14
 * Time: 12:06
 */

class DBManager
{
    protected static $instance = null;

    public static function getInstance()
    {
        if (!self::$instance)
            self::$instance = new DBManager();

        return self::$instance;
    }

    /**
     * Insert photo in wordpress table
     *
     * @param Photo $photo
     * @param $wordpressMediaId
     */
    public function insertPhoto(Photo $photo, $wordpressMediaId)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . ADENTIFY_SQL_TABLE_PHOTOS;

        $wpdb->insert(
            $table_name,
            array(
                'time' => current_time( 'mysql' ),
                'wordpress_photo_id' => $wordpressMediaId,
                'adentify_photo_id' => $photo->getId(),
                'thumb_url' => $photo->getSmallUrl()
            )
        );
    }

    public function getPhotos()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . ADENTIFY_SQL_TABLE_PHOTOS;
        return $wpdb->get_results("SELECT adentify_photo_id, thumb_url, wordpress_photo_id FROM $table_name");
    }

    public function deletePhoto($wp_photo_id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . ADENTIFY_SQL_TABLE_PHOTOS;
        return $wpdb->delete($table_name, array('wordpress_photo_id' => $wp_photo_id));
    }
} 