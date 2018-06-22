<?php
/**
 * DLSiblings
 * вывод соседних ресурсов с шаблонизацией (множественная кольцевая перелинковка)
 * @category snippet
 *
 * @version   0.2
 * CMS version MODx Evo 7.1.6
 * @lastupdate 03/04/2017
 * 
 * @author Aharito http://aharito.ru на основе DLPrevNext @author Agel_Nash <Agel_Nash@xaker.ru>
 *
 * @params &idType, &parents, &documents, &ignoreEmpty - как в DocLister 
 * @param int &Qty Кол-во соседей с каждой стороны, имеет приоритет над &prevQty и &nextQty, default 2
 * @param int &prevQty Кол-во соседей-предшественников. Приоритет меньше $Qty, default 2
 * @param int &nextQty Кол-во соседей-последователей. Приоритет меньше $Qty, default 2
 * @param string &ownerTPL Шаблон-обертка, должен содержать плейсхолдер [+wrap+], default null (вывод не оборачивается в ownerTPL)
 * @params string &tpl, &tplOdd и &tplEven, &tplIdN, &tplFirst и &tplLast Шаблоны элемента как в DocLister в порядке увеличения приоритета
 * @param string &noneTPL Шаблон с информацией, что ничего нет как в DocLister,  default null (пусто).
 * @param (0|1) &noneWrapOuter Как в DocLister, оборачивать ли шаблон noneTPL в обёртку ownerTPL.
 * Параметр &noneWrapOuter имеет смысл, только если ничего не нашлось и при этом задан ownerTPL.
 * @param string &prepare Как в DocLister.
 * 
 * @NOTE: Другие шаблоны из набора DocLister не используются.
 * @NOTE: Остальные параметры - как у DocLister
 *
 * @example
 *       [[DLSiblings? &idType=`parents` &parents=`[*parent*]` &tpl=`@CODE:<a href="[+url+]">[+tv_h1+]</a><br>` &Qty=`2` &tvList=`h1` ]]
**/

if ( ! defined('MODX_BASE_PATH')) { die('HACK???'); }

// Получаем параметры, заданные при вызове сниппета  DLSiblings
$params = is_array($modx->Event->params) ? $modx->Event->params : array();

/**
 * Задаем дефолтные значения новым DL-стилем :)
 *
 * Некешир. сниппет на некешир. ресурсе
 * Mem : 3.5 mb, MySQL: 0.0190 s, 17 request(s), PHP: 0.1800 s, total: 0.1990 s, document from database
 *
 * Разница со старым стилем (через isset) в пределах погрешности 
 */

// Шаблоны
$ownerTPL = \APIhelpers::getkey($params, 'ownerTPL', null);
$noneTPL = \APIhelpers::getkey($params, 'noneTPL', null);
$tpl = \APIhelpers::getkey($params, 'tpl', '@CODE:<a href="[+url+]">[+e_title+]</a>');

// Параметры
$Qty = \APIhelpers::getkey($params, 'Qty', 2);
$prevQty = \APIhelpers::getkey($params, 'prevQty', $Qty);
$nextQty = \APIhelpers::getkey($params, 'nextQty', $Qty);
$noneWrapOuter = \APIhelpers::getkey($params, 'noneWrapOuter', 1);


$out = "";

$ID = $modx->documentIdentifier;

// мержим 'display' => '0' (выводить все док-ты), потому что за кол-во отвечает Qty, prevQty и nextQty
$params = array_merge( $params, array('api' => '1', 'debug' => '0', 'display' => '0') );

// Этот вызов ДокЛистера обрабатывает все наши параметры, кроме шаблонов и подстановки плейсхолдера [+sysKey.class+]
$json = $modx->runSnippet("DocLister", $params);
$children = jsonHelper::jsonDecode($json, array('assoc' => true));

if (!is_array($children) || is_array($children) && count($children) < 2) {
	return '';
}

$ids = array_keys($children);
$count = count($ids);
$curIndex = array_search($ID, $ids);

$queue = [];

$iteration = 0;
$index = $curIndex;
while ($iteration < $prevQty) {
	if ($ids[$index] != $ID) {
		$queue[] = $ids[$index];
		$iteration++;
	}
	
	$index++;
	
	if ($index >= $count) {
		$index = 0;
	}
}

$iteration = 0;
$index = $curIndex;
while ($iteration < $nextQty) {
	if ($ids[$index] != $ID) {
		$queue[] = $ids[$index];
		$iteration++;
	}
	
	$index--;
	
	if ($index < 0) {
		$index = $count - 1;
	}
}

$TPL = DLTemplate::getInstance($modx);

foreach ($queue as $index => $docid) {
	if ($docid != $ID) {
		$iterationName = ($index % 2 == 1) ? 'Odd' : 'Even';

		// Какой шаблон выводить на этой итерации?
		// Идут сверху вниз по убыванию приоритета
		$renderTPL = $tpl;
		$renderTPL = \APIhelpers::getkey($params, 'tpl' . $iterationName, $renderTPL);			// tplOdd или tplEven
		$renderTPL = \APIhelpers::getkey($params, 'tplId' . ($index + 1), $renderTPL);				// tplIdN начиная с 1

		if ($index == 0) {
			$renderTPL = \APIhelpers::getkey($params, 'tplFirst', $renderTPL);			// tplFirst
		}

		if ($index == $prevQty + $nextQty - 1) {
			$renderTPL = \APIhelpers::getkey($params, 'tplLast', $renderTPL);			// tplLast
		}	

		$out .= $TPL->parseChunk($renderTPL, $children[$docid]);	
	}

	$index++;
}

// Оборачиваем в ownerTPL, если он не null
if( $ownerTPL ) {
	$out = $TPL->parseChunk( $ownerTPL, array('wrap' => $out) );
}

return $out;
