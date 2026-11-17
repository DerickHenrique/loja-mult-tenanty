# Protótipo local de lojas

Duas vitrines de demonstração usam a mesma aplicação Laravel + React/Inertia: `teste1.local` e `teste2.local`. Cada domínio é resolvido por uma lista local explícita, com tema e conteúdo próprios. Não há checkout, pagamento, e-mail, integração externa ou painel administrativo.

## Requisitos

- Docker Engine/Desktop com Docker Compose v2 e suporte a imagens Linux.
- Porta TCP 80 disponível no computador. O Vite usa também a porta 5173.
- OpenSSL para o script de inicialização criar a chave local de criptografia.
- Acesso à internet na primeira inicialização para baixar imagens e dependências. Depois, o Docker conserva as imagens, dependências e volumes.

O protótipo fixa Laravel `13.42.0`, Inertia Laravel `3.7.0`, Inertia React `3.11.0`, React `19.3.1`, Vite `8.3.8`, Laravel Vite Plugin `3.2.1` e plugin React `6.3.0`. Laravel 13 exige PHP 8.3 ou superior; o container usa LSAPI com PHP 8.4. Os arquivos `composer.lock` e `package-lock.json` são criados na primeira inicialização e ficam em `core/`; mantenha-os junto do protótipo depois de criados para congelar também as dependências transitivas. Sem esses lockfiles já presentes, a primeira resolução exige acesso aos repositórios de pacotes.

As versões foram selecionadas com base nas documentações e registros oficiais consultados em 16/11/2026: [requisitos e releases do Laravel 13](https://laravel.com/docs/13.x/releases), [guia Laravel Vite](https://laravel.com/docs/13.x/vite), [Inertia Laravel no Packagist](https://packagist.org/packages/inertiajs/inertia-laravel), [Inertia React no npm](https://www.npmjs.com/package/@inertiajs/react), [React no npm](https://www.npmjs.com/package/react), [Vite no npm](https://www.npmjs.com/package/vite), [Laravel Vite Plugin no npm](https://www.npmjs.com/package/laravel-vite-plugin), [imagem Docker oficial OpenLiteSpeed](https://github.com/litespeedtech/ols-docker-env) e [usuário padrão do PHP LSAPI no OpenLiteSpeed](https://docs.openlitespeed.org/config/php/externalapp/).

## Iniciar e parar

Na pasta do repositório:

```sh
cd prototipo-local
sh ./iniciar.sh
```

O script cria `prototipo-local/.env` com uma chave aleatória local, mantém o Inertia DevTools desligado para não gravar dados na release compartilhada somente leitura, prepara o ponto de montagem privado do Core e inicia a composição com `docker compose up --build`. Deixe esse terminal aberto. Para iniciar em segundo plano, execute o script uma vez para preparar os arquivos locais e depois use `docker compose up --build -d`.

Para parar os serviços, na pasta `prototipo-local/`:

```sh
docker compose down
```

Os dados continuam guardados nos volumes Docker. Para apagar também os bancos e runtimes locais do protótipo:

```sh
docker compose down -v
```

Esse comando remove os volumes nomeados desta composição. Não remove arquivos do host fora de `prototipo-local/`. A chave `.env` e as configurações versionadas das lojas permanecem.

## Configurar os domínios locais

O protótipo **não edita** `/etc/hosts`, DNS ou qualquer configuração do sistema. Acrescente manualmente, com privilégios administrativos, estas entradas ao arquivo `hosts` do seu computador:

```text
127.0.0.1 teste1.local teste2.local
```

No Linux e macOS, o arquivo usual é `/etc/hosts`; no Windows, é `C:\Windows\System32\drivers\etc\hosts`. Se `.local` conflitar com mDNS no seu computador, use `teste1.test` e `teste2.test`: os aliases já estão incluídos na configuração local.

Abra:

- [http://teste1.local](http://teste1.local) — Casa Botânica.
- [http://teste2.local](http://teste2.local) — Traço Urbano.
- Alternativas: [http://teste1.test](http://teste1.test) e [http://teste2.test](http://teste2.test).

Se a porta 80 já estiver ocupada, libere-a antes de iniciar o protótipo. O mapeamento do OpenLiteSpeed nesta demonstração usa a porta HTTP padrão.

## O que alterar na demonstração

### Tema e conteúdo de uma loja

Edite somente o JSON correspondente:

- `tenants/loja1/tenant.json`
- `tenants/loja2/tenant.json`

Cada arquivo contém marca, nome, textos e tokens de tema. As três cores precisam continuar no formato hexadecimal `#RRGGBB`. Atualize o navegador após salvar; Vite atualiza o Core e a aplicação lê a configuração da loja a cada requisição.

### Componente ou texto compartilhado

Edite `core/resources/js/Components/SharedMessage.jsx`. O diretório `core/` é montado uma única vez e compartilhado pelos dois domínios. Salve o arquivo e veja a mesma mudança nas duas lojas. Para alterar o layout compartilhado da página, edite `core/resources/js/Pages/Home.jsx`; estilos comuns ficam em `core/resources/css/app.css`.

### Domínio não cadastrado

`core/config/tenants.php` contém a lista fixa de hosts aceitos e o caminho de configuração permitido para cada um. Uma requisição a um host fora dessa lista recebe uma página 404. Não há seleção de caminhos ou tenants por parâmetros fornecidos pelo navegador.

## Onde ficam os arquivos e os dados

- `core/`: única cópia do código da aplicação, páginas, componentes, estilos e configuração compartilhada.
- `tenants/loja1/tenant.json` e `tenants/loja2/tenant.json`: configuração visual/conteúdo individual, fora do document root.
- Volumes Docker `loja1-runtime` e `loja2-runtime`: sessões, cache, views compiladas, logs Laravel e temporários, cada um montado no diretório de runtime de seu tenant. Os pontos de montagem são preparados na imagem com o usuário/grupo de serviço LSAPI padrão (`nobody:nogroup` em Linux/Ubuntu); isso não altera permissões do host. O log de acesso do vhost é descartado e os erros do servidor ficam no container.
- Volumes Docker `loja1-data` e `loja2-data`: arquivos SQLite independentes de cada tenant.
- `prototipo-local/.env`: chave local de criptografia e configuração do protótipo; fica fora de `public_html` e é ignorada pelo Git. O arquivo é montado somente para leitura no Core para que o PHP LSAPI receba a chave mesmo quando o OpenLiteSpeed não herda o ambiente do container.
- `core/composer.lock` e `core/package-lock.json`: criados no primeiro início e mantidos no diretório `core/` para travar as árvores de dependências subsequentes.

Use `docker volume ls` para listar os volumes. `docker compose down -v` remove os volumes e, portanto, os dados e runtimes deste protótipo.

## Limites da simulação de hospedagem

O servidor HTTP é OpenLiteSpeed com PHP LSAPI em container. Isso demonstra a família de servidor e o handler PHP indicados no plano, mas **não reproduz o WHM/cPanel completo**. O container não simula contas cPanel distintas, isolamento real de processos por usuário da conta, provisionamento/rotação da configuração privada por tenant, nem o handler e a identidade efetivos do seu servidor.

Também não representa permissões, grupos ou ACLs Linux de produção. Os runtimes e bancos são volumes Docker independentes, e o container pode executar com um usuário diferente do usuário efetivo de uma conta cPanel. Antes de qualquer implantação real, seria necessário diagnosticar o handler e usuário de execução de cada virtual host, validar leitura das releases imutáveis e escrita apenas no runtime privado correspondente, e confirmar permissões/ACLs no ambiente de homologação. Este protótipo não altera servidor real, permissões, DNS ou integrações externas.

Na demonstração, ambas as lojas observam a mesma árvore de desenvolvimento `core/`, para que uma alteração compartilhada apareça imediatamente nas duas. Em produção, a política descrita em `plano.md` continua valendo: releases de Core imutáveis e versionadas, com cada tenant apontando para sua própria versão; tema, configuração privada, runtime e banco permanecem isolados por loja.
