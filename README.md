# Painel de Indicadores Trindtech

Aplicacao para consultar IPCA e IGP-M usando os dados do sistema SGS do Banco Central.

## Requisitos

- Windows
- PHP 8.3 ou superior, com `pdo_mysql` e `curl` habilitados
- Composer
- Node.js 20.19+ ou 22.12+
- MySQL ou MariaDB em execucao; o usuario configurado precisa poder criar o banco
- Acesso a internet na primeira inicializacao para instalar dependencias e buscar as series no Banco Central

## Como iniciar

1. Clone ou extraia o repositorio.
2. Se precisar, configure `TesteTrindtechAPI/.env` com os dados do seu MySQL. Sem `.env`, o inicializador cria um a partir do exemplo, usando os valores padrao do Laravel (`127.0.0.1`, banco `laravel`, usuario `root` e senha vazia).
3. Execute `Iniciar Projeto.bat`.

O inicializador instala dependencias que estiverem faltando, cria a chave Laravel quando necessario, cria o banco de dados, executa as migrations e seeders e inicia a API e o frontend. Ao responderem, abre `http://127.0.0.1:5173` no navegador. Mantenha abertas as duas janelas de terminal dos servidores; feche-as para parar o projeto.

Se a sincronizacao inicial nao puder acessar o Banco Central, confira a conexao com a internet e execute `Iniciar Projeto.bat` novamente.

## Acesso inicial

- E-mail: `admin@trindtech.com`
- Senha: `senha123`
