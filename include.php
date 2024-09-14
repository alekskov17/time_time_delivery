<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;
use Bitrix\Main\Application;
use Bitrix\Main\Page\Asset;

\CJSCore::RegisterExt('krayt_timedelivery', [
    'js' => '/bitrix/js/krayt.timedelivery/main.js'
]);
\CJSCore::RegisterExt('kTimeType', [
    'js' => '/bitrix/js/krayt.timedelivery/kTimeType.js',
    'css' => '/bitrix/js/krayt.timedelivery/kTimeType.css',
]);

Class CKrayTimeDelivery
{
    /**
     *
     * @return \Bitrix\Main\Entity\DataManager
     */
    static function getObjSetting()
    {
        \Bitrix\Main\Loader::includeModule('highloadblock');
        $hlblock =  Bitrix\Highloadblock\HighloadBlockTable::getList([
            'filter' => ['TABLE_NAME' => 'k_time_setting']
        ])->fetch();
        $entity =  Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
        return $entity->getDataClass();
    }
    static function getAllSettingProp()
    {
        $resObj = self::getObjSetting();
        $dataR = $resObj::getList([
            'filter' => [
                "UF_ACTIVE" => 1
            ]
        ]);
        $arData = [];
        while ($d = $dataR->fetch())
        {
            $d['UF_SETTING'] = \Bitrix\Main\Web\Json::decode($d['UF_SETTING']);
            $arData[$d['UF_ID_PROP']] = $d;
        }
        return $arData;
    }
    static function eventUpdateProp(Entity\Event $event)
    {
        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();
        $primary = $event->getParameter('primary');
        $fields = $event->getParameter('fields');

        if($fields['TYPE'] == self::$typeF && $primary['ID'])
        {
            $setting = $request->getPost('k_setting_time');

            $obData = self::getObjSetting();

            $row = $obData::getList([
                'filter' => ['UF_ID_PROP' => $primary['ID']]
            ])->fetch();
            if($row)
            {
                $obData::update($row['ID'],[
                    'UF_ID_PROP' => $primary['ID'],
                    "UF_SETTING" => $setting
                ]);
            }else{
                $obData::add([
                    'UF_ID_PROP' => $primary['ID'],
                    "UF_SETTING" => $setting
                ]);
            }
        }
    }
    static function eventDeleteProp($Id)
    {
        if($Id)
        {
            $obData = self::getObjSetting();
            $row = $obData::getList([
                'filter' => ['UF_ID_PROP' => $Id]
            ])->fetch();
            if($row)
            {
                $obData::delete($row['ID']);
            }
        }
        return true;
    }
    static function getSettingProp($id_prop)
    {
        if($id_prop)
        {
            $obData = self::getObjSetting();
            $row = $obData::getList([
                'filter' => ['UF_ID_PROP' => $id_prop]
            ])->fetch();

            if($row)
            {
                return stripcslashes($row['UF_SETTING']);
            }
        }
    }

    static function OnSaleComponentOrderJsData(&$arResult,&$arParams)
    {
        $allSettingProp = self::getAllSettingProp();
        if($allSettingProp)
        {
            $ARJSMESS = [
                'k_time_setting' => $allSettingProp
            ];
            Asset::getInstance()->AddString("<script type=\"text/javascript\">BX.message(".CUtil::PhpToJSObject($ARJSMESS).")</script>");
            CUtil::InitJSCore(array('kTimeType'));
        }

    }
    function OnBuildGlobalMenu(&$aGlobalMenu, &$aModuleMenu)
    {

        if($GLOBALS['APPLICATION']->GetGroupRight("sale") < "R")
            return;

        global $USER;
        if(!$USER->IsAdmin())
            return;

        foreach($aModuleMenu as $k => $v)
        {

            if($v['parent_menu']=='global_menu_store' && is_array($aModuleMenu[$k]['items']))
            {
                foreach ($aModuleMenu[$k]['items'] as $kk=>$it)
                {

                   if($it['items_id'] == 'menu_sale_properties')
                   {
                       $aModuleMenu[$k]['items'][$kk]['items'][] = Array(
                           'text' => Loc::getMessage('K_TIME_DEV_NAME'),
                           'title' => GetMessage('K_TIME_DEV_NAME'),
                           'url' => '/bitrix/admin/k_time_delivery_list.php?lang='.LANGUAGE_ID,
                       );
                   }
                }

            }
        }
    }
}


?>
