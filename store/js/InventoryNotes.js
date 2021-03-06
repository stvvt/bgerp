var dialog;

function noteActions() {
	var ajaxForm = document.createElement("div");
	var ajaxFormHolder = document.createElement("div");

	$(ajaxFormHolder).addClass('ajaxFormHolder');
	$(ajaxForm).attr('id', 'ajax-form');
	$(ajaxForm).attr('class', 'ajax-form');
	$('body').append($(ajaxFormHolder));
	$('.ajaxFormHolder').append($(ajaxForm));

	$(document.body).on('change', ".toggle-charge", function (e) {
		var url = $(this).attr("data-url");
		var selectedUser = this.value;
		
		if(!url) return;
		var data = {userId:selectedUser};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
	});

	// При натискане на бутона за показване на форма
	$(document.body).on('click', ".inventoryNoteShowAddForm", function (e) {

		var url = $(this).attr("data-url");
		var nextelement = $(this).attr("data-nextelement");

		if (!url) return;

		// Кой ще е следващия елемент
		var data = {nextelement: nextelement};

		resObj = new Object();
		resObj['url'] = url;

		getEfae().process(resObj, data);

		dialog = $("#ajax-form").dialog({
			autoOpen: false,
			height: 350,
			width: 450,
			modal: true,
			clickOutside: true
		});

		dialog.dialog("open");
	});

	// При натискане на бутона за показване на форма
	$(document.body).on('click', ".ui-widget-overlay", function (e) {
		cancelForm();
	});

	// При натискане на бутон, когато е отворена формата
	$(document.body).on("keypress", ".inventoryNoteInsertForm", function (event) {
		if (event.keyCode == 13) {
			// При натискане на 'ENTER' не искаме да се събмитне формата по стандартния начин
			event.preventDefault();
			submitShowAddForm($(event.target).closest('form'));
		}
		if (event.keyCode == 27) {
			dialog.dialog('close');
		}
	});
}

// Затваряне на формата
function cancelForm(){
	dialog.dialog( "close" );
	$('.inventoryNoteInsertForm').remove();
}

// Събмитва формата и не отваря нова след това
function submitAndCloseForm(form) {
	submitShowAddForm(form, true);
}

// Субмитва формата за добавяне на установено количество
function submitShowAddForm(form, stop) {
	
	// Ако не е зададено изрично винаги след запис отваряме следващата форма
	if (typeof stop === "undefined" || stop === null) { 
		stop = false; 
	}
	
	var frm = $(form);
	frm.css('cursor', 'wait');
	
	var params = frm.serializeArray();
	var serialized = $.param(params);
	
	// Събмитваме формата по AJAX
	$.ajax({
		type: frm.attr('method'),
		url: frm.attr('action'),
		data: serialized + '&ajax_mode=1&Cmd[default]=1',
		dataType: 'json'
	}).done( function(data) {
		
		// При успех
		var r1 = data[0];
		var id = r1['arg']['id'];
		var html = r1['arg']['html'];
		var hide = true;
		
		// При грешка не затваряме формата
		if(typeof data[0]['arg']['hasError'] != 'undefined'){
			hide = false;
		}
		
		id = "#" + id;
		
		// Подмяна на съдържанието на обекта
		$(id).html(html);

		// Ако има втори обект за подмяна на съдържанието му
		if(typeof data[1] != 'undefined'){
			var r2 = data[1];
			var id2 = r2['arg']['id'];
			var html2 = r2['arg']['html'];
			id2 = "#" + id2;
			$(id2).html(html2);
		}
		
		// Ако искаме да затворим формата, затваряме я
		if(hide == true){
			cancelForm();
		}
		
		// Ако има трети обект за подмяна на съдържанието му
		if(typeof data[3] != 'undefined'){
			
			var r3 = data[3];
			var id3 = r3['arg']['id'];
			var html3 = r3['arg']['html'];
			id3 = "#" + id3;
			$(id3).html(html3);
		}
		
		if(typeof data[4] != 'undefined'){
			if(data[4] == true){
				$(id).closest("tr").addClass("quiet");
			} else {
				$(id).closest("tr").removeClass("quiet");
			}
		}
		
		// Ако не искаме да се отваря нова форма, излизаме от функцията
		if(stop == true){
			return;
		}
		
		// Ако има е респонса трети параметър
		if(typeof data[2] != 'undefined'){
			var r3 = data[2];
			var nextelement = r3['arg']['nextelement'];
			
			// Генерираме събитие за натискане на следващия елемент
			var event = jQuery.Event("click");
			$("#" + nextelement).trigger(event);
		}
	});
}
