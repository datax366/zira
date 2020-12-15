<?php
/**
 * Zira project.
 * search.php
 * (c)2016 https://github.com/ziracms/zira
 */

namespace Zira\Models;

use Zira\Locale;
use Zira\Orm;

class Search extends Orm {
    const MIN_CHARS = 3;
    const MAX_KEYWORDS = 5;

    public static $table = 'search';
    public static $pk = 'id';
    public static $alias = 'srch';

    public static function getTable() {
        return self::$table;
    }

    public static function getPk() {
        return self::$pk;
    }

    public static function getAlias() {
        return self::$alias;
    }

    public static function getReferences() {
        return array(
            Record::getClass() => 'record_id'
        );
    }

    public static function clearRecordIndex($record) {
        self::getCollection()
            ->delete()
            ->where('record_id', '=', $record->id)
            ->execute();
    }

    public static function indexRecord($record) {
        self::clearRecordIndex($record);

        if ($record->published != Record::STATUS_PUBLISHED) return;

        $keywords_str = '';
        if ($record->meta_keywords) {
            $keywords = explode(',', $record->meta_keywords);
            foreach ($keywords as $keyword) {
                $keywords_str .= ' ' . $keyword;
            }
        }

        $text = $record->title . ' ' . $record->meta_title . ' ' . $keywords_str . ' ' . date('Y-m-d', strtotime($record->creation_date));
        $text = trim($text);
        if (empty($text)) return;
        $text = mb_strtolower($text, CHARSET);
        $text = preg_replace('/[\x20]+/',' ', $text);
        $keywords = explode(' ', $text);

        $added = array();
        foreach($keywords as $keyword) {
            if (in_array($keyword, $added)) continue;
            if (mb_strlen($keyword, CHARSET)<self::MIN_CHARS) continue;
            $index = new self();
            $index->keyword = $keyword;
            $index->record_id = $record->id;
            $index->language = $record->language;
            $index->save();
            $added []= $keyword;
        }
    }

    public static function getRecords($text, $limit = 10, $offset = 0) {
        $text = trim($text);
        if (empty($text)) return array();
        $text = mb_strtolower($text, CHARSET);
        $keywords = explode(' ', $text);

        $query = self::getCollection();

        $added = array();
        foreach($keywords as $index=>$keyword) {
            if (mb_strlen($keyword, CHARSET)<self::MIN_CHARS) continue;
            if (in_array($keyword, $added)) continue;
            if (count($added)>0) $query->union();
            $query->open_query();
            $query->select('record_id');
            $query->where('language','=',Locale::getLanguage());
            $query->and_where('keyword','like',$keyword.'%');
            $query->group_by('record_id');
            $query->order_by('id', 'ASC');
            $query->limit($limit+$offset);
            $query->close_query();
            $added []= $keyword;
            if (count($added)>=self::MAX_KEYWORDS) break;
        }

        if (empty($added)) return array();

        $query->merge();
        $query->limit($limit, $offset);
        $rows = $query->get();

        $results = array();
        foreach($rows as $row) {
            $results[]=$row->record_id;
        }

        if (empty($results)) return array();

//        return Record::getCollection()
//                        ->select('id', 'name','author_id','title','description','thumb','creation_date','rating','comments')
//                        ->left_join(Category::getClass(), array('category_name'=>'name', 'category_title'=>'title'))
//                        ->where('id','in',$results)
//                        ->get();

        $query = Record::getCollection();
        foreach($results as $index=>$result) {
            if ($index>0) $query->union();
            $query->select('id', 'name', 'author_id', 'title', 'description', 'thumb', 'creation_date', 'rating', 'comments')
                        ->left_join(Category::getClass(), array('category_name' => 'name', 'category_title' => 'title'))
                        ->where('id', '=', $result);
        }

        return $query->get();
    }

    public static function getRecordsSorted($text, $limit = 5) {
        $text = trim($text);
        if (empty($text)) return array();
        $text = mb_strtolower($text, CHARSET);
        $keywords = explode(' ', $text);

        $query = self::getCollection();

        $added = array();
        foreach($keywords as $index=>$keyword) {
            if (mb_strlen($keyword, CHARSET)<self::MIN_CHARS) continue;
            if (in_array($keyword, $added)) continue;
            if (count($added)>0) $query->union();
            $query->open_query();
            $query->where('language','=',Locale::getLanguage());
            $query->and_where('keyword','like',$keyword.'%');
            $query->group_by('record_id');
            $query->order_by('id', 'ASC');
            $query->limit($limit);
            $query->close_query();
            $added []= $keyword;
            if (count($added)>=self::MAX_KEYWORDS) break;
        }

        if (empty($added)) return array();
        $rows = $query->get();

        $results = array();
        foreach($rows as $row) {
            if (!array_key_exists($row->record_id, $results)) {
                $results[$row->record_id] = 0;
            }
            $results[$row->record_id]++;
        }

        if (empty($results)) return array();

        arsort($results);

        $rows = Record::getCollection()
                        ->select('id', 'name','author_id','title','description','thumb','creation_date','rating','comments')
                        ->left_join(Category::getClass(), array('category_name'=>'name', 'category_title'=>'title'))
                        ->where('id','in',array_keys($results))
                        ->get();

        if (empty($rows)) return array();

        $_results = array();
        foreach($rows as $row) {
            $_results[$row->id] = $row;
        }

        $return = array();
        foreach($results as $id=>$co) {
            if (!array_key_exists($id, $_results)) continue;
            $return []= $_results[$id];
        }

        if (count($return)>$limit) return array_slice($return, 0, $limit);
        else return $return;
    }

    public static function getRecordsFullText($text, $limit = 10, $offset = 0) {
        $text = trim($text);
        if (empty($text)) return array();
        $keywords = explode(' ', $text);

        $query = Record::getCollection();

        $added = array();
        foreach($keywords as $keyword) {
            if (mb_strlen($keyword, CHARSET) < self::MIN_CHARS) continue;
            if (in_array($keyword, $added)) continue;
            if (count($added) > 0) $query->union();
            $query->open_query();
            $query->select('id');
            $query->where('language', '=', Locale::getLanguage());
            $query->and_where('published', '=', Record::STATUS_PUBLISHED);
            $query->and_where();
            $query->open_where();
            $query->where('title', 'like', '%'.$keyword.'%');
            $query->or_where('description', 'like', '%'.$keyword.'%');
            $query->or_where('content', 'like', '%'.$keyword.'%');
            $query->close_where();
            $query->order_by('id', 'DESC');
            $query->limit($limit+$offset);
            $query->close_query();
            $added []= $keyword;
            if (count($added) >= self::MAX_KEYWORDS) break;
        }

        if (empty($added)) return array();

        $query->merge();
        $query->limit($limit, $offset);
        $rows = $query->get();

        if (empty($rows)) return array();

        $query = Record::getCollection();
        foreach($rows as $index=>$row) {
            if ($index>0) $query->union();
            $query->select('id', 'name', 'author_id', 'title', 'description', 'thumb', 'creation_date', 'rating', 'comments')
            ->left_join(Category::getClass(), array('category_name' => 'name', 'category_title' => 'title'))
            ->where('id', '=', $row->id);
        }

        return $query->get();
    }
}