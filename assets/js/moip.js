/**
 *  Métodos que tratam do acesso à janela popup dos pagamentos
 */
var retorno;
var mpg_popup;
window.name="loja";
function fabrewin(jan) {
    if(navigator.appName.indexOf("Netscape") != -1) {
       mpg_popup = window.open("", "mpg_popup","toolbar=0,location=0,directories=0,status=1,menubar=0,scrollbars=1,resizable=0,screenX=0,screenY=0,left=0,top=0,width=800,height=600");
     }
    else {
       mpg_popup = window.open("", "mpg_popup","toolbar=0,location=0,directories=0,status=1,menubar=0,scrollbars=1,resizable=1,screenX=0,screenY=0,left=0,top=0,width=800,height=600");
    }	
	return true;
}

function OpenSaibaMais(){
	window.open('http://www.visa.com.br/vbv/vbv_saibamais.asp','principal','height=435,width=270,top=0,left=0,resizable=no,status=1');
}

function FormataValor(id,tammax,teclapres) {
	var tecla;
	if(window.event) { // Internet Explorer
	    tecla = teclapres.keyCode;
	} else if(teclapres.which) { 
    	tecla = teclapres.which;
	}

	vr = document.getElementById(id).value;
	vr = vr.toString().replace( "/", "" );
	vr = vr.toString().replace( "/", "" );
	vr = vr.toString().replace( ",", "" );
	vr = vr.toString().replace( ".", "" );
	vr = vr.toString().replace( ".", "" );
	vr = vr.toString().replace( ".", "" );
	vr = vr.toString().replace( ".", "" );
	tam = vr.length;

	if (tam < tammax && tecla != 8){ tam = vr.length + 1; }
	if (tecla == 8 ){ tam = tam - 1; }
    if ( tecla == 8 || tecla >= 48 && tecla <= 57 || tecla >= 96 && tecla <= 105 ){
		if ( tam <= 2 ){
		    document.getElementById(id).value = vr; }
		if ( (tam > 2) && (tam <= 5) ){
		    document.getElementById(id).value = vr.substr( 0, tam - 2 ) + ',' + vr.substr( tam - 2, tam ); }
		if ( (tam >= 6) && (tam <= 8) ){
		    document.getElementById(id).value = vr.substr( 0, tam - 5 ) + '' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ); }
		if ( (tam >= 9) && (tam <= 11) ){
		    document.getElementById(id).value = vr.substr( 0, tam - 8 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ); }
		if ( (tam >= 12) && (tam <= 14) ){
		    document.getElementById(id).value = vr.substr( 0, tam - 11 ) + '.' + vr.substr( tam - 11, 3 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam ); }
		if ( (tam >= 15) && (tam <= 17) ){
		    document.getElementById(id).value = vr.substr( 0, tam - 14 ) + '.' + vr.substr( tam - 14, 3 ) + '.' + vr.substr( tam - 11, 3 ) + '.' + vr.substr( tam - 8, 3 ) + '.' + vr.substr( tam - 5, 3 ) + ',' + vr.substr( tam - 2, tam );
	    }
	}
}

var tentativa = 1;
var aviso = 1;

function getBloqueador() {
    var janela = window.open("#","janelaBloq", "width=1, height=1, top=0, left=0, scrollbars=no, status=no, resizable=no, directories=no, location=no, menubar=no, titlebar=no, toolbar=no");
    if (janela == null) {
        if (tentativa == 1) {
            alert("Bloqueador de popup ativado. Clique na barra amarela do seu navegador e marque a opção 'Sempre permitir para este site'.");
            tentativa++;
            return false;
        } else if ((tentativa > 1) && (tentativa <= 3)) {
            alert("Tentativa " + tentativa + " de 3: O bloqueador ainda está ativado.");
            tentativa++;
            return false;
        } else if (tentativa > 3) {
			if (aviso == 1) {
				if (confirm("O bloqueador de popups ainda está ativado, você pode ter dificuldades para acessar o site.\n\nDeseja continuar assim mesmo?")) {
					aviso = 0;
					return true;
                } else {
					aviso = 0;
					return false;
                }
			}
        }
    } else {
		janela.close();
		return true;
    }
}

/**
 *  Métodos de acesso/persistencia na base
 */
var erro = false;

// função que arrenda números x com n casas decimais
function arredondamento (x, n){
	if (n < 0 || n > 10) return x;
	var pow10 = Math.pow (10, n);
	var y = x * pow10;
	return (Math.round (y)/pow10).toFixed(2);
}

function show_parcelas(item) {
	var id		= '';
	// var debito  = new Array('visa_electron','maestro');
	var credito = new Array('visa','master','diners','amex','discover','hipercard');

	for (var c in credito) {
		var cartao = credito[c];
		id = '#div_'+cartao;
		if (jQuery(id).length > 0) {
			if (this.erro) {
				mostra_erro(true);
			} else {
				mostra_erro(false);
			}
			mostra_div(id,item,cartao);
		}
	}
}

function mostra_div(id,item,valor) {
	if (item == valor) {
		jQuery(id).show();
	} else {
		jQuery(id).hide();
	}
}

function mostra_erro(erro) {
	if (erro) {
		//$('div_erro').style.display = 'block';
		jQuery('#div_erro').show();
	} else {
		//$('div_erro').style.display = 'none';
		jQuery('#div_erro').hide();
	}
}

function status_erro() {	
	return jQuery('#div_erro').css('display');
}

// Método que marca o campo radio manualmente ( para o ie )
function marcar_radio(id) {
	jQuery(id).checked='checked';
}

jQuery(document).ready(function(){
	jQuery('#cvv').mask("999?9");  
	// adicionando compatibilidade com telefones do prefixo 11 com um dígito a mais
	jQuery('#telefone_titular').mask("(99) 9999?9-9999");  
	jQuery('#cpf_titular').mask("999.999.999-99");
	jQuery('#expiry_date').mask("99/99");	 
	jQuery('#nascimento_titular').mask("99/99/9999");	 
});

/*
 Envio dos dados do cartão
*/
var erro = false;

function erro_cartao(id) {
	jQuery('form#'+id+' input[type=submit]').val('Efetuar Pagamento');
	erro = true;
	return false;
}

function submeter_cartao(formulario) {	
    var id = 'form#'+formulario.id;
	
    var cartao_selecionado 	= jQuery(id+' input[name=tipo_pgto]:checked').val();
	var parcela_selecionada 	= jQuery(id+' input[name=parcelamento]:visible:checked').val();
	var numero_cartao 			= jQuery(id+' input#card_number').val();
	var validade_cartao 		= jQuery(id+' input#expiry_date').val();
	var cvv_cartao 				= jQuery(id+' input#cvv').val();
	var titular_cartao 			= jQuery(id+' input#name_on_card').val();
	var nascimento_titular 		= jQuery(id+' input#nascimento_titular').val();
	var telefone_titular 			= jQuery(id+' input#telefone_titular').val();
	var cpf_titular 				= jQuery(id+' input#cpf_titular').val();

	jQuery('#div_erro').show();
	jQuery('#div_erro').addClass('error');

	if (titular_cartao == '') {
		jQuery('#div_erro_conteudo').text('Digite o nome impresso no Cartão de Crédito');
		msgPop();
		return erro_cartao();
	}
	if (cpf_titular == '') {
		jQuery('#div_erro_conteudo').text('Digite o cpf do titular do Cartão de Crédito');
		msgPop();
		return erro_cartao();
	}
	if (nascimento_titular == '') {
		jQuery('#div_erro_conteudo').text('Digite a data de nascimento do Titular do Cartão');
		msgPop();
		return erro_cartao();
	}
	if (numero_cartao == '') {
		jQuery('#div_erro_conteudo').text('Digite o número do Cartão de Crédito');
		msgPop();
		return erro_cartao();
	}
	if (validade_cartao == '') {
		jQuery('#div_erro_conteudo').text('Digite a validade do Cartão de Crédito');	
		msgPop();
		return erro_cartao();
	}		
	if (cvv_cartao == '') {
		jQuery('#div_erro_conteudo').text('Digite o código de verificação Cartão de Crédito');	
		msgPop();
		return erro_cartao();
	}	
	if (cartao_selecionado == '') {
		jQuery('#div_erro_conteudo').text('Selecione um Cartão de Crédito');
		msgPop();
		return erro_cartao();
	}
	if (cartao_selecionado == 'amex' && cvv_cartao.length != 4) {
		jQuery('#div_erro_conteudo').text('O Código de verificação deve ser de 4 dígitos.');
		msgPop();
		return erro_cartao();	
	} 
	if(cartao_selecionado != 'amex' && cvv_cartao.length != 3) {
		jQuery('#div_erro_conteudo').text('O Código de verificação deve ser de 3 dígitos.');
		msgPop();
		return erro_cartao();	
	}

	if (parcela_selecionada == '') {
		jQuery('#div_erro_conteudo').text('Selecione um parcelamento do Cartão de Crédito');
		msgPop();
		return erro_cartao();
	}
	erro = false;
	
	//jQuery('#div_erro').hide();	
	pagamentoEmAndamento();
	
	var cartoes = new Array();
	cartoes['amex'] 		= 'American Express';
	cartoes['diners'] 		= 'Diners';
	cartoes['hipercard'] = 'Hipercard';
	cartoes['visa'] 		= 'Visa';
	cartoes['master'] 	= 'Mastercard';

	var settings = {
		"Forma": "CartaoCredito",
		"Instituicao": cartoes[cartao_selecionado],
		"Parcelas": parcela_selecionada,
		"Recebimento": "AVista",
		"CartaoCredito": {
			"Numero": numero_cartao,
			"Expiracao": validade_cartao,
			"CodigoSeguranca": cvv_cartao,
			"Portador": {
				"Nome": titular_cartao,
				"DataNascimento": nascimento_titular,
				"Telefone": telefone_titular,
				"Identidade": cpf_titular
			}
		}
	}
	jQuery('#forma_pagamento').val('CartaodeCredito');
	//jQuery('#tipo_pagamento').val(cartoes[cartao_selecionado]+' - '+parcela_selecionada+'x |'+titular_cartao+'|'+nascimento_titular+'|'+telefone_titular+'|'+cpf_titular);
	jQuery('#tipo_pagamento').val(cartoes[cartao_selecionado]+' - '+parcela_selecionada+'x ');

	MoipWidget(settings);
	return false;
}

function msgPop() {
	jQuery.facebox(jQuery('#system-message-cartao').clone().attr('id','system-message-cartao').html());	
}

function pagamentoEmAndamento() {
	jQuery('#div_erro').removeClass('error');
	jQuery('#div_erro_conteudo').html('<div align="center">Pagamento em Andamento...<br /><br /><img src="/plugins/vmpayment/moip/assets/images/carregando.gif" border="0"/></div>');
	msgPop();
}

/*
 Envio dos dados do cartão
*/
function submeter_boleto(formulario) {
	var id = 'form#'+formulario.id;
	var settings = {
		"Forma": "BoletoBancario"
	}
	jQuery('#forma_pagamento').val('BoletoBancario');
	jQuery('#tipo_pagamento').val('Boleto Bradesco');
	
	pagamentoEmAndamento();
	
	MoipWidget(settings);
	jQuery(id+' input[type=submit]').val('Gerar 2a Via Boleto');	
	return false;
}

function submeter_debito(formulario) {
    var id = 'form#'+formulario.id;
    var debito_selecionado 	= jQuery(id+' input[name=tipo_pgto_debito]:checked').val();
	jQuery('#div_erro').show();
	jQuery('#div_erro').addClass('error');
	if (debito_selecionado == '') {
		jQuery('#div_erro_conteudo').text('Selecione um Débito Bancário');
		erro = true;
		return false;
	}
	erro = false;
	var debito = new Array();
	debito['bb'] 		= 'BancoDoBrasil';
	debito['bradesco'] 	= 'Bradesco';
	debito['banrisul']	= 'Banrisul';
	debito['itau'] 		= 'Itau';

	pagamentoEmAndamento();
	
	var settings = {
            "Forma": "DebitoBancario",
            "Instituicao": debito[debito_selecionado]
	}
	jQuery('#forma_pagamento').val('DebitoBancario');
	jQuery('#tipo_pagamento').val(debito[debito_selecionado]);
	
	MoipWidget(settings);
	return false; 
} 

function notificaPagamento(data) {
	var forma_pagamento = jQuery('#forma_pagamento').val();
	var tipo_pagamento = jQuery('#tipo_pagamento').val();
	var status_pagamento = data.StatusPagamento;
	if (status_pagamento == 'undefined') {
		status_pagamento = '';
	}
	var mensagem_retorno_pagto;
	if (typeof(data.Classificacao) != 'undefined') {
		mensagem_retorno_pagto = '#'+data.Classificacao.Codigo+' - '+data.Classificacao.Descricao;
	} else {
		mensagem_retorno_pagto = 'Transação em Andamento';
	}
	var dados = 'CodigoMoIP='+data.CodigoMoIP+
				'&Codigo='+data.Codigo+
				'&Status='+data.Status+
				'&StatusPagamento='+data.StatusPagamento+
				'&TaxaMoip='+data.TaxaMoip+
				'&TotalPago='+data.TotalPago+
				'&Mensagem='+mensagem_retorno_pagto+
				'&Url='+data.url+
				'&order_id='+jQuery('#order_id').val()+
				'&moip=1'+
				'&TipoPagamento='+tipo_pagamento+
				'&FormaPagamento='+forma_pagamento;

	jQuery.ajax({
		type: "POST",
		url: redireciona_moip,	
		data: dados,
		success: function(retorno){	
			var debito = jQuery('input[name=tipo_pgto_debito]:visible:checked').length;
			var boleto = jQuery('form#pagamento_boleto:visible').length;
			if ((debito > 0 || boleto > 0) && data.Codigo == 0) {			
				// SqueezeBox.initialize();
				// SqueezeBox.open(data.url, {handler: 'iframe', size: {x:750, y:500}});
				jQuery.facebox('<iframe width="1000" height="680" name="iframemoip" src="'+data.url+'"></iframe>');
			}
			if (!erro) {
				if (data.Codigo == 0) {
					jQuery('#div_erro').addClass('success').removeClass('error').show();
					// mensagem de retorno do pagamento
					var mensagem_pagamento = '';
					if (typeof (data.CodigoMoIP) != 'undefined') {
						mensagem_pagamento += '<b>ID MOIP: #'+data.CodigoMoIP+'</b> <br />';						
					}
					mensagem_pagamento += 'Status: <b>'+mensagem_retorno_pagto+'</b> <br /><br />';
					mensagem_pagamento += 'Forma de Pagamento: '+forma_pagamento+' - '+tipo_pagamento+' <br />';

					if (forma_pagamento == 'CartaodeCredito' || forma_pagamento == 'CartaodeDebito') {
						mensagem_pagamento +='Em alguns segundos você será redirecionado automaticamente  para o comprovante do Pagamento ou <a href="'+url_recibo_moip+'">clique aqui</a>.';
						jQuery('#div_erro_conteudo').show().html(data.Mensagem+'<br /><br />'+mensagem_pagamento);
						msgPop();												
						var t = setTimeout('redireciona_recibo()',5000);
					} else {						
						mensagem_pagamento += 'Clique no <a href="'+url_recibo_moip+'">link</a> para acessar os detalhes do pedido.';
						jQuery('#div_erro_conteudo').show().html(data.Mensagem+'<br /><br />'+mensagem_pagamento);
					}
					jQuery('#container form').parent().hide('slow');
					jQuery('#div_erro_conteudo').animate({"padding":"20px","font-size":"15px"}, 1000);

				} else {
				
					jQuery('#div_erro').addClass('error').show();					
					var mensagem = data;
					if (typeof data !== 'undefined' && data !== null && typeof data.length === 'number') {
						var mensagem_html = 'Erros: <br />';
						for(i=0; i<data.length; i++) {
						    mensagem_html += ' - ' +data[i].Mensagem + '<br />';
						}
						mensagem = mensagem_html;
					} else {
						mensagem = data.Mensagem;
					}
					
					if (mensagem == 'Pagamento já foi realizado') {
						mensagem +='<br/> <a href="'+url_recibo_moip+'">Clique aqui para ser redirecionado</a> para o status do Pagamento.';
						jQuery('#container form').parent().hide('slow');
					}
					
					jQuery('#div_erro_conteudo').show().html(mensagem+'<br />');					
					jQuery('#div_erro_conteudo').animate({"padding":"20px","font-size":"15px"}, 1000);
					
					jQuery.facebox(jQuery('#div_erro_conteudo').clone().attr('id','system-message-cartao').html());

					/*
					SqueezeBox.open(jQuery('system-message-cartao').clone().addClass('error').set('id','system-message-cartao'), {
						handler: 'adopt',
						size: {x: 500, y: 200}
					});*/
				}
			}
		}				
	});
}

function redireciona_recibo() {
	jQuery('#container form').hide();
	location.href = url_recibo_moip;
}

function efeito_divs(mostra,esconde,esconde2) {
	jQuery('#'+mostra+' form').show();
	jQuery('#'+esconde+' form').hide();
	jQuery('#'+esconde2+' form').hide();
}

var funcao_sucesso = function(data) {
	//console.log(data);
	notificaPagamento(data);
};

var funcao_falha = function(data) {
	//console.log(data);
	notificaPagamento(data);
};