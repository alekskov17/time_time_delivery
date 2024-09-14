<?php define('ADMIN_MODULE_NAME', 'krayt.timedelivery');
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\Directory;
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php';
CModule::IncludeModule("main");
CModule::IncludeModule("krayt.timedelivery");
CModule::IncludeModule("sale");


global $APPLICATION, $USER, $USER_FIELD_MANAGER;

$ee = $APPLICATION->GetUserRight("krayt.timedelivery");

Loc::loadMessages(__FILE__);

if (!$USER->IsAdmin())
{
    if($APPLICATION->GetUserRight("krayt.timedelivery") == "D")
        $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

if (!CModule::IncludeModule(ADMIN_MODULE_NAME))
{
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
$APPLICATION->SetTitle(Loc::getMessage('K_TITLE_PAGE'));

$request = Application::getInstance()->getContext()->getRequest();
$server = Application::getInstance()->getContext()->getServer();
$grid_options = new Bitrix\Main\Grid\Options('report_list');
$nav_params = $grid_options->GetNavParams();

$nav = new Bitrix\Main\UI\PageNavigation('report_list');
$nav->allowAllRecords(true)
    ->setPageSize($nav_params['nPageSize'])
    ->initFromUri();

$order = array(
    'ID' => 'desc'
);

$filter = [
    'TYPE' => 'STRING'
];

$filterOption = new Bitrix\Main\UI\Filter\Options('report_list');
$filterData = $filterOption->getFilter([]);

$arFieldF = array(
    'ACTIVE', 'PERSON_TYPE_LID','NAME','FIND','PERSON_TYPE_ID'
);

foreach ($filterData as $k => $v) {
    if(in_array($k,$arFieldF))
    {
        if($k == 'PERSON_TYPE_LID')
        {
            $k =  'PERSON_TYPE.LID';
        }
        if($k == 'FIND')
        {
            $k =  'NAME';
        }
        if($v)
        $filter[$k] = $v;
    }
}
if($request->get('by') && $request->get('order'))
{
    $order = array(
        $request->get('by') => $request->get('order')
    );
}
$resProps = \Bitrix\Sale\Internals\OrderPropsTable::getList([
    'filter' => $filter,
    'select' => [
            "ID",
            "NAME",
            "ACTIVE",
            "SORT",
            "REQUIRED",
            "PERSON_TYPE_NAME" => 'PERSON_TYPE.NAME',
            "PERSON_TYPE_LID" => "PERSON_TYPE.LID"
    ],
    'order' => $order,
    'count_total' => true,
    'offset' => $nav->getOffset(),
    'limit' => $nav->getLimit(),
]);
$nav->setRecordCount($resProps->getCount());
$list = [];

$settingObj = CKrayTimeDelivery::getObjSetting();

while($l = $resProps->fetch())
{
    $l['PERSON_TYPE_NAME'] = $l['PERSON_TYPE_NAME']."(".$l['PERSON_TYPE_LID'].")";
    $setting = $settingObj::getList([
            'filter' => ['UF_ID_PROP' => $l['ID']]
    ])->fetch();

    $l['IS_TIME_DELIVERY'] = 'N';

    if($setting && $setting['UF_ACTIVE'] == 1)
    {
        $l['IS_TIME_DELIVERY'] = 'Y';
    }

    $l['ACTIVE'] = Loc::getMessage('K_ACTIVE_'.$l['ACTIVE']);
    $l['REQUIRED'] = Loc::getMessage('K_ACTIVE_'.$l['REQUIRED']);
    $l['IS_TIME_DELIVERY'] = Loc::getMessage('K_ACTIVE_'.$l['IS_TIME_DELIVERY']);

    $list[] = array(
        'data' => $l,
        'actions' => array(
            array(
                'text'    => Loc::getMessage('K_SETTING'),
                'onclick' => "KOpenSettingWin({$l['ID']})"
            )
        )
    );
}
$arLid = [];
$arLidDb = \Bitrix\Main\SiteTable::getList();
while ($lid = $arLidDb->fetch())
{
    $arLid[$lid['LID']] = $lid['LID'];
}
$arPersonType = [];

$arPersonTypeR = Bitrix\Sale\Internals\PersonTypeTable::getList();
while ($perT = $arPersonTypeR->fetch())
{
    $arPersonType[$perT['ID']] =  $perT['NAME'].'('.$perT['LID'].")";
}

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php';
?>
<?
$APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
    'FILTER_ID' => 'report_list',
    'GRID_ID' => 'report_list',
    'FILTER' => [
        ['id' => 'ACTIVE', 'name' => 'Активность', 'type' => 'list',
            'items' => ['Y' => 'Да','N' => "Нет"]
        ],
        ['id' => 'PERSON_TYPE_LID', 'name' => 'Сайт', 'type' => 'list', 'items' => $arLid],
        ['id' => 'PERSON_TYPE_ID', 'name' => 'Тип плательщика', 'type' => 'list', 'items' => $arPersonType],
        ['id' => 'NAME', 'name' => 'Название'],
    ],
    'ENABLE_LIVE_SEARCH' => true,
    'ENABLE_LABEL' => true
]);
?>
<?
$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
    'GRID_ID' => 'report_list',
    'COLUMNS' => [
        ['id' => 'ID', 'name' => 'ID', 'sort' => 'ID', 'default' => true],
        ['id' => 'PERSON_TYPE_NAME', 'name' => Loc::getMessage('K_TYPE_PAY'),'default' => true],
        ['id' => 'NAME', 'name' => Loc::getMessage('K_NAME_F'),'default' => true],
        ['id' => 'ACTIVE', 'name' => Loc::getMessage('K_ACTIVE_F'),'default' => true],
        ['id' => 'SORT', 'name' => Loc::getMessage('K_SORT_F'),'default' => true],
        ['id' => 'REQUIRED', 'name' =>  Loc::getMessage('K_REQUIRED_F'),'default' => true],
        ['id' => 'IS_TIME_DELIVERY', 'name' => Loc::getMessage('K_TIME_D_F'),'default' => true]

    ],
    'ROWS' => $list, //Самое интересное, опишем ниже
    'SHOW_ROW_CHECKBOXES' => false,
    'NAV_OBJECT' => $nav,
    'PAGE_SIZES' => [
        ['NAME' => "5", 'VALUE' => '5'],
        ['NAME' => '10', 'VALUE' => '10'],
        ['NAME' => '20', 'VALUE' => '20'],
        ['NAME' => '50', 'VALUE' => '50'],
        ['NAME' => '100', 'VALUE' => '100']
    ],
    'AJAX_MODE' => 'Y',
    'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
    'AJAX_OPTION_JUMP'          => 'N',
    'SHOW_CHECK_ALL_CHECKBOXES' => false,
    'SHOW_ROW_ACTIONS_MENU'     => true,
    'SHOW_GRID_SETTINGS_MENU'   => true,
    'SHOW_NAVIGATION_PANEL'     => true,
    'SHOW_PAGINATION'           => true,
    'SHOW_SELECTED_COUNTER'     => true,
    'SHOW_TOTAL_COUNTER'        => true,
    'SHOW_PAGESIZE'             => true,
    'SHOW_ACTION_PANEL'         => true,
    'ACTION_PANEL'              => [

    ],
    'ALLOW_COLUMNS_SORT'        => true,
    'ALLOW_COLUMNS_RESIZE'      => true,
    'ALLOW_HORIZONTAL_SCROLL'   => true,
    'ALLOW_SORT'                => true,
    'ALLOW_PIN_HEADER'          => true,
    'AJAX_OPTION_HISTORY'       => 'N'
]);
?>
<script>
    function KOpenSettingWin(id_prop){
        var Dictionary = new BX.SidePanel.Dictionary();
        BX.SidePanel.Instance.open(
            '/bitrix/admin/k_setting_timedelivery.php?lang=<?=LANGUAGE_ID?>&id_prop='+id_prop,
            {
                allowChangeHistory:false,
                events:{
                    onClose:function () {
                        BX.Main.gridManager.reload('report_list');
                    }
                }
            }
        );
    }
</script>
<?require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php';
