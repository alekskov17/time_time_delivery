var KTimeDelivery = {

    clearInput:function (item) {
        if(item)
        BX.adjust(item,{
            props:{
                autocomplete:'off'
            }
        });
    },
    createModalTable: function (prop,item) {
      var modal =  BX('kt_wrp_modal');
        if(!modal)
      {
          var jsonTable = prop.UF_SETTING;
          var table;
          if(jsonTable)
          {
              table = KTimeDelivery.createShedule(jsonTable,item);
              var title,desc;
              if(jsonTable.title)
              {
                  title = BX.create('h4', {props:{
                            className:'kt_wrp_modal_title',
                        },
                      text:jsonTable.title
                  });
              }
              if(jsonTable.desc)
              {
                  desc = BX.create('div', {props:{
                      className:'kt_wrp_modal_desc',
                  },
                      text:jsonTable.desc
                  });
              }
          }
          modal = BX.create('div', {props:{
              id:'kt_wrp_modal',
              className:'kt_wrp_modal open'},
              children:[BX.create('div',{
                  props:{
                      className:'kt_modal_content'
                  },
                  children:[
                      title,
                      BX.create('a',{
                          props:{
                              className:'kt_modal_close'
                          },
                          html:'&times;',
                          events:{
                              click:function () {
                                  BX.removeClass(modal,'open');
                              }
                          }
                      }),
                      table,
                      desc
                  ]
              })]
          });
          //;
          BX.insertAfter(modal, BX.lastChild(document.body));
      }else{
            BX.addClass(modal,'open');
        }
    },
    createShedule:function (json,input) {

        if(json.times && Array.isArray(json.times))
        {
            let table = BX.create('div',{
                props:{
                    className:'kt_table'
                }});

            for(let i=1; i <= json.day; i++)
            {
                let day = i-1;
                var current = new Date();
                var cDate = new Date();
                var date = '';
                var dateFull = '';
                if(day == 0)
                {
                    date = BX.date.format('today', current);
                }else{
                    current.setDate(current.getDate() + day);
                    date = BX.date.format('l', current);
                }
                dateFull = BX.date.format('d.m.Y', current);
                let col = BX.create('div',{
                    props:{
                        className:'kt_table_col'
                    },
                    children:[
                        BX.create('div',{
                            props:{
                                className:'kt_table_title'
                            },
                            html:"<div>"+date+"</div><small>"+dateFull+"</small>"
                        })
                    ]
                });
                var curentB = Object.assign(current);
                json.times.forEach(function (item,key) {

                    var arTS = item.from.split(':');
                    var block = '';
                    if(arTS.length == 2)
                    {
                        curentB.setHours(arTS[0]);
                        curentB.setMinutes(arTS[1]);

                        if(Number(json.block_time) > 0)
                        {
                            curentB.setMinutes(curentB.getMinutes() - Number(json.block_time));
                        }

                        if(cDate.getTime() >= curentB.getTime())
                        {
                            block = 'block';
                        }
                    }

                    col.appendChild(BX.create('div',{
                        text:item.from+' - '+item.to,
                        props:{
                            className:'kt_table_row '+block
                        },
                        dataset:{
                            date:item.from+' - '+item.to+", "+dateFull
                        },
                        events:{
                            click:function () {
                                if(!BX.hasClass(this,'block'))
                                {
                                    input.value = this.dataset.date;
                                    BX.removeClass(BX('kt_wrp_modal'),'open');
                                }
                            }
                        }
                    }))
                });

                table.appendChild(col);
            }
          return  table;
        }
       // console.log(json);
    }
};
BX.ready(function(){

    var dataSetting = BX.message('k_time_setting');
    var wrp = BX('bx-soa-properties');

    /*BX.addCustomEvent('onAjaxSuccess', function(data){
        if(data['order'])
        {

        }
    });*/

   for(var ID in dataSetting)
   {
       var pObj = dataSetting[ID];
       var nameP = 'ORDER_PROP_'+ID;

       BX.bindDelegate(
           wrp,
           'click',
           {
               attribute: {
                   name:nameP
               }
           },
           function(e)
           {
               KTimeDelivery.createModalTable(pObj,this);
           }
       );
       BX.bindDelegate(
           wrp,
           'keydown',
           {
               attribute: {
                   name:nameP
               }
           },
           function(e)
           {
               e.preventDefault();
               return false;
           }
       );BX.bindDelegate(
           wrp,
           'mouseover',
           {
               attribute: {
                   name:nameP
               }
           },
           function(e)
           {
               KTimeDelivery.clearInput(this);
           }
       );
   }

});
