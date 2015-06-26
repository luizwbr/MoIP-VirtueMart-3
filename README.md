# MoIP-VirtueMart-3
Plugin de pagamentos para MoIP - VirtueMart 3

Passo 1 - Crie sua conta Moip ( caso não exista ) e solicite a ativação da API Moip.
Passo 2 - Configure o retorno automático em Menu Meus Dados / Notificação das Transações. 
A url tem que ser assim: http://urldositecom.br//index.php?option=com_virtuemart&view=pluginresponse&task=plugin&tmpl=component&task=pluginresponsereceived&pm=6&nasp=1

Passo 2.1 - Configure os 4 campos extras: "logradouro", "numero", "bairro" e "complemento". Caso o campo não exista, ele deve ser criado.

Passo 3 - Habilite o plugin aqui Administrar Plugins
Passo 4 - Instale Plugin por esta tela Métodos de pagamento
Passo 4.1 - Clique em Novo Método de Pagamento e preencha as informações:
* Nome do Pagamento: Cartões de crédito e débito, transferência e boleto bancário ( Moip )
* Publicado: Sim
* Descrição do pagamento: Pague com cartão de crédito, boleto ou saldo Moip
* Método de pagamento: VM Payment - Moip Checkout Transparente
* Grupo de Compradores: -default- e -anonymous-
Passo 4.2 - Clique em Salvar.
Passo 5 - Na aba configurações, preencha os dados:

Configurações do Plugin de Pagamento
* Logotipos:
* Modo de teste ( Sim ou Não )
* Token (teste) Menu do Moip FERRAMENTAS / API / Chaves de acesso
* Chave de Acesso (teste) Menu do Moip FERRAMENTAS / API / Chaves de acesso
* Token (produção) Menu do Moip FERRAMENTAS / API / Chaves de acesso
* Chave de Acesso (produção) Menu do Moip FERRAMENTAS / API / Chaves de acesso
* Valor Mínimo 0,01
* Mensagem Pagamento Mensagem que vai no detalhamento da compra dentro do Moip
* Status Postado pelo Moip (Compra Aprovada, Em Análise, Estornada, Aguardando Pagamento, Cancelada )

Configuração Parcelamento
** Max. Parcelas Sem Juros 3
** Max. Parcelas Com Juros 12
** Taxa de Juros Crédito à vista (0.035)
** Taxa de Juros Parcelado (0.045)

Formas de Pagamento Aceitas
* Ativar Boleto (Sim ou Não)
* Ativar Cartões de Crédito (Sim ou Não)
* Ativar Débito em Conta (Sim ou Não)

Cartões de Crédito Aceitos
* Visa (Sim ou Nâo)
* Mastercard (Sim ou Nâo)
* Hipercard (Sim ou Nâo)
* Diners (Sim ou Nâo)
* Amex (Sim ou Nâo)

Pagamento com Débito Aceitos
* Débito BB (Sim ou Nâo)
* Débito Bradesco (Sim ou Nâo)
* Débito Banrisul (Sim ou Nâo)
* Débito Itaú (Sim ou Nâo)

Pagamento com Boleto Aceitos
* Boleto Itaú (Sim ou Nâo)

Outras configurações
* Países (Brasil)
* Mínimo da Compra (Mínimo da compra para ativar o módulo)
* Máximo da Compra (Máximo da compra para ativar o módulo)
* Custo por Transação (Custo extra por transação feita)
* Custo percentual total (Custo extra por transação total)
* Tarifa/Imposto (Configurar de uma tarifa previamente cadastrada)

================
Contato: Luiz Felipe Weber
weber@weber.eti.br
================
