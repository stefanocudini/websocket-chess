
var pollingRun = false,	//stato di esecuzione di update()
	pollingStop = false;  //blocca Polling
	drag = false,	//stato di trascinamento di una pedina
	allpos = true,	//primo ingresso, quindi chiede la posizione di tutte le pedine
	myturn = false,
	versoOrd = 'ori',  //verso di ordinamento pedine
	pollingTime = 5000,	//intervallo di polling
	action = './scacchi.php',  //servente richieste ajax e websocket
	ws_support = window["WebSocket"],	//supporto a websocket
	ws_connected = false;  //dice se e' collegato in websocket


function myrand()  //valore da aggiungere agli url per non usare la cache
{
	return Math.floor(Math.random()*10000);  //a 5 cifre
}

function ora()
{
	var d = new Date(),
		T = {
			h: d.getHours(),
			m: d.getMinutes(),
			s: d.getSeconds()
			};

	for(t in T)
		T[t] = T[t]>9 ? T[t] : '0'+T[t];
	
	return [T.h,T.m,T.s].join(':');
}

function logga(text)//per debug
{
	if(debug)
		$('#log').prepend(text+'<hr />');
}

function pollingStartStop()//per debug
{
	pollingStop = !pollingStop;
}

function websocketStartStop()//per debug
{
	ws_support = !ws_support;
}

function toggleVersoOrd()//per debug
{
	versoOrd = versoOrd=='ori'?'ver':'ori';
	ordinaPedine();
}

function setZindex(ped)	//cambia il valore z-index con la posizine delle pedina
{						//quelle in alto vanno dietro quelle in basso vanno davanti
	var y = parseInt($(ped).css('top'));
	var h = parseInt($(ped).css('height'));	
	var z = 100 + y + h - 10;  //zindex rispetto alla base delle pedine
	$(ped).css({zIndex: z});
}

function evidenzia(ped)  //animazione pedina spostata dall'avversario
{
	$(ped).addClass('mossa');
	setTimeout(function() {
		$(ped).removeClass('mossa');
	},1000);
}
function evidenziaCella(cellid)  //animazione pedina spostata dall'avversario
{
	$(cellid).addClass('hover');
	setTimeout(function() {
		$(cellid).removeClass('hover');
	},3000);
}

function ordinaPedine()	//invia comando di riordinare le pedine con le posizioni di default
{
	var req = {ordina:'',verso: versoOrd};
	if(ws_connected)
		ws_send(req);	//websocket
	else
		$.get(action, req);	//ajax
}

function sposta(ped, x,y, callback)  //sposta una pedina da dove si trova ad un punto x,y con animazione
{
	drag = ped;  //stabilisce quale pedina si sta spostando
	
	var $ped = $(ped);
	
	$ped.animate({left: x, top: y}, 350, function() {
		
		drag = false;
		
		setZindex(ped);
		evidenzia(ped);

		if(typeof callback == 'function')
			callback( $ped );
		
		var cellid = cellidFromPed($ped);
		$ped.data('cellid', cellid);  //associa la cella sottostante
		
		if($ped.data('cellid')==$ped.data('cellfrom')) return;
		
		if(!$ped.data('cellfrom'))		//per le pedine mai postate a mano, ma solo dal'avversario
			$ped.data('cellfrom', cellid);
		else
			addHist($ped);
	});
}

function addHist($ped)	//aggiunge lo spostamento delle pedina allo storico
{
	var from = $ped.data('cellfrom'),
		to = $ped.data('cellid');
		pedid = $ped.attr('id').substr(1);
		
	var histText = '<small>'+ ora() +'</small>&nbsp;&nbsp;&nbsp;'+
					//'<b>'+ (pedid.length<3 ? pedid+'&nbsp;&nbsp;': pedid) +'</b> '+
					from.toUpperCase() +' &rarr; '+ to.toUpperCase();

	$('<a>').data({from: from, to: to, ped: $ped.attr('id')} )
	.attr({href:'#'})
	.addClass( ($ped.is('.bianca') ? 'bianca' : 'nera') )
	.html( histText ).appendTo('#history');
	$('#history').scrollTop($(this).height());
	//scrollare la history
}

function cellidFromPed(ped)	//restituisce la cella sotto alla pedina
{
	var cellid = false;

	$('td, .tav').each(function(i) {
	 	
	 	if( $(this).overlaps( ped.children('.cur') ) )	//se la cella si trova sotto al punto di riferimento della pedina
	 	{
			cellid = $(this).attr('id');
	 	}

	});
	return cellid;
}



/////////EVENTI... creare un dispacher fatto bene

function inviaPosAction(ped)
{
	var pedid = $(ped).attr('id').substr(1);	//toglie la 'p' iniziale
	var pedx = parseInt($(ped).css('left'));	//senza 'px'
	var pedy = parseInt($(ped).css('top'));

	return { mov: pedid, x: pedx, y: pedy };
}

function inviaPos(ped) //richiamato ondrop 
{
	var req = inviaPosAction(ped);	//ritorna { mov: pedid, x: pedx, y: pedy }

	if(ws_connected)
		ws_send(req);	//websocket
	else		
		$.get(action, req);
}

function updateOnlineAction(r)
{
	$('#online span').fadeOut('slow').text(r).fadeIn('fast');
}
function updateOnline()  //utenti online
{
	var req = {online:''};
	$.get(action, req, updateOnlineAction);
}

function updateTurnoAction(json)
{
	var turn = json.turn ? json.turn : '';
	
	if(turn=='yes')
	{
		$('#turno span').html("&Eacute; il tuo turno");
		myturn = true;
	}
	else if(turn=='no')
	{
		$('#turno span').html("Turno avversario");
		myturn = false;
	}
}
function updateTurno()
{
	var req = {myturn: ''};
	$.getJSON(action, req, updateTurnoAction );
}

function updatePedineAction(pedine)	//pedine e' un array di array
{
	if(typeof pedine == 'object' && pedine.length>0) //numero di pedine spostate dall'avversario
	{
		for(i in pedine)
			if(drag != 'p' + pedine[i][0])	//non sposta la pedina che si sta trascinando
				sposta('#p'+pedine[i][0], pedine[i][1], pedine[i][2]);
		allpos = false;  //dopo la prima volta non serve piu e richiede solo pedine spostate di recente
	}
}

function updatePedine()	//riceve dal server la posizione di tutte le pedine spostate dall'avversario
{
	if(drag) return;	//non sposta la pedina che si sta trascinando

	var req = { pos: '' };	
	if(allpos)
		req.allpos = '';	//richiede tutte le posizioni quando l'utente entra nella pagina
	
	$.getJSON(action, req, updatePedineAction );
}

////////////////////////...ed ora POLLING!!! :-)

function polling()  //unica richiesta HTTP per tutti i dati
{
	if(!pollingStop && pollingRun==false)  //per non far sovrapporre l'esecuzione di update
	{
		pollingRun = true;
		
		$('#status').text('connessione...  Ajax polling '+(pollingTime/1000)+' sec');

		var req = { updateall: '' };	
		if(allpos)
			req.allpos = '';	//richiede tutte le posizioni quando l'utente entra nella pagina

		$.getJSON(action, req,  //richiesta unica
			function(json) {

				if(json)
				{
					if(json.refresh)
						window.location.href = json.refresh;
					//refresh forzato della pagina, per debug

					updateOnlineAction(json.online);
					updateTurnoAction(json.turn);
					updatePedineAction(json.pos);
					//updateChatAction(d.chat);
				}
			});

		pollingRun = false;
	}
}

$(function() {

$.ajaxSetup({cache: false});

	$('.pedina').each(function(e) {		//EVENTI PEDINE

		//INVIARE E LA POSIZIONE ANCHE QUANDO SI TRASCINA IL MOUSE
		//MA FARE IN MODO CHE NON VENGA SALVATA SU SERVER
		//E SALVARE LA POSIZIONE SUL SERVER SOLO QUANDO SI RILASCIA IL MOUSE

		$(this).easydrag();

		$(this).ondrag(function(e, ped) {
			drag = $(ped).attr('id');
		});

		$(this).ondrop(function(e, ped) {	//unico evento in uscita oltre onopen!!
			
			drag = false;	//trascinamento pedina finito
			
			var $ped = $(ped);
			
			var cellid = $ped.data('cellto'); //impostato quando si trascina la pedina

			
			$ped.position({		//centra la pedina sulla cella
				my:'center',
				at:'center',
				of: '#' + cellid,//id cella sottostante
				using: function(p) {
				
					sposta(ped, p.left, p.top, inviaPos );
					//invia posizione al server in ajax oppure websocket
					//dopo che e' stata centrata sulla cella
					//e aggiunge alla history

				}//*/
			});

		});
	});

	$('.pedina').mousemove(function(e) {  //seleziona cella quando la pedina ci sta sopra
		
		if(drag)
		{
			var $ped = $(this), pedid = $ped.attr('id');
		
			setZindex('#' + pedid);		//posiziona dietro o davanti ad altre pedine
		
			var cellid = cellidFromPed($ped);
			if(!cellid) return;
			
			var cell = $('#'+cellid);

			$('td, .tav').removeClass('hover');	//disevidenzia altre celle
	 		cell.addClass('hover');		//evidenzia cella sottostante

	 		if(cellid != $ped.data('cellto'))
	 			$ped.data('cellto', cellid);
/*
	 		if(cellid != $ped.data('cellid'))
	 		{	
	 			$ped.data('cellfrom', $ped.data('cellid') );
				$ped.data('cellid', cellid );
			}*/
		}

	});	
	
	$('#history').delegate('a', 'mouseover', function() {
		$('td').removeClass('hover');
		$('#'+ $(this).data('from') +',#'+ $(this).data('to') ).addClass('hover');
		return false;
	});
	
	function clearLog()	{
		$('#log').text('');
	}
//	$('#debug_wrap').easydrag();	
	$('#debug_wrap :button').click(function() { //non va usato in websocket
	  	eval( $(this).val() );
	  	$(this).focus();
	});
//////////////////////////////////////connessione al server
	
	var ws_opts = {
		onopen: function(e) {
			ws_connected = true;
			ws_send( {updateall:'', allpos:''} );	//oggetto che rappresenta la richista GET
			$('#status').text('connessione... Websocket');
			console.log('ws open');
		},
		onclose: function(e) {
			ws_connected = false;
			console.log('ws close');
		},
		onerror: function(e) {
			ws_connected = false;
			console.log('ws error');
		},
		onmessage: function(data) {
			var json = $.parseJSON(data);	//usare questa sempre al posto di eval() per estrapolare json da una stringa
			//quindi con jquery >= 1.4.2!!!
			if(json.refresh) window.location.href = json.refresh;	//refresh forzato della pagina, per debug
			if(json.online) updateOnlineAction(json.online);
			if(json.turn) updateTurnoAction(json.turn);		  
			if(json.pos) updatePedineAction(json.pos);  //eseguito anche dopo un inviaPos
			//updateChatAction(d.chat);	
		}
	};

	//Polling o websocket//
	
	function initLoop() {	//loop ricorsivo
	
		if(ws_support)	//se il browser supporta i websocket ma il server non risponde, ritenta ed intato usa il polling
		{
			if(ws_connected==false)			  //se non e' gia collegato si ricollega
			{
				ws_connect(ws_opts);
			
				if(ws_connected==false)  //reimposta ws_connected a true o false
				{
					logga('websocket non funziona uso polling');
					polling();			  //se non riesce a collegarsi in websocket usa il polling
				}
				else
					logga('connesso con websocket');
			//*/
			}
		}
		else
		{
			logga('websocket non supportati');
			polling();
		}
		
		setTimeout(function() { initLoop(); }, pollingTime);
	}
	
	initLoop();

});
