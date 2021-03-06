<?php
/**
 * Zira project.
 * recordslides.php
 * (c)2016 https://github.com/ziracms/zira
 */

namespace Dash\Models;

use Zira;
use Zira\Permission;

class Recordslides extends Model {
    public function addRecordSlides($id, $images) {
        if (empty($id) || !is_array($images) || empty($images)) return array('error' => Zira\Locale::t('An error occurred'));
        if (!Permission::check(Permission::TO_CREATE_RECORDS) || !Permission::check(Permission::TO_EDIT_RECORDS)) {
            return array('error'=>Zira\Locale::t('Permission denied'));
        }
        
        $record = new Zira\Models\Record($id);
        if (!$record->loaded()) {
            return array('error' => Zira\Locale::t('An error occurred'));
        }

        foreach ($images as $image) {
            if (!file_exists(ROOT_DIR . DIRECTORY_SEPARATOR . $image)) {
                return array('error' => Zira\Locale::t('An error occurred'));
            }

            $thumb = Zira\Page::createRecordThumb(ROOT_DIR . DIRECTORY_SEPARATOR . $image, $record->category_id, $record->id, false, true);
            if (!$thumb) continue;

            $slideObj = new Zira\Models\Slide();
            $slideObj->record_id = $record->id;
            $slideObj->thumb = $thumb;
            $slideObj->image = str_replace(DIRECTORY_SEPARATOR, '/', $image);
            //$slideObj->description = Zira\Helper::utf8Clean(strip_tags($record->title));
            $slideObj->save();
        }
        
        $slides_count = Zira\Page::getRecordSlidesCount($record->id);
        $record->slides_count = intval($slides_count);
        $record->save();
        
        Zira\Cache::clear();

        return array('reload' => $this->getJSClassName());
    }
    
    public function addFolderSlides($id, $folder) {
        if (empty($id) || empty($folder)) return array('error' => Zira\Locale::t('An error occurred'));
        if (!Permission::check(Permission::TO_CREATE_RECORDS) || !Permission::check(Permission::TO_EDIT_RECORDS)) {
            return array('error'=>Zira\Locale::t('Permission denied'));
        }
        
        $record = new Zira\Models\Record($id);
        if (!$record->loaded()) {
            return array('error' => Zira\Locale::t('An error occurred'));
        }
        
        $folder = trim((string)$folder,DIRECTORY_SEPARATOR);
        if (strpos($folder,'..')!==false || strpos($folder,UPLOADS_DIR.DIRECTORY_SEPARATOR)!==0) {
            return array('error' => Zira\Locale::t('An error occurred'));
        }
        if ($folder==UPLOADS_DIR) return array('error' => Zira\Locale::t('An error occurred'));
        $path = ROOT_DIR . DIRECTORY_SEPARATOR . $folder;
        if (!file_exists($path)) return array('error' => Zira\Locale::t('An error occurred'));
        if (!is_dir($path)) return array('error' => Zira\Locale::t('An error occurred'));

        $d = opendir($path);
        if (!$d) return array('error' => Zira\Locale::t('An error occurred'));
        
        while(($f = readdir($d))!==false) {
            if ($f == '.' || $f == '..') continue;
            if (is_dir($path . DIRECTORY_SEPARATOR . $f)) continue;
            $image = $folder . DIRECTORY_SEPARATOR . $f;
            if (!file_exists(ROOT_DIR . DIRECTORY_SEPARATOR . $image)) {
                return array('error' => Zira\Locale::t('An error occurred'));
            }

            $thumb = Zira\Page::createRecordThumb(ROOT_DIR . DIRECTORY_SEPARATOR . $image, $record->category_id, $record->id, false, true);
            if (!$thumb) continue;

            $slideObj = new Zira\Models\Slide();
            $slideObj->record_id = $record->id;
            $slideObj->thumb = $thumb;
            $slideObj->image = str_replace(DIRECTORY_SEPARATOR, '/', $image);
            //$slideObj->description = Zira\Helper::utf8Clean(strip_tags($record->title));
            $slideObj->save();
        }
        
        closedir($d);
        
        $slides_count = Zira\Page::getRecordSlidesCount($record->id);
        $record->slides_count = intval($slides_count);
        $record->save();
        
        Zira\Cache::clear();

        return array('reload' => $this->getJSClassName());
    }

    public function saveDescription($id, $description) {
        if (empty($id)) return array('error' => Zira\Locale::t('An error occurred'));
        if (!Permission::check(Permission::TO_CREATE_RECORDS) || !Permission::check(Permission::TO_EDIT_RECORDS)) {
            return array('error'=>Zira\Locale::t('Permission denied'));
        }

        $slide = new Zira\Models\Slide($id);
        if (!$slide->loaded()) {
            return array('error' => Zira\Locale::t('An error occurred'));
        }

        $slide->description = Zira\Helper::utf8Clean(strip_tags($description));
        $slide->save();

        Zira\Cache::clear();

        return array('reload' => $this->getJSClassName(),'message'=>Zira\Locale::t('Successfully saved'));
    }
    
    public function saveLink($id, $link) {
        if (empty($id)) return array('error' => Zira\Locale::t('An error occurred'));
        if (!Permission::check(Permission::TO_CREATE_RECORDS) || !Permission::check(Permission::TO_EDIT_RECORDS)) {
            return array('error'=>Zira\Locale::t('Permission denied'));
        }

        $slide = new Zira\Models\Slide($id);
        if (!$slide->loaded()) {
            return array('error' => Zira\Locale::t('An error occurred'));
        }

        $slide->link = strip_tags($link);
        $slide->save();

        Zira\Cache::clear();

        return array('reload' => $this->getJSClassName(),'message'=>Zira\Locale::t('Successfully saved'));
    }

    public function delete($data) {
        if (empty($data) || !is_array($data)) return array('error' => Zira\Locale::t('An error occurred'));
        if (!Permission::check(Permission::TO_CREATE_RECORDS) || !Permission::check(Permission::TO_EDIT_RECORDS)) {
            return array('error'=>Zira\Locale::t('Permission denied'));
        }

        $record_ids = array();
        foreach($data as $id) {
            $slide = new Zira\Models\Slide($id);
            if (!$slide->loaded()) return array('error' => Zira\Locale::t('An error occurred'));
            $slide->delete();
            
            $record_ids []= $slide->record_id;

            if ($slide->thumb) {
                $thumb = ROOT_DIR . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $slide->thumb);
                if (file_exists($thumb)) @unlink($thumb);
            }
        }
        
        foreach($record_ids as $record_id) {
            $record = new Zira\Models\Record($record_id);
            if (!$record->loaded()) continue;
            $slides_count = Zira\Page::getRecordSlidesCount($record->id);
            $record->slides_count = intval($slides_count);
            $record->save();
        }

        Zira\Cache::clear();

        return array('reload' => $this->getJSClassName());
    }
}