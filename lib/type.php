<?php

namespace TAO;

/**
 * Class InfoblockType
 * @package TAO
 */
class InfoblockType
{

    public static function check($type, $data = false)
    {
        if (!$data) {
            $data = $type;
        }
        if (is_string($data)) {
            $data = array('NAME' => $data);
        }
        if (!is_array($data)) {
            print 'Invalid infoblock type description: ';
            var_dump($data);
            die;
        }
        if (!isset($data['ID'])) {
            $data['ID'] = $type;
        }

        $type = trim($data['ID']);
        $result = \CIBlockType::GetByID($type);
        $cdata = $result->Fetch();
        if ($cdata) {
            $langs = array();
            foreach (array_keys(\TAO::getLangs()) as $lang) {
                $ldata = \CIBlockType::GetByIDLang($type, $lang);
                $name = $ldata['NAME'];
                if ($lang == 'ru' && isset($data['NAME'])) {
                    $name = $data['NAME'];
                }
                $langs[$lang] = array(
                    'NAME' => $name,
                    'ELEMENT_NAME' => $ldata['ELEMENT_NAME'],
                    'SECTION_NAME' => $ldata['SECTION_NAME'],
                );
            }
            $cdata['LANG'] = $langs;
            if (isset($data['SECTIONS'])) {
                $cdata['SECTIONS'] = $data['SECTIONS'];
            }
            if (isset($data['IN_RSS'])) {
                $cdata['IN_RSS'] = $data['IN_RSS'];
            }
            if (isset($data['SORT'])) {
                $cdata['SORT'] = $data['SORT'];
            }
            if (!isset($data['EDIT_FILE_BEFORE'])) {
                $cdata['EDIT_FILE_BEFORE'] = '';
            }
            if (!isset($data['EDIT_FILE_AFTER'])) {
                $cdata['EDIT_FILE_AFTER'] = '';
            }
            self::updateType($cdata);
        } else {
            if (is_string($data['NAME'])) {
                $data['LANG'] = array(
                    'ru' => array('NAME' => $data['NAME']),
                );
            }
            foreach (array_keys(\TAO::getLangs()) as $lang) {
                if (isset($data['LANG'][$lang]) && is_string($data['LANG'][$lang])) {
                    $data['LANG'][$lang] = array('NAME' => $data['LANG'][$lang]);
                }
            }
            if (!isset($data['SECTIONS'])) {
                $data['SECTIONS'] = 'N';
            }
            if (!isset($data['IN_RSS'])) {
                $data['IN_RSS'] = 'N';
            }
            if (!isset($data['SORT'])) {
                $data['SORT'] = '500';
            }
            if (!isset($data['EDIT_FILE_BEFORE'])) {
                $data['EDIT_FILE_BEFORE'] = '';
            }
            if (!isset($data['EDIT_FILE_AFTER'])) {
                $data['EDIT_FILE_AFTER'] = '';
            }
            self::addNewType($data);
        }
    }

    /**
     * @return int
     */
    public function sort()
    {
        return 500;
    }

    /**
     * @throws AddTypeException
     */
    protected static function addNewType($data)
    {
        global $DB;
        $DB->StartTransaction();
        $o = new \CIBlockType;
        $res = $o->Add($data);
        if (!$res) {
            $DB->Rollback();
            throw new AddTypeException("Error create type " . $data['ID']);
        } else {
            $DB->Commit();
        }
    }

    /**
     * @throws UpdateTypeException
     */
    protected static function updateType($data)
    {
        global $DB;
        $DB->StartTransaction();
        $o = new \CIBlockType;
        $res = $o->Update($data['ID'], $data);
        if (!$res) {
            $DB->Rollback();
            throw new UpdateTypeException("Error update type " . $data['ID']);
        } else {
            $DB->Commit();
        }
    }
}
