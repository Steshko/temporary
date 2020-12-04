<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/** @var CUser $USER */
if (!$USER->IsAdmin()) {$APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));}

?>
<style>
    .collapsible {
        cursor: pointer;
    }
    .h-content {
        display: none;
        overflow: hidden;
        /*display: block;*/
    }
    .big-list-on {
        cursor: pointer;
    }
    .price-down {
        background: green;
        color: white;
    }
    .price-up {
        background: red;
    }
    .price-true {
        background: yellow;
    }
</style>



<?php

$csvArray = Array();// Массив для данных из csv файла
$changePrice = 0;// Счетчик измененных цен товара
$priceUp = 0;// Счетчик Подорожало
$priceDown = 0;// Счетчик Подешевело
$countPriceColumn = 0;// Товаров в прайсе поставщика
$countProductWithArt = 0;// Товаров с артикулом поставщика на сайте

$countCreateNew = 0;// Создано новых товаров на сайте
$countProductOff = 0;// Количество отключенных товаров (установлено "Нет в наличии")

switch ($request->get('vendor')) {
    case 'mistery':
        echo '<div class="success">Поставщик - Мистери</div>';
// mistery - Поставщик Мистери



// END mistery - Поставщик Мистери
        break;
    case 'chernov':
        echo '<div class="success">Поставщик - Чернов</div>';
// chernov - Поставщик Чернов
        $_firstRow = 11;// Номер первой строки с данными
        $_columnName = 1;// Номер колонки Наименование
        $_columnArticle = 2;// Номер колонки Артикул
        $_columnPrice = 9;// Номер колонки РРЦ
        $_propertyName = 'ART_CHERNOV';

        // Перебираем csv и создаем массив с позициями с прайса
        $i = 2;
        echo '<div >';

        foreach ($csv as $row) {
            if (($i > $_firstRow) && ($row[$_columnArticle])) { // Игнорируем строки до первой значимой и у которых нет артикула
                $csvArray[] = ['art' => trim($row[$_columnArticle]), 'name' => trim($row[$_columnName]), 'price' => substr(preg_replace('/[^0-9]/', '', $row[$_columnPrice]), 0, -2)];
              //  $csvArray[] = ['art' => trim($row[$_columnArticle]), 'name' => trim($row[$_columnName]), 'price' => (1*str_replace(",", "", substr($row[$_columnPrice],0,-3)))];
                echo 'Наименование: '.$row[$_columnName].' - Код: '.$row[$_columnArticle].' - Цена: '.substr(preg_replace('/[^0-9]/', '', $row[$_columnPrice]), 0, -2);
                echo '<br/>';
            }
            $i++;
        }
        echo '</div>';
        echo '<div class="success">Найдено '.count($csvArray).' товаров в прайсе.</div>';

// END chernov - Поставщик Чернов
        break;
    case 'karma':
        echo '<div class="success">Поставщик - Карма</div>';
        break;
    case 'stopol':
        echo '<div class="success">Поставщик - Стопол остатки</div>';
        break;
    case 'stopol_price':
        echo '<div class="success">Поставщик - Стопол цены</div>';
        break;
    case 'autoline':
        echo '<div class="success">Поставщик - Автолайн</div>';
        break;

    default: break;

}

    // Получаем выборку с БД товаров, у которых заполнен артикул соответствующего поставщика
    \Bitrix\Main\Loader::includeModule('iblock');
    $product = \CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => 11, '!PROPERTY_'.$_propertyName => false], false, false,
        ["ID", 'PROPERTY_'.$_propertyName, "PROPERTY_PRICE_BACE"]);
//echo "<pre>";
//print_r($product->GetNext() );
//echo "</pre>";
//exit();
    $countProductWithArt = $product->SelectedRowsCount();
    echo '<div class="success">Найдено '.$countProductWithArt.' товаров с артикулом поставщика в каталоге.</div>';

    // Заполняем массив найденными товарами и одновременно проверяем вхождение в прайс поставщика
    if($countProductWithArt) {
        echo '<span class="collapsible" title="Показать список">+</span><div class="big-list list-hidden">';
    }
    $pArray = Array();
    while ($el = $product->fetch()):
        $csvKey = null;
        $pArray[$el['ID']] = $el['PROPERTY_'.$_propertyName.'_VALUE'];
        $csvKey = searchArtInCsv($el['PROPERTY_'.$_propertyName.'_VALUE'], $csvArray);
        echo $el['ID'] . ' - ' . $el['PROPERTY_'.$_propertyName.'_VALUE'] . ' - '. ($csvKey ? 'Есть в прайсе №'.$csvKey : '').'<br/>';
        if (!is_null($csvKey)) { // Товар из прайса есть на сайте
            // Обновляем цену товара и наличие
            setProductQuantity($el['ID']);
            $basePrice = CPrice::GetBasePrice($el['ID']);
            echo 'Цена на сайте: '.CurrencyFormat($basePrice["PRICE"], $basePrice["CURRENCY"])
                .' ||| Цена в прайсе: '.$csvArray[$csvKey]['price'].' - '.(($basePrice["PRICE"] > $csvArray[$csvKey]['price']) ? '<span class="price-down">Подешевело</span>' : (($basePrice["PRICE"] < $csvArray[$csvKey]['price']) ? '<span class="price-up">Подорожало</span>' : '<span class="price-true">Цена не изменилась</span>')).'<br/>';
            if($_changePrice && ($basePrice["PRICE"] != $csvArray[$csvKey]['price'])) {
                $result = changeProductPrice($el['ID'], $csvArray[$csvKey]['price']);
                $changePrice += $result;
                echo '<div class="warning">Цену изменили!</div>';
            }

            // Удаляем элемент из массива товара сайта
            unset($pArray[$el['ID']]);
            // Удаляем элемент с ключом $csvKey из массива прайса поставщика
            unset($csvArray[$csvKey]);

        }
        else {
            // Проверяем необходимость установки "Нет в наличии" и дективируем товар
            if($_deactivate) {
                setProductQuantity($el['ID'], false);
                $countProductOff++;
                echo '<div class="warning">Установлено "Нет в наличии"! </div>';
            }
        }
    endwhile;
    if($countProductWithArt) {
        echo '</div>';
    }

    // Выводим итоги работы скрипта
    if($changePrice)
        echo '<div class="success">Изменили цену для: '.$changePrice.' товаров</div>';
    echo '<div class="success">Товаров сайта отсутствующих в прайсе и готовых к деактивации (статус "Нет в наличии"): '.count($pArray).'</div>';
    // Вывод количества деактивированного товара
    if($_deactivate && $countProductOff) {
        echo '<div class="warning">Товаров декативировано: '.$countProductOff.'</div>';
    }
    else {
        echo '<div class="error">Деактивация отключена!!</div>';
    }

    echo '<div class="success">Товаров в прайсе, готовых к добавлению на сайт: '.count($csvArray).'</div>';
    if($request->get('create') && count($csvArray)) { // Товар есть в прайсе но нет на сайте
        // СОЗДАЕМ И ДОБАВЛЯЕМ НОВЫЕ ТОВАРЫ НА САЙТ
        if(count($csvArray) > 0) {
            $countCreateNew = addNewProducts($csvArray, $_propertyName);
        }
        if($countCreateNew) {
            echo '<div class="warning">Новых товаров создано: '.$countCreateNew.'</div>';
        }
        else {
            echo '<div class="error">При добавлении товаров возникла ошибка! Проверьте раздел Системное.</div>';
        }
    }
    else {
        if(count($csvArray))
        echo '<div class="error">Новые товары не созданы для '.count($csvArray).' позиций из прайса поставщика</div>';
    }

    //*
    //* Конец Фильма
    //*

    // Просматривает прайс поставщика на предмет наличия артикула товара
    // Возвращает ключ в массиве прайса поставщика
    function searchArtInCsv($art, $array) {
        foreach ($array as $key=>$value) {
            if (trim($value['art']) == trim($art)) return $key;
        }
        return null;
    }

    // Получает остаток массива прайса поставщика и создает новые товары
    // Добавляет Название, раздел Системное, цену, артикул поставщика, делает неактивным
    // Возвращает количество добавленных товаров
    function addNewProducts($arr, $prop) {
        $cnp = 0;
        $elProd = new CIBlockElement;
        foreach ($arr as $item) {
            $params = Array(
                "max_len" => "100", // обрезает символьный код до 100 символов
                "change_case" => "L", // буквы преобразуются к нижнему регистру
                "replace_space" => "_", // меняем пробелы на нижнее подчеркивание
                "replace_other" => "_", // меняем левые символы на нижнее подчеркивание
                "delete_repeat_replace" => "true", // удаляем повторяющиеся нижние подчеркивания
                "use_google" => "false", // отключаем использование google
            );
            $arFields = array(
                "ACTIVE" => "N",
                "IBLOCK_ID" => 11,
                "IBLOCK_SECTION_ID" => 99,
                "NAME" => $item['name'],
                "CODE" => CUtil::translit($item['name'], "ru" , $params),
                "PROPERTY_VALUES" => array(
                    $prop => $item['art']
                )
            );
            $id = $elProd->Add($arFields, false, false, true);
            if($id) {
                echo '<div class="success">Товар с id '.$id.' добавлен</div>';
            }
            else {
                echo '<div class="error">Ошибка!: '.$elProd->LAST_ERROR.' </div>';
            }

            if($id) { // Устанавливаем цену и наличие
                $res = changeProductPrice($id, $item['price']);
                setProductQuantity($id);
                $cnp += $res;
            }


        }
        if ($cnp) return $cnp;
        return false;
    }// Function addNewProducts

    // Изменяет цену товара
    function changeProductPrice($id, $price) {

        $cPrice = new CPrice;

        $res = $cPrice->SetBasePrice($id, $price, 'UAH');

        CCatalogProduct::Add(["ID" => $id, "PRICE_TYPE" => "TYPE_PRODUCT"]);
        //CCatalogProduct::Update($id, ["PRICE_TYPE" => "TYPE_PRODUCT"]);
        return $res;
    }

    // Устанавливает товар "В наличии"
    function setProductQuantity($id, $on = true) {
        $elUpdate = new CIBlockElement;
        if($on) {
            $elUpdate->SetPropertyValuesEx($id, 11, [ 'nalichie' => 20]);
        }
        else {
            $elUpdate->SetPropertyValuesEx($id, 11, [ 'nalichie' => 22]);
        }

        echo '<div class="warning">Наличие обновлено </div>';
    }


?>
