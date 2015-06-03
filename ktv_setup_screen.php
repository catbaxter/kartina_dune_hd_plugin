<?php
///////////////////////////////////////////////////////////////////////////
require_once 'lib/abstract_preloaded_regular_screen.php';
require_once 'lib/abstract_controls_screen.php';

///////////////////////////////////////////////////////////////////////////

class KtvSetupScreen extends AbstractControlsScreen
{
    const ID = 'setup';
	const EPG_FONTSIZE_DEF_VALUE	= 'normal';
	private	$epg_font_size;
    ///////////////////////////////////////////////////////////////////////
	private $page_num = 1;
    private $session;
	
    ///////////////////////////////////////////////////////////////////////

    public function __construct($session)
    {
        parent::__construct(self::ID);

        $this->session = $session;
    }
	//////////////////////////////////////////////////////////////////////
	
	//////////////////////////////////////////////////////////////////////
    private function get_http_caching_caption($value)
    {
        if ($value % 1000 == 0)
            return sprintf('%d сек', intval($value / 1000));
         return sprintf('%.1f сек', $value / 1000.0);
    }

    private function do_get_edit_pcode_defs()
    {
        $defs = array();

        $this->add_text_field($defs,
            'current_pcode', 'Текущий код:',
            '', true, true, false, 1, 500, 0);

        $this->add_text_field($defs,
            'new_pcode', 'Новый код:',
            '', true, true, false, 1, 500, 0);

        $this->add_text_field($defs,
            'new_pcode_copy', 'Подтвердить:',
            '', true, true, false, 1, 500, 0);

        $this->add_vgap($defs, 50);

        $this->add_button($defs,
            'apply_pcode', null, 'Применить', 300);

        $this->add_vgap($defs, -3);

        $this->add_close_dialog_button($defs,
            'Отмена', 300);

        return $defs;
    }

    private function do_get_edit_pcode_action()
    {
        return ActionFactory::show_dialog(
            'Изменение кода закрытых каналов',
            $this->do_get_edit_pcode_defs(),
            true);
    }
	///////////////////////////record///////////////////////////////////////////
	public function do_get_ip_path_smb_defs(&$plugin_cookies)
    {
        $smb_user = isset($plugin_cookies->smb_user) ? 
		$plugin_cookies->smb_user : 'guest';
		$smb_pass = isset($plugin_cookies->smb_pass) ? 
		$plugin_cookies->smb_pass : 'guest';
		$ip_path = isset($plugin_cookies->ip_path) ? 
		$plugin_cookies->ip_path : '';
		$defs = array();
		
		$this->add_text_field($defs,
                    'ip_path',
                   'Путь к SMB папке(IP/имя папки/..)',
                    $ip_path, 0, 0, 0, 1, 750, 0, 0
            );
        $this->add_text_field($defs,
                    'smb_user',
                    'Имя пользователя SMB папки:',
                    $smb_user, 0, 0, 0, 1, 750, 0, 0
            );

		$this->add_text_field($defs,
                    'smb_pass',
                    'Пароль SMB папки:',
                    $smb_pass, 0, 1, 0, 1, 750, 0, 0
            );
        
        $this->add_close_dialog_and_apply_button($defs,
            'ip_path_smb_apply', 'ОК', 250);
        $this->add_close_dialog_button($defs,
            'Отмена', 250);
 
        return $defs;
    }
	public function do_get_recdata_path_smb_defs(&$plugin_cookies)
    {
        $recdata_smb_user = isset($plugin_cookies->recdata_smb_user) ? 
		$plugin_cookies->recdata_smb_user : 'guest';
		$recdata_smb_pass = isset($plugin_cookies->recdata_smb_pass) ? 
		$plugin_cookies->recdata_smb_pass : 'guest';
		$recdata_ip_path = isset($plugin_cookies->recdata_ip_path) ? 
		$plugin_cookies->recdata_ip_path : '';
		$defs = array();
		
		$this->add_text_field($defs,
                    'recdata_ip_path',
                   'Путь к SMB папке(IP/имя папки/..)',
                    $recdata_ip_path, 0, 0, 0, 1, 750, 0, 0
            );
        $this->add_text_field($defs,
                    'recdata_smb_user',
                    'Имя пользователя SMB папки:',
                    $recdata_smb_user, 0, 0, 0, 1, 750, 0, 0
            );

		$this->add_text_field($defs,
                    'recdata_smb_pass',
                    'Пароль SMB папки:',
                    $recdata_smb_pass, 0, 1, 0, 1, 750, 0, 0
            );
        
        $this->add_close_dialog_and_apply_button($defs,
            'recdata_path_smb_apply', 'ОК', 250);
        $this->add_close_dialog_button($defs,
            'Отмена', 250);
 
        return $defs;
    }
	///////////////////////////////////////////////////////////////////////////
    private function do_get_control_defs(&$plugin_cookies)
    {
        $defs = array();
		///////////////////////////////////////////////////////////////////
		$epg_font_size = isset($plugin_cookies->epg_font_size) ? $plugin_cookies->epg_font_size : self::EPG_FONTSIZE_DEF_VALUE;///igores
		$buf_time = isset($plugin_cookies->buf_time) ? $plugin_cookies->buf_time : 0;
		////////////////////////record////////////////////////////////////////////
		$recdata = isset($plugin_cookies->recdata) ? 
		$plugin_cookies->recdata : '/D';
		$recdata_dir = isset($plugin_cookies->recdata_dir) ? 
		$plugin_cookies->recdata_dir : '/';
		////////////////////////////
		if ($this->page_num == 1)
		{
		
		//////////////////////////
        $user_name = isset($plugin_cookies->user_name) ?
            $plugin_cookies->user_name : '';

        $logged_in = $this->session->is_logged_in();

        hd_print('Packet name: ' .
            ($logged_in ? $this->session->get_account()->packet_name : 'unset'));
        if ($user_name === '')
            $login_str = 'unset';
        else if (!$logged_in ||
            !isset($this->session->get_account()->packet_name))
        {
            $login_str = $user_name;
        }
        else
        {
            $login_str = $user_name . ' (' .
                $this->session->get_account()->packet_name . ')';
        }

        hd_print('Packet expire: ' .
            ($logged_in ? $this->session->get_account()->packet_expire : 'unset'));
        if (!$logged_in ||
            !isset($this->session->get_account()->packet_expire) ||
            $this->session->get_account()->packet_expire <= 0)
        {
            $expires_str = 'not available';
        }
        else
        {
            $tm = $this->session->get_account()->packet_expire;

            $expires_str =
                HD::format_date_time_date($tm) . ', ' .
                HD::format_date_time_time($tm);
        }

        $this->add_label($defs,
            'Абонемент:', $login_str);

        $this->add_label($defs,
            'Истекает:', $expires_str);

        $this->add_button($defs, 'edit_subscription', null,
            'Редактировать абонемент...', 700);

        $settings = $this->session->get_settings();

        $stream_server_caption = 'not available';
        $bitrate_caption = 'not available';
        $http_caching_caption = 'not available';
        $timeshift_caption = 'not available';

        if (isset($settings))
        {
            $stream_server = $settings->stream_server->value;
            foreach ($settings->stream_server->list as $pair)
            {
                if ($pair->ip === $stream_server)
                {
                    $stream_server_caption = $pair->descr;
                    break;
                }
            }
            ///////////////
			$bitrate = $settings->bitrate->value;
            foreach ($settings->bitrate->names as $b)
            {
                if ($b->val === $bitrate)
                {
                    $bitrate_caption = $b->title;
                    break;
                }
            }
			/////////////////
            #$bitrate= $settings->bitrate->value;
            #$bitrate_caption = $bitrate;
			
            $http_caching = $settings->http_caching->value;
			$http_caching_caption = $http_caching;

            $timeshift = $settings->timeshift->value;
            $timeshift_caption = $timeshift;
        }
		
        if ($logged_in)
        {
            $this->add_button($defs, 'edit_pcode', 'Код для закрытых каналов:',
                'Изменить...', 700);

            $stream_server_ops = array();
            foreach ($settings->stream_server->list as $pair)
                $stream_server_ops[strval($pair->ip)] = strval($pair->descr);
            $this->add_combobox($defs,
                'stream_server', 'Сервер вещания:',
                $stream_server, $stream_server_ops, 700, true);

            $bitrate_ops = array();
			#foreach ($settings->bitrate->list as $v)
			foreach ($settings->bitrate->names as $b)
                #$bitrate_ops[$v] = $v;
				$bitrate_ops[$b->val] = $b->title;
            $this->add_combobox($defs,
                'bitrate', 'Битрейт:',
                $bitrate, $bitrate_ops, 700, true);

            $http_caching_ops = array();
			foreach ($settings->http_caching->list as $v)
                $http_caching_ops[$v] = $this->get_http_caching_caption($v);
			///////////////////////////////////////////////////////////////
			$show_buf_time_ops = array();

				$show_buf_time_ops[0] = 'По умолчанию';
				$show_buf_time_ops[500] = '0.5 с';
				$show_buf_time_ops[1500] = '1,5 с';
				$show_buf_time_ops[3000] = '3 с';
				$show_buf_time_ops[5000] = '5 с';
				$show_buf_time_ops[8000] = '8 с';
				$show_buf_time_ops[15000] = '15 с';

				$this->add_combobox
				(
					$defs,
					'buf_time',
					'Время буферизации:',
					$buf_time, $show_buf_time_ops, 700, true
				);
			

            $timeshift_ops = array();
            foreach ($settings->timeshift->list as $v)
                $timeshift_ops[$v] = $v;
            $this->add_combobox($defs,
                'timeshift', 'Задержка по времени (часы):',
                $timeshift, $timeshift_ops, 700, true);
        }
        else
        {
            $this->add_label($defs,
                'Код для закрытых каналов:', 'not available');

            $this->add_label($defs,
                'Сервер вещания:', $stream_server_caption);
            $this->add_label($defs,
                'Битрейт:', $bitrate_caption);
            $this->add_label($defs,
                'Время буфферизации:', $http_caching_caption);
            $this->add_label($defs,
                'Задержка по времени (часы):', $timeshift_caption);
        }

        if (isset($plugin_cookies->show_in_main_screen))
            $show_in_main_screen = $plugin_cookies->show_in_main_screen;
        else
            $show_in_main_screen = 'auto';

        $show_ops = array();
        $show_ops['auto'] = 'Автоматически';
        $show_ops['yes'] = 'Да';
        $show_ops['no'] = 'Нет';
        $this->add_combobox($defs,
            'show_in_main_screen', 'Показывать в главном меню:',
            $show_in_main_screen, $show_ops, 0, true);
			
		$this->add_button
            (
                $defs,
                'page2',
                '',
                'Настройки далее ==>',
                700
            );
		}
	else if ($this->page_num == 2)
		{
        
		$this->add_button
            (
                $defs,
                'page1',
                'Настройки страница 2 из 2             ',
                '<== На первую страницу настроек',
                700
            );
		////////////////////////////////////////////////////////////////
			$epg_font_size_ops = array();
			$epg_font_size_ops ['normal'] = 'Обычный';
			$epg_font_size_ops ['small'] = 'Мелкий';
			$this->add_combobox($defs,
				'epg_font_size', 'Размер шрифта EPG:', 
				$epg_font_size, $epg_font_size_ops, 700, true);
		//////////////////////////record/////////////////////////////////////
			$recdata_ops = array();
			$recdata_ops['/D'] = 'Первый HDD/USB-диск';
			$recdata_ops[1] = 'SMB папка';
			foreach (glob('/tmp/mnt/storage/*') as $file) 
				if (is_dir($file)) $recdata_ops[$file] = ' -'.substr($file,17,strlen($file));
		/////////////////////////////////////////////////////////////////////
			$this->add_combobox($defs,
            'recdata', 'Сохранять записи в:',
            $recdata, $recdata_ops, 0, true);		
			if ($recdata !== '1') {
				$recdata_dir_ops = array();
				$recdata_dir_ops["$recdata"] = '/';
				foreach (glob("$recdata/*") as $file)
				if (is_dir($file)) {
				if (preg_match('|/tmp/mnt/|', $file))
					$file	= basename($file);
				else
					$file	= substr($file,3,strlen($file));
				$recdata_dir_ops['/'.$file.'/'] = $file;
				}
				$this->add_combobox($defs,
						'recdata_dir', 'Каталог:',
						$recdata_dir, $recdata_dir_ops, 0, true);
			}	
			elseif ($recdata == '1') {
				 $this->add_button($defs,
					'recdata_path_smb',
					'Путь, логин и пароль SMB:',
					'Изменить',
					500
				);	
			}
			////////////////////////////////////////////////////////////////
		}	
        return $defs;
    }
    public function get_control_defs(MediaURL $media_url, &$plugin_cookies)
    {
        try
        {
            if (!$this->session->is_login_incorrect())
                $this->session->ensure_logged_in($plugin_cookies);
        }
        catch (Exception $e)
        {
            // Nop.
        }

        return $this->do_get_control_defs($plugin_cookies);
    }

    public function handle_user_input(&$user_input, &$plugin_cookies)
    {
        hd_print('Setup: handle_user_input:');
        foreach ($user_input as $key => $value)
            hd_print("  $key => $value");

        $need_close_dialog = false;
        $need_reset_controls = false;
        $post_action = null;
        hd_silence_warnings();
        if ($user_input->action_type === 'confirm' || $user_input->action_type === 'apply')
        {
            $control_id = $user_input->control_id;
			$new_value = $user_input->{$control_id};
            #hd_print("Setup: changing $control_id value to $new_value");
			
            if ($control_id === 'edit_subscription')
            {
                return $this->session->do_get_edit_subscription_action(
                    $plugin_cookies, $this);
            }
            else if ($control_id === 'apply_subscription')
            {
                if ($user_input->user_name === '')
                {
                    return ActionFactory::show_error(false,
                        'Error',
                        array('Subscription should be non-empty.'));
                }

                $plugin_cookies->user_name = $user_input->user_name;
                $plugin_cookies->password = $user_input->password;

                $this->session->logout();

                try
                {
                    $this->session->try_login($plugin_cookies);
                }
                catch (DuneException $e)
                {
                    $post_action = $e->get_error_action();
                }

                $need_close_dialog = true;
                $need_reset_controls = true;
            }
            else if ($control_id === 'edit_pcode')
            {
                return $this->do_get_edit_pcode_action();
            }
            else if ($control_id === 'apply_pcode')
            {
                try
                {
                    $this->session->api_change_pcode(
                        $user_input->current_pcode,
                        $user_input->new_pcode,
                        $user_input->new_pcode_copy);
                }
                catch (DuneException $e)
                {
                    return $e->get_error_action();
                }

                $need_close_dialog = true;
                $need_reset_controls = true;
            }
        

            elseif ($control_id === 'show_in_main_screen')
            {
                $plugin_cookies->show_in_main_screen = $new_value;
                if ($new_value === 'auto')
                    $plugin_cookies->show_tv = 'lang(russian)';
                else
                    $plugin_cookies->show_tv = $new_value;
            }
            else if ($control_id === 'bitrate' ||
                $control_id == 'stream_server' ||
                $control_id == 'http_caching' ||
                $control_id == 'timeshift')
            {
                try
                {
                $this->session->api_set_setting($control_id, $new_value);
                }
                catch (DuneException $e)
                {
                    return $e->get_error_action();
                }
            }
			///////////////////////////////////////////////////
			else if ($control_id == 'epg_font_size')
				$plugin_cookies->epg_font_size = $new_value;
			//////////////////////////////////////////////////
			else if ($control_id == 'buf_time')
				$plugin_cookies->buf_time = $new_value;
			//////////////////////////////////////////////////
			else if ($control_id === 'page1')
            {
				$need_reset_controls = true;
                $this->page_num = 1;
                return ActionFactory::reset_controls(
                    $this->do_get_control_defs($plugin_cookies), null, 1);
            }
			else if ($control_id === 'page2')
            {
				$need_reset_controls = true;
                $this->page_num = 2;
                return ActionFactory::reset_controls(
                    $this->do_get_control_defs($plugin_cookies), null, 1);
            }
			///////////////////////////////record/////////////////////////////////////////
			else if ($control_id === 'recdata_dir')
			{
			$plugin_cookies->recdata_dir = $user_input->recdata_dir;
			$perform_new_action = UserInputHandlerRegistry::create_action(
            $this, 'reset_controls');
				return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			else if ($control_id === 'recdata')
			{
			$plugin_cookies->recdata = $user_input->recdata;
			$perform_new_action = UserInputHandlerRegistry::create_action(
            $this, 'reset_controls');
				return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			else if ($control_id === 'recdata_path_smb'){
				$defs = $this->do_get_recdata_path_smb_defs($plugin_cookies);
					
					return  ActionFactory::show_dialog
							(
								"Логин и пароль SMB папки",
								$defs,
								true
							);
				}
			else if ($control_id === 'recdata_path_smb_apply'){
				$plugin_cookies->recdata_smb_user = $user_input->recdata_smb_user;
				$plugin_cookies->recdata_smb_pass = $user_input->recdata_smb_pass;
				$plugin_cookies->recdata_ip_path = $user_input->recdata_ip_path;
				$perform_new_action = UserInputHandlerRegistry::create_action(
						$this, 'reset_controls');
			return ActionFactory::invalidate_folders(array('tv_group_list'), $perform_new_action);
			}
			else if ($control_id === 'ip_path_smb'){
				$defs = $this->do_get_ip_path_smb_defs($plugin_cookies);
					
					return  ActionFactory::show_dialog
							(
								"Логин и пароль SMB папки",
								$defs,
								true
							);
				}
			////////////////////////////////////////////////////////////////////////
			else if ($control_id === 'reset_controls')
				{
				return ActionFactory::reset_controls(
            $this->do_get_control_defs($plugin_cookies));
				}
            #else
                #return null;

            $need_reset_controls = true;
        }
		
        if ($need_reset_controls)
        {
            $defs = $this->do_get_control_defs($plugin_cookies);

            $reset_controls_action = ActionFactory::reset_controls(
                $defs, $post_action);

            if ($need_close_dialog)
            {
                return ActionFactory::close_dialog_and_run(
                    $reset_controls_action);
            }

            return $reset_controls_action;
        }

        #return null;
		return ActionFactory::reset_controls(
           $this->do_get_control_defs($plugin_cookies));
		   hd_restore_warnings();
    }
	
}

///////////////////////////////////////////////////////////////////////////
?>
