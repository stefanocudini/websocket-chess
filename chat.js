

var allchat = true;
var bot = false;

function clearChat()
{
  $.get('chat.php?',{clearchat:''});
}

function sendChat(mymessage)
{
	var myname = $('input[@name=nom]').get(0).value;
	var text = $('input[@name=mes]').get(0);	
	text.value = '';
	text.focus();	
	$.post('chat.php?' + myrand(),
				{chat:'', mes: mymessage, nom: myname},
				function(r) {
					updateChat();					
				});
}

function updateChatAction(r)
{
	if(myturn && r.length>0) //numero di pedine spostate dall'avversario
	{
	  $('#chatarea').empty();
		$.each(r, function(k,v) {
		  var color = 'black';
		  if(v[3]) color = v[3].color;
			$('#chatarea').prepend('<small>'+v[0]+'</small> <span style="color:'+color+'"><b>'+v[1]+':</b> <span>'+v[2]+'</span></span><br />');
			allchat = false;
		});
		logga('updateChat: '+r.toString());
	}
}
function updateChat()
{
	var uenter = allchat ? '&allchat&' : '';
	//richiede tutte le posizioni quando l'utente entra nella pagina
	$.getJSON('./?' + uenter + myrand(),
	  { chat: '' },
	  updateChatAction
	  );
}

function botchat()	/* piccolo robot di benvenuto */
{
	setTimeout(function() {
		setInterval(function() {
		  $('#online').text('2');
		}, 200);
	},3000);

	var mes = ['Hei ciao!',
			   'giochiamo? :)',
			   'dai!',
			   'io le rosse',
			   'vai comincia te'];	
	var i = 0;
	var tt = setInterval(function() {
		var mess = mes[i++];
		if(i>mes.length-1) clearInterval(tt);
		sendChat(mess,'Ciccio');
	}, 5000);
}

$('#chatform').submit(function() {
	var text = $('input[@name=mes]').get(0);
	var mymessage = text.value;
	sendChat(mymessage);
	return false;
});
/*
if($.cookie('nickname')==null)
{
  $('input[@name=nom]').value = $.cookie('nickname');
}
else
{
  $.cookie('nickname', $('input[@name=nom]').value);
}

//alert($.cookie('nickname'));

$('input[@name=nom]').change(function() {
	$.cookie('nickname', $(this).value );
});
*/

$('#setmoticons .sel').click(function() {
  $('#emoticons').show();
});

$('#emoticons').easydrag();
//	$('#chatcontainer').easydrag();

$('#emoticons em').click(function() {
	$(this).parent().hide();
});

$('#emoticons a').click(function() {
	var text = $('input[@name=mes]').get(0);
	sendChat(text.value +' '+$(this).attr('alt'));
	//$('#emoticons').hide();
});

if(bot) botchat();

