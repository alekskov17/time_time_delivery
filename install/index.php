<? defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Highloadblock as HL;

Loc::loadMessages(__file__);

if (class_exists('krayt_timedelivery')) {
    return;
}

class krayt_timedelivery extends CModule
{
    const MODULE_ID = "krayt.timedelivery";
    var $MODULE_ID = "krayt.timedelivery";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $MODULE_GROUP_RIGHTS = "Y";

    function __construct()
    {
        $arModuleVersion = array();

        $path = str_replace("\\", "/", __file__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include ($path . "/version.php");

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        $this->MODULE_NAME = Loc::getMessage("SCOM_INSTALL_NAME_krayt.timedelivery");
        $this->MODULE_DESCRIPTION = Loc::getMessage("SCOM_INSTALL_DESCRIPTION_krayt.timedelivery");
        $this->PARTNER_NAME = Loc::getMessage("SPER_PARTNER_krayt.timedelivery");
        $this->PARTNER_URI = Loc::getMessage("PARTNER_URI_krayt.timedelivery");
    }

    function InstallDB($arParams = array())
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->registerEventHandler("sale","OnSaleComponentOrderJsData","krayt.timedelivery","CKrayTimeDelivery","OnSaleComponentOrderJsData");
        $eventManager->registerEventHandler("main","OnBuildGlobalMenu","krayt.timedelivery","CKrayTimeDelivery","OnBuildGlobalMenu");

        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".self::MODULE_ID."/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);
        CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".self::MODULE_ID."/install/admin", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin", true, true);

        if (CModule::IncludeModule("highloadblock"))
        {
            $this->installHL();
        }
        //#SET_MORE#
        return true;
    }

     function UnInstallDB($arParams = array())
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->unRegisterEventHandler("sale","OnSaleComponentOrderJsData","krayt.timedelivery","CKrayTimeDelivery","OnSaleComponentOrderJsData");
        $eventManager->unRegisterEventHandler("main","OnBuildGlobalMenu","krayt.timedelivery","CKrayTimeDelivery","OnBuildGlobalMenu");

        return true;
    }

    function InstallFiles($arParams = array())
    {
        return true;
    }
     function UnInstallFiles()
    {
        \Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".self::MODULE_ID);//удалилить мастер установки
        return true;
    }

    function DoInstall()
    {
        $this->InstallFiles();
        $this->InstallDB();
        RegisterModule(self::MODULE_ID);
    }

    function DoUninstall()
    {
        global $APPLICATION;
        UnRegisterModule(self::MODULE_ID);
        $this->UnInstallDB();
        $this->UnInstallFiles();
    }
    function installHL()
    {
        $jsonStr = '{"15":{"ID":"15","NAME":"KTimeSetting","TABLE_NAME":"k_time_setting","FIELD":[{"ID":"135","ENTITY_ID":"HLBLOCK_15","FIELD_NAME":"UF_ID_PROP","USER_TYPE_ID":"integer","XML_ID":"UF_ID_PROP","SORT":"100","MULTIPLE":"N","MANDATORY":"N","SHOW_FILTER":"N","SHOW_IN_LIST":"Y","EDIT_IN_LIST":"Y","IS_SEARCHABLE":"N","SETTINGS":{"SIZE":20,"MIN_VALUE":0,"MAX_VALUE":0,"DEFAULT_VALUE":""},"EDIT_FORM_LABEL":"UF_ID_PROP","LIST_COLUMN_LABEL":"UF_ID_PROP","LIST_FILTER_LABEL":"UF_ID_PROP","ERROR_MESSAGE":"","HELP_MESSAGE":""},{"ID":"136","ENTITY_ID":"HLBLOCK_15","FIELD_NAME":"UF_SETTING","USER_TYPE_ID":"string","XML_ID":"UF_SETTING","SORT":"100","MULTIPLE":"N","MANDATORY":"N","SHOW_FILTER":"N","SHOW_IN_LIST":"Y","EDIT_IN_LIST":"Y","IS_SEARCHABLE":"N","SETTINGS":{"SIZE":20,"ROWS":1,"REGEXP":"","MIN_LENGTH":0,"MAX_LENGTH":0,"DEFAULT_VALUE":""},"EDIT_FORM_LABEL":"UF_SETTING","LIST_COLUMN_LABEL":"UF_SETTING","LIST_FILTER_LABEL":"UF_SETTING","ERROR_MESSAGE":"","HELP_MESSAGE":""},{"ID":"137","ENTITY_ID":"HLBLOCK_15","FIELD_NAME":"UF_ACTIVE","USER_TYPE_ID":"boolean","XML_ID":"UF_ACTIVE","SORT":"100","MULTIPLE":"N","MANDATORY":"N","SHOW_FILTER":"N","SHOW_IN_LIST":"Y","EDIT_IN_LIST":"Y","IS_SEARCHABLE":"N","SETTINGS":{"DEFAULT_VALUE":"1","DISPLAY":"CHECKBOX","LABEL":["",""],"LABEL_CHECKBOX":""},"EDIT_FORM_LABEL":"UF_ACTIVE","LIST_COLUMN_LABEL":"UF_ACTIVE","LIST_FILTER_LABEL":"UF_ACTIVE","ERROR_MESSAGE":"","HELP_MESSAGE":""}]}}';

        $jsonHl = \Bitrix\Main\Web\Json::decode($jsonStr);
        foreach($jsonHl as $key=>$hl)
        {
            $dbHblock = HL\HighloadBlockTable::getList(
                array(
                    "filter" => array("NAME" => $hl['NAME'])
                ));

            if (!$dbHblock->Fetch())
            {
                $data = array(
                    'NAME' => $hl['NAME'],
                    'TABLE_NAME' => $hl['TABLE_NAME'],
                );
                $result = HL\HighloadBlockTable::add($data);
                $ID = $result->getId();

                $hldata = HL\HighloadBlockTable::getById($ID)->fetch();
                $hlentity = HL\HighloadBlockTable::compileEntity($hldata);

                if(isset($hl['FIELD']) && count($hl['FIELD']))
                {
                    $obUserField  = new CUserTypeEntity;
                    foreach($hl['FIELD'] as $hlFeild)
                    {
                        $arUserFields = array (
                            'ENTITY_ID' => "HLBLOCK_".$ID,
                            'FIELD_NAME' => $hlFeild['FIELD_NAME'],
                            'USER_TYPE_ID' => $hlFeild['USER_TYPE_ID'],
                            'XML_ID' => $hlFeild['XML_ID'],
                            'SORT' => $hlFeild['SORT'],
                            'MULTIPLE' => $hlFeild['MULTIPLE'],
                            'MANDATORY' => $hlFeild['MANDATORY'],
                            'SHOW_FILTER' => $hlFeild['SHOW_FILTER'],
                            'SHOW_IN_LIST' => $hlFeild['SHOW_IN_LIST'],
                            'EDIT_IN_LIST' => $hlFeild['EDIT_IN_LIST'],
                            'IS_SEARCHABLE' => $hlFeild['IS_SEARCHABLE'],
                            "EDIT_FORM_LABEL" =>  $hlFeild['EDIT_FORM_LABEL'],
                            "LIST_COLUMN_LABEL" => $hlFeild['LIST_COLUMN_LABEL'],
                            "LIST_FILTER_LABEL" => $hlFeild['LIST_FILTER_LABEL']
                        );
                        $ID_USER_FIELD = $obUserField->Add($arUserFields);
                    }

                }
            }
        }
    }
}
?>