<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/vod/vod_list_screen.php';
require_once 'ktv_check_bin.php';

class KtvVodListScreen extends VodListScreen
{
    public static function get_media_url_str($page_name, $arg = null)
    {
        $arr['screen_id'] = self::ID;
        $arr['page_name'] = $page_name;
        if ($page_name === 'search')
            $arr['pattern'] = $arg;
        else if ($page_name === 'genres')
            $arr['genre_id'] = $arg;
        return MediaURL::encode($arr);
    }

    ///////////////////////////////////////////////////////////////////////

    private $session;

    public function __construct($session, Vod $vod)
    {
        parent::__construct($vod);
		$this->vod = $vod;
        $this->session = $session;
    }

    ///////////////////////////////////////////////////////////////////////
	public function get_action_map(MediaURL $media_url, &$plugin_cookies)
    {
        $actions = array();

        if ($this->vod->is_movie_page_supported())
            $actions[GUI_EVENT_KEY_ENTER] = ActionFactory::open_folder();
        else
            $actions[GUI_EVENT_KEY_ENTER] = ActionFactory::vod_play();

        if ($this->vod->is_favorites_supported())
        {
            $add_favorite_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'add_favorite');
            $add_favorite_action['caption'] = 'Мои фильмы';

            $popup_menu_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'popup_menu');

            $actions[GUI_EVENT_KEY_D_BLUE] = $add_favorite_action;
            $actions[GUI_EVENT_KEY_POPUP_MENU] = $popup_menu_action;
        }
		/////////////////////////
		$actions[GUI_EVENT_KEY_INFO] =
            UserInputHandlerRegistry::create_action(
                $this, 'info');
		
		//////////////////
        return $actions;
    }
	///////////////////////////////////////////////////////////////////////
    public function handle_user_input(&$user_input, &$plugin_cookies)
    {
        foreach ($user_input as $key => $value)
            #hd_print("  $key => $value");

        if ($user_input->control_id == 'popup_menu')
        {
            if (!isset($user_input->selected_media_url))
                return null;

            $media_url = MediaURL::decode($user_input->selected_media_url);
            $movie_id = $media_url->movie_id;
			
			
            $is_favorite = $this->vod->is_favorite_movie_id($movie_id);
            $add_favorite_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'add_favorite');
            $caption = 'Добавить в мои фильмы';
            $menu_items[] = array(
                GuiMenuItemDef::caption => $caption,
                GuiMenuItemDef::action => $add_favorite_action);
			

            return ActionFactory::show_popup_menu($menu_items);
        }
        else if ($user_input->control_id == 'add_favorite')
        {
            if (!isset($user_input->selected_media_url))
                return null;

            $media_url = MediaURL::decode($user_input->selected_media_url);
            $movie_id = $media_url->movie_id;

            $is_favorite = $this->vod->is_favorite_movie_id($movie_id);
            if ($is_favorite)
            {
                return ActionFactory::show_title_dialog(
                    'Фильм уже есть в моих фильмах');
            }
            else
            {
                $this->vod->add_favorite_movie($movie_id, $plugin_cookies);

                return ActionFactory::show_title_dialog(
                    'Фильм добавлен в мои фильмы');
            }
        }
		//////////////////////
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
        return null;
    }
    ///////////////////////////////////////////////////////////////////////

    private function get_page_for_ndx($ndx, $page_size)
    {
        return intval($ndx / $page_size) + 1;
    }

    protected function get_short_movie_range(MediaURL $media_url, $from_ndx,
        &$plugin_cookies)
    {
        $nums = 24;
        $page = $this->get_page_for_ndx($from_ndx, $nums);

        if ($media_url->page_name === 'last')
            $doc = $this->session->api_vod_list_last($page, $nums);
        else if ($media_url->page_name === 'best')
            $doc = $this->session->api_vod_list_best($page, $nums);
        else if ($media_url->page_name === 'search')
        {
            $doc = $this->session->api_vod_list_search(
                $media_url->pattern, $page, $nums);
        }
        else if ($media_url->page_name === 'genres')
        {
            $doc = $this->session->api_vod_list_genres(
                $media_url->genre_id, $page, $nums);
        }
        else
            throw new Exception('Vod list: invalid page name.');

        if (!isset($doc->total))
            throw new Exception('Invalid data returned from server');

        $total = intval($doc->total);
        if ($total === 0)
            return new ShortMovieRange(0, 0);

        $from_ndx = (intval($doc->page) - 1) * $nums;

        $movies = array();
        foreach ($doc->rows as $row)
        {
            $icon_url = 'http://' . KTV::$SERVER . $row->poster;
            $movies[] = new ShortMovie(
                $row->id, $row->name, $icon_url);
        }

        return new ShortMovieRange($from_ndx, $total, $movies);
    }
	private static function cropStr($str, $length){
     $new_str = substr($str, 0, strpos($str, " ", $length) ?: $length);
     return (strlen($new_str) > $length) ? $new_str."..." : $new_str;
	}
}

///////////////////////////////////////////////////////////////////////////
?>
