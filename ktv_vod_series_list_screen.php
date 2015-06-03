<?php
///////////////////////////////////////////////////////////////////////////
require_once 'lib/vod/vod.php';
require_once 'lib/vod/vod_series_list_screen.php';
require_once 'ktv_check_bin.php';

class KtvVodSeriesListScreen extends VodSeriesListScreen
implements UserInputHandler
{
    const ID = 'vod_series';

    public static function get_media_url_str($movie_id)
    {
        return MediaURL::encode(
            array(
                'screen_id' => self::ID,
                'movie_id' => $movie_id));
    }

    ///////////////////////////////////////////////////////////////////////

    private $vod;

    public function __construct(Vod $vod)
    {
        $this->vod = $vod;
		
        parent::__construct(self::ID, self::get_folder_views());
		UserInputHandlerRegistry::get_instance()->
            register_handler($this);
    }

    ///////////////////////////////////////////////////////////////////////

    public function get_action_map(MediaURL $media_url, &$plugin_cookies)
    {
        $actions = array();
		
        $actions[GUI_EVENT_KEY_ENTER] = ActionFactory::open_folder();
       
        $actions[GUI_EVENT_KEY_ENTER] = ActionFactory::vod_play();
		
		$popup_menu_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'popup_menu');
					
		$actions[GUI_EVENT_KEY_POPUP_MENU] = $popup_menu_action;
		
		return $actions;
    }
	public function get_handler_id()
    { return self::ID; }
	///////////////////////////////////////////////////////////////////////////
    public function handle_user_input(&$user_input, &$plugin_cookies)
    {
        $ip_path = isset($plugin_cookies->ip_path) ? 
			$plugin_cookies->ip_path : '';
		$smb_user = isset($plugin_cookies->smb_user) ? 
			$plugin_cookies->smb_user : 'guest';
		$smb_pass = isset($plugin_cookies->smb_pass) ? 
			$plugin_cookies->smb_pass : 'guest';
		#hd_print('Vod favorites: handle_user_input:');
        foreach ($user_input as $key => $value)
            #hd_print("  $key => $value");

        if ($user_input->control_id == 'popup_menu')
        {
            if (!isset($user_input->selected_media_url))
                return null;

            $media_url = MediaURL::decode($user_input->selected_media_url);
            $movie_id = $media_url->movie_id;
			
			//////
			$bgr_rs = DuneSystem::$properties['tmp_dir_path'] . '/background_rec_stop';
			if (file_exists($bgr_rs)) {
			$name = trim(file_get_contents($bgr_rs));
			$dd = "/tmp/".$name."_kartinarec.sh";
			if (!file_exists($dd))
				unlink($bgr_rs);
			}
			
			$background_rec_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'background_rec');
            $background_rec_caption = 'Фоновая запись фильма';
			$background_stoprec_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'background_stoprec');
            $background_stoprec_caption = 'Остановить Запись';
	
			$menu_items[] = array(
                GuiMenuItemDef::caption => $background_rec_caption,
                GuiMenuItemDef::action => $background_rec_action);
			if (file_exists($bgr_rs)) {
			$menu_items[] = array(
                GuiMenuItemDef::caption => $background_stoprec_caption,
                GuiMenuItemDef::action => $background_stoprec_action);}
			////////

            return ActionFactory::show_popup_menu($menu_items);
        }
		elseif ($user_input->control_id == 'info')
        {
            if (!isset($user_input->selected_media_url))
                return null;

            $media_url = MediaURL::decode($user_input->selected_media_url);
            

            $movies = $this->vod->get_loaded_movie($media_url->movie_id, $plugin_cookies);
            $title = $movies->name;
			$movie_id = $movies->id;
			$lenght = $movies->length_min;
			$text = $movies->description;
			$newtext_1 = wordwrap($text, 100, "\n");
			$newtext = self::cropStr($newtext_1, 500);
			$defs = array();
			$texts = explode("\n", $newtext);
			$texts = array_values($texts);
			ControlFactory::add_label($defs, "ID фильма: ", $movie_id);
			ControlFactory::add_label($defs, "Длительность: ", $lenght . ' мин.');
			foreach($texts as $text)
			{
				
				ControlFactory::add_label($defs, "", $text);
			}
			ControlFactory::add_close_dialog_button($defs, 'OK', 150);

			return ActionFactory::show_dialog($title, $defs);
        }
		//////////////////////
		else if ($user_input->control_id === 'background_rec')
		{
		if (!isset($user_input->selected_media_url))
        return null;
		$media_url = MediaURL::decode($user_input->selected_media_url);
                $movie_id = $media_url->movie_id;
		$movies = $this->vod->get_loaded_movie($media_url->movie_id, $plugin_cookies);
		
		$title = $movies->name;
		$ru_title = self::translit_to_rus($title);
		#hd_print("ru_title----->$ru_title");
		$series_id = $media_url->series_id;
		#hd_print("series_id----->$series_id");
		$streaming_url_1 = $this->vod->get_vod_pl($series_id, &$plugin_cookies);
		#hd_print("streaming_url_1----->$streaming_url_1");
		$rec_path = self::get_rec_path($plugin_cookies);
		#hd_print("rec_path----->$rec_path");
		if (preg_match('/"url":"([^"]*) :http-caching/i', $streaming_url_1, $ttt))
		{
		$streaming_url = $ttt[1];
		}
		#hd_print("URL_REC----->$streaming_url");
		$rec_name = $ru_title;
		$ptl = "http";
		$rec_script = '/tmp/arch_kart/rec';
		CHECK::check_for_arch();
		$cmd_rec = "$rec_script --$ptl \"$streaming_url\" \"$rec_name\" \"$rec_path\"";
		$free = "$rec_path";
		if (!file_exists($rec_path))
		return ActionFactory::show_title_dialog("Накопитель для записи не найден!!! Подключите к плееру накопитель.");
		if (preg_match('/\/D\//',$rec_path)){
		$bytes = disk_free_space ('/D/');
		#hd_print("bytes------------->$bytes");
		$si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
		$base = 1024;
		$class = min((int)log($bytes , $base) , count($si_prefix) - 1);
		$free = sprintf('%1.2f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class];
		if ($bytes < 1000000000)
		return ActionFactory::show_title_dialog("Свободного места на диске меньше 1ГБ ($free). Запись не началась!!!");
		}
		$rec_file = DuneSystem::$properties['tmp_dir_path'] . '/background_rec_stop';
		$date_count = fopen($rec_file,"w");
		if (!$date_count)
		return ActionFactory::show_title_dialog("Не могу записать в tmp Что-то здесь не так!!!");
		fwrite($date_count, $rec_name);
		@fclose($date_count);
		shell_exec($cmd_rec);
		ControlFactory::add_label($defs, "Запись фильма:", "$title");
		ControlFactory::add_label($defs, "Свободно на диске:", "$free");
		ControlFactory::add_label($defs, "", "Вы можете в любой момент остановить запись!");
		$do_br_apply = UserInputHandlerRegistry::create_action($this, 'new_br_apply');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_br_apply', 'ОК', 250, $do_br_apply);
		return ActionFactory::show_dialog('Запись фильма', $defs, 1);
		}
		else if ($user_input->control_id === 'background_stoprec')///////////////////////////////////////
		{
		if (!isset($user_input->selected_media_url))
                return null;
		$rec_file = DuneSystem::$properties['tmp_dir_path'] . '/background_rec_stop';
		if (!file_exists($rec_file))
		return ActionFactory::show_title_dialog("Активная фоновая запись не найдена.");
		$background_rec_stop = trim(file_get_contents($rec_file));
		unlink($rec_file);
		$cmd_stoprec = '/tmp/' .$background_rec_stop.'_kartinarec.sh';
		shell_exec($cmd_stoprec);
		$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'dialog_rec_stop');
		return ActionFactory::invalidate_folders(array('vod_list'), $perform_new_action);}
		else if ($user_input->control_id === 'dialog_rec_stop')	{
		return ActionFactory::show_title_dialog("Запись остановлена.");}
		//////////////
        return null;
    }
    ///////////////////////////////////////////////////////////////////////

    public function get_all_folder_items(MediaURL $media_url, &$plugin_cookies)
    {
        $this->vod->folder_entered($media_url, $plugin_cookies);

        $movie = $this->vod->get_loaded_movie($media_url->movie_id, $plugin_cookies);
        if ($movie === null)
        {
            // TODO: dialog?
            return array();
        }

        $items = array();

        foreach ($movie->series_list as $series)
        {
            $items[] = array
            (
                PluginRegularFolderItem::media_url =>
                    MediaURL::encode(
                        array
                        (
                            'screen_id' => self::ID,
                            'movie_id' => $movie->id,
                            'series_id'  => $series->id,
                        )),
                PluginRegularFolderItem::caption => $series->name,
                PluginRegularFolderItem::view_item_params => array
                (
                    ViewItemParams::icon_path => 'gui_skin://small_icons/movie.aai',
                ),
            );
        }

        return $items;
    }

    ///////////////////////////////////////////////////////////////////////

    private function get_folder_views()
    {
        return array(
            array
            (
                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 1,
                    ViewParams::num_rows => 12,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_layout => HALIGN_LEFT,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::icon_dx => 10,
                    ViewItemParams::icon_dy => -5,
                    ViewItemParams::item_caption_dx => 60,
                    ViewItemParams::icon_path => 'gui_skin://small_icons/movie.aai'
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array (),
            ),
        );
    }

    public function get_archive(MediaURL $media_url)
    {
        return $this->vod->get_archive($media_url);
    }
	private static function translit_to_rus($str) {
	$str = preg_replace('/[-`~!#$%^&*()_=+\\\\|\\/\\[\\]{};:"\',<>?]+/','',$str);
    return str_replace(' ', '_', $str);
	}
	private static function get_rec_path(&$plugin_cookies) {
		$recdata = isset($plugin_cookies->recdata) ? 
		$plugin_cookies->recdata : '/D';
		$recdata_dir = isset($plugin_cookies->recdata_dir) ? 
		$plugin_cookies->recdata_dir : '/';
		if ($recdata !== '1')
		$rec_path = $recdata.$recdata_dir;
		elseif ($recdata == '1') {
			$recdata_smb_user = isset($plugin_cookies->recdata_smb_user) ? 
			$plugin_cookies->recdata_smb_user : 'guest';
			$recdata_smb_pass = isset($plugin_cookies->recdata_smb_pass) ? 
			$plugin_cookies->recdata_smb_pass : 'guest';
			$recdata_ip_path = isset($plugin_cookies->recdata_ip_path) ? 
			$plugin_cookies->recdata_ip_path : '';
			if ($recdata_ip_path == '')
				$rec_path = '/D/';
			else
				$rec_path = HD::get_mount_smb_path($recdata_ip_path, $recdata_smb_user, $recdata_smb_pass, 'recdata_path');
		}
		return $rec_path;
	}
}

///////////////////////////////////////////////////////////////////////////
?>
