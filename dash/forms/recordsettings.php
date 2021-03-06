<?php
/**
 * Zira project.
 * recordsettings.php
 * (c)2016 https://github.com/ziracms/zira
 */

namespace Dash\Forms;

use Zira;
use Zira\Form;
use Zira\Locale;

class Recordsettings extends Form
{
    const THUMB_MIN_SIZE = 50;
    const THUMB_MAX_SIZE = 600;
    
    protected $_id = 'dash-recordsettings-form';

    protected $_label_class = 'col-sm-5 control-label';
    protected $_input_wrap_class = 'col-sm-7';
    protected $_input_offset_wrap_class = 'col-sm-offset-5 col-sm-7';
    protected $_select_wrapper_class = 'col-sm-5';

    protected $_checkbox_inline_label = false;

    public function __construct()
    {
        parent::__construct($this->_id);
    }

    protected function _init()
    {
        $this->setRenderPanel(false);
        $this->setFormClass('form-horizontal dash-window-form');
    }

    protected function _render()
    {
        $q_arr = array();
        for ($i=50; $i<=Zira\Image::QUALITY_JPEG; $i+=5) {
            $q_arr[(string)$i] = $i.'%';
        }
        $html = $this->open();
        $html .= $this->input(Locale::t('Thumbs width'), 'thumbs_width', array('placeholder'=>self::THUMB_MIN_SIZE.' - '.self::THUMB_MAX_SIZE));
        $html .= $this->input(Locale::t('Thumbs height'), 'thumbs_height', array('placeholder'=>self::THUMB_MIN_SIZE.' - '.self::THUMB_MAX_SIZE));
        $html .= $this->select(Locale::t('Image quality'), 'jpeg_quality', $q_arr);
        $html .= $this->checkbox(Locale::t('Create thumbnails'), 'create_thumbnails', null, false);
        $html .= $this->select(Locale::t('Slider type'), 'slider_type', array('default'=>Locale::t('Default'), 'slider3d'=>Locale::t('3D slider'), 'fullscreen'=>Locale::t('Fullscreen slider')));
        $html .= $this->radioButton(Locale::t('Slider mode'), 'slider_mode', array('1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5'));
        $html .= $this->checkbox(Locale::t('Show slider'), 'slider_enabled', null, false);
        $html .= $this->input(Locale::t('Gallery thumbs width'), 'gallery_thumbs_width', array('placeholder'=>self::THUMB_MIN_SIZE.' - '.self::THUMB_MAX_SIZE));
        $html .= $this->input(Locale::t('Gallery thumbs height'), 'gallery_thumbs_height', array('placeholder'=>self::THUMB_MIN_SIZE.' - '.self::THUMB_MAX_SIZE));
        $html .= $this->input(Locale::t('Gallery limit'), 'gallery_limit');
        $html .= $this->select(Locale::t('Gallery sorting'), 'gallery_sorting', array('asc'=>Locale::t('Ascending'), 'desc'=>Locale::t('Descending')));
        $html .= $this->checkbox(Locale::t('Show gallery'), 'gallery_enabled', null, false);
        $html .= $this->checkbox(Locale::t('Show files'), 'files_enabled', null, false);
        $html .= $this->checkbox(Locale::t('Show audio'), 'audio_enabled', null, false);
        $html .= $this->checkbox(Locale::t('Show video'), 'video_enabled', null, false);
        $html .= $this->checkbox(Locale::t('Enable rating'), 'rating_enabled', null, false);
        $html .= $this->checkbox(Locale::t('Display author'), 'display_author', null, false);
        $html .= $this->checkbox(Locale::t('Display date'), 'display_date', null, false);
        $html .= $this->close();
        return $html;
    }

    protected function _validate() {
        $validator = $this->getValidator();

        $validator->registerNumber('thumbs_width',self::THUMB_MIN_SIZE,self::THUMB_MAX_SIZE,true,Locale::t('Invalid value "%s"',Locale::t('Thumbs width')));
        $validator->registerNumber('thumbs_height',self::THUMB_MIN_SIZE,self::THUMB_MAX_SIZE,true,Locale::t('Invalid value "%s"',Locale::t('Thumbs height')));
        $validator->registerNumber('gallery_thumbs_width',self::THUMB_MIN_SIZE,self::THUMB_MAX_SIZE,true,Locale::t('Invalid value "%s"',Locale::t('Gallery thumbs width')));
        $validator->registerNumber('gallery_thumbs_height',self::THUMB_MIN_SIZE,self::THUMB_MAX_SIZE,true,Locale::t('Invalid value "%s"',Locale::t('Gallery thumbs height')));
        $validator->registerNumber('gallery_limit',0,1000,true,Locale::t('Invalid value "%s"',Locale::t('Gallery limit')));
        $validator->registerCustom(array(get_class(), 'checkGallerySorting'), 'gallery_sorting', Locale::t('Invalid value "%s"',Locale::t('Gallery sorting')));

    }
    
    public static function checkGallerySorting($sorting) {
        return in_array($sorting, array('asc', 'desc', 'ASC', 'DESC'));
    }
}