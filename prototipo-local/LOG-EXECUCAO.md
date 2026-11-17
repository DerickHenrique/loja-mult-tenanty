# Log de execução do protótipo

**Data:** 16/11/2026, 21:26 (America/Sao_Paulo)  
**Solicitação:** iniciar o protótipo, testar as vitrines e corrigir problemas necessários.

## Diagnóstico e alterações

1. A composição Docker iniciou e instalou as dependências. A primeira consulta HTTP retornou 404 porque o listener HTTP da imagem OpenLiteSpeed não estava mapeado para o virtual host `Example`.
2. Ajustei a imagem para mapear o listener HTTP ao virtual host da aplicação, remover o membro curinga conflitante e usar `/var/www/core/` como raiz do virtual host. Removi o bloco `context /` que fazia o OpenLiteSpeed responder 403; o document root definido continua sendo `/var/www/core/public/`.
3. O middleware procurava domínios com pontos usando a notação de caminho do Laravel. Troquei a consulta para acessar diretamente a chave literal do host no array de configuração, preservando a lista explícita de domínios permitidos.
4. O processo PHP LSAPI não recebia a chave de criptografia somente pela variável de ambiente do container. O Compose agora monta o `.env` local, somente para leitura, no Core. O script prepara o ponto de montagem ignorado pelo Git.
5. Desativei o Inertia DevTools no ambiente local para evitar gravação de entradas de diagnóstico na árvore compartilhada do Core, montada como somente leitura.
6. Corrigi a criação dos diretórios runtime no Dockerfile: o shell da imagem não expandia a sintaxe de chaves e criava um diretório com o nome literal `{cache,sessions,...}`. Agora cada diretório é criado explicitamente.
7. Atualizei o README com o preparo necessário antes de iniciar em segundo plano.

## Verificações realizadas

- `docker compose build web`: concluído.
- `docker compose up --build -d web`: concluído; serviços `web` e `frontend` ativos. O serviço de dependências `app-deps` concluiu com código 0.
- `sh -n iniciar.sh`: aprovado.
- `docker compose config -q`: aprovado.
- `git diff --check`: aprovado.
- Vite client em `http://127.0.0.1:5173/@vite/client`: HTTP 200.
- Requisições HTTP à porta 80 com Host `teste1.local` e `teste1.test`: HTTP 200 e props Inertia identificam o tenant `loja1`.
- Requisições HTTP à porta 80 com Host `teste2.local` e `teste2.test`: HTTP 200 e props Inertia identificam o tenant `loja2`.
- Host `desconhecida.local`: HTTP 404 e mensagem de domínio não configurado.

## Artefatos locais

- `prototipo-local/.env` foi criado pelo script com uma chave aleatória; também contém a opção local para desativar o Inertia DevTools. O valor secreto não foi incluído neste log.
- `core/composer.lock` e `core/package-lock.json` foram gerados pela primeira instalação, conforme instrução do README, e devem ser mantidos para fixar as dependências transitivas.
- Laravel gerou `core/bootstrap/cache/packages.php` e `core/bootstrap/cache/services.php` durante a descoberta de pacotes.
- Não foram apagados volumes Docker. Os containers permanecem ativos.

## Observações

- Os testes HTTP enviaram o cabeçalho `Host` diretamente para `127.0.0.1`. Para abrir os endereços pelo nome no navegador, as entradas de `teste1.local` e `teste2.local` (ou os aliases `.test`) ainda precisam estar configuradas no arquivo `hosts` da máquina, conforme o README.
- O entrypoint da imagem emite um aviso ao tentar ajustar a propriedade do arquivo de configuração montado como somente leitura. O aviso não impede o servidor de iniciar nem as páginas de responder.

## Atualização — arquivos de tenant não refletiam alterações

**Data:** 16/11/2026, 22:13 (America/Sao_Paulo)

### Diagnóstico e correção

- Reproduzi o problema: o arquivo do host `tenants/loja1/tenant.json` estava com o nome `Casa Esotérica`, enquanto o container ainda lia `Casa Botânica`. A composição montava cada JSON como um arquivo individual; ao salvar por substituição atômica, o mount podia continuar apontando para o arquivo antigo.
- Mudei os mounts para pastas inteiras de configuração, fora do document root e somente para leitura. O caminho permitido de cada tenant foi atualizado para a nova localização no container.
- Configurei o Vite para observar `tenants/**/*.json`, além dos caminhos de atualização existentes. Ao salvar um JSON, o navegador conectado recebe um full reload e Laravel lê o conteúdo novo na requisição seguinte.
- Atualizei o README com o comportamento após salvar.

### Verificações

- `docker compose up --build -d`: concluído; `web` e `frontend` ativos.
- Confirmei que o JSON do container agora contém `Casa Esotérica`, igual ao arquivo atual do host.
- Host `teste1.local`: HTTP 200 com `Casa Esotérica`; host `teste2.local`: HTTP 200 com `Traço Urbano`; domínio desconhecido: HTTP 404.
- Conectei ao WebSocket HMR do Vite, alterei um JSON temporário na pasta montada e recebi `full-reload`. Removi o arquivo temporário após a verificação.
- `docker compose config -q` e `git diff --check`: aprovados.
- Preservei as alterações já presentes do usuário em `tenant.json`, `Home.jsx` e `SharedMessage.jsx`.
