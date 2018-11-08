var AdminLTEOptions={animationSpeed:80,controlSidebarOptions:{toggleBtnSelector:"[data-toggle='control-sidebar']",selector:".control-sidebar",slide:false}};var dateToday="";(function($){$.fn.extend({limiter:function(limit,elem){$(this).on("keyup focus",function(){setCount(this,elem)});function setCount(src,elem){var chars=src.value.length;if(chars>limit){src.value=src.value.substr(0,limit);chars=limit}elem.html(limit-chars+" characters left")}setCount($(this)[0],elem)}})})(jQuery);$(document).delegate(".modal-link","click",function(event){event.preventDefault();var modalUrl=$(this).attr("href");var modalWrapper=$("#modal-wrapper");$(".modal-content",modalWrapper).load(modalUrl,function(){modalWrapper.modal("show");modalWrapper.on("shown.bs.modal",function(){modalWrapper.find(".modal-body input:first").focus();setUpSelectMenus()})});return false});$(document).delegate(".note-delete","click",function(event){event.preventDefault();deleteNote($(this).attr("data-id"));return false});$(document).ready(function(){dateToday=moment().format("ddd MMM D YYYY");$("#data-table").DataTable({pageLength:50,ordering:false});$(".tab-table").DataTable({pageLength:25,ordering:true});setUpSelectMenus();$(".content").on("click","#show-filters",function(){$("#primary-filter").fadeIn(500);$("#show-filters").fadeOut(500);setUpSelectMenus()});$('[data-toggle="tabajax"]').click(function(e){var $this=$(this),loadurl=$this.attr("href"),targ=$this.attr("data-target");$.get(loadurl,function(data){$(targ).html(data)});$this.tab("show");return false})});function setUpSelectMenus(){$("select").not(".ajax").not(".child select").select2({minimumResultsForSearch:10});$(".contact-add").select2({ajax:{url:selectContactPath,dataType:"json",delay:250,data:function(params){return{q:params.term}},processResults:function(data){return{results:$.map(data,function(item){return{text:item.text,id:item.id}})}}},minimumInputLength:2});$(".inventory-add").select2({ajax:{url:selectInventoryItemPath,dataType:"json",delay:250,data:function(params){return{q:params.term}},processResults:function(data){return{results:$.map(data,function(item){return{text:item.text,id:item.id}})}}},minimumInputLength:2});var singleDatePickerField=$(".single-date-picker");var dateField=$("#"+singleDatePickerField.attr("id")+"_data");dateField.val(moment().format("YYYY-MM-DD"));if(singleDatePickerField.length>0){singleDatePickerField.dateRangePicker({format:"ddd MMM D YYYY",autoClose:true,singleDate:true,singleMonth:true,showShortcuts:false,setValue:function(s){if(!$(this).attr("readonly")&&!$(this).is(":disabled")&&s!=$(this).val()){$(this).val(s)}}}).bind("datepicker-change",function(event,obj){var dateChosen=moment(obj.date1).format("YYYY-MM-DD");dateField.val(dateChosen)});singleDatePickerField.data("dateRangePicker").setDateRange(dateToday,dateToday)}}function disableButton(button){button.attr("disabled",true);console.log("...")}function deleteNote(id){if(window.confirm("Delete this note?")){$.get(noteDeletePath,{id:id,entity:"Note"},function(data){if(data=="OK"){$("#note-"+id).remove()}else{alert("Sorry! Couldn't delete ... "+data)}console.log(data)},"json")}}function debounce(func,wait,immediate){var timeout;return function(){var context=this,args=arguments;var later=function(){timeout=null;if(!immediate)func.apply(context,args)};var callNow=immediate&&!timeout;clearTimeout(timeout);timeout=setTimeout(later,wait);if(callNow)func.apply(context,args)}}$(".modal-content").delegate(".modal-submit","click",function(event){event.preventDefault();var modalForm=$(".modal-body form");var errors=false;modalForm.find("input, select").each(function(){if($(this).prop("required")==true&&errors==false){if(!$(this).val()){if($(this).attr("data-name")==undefined){alert("Please fill out all required fields."+$(this).attr("id"))}else{alert("Please fill out the "+$(this).attr("data-name")+" field")}errors=true}}});if(errors==false){modalForm.submit();waitButton($(this))}});function waitButton(obj){obj.removeClass("bg-green").addClass("btn-default").attr("disabled",true).before('<img src="/images/ajax-loader.gif" style="padding-right: 10px">')}if(stripePublicApiKey){var handler=StripeCheckout.configure({key:stripePublicApiKey,locale:"auto",token:function(token){$(".stripe-token").val(token.id);$(".modal-body form").submit();waitButton($(".payment-submit"))}})}$(".modal-content").delegate(".payment-submit","click",function(e){if($(".payment-method").val()==stripePaymentMethodId&&$(".payment-method").val()&&!$(".stripe-card-id").val()){handler.open({name:"Lend Engine",description:"Payment",zipCode:false,currency:"gbp",allowRememberMe:false,email:$(".contact-email").val(),amount:$(".payment-amount").val()*100});e.preventDefault()}else{$(".modal-body form").submit();waitButton($(".payment-submit"))}});$(window).on("popstate",function(){if(handler!=undefined){handler.close()}});function setCard(cardId){var selectedCard=$("#"+cardId);$(".creditCard").removeClass("active");$(".card-select").html("Use this card");selectedCard.addClass("active");selectedCard.find(".card-select").html("This card will be used.");$(".stripe-card-id").val(cardId);$(".payment-method").val(stripePaymentMethodId);setUpSelectMenus()}function showTakePaymentFields(){$("#payment-fields").show();$(".no-payment-needed").hide();setUpSelectMenus()}