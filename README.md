## Sobre essa API

Foi criada com a intencao de realizar a integracao com Microsoft Graph.

## Tecnologias

- php 7.4
- Laravel 8
- Composer 2.4.2

## Bibliotecas Principais

- guzzlehttp/guzzle:7.0.1
- laravel/framework:8.75
- laravel/sanctum:2.15
- laravel/ui:3.4
- league/oauth2-client:.6.0
- microsoft/microsoft-graph:1.100

## Passos para o bom funcionamento da API

- 1) Ao acessar a home Azure, no menu a esquerda, acesse `Azure Active Directory`.
- 2) Va em `registro de aplicativo`.
- 3) Registre um novo aplicativo.
- 4) Selecione a `3a opção` (Contas em qualquer diretório organizacional e contas pessoais da Microsoft) e cadastre a `URI de callback da api`.
- 5) Em `certificados e segredos`, selecione `Novo registro do cliente`.
- 6) Apos o registro, `copie a chave do valor`, pois aparece apenas uma vez.
- 7) Em `Visao geral`, copie o `ID aplicativo (cliente)`

## PASSOS SEGUINTES
- Configure o arquivo .env
- Crie o arquivo config/azure.php
