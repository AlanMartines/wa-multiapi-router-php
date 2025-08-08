# Wa Multi API Router php
É um roteador PHP para integrar múltiplas APIs não-oficiais do WhatsApp em uma única interface simples e modular, facilitando o envio de mensagens, gerenciamento de instâncias.


# Configuração da API

Este projeto utiliza constantes definidas no arquivo config.php para centralizar as credenciais e URLs das APIs de WhatsApp suportadas.

Parâmetros de configuração:

## ADMINTOKEN
O que é: Chave de autenticação para acesso administrativo à API.
Onde obter: No painel ou documentação do provedor de API escolhido.
Importante: Nunca compartilhe publicamente este token. Ele concede acesso total à API.

## APIURL
O que é: Endereço base (endpoint raiz) da API que será utilizada.
Exemplo: [http://localhost:8080](http://localhost:8080)
Uso: Todas as requisições serão feitas a partir deste endereço.

## SOURCES
O que é: Identifica o provedor de API utilizado para roteamento de chamadas.
Exemplo de valores possíveis:

* evolutionapi
  Função: Permite ao roteador direcionar as requisições para o adaptador correto.

---

> **⚠ Aviso:** Este projeto está em desenvolvimento ativo. Algumas funcionalidades podem ainda não estar implementadas ou apresentar erros/falhas. Recursos adicionais serão adicionados gradualmente nas próximas versões.