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

$csvArray = Array();// ������ ��� ������ �� csv �����
$changePrice = 0;// ������� ���������� ��� ������
$priceUp = 0;// ������� ����������
$priceDown = 0;// ������� ����������
$countPriceColumn = 0;// ������� � ������ ����������
$countProductWithArt = 0;// ������� � ��������� ���������� �� �����

$countCreateNew = 0;// ������� ����� ������� �� �����
$countProductOff = 0;// ���������� ����������� ������� (����������� "��� � �������")

switch ($request->get('vendor')) {
    case 'mistery':
        echo '<div class="success">��������� - �������</div>';
// mistery - ��������� �������



// END mistery - ��������� �������
        break;
    case 'chernov':
        echo '<div class="success">��������� - ������</div>';
// chernov - ��������� ������
        $_firstRow = 11;// ����� ������ ������ � �������
        $_columnName = 1;// ����� ������� ������������
        $_columnArticle = 2;// ����� ������� �������
        $_columnPrice = 9;// ����� ������� ���
        $_propertyName = 'ART_CHERNOV';

        // ���������� csv � ������� ������ � ��������� � ������
        $i = 2;
        echo '<div >';

        foreach ($csv as $row) {
            if (($i > $_firstRow) && ($row[$_columnArticle])) { // ���������� ������ �� ������ �������� � � ������� ��� ��������
                $csvArray[] = ['art' => trim($row[$_columnArticle]), 'name' => trim($row[$_columnName]), 'price' => substr(preg_replace('/[^0-9]/', '', $row[$_columnPrice]), 0, -2)];
              //  $csvArray[] = ['art' => trim($row[$_columnArticle]), 'name' => trim($row[$_columnName]), 'price' => (1*str_replace(",", "", substr($row[$_columnPrice],0,-3)))];
                echo '������������: '.$row[$_columnName].' - ���: '.$row[$_columnArticle].' - ����: '.substr(preg_replace('/[^0-9]/', '', $row[$_columnPrice]), 0, -2);
                echo '<br/>';
            }
            $i++;
        }
        echo '</div>';
        echo '<div class="success">������� '.count($csvArray).' ������� � ������.</div>';

// END chernov - ��������� ������
        break;
    case 'karma':
        echo '<div class="success">��������� - �����</div>';
        break;
    case 'stopol':
        echo '<div class="success">��������� - ������ �������</div>';
        break;
    case 'stopol_price':
        echo '<div class="success">��������� - ������ ����</div>';
        break;
    case 'autoline':
        echo '<div class="success">��������� - ��������</div>';
        break;

    default: break;

}

    // �������� ������� � �� �������, � ������� �������� ������� ���������������� ����������
    \Bitrix\Main\Loader::includeModule('iblock');
    $product = \CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => 11, '!PROPERTY_'.$_propertyName => false], false, false,
        ["ID", 'PROPERTY_'.$_propertyName, "PROPERTY_PRICE_BACE"]);
//echo "<pre>";
//print_r($product->GetNext() );
//echo "</pre>";
//exit();
    $countProductWithArt = $product->SelectedRowsCount();
    echo '<div class="success">������� '.$countProductWithArt.' ������� � ��������� ���������� � ��������.</div>';

    // ��������� ������ ���������� �������� � ������������ ��������� ��������� � ����� ����������
    if($countProductWithArt) {
        echo '<span class="collapsible" title="�������� ������">+</span><div class="big-list list-hidden">';
    }
    $pArray = Array();
    while ($el = $product->fetch()):
        $csvKey = null;
        $pArray[$el['ID']] = $el['PROPERTY_'.$_propertyName.'_VALUE'];
        $csvKey = searchArtInCsv($el['PROPERTY_'.$_propertyName.'_VALUE'], $csvArray);
        echo $el['ID'] . ' - ' . $el['PROPERTY_'.$_propertyName.'_VALUE'] . ' - '. ($csvKey ? '���� � ������ �'.$csvKey : '').'<br/>';
        if (!is_null($csvKey)) { // ����� �� ������ ���� �� �����
            // ��������� ���� ������ � �������
            setProductQuantity($el['ID']);
            $basePrice = CPrice::GetBasePrice($el['ID']);
            echo '���� �� �����: '.CurrencyFormat($basePrice["PRICE"], $basePrice["CURRENCY"])
                .' ||| ���� � ������: '.$csvArray[$csvKey]['price'].' - '.(($basePrice["PRICE"] > $csvArray[$csvKey]['price']) ? '<span class="price-down">����������</span>' : (($basePrice["PRICE"] < $csvArray[$csvKey]['price']) ? '<span class="price-up">����������</span>' : '<span class="price-true">���� �� ����������</span>')).'<br/>';
            if($_changePrice && ($basePrice["PRICE"] != $csvArray[$csvKey]['price'])) {
                $result = changeProductPrice($el['ID'], $csvArray[$csvKey]['price']);
                $changePrice += $result;
                echo '<div class="warning">���� ��������!</div>';
            }

            // ������� ������� �� ������� ������ �����
            unset($pArray[$el['ID']]);
            // ������� ������� � ������ $csvKey �� ������� ������ ����������
            unset($csvArray[$csvKey]);

        }
        else {
            // ��������� ������������� ��������� "��� � �������" � ����������� �����
            if($_deactivate) {
                setProductQuantity($el['ID'], false);
                $countProductOff++;
                echo '<div class="warning">����������� "��� � �������"! </div>';
            }
        }
    endwhile;
    if($countProductWithArt) {
        echo '</div>';
    }

    // ������� ����� ������ �������
    if($changePrice)
        echo '<div class="success">�������� ���� ���: '.$changePrice.' �������</div>';
    echo '<div class="success">������� ����� ������������� � ������ � ������� � ����������� (������ "��� � �������"): '.count($pArray).'</div>';
    // ����� ���������� ����������������� ������
    if($_deactivate && $countProductOff) {
        echo '<div class="warning">������� ��������������: '.$countProductOff.'</div>';
    }
    else {
        echo '<div class="error">����������� ���������!!</div>';
    }

    echo '<div class="success">������� � ������, ������� � ���������� �� ����: '.count($csvArray).'</div>';
    if($request->get('create') && count($csvArray)) { // ����� ���� � ������ �� ��� �� �����
        // ������� � ��������� ����� ������ �� ����
        if(count($csvArray) > 0) {
            $countCreateNew = addNewProducts($csvArray, $_propertyName);
        }
        if($countCreateNew) {
            echo '<div class="warning">����� ������� �������: '.$countCreateNew.'</div>';
        }
        else {
            echo '<div class="error">��� ���������� ������� �������� ������! ��������� ������ ���������.</div>';
        }
    }
    else {
        if(count($csvArray))
        echo '<div class="error">����� ������ �� ������� ��� '.count($csvArray).' ������� �� ������ ����������</div>';
    }

    //*
    //* ����� ������
    //*

    // ������������� ����� ���������� �� ������� ������� �������� ������
    // ���������� ���� � ������� ������ ����������
    function searchArtInCsv($art, $array) {
        foreach ($array as $key=>$value) {
            if (trim($value['art']) == trim($art)) return $key;
        }
        return null;
    }

    // �������� ������� ������� ������ ���������� � ������� ����� ������
    // ��������� ��������, ������ ���������, ����, ������� ����������, ������ ����������
    // ���������� ���������� ����������� �������
    function addNewProducts($arr, $prop) {
        $cnp = 0;
        $elProd = new CIBlockElement;
        foreach ($arr as $item) {
            $params = Array(
                "max_len" => "100", // �������� ���������� ��� �� 100 ��������
                "change_case" => "L", // ����� ������������� � ������� ��������
                "replace_space" => "_", // ������ ������� �� ������ �������������
                "replace_other" => "_", // ������ ����� ������� �� ������ �������������
                "delete_repeat_replace" => "true", // ������� ������������� ������ �������������
                "use_google" => "false", // ��������� ������������� google
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
                echo '<div class="success">����� � id '.$id.' ��������</div>';
            }
            else {
                echo '<div class="error">������!: '.$elProd->LAST_ERROR.' </div>';
            }

            if($id) { // ������������� ���� � �������
                $res = changeProductPrice($id, $item['price']);
                setProductQuantity($id);
                $cnp += $res;
            }


        }
        if ($cnp) return $cnp;
        return false;
    }// Function addNewProducts

    // �������� ���� ������
    function changeProductPrice($id, $price) {

        $cPrice = new CPrice;

        $res = $cPrice->SetBasePrice($id, $price, 'UAH');

        CCatalogProduct::Add(["ID" => $id, "PRICE_TYPE" => "TYPE_PRODUCT"]);
        //CCatalogProduct::Update($id, ["PRICE_TYPE" => "TYPE_PRODUCT"]);
        return $res;
    }

    // ������������� ����� "� �������"
    function setProductQuantity($id, $on = true) {
        $elUpdate = new CIBlockElement;
        if($on) {
            $elUpdate->SetPropertyValuesEx($id, 11, [ 'nalichie' => 20]);
        }
        else {
            $elUpdate->SetPropertyValuesEx($id, 11, [ 'nalichie' => 22]);
        }

        echo '<div class="warning">������� ��������� </div>';
    }


?>
