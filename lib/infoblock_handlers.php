<?php

namespace TAO;

/**
 * Class InfoblockHandlers
 * @package TAO
 */
class InfoblockHandlers
{
    /**
     * @param $fields
     */
    public static function OnAfterIBlockUpdate($fields)
    {
        $code = $fields['CODE'];
        $file = \TAO::localDir("cache/infoblock/{$code}.php");
        if (is_file($file)) {
            unlink($file);
        }
    }

    /**
     * @param $fields
     */
    public static function OnBeforeIBlockElementUpdate(&$fields)
    {
        $item = self::makeItem($fields);
        if (!$item) {
            return;
        }

        $item->beforeSaveInner($fields);
        $r = $item->beforeSave();
        if ($r === false) {
            return;
        }

        $r = $item->beforeUpdate();
        if ($r === false) {
            return;
        }

        self::itemToFields($item, $fields);
    }

    /**
     * @param $fields
     */
    public static function OnAfterIBlockElementUpdate(&$fields)
    {
        $item = self::makeItem($fields);
        if (!$item) {
            return;
        }

        $item->afterSave();
        $item->afterUpdate();
        $item->generateUrls($fields);

        self::itemToFields($item, $fields);
    }

    /**
     * @param $fields
     */
    public static function OnBeforeIBlockElementAdd(&$fields)
    {
        $item = self::makeItem($fields);
        if (!$item) {
            return;
        }

        $item->beforeSaveInner($fields);
        $r = $item->beforeSave();
        if ($r === false) {
            return;
        }

        $r = $item->beforeInsert();
        if ($r === false) {
            return;
        }

        self::itemToFields($item, $fields);
    }

    /**
     * @param $fields
     */
    public static function OnAfterIBlockElementAdd(&$fields)
    {
        $item = self::makeItem($fields);
        if (!$item) {
            return;
        }

        $item->afterSave();
        $item->afterInsert();
        $item->generateUrls($fields);

        self::itemToFields($item, $fields);
    }

    /**
     * @param $fields
     */
    protected static function makeItem(&$fields)
    {
        $infoblock = self::getInfoblock($fields);
        if (!$infoblock) {
            return;
        }
        $props = $fields['PROPERTY_VALUES'];
        unset($fields['PROPERTY_VALUES']);
        return $infoblock->makeItem($fields, $props);
    }

    /**
     * @param $item
     * @param $fields
     */
    protected static function itemToFields($item, &$fields)
    {
        foreach ($item->fieldsData as $k => $v) {
            $fields[$k] = $v;
        }
        $fields['PROPERTY_VALUES'] = $item->propertiesData;
    }

    /**
     * @param $fields
     * @return bool|mixed
     */
    protected static function getInfoblock(&$fields)
    {
        if (isset($fields['FROM_TAO_API']) && $fields['FROM_TAO_API']) {
            return false;
        }
        if (!isset($fields['IBLOCK_ID'])) {
            return false;
        }
        return \TAO::getInfoblock((int)$fields['IBLOCK_ID']);

    }

    /**
     *
     */
    public static function register()
    {
        $class = __CLASS__;
        $ref = new \ReflectionClass($class);
        foreach ($ref->getMethods() as $rm) {
            if ($rm->isStatic()) {
                $name = $rm->name;
                if (strpos($name, 'On') === 0) {
                    AddEventHandler("iblock", $name, array($class, $name));
                }
            }
        }
    }


}


InfoblockHandlers::register();