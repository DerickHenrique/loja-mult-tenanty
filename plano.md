# Guia de arquitetura e construção de uma plataforma de e-commerce reutilizável

> Este documento orienta a criação de um **produto de software** capaz de publicar e operar várias lojas virtuais. A plataforma deve servir a diferentes setores sem pressupor o que cada loja vende. As particularidades de uma loja entram como configuração, conteúdo, tema e integrações; não devem exigir uma cópia do código-base.
>
> Preencha os campos `[definir]` durante as decisões técnicas. Marque cada atividade como `Não iniciado`, `Em andamento`, `Bloqueado` ou `Concluído`. Registre decisões arquiteturais importantes com data, motivo e consequências.

## 1. Objetivo e princípios do produto

### Objetivo

Construir uma plataforma que permita criar, configurar, publicar e manter múltiplos sites de e-commerce sobre uma base compartilhada. Cada loja terá identidade visual, domínio, catálogo, conteúdo, regras comerciais e integrações próprias, enquanto recursos comuns serão desenvolvidos e corrigidos uma vez na plataforma.

**Nome interno do produto:** [definir]  
**Equipe responsável:** [definir]  
**Público comprador da plataforma:** [lojistas / agências / equipe interna / outro: ]  
**Meta de lojas suportadas no primeiro ano:** [definir]  
**Mercados/regiões de operação:** [definir]  
**Data alvo para primeira versão operacional:** [definir]

### Princípios que guiam decisões

1. **Configurar antes de personalizar código:** a maioria das diferenças entre lojas deve ser resolvida por dados e configurações validadas.
2. **Uma base, várias lojas:** compartilhar componentes e serviços reduz divergência e custo de manutenção.
3. **Isolamento por loja:** dados e permissões de uma loja nunca podem vazar para outra.
4. **Experiência clara:** ajudar clientes a encontrar, avaliar e comprar itens sem esconder informações ou criar pressão enganosa.
5. **Falhas visíveis e recuperáveis:** informar o que ocorreu, preservar o trabalho do usuário e permitir tentar novamente quando possível.
6. **Evolução segura:** mudanças de banco, configuração e interface precisam ser compatíveis com lojas já publicadas.
7. **Medir antes de otimizar:** decisões de conversão e desempenho devem usar métricas e pesquisa, respeitando privacidade.

## 2. Escopo do produto de software

O produto é a plataforma de criação e operação de lojas. O catálogo e as regras de cada lojista são dados de instância, não escopo fixo do código.

### Capacidades da plataforma

- Criar uma loja e atribuir um identificador estável (`store_id`).
- Configurar nome, marca, tema, conteúdo, domínio, idioma, moeda e fuso horário.
- Cadastrar e organizar produtos genéricos, categorias, variantes, mídias, preços e disponibilidade.
- Publicar páginas de descoberta, listagem, detalhe de produto, carrinho, checkout, conta e pedidos.
- Integrar serviços de pagamento, entrega, e-mail e análise por adaptadores substituíveis.
- Administrar usuários, funções, catálogo, pedidos, conteúdo e configurações.
- Controlar publicação, pré-visualização, versões e reversão de configurações.
- Medir saúde técnica e comportamento agregado da loja, com controles de privacidade.

### Limites configuráveis por loja

| Área configurável | Exemplos de configuração | Regra de implementação |
|---|---|---|
| Marca e tema | Logotipo, cores, fontes, espaçamento, estilo de botões | Usar tokens e componentes; não criar CSS específico espalhado pelo sistema. |
| Navegação e conteúdo | Menus, banners, páginas institucionais, ordem de seções | Conteúdo editável e validado; layout com blocos permitidos. |
| Catálogo | Categorias, atributos, variantes, campos descritivos | Modelo extensível; não assumir atributos universais como tamanho ou cor. |
| Comércio | Moeda, impostos, descontos, estoque, regras de venda | Política explícita e testável por loja/região. |
| Operação | Endereços, entrega, retirada, notificações | Adaptadores e configurações sem credenciais no código. |
| Identidade e acesso | Domínio, idioma, fuso horário, usuários e papéis | Resolução de loja e autorização sempre verificadas no servidor. |

**Itens que precisam ser iguais em todas as lojas:** [definir]  
**Pontos de extensão permitidos:** [definir]  
**Personalizações que não serão suportadas:** [definir]

## 3. Modelo de arquitetura

### 3.1 Arquitetura lógica inicial

Começar com um **monólito modular** costuma simplificar a primeira versão: uma aplicação implantável com módulos internos bem separados. Extrair serviços independentes só quando escala, autonomia de equipe ou isolamento operacional justificar a complexidade.

```text
Navegador / dispositivo móvel
          |
 CDN, proteção de borda e arquivos estáticos
          |
 Resolução de domínio -> contexto da loja
          |
 Aplicação web / API
   |         |          |
 Catálogo  Checkout   Administração
   |         |          |
 Banco de dados — cache — fila de tarefas
          |
 Adaptadores: pagamento, entrega, e-mail, busca, métricas
```

**Estilo arquitetural:** monólito Laravel modular, com releases de código compartilhadas e selecionáveis independentemente por loja; execução, configuração, runtime e dados isolados por conta/loja.  
**Backend:** Laravel e PHP em versões estáveis atuais e mutuamente compatíveis no início da implementação; as versões exatas serão selecionadas e fixadas antes do primeiro release.  
**Frontend:** React + Inertia.js + Vite em versões estáveis atuais e mutuamente compatíveis no início da implementação; fixar versões exatas nos arquivos de dependências.  
**Web server:** LiteSpeed.  
**Execução PHP preferida:** LSAPI, executando como o usuário Linux/cPanel de cada conta. PHP-FPM não é requisito; confirmar no servidor antes de configurar.  
**Hospedagem atual:** WHM/cPanel [versões e detalhes a confirmar].  
**Banco:** banco independente por cliente/loja na estratégia inicial.  
**Cache, fila, armazenamento de mídia e região:** [definir]

### 3.2 Separação de módulos

Manter limites claros entre módulos, mesmo que compartilhem o mesmo processo inicialmente:

- **Tenancy e provisionamento:** criação da loja, identificação pelo domínio, estado da publicação e limites do plano.
- **Identidade e autorização:** clientes, operadores, sessões, papéis e permissões.
- **Configuração e conteúdo:** tema, páginas, navegação, textos e versões publicadas.
- **Catálogo:** produtos, variantes, categorias, atributos, preço e mídia.
- **Descoberta:** pesquisa, filtros, ordenação, recomendações e coleções.
- **Carrinho e checkout:** itens, endereço, entrega, descontos, totais e tentativa de pagamento.
- **Pedidos:** registro imutável do que foi comprado, status, cancelamento e histórico.
- **Integrações:** contratos e adaptadores para serviços externos.
- **Administração:** telas e operações de gestão, sempre autorizadas no servidor.
- **Medição e auditoria:** eventos de produto, logs técnicos e trilha de ações administrativas.

**Responsável por cada módulo:** [definir]  
**Dependências proibidas entre módulos:** [definir]

### 3.3 Instalação compartilhada no servidor

Na hospedagem atual, a proposta é manter uma instalação comum do Laravel fora das pastas públicas das contas e um ponto de entrada mínimo em cada `public_html`. Cada conta identifica sua loja e carrega somente sua configuração. O código compartilhado não deve ficar diretamente acessível pela web.

```text
/opt/plataforma/
├── releases/                     # Releases imutáveis do Laravel
│   ├── 1.0.0/                    # Código e vendor, somente leitura
│   ├── 1.1.0/
│   └── ...
└── platform-registry/            # Metadados sem segredos
    └── ...                       # tenant, domínio, release atribuída, estado

/home/
├── cliente_a/public_html/
│   ├── index.php                  # Bootstrap mínimo da loja e release atribuída
│   └── assets/                    # Assets públicos servidos para essa loja
└── cliente_b/public_html/
    ├── index.php                  # Bootstrap mínimo da loja e release atribuída
    └── assets/

/home/cliente_a/.platform/         # Fora do document root, propriedade da conta
└── tenants/loja_a/
    ├── config.php                 # Configuração e credenciais privadas
    └── runtime/                   # Sessões, caches, logs e temporários
```

Cada loja deve ter um bootstrap mínimo que selecione uma release aprovada e carregue a configuração privada da própria conta. O registro central pode guardar domínio, identificador, estado, versão instalada e direito de atualização, mas não deve conter credenciais legíveis por todas as contas. Segredos não devem ser servidos pelo `public_html`, versionados no repositório ou compartilhados entre contas sem necessidade.

**Diretório de releases:** `/opt/plataforma/releases/{versao}` (proposta; validar permissões da hospedagem).  
**Local proposto da configuração privada:** `/home/{conta}/.platform/tenants/{tenant_id}/config.php`.  
**Convenção de nomes para contas e tenants:** [definir]

### Configuração e segredos de cada tenant

**Decisão aprovada:** manter um arquivo de configuração por loja dentro da área privada da respectiva conta cPanel, fora de `public_html`, com permissões de leitura/escrita limitadas ao usuário daquela conta. O bootstrap daquela loja aponta para seu próprio arquivo. O arquivo contém ou referencia as credenciais do banco e os caminhos de runtime; nunca aceita caminhos ou nomes de conexão enviados pelo navegador.

O registro central guarda apenas metadados necessários à operação (tenant, domínio validado, release, entitlement e estado), sem credenciais de tenant. Esse desenho combina com execução PHP sob usuário individual e reduz o impacto de uma conta comprometida sobre os segredos de outras. Se o provisionamento central precisar ler ou gravar os arquivos das contas, criar um processo administrativo restrito e auditado; não conceder ao PHP público acesso global aos arquivos de configuração.

**Método de geração/rotação da configuração:** [definir]  
**Campos não secretos no registro central:** [definir]  
**Cópia de segurança e rotação de credenciais:** [definir]

### 3.4 Execução PHP e permissões no LiteSpeed/cPanel

Manter o LiteSpeed e preferir PHP LSAPI, sem planejar migração obrigatória para PHP-FPM. O processo PHP deve executar com o usuário da conta cPanel correspondente, nunca como `root`. O acesso de leitura às releases imutáveis pode ser concedido por grupo Linux ou ACL, conforme a configuração existente do servidor; o usuário da conta não deve poder modificar o código da release em execução.

**Regra:** não alterar propriedade, grupo, ACL ou permissões de produção até confirmar como LiteSpeed/LSAPI executa cada virtual host e validar o acesso com uma conta de teste. As permissões são um plano de implantação sujeito a diagnóstico, não comandos para aplicar às cegas.

Diagnóstico a realizar por SSH com acesso administrativo, registrando saída e versões:

```bash
/usr/local/lsws/bin/lswsctrl status
ls -1 /opt/cpanel/ | grep '^ea-php'
whmapi1 php_get_vhost_versions
ps aux | grep -E 'lsphp|php-fpm' | grep -v grep
grep -E 'open_basedir|user_ini.filename' /opt/cpanel/ea-php*/root/etc/php.ini
```

Após o diagnóstico, documentar:

- Versão do LiteSpeed: [ ]
- Versões PHP disponíveis e versão atribuída às contas: [ ]
- Handler efetivo por virtual host (LSAPI/FPM/outro): [ ]
- Usuário efetivo do processo PHP: [ ]
- Restrições `open_basedir` e diretivas relevantes: [ ]
- Estratégia de grupo/ACL aprovada e validada em homologação: [ ]

### 3.5 Laravel compartilhado e estado isolado por tenant

Compartilhar os arquivos de uma release entre lojas não significa compartilhar o estado de execução. Cada tenant precisa de diretórios próprios, localizados dentro do home da conta e fora do document root, para:

- `storage` e logs;
- cache da aplicação e cache de configuração/rotas;
- sessões;
- arquivos temporários da aplicação;
- uploads e mídia privada;
- arquivos gerados por tarefas e exportações.

O bootstrap por conta deve selecionar a release atribuída à loja, resolver o tenant por configuração confiável e apontar os caminhos de runtime para uma área privada dentro do home daquela conta/domínio. Não selecionar tenant apenas com base em um `Host` recebido sem validação. Validar compatibilidade com a versão do Laravel escolhida e evitar mudar caminhos globais em requisições concorrentes sem isolamento adequado.

**Versões exatas de Laravel/PHP e extensões:** definir no início da implementação após verificar compatibilidade e ambiente atualizado.  
**Diretório base de runtime:** `/home/{conta}/private/{dominio}/runtime/` (proposta; ajustar à estrutura e restrições reais do cPanel).  
**Subdiretórios previstos:** `sessions/`, `cache/`, `views/`, `logs/`, `tmp/` e arquivos gerados necessários.  
**Diretório temporário de upload do PHP:** [confirmar configuração efetiva de `upload_tmp_dir`/`sys_get_temp_dir` e se pode ser isolado por virtual host].  
**Estratégia de filas e cache:** [definir]  
**Tarefas agendadas e workers por tenant:** [definir]

Sessões e arquivos temporários da aplicação devem ficar dentro de `/home/{conta}/{dominio}` ou de um diretório privado equivalente da conta, nunca sob `public_html`. O diretório temporário usado pelo PHP para uploads pode ser diferente dos temporários do Laravel e precisa ser verificado separadamente. Usar nome de cookie de sessão específico por tenant/domínio e atributos seguros adequados.

### 3.6 React, Inertia e assets por tenant

Compartilhar páginas e componentes React comuns entre releases; personalizar a experiência principalmente com configuração de tema, conteúdo e mídias. Quando existir uma necessidade aprovada, permitir componentes específicos de uma loja por meio de extensões com contrato e versão, compatíveis com a release atribuída. Inertia transporta somente propriedades de tenant explicitamente permitidas, como nome, tokens de tema e URLs de logo. Não enviar segredos ou configurações internas nas props.

Para a primeira versão, usar build comum dos componentes-base por release, com assets, conteúdo e temas próprios por loja. Um `data-theme` ou tokens CSS podem selecionar identidade visual sem duplicar a aplicação. CSS/JavaScript customizados por cliente devem ter escopo, validação, compatibilidade de release e política de cache definidos.

Se a personalização exigir componentes React exclusivos, páginas próprias ou dependências distintas, avaliar builds independentes ou extensões versionadas. Isso aumenta custo de build, validação, compatibilidade e suporte; não introduzir antes de decidir o nível de extensão suportado.

**Política aprovada:** temas e conteúdos são a personalização principal; componentes específicos podem ser criados quando houver necessidade aprovada. Cada extensão deve ser versionada por tenant e declarar as releases compatíveis.

**Detalhes ainda a definir para extensões específicas:**

- [ ] Contrato e API de extensão dos componentes.
- [ ] Onde armazenar e como compilar os componentes específicos.
- [ ] Compatibilidade da extensão com releases do Core.
- [ ] Processo de revisão, publicação, desativação e rollback.

**Estratégia de build Vite:** build central + assets por tenant (proposta inicial) / [decisão final].  
**Local público dos assets:** [definir]  
**Versionamento e invalidação de cache dos assets:** [definir]

### 3.7 Banco de dados por cliente

A estratégia inicial será um banco independente por cliente/loja, com credenciais específicas e conexão selecionada pelo contexto seguro do tenant. Isso reduz o acoplamento entre dados de clientes, permite backup/restauração por loja e exige automação de provisionamento, migrações e monitoramento para cada banco.

Não colocar credenciais do banco no código nem aceitar nome de conexão arbitrário vindo do navegador. Definir limites de conexões, migrações compatíveis e procedimento para falha parcial durante atualização do Core.

**Motor e versão do banco:** [definir]  
**Como provisionar credenciais e bancos:** [definir]  
**Execução e auditoria de migrações:** [definir]  
**Backup/restauração individual por cliente:** [definir]

### 3.8 Multi-tenancy e isolamento

Esta plataforma mantém releases do Core acessíveis de forma compartilhada, mas cada loja seleciona sua própria versão instalada. O identificador e o contexto do tenant continuam necessários para resolver domínio, configuração, release atribuída, arquivos e permissões. Os bancos de negócio são independentes por loja.

Regras obrigatórias:

1. Registros no banco central da plataforma que pertençam a uma loja incluem `store_id` e índices apropriados. Em bancos de negócio separados, a própria conexão já delimita o tenant; incluir o identificador também quando ajudar auditoria, exportação ou migração, sem depender dele como único controle de acesso.
2. Toda leitura ou escrita de dados da loja deriva o contexto da requisição no servidor; nunca confiar em `store_id` recebido livremente do navegador.
3. Aplicar autorização também em tarefas assíncronas, webhooks, exportações e ferramentas administrativas.
4. Testar tentativas de acessar dados de outra loja em cada módulo com dados de pelo menos duas lojas.
5. Registrar qual operador fez alterações relevantes e em qual loja.
6. Definir limites por loja para evitar que uma instância monopolize recursos compartilhados.

**Estratégia multi-tenant:** releases compartilhadas e selecionadas por tenant; configuração/runtime e banco isolados por loja.  
**Plano para migração de estratégia:** [não aplicável no escopo atual / registrar mudança futura]  
**Limites por loja (requisições, armazenamento, tarefas):** [definir]

### 3.9 Resolução de loja e domínios

O sistema deve mapear domínio ou subdomínio validado para uma loja ativa. Um domínio não verificado não pode servir conteúdo de produção. A configuração deve prever domínio principal, domínios alternativos, redirecionamento canônico, TLS e estado de publicação.

**Formato de endereço inicial:** [subdomínio da plataforma / domínio próprio / ambos]  
**Processo de verificação de domínio:** [definir]  
**Comportamento para domínio inválido ou loja suspensa:** [definir]

### 3.10 Modelo de dados conceitual

Definir entidades e relações antes de codificar telas. Evitar campos específicos de um setor no núcleo do catálogo. Separar o registro central de lojas e domínios dos dados comerciais mantidos no banco de cada loja.

- **Banco central da plataforma:** `Store`, `StoreDomain`, plano/estado da loja, referência ao banco, configuração de provisionamento, `PlatformRelease`, release atribuída e entitlement de atualização por tenant; guardar apenas metadados não secretos sempre que possível.
- **Banco de cada loja:** `User`, `Role`, `Permission`, `CustomerAddress`; `Product`, `ProductVariant`, `Category`, `ProductAttribute`, `MediaAsset`; `Price`, `InventoryItem`, `InventoryMovement`; `Cart`, `CartItem`, `CheckoutSession`; `Order`, `OrderLine`, `PaymentAttempt`, `Shipment`; `Promotion`, `DiscountRule`; `ContentPage`, `NavigationMenu`, `ContentBlock`; `IntegrationConfig`, `WebhookEvent`; `AuditEvent` e eventos analíticos dentro da política de retenção.
- **Regra de separação:** resolver a loja antes de abrir conexão; cada requisição, tarefa, webhook e processo de migração deve selecionar a conexão correta no servidor.

**Convenções de nomes e identificadores:** [definir]  
**Campos extensíveis (metadados) permitidos e limites:** [definir]  
**Política de exclusão, anonimização e retenção:** [definir]

## 4. Regras de programação e qualidade

### 4.1 Estrutura e responsabilidades do código

- Organizar código por domínio funcional, não apenas por tipo genérico de arquivo.
- Manter interface, regras de negócio e acesso a dados em camadas separáveis.
- Regras de preço, estoque, autorização e pedido não devem existir apenas no navegador.
- Evitar duplicar lógica entre página pública, painel e API; chamar o mesmo caso de uso.
- Criar interfaces para integrações externas, com adaptadores concretos substituíveis.
- Preferir funções e módulos pequenos com nomes que expressem a intenção.
- Documentar decisões não óbvias e invariantes; evitar comentários que apenas repetem o código.

### 4.2 Configuração e temas

- Validar configurações por esquema e rejeitar valores inválidos antes da publicação.
- Separar configuração editável de segredos; segredos ficam em cofre apropriado e nunca são enviados ao cliente.
- Usar tokens de design (cor, tipografia, raio, espaçamento, largura) como contrato entre tema e componentes.
- Oferecer valores padrão acessíveis e uma pré-visualização antes de publicar.
- Versionar configurações; permitir comparar e reverter uma publicação.
- Não permitir que configurações arbitrárias executem código ou HTML inseguro.
- Definir blocos de página com tipos permitidos, validação e limites de conteúdo.

**Esquema de configuração e versão:** [definir]  
**Tokens obrigatórios de tema:** [definir]  
**Blocos de página suportados na versão inicial:** [definir]

### 4.3 APIs e contratos

- Definir contratos de entrada e saída com validação no limite do sistema.
- Usar erros consistentes: código estável, mensagem legível e identificador de rastreamento.
- Aplicar paginação, ordenação permitida e limites de tamanho em listagens.
- Versionar APIs públicas quando compatibilidade quebrada for inevitável.
- Tornar operações repetíveis seguras quando aplicável, especialmente criação de pedido, pagamento e webhooks.
- Não expor dados pessoais ou internos desnecessários na resposta.
- Documentar permissões e efeitos colaterais de cada operação administrativa.

**Formato padrão de erro:** [definir]  
**Política de versão da API:** [definir]  
**Limites de requisição:** [definir]

### 4.4 Concorrência, valores e consistência

- Representar dinheiro com tipo decimal apropriado ou unidades mínimas inteiras; não usar ponto flutuante binário para cálculos monetários.
- Registrar moeda em valores monetários e definir arredondamento por regra explícita.
- Recalcular e validar preço, desconto, frete e estoque no servidor durante o checkout.
- Proteger baixa de estoque e criação de pedido contra concorrência e duplicidade.
- Armazenar no pedido uma fotografia dos itens, preços, impostos e endereço aplicáveis no momento da compra.
- Definir estados válidos e transições para pedido, pagamento e entrega; rejeitar transições impossíveis.

**Moedas e precisão necessárias:** [definir]  
**Política de reserva/baixa de estoque:** [definir]  
**Máquina de estados de pedido:** [desenhar ou vincular]

### 4.5 Segurança e privacidade

- Aplicar menor privilégio em contas administrativas e integrações.
- Proteger sessões, senhas, recuperação de conta e operações de alto impacto.
- Validar e escapar conteúdo; limitar tipos e tamanho de arquivos enviados.
- Guardar segredos fora do repositório, com rotação e acesso auditável.
- Verificar dependências e corrigir vulnerabilidades relevantes.
- Minimizar dados pessoais coletados e limitar retenção conforme finalidade e regras aplicáveis.
- Fornecer meios claros de consentimento quando necessários e respeitar preferências de comunicação.
- Redigir logs para não armazenar senhas, tokens ou dados de pagamento sensíveis.
- Definir resposta a incidentes, cópias de segurança e recuperação.

**Responsável por privacidade/segurança:** [definir]  
**Política de acesso e retenção:** [definir]  
**Objetivo de recuperação de serviço e dados:** [definir]

### 4.6 Acessibilidade e internacionalização

- Componentes devem funcionar por teclado, apresentar foco visível e associar rótulos a controles.
- Manter contraste e hierarquia tipográfica legíveis; não depender somente de cor para comunicar estado.
- Fornecer texto alternativo contextual para imagens relevantes.
- Tratar idioma, formato de data, moeda, fuso horário e direção do texto como configuração, não como constantes espalhadas.
- Não traduzir conteúdo do lojista automaticamente sem indicar revisão ou origem.

**Meta de acessibilidade:** [definir]  
**Idiomas e regiões iniciais:** [definir]

## 5. Experiência que ajuda clientes a descobrir e comprar

O objetivo de retenção é oferecer uma experiência útil para que a pessoa queira voltar por confiança, relevância e facilidade. Não usar padrões enganosos, urgência falsa, custos escondidos, obstáculos para cancelar ou notificações sem consentimento.

### 5.1 Princípios de atenção e confiança

1. **Orientação imediata:** em poucos segundos, a pessoa entende o que o site oferece e como começar a navegar.
2. **Hierarquia visual:** destacar uma ação principal por contexto; reduzir competição entre banners e chamadas.
3. **Descoberta progressiva:** apresentar categorias, pesquisa e filtros sem sobrecarregar a tela.
4. **Informação suficiente:** mostrar preço, variantes, disponibilidade e condições relevantes antes de pedir dados ou pagamento.
5. **Continuidade:** preservar busca, filtros e carrinho quando a pessoa navega, atualiza a página ou retorna, conforme política de privacidade.
6. **Feedback claro:** responder a cada ação com confirmação, carregamento, erro útil ou alternativa.
7. **Consistência:** manter padrões de navegação e comportamento entre páginas e lojas, mesmo com temas diferentes.
8. **Desempenho percebido:** priorizar conteúdo principal, evitar mudanças inesperadas de layout e informar carregamentos demorados.

### 5.2 Jornada padrão

| Momento | Necessidade do cliente | Recursos do produto | Sinal de boa experiência |
|---|---|---|---|
| Entrada | Entender a loja e encontrar um caminho | Proposta clara, navegação, categorias, busca e conteúdo principal | A pessoa consegue iniciar uma busca ou navegar sem adivinhar. |
| Exploração | Reduzir opções e comparar | Filtros úteis, ordenação, disponibilidade, preço e contagem de resultados | Filtros explicam o efeito e podem ser removidos facilmente. |
| Avaliação | Decidir se o item atende | Imagens, descrição, atributos, variantes, preço total relevante e política aplicável | Informações essenciais aparecem antes da decisão. |
| Intenção | Guardar itens para avaliar ou comprar | Carrinho persistente, quantidade, remoção e indicação de disponibilidade | Alterações são simples e o total se atualiza com clareza. |
| Checkout | Concluir com confiança | Etapas curtas, resumo, endereço, entrega, pagamento e erros acionáveis | Custos e condições aparecem antes da confirmação final. |
| Pós-compra | Saber o que acontece em seguida | Confirmação, histórico, status, suporte e preferências de comunicação | Próximos passos e canal de ajuda ficam claros. |
| Retorno | Retomar uma necessidade ou compra anterior | Histórico, listas salvas opcionais e recomendações pertinentes | O retorno é útil e não depende de pressão artificial. |

### 5.3 Requisitos para reduzir fricção

- Busca disponível e compreensível em todas as páginas principais; estado vazio deve sugerir correções ou categorias relacionadas.
- Filtros devem refletir os atributos existentes no catálogo da loja, mostrar filtros ativos e oferecer limpeza simples.
- Cartão de produto deve comunicar informações essenciais sem exigir abrir cada item.
- Detalhe do produto deve diferenciar descrição, especificações, variantes, disponibilidade e condições comerciais.
- Checkout deve pedir somente dados necessários à operação; permitir compra sem conta se a política do produto permitir.
- Mostrar custos adicionais, opções de entrega e restrições antes do envio do pedido.
- Preservar o conteúdo do carrinho quando ocorrer erro recuperável; não confirmar pagamento duas vezes.
- Formulários devem indicar formato esperado e apontar erros no campo correspondente sem apagar o restante.
- Dar controle para fechar banners, dispensar mensagens e alterar preferências de comunicação.

### 5.4 Personalização, recomendações e retorno

- Começar com regras explicáveis: relacionados por categoria/atributo, mais vistos ou coleções selecionadas pelo lojista.
- Exibir recomendações apenas quando houver sinal suficiente e relevância; esconder o módulo quando não houver resultados úteis.
- Distinguir conteúdo patrocinado ou promoção de recomendações orgânicas quando aplicável.
- Permitir que a loja desative personalização e configure retenção de dados.
- Não inferir atributos sensíveis nem usar dados de uma loja para personalizar outra.
- Frequência de e-mails e notificações deve respeitar consentimento, preferências e opção de descadastro.

**Estratégias de recomendação iniciais:** [definir]  
**Dados usados e período de retenção:** [definir]  
**Controles disponíveis ao cliente:** [definir]

### 5.5 Métricas de experiência

Medir funis agregados para localizar problemas, não para substituir pesquisa com usuários. Definir eventos com nomes e propriedades documentados, sem dados pessoais desnecessários.

- Descoberta: pesquisa iniciada, pesquisa sem resultado, filtro aplicado, produto aberto.
- Intenção: variante escolhida, item adicionado/removido, carrinho visualizado.
- Compra: checkout iniciado, etapa concluída, erro recuperável, pedido confirmado.
- Qualidade: tempo de carregamento, falhas, abandono por etapa e contatos de suporte relacionados.
- Retorno: visitas recorrentes e recompra em janelas definidas, com consentimento e anonimização apropriados.

Para cada métrica, registrar: **definição** [ ], **evento** [ ], **segmento** [ ], **meta** [ ], **responsável** [ ]. Evitar otimizar apenas cliques ou tempo no site; priorizar conclusão bem-sucedida, compreensão, satisfação e ausência de surpresas.

## 6. Administração e criação massiva de lojas

### 6.1 Fluxo de provisionamento

1. Criar registro de loja com identificador estável e estado `rascunho`.
2. Aplicar configuração inicial versionada e tema padrão acessível.
3. Criar primeiro usuário administrador e atribuir permissões mínimas necessárias.
4. Solicitar subdomínio ou domínio; validar posse antes de publicação.
5. Guiar o operador pela configuração em etapas: marca, navegação, catálogo de exemplo/importação, entrega, pagamento e políticas.
6. Validar seções obrigatórias e integrações em modo de teste.
7. Gerar pré-visualização protegida e lista de pendências.
8. Publicar atomicamente uma versão de configuração e ativar o domínio.
9. Registrar evento de auditoria e oferecer procedimento de reversão.

**Campos obrigatórios para criar loja:** [definir]  
**Etapas que podem ser puladas:** [definir]  
**Critérios para permitir publicação:** [definir]

### 6.2 Painel da plataforma e painel da loja

Separar claramente:

- **Painel da plataforma:** provisiona lojas, gerencia planos/limites, saúde, suporte e incidentes. Acesso altamente restrito e auditado.
- **Painel da loja:** gerencia conteúdo, catálogo, operações e usuários daquela loja. Toda ação tem escopo da loja.

Definir papéis iniciais como proprietário, administrador, operador de catálogo, atendimento e analista. Permissões devem ser granulares e verificadas no servidor.

**Papéis e permissões:** [definir]  
**Ações que exigem confirmação adicional:** [definir]

### 6.3 Importação, modelos e automação

- Criar modelos iniciais neutros de navegação, página e tema; não incluir pressupostos de setor como estrutura obrigatória.
- Importação de catálogo deve validar colunas, apresentar prévia e relatório de erros antes de gravar.
- Permitir exportar dados da loja em formato documentado.
- Automatizar tarefas repetíveis e idempotentes (provisionar domínio, enviar confirmação, sincronizar catálogo).
- Exibir progresso, resultado e possibilidade de correção; não esconder falhas parciais.

**Formatos de importação/exportação:** [definir]  
**Modelos neutros disponibilizados:** [definir]

## 7. Tutorial de implementação por fases

Cada fase só avança quando seus critérios de saída estiverem atendidos. O plano é iterativo: construir uma fatia simples de ponta a ponta e então ampliar.

### Fase 1 — Especificar o produto e os limites

**Finalidade:** transformar a ideia da plataforma em requisitos verificáveis.  
**Passos:** documentar atores; separar capacidades comuns de configurações por loja; definir escopo da primeira versão; escrever jornadas e estados de erro; registrar requisitos não funcionais.  
**Saídas:** visão do produto, mapa de capacidades, requisitos priorizados e glossário.  
**Critério de saída:** cada requisito tem motivo, prioridade e forma de validação.  
**Responsável/prazo/status:** [definir]

### Fase 2 — Desenhar domínio e contratos

**Finalidade:** reduzir retrabalho causado por modelos e interfaces ambíguos.  
**Passos:** desenhar entidades; definir propriedade e escopo multi-tenant; mapear estados e transições; especificar APIs e eventos; decidir campos extensíveis e seus limites.  
**Saídas:** diagrama de dados, contratos de API/eventos e decisões arquiteturais registradas.  
**Critério de saída:** fluxos principais podem ser descritos sem depender de campo específico de um setor.  
**Responsável/prazo/status:** [definir]

### Fase 3 — Preparar repositório, ambiente Laravel e fluxo de desenvolvimento

**Finalidade:** tornar mudanças repetíveis e revisáveis, compatíveis com a hospedagem real.  
**Passos:** estabelecer estrutura de módulos Laravel; atualizar/confirmar o servidor para suportar o conjunto estável e compatível de PHP, Laravel e extensões escolhido; registrar as versões exatas; confirmar LiteSpeed/LSAPI e usuário efetivo por virtual host; validar acesso somente de leitura às releases e escrita aos diretórios de runtime em homologação; configurar React/Inertia/Vite e build; preparar ambientes local, teste e produção; definir migrações de cada banco; configurar revisão de código, gestão de segredos e processo de release imutável.  
**Saídas:** aplicação mínima executável, instruções locais, relatório de diagnóstico do WHM, mapa de permissões e pipeline de integração.  
**Critério de saída:** outra pessoa consegue iniciar o projeto seguindo a documentação, e uma conta de homologação executa o Laravel sem privilégios de root e sem acesso ao estado de outro tenant. As versões estão fixadas nos manifests/lockfiles e o ambiente web LSAPI corresponde aos requisitos definidos.  
**Responsável/prazo/status:** [definir]

### Fase 4 — Construir tenancy, identidade e configuração

**Finalidade:** criar a fundação que permite a existência segura de várias lojas.  
**Passos:** implementar resolução de loja e ponto de entrada por conta; criar contexto tenant no servidor; carregar conexão de banco e caminhos isolados; adicionar autenticação e permissões; validar configurações e temas; criar provisionamento em rascunho; adicionar auditoria.  
**Saídas:** lojas isoladas, bootstrap mínimo por `public_html`, banco independente por tenant, domínio/subdomínio de teste e painel mínimo.  
**Critério de saída:** operador de uma loja não consegue ler nem modificar outra; configuração inválida não publica; diretórios graváveis não são compartilhados; código comum não fica publicamente acessível.  
**Responsável/prazo/status:** [definir]

### Fase 5 — Construir catálogo e descoberta

**Finalidade:** oferecer estrutura genérica para cadastrar e localizar itens.  
**Passos:** implementar categorias, produto, variantes, atributos, mídia, preço e disponibilidade; criar listagem, detalhe, busca, filtro e ordenação; incluir conteúdo vazio e erro.  
**Saídas:** catálogo funcional com dados fictícios em duas lojas de aparência distinta.  
**Critério de saída:** o modelo não depende de atributos fixos de uma única categoria comercial.  
**Responsável/prazo/status:** [definir]

### Fase 6 — Construir carrinho, checkout e pedido

**Finalidade:** completar a jornada transacional com consistência e feedback claro.  
**Passos:** implementar carrinho persistente conforme política definida; recalcular valores no servidor; modelar checkout e validações; criar pedido com fotografia dos dados comerciais; tratar tentativa, falha e confirmação de pagamento; fazer transições idempotentes.  
**Saídas:** compra demonstrável em modo de teste, com histórico e estados rastreáveis.  
**Critério de saída:** repetição de requisição ou webhook não duplica cobrança nem pedido.  
**Responsável/prazo/status:** [definir]

### Fase 7 — Construir temas, conteúdo e editor

**Finalidade:** permitir que cada loja tenha aparência e conteúdo próprios sem bifurcar o produto.  
**Passos:** implementar tokens de design; criar biblioteca de componentes React; integrar Inertia com props de tenant permitidas; configurar build Vite para cada release; publicar assets comuns e mídias/tema por tenant; definir blocos de conteúdo; estabelecer o contrato para extensões de componentes específicos; fazer edição, pré-visualização, validação, publicação e reversão.  
**Saídas:** duas ou mais lojas com identidade visual distinta usando releases atribuídas independentemente; contrato documentado para componentes específicos quando aprovados.  
**Critério de saída:** mudança de tema não quebra checkout, acessibilidade ou layout responsivo; assets resolvem para o tenant e release corretos e têm cache/versionamento previsíveis; extensão incompatível não é carregada.  
**Responsável/prazo/status:** [definir]

### Fase 8 — Implementar adaptadores e integrações

**Finalidade:** conectar serviços externos sem acoplar regras centrais a um fornecedor.  
**Passos:** definir contratos de pagamento, entrega, e-mail e busca; criar modo simulado; implementar primeiro adaptador real; verificar assinatura de webhook; adicionar repetição segura, timeouts e observabilidade.  
**Saídas:** integrações testáveis e substituíveis.  
**Critério de saída:** indisponibilidade externa gera estado recuperável e mensagem clara.  
**Responsável/prazo/status:** [definir]

### Fase 9 — Construir operação e automação de lojas

**Finalidade:** tornar viável criar e manter muitas lojas sem trabalho manual repetitivo.  
**Passos:** concluir assistente de provisionamento; criar importação/exportação; aplicar limites por plano; preparar publicação de configuração; incluir diagnósticos e painel de saúde; registrar release instalada, releases disponíveis e direito de atualização por tenant.  
**Saídas:** criação de loja previsível, documentada e reversível, com atribuição de release independente.  
**Critério de saída:** uma loja nova pode ser criada e validada por fluxo padronizado sem alteração manual no banco; atualizar uma loja não altera a release atribuída às demais.  
**Responsável/prazo/status:** [definir]

### Fase 10 — Validar segurança, experiência e capacidade

**Finalidade:** encontrar falhas antes de ampliar o número de lojas.  
**Passos:** revisar isolamento; testar papéis; percorrer jornada em dispositivos e temas distintos; medir desempenho; simular falha de integrações; testar cópias e restauração; revisar privacidade e acessibilidade.  
**Saídas:** relatório de validação, problemas classificados e plano de correção.  
**Critério de saída:** riscos críticos resolvidos e capacidade observada atende às metas definidas.  
**Responsável/prazo/status:** [definir]

### Fase 11 — Publicar e operar

**Finalidade:** lançar a plataforma com capacidade de observar e recuperar o serviço.  
**Passos:** publicar release imutável; ativar alertas; documentar rollback por tenant; criar runbooks de entitlement, atualização, migração e suporte; atualizar uma loja piloto sem afetar outras; acompanhar erros e métricas antes de oferecer a release a mais clientes.  
**Saídas:** release publicada, operação monitorada e fluxo de atualização individual documentado.  
**Critério de saída:** equipe consegue identificar falhas, limitar impacto à loja atualizada e restaurar a versão anterior sem interromper outros tenants.  
**Responsável/prazo/status:** [definir]

## 8. Validação e critérios de aceite

Definir uma matriz de verificação antes de cada release:

- **Isolamento:** dados, arquivos, busca, cache, tarefas e exportações de duas lojas não se misturam.
- **Configuração:** configuração inválida é rejeitada; publicação e reversão são previsíveis.
- **Compra:** preços e disponibilidade são recalculados no servidor; pedido mantém os valores confirmados.
- **Idempotência:** repetições em checkout e webhooks não criam efeitos duplicados.
- **Autorização:** cada papel só executa ações concedidas e sempre dentro de sua loja.
- **Experiência:** busca, filtros, carrinho e checkout têm estados vazio, carregando, sucesso e erro.
- **Responsividade e acessibilidade:** jornadas principais funcionam com teclado e em telas pequenas.
- **Resiliência:** queda de serviço externo não perde o pedido nem deixa estado indefinido.
- **Desempenho:** metas definidas são atingidas com volume representativo de lojas e catálogo.
- **Operação:** backup pode ser restaurado; logs e alertas identificam o contexto sem vazar segredos.
- **Hospedagem:** LiteSpeed encaminha cada conta ao handler PHP esperado; o processo executa sob o usuário correto; leitura da release e escrita no runtime foram verificadas em homologação.
- **Atualização independente:** trocar a release de um tenant não muda as outras; entitlement, migração, registro de resultado e recuperação foram definidos por loja.

**Meta de lojas simultâneas:** [definir]  
**Volume de produtos/pedidos por loja:** [definir]  
**Metas de disponibilidade e latência:** [definir]  
**Critérios para liberar uma versão:** [definir]

## 9. Implantação, observabilidade e manutenção

### Ambientes e publicação

- Manter ambientes separados para desenvolvimento, homologação e produção.
- Promover a mesma versão de artefato entre ambientes; variar apenas configuração e segredos.
- Manter releases imutáveis e fazer cada tenant apontar para uma versão aprovada.
- Fazer migrações compatíveis com as versões que ainda estejam em uso; registrar o resultado por loja.
- Usar publicação gradual ou atualização individual, com caminho de reversão por tenant.
- Versionar tema/configuração por loja de forma independente da versão do código.

#### Releases independentes e venda de atualizações

**Decisão:** cada loja pode permanecer em sua versão e receber atualizações de forma independente. Não substituir uma release em uso; publicar novas releases imutáveis em `/opt/plataforma/releases/{versao}` e manter um ponteiro/atribuição de versão por tenant. Isso permite comercializar atualizações sem forçar a atualização de todas as lojas.

Cada atualização precisa ter versão, changelog, requisitos, compatibilidade de extensões e de PHP, instruções de migração e política de suporte. O mecanismo comercial deve registrar quais versões estão disponíveis para cada loja e liberar o upgrade depois da aquisição/entitlement válido. Definir se atualizações críticas de segurança terão política especial de aviso, suporte ou distribuição.

Fluxo de atualização por tenant:

1. Publicar pacote imutável com código, dependências travadas, assets e metadados da release.
2. Registrar requisitos e compatibilidades; validar se o tenant possui direito de acesso à atualização.
3. Pré-verificar requisitos de PHP/extensões, espaço e compatibilidade das extensões instaladas.
4. Fazer backup conforme política e aplicar migrações compatíveis ao banco daquele tenant.
5. Trocar apenas a release atribuída àquele tenant e verificar saúde da aplicação.
6. Se houver falha, reverter o ponteiro de código e aplicar plano de recuperação de dados; migrações devem preferir padrão expand/contract e não depender de rollback destrutivo automático.
7. Registrar versão anterior/nova, operador, resultado e horário.

**Formato de licença/entitlement de atualização:** [definir]  
**Versões incluídas na compra inicial e upgrades pagos:** [definir]  
**Política de suporte e segurança para versões antigas:** [definir]  
**Como coordenar migrações por banco de tenant:** migração individual vinculada à atualização daquela loja (proposta).  
**Tratamento de falha durante atualização de uma loja:** bloquear nova versão só para essa loja, manter outras lojas operacionais e executar recuperação documentada.  
**Frequência de release:** [definir]

**Estratégia de deploy:** [definir]  
**Plano de reversão:** [definir]  
**Janela e responsáveis por publicação:** [definir]

#### Política de versões

No início da implementação, selecionar versões estáveis atuais que sejam compatíveis entre si e suportadas pelo ambiente atualizado. “Mais atual” é uma regra para a seleção inicial, não uma dependência flutuante em produção: registrar versões exatas nos manifests e lockfiles, manter ambiente de desenvolvimento/homologação equivalente à produção e atualizar dependências por releases planejadas com revisão de compatibilidade.

**Data da seleção inicial das versões:** [definir]  
**Matriz de versões aprovada (PHP/Laravel/Node/React/Inertia/Vite/banco):** [registrar]  
**Política de atualização de dependências:** [definir]

### Observabilidade

- **Logs:** eventos estruturados com identificador de requisição e loja; remover segredos e dados sensíveis.
- **Métricas técnicas:** latência, erros, saturação, fila, banco e disponibilidade de integrações.
- **Métricas de produto:** funil e busca, agregados e com retenção documentada.
- **Alertas:** acionáveis, com severidade, responsável e instrução inicial.
- **Rastreamento:** seguir uma operação entre módulos e integrações sem registrar conteúdo sensível.

**Ferramentas:** [definir]  **Responsável de plantão:** [definir]  **Canais de incidente:** [definir]

## 10. Documentação do produto e decisões

Manter atualizados:

- Guia para executar o projeto localmente.
- Diagrama de arquitetura e limites dos módulos.
- Modelo de dados e política de migrações.
- Contratos de API, eventos e integrações.
- Manual de provisionamento, configuração e publicação de loja.
- Guia de temas e componentes permitidos.
- Procedimentos de backup, restauração, incidentes e reversão.
- Registro de decisões arquiteturais (`ADR`): contexto, decisão, alternativas, consequências e data.

**Local da documentação técnica:** [definir]  
**Responsável por revisão:** [definir]  **Periodicidade:** [definir]

## 11. Backlog inicial do produto

| Item | Finalidade | Prioridade | Dependência | Critério de aceite | Responsável/status |
|---|---|---|---|---|---|
| Resolução de loja por domínio | [ ] | [P1/P2/P3] | [ ] | [ ] | [ ] |
| Configuração e tema versionados | [ ] | [P1/P2/P3] | [ ] | [ ] | [ ] |
| Catálogo genérico | [ ] | [P1/P2/P3] | [ ] | [ ] | [ ] |
| Busca e filtros | [ ] | [P1/P2/P3] | [ ] | [ ] | [ ] |
| Carrinho e checkout | [ ] | [P1/P2/P3] | [ ] | [ ] | [ ] |
| Administração e papéis | [ ] | [P1/P2/P3] | [ ] | [ ] | [ ] |
| Adaptadores de integração | [ ] | [P1/P2/P3] | [ ] | [ ] | [ ] |
| Provisionamento de lojas | [ ] | [P1/P2/P3] | [ ] | [ ] | [ ] |
| Observabilidade e recuperação | [ ] | [P1/P2/P3] | [ ] | [ ] | [ ] |

## 12. Registro de decisões e próximos passos

### Decisões arquiteturais

| Data | Decisão | Motivo | Consequências/alternativas descartadas | Responsável |
|---|---|---|---|---|
| [ ] | [ ] | [ ] | [ ] | [ ] |

### Próxima execução

**Fase atual:** [definir]  
**Próxima tarefa concreta:** [definir]  
**Responsável:** [definir]  
**Prazo:** [definir]  
**Bloqueios:** [definir]  
**Data da próxima revisão:** [definir]
