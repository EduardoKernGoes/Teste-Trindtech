# Painel de Indicadores - Teste Trindtech

Painel full-stack desenvolvido para monitoramento e consulta de séries históricas de indicadores econômicos (**IPCA** - Código 433 e **IGP-M** - Código 189) através da API pública do Banco Central (SGS).

## Tecnologias Utilizadas

- **Back-end:** PHP 8+, Laravel, Laravel Sanctum (Autenticação API), MySQL.
- **Front-end:** React, Recharts (Gráficos interativos), Axios, CSS Puro (Dark Mode corporativo).

## Funcionalidades Implementadas
- Acesso Protegido: Autenticação robusta via Laravel Sanctum.

- Gerenciamento de Indicadores: Listagem em tempo real, edição flexível de nomes (mantendo os códigos SGS originais) e exclusão em cascata (removendo o indicador e todo o seu histórico).

- Sincronização com o Banco Central: Botão dedicado para buscar e atualizar o período (01/01/2025 a 01/11/2026) sem gerar linhas duplicadas.

- Consulta Avançada & Visualização: Filtros dinâmicos por indicador e intervalo de datas, tabelas corporativas responsivas com rolagem interna e gráficos de linha sincronizados via Recharts.

## Requisitos e instalacao (Windows)

Instale estes programas antes de iniciar o projeto:

1. **PHP 8.3 ou superior**
   - Baixe o pacote ZIP para Windows em [windows.php.net/download](https://windows.php.net/download/). Escolha x64 e uma versao Thread Safe.
   - Extraia, por exemplo, em `C:\php`.
   - Dentro dessa pasta, copie `php.ini-development` para `php.ini`.
   - Abra `php.ini` e habilite as extensoes do projeto removendo o `;` no inicio destas linhas:
     ```ini
     extension=curl
     extension=pdo_mysql
     ```
     Se as linhas nao estiverem no arquivo, adicione-as. Confira tambem se `extension_dir="ext"` esta habilitado.
   - Adicione `C:\php` ao `Path` do Windows: pesquise por **Editar as variaveis de ambiente do sistema** > **Variaveis de Ambiente** > selecione **Path** > **Editar** > **Novo**. Feche e reabra o terminal.
   - Confira com `php -v`, `php --ini` e `php -m`. A lista de modulos deve conter `curl` e `pdo_mysql`.
   - Referencia: [instalacao do PHP no Windows](https://www.php.net/manual/pt_BR/install.windows.php).

2. **Composer**
   - Baixe e execute o instalador oficial [Composer-Setup.exe](https://getcomposer.org/Composer-Setup.exe). Quando solicitado, selecione o `php.exe` instalado no passo anterior.
   - Confira com `composer --version`.
   - Referencia: [instalacao do Composer](https://getcomposer.org/download/).

3. **Node.js e npm**
   - Baixe e instale a versao LTS em [nodejs.org/en/download](https://nodejs.org/en/download/). O projeto requer Node.js 20.19+ ou 22.12+; versoes superiores tambem sao aceitas.
   - O npm e instalado junto com o Node.js. Confira com `node --version` e `npm --version`.

4. **MySQL ou MariaDB**
   - Instale e inicie um servidor MySQL, por exemplo, usando o [MySQL Installer oficial para Windows](https://dev.mysql.com/downloads/installer/).
   - Tenha em maos o host, a porta, o nome de usuario e a senha. O usuario precisa poder criar um banco de dados; o inicializador cria o banco configurado se ele ainda nao existir.

## Configurar e iniciar o projeto

1. Clone ou extraia o repositorio para uma pasta local.
2. Se necessario, edite `TesteTrindtechAPI/.env` e ajuste `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD` para corresponder ao seu MySQL. Se o arquivo ainda nao existir, copie `TesteTrindtechAPI/.env.example` para `TesteTrindtechAPI/.env` antes de edita-lo. O exemplo usa `127.0.0.1:3306`, banco `teste_trindtech`, usuario `root` e senha vazia.
3. Dê dois cliques em `Iniciar Projeto.bat`.

O inicializador instala dependencias que estiverem faltando, cria a chave Laravel quando necessario, cria o banco de dados, executa as migrations e seeders e inicia a API e o frontend. Na primeira execucao, mantenha a conexao com a internet disponivel para baixar dependencias e sincronizar IPCA e IGP-M com o Banco Central. Quando os servidores responderem, o navegador abrira `http://127.0.0.1:5173`. Mantenha abertas as duas janelas de terminal dos servidores; feche-as para parar o projeto.

Se a sincronizacao inicial nao puder acessar o Banco Central, confira a conexao com a internet e execute `Iniciar Projeto.bat` novamente.

## Acesso inicial

- E-mail: `admin@trindtech.com`
- Senha: `senha123`

## Solucao de problemas

- **PHP nao encontrado:** confira se a pasta que contem `php.exe` foi adicionada ao `Path` e abra um novo terminal.
- **`pdo_mysql` ou `curl` ausente:** habilite `extension=pdo_mysql` e `extension=curl` no `php.ini` usado pelo terminal. Use `php --ini` para localizar esse arquivo.
- **Erro MySQL `1045 Access denied`:** confira usuario e senha em `TesteTrindtechAPI/.env`.
- **Erro ao criar o banco:** verifique se o servidor MySQL esta em execucao e se o usuario configurado tem permissao para criar bancos de dados.
- **Erro de conexao com o Banco Central:** confirme que ha acesso a internet e execute o inicializador novamente.

Para mais informações ou dúvidas entrar em contato via e-mail: edugoes215@gmail.com.