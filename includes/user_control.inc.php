<?php

/**
* AT CMS version 2.0.0
* Система управления содержимым веб-сайта
* © 2007–2008 группа разработчиков AT CMS
* ----------------------------------------------------------------
* Главный разработчик: Илья “WatchRooster” Аверков
* Интерпретатор BBML от Алексея “shade” Золотова
* Многие идеи заимствованы из проекта ArigatoCMS,
* разработанного Алексеем “Arigato” Акатовым в 2006 г.
* Выражаю также благодарность следующим лицам:
* MayKoPSKiy, Erik, Вася Триллер
* ----------------------------------------------------------------
* http://shamangrad.net/project.php?act=view&prj=atcms2
* ----------------------------------------------------------------
* Лицензировано на условиях GNU GPL v 2.0,
* за более подробной информацией смотрите COPYING.
* ----------------------------------------------------------------
*
*/

defined('ATCMS') || die('Error');

/**
* @note NOTE: Немного тупое решение сделать методы этого класса статическими.
* @todo TODO: прикрутить конструктор, тем самым сэкономить на $atc и $manager при вызовах методов
*/

class user_control
{
	/**
	* Редактировать учётную запись пользователя (построить форму)
	* Это и в профиле, и при администрировании (правка выбранного пользователя)
	* @param object ссылка на ядро
	* @param int ID пользователя (можно напрямую из GET, POST)
	* @param string URL, на который требуется отправлять форму
	* @param string значение для скрытого поля act (навигация)
	* @param string сообщение об ошибке
	* @param bool менеджер или нет
	*/
	
	public static function profile_edit(& $atc, $id_user, $target, $act, $error = '', $manager=false)
	{
		$profile_edit = $atc->template('profile_edit');
		
		$sql = 'SELECT * FROM ' . USERS_TABLE . ' WHERE id_user=' . (int) $id_user;
		$u = $atc->db->db_query($sql);
		if($atc->db->db_numrows($u) == 0)
		{
			$atc->db->db_freeresult($u);
			$atc->general_message(ATCMESSAGE_USER_DOESNT_EXIST);
		}
		$u_res = $atc->db->db_fetchassoc($u);
		$atc->db->db_freeresult($u);
		
		if($manager)
		{
			$_POST['level'] = isset($_POST['level']) ? $_POST['level'] : $u_res['level'];
			$levels = '<option value="0"' . ($_POST['level']==0 ? ' selected' : '') . '> ' . $atc->lang['user'] . '</option><option value="1"' . ($_POST['level']==1 ? ' selected' : '') . '> ' . $atc->lang['admin'] . '</option>';
		}
		
		$data = array
		(
			/// Yet another boring array definition :-(
			'act'=>$act,
			'id_user'=>$id_user,
			'name'=>htmlspecialchars(isset($_POST['name']) ? $_POST['name'] : $u_res['name']),
			'email'=>htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : $u_res['email']),
			'icq'=>htmlspecialchars(isset($_POST['icq']) ? $_POST['icq'] : ($u_res['icq'] == 0 ? '' : $u_res['icq'])),
			'site'=>htmlspecialchars(isset($_POST['site']) ? $_POST['site'] : $u_res['site']),
			'jabber'=>htmlspecialchars(isset($_POST['jabber']) ? $_POST['jabber'] : $u_res['jabber']),
			'phone'=>htmlspecialchars(isset($_POST['phone']) ? $_POST['phone'] : $u_res['phone']),
			'hide_email'=>(isset($_POST['hide_email']) ? $_POST['hide_email'] : $u_res['hide_email']),
			'language'=>$atc->get_languages_list((isset($_POST['language']) ? $_POST['language'] : $atc->language)),
			'timezone'=>atcms_timezone_browser((isset($_POST['timezone']) ? $_POST['timezone'] : $atc->cfgvar('timezone'))),
			'layout'=>atcms_layout_browser((isset($_POST['layout']) ? $_POST['layout'] : $atc->cfgvar('layout'))),
			'location'=>htmlspecialchars(isset($_POST['location']) ? $_POST['location'] : $u_res['location']),
			'occupation'=>htmlspecialchars(isset($_POST['occupation']) ? $_POST['occupation'] : $u_res['occupation']),
			'interests'=>htmlspecialchars(isset($_POST['interests']) ? $_POST['interests'] : $u_res['interests']),
			'signature'=>(isset($_POST['signature']) ? htmlspecialchars($_POST['signature']) : $atc->unprocess_text($u_res['signature'], ATCFORMAT_BBCODE))
		);
		
		if($manager)
		{
			$data['level'] = $levels;
		}
		
		$scheme = $manager ? 'user_edit' : 'profile';
		
		$form = $atc->forms->create($scheme, false, $atc->lang, $data, $target, 'POST', $error, 'multipart/form-data');
		$profile_edit->add_tag('FORM', $form->ret());
		$atc->process_contents($profile_edit->ret(), $atc->lang['profile_edit']);
	}
	
	/**
	* Сохранить учётную запись пользователя
	* Обработчик формы, генерируемой функцией atcform::profile_edit
	* @param object ссылка на ядро
	* @param int ID пользователя (можно напрямую из GET, POST)
	* @param string URL, на который требуется отправлять форму
	* @param string значение для скрытого поля act (навигация)
	* @param string куда переходить при успешном выполнении
	*/
	
	public static function profile_edit_save(& $atc, $id_user, $target, $act, $return_to, $manager=false)
	{
		if(!$atc->forms->validate())
		{
			return self::profile_edit($atc, $id_user, $target, $act, $atc->lang['oops']);
		}
		
		$password = '';
		$set = array();
		
		if(isset($_FILES['avatar']) && $_FILES['avatar']['size'] > 0)
		{
			if(($a = $atc->install_avatar($_FILES['avatar']['tmp_name'], $id_user)) !== true)
			{
				return self::profile_edit($atc, $id_user, $target, $act, $a);
			}
		}
		
		//Удалить аватар
		if(isset($_POST['delete_avatar']) && $_POST['delete_avatar'] == '1')
		{
			$atc->delete_avatar($id_user);
		}
		
		//Флаг скрытия адреса электронной почты
		if(isset($_POST['hide_email']) && $_POST['hide_email'] == '1')
		{
			$set[] = 'hide_email=1';
		}
		else
		{
			$set[] = 'hide_email=0';
		}
		
		$sql = 'SELECT password, email FROM ' . USERS_TABLE . ' WHERE id_user=' . $id_user;
		$c = $atc->db->db_query($sql);
		$c_res = $atc->db->db_fetchassoc($c);
		$atc->db->db_freeresult($c);
		
		$ok = $c_res['password']==md5(isset($_POST['oldpass']) ? $_POST['oldpass'] : '');
		
		//Собственно адрес электронной почты
		if(isset($_POST['email']) && $_POST['email'] != $c_res['email'])
		{
			if(preg_match(PCREGEXP_EMAIL, $_POST['email']))
			{
				$_POST['email'] = $atc->db->db_escape($_POST['email']);
				
				if(!$manager && $ok)
				{
					if($atc->db->db_countrows(USERS_TABLE, '*', 'email=\'' . $_POST['email'] . '\''))
					{
						return self::profile_edit($atc, $id_user, $target, $act, $atc->lang['email_already_exists']);
					}
					else
					{
						$set[] = 'email=\'' . $_POST['email'] . '\'';
					}
				}
				elseif($manager)
				{
					$set[] = 'email=\'' . $_POST['email'] . '\'';
				}
				else
				{
					return self::profile_edit($atc, $id_user, $target, $act, $atc->lang['email_unchanged_because_of_wrong_password']);
				}
			}
			else
			{
				return self::profile_edit($atc, $id_user, $target, $act, $atc->lang['email_is_incorrect_and_was_ignored']);
			}
		}
		
		//Ацка
		if(isset($_POST['icq']))
		{
			if(preg_match(PCREGEXP_ICQ, $_POST['icq']))
			{
				$set[] = 'icq=' . $_POST['icq'];
			}
			else
			{
				if($_POST['icq'] == '')
				{
					$set[] = 'icq=\'\'';
				}
				else
				{
					return self::profile_edit($atc, $id_user, $target, $act, $atc->lang['icq_is_incorrect_and_was_ignored']);
				}
			}
		}
		
		//Номер телефона
		if(isset($_POST['phone']))
		{
			if(preg_match(PCREGEXP_PHONE_NUMBER, $_POST['phone']))
			{
				$set[] = 'phone=\'' . $_POST['phone'] . '\'';
			}
			else
			{
				if($_POST['phone'] == '')
				{
					$set[] = 'phone=\'\'';
				}
				else
				{
					return self::profile_edit($atc, $id_user, $target, $act, $atc->lang['phone_is_incorrect_and_was_ignored']);
				}
			}
		}
		
		
		//Jabber
		if(isset($_POST['jabber']))
		{
			if(preg_match(PCREGEXP_JABBER, $_POST['jabber']))
			{
				$set[] = 'jabber=\'' . $atc->db->db_escape($_POST['jabber']) . '\'';
			}
			else
			{
				if($_POST['jabber'] == '')
				{
					$set[] = 'jabber=\'\'';
				}
				else
				{
					return self::profile_edit($atc, $id_user, $target, $act, $atc->lang['jid_is_incorrect_and_was_ignored']);
				}
			}
		}
		
		if(isset($_POST['language']))
		{
			if(preg_match(PCREGEXP_INTEGER, $_POST['language']))
			{
				$set[] = 'language=' . $_POST['language'];
			}
			else
			{
				if($_POST['language'] == '')
				{
					$set[] = 'language=0';
				}
				else
				{
					return self::profile_edit($atc, $id_user, $target, $act, $atc->lang['language_is_incorrect_and_was_ignored']);
				}
			}
		}
		
		//Новый пасс
		if(isset($_POST['new_password']) && isset($_POST['new_password_confirmation']) && $_POST['new_password'] != '' && $_POST['new_password_confirmation'] != '')
		{
			if(!$manager && $ok)
			{
				if($_POST['new_password'] == $_POST['new_password_confirmation'])
				{
					$set[] = 'password=\'' . md5($_POST['new_password']) . '\'';
				}
				else
				{
					return self::profile_edit($atc, $id_user, $target, $act, $atc->lang['not_equal_passwords']);
				}
			}
			elseif($manager)
			{
				$set[] = 'password=\'' . md5($_POST['new_password']) . '\'';
			}
			else
			{
				return self::profile_edit($atc, $id_user, $target, $act, $atc->lang['incorrect_old_password']);
			}
		}
		
		if(isset($_POST['layout']))
		{
			if(preg_match(PCREGEXP_LAYOUT, $_POST['layout']) && @is_dir(ATCMS_ROOT . '/layout/' . $_POST['layout']))
			{
				$set[] = 'layout=\'' . $_POST['layout'] . '\'';
			}
			else
			{
				return self::profile_edit($atc, $id_user, $target, $act, $atc->lang['layout_is_incorrect_and_was_ignored']);
			}
		}
		
		//Временная зона
		if(isset($_POST['timezone']))
		{
			if(preg_match(PCREGEXP_TIMEZONE, $_POST['timezone']))
			{
				$set[] = 'timezone=\'' . $_POST['timezone'] . '\'';
			}
			else
			{
				return self::profile_edit($atc, $id_user, $target, $act, $atc->lang['timezone_is_incorrect_and_was_ignored']);
			}
		}
		
		if(isset($_POST['site']))
		{
			if($_POST['site'] != '')
			{
				if(preg_match(PCREGEXP_SITE_URL, $_POST['site']))
				{
					$set[] = 'site=\'' . $atc->db->db_escape($_POST['site']) . '\'';
				}
				else
				{
					return self::profile_edit($atc, $id_user, $target, $act, $atc->lang['site_is_incorrect_and_was_ignored']);
				}
			}
			else
			{
				$set[] = 'site=\'\'';
			}
		}
		
		if($manager)
		{
			if(isset($_POST['level']))
			{
				$_POST['level'] = $_POST['level'] == 1 ? 1:0;
				if($id_user == 1 && $_POST['level'] == 0)
				{
					return self::profile_edit($atc, $id_user, $target, $act, $atc->lang['cannot_reset_admin_level']);
				}
				else
				{
					$set[] = 'level=' . $_POST['level'];
				}
			}
		}
		
		if(isset($_POST['date_format'])) $set[] = 'date_format=\'' . $atc->db->db_escape($_POST['date_format']) . '\'';
		if(isset($_POST['signature'])) $set[] = 'signature=\'' . $atc->db->db_escape($atc->preprocess_text($_POST['signature'], ATCFORMAT_BBCODE, BBCODE_MODE_CLASSIC)) . '\'';
		if(isset($_POST['location'])) $set[] = 'location=\'' . $atc->db->db_escape($_POST['location']) . '\'';
		if(isset($_POST['occupation'])) $set[] = 'occupation=\'' . $atc->db->db_escape($_POST['occupation']) . '\'';
		if(isset($_POST['interests'])) $set[] = 'interests=\'' . $atc->db->db_escape($_POST['interests']) . '\'';
		
		//Если есть что обновлять
		if(count($set) > 0)
		{
			$set = implode(', ', $set);
			$sql = 'UPDATE ' . USERS_TABLE . ' SET ' . $set . ' WHERE id_user=' . $id_user;
			$atc->db->db_query($sql);
		}
		
		if($manager)
		{
			$atc->log_message(ATCEVENT_GENERAL, 'Editing the profile of ' . $_POST['name']);
		}
		else
		{
			$atc->log_message(ATCEVENT_GENERAL, 'User ' . $atc->session->user . ' modified his/her profile');
		}
		
		$atc->message($atc->lang['message'], $atc->lang['profile_updated'], $return_to, $atc->lang['go_back']);
	}
	
	/**
	* Создать новую учётную запись
	* Это и при регистрации, и при администрировании (добавление пользователя)
	* @param object ссылка на ядро
	* @param string URL, на который требуется отправлять форму
	* @param string значение для скрытого поля act (навигация)
	* @param string сообщение об ошибке
	* @param bool админка или нет
	*/
	
	public static function create_user(& $atc, $target, $act, $error='', $manager=false)
	{
		!$manager && $atc->session->ok && $atc->message($lang['error'], $lang['access_denied'], ATCMS_WEB_PATH . '/register.php', $lang['go_back']);
		
		if($manager)
		{
			$_POST['level'] = isset($_POST['level']) ? $_POST['level'] : 0;
			$levels = '<option value="0"' . ($_POST['level']==0 ? ' selected' : '') . '> ' . $atc->lang['user'] . '</option><option value="1"' . ($_POST['level']==1 ? ' selected' : '') . '> ' . $atc->lang['admin'] . '</option>';
		}
		
		$data = array
		(
			'act'=>$act,
			'name'=>htmlspecialchars(isset($_POST['name']) ? $_POST['name'] : ''),
			//'password'=>htmlspecialchars(isset($_POST['password']) ? $_POST['password'] : ''),
			//'password_confirmation'=>htmlspecialchars(isset($_POST['password_confirmation']) ? $_POST['password_confirmation'] : ''),
			'password'=>'',
			'password_confirmation'=>'',
			'email'=>htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : ''),
			'language'=>$atc->get_languages_list(isset($_POST['language']) ? $_POST['language'] : $atc->language),
			'timezone'=>atcms_timezone_browser(isset($_POST['timezone']) ? $_POST['timezone'] : 0),
			'layout'=>atcms_layout_browser(isset($_POST['layout']) ? $_POST['layout'] : $atc->cfgvar('layout')),
		);
		
		if($manager)
		{
			$data['level'] = $levels;
		}
		
		$scheme = $manager ? 'admin/new_user' : 'register';
		
		$nu = $atc->template('registration_form');
		$form = $atc->forms->create($scheme, false, $atc->lang, $data, $target, 'POST', $error);
		$nu->add_tag('FORM', $form->ret());
		$atc->process_contents($nu->ret(), $atc->lang['new_user']);
	}
	
	/**
	* Сохранить учётную запись пользователя
	* Обработчик формы, генерируемой функцией atcform::create_user
	* @param object ссылка на ядро
	* @param string URL, на который требуется отправлять форму
	* @param string значение для скрытого поля act (навигация)
	* @param string куда переходить при успешном выполнении
	* @param bool менеджер или нет
	*/
	
	public static function create_user_save(& $atc, $target, $act, $return_to, $manager=false)
	{
		if(!$atc->forms->validate())
		{
			return self::create_user($atc, $target, $act, $atc->lang['oops'], $manager);
		}
		
		if(!$manager && !$atc->session->validate_captcha())
		{
			return self::create_user($atc, $target, $act, $atc->lang['wrong_captcha'], $manager);
		}
		
		if(!isset($_POST['name']) || !preg_match(PCREGEXP_USERNAME, $_POST['name']))
		{
			return self::create_user($atc, $target, $act, $atc->lang['register_invalid_login'], $manager);
		}
		
		$sql = 'SELECT count(*) AS cnt FROM ' . USERS_TABLE . ' WHERE name=\'' . $atc->db->db_escape($_POST['name']) . '\'';
		$c = $atc->db->db_query($sql);
		$cnt = $atc->db->db_result($c, 0, 'cnt');
		$atc->db->db_freeresult($c);
		if($cnt > 0)
		{
			return self::create_user($atc, $target, $act, $atc->lang['user_already_exists'], $manager);
		}
		
		if(!isset($_POST['password']) || !preg_match(PCREGEXP_PASSWORD, $_POST['password']))
		{
			return self::create_user($atc, $target, $act, $atc->lang['register_invalid_password'], $manager);
		}
		
		if(!isset($_POST['password']) ||$_POST['password'] != $_POST['password_confirmation'])
		{
			return self::create_user($atc, $target, $act, $atc->lang['register_password_mismatch'], $manager);
		}
		
		if(!isset($_POST['email']) || !preg_match(PCREGEXP_EMAIL, $_POST['email']))
		{
			return self::create_user($atc, $target, $act, $atc->lang['register_invalid_email'], $manager);
		}
		
		//Если не менеджер, ещё нужно проверить, не занят ли мыл
		if(!$manager)
		{
			$sql = 'SELECT count(*) AS cnt FROM ' . USERS_TABLE . ' WHERE email=\'' . $_POST['email'] . '\'';
			$c = $atc->db->db_query($sql);
			$cnt = $atc->db->db_result($c, 0, 'cnt');
			$atc->db->db_freeresult($c);
			if($cnt > 0)
			{
				return self::create_user($atc, $target, $act, $atc->lang['email_already_exists'], $manager);
			}
		}
		
		if(!isset($_POST['language']) || !$atc->id_language_installed($_POST['language']))
		{
			return self::create_user($atc, $target, $act, $atc->lang['invalid_language'], $manager);
		}
		
		if($manager)
		{
			if(!isset($_POST['level']) || !preg_match('#^(0|1)$#', $_POST['level']))
			{
				return self::create_user($atc, $target, $act, $atc->lang['invalid_access_level'], $manager);
			}
		}
		else
		{
			$_POST['level'] = 0;
		}
		
		if(!isset($_POST['timezone']) || !preg_match(PCREGEXP_TIMEZONE, $_POST['timezone']))
		{
			return self::create_user($atc, $target, $act, $atc->lang['invalid_timezone'], $manager);
		}
		
		$sql = 'INSERT INTO ' . USERS_TABLE . ' (name, password, level, email, regtime, hide_email, language, layout, timezone) VALUES (\'' . $atc->db->db_escape($_POST['name']) . '\', \'' . md5($_POST['password']) . '\', ' . $_POST['level'] . ', \'' . $atc->db->db_escape($_POST['email']) . '\', ' . CURRENT_TIMESTAMP . ', 1, ' . $_POST['language'] . ', \'' . $_POST['layout'] . '\', \'' . $_POST['timezone'] . '\')';
		$atc->db->db_query($sql);
		
		if($manager)
		{
			$atc->log_message(ATCEVENT_GENERAL, 'Created new user: ' . $_POST['name']);
		}
		else
		{
			$atc->log_message(ATCEVENT_GENERAL, 'Registered as: ' . $_POST['name']);
		}
		
		$atc->message($atc->lang['message'], $atc->lang['user_saved'], $return_to, $manager ? $atc->lang['return_to_acp'] : $atc->lang['go_index']);
		/// NOTE: ^ в одном месте в админке определяем куда возвращаться, в другом месте тут определяем что писать. Вся строгость в жопе.
	}
}

?>
