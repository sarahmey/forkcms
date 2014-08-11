<?php

namespace Backend\Modules\Location\Engine;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Language as BL;
use Backend\Core\Engine\Model as BackendModel;

/**
 * In this file we store all generic functions that we will be using in the location module
 *
 * @author Matthias Mullie <forkcms@mullie.eu>
 * @author Jelmer Snoeck <jelmer@siphoc.com>
 * @author Jeroen Desloovere <jeroen@siesqo.be>
 */
class Model
{
    const QRY_DATAGRID_BROWSE =
        'SELECT id, title, CONCAT(street, " ", number, ", ", zip, " ", city, ", ", country) AS address
         FROM location
         WHERE language = ?';

    /**
     * Delete an item
     *
     * @param int $id The id of the record to delete.
     */
    public static function delete($id)
    {
        // get db
        $db = BackendModel::getContainer()->get('database');

        // get item
        $item = self::get($id);

        // build extra
        $extra = array(
            'id' => $item['extra_id'],
            'module' => 'Location',
            'type' => 'widget',
            'action' => 'Location'
        );

        $db->delete('modules_extras', 'id = ? AND module = ? AND type = ? AND action = ?', array($extra['id'], $extra['module'], $extra['type'], $extra['action']));
        $db->delete('location', 'id = ? AND language = ?', array((int) $id, BL::getWorkingLanguage()));
        $db->delete('location_settings', 'map_id = ?', array((int) $id));
    }

    /**
     * Check if an item exists
     *
     * @param int $id The id of the record to look for.
     * @return bool
     */
    public static function exists($id)
    {
        return (bool) BackendModel::getContainer()->get('database')->getVar(
            'SELECT 1
             FROM location AS i
             WHERE i.id = ? AND i.language = ?
             LIMIT 1',
            array((int) $id, BL::getWorkingLanguage())
        );
    }

    /**
     * Fetch a record from the database
     *
     * @param int $id The id of the record to fetch.
     * @return array
     */
    public static function get($id)
    {
        return (array) BackendModel::getContainer()->get('database')->getRecord(
            'SELECT i.*
             FROM location AS i
             WHERE i.id = ? AND i.language = ?',
            array((int) $id, BL::getWorkingLanguage())
        );
    }

    /**
     * Fetch a record from the database
     *
     * @return array
     */
    public static function getAll()
    {
        return (array) BackendModel::getContainer()->get('database')->getRecords(
            'SELECT i.*
             FROM location AS i
             WHERE i.language = ? AND i.show_overview = ?',
            array(BL::getWorkingLanguage(), 'Y')
        );
    }

    /**
     * Retrieve a map setting
     *
     * @param int $mapId
     * @param string $name
     * @return mixed
     */
    public static function getMapSetting($mapId, $name)
    {
        $serializedData = (string) BackendModel::getContainer()->get('database')->getVar(
            'SELECT s.value
             FROM location_settings AS s
             WHERE s.map_id = ? AND s.name = ?',
            array((int) $mapId, (string) $name)
        );

        if ($serializedData != null) return unserialize($serializedData);
        return false;
    }

    /**
     * Fetch all the settings for a specific map
     *
     * @param int $mapId
     * @return array
     */
    public static function getMapSettings($mapId)
    {
        $mapSettings = (array) BackendModel::getContainer()->get('database')->getPairs(
            'SELECT s.name, s.value
             FROM location_settings AS s
             WHERE s.map_id = ?',
            array((int) $mapId)
        );

        foreach ($mapSettings as $key => $value) $mapSettings[$key] = unserialize($value);

        return $mapSettings;
    }

    /**
     * Insert an item
     *
     * @param array $item The data of the record to insert.
     * @return int
     */
    public static function insert($item)
    {
        // define database
        $db = BackendModel::getContainer()->get('database');

        $item['created_on'] = BackendModel::getUTCDate();
        $item['edited_on'] = BackendModel::getUTCDate();

        // insert extra
        $item['extra_id'] = BackendModel::insertExtra(
            'widget',
            'Location'
        );

        // insert new location
        $item['id'] = $db->insert('location', $item);

        // update extra (item id is now known)
        BackendModel::updateExtra(
            $item['extra_id'],
            'data',
            array(
                'id' => $item['id'],
                'extra_label' => \SpoonFilter::ucfirst(BL::lbl('Location', 'Core')) . ': ' . $item['title'],
                'language' => $item['language'],
                'edit_url' => BackendModel::createURLForAction('Edit') . '&id=' . $item['id']
            )
        );

        // return the new id
        return $item['id'];
    }

    /**
     * Save the map settings
     *
     * @param int $mapId
     * @param string $key
     * @param mixed $value
     */
    public static function setMapSetting($mapId, $key, $value)
    {
        $value = serialize($value);

        BackendModel::getContainer()->get('database')->execute(
            'INSERT INTO location_settings(map_id, name, value)
             VALUES(?, ?, ?)
             ON DUPLICATE KEY UPDATE value = ?',
            array((int) $mapId, $key, $value, $value)
        );
    }

    /**
     * Update an item
     *
     * @param array $item The data of the record to update.
     * @return int
     */
    public static function update($item)
    {
        $db = BackendModel::getContainer()->get('database');
        $item['edited_on'] = BackendModel::getUTCDate();

        if (isset($item['extra_id'])) {
            // build extra
            $extra = array(
                'id' => $item['extra_id'],
                'module' => 'Location',
                'type' => 'widget',
                'label' => 'Location',
                'action' => 'Location',
                'data' => serialize(array(
                    'id' => $item['id'],
                    'extra_label' => \SpoonFilter::ucfirst(BL::lbl('Location', 'core')) . ': ' . $item['title'],
                    'language' => $item['language'],
                    'edit_url' => BackendModel::createURLForAction('Edit') . '&id=' . $item['id'])
                ),
                'hidden' => 'N'
            );

            // update extra
            $db->update('modules_extras', $extra, 'id = ? AND module = ? AND type = ? AND action = ?', array($extra['id'], $extra['module'], $extra['type'], $extra['action']));
        }

        // update item
        return $db->update('location', $item, 'id = ? AND language = ?', array($item['id'], $item['language']));
    }
}
