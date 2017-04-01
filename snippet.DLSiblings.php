<?php
/**
 * DLSiblings
 * вывод соседних ресурсов с шаблонизацией (множественная кольцевая перелинковка)
 * @category snippet
 *
 * @version   0.1
 * CMS version MODx Evo 7.1.6
 * @lastupdate 25/03/2017
 * 
 * @author Aharito http://aharito.ru на основе DLPrevNext @author Agel_Nash <Agel_Nash@xaker.ru>
 *
 * @param int &Qty Кол-во соседей с каждой стороны, имеет приоритет над &prevQty и &nextQty, default &Qty=`2`
 * @param int &prevQty Кол-во соседей-предшественников. Приоритет меньше $Qty, default &prevQty=`2` 
 * @param int &nextQty Кол-во соседей-последователей. Приоритет меньше $Qty, default &nextQty=`2` 
 * @param string &ownerTPL Шаблон-обертка, должен содержать плейсхолдер [+wrap+], default &ownerTPL=`@CODE:<div>[+wrap+]</div>`
 * @param string &tpl Шаблон элемента как в DocLister.
 * @param string &noneTPL Шаблон с информацией, что ничего нет как в DocLister,  default - пусто.
 * @param (0|1) &noneWrapOuter Как в DocLister, оборачивать ли шаблон noneTPL в обёртку ownerTPL.
 * Параметр &noneWrapOuter имеет смысл, только если ничего не нашлось и при этом задан ownerTPL.
 * @param string &prepare Как в DocLister.
 * 
 * @NOTE: Другие шаблоны из набора DocLister пока не используются.
 * @NOTE: Остальные параметры - как у DocLister
 *
 * @example
 *       [[DLSiblings? &idType=`parents` &parents=`[*parent*]` &tpl=`@CODE: <a href="[+url+]">[+tv_h1+]</a><br>` &Qty=`2` &tvList=`h1` ]]
**/

if ( ! defined('MODX_BASE_PATH')) { die('HACK???'); }

$ownerTPL = isset($ownerTPL) ? $ownerTPL : '@CODE:[+wrap+]'; //Дефолтное значение &ownerTPL
$Qty = isset($Qty) ? $Qty : 2; //Дефолтное значение &Qty

$out = "";
$siblings = array();

$ID = $modx->documentIdentifier;
$params = is_array($modx->Event->params) ? $modx->Event->params : array();
$params = array_merge( $params, array('api' => '1', 'debug' => '0', 'display' => 'all') ); // 'display' => 'all', потому что за кол-во отвечает Qty 

$json = $modx->runSnippet("DocLister", $params);
$children = jsonHelper::jsonDecode($json, array('assoc' => true));
$children = is_array($children) ? $children : array(); // Тут проверка, что вернулся массив

$ids = array_keys($children); //Индексный массив ID в выборке (потом избавиться от него через prev-next?)

$curIndex = array_search($ID, $ids); //Текущий индекс (индекс текущего ID)

$count = count($ids); // Длина массива $ids
$lastIndex = $count - 1; // Последний индекс массива $ids

$TPL = DLTemplate::getInstance($modx);

if($count-1 > 0) {// Если длина выборки (за исключением текущего элемента) больше 0

	if(($count - 1) <= $Qty*2) { // Если длина выборки (за исключением текущего элемента) меньше нужного кол-ва
		// То просто выводим все элементы выборки
		for($i=0; $i<=$lastIndex; $i++) {
			$out .= ($curIndex == $i) ? "" : $TPL->parseChunk($tpl, $children[$ids[$i]]);
		}
<?php
/**
 * DLSiblings
 * вывод соседних ресурсов с шаблонизацией (множественная кольцевая перелинковка)
 * @category snippet
 *
 * @version   0.1
 * CMS version MODx Evo 7.1.6
 * @lastupdate 25/03/2017
 * 
 * @author Aharito http://aharito.ru на основе DLPrevNext @author Agel_Nash <Agel_Nash@xaker.ru>
 *
 * @param int &Qty Кол-во соседей с каждой стороны, default &Qty=`2`
 * @param string &ownerTPL Шаблон-обертка, должен содержать плейсхолдер [+wrap+], default &ownerTPL=`@CODE:<div>[+wrap+]</div>`
 * @NOTE остальные параметры - как у DocLister
 * @example
 *       [[DLSiblings? &idType=`parents` &parents=`[*parent*]` &tpl=`@CODE: <a href="[+url+]">[+tv_h1+]</a><br>` &Qty=`2` &tvList=`h1` ]]
**/


if ( ! defined('MODX_BASE_PATH')) { die('HACK???'); }

include_once(MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');

$p = &$modx->event->params;
if ( ! is_array($p)) {
    $p = array();
}

/**
 * Старым стилем :)
 * 
 * Некешир. сниппет на некешир. ресурсе
 * Mem : 3.5 mb, MySQL: 0.0140 s, 17 request(s), PHP: 0.1630 s, total: 0.1770 s, document from database
 *
 * $ownerTPL = isset($ownerTPL) ? $ownerTPL : '@CODE:[+wrap+]';			//Дефолтное значение &ownerTPL
 *
 * $prevQty = isset($Qty) ? $Qty : ( isset($prevQty) ? $prevQty : 2 );	//Дефолтное значение &prevQty
 * $nextQty = isset($Qty) ? $Qty : ( isset($nextQty) ? $prevQty : 2 );	//Дефолтное значение &nextQty
**/


/**
 * Новым DL-стилем :)
 *
 * Некешир. сниппет на некешир. ресурсе
 * Mem : 3.5 mb, MySQL: 0.0190 s, 17 request(s), PHP: 0.1800 s, total: 0.1990 s, document from database
 *
 * Разница со старым способом в пределах погрешности 
**/

$ownerTPL = \APIhelpers::getkey($p, 'ownerTPL', '@CODE:[+wrap+]');   //Дефолтное значение &ownerTPL

$Qty = \APIhelpers::getkey($p, 'Qty', 2);             //Дефолтное значение &Qty
$prevQty = \APIhelpers::getkey($p, 'prevQty', $Qty);  //Дефолтное значение &prevQty
$nextQty = \APIhelpers::getkey($p, 'nextQty', $Qty);  //Дефолтное значение &nextQty


$out = "";
$siblings = array();

$ID = $modx->documentIdentifier;
$params = is_array($modx->Event->params) ? $modx->Event->params : array();
$params = array_merge( $params, array('api' => '1', 'debug' => '0', 'display' => 'all') ); // 'display' => 'all', потому что за кол-во отвечает Qty 

$json = $modx->runSnippet("DocLister", $params);
$children = jsonHelper::jsonDecode($json, array('assoc' => true));
$children = is_array($children) ? $children : array(); // Тут проверка, что вернулся массив

$ids = array_keys($children); //Индексный массив ID в выборке (потом избавиться от него через prev-next?)

$curIndex = array_search($ID, $ids); //Текущий индекс (индекс текущего ID)

$count = count($ids);    // Длина массива $ids
$lastIndex = $count - 1; // Последний индекс массива $ids

$TPL = DLTemplate::getInstance($modx);

if($count-1 > 0) {// Если длина выборки (за исключением текущего элемента) больше 0

	if(($count - 1) <= $prevQty + $nextQty) { // Если длина выборки (за исключением текущего элемента) меньше нужного кол-ва
		// То просто выводим все элементы выборки
		for($i=0; $i<=$lastIndex; $i++) {
			$out .= ($curIndex == $i) ? "" : $TPL->parseChunk($tpl, $children[$ids[$i]]);
		}

	} else { // Иначе ищем соседей

		for($i=1; $i<=$prevQty; $i++) {

			/** 
			 * Для Prev
			 * Если "перескока" в хвост нет, то индекс вычисляется как $curIndex - $i
			 * Если из начала $ids перескочили в его хвост, то индекс считаем как $count + $curIndex - $i
			**/
			$index = ($curIndex - $i >= 0) ? $curIndex - $i : $count + $curIndex - $i;
			
			// Формируем массив $siblings с теми же индексами и значениями, как у $ids ($ids уже упорядочен как надо)
			$siblings[$index] = $ids[$index];
		}
		
		for($i=1; $i<=$nextQty; $i++) {

			/**
			 * Для Next
			 * Если "перескока" на начало нет, то индекс вычисляется как $curIndex + $i
			 * Если из хвоста $ids перескочили на его начало, то индекс считаем как $i - ($lastIndex - $curIndex) - 1
			**/
			$index = ($curIndex + $i <= $lastIndex) ? $curIndex + $i : $i - ($lastIndex - $curIndex) - 1;
			
			// Дополняем массив $siblings с теми же индексами и значениями, как у $ids
			$siblings[$index] = $ids[$index];

		}
		
		/**
		 * В итоге $siblings - это индексный массив с пропусками индексов, значения - ID ресурсов
		 * До сортировки выглядит примерно так: Array ( [6] => 114, [0] => 18, [5] => 109, [1] => 95 )
		**/

		// Сортируем по индексам (ключам) этот небольшой массив $siblings (не более 4+4 элементов, а скорее всего 2+2 или 3+3)		
		ksort($siblings);
		
		/**
		 * После сортировки выглядит так: Array ( [0] => 18, [1] => 95, [5] => 109, [6] => 114 )
	         * Теперь он отсортирован точно так же, как и было в выходных данных ДокЛистера
		**/

		// Выводим все элементы $siblings с шаблонизацией
		foreach($siblings as $value)
			$out .= $TPL->parseChunk($tpl, $children[$value]);

	}
        
	// Оборачиваем в ownerTPL
	$out = $TPL->parseChunk( $ownerTPL, array('wrap' => $out) );

} else { // Если длина выборки (за исключением текущего элемента) <= 0 (нет элементов, кроме текущего, или вообще нет)
        
	// Далее копируем поведение ДокЛистер для параметра &noneWrapOuter и шаблонов &noneTPL и &ownerTPL
	
	// Если задан noneTPL, парсим его без параметров
	if(isset($params['noneTPL'])) $out = $TPL->parseChunk( $noneTPL, array() );

	// Если noneWrapOuter не задано или задано как 1, и ownerTPL не пустой
	if( (!isset($params['noneWrapOuter']) || $params['noneWrapOuter'] == 1) && !empty($params['ownerTPL']) )
		// то "нулевой" результат оборачиваем в ownerTPL
		$out = $TPL->parseChunk( $ownerTPL, array('wrap' => $out) );

}

return $out;
