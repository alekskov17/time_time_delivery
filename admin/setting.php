<?php define('ADMIN_MODULE_NAME', 'krayt.timedelivery');
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\Directory;
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php';
CModule::IncludeModule("main");
CModule::IncludeModule("krayt.timedelivery");
\Bitrix\Main\UI\Extension::load("ui.vue");
\Bitrix\Main\UI\Extension::load("ui.buttons");
\Bitrix\Main\UI\Extension::load("ui.buttons.icons");
\Bitrix\Main\UI\Extension::load("ui.notification");

CUtil::InitJSCore(array('krayt_timedelivery'));
\CJSCore::init("color_picker");

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
$id_prop = $request->get('id_prop');

if($request->isAjaxRequest() && $request->getPost('action') && $id_prop)
{

    $APPLICATION->RestartBuffer();
    $action = $request->getPost('action');
    if($action == 'save-setting')
    {
        $settingObj = CKrayTimeDelivery::getObjSetting();
        $setting = $settingObj::getList([
            'filter' => ["UF_ID_PROP" => $id_prop]
        ])->fetch();


        $data = $request->getPost('data');

        $arJsonData = \Bitrix\Main\Web\Json::encode($data);
        $arF = [
                "UF_ID_PROP" => $id_prop,
                "UF_ACTIVE" => $data['UF_ACTIVE'] == 'true'?1:0,
                "UF_SETTING" => $arJsonData
        ];
        if($setting)
        {
           $res = $settingObj::update($setting['ID'],$arF);

        }else{
            $res = $settingObj::add($arF);
        }
        if($res->isSuccess())
        {
            echo \Bitrix\Main\Web\Json::encode([
                'ok' => 1
            ]);
        }else{
            echo \Bitrix\Main\Web\Json::encode([
                'error' => $res->getErrorMessages()
            ]);
        }
    }
    die();
}


$strSetting  = '';
if($id_prop)
{
    $settingObj = CKrayTimeDelivery::getObjSetting();
    $setting = $settingObj::getList([
            'filter' => ["UF_ID_PROP" => $id_prop]
    ])->fetch();
    if($setting['UF_SETTING'])
    {
        $strSetting = $setting['UF_SETTING'];
    }
}


require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php';
?>
<div id="k_setting_time" class="k-row">
    <div class="k-col kc3">
        <form action="">
            <div class="k-item-form">
                <label for=""><?=Loc::getMessage('K_L_ACTIVE')?>:</label>
                <input type="checkbox" name="UF_ACTIVE" value="1" v-model="UF_ACTIVE">
            </div>
            <div class="k-item-form">
                <label for=""><?=Loc::getMessage('K_L_TITLE')?>:</label>
                <input type="text" v-model="table.title" name="title"> <bx-color-box v-model="table.title_color"></bx-color-box>
            </div>
            <div class="k-item-form">
                <label for=""><?=Loc::getMessage('K_L_DESC')?>:</label>
                <textarea style="width: 80%" v-model="table.desc"></textarea>
                <bx-color-box v-model="table.desc_color"></bx-color-box>
            </div>
              <div class="k-item-form">
                  <label for=""><?=Loc::getMessage('K_L_CNT_DAY')?>:</label>
                  <select v-model.number="table.col" name="cnt_day">
                      <?for($i=1;$i<15;$i++):?>
                        <option value="<?=$i?>"><?=$i?></option>
                      <?endfor;?>
                  </select>
              </div>
             <div class="k-item-form">
                <label for=""><?=Loc::getMessage('K_L_CNT_MIN')?>:</label>
                <input v-mask="'####'" type="text" v-model.number="table.block_time" name="block_time">
            </div>
            <div class="k-wrp-time">
                <div class="k-time-title"><?=Loc::getMessage('K_L_SHEDULE')?></div>
                <div class="k-time-row" v-for="(t,k) in times">
                    <input v-mask="'##:##'" class="k-time-input" v-model="t.from" type="text"> - <input v-mask="'##:##'" class="k-time-input" v-model="t.to" type="text">
                    <button class="ui-btn ui-btn-xs ui-btn-icon-remove" type="button" v-on:click="delTime(k)" v-if="k > 0"></button>
                </div>
                <div class="k-item-form">
                    <button class="ui-btn ui-btn-success-light ui-btn-sm ui-btn-icon-add" v-on:click="addTime" type="button"><?=Loc::getMessage('K_L_BTN_ADD_ROW')?></button>
                </div>
            </div>
            <div>
                <button class="ui-btn ui-btn-success" v-on:click="save" type="button"><?=Loc::getMessage('K_L_BTN_SAVE')?></button>
                <button class="ui-btn" v-on:click="close" type="button"><?=Loc::getMessage('K_L_BTN_CANCEL')?></button>
            </div>
        </form>
    </div>
    <div class="k-col kc7">
        <h3 v-bind:style="{ color: table.title_color}" class="k-title-table">{{table.title}}</h3>
        <table cellpadding="2" cellspacing="8px" class="k-table">
            <tr>
                <th v-for="n in table.col">
                    {{getDate(n)}}<br>
                    <small>{{getDateFull(n)}}</small>
                </th>
            </tr>
            <tr v-for="t in times">
                <td v-bind:class="{block:isBlockTime(n,t.from)}" v-for="n in table.col">{{t.from}} - {{t.to}}</td>
            </tr>
        </table>
        <div v-bind:style="{ color: table.desc_color}" class="k-table-desc">
            {{table.desc}}
        </div>
        <div class="wrp-color-settings">
            <div>Настройки цвета таблицы</div>
            <div>
                Фона: <bx-color-box v-model="table.desc_color"></bx-color-box>
            </div>
            <div>
                Рамка таблицы: <bx-color-box v-model="table.desc_color"></bx-color-box>
            </div>
            <div>
                День недели: <bx-color-box v-model="table.desc_color"></bx-color-box>
            </div>
            <div>
                Дата: <bx-color-box v-model="table.desc_color"></bx-color-box>
            </div>
            <div>
                Время: <bx-color-box v-model="table.desc_color"></bx-color-box>
            </div>
        </div>
    </div>
</div>
<script>
    var dataDef = {
        UF_ACTIVE:false,
        table:{
            col:7,
            block_time: 30,
            title_color: '#000',
            custom_css:[]
        },
        times:[
            {
                from:'07:00',
                to:'09:00'
            }
        ]
    };
    function CustomCssGenerate(data_ar) {
        var cssDom =  BX('custom_css');
        if(Array.isArray(data_ar))
        {
            data_ar.forEach(function () {

            });
        }
    };
    var dataSetting = '<?=$strSetting?>';
    if(dataSetting)
    {
        var newData = BX.parseJSON(dataSetting);
        if(newData)
        {
            dataDef.table.col = Number(newData.day);
            dataDef.times = newData.times;
            dataDef.table.title = newData.title;
            dataDef.table.desc = newData.desc;
            dataDef.table.block_time = Number(newData.block_time);
            dataDef.UF_ACTIVE = newData.UF_ACTIVE == 'true'?true:false;
        }
    }
    var color_title = BX("color_title");

    BX.Vue.component('bx-color-box', {
        prop: ['value'],
        data:function () {
          return {
              color:this.value,
              content: this.value
          }
        },
        template: '<input @input="handleInput" readonly v-bind:style="{ backgroundColor: color}" v-on:click="openColor" class="color_box">',
        mounted:function(){
            var self = this;
            this.picker = new BX.ColorPicker({
                bindElement: this.$el,
                onColorSelected: function(color, picker) {
                    self.color = color;
                    self.$emit('input', color)
                },
                popupOptions: {
                    offsetTop: 10,
                    offsetLeft: 10
                }
            });
        },
        methods:{
            openColor: function () {
                this.picker.open();
            },
            handleInput: function(e) {
                this.$emit('input', this.content)
            }
        }
    });
    BX.Vue.use(VueTheMask);
    BX.Vue.create({
        el: '#k_setting_time',
        data:dataDef,
        methods:{
            addTime: function () {
                var last = this.times[this.times.length - 1];
                this.times.push({
                    from:last.to,
                    to:''
                })
            },
            delTime:function (k) {
                this.times.splice(k, 1);
            },
            save:function () {

                var data = {
                    day:this.table.col,
                    times:this.times,
                    UF_ACTIVE:this.UF_ACTIVE,
                    block_time:this.table.block_time,
                    title:this.table.title,
                    desc:this.table.desc
                };
                BX.showWait('k_setting_time');
                BX.ajax.post(
                    location.href,
                    {
                        data:data,
                        action:'save-setting'
                    },
                    function (data) {

                        var json = BX.parseJSON(data);
                        if(json)
                        {
                            if(json.ok)
                            {
                                BX.UI.Notification.Center.notify({
                                    position:'top-left',
                                    autoHideDelay: 5000,
                                    content: "<?=Loc::getMessage('K_L_MSG_OK')?>"
                                });
                            }
                            if(json.error)
                            {

                            }
                        }else{
                            alert(data);
                        }
                        BX.closeWait();
                    }
                );

            },
            getDate:function (day) {
                day = day-1;
                var current = new Date();
                if(day == 0)
                {
                    return BX.date.format('today', current);
                }else{
                    current.setDate(current.getDate() + day);
                    return BX.date.format('l', current);
                }
            },
            getDateFull:function (day) {
                day = day-1;
                var current = new Date();
                if(day > 0)
                {
                    current.setDate(current.getDate() + day);
                }
                return BX.date.format('d-m-Y', current);
            },
            isBlockTime:function (day,to) {
                day = day-1;
                var current = new Date();
                var cDate = new Date();
                if(to)
                {
                    if(day > 0)
                    {
                        current.setDate(current.getDate() + day);
                    }
                    var arTS = to.split(':');
                    if(arTS.length == 2)
                    {
                        current.setHours(arTS[0]);
                        current.setMinutes(arTS[1]);

                        if(this.table.block_time > 0)
                        {
                            current.setMinutes(current.getMinutes() - this.table.block_time);
                        }

                        if(cDate.getTime() >= current.getTime())
                        {
                            return true;
                        }
                    }
                }
                return false;
            },
            selectColorTitle:function () {
                picker.open();
            },
            close: function () {
                BX.SidePanel.Instance.close();
            }
        }
    });
</script>
<style>
    #k_setting_time{
        background-color: #fff;
        padding: 20px;
    }
    .k-row{
        display: block;
    }
    .k-row:after{
        content: '';
        display: block;
        clear: both;
    }
    .k-col{
        float: left;
    }
    .kc3{
        width: 30%;

    }
    .kc7{
        width: 70%;
    }
    .k-table th{
        text-transform: capitalize;
    }
    .k-table th small{
        color: #999;
    }
    .k-table td{
        border: 1px solid #ddd;
        padding: 10px;
        text-align: center;
    }
    .k-table td.block{
        opacity: 0.6;
    }
    .k-item-form{
        margin-bottom: 10px;
    }
    .k-item-form label{
        display: block;
        margin-bottom: 5px;
    }
    .k-time-title{
        margin-bottom: 10px;
    }
    .k-time-row{
        margin-bottom: 5px;
    }
    .k-time-input{
        max-width: 20%;
    }
    .k-title-table{
        text-align: left;
    }
    .k-table-desc{
        font-size: 14px;
        margin-top: 10px;
    }
    .color_box{
        display: inline-block;
        width: 25px;
        height: 25px;
        border: 1px solid #959ea9;
        -webkit-border-radius:3px;
        -moz-border-radius:3px;
        border-radius:3px;
        vertical-align: bottom;
        cursor: pointer;
        background-color: #000;
        opacity: 1 !important;
        color: transparent;
    }
</style>
<style id="custom_css"></style>
<?require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php';