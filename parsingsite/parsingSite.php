<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of parsingSite
 *
 * @author Александр
 */
class parsingSite {
	
	/**
	 * Функция ищет пользователе во всех сообществах
	 * 
	 * @param object $__usersArr			  Массив который будет заполнен пользователями.
	 * @param object $__countCommunities	  Колличество сообществ который нужно посмотреть.
	 * @param object $__cuntPageInCommunities Колличество страниц в сообществах которые нужно посмотреть.
	 * 
	 * @return void
	 */
	function parsSite(&$__usersArr, $__countCommunities, $__cuntPageInCommunities, $__modeSerch)
	{	
		#массив ссылок
		$linksArr = array();

		#ишет  ссылку на сообщесвто
		$parsCommunities = '|<\s*a\sclass\s*=\s* "*c-link\s*c-link--text"* \s* href\s*="*(/communities/[a-z/0-9.?&=]*)"* [^>]*? > |ixs';

		#ищет ссылки пользователей на странице сообщетва
		$parsPageCommunities = '|<\s*a\s+ .*? href\s*="*/users/([a-z\-0-9.?/#&=]*)"* .*? \s*>|ixs';

		#ищет ссылку на следующую страничку в блоке
		$parsNextPage = '|<\s*a\sclass\s*=\s* "*c-pager__link"* \s* rel="*next"*
						\s* href\s*="*([a-z/0-9#.?&=]*)"* [^>]*? >|ixs';

		#забираем все ссылки на элементы сообщества поиск
		$str = file_get_contents('https://www.drive2.ru/communities/search');
		preg_match_all($parsCommunities, $str, $linksArr);

		#колличество просмотренных сообществ
		$countCommunities = 0;

		#поиск пользователей в из меню соообществ
		foreach ($linksArr[1] as $value) {
			$nextPageArr = array();
			$matchesArr  = array();
			$countPage   = 0;
			$countCommunities++;

			#просматриваем все сообщества
			do{
				$countPage++;
				$value = isset($nextPageArr[1])
					? $nextPageArr[1]
					: $value.'/blog/';

				#получаем очередную страницу сообщества
				$page = $this->getContents('https://www.drive2.ru'.$value);
				preg_match_all($parsPageCommunities, $page, $matchesArr);

				#добавляем пользователей со страницы, если они не были найдены ранее
				foreach ($matchesArr[1] as $value) {
					if (!in_array($value, $__usersArr)) {
						$__usersArr[] = $value;
					}
				}
			} while (preg_match($parsNextPage, $page, $nextPageArr) && 
					($countPage < $__cuntPageInCommunities || $__modeSerch == "все")
				);

			#если лимит страниц превышен то выходим из цыкла 
			if ($countCommunities == $__countCommunities && !($__modeSerch == "все")) {
				break;			
			}
		}
	}

	/**
	 * Функция ищет информайию о пользователях, взвращает true, если все хорошо и false в противном случае
	 * 
	 * @param object $__name				Имя пользователя информация о котором мы ищеи.
	 * @param object $__informationUsersArr Массив в которыый будет добавлена найденная инофрмация.
	 * 
	 * @return boolean
	 */
	function addInformationUser($__name, &$__informationUsersArr)
	{

		#забирает информацию о пользователе
		$parsUsers = '|
			<[^>]*> \s* <[^>]*>[Оо]бо\s+мне <[^>]*>\s* #нашли блок с информацией

			<div\s+[^>]*> \s* <a\s+[^>]*> \s* <div \s+[^>]*> 
			<span \s+ [^>]*><\s*/span\s*> <\s*img \s+ src\s*=\s*"([^"]*)"
			[^>]*> \s* <\s*/div\s*>\s*<\s*/a\s*>\s*<\s*/div\s*> \s*#забираем ссылкуна картинку

			(?: .*? <\s*div\s+ id\s*=\s* "\s*user-about-full\s*"[^>]*> 
			\s* (.*?) \s* <\s*/div\s*>)? # в этом кармане вся информация о пользователе, ее надо будет обработать

			.*? <\s*span\s* data-tt\s*=\s*"([^"]*)"\s*> #когда зарегестрирован
			([^<]*) # сколько лет на сайте
			|ixsu';

		#забирает имя воздаст и место жительства
		$parsDeterminationData = '|
			
			<\s*div \s+ class\s*=\s*"c-user-card__info"> \s*
			(?:<\s*span\s+class\s*=\s*"\s*c-user-card__imp\s*">)* \s*
			(?:<\s*span\s*>\s*([^<]*)\s* <[^>]*>)*#забираем имя
			[^<]* (?:<\s*span\s+ class\s*=\s*"c-user-card__age"\s* [^>]*> (.*?) <\s*/span\s*>\s*<\s*/span\s*>)* #возраст
			.*?	<\s*span\s*itemprop\s*=\s*"address">\s*<\s*span\s*title\s*="([^"]*)"\s*> #место жительства
			|ixsu';

		#массив с регулярками ищущими инстаграмм майл и т.д.
		$parsPersonalInformationArr = array(
			'YandexZen'	 => '|"(http[s]?://[^"]*?zen\.yandex\.ru[^"]*?)"|ixs',
			'FaceBook'	 => '|"(http[s]?://[^"]*?facebook\.com[^"]*?)"|ixs',
			'Instagramm' => '|"(http[s]?://[^"]*?instagram\.com[^"]*?)"|ixs',
			'Mail'		 => '|([a-z._\-0-9]+@[a-z.0-9]+)|xis'
		);
		
		#в этом массиве будет храниться информация о пользователях
		$personalInformationArr = array();
		$page				    = $this->getContents('https://www.drive2.ru/users/'.$__name);
		$bufferArr				= array();

		#достаем из страници блок с данными пользователя
		preg_match($parsDeterminationData, $page, $bufferArr);
		
		#ссылка на пользователя
		$personalInformationArr['URL'] = 'https://www.drive2.ru/users/'.$__name; 
		
		#записываем имя
		$personalInformationArr['Name'] = isset($bufferArr[1])
			? $bufferArr[1]
			: '';

		#записываем возраст
		$personalInformationArr['Age'] = isset($bufferArr[2])
			? $bufferArr[2]
			: '';

		#записываем место жительства
		$personalInformationArr['Location'] = isset($bufferArr[3])
			? $bufferArr[3]
			: '';

		#достаем из страници блок с данными пользователя
		preg_match($parsUsers, $page, $bufferArr);

		#записываем ссылку на фото
		$personalInformationArr['foto'] = isset($bufferArr[1])
			? $bufferArr[1]
			: '';

		#записываем дату регистрации
		$personalInformationArr['dataRegistration'] = isset($bufferArr[3])
			? $bufferArr[3]
			: '';

		#записываем сколько времени пользователь зарегистрирован в системе
		$personalInformationArr['YarOnSeSyte'] = isset($bufferArr[4])
			? $bufferArr[4]
			: '';

		#достаем инстарграмм, мэейл и т.д.
		foreach ($parsPersonalInformationArr as $key => $value) {
			preg_match($value, $page, $bufferArr);
			$personalInformationArr[$key] = isset($bufferArr[1])
				? $bufferArr[1]
				: '';
		}

		#добавляем в массив пользователя и информацию о нем
		$__informationUsersArr[$__name] = $personalInformationArr;

		return true;
	}
	
		
	function getContents($__url){
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $__url);
		
		$data = curl_exec($ch);
		curl_close($ch);
		
		return $data;
	}
}
