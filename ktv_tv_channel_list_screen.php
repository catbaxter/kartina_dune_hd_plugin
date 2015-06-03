<?php

///////////////////////////////////////////////////////////////////////////

require_once 'lib/tv/tv_channel_list_screen.php';
require_once 'ktv_check_bin.php';

class KtvTvChannelListScreen extends TvChannelListScreen
{
    public function get_all_folder_items(MediaURL $media_url, &$plugin_cookies)
    {
        return parent::get_all_folder_items($media_url, $plugin_cookies);
    }

   private function get_sel_item_update_action(&$user_input, &$plugin_cookies)
    {
        $parent_media_url = MediaURL::decode($user_input->parent_media_url);
        $sel_ndx = $user_input->sel_ndx;
        $group = $this->tv->get_group($parent_media_url->group_id);
        $channels = $group->get_channels($plugin_cookies);

        $items[] = $this->get_regular_folder_item($group,
            $channels->get_by_ndx($sel_ndx), $plugin_cookies);
        $range = HD::create_regular_folder_range($items,
            $sel_ndx, $channels->size());
                                                  
        return ActionFactory::update_regular_folder($range, false);
    }

    private function get_regular_folder_item($group, $c, &$plugin_cookies)
    {
        return array
        (
            PluginRegularFolderItem::media_url =>
                MediaURL::encode(
                    array(
                        'channel_id' => $c->get_id(),
                        'group_id' => $group->get_id())),
            PluginRegularFolderItem::caption => $c->get_title(),
            PluginRegularFolderItem::view_item_params => array
            (
                ViewItemParams::icon_path => $c->get_icon_url(),
                ViewItemParams::item_detailed_icon_path => $c->get_icon_url(),
            ),
            PluginRegularFolderItem::starred =>
                $this->tv->is_favorite_channel_id(
                    $c->get_id(), $plugin_cookies),
        );
    }

    public function handle_user_input(&$user_input, &$plugin_cookies)
    {
		$ip_path = isset($plugin_cookies->ip_path) ? 
			$plugin_cookies->ip_path : '';
		$smb_user = isset($plugin_cookies->smb_user) ? 
			$plugin_cookies->smb_user : 'guest';
		$smb_pass = isset($plugin_cookies->smb_pass) ? 
			$plugin_cookies->smb_pass : 'guest';
	 
        if ($user_input->control_id == 'info')
        {
            if (!isset($user_input->selected_media_url))
                return null;

            $media_url = MediaURL::decode($user_input->selected_media_url);
            $channel_id = $media_url->channel_id;

            $channels = $this->tv->get_channels();
            $c = $channels->get($channel_id);
            $id = $c->get_id();
            $title = $c->get_title();

	    
	    $url = $this->tv->get_tv_playback_url($id, null, null, &$plugin_cookies);
	//	hd_print("URL---------------->$url");

            return ActionFactory::show_title_dialog("(Channel ID: $id) $title");
        }
        else if ($user_input->control_id == 'popup_menu')
        {
            if (!isset($user_input->selected_media_url))
                return null;

            $media_url = MediaURL::decode($user_input->selected_media_url);
            $channel_id = $media_url->channel_id;
			$bgr_rs = DuneSystem::$properties['tmp_dir_path'] . '/background_rec_stop';
			if (file_exists($bgr_rs)) {
			$name = trim(file_get_contents($bgr_rs));
			$dd = "/tmp/".$name."_kartinarec.sh";
			if (!file_exists($dd))
				unlink($bgr_rs);
			}
			$is_favorite = $this->tv->is_favorite_channel_id($channel_id, $plugin_cookies);
            $add_favorite_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'add_favorite');
            $caption = 'Добавить в Избранное';
			$one_rec_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'one_rec');
            $one_rec_caption = 'Расписание записи каналов';
			$new_rec_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'new_rec');
            $new_rec_caption = 'Запись канала по таймеру';
			$background_rec_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'background_rec');
            $background_rec_caption = 'Фоновая Запись канала';
			$background_stoprec_action =
                UserInputHandlerRegistry::create_action(
                    $this, 'background_stoprec');
            $background_stoprec_caption = 'Остановить Запись';
			
            $menu_items[] = array(
                GuiMenuItemDef::caption => $caption,
                GuiMenuItemDef::action => $add_favorite_action);
			$menu_items [] =  array( 
				GuiMenuItemDef::is_separator => true,);	
			$menu_items[] = array(
                GuiMenuItemDef::caption => $background_rec_caption,
                GuiMenuItemDef::action => $background_rec_action);
			if (file_exists($bgr_rs)) {
			$menu_items[] = array(
                GuiMenuItemDef::caption => $background_stoprec_caption,
                GuiMenuItemDef::action => $background_stoprec_action);}
			$menu_items[] = array(
                GuiMenuItemDef::caption => $new_rec_caption,
                GuiMenuItemDef::action => $new_rec_action);
			$menu_items[] = array(
                GuiMenuItemDef::caption => $one_rec_caption,
                GuiMenuItemDef::action => $one_rec_action);
			$menu_items [] =  array( 
				GuiMenuItemDef::is_separator => true,);	

			return ActionFactory::show_popup_menu($menu_items);
        }

        else if ($user_input->control_id == 'add_favorite')
        {
            if (!isset($user_input->selected_media_url))
                return null;

            $media_url = MediaURL::decode($user_input->selected_media_url);
            $channel_id = $media_url->channel_id;

            $is_favorite = $this->tv->is_favorite_channel_id($channel_id, $plugin_cookies);
            if ($is_favorite)
            {
                return ActionFactory::show_title_dialog(
                    'Канал уже находится в Избранном',
                    $this->get_sel_item_update_action(
                        $user_input, $plugin_cookies));
            }
            else
            {
                $this->tv->change_tv_favorites(PLUGIN_FAVORITES_OP_ADD,
                    $channel_id, $plugin_cookies);

                return ActionFactory::show_title_dialog(
                    'Канал добавлен в Избранное',
                    $this->get_sel_item_update_action(
                        $user_input, $plugin_cookies));
            }
        }
		else if ($user_input->control_id == 'one_rec')
			{
				if (!isset($user_input->selected_media_url))
                return null;

            $media_url = MediaURL::decode($user_input->selected_media_url);
			
			$defs = $this->do_get_one_rec_defs($plugin_cookies);
					return  ActionFactory::show_dialog
							(
								"Расписание записи каналов",
								$defs,
								true
							);
			}

		else if ($user_input->control_id === 'rec_del_menu')
		{
			if (!isset($user_input->selected_media_url))
                return null;
            $media_url = MediaURL::decode($user_input->selected_media_url);
			$defs = $this->do_get_del_rec_defs($plugin_cookies);
			return  ActionFactory::show_dialog
			("Список записи очищен!!!",
			$defs,
			true
			);
		}
		else if ($user_input->control_id === 'rec_cool')
		{
		if (isset($user_input->rec_hdd))
			$rec_hdd = $user_input->rec_hdd;
		return ActionFactory::launch_media_url(
                $rec_hdd);
		}
		else if ($user_input->control_id === 'rec_del')
		{
                $control_id = $user_input->control_id;
				$new_value = $user_input->{$control_id};
				$cron_file = '/tmp/cron/crontabs/root';
				$doc = file_get_contents($cron_file);
				$texts = explode('###', $doc);
				$one_del = "###" . strstr($texts[$new_value], 'kartinarec.sh', true) .'kartinarec.sh';
				$data = str_replace($one_del, '', $doc);
				$cron_edit = fopen($cron_file,"w");
			if (!$cron_file)
			hd_print("НЕ МОГУ ЗАПИСАТЬ НА USB/HDD");
			fwrite($cron_edit, $data);
			@fclose($cron_edit);
			chmod($cron_file, 0575);
			shell_exec('crontab -e');
			$perform_new_action = UserInputHandlerRegistry::create_action(
                    $this, 'one_rec');
		return ActionFactory::invalidate_folders(array('one_rec'), $perform_new_action);
		}
		else if ($user_input->control_id === 'background_rec')
		{
		if (!isset($user_input->selected_media_url))
        return null;
		$media_url = MediaURL::decode($user_input->selected_media_url);
        $channel_id = $media_url->channel_id;
        $channels = $this->tv->get_channels();
        $c = $channels->get($channel_id);
        $title = $c->get_title();
		$tr_title = self::translit($title);
		$rec_path = self::get_rec_path($plugin_cookies);
		$streaming_url_1 = $this->tv->get_tv_for_rec($channel_id, &$plugin_cookies);
		#hd_print("URL_REC_VOR----->$streaming_url_1");
		$doc = file_get_contents('/config/settings.properties');
		if (preg_match('/time_zone =(.*)\s/', $doc, $matches)) {
		$tmp = explode(':', $matches[1]);
		$rec_shift =($tmp[0] * 3600 ) + ($tmp[1] * 60 );}
        $unix_time = time() - $rec_shift;
		$date = date("HidmY" , $unix_time);
		$rec_name = $tr_title .'_'. $date;
		if (preg_match('/"url":"http\/ts:\/\/([^"]*) :http-caching/i', $streaming_url_1, $ttt))
		{
		$streaming_url = 'http://' . $ttt[1];	
		#hd_print("URL_REC----->$streaming_url");
		}
		$ptl = "http";
		$rec_script = '/tmp/arch_kart/rec';
		CHECK::check_for_arch();
		$cmd_rec = "$rec_script --$ptl \"$streaming_url\" \"$rec_name\" \"$rec_path\"";
		#hd_print("cmd_rec----->$cmd_rec");
		$free = "$rec_path";
		if (!file_exists($rec_path))
		return ActionFactory::show_title_dialog("Накопитель для записи не найден!!! Подключите к плееру накопитель.");
		if (preg_match('/\/D\//',$rec_path)){
		$bytes = disk_free_space ('/D/');
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
		ControlFactory::add_label($defs, "Запись канала:", "$title");
		ControlFactory::add_label($defs, "Свободно на диске:", "$free");
		ControlFactory::add_label($defs, "", "Не забудьте Выключить запись!!!");
		$do_br_apply = UserInputHandlerRegistry::create_action($this, 'new_br_apply');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_br_apply', 'ОК', 250, $do_br_apply);
		return ActionFactory::show_dialog('Фоновая запись канала', $defs, 1);
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
		return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);}
		else if ($user_input->control_id === 'dialog_rec_stop')	{
		return ActionFactory::show_title_dialog("Запись остановлена.");}
		else if ($user_input->control_id === 'new_rec')
		{
			if (!isset($user_input->selected_media_url))
                return null;

			$start_tvg_times = 0;
			$stop_tvg_times = 0;
			$tvg_rec_day = 0;
			$inf = '';
			if (isset($user_input->start_tvg_times))
			$start_tvg_times = $user_input->start_tvg_times;
			if (isset($user_input->stop_tvg_times))
			$stop_tvg_times = $user_input->stop_tvg_times;
			if (isset($user_input->tvg_rec_day))
			$tvg_rec_day = $user_input->tvg_rec_day;
			if (isset($user_input->inf))
			$inf = $user_input->inf;
            $media_url = MediaURL::decode($user_input->selected_media_url);
			$caption_g = $media_url->caption_g;
			$defs = $this->do_get_new_rec_defs($media_url, $start_tvg_times, $stop_tvg_times, $tvg_rec_day, $inf, $plugin_cookies);
			return  ActionFactory::show_dialog
			("Задать время записи:",
			$defs,
			true
			);
		}
		else if ($user_input->control_id === 'new_rec_conf')
		{
			$rec_start_t = $user_input->rec_start_t;
			$rec_start_d = $user_input->rec_start_d;
			$rec_stop_t = $user_input->rec_stop_t;
			$rec_stop_d = $user_input->rec_stop_d;
			$inf = $user_input->inf;
			$defs = $this->do_get_new_rec_conf_defs($rec_start_t, $rec_start_d, $rec_stop_t, $rec_stop_d, $inf, $plugin_cookies);
			return  ActionFactory::show_dialog
			("Добавить задание записи",
			$defs,
			true
			);
		}
		else if ($user_input->control_id === 'new_rec_apply')
		{
			if (!isset($user_input->selected_media_url))
                return null;
			$doc = file_get_contents('/config/settings.properties');
			if (preg_match('/time_zone =(.*)\s/', $doc, $matches)) {
			$tmp = explode(':', $matches[1]);
			$rec_shift =($tmp[0] * 3600 ) + ($tmp[1] * 60 );}
			#hd_print ("TIME---> $rec_shift");
			$rec_shift = $rec_shift / 3600;
			#hd_print ("TIME_2---> $rec_shift");
			$seconds = '00';
			$year = date("Y");
			$media_url = MediaURL::decode($user_input->selected_media_url);
			$channel_id = $media_url->channel_id;
			$channels = $this->tv->get_channels();
			$c = $channels->get($channel_id);
            $title = $c->get_title();
			$streaming_url_1 = $this->tv->get_tv_for_rec($channel_id, &$plugin_cookies);
			#hd_print("URL_REC----->$streaming_url");
			$selected_media_url = $media_url->selected_media_url;
			$rec_start_t = $user_input->rec_start_t;
			$rec_start_d = $user_input->rec_start_d;
			$rec_stop_t = $user_input->rec_stop_t;
			$rec_stop_d = $user_input->rec_stop_d;
			$day_e = substr($rec_stop_d, 0, 2);
			$mns_e = substr($rec_stop_d, -2);
			$day_s = substr($rec_start_d, 0, 2);
			$mns_s = substr($rec_start_d, -2);
			$year =  date("y");
			$inf = $user_input->inf;
			if (($rec_start_t >= $rec_stop_t) && ((strtotime ("$day_s.$mns_s.$year")) >= strtotime (("$day_e.$mns_e.$year"))))
			return ActionFactory::show_title_dialog("Время окончания записи указанно не верно.");
			$cron_file = '/tmp/cron/crontabs/root';
			$hrs_s = substr($rec_start_t, 0, 2);
			$min_s = substr($rec_start_t, -2);
			$time_s = $hrs_s .":".$min_s;
			if (!preg_match('/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/', $time_s))
			return ActionFactory::show_title_dialog("Время начала записи указанно не верно.");	

			$data_s = $day_s .":".$mns_s;
			if (!preg_match('/^([0-2][0-9]|[3][0-1]):([0-1][0-9])$/', $data_s))
			return ActionFactory::show_title_dialog("Дата начала записи указанно не верно.");			
			$timestamp = mktime($hrs_s + $rec_shift, $min_s , $seconds, $mns_s, $day_s, $year);
		//	hd_print
			$unix_time = time();
			if ($unix_time > $timestamp)
			return ActionFactory::show_title_dialog("Время начала записи указанно не верно.");		
			$hrs_s1 = strftime('%H',$timestamp);
			$min_s1 = strftime('%M',$timestamp);
			$day_s1 = strftime('%d',$timestamp);
			$mns_s1 = strftime('%m',$timestamp);
			$hrs_e = substr($rec_stop_t, 0, 2);
			$min_e = substr($rec_stop_t, -2);
			$time_e = $hrs_e .":".$min_e;
			if (!preg_match('/^([0-1][0-9]|[2][0-3]):([0-5][0-9])$/', $time_e))
			return ActionFactory::show_title_dialog("Время начала записи указанно не верно.");			

			$data_e = $day_e .":".$mns_e;
			if (!preg_match('/^([0-2][0-9]|[3][0-1]):([0-1][0-9])$/', $data_e))
			return ActionFactory::show_title_dialog("Дата окончания записи указанно не верно.");			
			$timestamp = mktime($hrs_e + $rec_shift, $min_e, $seconds, $mns_e, $day_e, $year);
			$hrs_e1 = strftime('%H',$timestamp);
			$min_e1 = strftime('%M',$timestamp);
			$day_e1 = strftime('%d',$timestamp);
			$mns_e1 = strftime('%m',$timestamp);
			$rec_path = self::get_rec_path($plugin_cookies);
			$tr_title = self::translit($title);
			$date_name = $hrs_s . $min_s . $day_s . $mns_s ."-". $hrs_e . $min_e . $day_e . $mns_e;
			$rec_name = $tr_title .'_'. $date_name;
			if (preg_match('/"url":"http\/ts:\/\/([^"]*) :http-caching/i', $streaming_url_1, $ttt))
			{
			$streaming_url = 'http://' . $ttt[1];	
			#hd_print("URL_REC----->$streaming_url");
			}
			$ptl = "http";
			$rec_script = '/tmp/arch_kart/rec';
			CHECK::check_for_arch();
		        $cmd_rec = "$rec_script --$ptl \"$streaming_url\" \"$rec_name\" \"$rec_path\"";
			
			if (!file_exists($rec_path))
			return ActionFactory::show_title_dialog("Накопитель для записи не найден!!! Подключите к плееру накопитель.");
			if (preg_match('/\/D\//',$rec_path)){
			$bytes = disk_free_space ('/D/');
			$si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
			$base = 1024;
			$class = min((int)log($bytes , $base) , count($si_prefix) - 1);
			$free = sprintf('%1.2f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class];
			if ($bytes < 1000000000)
			return ActionFactory::show_title_dialog("Свободного места на диске меньше 1ГБ ($free). Запись не началась!!!");
			}
			$background_rec_stop = DuneSystem::$properties['tmp_dir_path'] . '/background_rec_stop';
			$save_cron = "\n###$title $inf [$hrs_s:$min_s] [$day_s-$mns_s] по [$hrs_e:$min_e] [$day_e-$mns_e]* \n$min_s1 $hrs_s1 $day_s1 $mns_s1 * $cmd_rec\n$min_s1 $hrs_s1 $day_s1 $mns_s1 * echo \"$rec_name\" > $background_rec_stop\n$min_e1 $hrs_e1 $day_e1 $mns_e1 * /tmp/". $rec_name ."_kartinarec.sh";
			$cron_data = fopen($cron_file,"a");
			if (!$cron_data)
			hd_print("НЕ МОГУ ЗАПИСАТЬ cron");
			fwrite($cron_data, $save_cron);
			@fclose($cron_data);
			chmod($cron_file, 0575);
			shell_exec('crontab -e');
			return ActionFactory::show_title_dialog("Добавлено $title Старт:$hrs_s:$min_s $day_s-$mns_s Стоп:$hrs_e:$min_e $day_e-$mns_e");
			//break;
		}
        return null;
    }
	public function do_get_one_rec_defs(&$plugin_cookies)
    {
		$doc = file_get_contents('/tmp/run/storage_list.xml');
		if (is_null($doc))
		throw new Exception('Can not fetch storage_list');
		$xml = simplexml_load_string($doc);
		$uuid = $xml->storages->storage[0]->uuid;
		if ($xml === false)
			{
				$tmp = file('/tmp/run/storages.txt');
				$uuid = $tmp[2];
			}
		
		$defs = array();
		$cron_file = '/tmp/cron/crontabs/root';
		$doc = file_get_contents($cron_file);
		$texts = explode('###', $doc);
		unset($texts[0]);
		$texts = array_values($texts);
		$ndx_rec = 1;
		foreach($texts as $text){
		$tmp = explode('*', $text);
		$time =$tmp[0];
		$pattern = '|\/tmp/(.*?)_kartinarec.sh|';
		preg_match( $pattern, $text , $matches);
		$file_rec = $matches[1];
		if (!file_exists("/D/IPTV_recordings/$file_rec.ts")){
		ControlFactory::add_label($defs, "$ndx_rec", $time);}
		else{
		$rec_hdd = "storage_uuid://$uuid/IPTV_recordings/$file_rec.ts";
		$add_params ['rec_hdd'] = $rec_hdd;
		ControlFactory::add_button ($defs, $this, $add_params,'rec_cool', $ndx_rec, $time, 500);}
		++$ndx_rec;
		}
		if ($ndx_rec == 1){
		ControlFactory::add_label($defs, "", 'Запись каналов не задана.');
		ActionFactory::show_dialog('Расписание записи каналов', $defs, 1);
		ControlFactory::add_close_dialog_button($defs,
            'Ок', 350);
		return $defs;}
		$rec_del = '0';
		$rec_ops[0] = 'Выбор';
		foreach($texts as $text)
			{
			$tmp = explode('*', $text);
			$rec_ops[] = $tmp[0];
			}
		ControlFactory::add_label($defs, "", 'Удалить задание:');
        ControlFactory::add_combobox($defs, $this, null,
            'rec_del', '',
            $rec_del, $rec_ops, 0, $need_confirm = false, $need_apply = false
        );
		$do_rec_del = UserInputHandlerRegistry::create_action($this, 'rec_del');
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_rec_del', 'Удалить', 350, $do_rec_del);	
		$rec_del_menu = UserInputHandlerRegistry::create_action($this, 'rec_del_menu');
		ControlFactory::add_custom_close_dialog_and_apply_buffon($defs, '_del', 'Удалить все', 350,  $rec_del_menu);
        ControlFactory::add_close_dialog_button($defs,
            'Отмена', 350);
 
        return $defs;
    }
	public function do_get_del_rec_defs(&$plugin_cookies)
    {
	$defs = array();
	$cron_file = '/tmp/cron/crontabs/root';
	$doc = file_get_contents($cron_file);
	$texts = explode('###', $doc);
	foreach ($texts as $text){
	$one_del = "###" . strstr($text, 'kartinarec.sh', true) .'kartinarec.sh';
	$doc = str_replace($one_del, '', $doc);
	}
	
	$date_cron = fopen($cron_file,"w");
	if (!$date_cron)
		{
		ActionFactory::show_title_dialog("Не могу записать. Что-то здесь не так!!!");
		}
	fwrite($date_cron, $doc);
	@fclose($date_cron);
	chmod($cron_file, 0575);
	shell_exec('crontab -e');

		ControlFactory::add_close_dialog_button($defs,
            'Ок', 350);
	return $defs;
    }
	public function do_get_new_rec_conf_defs($rec_start_t, $rec_start_d, $rec_stop_t, $rec_stop_d, $inf, &$plugin_cookies)
    {
        
		$defs = array();
        ControlFactory::add_label($defs, "", "Вы уверены что хотите добавить задание записи?");
		ControlFactory::add_label($defs, "$rec_start_t - $rec_stop_t", "$inf");
		$add_params ['rec_start_t'] = $rec_start_t;
		$add_params ['rec_start_d'] = $rec_start_d;
		$add_params ['rec_stop_t'] = $rec_stop_t;
		$add_params ['rec_stop_d'] = $rec_stop_d;
		$add_params ['inf'] = $inf;
		$new_rec_apply = UserInputHandlerRegistry::create_action($this, 'new_rec_apply', $add_params);
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_add_new_rec_apply', 'Да', 250, $new_rec_apply);
        ControlFactory::add_close_dialog_button($defs,
            'Нет', 250);
        return $defs;
    }
	public function do_get_new_rec_defs($media_url, $start_tvg_times, $stop_tvg_times, $tvg_rec_day, $inf, &$plugin_cookies)
    {	
		$start_tvg_times = str_replace(":", "", $start_tvg_times);
		$stop_tvg_times = str_replace(":", "", $stop_tvg_times);
		$doc = file_get_contents('/config/settings.properties');
		if (preg_match('/time_zone =(.*)\s/', $doc, $matches)) {
		$tmp = explode(':', $matches[1]);
		$rec_shift =($tmp[0] * 3600 ) + ($tmp[1] * 60 );
		}
		$defs = array();
		$add_params['inf'] = $inf;
		if ($start_tvg_times == '0'){
        $unix_time = time() - $rec_shift;
		$date = date("m-d H:i:s" , $unix_time);
		$rec_start_do = date("dm", $unix_time);
		$rec_start_to = date("Hi", $unix_time);
		$rec_stop_do = date("dm", $unix_time);
		$rec_stop_to = date("Hi", $unix_time);}
		else{
		$unix_time = time() - $rec_shift;
		$year =  date("y");
		$rec_start_do = date("dm", $unix_time);
		$rec_start_to = $start_tvg_times;
		$rec_stop_do = date("dm", $unix_time);
		$rec_stop_to = $stop_tvg_times;
		if (!$tvg_rec_day == '0'){
		$rec_day =  explode('-', $tvg_rec_day);
		$rec_start_do = $rec_day[2] . $rec_day[1];
		$rec_stop_do = $rec_start_do;
		}
		$c_time= intval(date("Hi", $unix_time));
		$c_day_start = intval($start_tvg_times);
		$c_day_stop = intval($stop_tvg_times);
		
		if(($c_day_start <= 500) && ($c_time > 500)){
		$unix_t = time() + $rec_shift + 86400;
		$rec_start_do = date("dm", $unix_t);
		}
		if(($c_day_stop <= 500)&& ($c_time > 500)){
		$unix_t = time() + $rec_shift + 86400;
		$rec_stop_do = date("dm", $unix_t);
		}
		if ((!$tvg_rec_day == '0') && ($c_day_start <= 500)){
		$date = strtotime($tvg_rec_day);
		$date = strtotime("+1 day", $date);
		$rec_start_do = date('dm', $date);
		}
		if ((!$tvg_rec_day == '0') && ($c_day_stop <= 500)){
		$date = strtotime($tvg_rec_day);
		$date = strtotime("+1 day", $date);
		$rec_stop_do = date('dm', $date);
		}
		$day = substr($rec_start_do, 0, 2);
		$mns = substr($rec_start_do, -2);
		$hrs = substr($rec_start_to, 0, 2);
		$min = substr($rec_start_to, -2);
		$timestamp = mktime($hrs, $min, 0, $mns, $day, $year);
		if($timestamp < $unix_time)
		$rec_start_to = date("Hi", $unix_time + 60);
		}
		ControlFactory::add_text_field($defs,0,0,
            'rec_start_t', 'Время начала записи [ЧЧММ]:',
            $rec_start_to, 1, 0, 0, 1, 250, 0, false);
		ControlFactory::add_text_field($defs,0,0,
            'rec_start_d', 'Дата начала записи [ДДMM]:',
            $rec_start_do, 1, 0, 0, 1, 250, 0, false);	
		ControlFactory::add_text_field($defs,0,0,
            'rec_stop_t', 'Время окончания записи [ЧЧММ]:',
            $rec_stop_to, 1, 0, 0, 1, 250, 0, false);
		ControlFactory::add_text_field($defs,0,0,
            'rec_stop_d', 'Дата окончания записи [ДДMM]:',
            $rec_stop_do, 1, 0, 0, 1, 250, 0, false);
			
		$do_rec_apply = UserInputHandlerRegistry::create_action($this, 'new_rec_apply', $add_params);
        ControlFactory::add_custom_close_dialog_and_apply_buffon($defs,
		'_do_rec_apply', 'ОК', 250, $do_rec_apply);
        ControlFactory::add_close_dialog_button($defs,
            'Отмена', 250);
        return $defs;
    }
	private static function translit($str) {
    $str = preg_replace('/[-`~!#$%^&*()_=+\\\\|\\/\\[\\]{};:"\',<>?]+/','',$str);
    $rus = array(' ','А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
    $lat = array('_','A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya');
    return str_replace($rus, $lat, $str);
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
