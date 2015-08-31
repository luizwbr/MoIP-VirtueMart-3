<?php

if (!defined('_VALID_MOS') && !defined('_JEXEC'))
    die('Direct Access to ' . basename(__FILE__) . ' is not allowed.');

/**
 * @version $Id: moip.php,v 1.6 2012/08/31 11:00:57 ei
 *
 * a special type of 'cash on delivey':
 * @author Max Milbers, Valérie Isaksen, Luiz Weber
 * @version $Id: moip.php 5122 2012-02-07 12:00:00Z luizwbr $
 * @package VirtueMart
 * @subpackage payment
 * @copyright Copyright (C) 2004-2008 soeren - All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */
if (!class_exists('vmPSPlugin'))
    require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');

class plgVmPaymentMoip extends vmPSPlugin {

    // instance of class
    public static $_this = false;

    function __construct(& $subject, $config) {
        //if (self::$_this)
        //   return self::$_this;
        parent::__construct($subject, $config);

        $this->_loggable = true;
        $this->tableFields = array_keys($this->getTableSQLFields());
        $this->_tablepkey = 'id';
        $this->_tableId = 'id';
        $varsToPush = $this->getVarsToPush ();
        $this->setConfigParameterable($this->_configTableFieldName, $varsToPush);

        $this->domdocument = false;

        if (!class_exists('VirtueMartModelOrders'))
           require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
    // Set the language code
       $lang = JFactory::getLanguage();
       $lang->load('plg_vmpayment_' . $this->_name, JPATH_ADMINISTRATOR);
        // self::$_this = $this;

       if (!class_exists('CurrencyDisplay'))
        require( JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'currencydisplay.php' );


}
    /**
     * Create the table for this plugin if it does not yet exist.
     * @author Valérie Isaksen
     */
    protected function getVmPluginCreateTableSQL() {
        return $this->createTableSQL('Payment Moip Table');
    }

    /**
     * Fields to create the payment table
     * @return string SQL Fileds
     */
    function getTableSQLFields() {
    // tabela com as configurações de cada transação Moip
        $SQLfields = array(
            'id' => 'bigint(15) unsigned NOT NULL AUTO_INCREMENT',
            'codigo_moip' => ' varchar(25) NOT NULL',
            'token_api' => ' varchar(150) NOT NULL',
            'virtuemart_order_id' => 'int(11) UNSIGNED DEFAULT NULL',
            'order_number' => 'char(32) DEFAULT NULL',
            'virtuemart_paymentmethod_id' => 'mediumint(1) UNSIGNED DEFAULT NULL',
            'payment_name' => 'char(255) NOT NULL DEFAULT \'\' ',
            'payment_order_total' => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ',
            'payment_currency' => 'char(3) ',
            'type_transaction' => 'varchar(200) DEFAULT NULL ',
            'cofre' => 'varchar(200) DEFAULT NULL ',
            'nome_titular_cartao' => ' varchar(255) DEFAULT NULL ',
            'nascimento_titular_cartao' => ' varchar(20) DEFAULT NULL ',
            'telefone_titular_cartao' => ' varchar(20) DEFAULT NULL ',
            'cpf_titular_cartao' => ' varchar(20) DEFAULT NULL ',
            'log' => ' varchar(200) DEFAULT NULL',
            'status' => ' char(1) not null default \'P\'',
            'msg_status' => ' varchar(255) NOT NULL',
            'url_redirecionar' => ' varchar(255) NOT NULL',
            'tax_id' => 'smallint(11) DEFAULT NULL',
            );
return $SQLfields;
}

function getPluginParams(){
    $db = JFactory::getDbo();
    $sql = "select virtuemart_paymentmethod_id from #__virtuemart_paymentmethods where payment_element = 'moip'";
    $db->setQuery($sql);
    $id = (int)$db->loadResult();
    return $this->getVmPluginMethod($id);
}

    /**
     *
     *
     * @author Valérie Isaksen
     */
    function plgVmConfirmedOrder($cart, $order) {

        if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
            return null; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement($method->payment_element)) {
            return false;
        }

        $this->order_id = $order['details']['BT']->order_number;

        $url  = JURI::root();

        vmJsApi::js('facebox');
        vmJsApi::css('facebox');

    // carrega os js e css
        $doc = & JFactory::getDocument();
        $url_lib      = $url. DS .'plugins'. DS .'vmpayment'. DS .'moip'.DS;
        $url_js       = $url_lib . 'assets'. DS. 'js'. DS;
        $this->url_imagens  = $url_lib . 'imagens'. DS;
        $url_css      = $url_lib . 'assets'. DS. 'css'. DS;
    // redirecionar dentro do componente para validar
        $url_redireciona_moip = JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component&pm='.$order['details']['BT']->virtuemart_paymentmethod_id);
        $url_pedidos = JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=orders');
        $url_recibo_moip = JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&on='.$this->order_id.'&pm='.$order['details']['BT']->virtuemart_paymentmethod_id);

    // carrega ou não o squeezebox
        $load_squeezebox = $method->load_squeezebox;
        $sq_js = '<script type="text/javascript" language="javascript" src="'.$url_js.'SqueezeBox.js"></script>';
        $sq_css = '<link href="'.$url_css.'SqueezeBox.css" rel="stylesheet" type="text/css"/>';

        $doc->addCustomTag('
           <script language="javascript">
            jQuery.noConflict();
            var redireciona_moip = "'.$url_redireciona_moip.'";
            var url_pedidos = "'.$url_pedidos.'";
            var url_recibo_moip = "'.$url_recibo_moip.'";
        </script>
        <script type="text/javascript" language="javascript" src="'.$url_js.'jquery.mask.js"></script>
        <script type="text/javascript" charset="utf-8" language="javascript" src="'.$url_js.'moip.js"></script>
        <script type="text/javascript" language="javascript" src="'.$url_js.'jquery.card.js"></script>
        <script type="text/javascript" language="javascript" src="'.$url_js.'validar_cartao.js"></script>
        '.($load_squeezebox!=0?$sq_js:'').'
        <link href="'.$url_css.'css_pagamento.css" rel="stylesheet" type="text/css"/>
        <link href="'.$url_css.'card.css" rel="stylesheet" type="text/css"/>
        '.($load_squeezebox!=0?$sq_css:'').'
        ');



        $lang = JFactory::getLanguage();
        $filename = 'com_virtuemart';
        $lang->load($filename, JPATH_ADMINISTRATOR);
        $vendorId = 0;

        $this->logInfo('plgVmConfirmedOrder order number: ' . $order['details']['BT']->order_number, 'message');
        $html = "";

        if (!class_exists('VirtueMartModelOrders')) {
            require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
        }

        $this->getPaymentCurrency($method);

        $q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $method->payment_currency . '" ';
        $db = &JFactory::getDBO();
        $db->setQuery($q);
        $currency_code_3 = $db->loadResult();
        $paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);
        $totalInPaymentCurrency = round($paymentCurrency->convertCurrencyTo($method->payment_currency, $order['details']['BT']->order_total, false), 2);
        $cd = CurrencyDisplay::getInstance($cart->pricesCurrency);

    // pega o nome do método de pagamento
        $dbValues['payment_name']           = $this->renderPluginName($method);

        $html = '<table>' . "\n";
        $html .= $this->getHtmlRowBE('MOIP_PAYMENT_NAME', $dbValues['payment_name']);
        if (!empty($payment_info)) {
            $lang = & JFactory::getLanguage();
            if ($lang->hasKey($method->payment_info)) {
                $payment_info = JText::_($method->payment_info);
            } else {
                $payment_info = $method->payment_info;
            }
            $html .= $this->getHtmlRowBE('MOIP_INFO', $payment_info);
        }
        if (!class_exists('VirtueMartModelCurrency')) {
            require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'currency.php');
        }

        $currency = CurrencyDisplay::getInstance('', $order['details']['BT']->virtuemart_vendor_id);
        $html .= $this->getHtmlRowBE('MOIP_ORDER_NUMBER', $order['details']['BT']->order_number);
        $html .= $this->getHtmlRowBE('MOIP_AMOUNT', $currency->priceDisplay($order['details']['BT']->order_total));
        $html .= '
        <input type="hidden" name="order_id" id="order_id" value="'. $order['details']['BT']->order_number .'"/>
    </table>' . "\n";

    $this->chave_moip = $this->getChaveMoip($method);
    $this->token_moip = $this->getAfiliacaoMoip($method);

    if ($method->modo_teste) {
      // url do ambiente de desenvolvimento
       $this->setaUrlRequest('https://desenvolvedor.moip.com.br/sandbox/ws/alpha/EnviarInstrucao/Unica');
       $this->setaUrlJs('https://desenvolvedor.moip.com.br/sandbox/transparente/MoipWidget-v2.js');
   } else {
      // url do ambiente de produção
       $this->setaUrlRequest('https://www.moip.com.br/ws/alpha/EnviarInstrucao/Unica');
       $this->setaUrlJs('https://www.moip.com.br/transparente/MoipWidget-v2.js');
   }

   $arr_pagamento = $this->solicitaToken($method, $order);

   $this->_virtuemart_paymentmethod_id  = $order['details']['BT']->virtuemart_paymentmethod_id;
   $dbValues['order_number']            = $order['details']['BT']->order_number;
   $dbValues['virtuemart_paymentmethod_id'] = $this->_virtuemart_paymentmethod_id;
   $dbValues['cost_per_transaction']      = $method->cost_per_transaction;
   $dbValues['cost_percent_total']        = $method->cost_percent_total;
   $dbValues['payment_currency']        = $currency_code_3;
   $dbValues['payment_order_total']       = $totalInPaymentCurrency;
   $dbValues['tax_id']                = $method->tax_id;
   $dbValues['token_api']               = (string)$arr_pagamento['token'];
   $this->storePSPluginInternalData($dbValues);


    // grava os dados do pagamento
    //$this->gravaDados($method,0,$arr_pagamento['status']);

   if ($arr_pagamento['status'] == "Sucesso") {
       $this->token_api   = $arr_pagamento['token'];
       $this->erro_api    = $arr_pagamento['erro'];

      // monta o formulário na ultima página
       $html .= $this->Moip_mostraParcelamento($method, $order);

       JFactory::getApplication()->enqueueMessage(utf8_encode(
        JText::_('VMPAYMENT_MOIP_ORDER_OK')
        ));

      // envia emails e redireciona
       $novo_status = $method->transacao_nao_finalizada;
       return $this->processConfirmedOrderPaymentResponse(1, $cart, $order, $html, $dbValues['payment_name'], $novo_status);

   } else {
       $this->token_api   = null;
       $this->erro_api  = $arr_pagamento['erro'];

       JFactory::getApplication()->enqueueMessage(utf8_encode($this->erro_api),'error');
       return false;
   }


}

public function Moip_mostraParcelamento($method, $order) {

  $doc =& JFactory::getDocument();
  $doc->addScript($this->url_js);

  $conteudo = '
  <div id="MoipWidget"
  data-token="'.$this->token_api.'"
  callback-method-success="funcao_sucesso"
  callback-method-error="funcao_falha"></div>

  <div align="left"><h3>'.JText::_('VMPAYMENT_MOIP_TRANSACTION_TITLE').'</h3></div>
  <div>

   <input type="hidden" value="" name="forma_pagamento" id="forma_pagamento"/>
   <input type="hidden" value="" name="tipo_pagamento" id="tipo_pagamento"/>

   <dl id="system-message-cartao" class="system-message-cartao">
       <dd class="message error" id="div_erro" style="display:none">
        <ul>
         <li id="div_erro_conteudo"></li>
     </ul>
 </dd>
</dl>

<div align="left" class="subtitulo_cartao">
    <div style="float:right"><img src="'.$this->url_imagens.'/moip.jpg" border="0"/>
    </div>
</div>

<div id="container_moip" class="div_pagamentos">';

   if ($method->ativar_boleto) {
    $conteudo .= $this->getPagamentoBoleto();
}

if ($method->ativar_cartao) {
    $conteudo .= $this->getPagamentoCartao($method, $order);
}

if ($method->ativar_debito) {
    $conteudo .= $this->getPagamentoDebito($method, $order);
}

$conteudo .= "</div>
<br style='clear:both'/>

<script language='javascript'>
    jQuery('#container_moip form:first').show();
    jQuery('#container_moip input[type=radio][name=toggle_pagamentos]:first').attr('checked','checked');
</script>";

return $conteudo;
}

public function getPagamentoBoleto() {
  $html = '
  <div id="div_boleto">
      <div><h4 class="titulo_toggle">
       <input type="radio" name="toggle_pagamentos" id="toggle_boleto" value="boleto" onclick="efeito_divs(\'div_boleto\',\'div_cartao\',\'div_debito\')" style="float: left;margin: 0 5px; />
       <label for="toggle_boleto">'.JText::_('VMPAYMENT_MOIP_TRANSACTION_CREDIT_BOLETO').'</label></h4>
   </div>
   <form name="pagamento_boleto" onsubmit="return submeter_boleto(this)" id="pagamento_boleto"  style="display:none">
      <ul>
       <li>
        <input type="hidden" value="1" id="tipo_pgto_boleto" name="tipo_pgto_boleto" /><img src="'.$this->url_imagens.'bradesco_boleto.jpg"/>
    </li>
</ul>
<br style="clear:both"/>
<input type="submit" class="buttonMoIP" value="'.JText::_('VMPAYMENT_MOIP_TRANSACTION_BUTTON_BOLETO').'" />
</form>
</div>
<br style="clear:both"/>';
return $html;
}

public function getPagamentoCartao($method, $order) {
  $order_total    = round($order['details']['BT']->order_total,2);

  $cartoes_aceitos = array();
  $method->cartao_visa?$cartoes_aceitos[] = 'visa':'';
  $method->cartao_master==1?$cartoes_aceitos[] = 'master':'';
  $method->cartao_elo==1?$cartoes_aceitos[] = 'elo':'';
  $method->cartao_diners==1?$cartoes_aceitos[] = 'diners':'';
  $method->cartao_discover==1?$cartoes_aceitos[] = 'discover':'';
  $method->cartao_amex==1?$cartoes_aceitos[] = 'amex':'';
  $method->cartao_hipercard==1?$cartoes_aceitos[] = 'hipercard':'';

  $html ='
  <div id="div_cartao">
   <div><h4 class="titulo_toggle">
    <input type="radio" name="toggle_pagamentos" value="cartao" id="toggle_cartao" onclick="efeito_divs(\'div_cartao\',\'div_boleto\',\'div_debito\')" style="float: left;margin: 0 5px; />
    <label for="toggle_cartao">'.JText::_('VMPAYMENT_MOIP_TRANSACTION_CREDIT_CARD').'</label>
</h4>
</div>
<form name="pagamento_cartao" onsubmit="return submeter_cartao(this)" id="pagamento_cartao"  style="display:none">
  <ul>
    <!--  cartões -->
    <li>Cartões de crédito:</li>
    <li><ul class="cards">';
     foreach($cartoes_aceitos as $v) {
      $html .= "<li><label for=\"tipo_".$v."\"><input type=\"radio\" name=\"tipo_pgto\" style=\"width:15px\" id=\"tipo_".$v."\" value=\"".$v."\" onclick=\"show_parcelas(this.value)\" /><img src=\"".$this->url_imagens.$v."_cartao.jpg\" border=\"0\" align=\"absmiddle\" onclick=\"marcar_radio('tipo_".$v."');show_parcelas('".$v."');\" /></label></li>";
  }
  $html .= '
</ul>
</li>
<li>
 <label for="name_on_card">'.JText::_('VMPAYMENT_MOIP_TRANSACTION_CARD_OWNER').'</label>
 <input name="name_on_card" id="name_on_card" type="text">
</li>
'.$this->getCamposCartao($order, $method).'

<li>
 <label for="card_number">'.JText::_('VMPAYMENT_MOIP_TRANSACTION_CARD_NUMBER').'</label>
 <input name="card_number" id="card_number" type="text" maxlength="16"/>
</li>

<li class="vertical">
 <ul>
  <li>
   <label for="expiry_date">'.JText::_('VMPAYMENT_MOIP_TRANSACTION_EXPIRY_DATE').'</label>
   <input name="expiry_date" id="expiry_date" maxlength="5" type="text" size="5" style="width: 68px"/>
</li>

<li>
   <label for="cvv">'.JText::_('VMPAYMENT_MOIP_TRANSACTION_VERIFY_NUMBER2').'</label>
   <input name="cvv" id="cvv" maxlength="4" type="text" size="3" style="width: 68px"/>
</li>
</ul>
</li>

<li style="display: none; opacity: 0;" class="vertical">
 <ul>
  <li>
   <label for="issue_date">'.JText::_('VMPAYMENT_MOIP_TRANSACTION_EXPIRY_DATE').' </label>
   <input name="issue_date" id="issue_date" maxlength="5" type="text" size="5" style="width: 68px"/>
</li>

<li>
   <span class="or">ou</span>
   <label for="issue_number">'.JText::_('VMPAYMENT_MOIP_TRANSACTION_VERIFY_NUMBER').'</label>
   <input name="issue_number" id="issue_number" maxlength="2" type="text" size="2" style="width: 68px"/>
</li>
</ul>
</li>
<li>Escolha em quantas vezes pagar:</li>';
$html .= "
<li>
  <div align=\"left\" style=\"padding: 15px;\" class=\"subtitulo_cartao\"><b>".JText::_('VMPAYMENT_MOIP_TRANSACTION_CREDIT_CARD_PARCELA')."</b></div>
  <!-- parcelas credito -->
  <ul>";
      $html .= $this->calculaParcelasCredito($method, $order_total,'div_visa');
      $html .= $this->calculaParcelasCredito($method, $order_total,'div_master');
      $html .= $this->calculaParcelasCredito($method, $order_total,'div_hipercard');
      $html .= $this->calculaParcelasCredito($method, $order_total,'div_elo');
      $html .= $this->calculaParcelasCredito($method, $order_total,'div_amex');
      $html .= $this->calculaParcelasCredito($method, $order_total,'div_diners');
      $html .= "
  </ul>
</li>
</ul>
<br style='clear:both'/>
<input type='submit' class='buttonMoIP' value='".JText::_('VMPAYMENT_MOIP_TRANSACTION_BUTTON')."'  />
</form>
</div>
<br style='clear:both'/>
";
return $html;
}


public function getPagamentoDebito($method, $order) {
  $order_total    = round($order['details']['BT']->order_total,2);
  $debitos_aceitos = array();
  $method->debito_bb?$debitos_aceitos[] = 'bb':'';
  $method->debito_bradesco==1?$debitos_aceitos[] = 'bradesco':'';
  $method->debito_banrisul==1?$debitos_aceitos[] = 'banrisul':'';
  $method->debito_itau==1?$debitos_aceitos[] = 'itau':'';

  $html ='
  <div id="div_debito">
   <div><h4 class="titulo_toggle">
    <input type="radio" name="toggle_pagamentos" id="toggle_debito" value="debito" onclick="efeito_divs(\'div_debito\',\'div_boleto\',\'div_cartao\')" style="float: left;margin: 0 5px; />
    <label for="toggle_debito">'.JText::_('VMPAYMENT_MOIP_TRANSACTION_DEBIT').'</label>
</h4>
</div>
<form name="pagamento_debito" onsubmit="return submeter_debito(this)" id="pagamento_debito"  style="display:none">
  <ul>
    <li>
     <li><ul class="cards">';
         foreach($debitos_aceitos as $v) {
          $html .= "<li><label for=\"tipo_".$v."\"><input type=\"radio\" name=\"tipo_pgto_debito\" style=\"width:15px\" id=\"tipo_".$v."\" value=\"".$v."\" /><img src=\"".$this->url_imagens.$v."_debito.jpg\" border=\"0\" align=\"absmiddle\" /></label></li>";
      }
      $html .= "</ul>
  </li>
</li>
</ul>
<br style='clear:both'/>
<input type='submit' class='buttonMoIP' value='".JText::_('VMPAYMENT_MOIP_TRANSACTION_BUTTON')."'  />
</form>
</div>
<br style='clear:both'/>";
return $html;
}


  /**
  * Exibe os campos como hidden se não tiverem sido cadastrados no Virtuemart por padrão
  **/
  public function getCamposCartao($order, $method) {
    $o = $order['details']['BT'];
    $html = '';

    // configuração dos campos
        $campo_cpf          = $method->campo_cpf;
        $campo_data_nascimento  = $method->campo_data_nascimento;
        $campo_telefone     = $method->campo_telefone;

        $valor_cpf = '';
        if (isset($o->$campo_cpf) and $o->$campo_cpf != '') {
           $valor_cpf  = 'value="'.$o->$campo_cpf.'"';
       }
       $html .='<li>
       <label for="cpf_titular">'.JText::_('VMPAYMENT_MOIP_TRANSACTION_CPF').'</label>
       <input name="cpf_titular" id="cpf_titular" type="text" maxlength="14" style="width: 135px" '.$valor_cpf.'/>
   </li> ';

   $valor_data_nascimento  = '';
   if (isset($o->$campo_data_nascimento) and $o->$campo_data_nascimento != '') {
       $valor_data_nascimento  = 'value="'.$o->$campo_data_nascimento.'"';
   }
   $html .='<li>
   <label for="nascimento_titular">'.JText::_('VMPAYMENT_MOIP_TRANSACTION_BIRTHDAY_DATE').'</label>
   <input name="nascimento_titular" id="nascimento_titular" type="text" size="15" maxlength="10" style="width:100px" '.$valor_data_nascimento.'/>
</li> ';

$valor_telefone = '';
if (isset($o->$campo_telefone) and $o->$campo_telefone != '') {
   $valor_telefone  = 'value="'.$o->$campo_telefone.'"';
}
$html .='<li>
<label for="telefone_titular">'.JText::_('VMPAYMENT_MOIP_TRANSACTION_PHONE').' </label>
<input name="telefone_titular" id="telefone_titular" type="text" size="25" maxlength="15" style="width: 135px" '.$valor_telefone.'/>
</li> ';
return $html;
}

  // grava os dados do retorno da Transação
public function gravaDadosRetorno($method, $status=0, $msg_status='', $status_pagamento="") {

  $this->timestamp = date('Y-m-d').'T'.date('H:i:s');
    // recupera as informações do pagamento
  $db = JFactory::getDBO();
  $query = 'SELECT payment_name, payment_order_total, payment_currency, virtuemart_paymentmethod_id
  FROM `' . $this->_tablename . '`
  WHERE order_number = "'.$this->order_id.'"';
  $db->setQuery($query);
  $pagamento = $db->loadObjectList();

  $cartao_bandeira  = JRequest::getVar('cartao_bandeira','');
  $cofre          = JRequest::getVar('cofre','');
  $parcelas         = JRequest::getVar('parcelas','');
  $tipo_pagamento   = JRequest::getVar('tipo_pagamento','');

  $forma_pagamento = $tipo_pagamento.' - '.$cartao_bandeira.($parcelas!=''?' - '.$parcelas.'x ':'');

  $log = $this->timestamp.'|'.$this->codigo_moip.'|'.$msg_status.'|'.$tipo_pagamento.'|'.$forma_pagamento.'|'.$pagamento[0]->payment_order_total;

  if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber ($this->order_id))) {
   return NULL;
}

$response_fields = array();
$response_fields['virtuemart_order_id']   = $virtuemart_order_id;
$response_fields['codigo_moip']         = $this->codigo_moip;
$response_fields['cofre']           = $cofre;
$response_fields['type_transaction']    = $forma_pagamento;
$response_fields['log']               = $log;
$response_fields['status']            = $status_pagamento;
$response_fields['msg_status']        = $msg_status;
$response_fields['order_number']      = $this->order_id;

$response_fields['payment_name']              = $pagamento[0]->payment_name;
$response_fields['payment_currency']            = $pagamento[0]->payment_currency;
$response_fields['payment_order_total']           = $pagamento[0]->payment_order_total;
$response_fields['virtuemart_paymentmethod_id']   = $pagamento[0]->virtuemart_paymentmethod_id;

$this->storePSPluginInternalData($response_fields, 'virtuemart_order_id', true);
}


function plgVmgetPaymentCurrency($virtuemart_paymentmethod_id, &$paymentCurrencyId) {

  if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
      return null; // Another method was selected, do nothing
    }
    if (!$this->selectedThisElement($method->payment_element)) {
      return false;
    }
    $this->getPaymentCurrency($method);
    $paymentCurrencyId = $method->payment_currency;
  }

    /**
     * Display stored payment data for an order
     *
     */
    function plgVmOnShowOrderBEPayment($virtuemart_order_id, $virtuemart_payment_id) {
        if (!$this->selectedThisByMethodId($virtuemart_payment_id)) {
            return null; // Another method was selected, do nothing
        }

        $db = JFactory::getDBO();
        $q = 'SELECT * FROM `' . $this->_tablename . '` '
        . 'WHERE `virtuemart_order_id` = ' . $virtuemart_order_id;
        $db->setQuery($q);
        if (!($paymentTable = $db->loadObject())) {
            vmWarn(500, $q . " " . $db->getErrorMsg());
            return '';
        }
        $this->getPaymentCurrency($paymentTable);

        $html = '<table class="adminlist">' . "\n";
        $html .=$this->getHtmlHeaderBE();

        $html .= $this->getHtmlRowBE('MOIP_PAYMENT_NAME', 'MoIP');
        $html .= $this->getHtmlRowBE('MOIP_PAYMENT_DATE', $paymentTable->modified_on);
        $html .= $this->getHtmlRowBE('MOIP_CODIGO_MOIP', $paymentTable->codigo_moip);
        $html .= $this->getHtmlRowBE('MOIP_STATUS', $paymentTable->status . ' - ' . $paymentTable->msg_status);
        if (!empty($paymentTable->cofre))
           $html .= $this->getHtmlRowBE('MOIP_COFRE', $paymentTable->cofre);
       $html .= $this->getHtmlRowBE('MOIP_TOTAL_CURRENCY', $paymentTable->payment_order_total . ' ' . $paymentTable->payment_currency);
       $html .= $this->getHtmlRowBE('MOIP_TYPE_TRANSACTION', $paymentTable->type_transaction);

       if (!empty($paymentTable->nome_titular_cartao))
           $html .= $this->getHtmlRowBE('MOIP_NOME_TITULAR_CARTAO', $paymentTable->nome_titular_cartao);
       if (!empty($paymentTable->nascimento_titular_cartao))
           $html .= $this->getHtmlRowBE('MOIP_NASCIMENTO_TITULAR_CARTAO', $paymentTable->nascimento_titular_cartao);
       if (!empty($paymentTable->telefone_titular_cartao))
           $html .= $this->getHtmlRowBE('MOIP_TELEFONE_TITULAR_CARTAO', $paymentTable->telefone_titular_cartao);
       if (!empty($paymentTable->cpf_titular_cartao))
           $html .= $this->getHtmlRowBE('MOIP_CPF_TITULAR_CARTAO', $paymentTable->cpf_titular_cartao);
       $html .= $this->getHtmlRowBE('MOIP_LOG', $paymentTable->log);
       $html .= '</table>' . "\n";
       return $html;
   }

   function getCosts(VirtueMartCart $cart, $method, $cart_prices) {
    if (preg_match('/%$/', $method->cost_percent_total)) {
        $cost_percent_total = substr($method->cost_percent_total, 0, -1);
    } else {
        $cost_percent_total = $method->cost_percent_total;
    }
    return ($method->cost_per_transaction + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01));
}

function setCartPrices (VirtueMartCart $cart, &$cart_prices, $method) {

        if ($method->modo_calculo_desconto == '2') {
            return parent::setCartPrices($cart, $cart_prices, $method);
        } else {

            if (!class_exists ('calculationHelper')) {
                require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'calculationh.php');
            }
            $_psType = ucfirst ($this->_psType);
            $calculator = calculationHelper::getInstance ();

            $cart_prices[$this->_psType . 'Value'] = $calculator->roundInternal ($this->getCosts ($cart, $method, $cart_prices), 'salesPrice');

            /*
            if($this->_psType=='payment'){
                $cartTotalAmountOrig=$this->getCartAmount($cart_prices);
                $cartTotalAmount=($cartTotalAmountOrig + $method->cost_per_transaction) / (1 -($method->cost_percent_total * 0.01));
                $cart_prices[$this->_psType . 'Value'] = $cartTotalAmount - $cartTotalAmountOrig;
            }
            */

            $taxrules = array();
            if(isset($method->tax_id) and (int)$method->tax_id === -1){

            } else if (!empty($method->tax_id)) {
                $cart_prices[$this->_psType . '_calc_id'] = $method->tax_id;

                $db = JFactory::getDBO ();
                $q = 'SELECT * FROM #__virtuemart_calcs WHERE `virtuemart_calc_id`="' . $method->tax_id . '" ';
                $db->setQuery ($q);
                $taxrules = $db->loadAssocList ();

                if(!empty($taxrules) ){
                    foreach($taxrules as &$rule){
                        if(!isset($rule['subTotal'])) $rule['subTotal'] = 0;
                        if(!isset($rule['taxAmount'])) $rule['taxAmount'] = 0;
                        $rule['subTotalOld'] = $rule['subTotal'];
                        $rule['taxAmountOld'] = $rule['taxAmount'];
                        $rule['taxAmount'] = 0;
                        $rule['subTotal'] = $cart_prices[$this->_psType . 'Value'];
                    }
                }
            } else {
                $taxrules = array_merge($calculator->_cartData['VatTax'],$calculator->_cartData['taxRulesBill']);

                if(!empty($taxrules) ){
                    $denominator = 0.0;
                    foreach($taxrules as &$rule){
                        //$rule['numerator'] = $rule['calc_value']/100.0 * $rule['subTotal'];
                        if(!isset($rule['subTotal'])) $rule['subTotal'] = 0;
                        if(!isset($rule['taxAmount'])) $rule['taxAmount'] = 0;
                        $denominator += ($rule['subTotal']-$rule['taxAmount']);
                        $rule['subTotalOld'] = $rule['subTotal'];
                        $rule['subTotal'] = 0;
                        $rule['taxAmountOld'] = $rule['taxAmount'];
                        $rule['taxAmount'] = 0;
                        //$rule['subTotal'] = $cart_prices[$this->_psType . 'Value'];
                    }
                    if(empty($denominator)){
                        $denominator = 1;
                    }

                    foreach($taxrules as &$rule){
                        $frac = ($rule['subTotalOld']-$rule['taxAmountOld'])/$denominator;
                        $rule['subTotal'] = $cart_prices[$this->_psType . 'Value'] * $frac;
                        vmdebug('Part $denominator '.$denominator.' $frac '.$frac,$rule['subTotal']);
                    }
                }
            }


            if(empty($method->cost_per_transaction)) $method->cost_per_transaction = 0.0;
            if(empty($method->cost_percent_total)) $method->cost_percent_total = 0.0;

            if (count ($taxrules) > 0 ) {

                $cart_prices['salesPrice' . $_psType] = $calculator->roundInternal ($calculator->executeCalculation ($taxrules, $cart_prices[$this->_psType . 'Value'],true,false), 'salesPrice');
                //vmdebug('I am in '.get_class($this).' and have this rules now',$taxrules,$cart_prices[$this->_psType . 'Value'],$cart_prices['salesPrice' . $_psType]);
                $cart_prices[$this->_psType . 'Tax'] = $calculator->roundInternal (($cart_prices['salesPrice' . $_psType] -  $cart_prices[$this->_psType . 'Value']), 'salesPrice');
                reset($taxrules);
                $taxrule =  current($taxrules);
                $cart_prices[$this->_psType . '_calc_id'] = $taxrule['virtuemart_calc_id'];

                foreach($taxrules as &$rule){
                    if(isset($rule['subTotalOld'])) $rule['subTotal'] += $rule['subTotalOld'];
                    if(isset($rule['taxAmountOld'])) $rule['taxAmount'] += $rule['taxAmountOld'];
                }

            } else {
                $cart_prices['salesPrice' . $_psType] = $cart_prices[$this->_psType . 'Value'];
                $cart_prices[$this->_psType . 'Tax'] = 0;
                $cart_prices[$this->_psType . '_calc_id'] = 0;
            }


            return $cart_prices['salesPrice' . $_psType];
        }
    }
    /**
     * Check if the payment conditions are fulfilled for this payment method
     * @author: Valerie Isaksen
     *
     * @param $cart_prices: cart prices
     * @param $payment
     * @return true: if the conditions are fulfilled, false otherwise
     *
     */
    protected function checkConditions($cart, $method, $cart_prices) {

    //  $params = new JParameter($payment->payment_params);
        $address = (($cart->ST == 0) ? $cart->BT : $cart->ST);

        $amount = $cart_prices['salesPrice'];
        $amount_cond = ($amount >= $method->min_amount AND $amount <= $method->max_amount
            OR
            ($method->min_amount <= $amount AND ($method->max_amount == 0) ));
        if (!$amount_cond) {
            return false;
        }
        $countries = array();
        if (!empty($method->countries)) {
            if (!is_array($method->countries)) {
                $countries[0] = $method->countries;
            } else {
                $countries = $method->countries;
            }
        }

        // probably did not gave his BT:ST address
        if (!is_array($address)) {
            $address = array();
            $address['virtuemart_country_id'] = 0;
        }

        if (!isset($address['virtuemart_country_id']))
            $address['virtuemart_country_id'] = 0;
        if (count($countries) == 0 || in_array($address['virtuemart_country_id'], $countries) || count($countries) == 0) {
            return true;
        }

        return false;
    }

    /*
     * We must reimplement this triggers for joomla 1.7
     */

    /**
     * Create the table for this plugin if it does not yet exist.
     * This functions checks if the called plugin is active one.
     * When yes it is calling the moip method to create the tables
     * @author Valérie Isaksen
     *
     */
    function plgVmOnStoreInstallPaymentPluginTable($jplugin_id) {
        return $this->onStoreInstallPluginTable($jplugin_id);
    }

    /**
     * This event is fired after the payment method has been selected. It can be used to store
     * additional payment info in the cart.
     *
     * @author Max Milbers
     * @author Valérie isaksen
     *
     * @param VirtueMartCart $cart: the actual cart
     * @return null if the payment was not selected, true if the data is valid, error message if the data is not vlaid
     *
     */
    public function plgVmOnSelectCheckPayment(VirtueMartCart $cart) {
        return $this->OnSelectCheck($cart);
    }

    /**
     * plgVmDisplayListFEPayment
     * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for exampel
     *
     * @param object $cart Cart object
     * @param integer $selected ID of the method selected
     * @return boolean True on succes, false on failures, null when this plugin was not selected.
     * On errors, JError::raiseWarning (or JError::raiseError) must be used to set a message.
     *
     * @author Valerie Isaksen
     * @author Max Milbers
     */
    public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn) {
        return $this->displayListFE($cart, $selected, $htmlIn);
    }

    /*
     * plgVmonSelectedCalculatePricePayment
     * Calculate the price (value, tax_id) of the selected method
     * It is called by the calculator
     * This function does NOT to be reimplemented. If not reimplemented, then the default values from this function are taken.
     * @author Valerie Isaksen
     * @cart: VirtueMartCart the current cart
     * @cart_prices: array the new cart prices
     * @return null if the method was not selected, false if the shiiping rate is not valid any more, true otherwise
     *
     *
     */

    public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
        return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
    }

    /**
     * plgVmOnCheckAutomaticSelectedPayment
     * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
     * The plugin must check first if it is the correct type
     * @author Valerie Isaksen
     * @param VirtueMartCart cart: the cart object
     * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
     *
     */
    function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array()) {
        return $this->onCheckAutomaticSelected($cart, $cart_prices);
    }

    /**
     * This method is fired when showing the order details in the frontend.
     * It displays the method-specific data.
     *
     * @param integer $order_id The order ID
     * @return mixed Null for methods that aren't active, text (HTML) otherwise
     * @author Max Milbers
     * @author Valerie Isaksen
     */
    public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {
      $mainframe = JFactory::getApplication();
      if($mainframe->isAdmin()) {
       return;
   }

   if (!($method = $this->getVmPluginMethod ($virtuemart_paymentmethod_id))) {
      return NULL; // Another method was selected, do nothing
    }
    if (!$this->selectedThisElement ($method->payment_element)) {
      return FALSE;
    }

    $view = JRequest::getVar('view');
    if ($view=='orders') {
      $orderModel   = VmModel::getModel('orders');
      $orderDetails   = $orderModel->getOrder($virtuemart_order_id);
      $order_id     = $orderDetails['details']['BT']->order_number;
      $virtuemart_paymentmethod_id = $orderDetails['details']['BT']->virtuemart_paymentmethod_id;

      JHTML::_('behavior.modal');
      $url_recibo = JRoute::_('/index.php?option=com_virtuemart&view=pluginresponse&tmpl=component&task=pluginresponsereceived&on='.$order_id.'&pm='.$virtuemart_paymentmethod_id);
      $html = '<br /><b><a href="'.$url_recibo.'" class="modal" rel="{size: {x: 700, y: 500}, handler:\'iframe\'}" >Clique aqui para visualizar o status detalhado da transação no MoIP</a></b> <br /><br />';
      JFactory::getApplication()->enqueueMessage(
        $html, 'alert'
               );
    }
        $this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
    }

    /**
     * This event is fired during the checkout process. It can be used to validate the
     * method data as entered by the user.
     *
     * @return boolean True when the data was valid, false otherwise. If the plugin is not activated, it should return null.
     * @author Max Milbers

      public function plgVmOnCheckoutCheckDataPayment(  VirtueMartCart $cart) {
      return null;
      }
     */

    /**
     * This method is fired when showing when priting an Order
     * It displays the the payment method-specific data.
     *
     * @param integer $_virtuemart_order_id The order ID
     * @param integer $method_id  method used for this order
     * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
     * @author Valerie Isaksen
     */
    function plgVmonShowOrderPrintPayment($order_number, $method_id) {
        return $this->onShowOrderPrint($order_number, $method_id);
    }

    function plgVmDeclarePluginParamsPayment($name, $id, &$data) {
        return $this->declarePluginParams('payment', $name, $id, $data);
    }

    function plgVmSetOnTablePluginParamsPayment($name, $id, &$table) {
        return $this->setOnTablePluginParams($name, $id, $table);
    }

    //Notice: We only need to add the events, which should work for the specific plugin, when an event is doing nothing, it should not be added

    /**
     * Save updated order data to the method specific table
     *
     * @param array $_formData Form data
     * @return mixed, True on success, false on failures (the rest of the save-process will be
     * skipped!), or null when this method is not actived.
     * @author Oscar van Eijk
     *
      public function plgVmOnUpdateOrderPayment(  $_formData) {
      return null;
      }

      /**
     * Save updated orderline data to the method specific table
     *
     * @param array $_formData Form data
     * @return mixed, True on success, false on failures (the rest of the save-process will be
     * skipped!), or null when this method is not actived.
     * @author Oscar van Eijk
     *
      public function plgVmOnUpdateOrderLine(  $_formData) {
      return null;
      }

      /**
     * plgVmOnEditOrderLineBE
     * This method is fired when editing the order line details in the backend.
     * It can be used to add line specific package codes
     *
     * @param integer $_orderId The order ID
     * @param integer $_lineId
     * @return mixed Null for method that aren't active, text (HTML) otherwise
     * @author Oscar van Eijk
     *
      public function plgVmOnEditOrderLineBEPayment(  $_orderId, $_lineId) {
      return null;
      }

      /**
     * This method is fired when showing the order details in the frontend, for every orderline.
     * It can be used to display line specific package codes, e.g. with a link to external tracking and
     * tracing systems
     *
     * @param integer $_orderId The order ID
     * @param integer $_lineId
     * @return mixed Null for method that aren't active, text (HTML) otherwise
     * @author Oscar van Eijk
     *
      public function plgVmOnShowOrderLineFE(  $_orderId, $_lineId) {
      return null;
      }

      /**
     * This event is fired when the  method notifies you when an event occurs that affects the order.
     * Typically,  the events  represents for payment authorizations, Fraud Management Filter actions and other actions,
     * such as refunds, disputes, and chargebacks.
     *
     * NOTE for Plugin developers:
     *  If the plugin is NOT actually executed (not the selected payment method), this method must return NULL
     *
     * @param $return_context: it was given and sent in the payment form. The notification should return it back.
     * Used to know which cart should be emptied, in case it is still in the session.
     * @param int $virtuemart_order_id : payment  order id
     * @param char $new_status : new_status for this order id.
     * @return mixed Null when this method was not selected, otherwise the true or false
     *
     * @author Valerie Isaksen
     *
     *
      public function plgVmOnPaymentNotification() {
      return null;
      }
  */
      function plgVmOnPaymentNotification() {

          if (!class_exists('VirtueMartCart'))
           require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
       if (!class_exists('shopFunctionsF'))
           require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
       if (!class_exists('VirtueMartModelOrders'))
           require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );

       $moip = JRequest::getVar('moip');
       if (!isset($moip)) {
           return;
       }

    // trata os retorno no Virtuemart ( atualizando status )
       $this->order_id = $order_number = JRequest::getVar('order_id');
       $pm = JRequest::getVar('pm');

       $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);
       $this->logInfo('plgVmOnPaymentNotification: virtuemart_order_id  found ' . $virtuemart_order_id, 'message');

       if (!$virtuemart_order_id) {
           return;
       }
       $vendorId = 0;
       $payment = $this->getDataByOrderId($virtuemart_order_id);
       if($payment->payment_name == '') {
           return false;
       }

    // recupera as informações do método de pagamento
       $method = $this->getVmPluginMethod($pm);
       if (!$this->selectedThisElement($method->payment_element)) {
           return false;
       }

       if (!$payment) {
           $this->logInfo('getDataByOrderId payment not found: exit ', 'ERROR');
           return null;
       }

       $status_pagamento  = JRequest::getVar('StatusPagamento');
       $mensagem      = JRequest::getVar('Mensagem');
       $status          = JRequest::getVar('Status');
       $total_pago      = JRequest::getVar('TotalPago');
       $forma_pagamento = JRequest::getVar('FormaPagamento');
       $tipo_pagamento    = JRequest::getVar('TipoPagamento');
       $url_redirecionar    = JRequest::getVar('Url');
       $timestamp       = date('Y-m-d').'T'.date('H:i:s');

    // recupera as informações do pagamento
       $db = JFactory::getDBO();
       $query = 'SELECT payment_name, payment_order_total, payment_currency, virtuemart_paymentmethod_id
       FROM `' . $this->_tablename . '`
       WHERE order_number = "'.$this->order_id.'"';
       $db->setQuery($query);
       $pagamento = $db->loadObjectList();

    //if ($status_pagamento=='Sucesso') {
       $this->codigo_moip = $codigo_moip = JRequest::getVar('CodigoMoIP',0);
       if ($codigo_moip=='undefined') {
        $this->codigo_moip = $codigo_moip = '';
    }
    $log = $timestamp.'|'.$this->codigo_moip.'|'.$mensagem.'|'.$tipo_pagamento.'|'.$forma_pagamento.'|'.$pagamento[0]->payment_order_total;

    if ($status == 'Autorizado') {
        $novo_status = '1';
    } else {
        $novo_status = '0';
    }
    $arr_status = array (
        "EmAnalise"   => "Pagamento em análise de risco",
        "Autorizado"  => "Pagamento autorizado.",
        "Iniciado"    => "Pagamento foi iniciado, porem sem confirmação de finalização até o momento",
        "Cancelado"   => "Pagamento foi cancelado",
        );

    $response_fields['payment_currency']            = $pagamento[0]->payment_currency;
    $response_fields['payment_order_total']           = $pagamento[0]->payment_order_total;
      //$response_fields['virtuemart_paymentmethod_id']     = $pagamento[0]->virtuemart_paymentmethod_id;

    $response_fields['status']              = $novo_status;
    $response_fields['msg_status']          = $arr_status[$status];
    $response_fields['virtuemart_paymentmethod_id'] = $pm;
    $response_fields['payment_name']        = $payment->payment_name;
    $response_fields['order_number']        = $order_number;
    $response_fields['virtuemart_order_id']     = $virtuemart_order_id;
    $response_fields['type_transaction']      = $forma_pagamento.' - '.$tipo_pagamento;
    $response_fields['log']                 = $log;

    if (!empty($codigo_moip) ) {
        $response_fields['codigo_moip']           = $codigo_moip;
    }
    if (!empty($url_redirecionar) ) {
        $response_fields['url_redirecionar']        = $url_redirecionar;
    }
    $this->storePSPluginInternalData($response_fields, 'virtuemart_order_id', true);

      // notificação do pagamento realizado
    $notificacao = "<b>".JText::_('VMPAYMENT_MOIP_NOTIFY_TRANSACTION')." - ".$forma_pagamento."</b>\n";
    $notificacao .= JText::_('VMPAYMENT_MOIP_NOTIFY_CODIGO_MOIP')." ".$codigo_moip."\n";
    $notificacao .= JText::_('VMPAYMENT_MOIP_NOTIFY_PEDIDO')." ".$order_number."\n";
    $notificacao .= "<hr />";
    $notificacao .= JText::_('VMPAYMENT_MOIP_NOTIFY_STATUS')." <b>".(($status==1)?JText::_('VMPAYMENT_MOIP_NOTIFY_PAID'):JText::_('VMPAYMENT_MOIP_NOTIFY_NOTPAID'))."</b>\n";
    $notificacao .= JText::_('VMPAYMENT_MOIP_NOTIFY_TYPE_TRANSACTION')." <b>".$response_fields['type_transaction']."</b>\n";
    $notificacao .= JText::_('VMPAYMENT_MOIP_NOTIFY_TYPE_MESSAGE')." <b>".$mensagem." </b>\n";
    $notificacao .= JText::_('VMPAYMENT_MOIP_NOTIFY_ORDER_TOTAL')." <b>R$ ".number_format($this->valor,2,',','.')."</b> \n";
    $notificacao .= "\n\n";
    $notificacao .= JText::_('VMPAYMENT_MOIP_NOTIFY_AUTHENTICATE')."<a href='http://www.moip.com.br'>Moip</a>";

    if ($virtuemart_order_id) {
        // send the email only if payment has been accepted
        if (!class_exists('VirtueMartModelOrders'))
            require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
        $modelOrder = new VirtueMartModelOrders();
        $orderitems = $modelOrder->getOrder($virtuemart_order_id);
        $nb_history = count($orderitems['history']);

        $order = array();
        $order['order_status']      = $this->_getPaymentStatus($method, $status);
        $order['virtuemart_order_id']   = $virtuemart_order_id;
        $order['comments']        = $notificacao;
        $order['customer_notified']   = 1;

        $modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);
        if ($nb_history == 1) {
         if (!class_exists('shopFunctionsF'))
          require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');

      $this->logInfo('Notification, sentOrderConfirmedEmail ' . $order_number. ' '. $order['order_status'], 'message');
  }
}

$cart = VirtueMartCart::getCart();
$cart->emptyCart();
    //}
return true;
}

      /**
     * plgVmOnPaymentResponseReceived
     * This event is fired when the  method returns to the shop after the transaction
     *
     *  the method itself should send in the URL the parameters needed
     * NOTE for Plugin developers:
     *  If the plugin is NOT actually executed (not the selected payment method), this method must return NULL
     *
     * @param int $virtuemart_order_id : should return the virtuemart_order_id
     * @param text $html: the html to display
     * @return mixed Null when this method was not selected, otherwise the true or false
     *
     * @author Valerie Isaksen
     *
     *
      function plgVmOnPaymentResponseReceived(, &$virtuemart_order_id, &$html) {
      return null;
      }
     */
      function plgVmOnPaymentResponseReceived(&$html='') {
    // notificação dos pagamentos e recibo
          if (!class_exists('VirtueMartCart'))
           require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
       if (!class_exists('shopFunctionsF'))
           require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
       if (!class_exists('VirtueMartModelOrders'))
           require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
       $moip_data = JRequest::get('post');
       vmdebug('MOIP plgVmOnPaymentResponseReceived', $moip_data);
    // the payment itself should send the parameter needed.
       $virtuemart_paymentmethod_id = JRequest::getInt('pm', 0);

       if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
      return null; // Another method was selected, do nothing
    }
    if (!$this->selectedThisElement($method->payment_element)) {
      return null;
    }

    $nasp = JRequest::getVar('nasp','');

    if ($nasp==1) {
      $order_number = $this->order_id = $order_id = JRequest::getVar('id_transacao', '');
      $status_pagamento       = JRequest::getVar('status_pagamento');
      $this->tipo_pagamento   = JRequest::getVar('tipo_pagamento');
      $cod_forma_pagamento  = JRequest::getVar('forma_pagamento');
      $this->forma_pagamento  = $this->_getFormaPagamentoRetorno($cod_forma_pagamento);
      $this->valor          = $this->reformataValor(JRequest::getVar('valor',''));
      $this->codigo_moip      = $codigoMoip = JRequest::getVar('cod_moip');
      if ($status_pagamento == 1 or $status_pagamento == 4) {
        $status = 1;
      } else {
        $status = 0;
      }
      $notificacao_status = $this->_getPaymentStatusNotificacao($method, $status_pagamento);
      $mensagem = $notificacao_status['mensagem'];

      // grava os dados
      $this->gravaDadosRetorno($method, $status, $mensagem , $status_pagamento);

      // notificação do pagamento realizado
      $notificacao = "<b>".JText::_('VMPAYMENT_MOIP_NOTIFY_TRANSACTION')." - ".$this->tipo_pagamento."</b>\n";
      $notificacao .= JText::_('VMPAYMENT_MOIP_NOTIFY_CODIGO_MOIP')." ".$this->codigo_moip."\n";
      $notificacao .= JText::_('VMPAYMENT_MOIP_NOTIFY_PEDIDO')." ".$order_number."\n";
      $notificacao .= "<hr />";
      $notificacao .= JText::_('VMPAYMENT_MOIP_NOTIFY_STATUS')." <b>".(($status==1)?JText::_('VMPAYMENT_MOIP_NOTIFY_PAID'):JText::_('VMPAYMENT_MOIP_NOTIFY_NOTPAID'))."</b>\n";
      $notificacao .= JText::_('VMPAYMENT_MOIP_NOTIFY_TYPE_TRANSACTION')." <b>".$this->tipo_pagamento.' - '.$this->forma_pagamento."</b>\n";
      $notificacao .= JText::_('VMPAYMENT_MOIP_NOTIFY_TYPE_MESSAGE')." <b>".$mensagem." </b>\n";
      $notificacao .= JText::_('VMPAYMENT_MOIP_NOTIFY_ORDER_TOTAL')." <b>R$ ".number_format($this->valor,2,',','.')."</b> \n";
      $notificacao .= "\n\n";
      $notificacao .= JText::_('VMPAYMENT_MOIP_NOTIFY_AUTHENTICATE')."<a href='http://www.moip.com.br'>Moip</a>";

      if (!empty($codigoMoip)) {
        vmdebug('plgVmOnPaymentResponseReceived', $notificacao);
        if (!class_exists('VirtueMartModelOrders'))
                    require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
                $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);

                if ($virtuemart_order_id) {
                 $modelOrder = VmModel::getModel('orders');
                 $orderitems = $modelOrder->getOrder($virtuemart_order_id);
                 $nb_history = count($orderitems['history']);

                 if ($orderitems['history'][$nb_history - 1]->order_status_code != $order['order_status']) {
                  $this->logInfo('plgVmOnPaymentResponseReceived, sentOrderConfirmedEmail ' . $order_number, 'message');

                  $order['order_status'] = $notificacao_status['status'];
                  $order['virtuemart_order_id'] = $virtuemart_order_id;
                  $order['customer_notified'] = 1;
                  $order['comments'] = $notificacao;
                  $modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);
              }
          } else {
             vmError('Dados da Moip recebidos, mas nenhum código de pedido');
             return;
         }
     }

 } else {

   $order_number = JRequest::getString('on', 0);
   $vendorId = 0;

   if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number) )) {
    return null;
}
if (!($paymentTable = $this->getDataByOrderId($virtuemart_order_id) )) {
        // JError::raiseWarning(500, $db->getErrorMsg());
    return '';
}
$payment_name = $this->renderPluginName($method);
$modelOrder = VmModel::getModel('orders');
$orderdetails = $modelOrder->getOrder($virtuemart_order_id);
$html = $this->_getPaymentResponseHtml($paymentTable, $payment_name, $orderdetails['details'], $method);

      //We delete the old stuff
      // get the correct cart / session
$cart = VirtueMartCart::getCart();
$cart->emptyCart();
}
return true;
}

function _getPaymentResponseHtml($moipTable, $payment_name, $orderDetails=null, $method=null) {
  $html = '<table>' . "\n";
  $html .= $this->getHtmlRowBE('MOIP_PAYMENT_NAME', $payment_name);
  $task = JRequest::getVar('task','');
  $img_pagamentos = array();
  $img_pagamentos['BoletoBancario - Boleto Bradesco'] = 'bradesco_boleto.jpg';
  $img_pagamentos['DebitoBancario - Bradesco']      = 'bradesco_debito.jpg';
  $img_pagamentos['DebitoBancario - BancoDoBrasil']   = 'bb_debito.jpg';
  $img_pagamentos['DebitoBancario - Banrisul']      = 'banrisul_debito.jpg';
  $img_pagamentos['DebitoBancario - Itau']        = 'itau_debito.jpg';
  if ($task == 'pluginresponsereceived') {
   JFactory::getApplication()->enqueueMessage(
    JText::_('VMPAYMENT_MOIP_CHECK_TRANSACTION')
    );

   $link_pedido = JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=orders&layout=details&order_number='.$moipTable->order_number);
   if (!empty($moipTable)) {
    $html .= $this->getHtmlRowBE('MOIP_ORDER_NUMBER', $moipTable->order_number);
    $html .= $this->getHtmlRowBE('MOIP_PAYMENT_DATE', $moipTable->modified_on);
    $html .= '<tr><td colspan="2"><br /></td></tr>';

    if ($moipTable->codigo_moip) {
     $html .= $this->getHtmlRowBE('MOIP_CODIGO_MOIP','<b>'.$moipTable->codigo_moip.'</b>');
 }

 if ($moipTable->msg_status) {
     $moip_status = '<b>'.$moipTable->status. " - " . $moipTable->msg_status.'</b><br />';
 } else {
     $moip_status = '<b>Transação em Andamento</b>';
     if ($orderDetails['BT']->order_status == $method->transacao_nao_finalizada and !$moipTable->codigo_moip) {
      $url_imagem = JURI::root().DS.'plugins'.DS.'vmpayment'.DS.'moip'.DS.'imagens'.DS;
      $url_imagem .= $img_pagamentos[$moipTable->type_transaction];
      $imagem_redirecionar = '<img src="'.$url_imagem.'" border="0"/>';
      $moip_status .= '<div style="padding: 10px"><br />Faça o pagamento clicando aqui: <br /><a target="blank" href="'.$moipTable->url_redirecionar.'">'.$imagem_redirecionar.'</a><br /><br /></div>';
  }
}

$html .= $this->getHtmlRowBE('MOIP_STATUS', $moip_status);

$html .= '<tr><td colspan="2"><br /></td></tr>';
if ($moipTable->cofre) {
 $html .= $this->getHtmlRowBE('MOIP_COFRE', $moipTable->cofre);
}

$html .= $this->getHtmlRowBE('MOIP_AMOUNT', $moipTable->payment_order_total. " " . $moipTable->payment_currency);
$html .= $this->getHtmlRowBE('MOIP_TYPE_TRANSACTION', $moipTable->type_transaction);

if ($moipTable->nome_titular_cartao) {
 $html .= $this->getHtmlRowBE('MOIP_NOME_TITULAR_CARTAO', $moipTable->nome_titular_cartao);
}
if ($moipTable->nascimento_titular_cartao) {
 $html .= $this->getHtmlRowBE('MOIP_NASCIMENTO_TITULAR_CARTAO', $moipTable->nascimento_titular_cartao);
}
if ($moipTable->telefone_titular_cartao) {
 $html .= $this->getHtmlRowBE('MOIP_TELEFONE_TITULAR_CARTAO', $moipTable->telefone_titular_cartao);
}
if ($moipTable->cpf_titular_cartao) {
 $html .= $this->getHtmlRowBE('MOIP_CPF_TITULAR_CARTAO', $moipTable->cpf_titular_cartao);
}

$html .= $this->getHtmlRowBE('MOIP_LOG', $moipTable->log);
$html .= '</table>' . "\n";
$html .= '<br />';
$tmpl = JRequest::getVar('tmpl');
if ($tmpl != 'component') {
 $html .= '<a href="'.$link_pedido.'" class="button">'.JText::_('VMPAYMENT_MOIP_ORDER_DETAILS').'</a>
 ' . "\n";
}
}
} else {
   $html .= $this->getHtmlRowBE('MOIP_ORDER_NUMBER', $this->order_id);
}
$html .= '</table>' . "\n";

return $html;
}

function plgVmOnUserPaymentCancel() {

   if (!class_exists('VirtueMartModelOrders'))
       require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );

   $order_number = JRequest::getVar('on');
   if (!$order_number)
       return false;
   $db = JFactory::getDBO();
   $query = 'SELECT ' . $this->_tablename . '.`virtuemart_order_id` FROM ' . $this->_tablename . " WHERE  `order_number`= '" . $order_number . "'";

   $db->setQuery($query);
   $virtuemart_order_id = $db->loadResult();

   if (!$virtuemart_order_id) {
    return null;
}
$this->handlePaymentUserCancel($virtuemart_order_id);

return true;
}

public function Moip_requestPost($params, $url_request, $method, $headers = array()) {

  $ch = curl_init($url_request);
  if (isset($params)) {
   curl_setopt ($ch, CURLOPT_POST, true);
   curl_setopt ($ch, CURLOPT_POSTFIELDS, $params);
}

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, 1);
curl_setopt($ch, CURLOPT_HEADER, false);

if (!empty($headers))
   curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

if ($method->modo_teste) {
 curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
 curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
}

$response = curl_exec($ch);
$responseInfo = curl_getinfo($ch);
curl_close($ch);

if (!$this->domdocument) {
   $xml =  simplexml_load_string($response);
} else {
   $dom = new DomDocument();
   $xml = $dom->loadXML(trim($response));
}

return $xml;
}

public function solicitaToken($method, $order ) {

  $base = $this->token_moip . ":" . $this->chave_moip;
  $auth = base64_encode($base);
  $headers[] = "Authorization: Basic " . $auth;

  $params = $this->getXmlConsulta($method, $order);
  $xml = $this->Moip_requestPost( $params, $this->url_request, $method, $headers );

  $arr = array(
   "status"=> $xml->Resposta->Status,
   "token"  => $xml->Resposta->Token,
   "erro" => utf8_decode($xml->Resposta->Erro)
   );
  return $arr;
}

  /**
  * Xml Consulta inicial
  **/
    function getXmlConsulta($method, $order) {

    // configuração de campos extras
      $campo_bairro = $method->campo_bairro;
      $campo_numero = $method->campo_numero;
      $campo_complemento = $method->campo_complemento;
      $campo_logradouro = $method->campo_logradouro;

      $order_total = round($order['details']["BT"]->order_total,2);
      $order_number = $order['details']["BT"]->order_number;
      $customer_name = $order["details"]["BT"]->first_name .' '. $order["details"]["BT"]->last_name;
      $order_email = $order["details"]["BT"]->email;
      $customer_number = $order["details"]["BT"]->virtuemart_user_id;
      $customer_address = $order["details"]["BT"]->$campo_logradouro;
      $customer_address_number = $order["details"]["BT"]->$campo_numero;
      $customer_address_complemento = $order["details"]["BT"]->$campo_complemento;
      $customer_bairro = $order["details"]["BT"]->$campo_bairro;
      $customer_city = $order["details"]["BT"]->city;
      $customer_state = ShopFunctions::getStateByID($order["details"]["BT"]->virtuemart_state_id, "state_2_code");

      $customer_phone = $order["details"]["BT"]->phone_1;
      $replacements = array(" ", "-", "(",")");
      $customer_phone = str_replace($replacements, "", $customer_phone);
      $customer_phone = '('.substr($customer_phone,0,2).')'.substr($customer_phone,2,4).'-'.substr($customer_phone,6,4);

      $customer_zip = $order["details"]["BT"]->zip;
      $replacements = array(" ", ".", ",", "-", ";");
      $customer_zip = str_replace($replacements, "", $customer_zip);
      $customer_zip = substr($customer_zip,0,5).'-'.substr($customer_zip,5,3);

      $this->xml_consulta = '<EnviarInstrucao>
      <InstrucaoUnica TipoValidacao="Transparente">
          <Razao>'.$method->mensagem_pagamento.' - '.($order_number).'</Razao>
          <Valores>
           <Valor moeda="BRL">'.$order_total.'</Valor>
       </Valores>
       <IdProprio>'.$order_number.'</IdProprio>
       <Pagador>
           <Nome>'.$customer_name.'</Nome>
           <Email>'.$order_email.'</Email>
           <IdPagador>'.$customer_number.'</IdPagador>
           <EnderecoCobranca>
            <Logradouro>'.$customer_address.'</Logradouro>
            <Numero>'.$customer_address_number.'</Numero>
            <Complemento>'.$customer_address_complemento.'</Complemento>
            <Bairro>'.$customer_bairro.'</Bairro>
            <Cidade>'.$customer_city.'</Cidade>
            <Estado>'.$customer_state.'</Estado>
            <Pais>BRA</Pais>
            <CEP>'.$customer_zip.'</CEP>
            <TelefoneFixo>'.$customer_phone.'</TelefoneFixo>
        </EnderecoCobranca>
    </Pagador>
    <Parcelamentos>
      '.$this->montaParcelamentos( $method ).'
  </Parcelamentos>
</InstrucaoUnica>
</EnviarInstrucao>';
return $this->xml_consulta;
}

  /**
  * Monta os parcelamentos para enviar o xml de introdução
  **/
  public function montaParcelamentos( $method ) {
    // original $method->parcelamento
    $max_parcela_sem_juros = $method->max_parcela_sem_juros;
    $max_parcela_com_juros = $method->max_parcela_com_juros;
    $taxa_credito = $method->taxa_credito;
    $taxa_parcelado = $method->taxa_parcelado;

    $xml_parc;
    // taxa para crédito a vista
    $inicio_parcelamento_juros = 2;
    if ($max_parcela_sem_juros > 0) {
      if ($max_parcela_sem_juros != 1) {
        $xml_parc .= '<Parcelamento>
        <MinimoParcelas>1</MinimoParcelas>
        <MaximoParcelas>'.$max_parcela_sem_juros.'</MaximoParcelas>
        <Juros>0</Juros>
            </Parcelamento>';
            $inicio_parcelamento_juros = $max_parcela_sem_juros + 1;
        }
    }
    if ($max_parcela_com_juros > 0) {
       $juros_parcelamento = $taxa_parcelado;
       $juros_credito_avista = $taxa_credito;
      // seta o parcelamento com juros para a primeira parcela no crédito
       if ($max_parcela_sem_juros == 0) {
         $xml_parc .= '<Parcelamento>
         <MinimoParcelas>1</MinimoParcelas>
         <MaximoParcelas>1</MaximoParcelas>
         <Juros>'.$juros_credito_avista.'</Juros>
     </Parcelamento>';
 }
      // seta o parcelamento com juros para as parcelas restantes
 $xml_parc .='<Parcelamento>
 <MinimoParcelas>'.$inicio_parcelamento_juros.'</MinimoParcelas>
 <MaximoParcelas>'.$max_parcela_com_juros.'</MaximoParcelas>
 <Juros>'.$juros_parcelamento.'</Juros>
</Parcelamento>';
}
return $xml_parc;

    /*
    $arr_parcelas = explode(';',$parcelamento);
    if (is_array($arr_parcelas)) {
      $xml_parcelamento = '';
      foreach($arr_parcelas as $v){
        $parcela = explode(',',$v);
        if (isset($parcela[0]) and $parcela[0] != '') {
          $xml_parc = "<Parcelamento>
          <MinimoParcelas>".$parcela[0]."</MinimoParcelas>
          <MaximoParcelas>".$parcela[1]."</MaximoParcelas>
          ";
          if ($parcela[2]=='true') {
            $xml_parc .= "<Repassar>true</Repassar>";
          } else {
            $xml_parc .= "<Juros>".$parcela[2]."</Juros>";
          }
          $xml_parc .= "</Parcelamento>";
          $xml_parcelamento .= $xml_parc;
        }
      }
      return $xml_parcelamento;
    } else {
      return '';
    }*/
  }

  public function setaUrlRequest($valor){
    $this->url_request = $valor;
  }

  public function setaUrlJs($valor){
    $this->url_js = $valor;
  }

  public function getChaveMoip($method) {
    if ($method->modo_teste) {
      $this->chave_moip = $method->chave_teste;
    } else {
      $this->chave_moip = $method->chave_producao;
    }
    return $this->chave_moip;
  }

  public function getAfiliacaoMoip($method) {
    if ($method->modo_teste) {
      $this->token_moip = $method->token_teste;
    } else {
      $this->token_moip = $method->token_producao;
    }
    return $this->token_moip;
  }

  /**
  * Calcula as parcelas do crédito
  */
  public function calculaParcelasCredito( $method, $order_total, $id, $numero_parcelas=null ) {
    $conteudo = "<div id='".$id."' class='div_parcelas div_pagamentos'>";
    $parcelas_juros = 1;
    $paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);

    if (is_null($numero_parcelas)) {
            $limite_sem_juros = $method->max_parcela_sem_juros;
        } else {
            $limite_sem_juros = $numero_parcelas;
        }

        if (!empty($limite_sem_juros)) {
           for ($i=1; $i<=$limite_sem_juros; $i++) {
            $valor_parcela = $order_total / $i;
            $parcelas_juros ++;
        // caso o valor da parcela seja menor do que o permitido, não a exibe
            if (($valor_parcela < $method->valor_minimo or $valor_parcela < 5) and $i != 1) {
             continue;
         }
        //$valor_formatado_credito = 'R$ '.number_format($valor_parcela,2,',','.');
         $valor_formatado_credito = $paymentCurrency->priceDisplay($valor_parcela,$paymentCurrency->payment_currency);

         $conteudo .= '<div class="field_visa"><label><input type="radio" value="'.$i.'" name="parcelamento" style="width:15px; height: 18px;"/>&nbsp;<span id="p0'.$i.'">'.$i.' x </span>&nbsp;<span class="asterisco">'.$valor_formatado_credito.' sem juros</span></label></div>';
         if ($method->max_parcela_com_juros == $i) {
             break;
         }
     }
 }

 if (is_null($numero_parcelas)) {
   $limite_parcelamento = $method->max_parcela_com_juros;
} else {
   $limite_parcelamento = $numero_parcelas;
}

$asterisco = false;
for($i=$parcelas_juros; $i<=$limite_parcelamento; $i++) {
      // verifica se o juros será para o emissor ou para o comprador
      // caso o valor da parcela seja menor do que o permicodigo_moipo, não a exibe
   if (($valor_parcela < $method->valor_minimo or $valor_parcela < 5) and $i != 1) {
    continue;
}
      //$valor_formatado_credito = 'R$ '.number_format($valor_parcela,2,',','.');

if ($i==1) {
        $valor_parcela  = $order_total * (1+$method->taxa_credito); // calcula o valor da parcela

      } else {
        $valor_parcela = $this->calculaParcelaPRICE($order_total,$i,$method->taxa_parcelado);
        //$valor_pedido = $order_total * (1+$method->taxa_parcelado); // calcula o valor da parcela
        $asterisco = true;
      }
      $valor_formatado_credito = $paymentCurrency->priceDisplay($valor_parcela,$paymentCurrency->payment_currency);
      //$valor_parcela = $valor_pedido / $i;

      $conteudo .= '<div class="field_visa"><label><input type="radio" value="'.$i.'" name="parcelamento" style="width:15px; height: 18px;"/>&nbsp;<span id="p0'.$i.'">'.$i.' x </span>&nbsp;<span class="asterisco">'.$valor_formatado_credito.' * </span></label></div>';
      if ($limite_parcelamento == $i) {
        break;
      }
    }
    if ($asterisco) {
      $conteudo .= "<div>* Valores sujeitos à alteração ao efetuar o pagamento via Cartão (".$method->taxa_parcelado."% a.m.).</div>";
    }
    $conteudo .= '</div>';
    return $conteudo;
  }

  public function calculaParcelaPRICE($Valor, $Parcelas, $Juros) {
    $Juros = bcdiv($Juros,100,15);
    $E=1.0;
    $cont=1.0;
    for($k=1;$k<=$Parcelas;$k++) {
      $cont= bcmul($cont,bcadd($Juros,1,15),15);
      $E=bcadd($E,$cont,15);
    }
    $E=bcsub($E,$cont,15);
    $Valor = bcmul($Valor,$cont,15);
    return round(bcdiv($Valor,$E,15),2);
  }

  // status do pagamento da Moip
  public function _getPaymentStatusNotificacao($method, $moip_status) {
    if ($moip_status == 1) {
      $new_status = $method->transacao_concluida;
      $mensagem = JText::_('VMPAYMENT_MOIP_STATUSNOTIFICATION1');
    } elseif ($moip_status == 4) {
      $new_status = $method->transacao_concluida;
      $mensagem = JText::_('VMPAYMENT_MOIP_STATUSNOTIFICATION4');
    } elseif ($moip_status == 6) {
      $new_status = $method->transacao_em_analise;
      $mensagem = JText::_('VMPAYMENT_MOIP_STATUSNOTIFICATION6');
    } elseif ($moip_status == 7) {
      $new_status = $method->transacao_estornada;
      $mensagem = JText::_('VMPAYMENT_MOIP_STATUSNOTIFICATION7');
    } elseif ($moip_status == 5) {
      $new_status = $method->transacao_cancelada;
      $mensagem = JText::_('VMPAYMENT_MOIP_STATUSNOTIFICATION5');
    } elseif ($moip_status == 3) {
      $new_status = $method->transacao_nao_finalizada;
      $mensagem = JText::_('VMPAYMENT_MOIP_STATUSNOTIFICATION3');
    } else {
      $new_status = $method->transacao_nao_finalizada;
      $mensagem = JText::_('VMPAYMENT_MOIP_STATUSNOTIFICATIONDEFAULT');
    }
    return array(
      'status'=> $new_status,
      'mensagem'=> $mensagem
          );
  }

  // status do pagamento da Moip
  public function _getPaymentStatus($method, $moip_status) {
    if ($moip_status == 'Autorizado') {
      $new_status = $method->transacao_concluida;
    } elseif ($moip_status == 'EmAnalise') {
      $new_status = $method->transacao_em_analise;
    } elseif ($moip_status == 'Cancelado') {
      $new_status = $method->transacao_cancelada;
    } else {
      $new_status = $method->transacao_nao_finalizada;
    }
    return $new_status;
  }

  public function _getFormaPagamentoRetorno($codigo="0"){
    $pm = array();
    $pm[0] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN0');
    $pm[1] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN1');
    $pm[3] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN3');
    $pm[4] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN4');
    $pm[5] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN5');
    $pm[6] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN6');
    $pm[7] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN7');
    $pm[8] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN8');
    $pm[9] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN9');
    $pm[10] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN10');
    $pm[12] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN12');
    $pm[13] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN13');
    $pm[14] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN14');
    $pm[21] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN21');
    $pm[22] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN22');
    $pm[24] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN24');
    $pm[31] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN31');
    $pm[32] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN32');
    $pm[35] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN35');
    $pm[58] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN58');
    $pm[73] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN73');
    $pm[75] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN75');
    $pm[76] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN76');
    $pm[88] = JText::_('VMPAYMENT_MOIP_TYPEPAYMENT_RETURN88');
    $forma_pagamento = (isset($pm[$codigo])?$pm[$codigo]:'Não-encontrado');
    return $forma_pagamento;
  }

  // recupera o codigo_moip com base no numero do pedido
    public function recuperaCodigoMoip($order_number) {
      $db = JFactory::getDBO();
      $query = 'SELECT ' . $this->_tablename . '.`codigo_moip` FROM ' . $this->_tablename . " WHERE  `order_number`= '" . $order_number . "'";
      $db->setQuery($query);
      $this->codigo_moip =  $db->loadResult();
  }

    // reformata o valor que vem do servidor da Moip
  public function reformataValor($valor) {
    $valor = substr($valor,0,strlen($valor)-2).'.'.substr($valor,-2);
    return $valor;
}
function plgVmDeclarePluginParamsPaymentVM3($data) {
    return $this->declarePluginParams('payment', $data);
}

}
