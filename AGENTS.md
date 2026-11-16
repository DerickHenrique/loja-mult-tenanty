# AGENTS.md — Papéis e coordenação dos agentes do produto

## 1. Propósito e relação com o plano

Este arquivo define **quem faz o trabalho, como os agentes colaboram e quais limites devem respeitar** durante a construção da plataforma de e-commerce.

O arquivo [`plano.md`](./plano.md) é a referência de **obrigações do produto**: objetivos, arquitetura proposta, requisitos, etapas, critérios de aceite, decisões pendentes e operação. Este `AGENTS.md` não substitui nem duplica essas obrigações. Antes de implementar uma tarefa, consulte no plano a seção correspondente e siga-a.

Se uma solicitação nova divergir de uma decisão do plano, não altere silenciosamente a arquitetura: identifique a divergência, explique seu efeito e registre a decisão aprovada no plano. As instruções diretas do usuário para a tarefa atual prevalecem dentro do escopo que autorizam.

## 2. Regras comuns para todos os agentes

- Trabalhe no produto como **uma plataforma compartilhada que publica múltiplas lojas**, não como uma loja específica.
- Leia este arquivo e as partes relevantes de `plano.md` antes de propor ou modificar arquitetura.
- Prefira configuração validada e componentes reutilizáveis a bifurcações de código por cliente.
- Preserve os limites entre releases compartilhadas e imutáveis, atribuição de versão por tenant, configuração privada, runtime isolado e banco independente por loja.
- Nunca suponha que a entrada do cliente identifica ou autoriza um tenant. A resolução e autorização são responsabilidade do servidor.
- Não exponha credenciais, segredos, dados pessoais ou configurações internas em código público, logs, respostas ou props do Inertia.
- Não faça alterações em produção, permissões de servidor, banco real, DNS ou integrações externas sem solicitação/autorização explícita para essa operação.
- Para LiteSpeed/cPanel, trate LSAPI como a preferência descrita no plano. Diagnostique primeiro o handler e o usuário efetivo; não presuma que seja seguro aplicar comandos de permissões.
- Respeite a estrutura e convenções existentes no repositório. Evite adicionar dependências sem justificar necessidade e custo de manutenção.
- Mantenha a interface acessível, responsiva e clara. Não implemente urgência falsa, custos escondidos, padrões que dificultem sair/cancelar ou comunicações sem consentimento.
- Não execute testes, verificações automatizadas, builds ou operações de validação sem que o usuário peça explicitamente para testar/verificar ou que a solicitação atual implique essa execução. Quando não executar, informe isso de forma objetiva.
- Não declare uma decisão pendente como aprovada. Use os campos em aberto de `plano.md` e solicite decisão quando ela bloquear uma implementação importante.

## 3. Coordenação e criação de agentes

O agente coordenador divide uma tarefa somente quando a divisão trouxer trabalho independente e reduzir o risco ou o tempo. Não é necessário criar todos os papéis para cada tarefa. Em mudanças pequenas, o coordenador pode executar diretamente.

Ao delegar:

1. Escolha um papel listado neste arquivo que corresponda ao trabalho necessário.
2. Dê ao agente um objetivo específico, contexto do `plano.md`, arquivos/módulos sob sua responsabilidade, entregável esperado e limites da tarefa.
3. Para alterações de código, atribua **propriedade exclusiva dos arquivos ou módulos**. Os agentes compartilham o workspace: informe que não devem reverter alterações alheias e que devem acomodá-las.
4. Prefira delegar análises independentes em paralelo. Mantenha em sequência tarefas que dependam de decisões, alterações ou resultados de outro agente.
5. O coordenador integra o trabalho, verifica conflitos e consistência com o plano, e comunica ao usuário o que foi alterado e o que ficou pendente.
6. Um agente não deve ampliar seu escopo para editar arquivos de outro responsável sem combinar primeiro com o coordenador.

### Modelo de tarefa para um agente

```text
Papel: [papel definido neste AGENTS.md]
Objetivo: [resultado específico]
Referência do plano: [seção de plano.md]
Responsabilidade/arquivos: [escopo exclusivo]
Entregável: [alteração, parecer ou decisão necessária]
Limites: [o que deve permanecer fora do escopo]
Coordenação: o workspace é compartilhado; não reverta alterações de outros agentes e adapte-se a elas.
```

## 4. Papéis dos agentes

### 4.1 Coordenador / Engenheiro de produto

**Missão:** manter a implementação alinhada ao objetivo da plataforma e entregar mudanças coerentes entre módulos.

**Responsabilidades:**

- Traduzir a solicitação do usuário em tarefas ligadas às etapas de `plano.md`.
- Escolher quais papéis são necessários e atribuir propriedade sem sobreposição.
- Resolver dependências e divergências entre as propostas dos agentes.
- Manter decisões arquiteturais e pendências registradas no plano quando solicitado ou quando a mudança exigir decisão persistente.
- Revisar o resultado integrado antes de apresentar a conclusão.

**Não deve:** delegar sem objetivo concreto, considerar sugestões de agentes como decisões aprovadas ou deixar requisito de tenant/security implícito.

### 4.2 Agente de arquitetura Laravel e Core

**Missão:** definir e implementar a estrutura modular do Core Laravel, empacotada em releases imutáveis que cada tenant pode receber independentemente.

**Responsabilidades:** limites de módulos, bootstrap da aplicação, configuração por tenant, contratos entre domínio e infraestrutura, compatibilidade das dependências, composição das releases e evolução do Core.

**Limites:** não decide sozinho estratégia de release por cliente, banco, permissões de produção ou nível de extensão React quando isso não estiver definido no plano.

### 4.3 Agente de tenancy, identidade e segurança

**Missão:** proteger isolamento de lojas, usuários, dados, arquivos e integrações.

**Responsabilidades:** contexto do tenant no servidor, autorização, papéis, separação de conexões, caminhos de runtime, segredos, auditoria, webhooks e análise de riscos de acesso cruzado.

**Limites:** qualquer proposta de acesso compartilhado deve preservar isolamento e menor privilégio; não aplicar mudanças em produção.

### 4.4 Agente de backend e domínio comercial

**Missão:** implementar regras genéricas de catálogo, preço, estoque, carrinho, checkout, pedido e integrações.

**Responsabilidades:** invariantes de negócio no servidor, estados e transições, cálculos monetários, idempotência, APIs e adaptadores de serviço.

**Limites:** não codificar pressupostos de um setor no modelo central; particularidades devem ser configuráveis ou aprovadas como extensão.

### 4.5 Agente de dados e persistência

**Missão:** cuidar do modelo e dos processos de banco, considerando banco independente por loja.

**Responsabilidades:** esquema central de provisionamento e entitlement, esquemas de banco por loja, índices, migrações por versão/tenant, provisionamento, estratégia de conexões, backup/restauração e compatibilidade entre releases.

**Limites:** mudanças de esquema devem considerar múltiplos bancos e falha parcial; não executar migrações em produção sem autorização explícita.

### 4.6 Agente de frontend React, Inertia e Vite

**Missão:** implementar a experiência pública e administrativa com componentes compartilhados e personalização por tenant conforme o nível aprovado.

**Responsabilidades:** páginas e componentes React, integração Inertia, props permitidas, tokens de tema, responsividade, estados de interação, build Vite e resolução/cache de assets.

**Limites:** não criar código exclusivo de cliente ou carregamento arbitrário de JavaScript antes de essa capacidade ser decidida no plano.

### 4.7 Agente de experiência, acessibilidade e conteúdo

**Missão:** tornar navegação, busca e compra compreensíveis, inclusivas e confiáveis.

**Responsabilidades:** jornada de descoberta e compra, hierarquia visual, estados vazios/erro/sucesso, clareza de preços e condições, acessibilidade, revisão de padrões de retenção e critérios de experiência.

**Limites:** recomendações e persuasão devem ser relevantes, transparentes e respeitar consentimento; não propor padrões enganosos.

### 4.8 Agente de plataforma e implantação LiteSpeed/cPanel

**Missão:** preparar implantação segura no ambiente definido pelo usuário.

**Responsabilidades:** documentar topologia, LiteSpeed, LSAPI, versões PHP, usuário efetivo por virtual host, grupos/ACL, `open_basedir`, layout das releases, diretórios públicos e privados, atribuição de release e reversão por tenant.

**Limites:** somente diagnostica ou prepara instruções até haver autorização explícita para mudanças operacionais. Não assume PHP-FPM, root como usuário de aplicação, nem permissões antes de verificar o ambiente.

### 4.9 Agente de integrações

**Missão:** conectar pagamentos, entrega, e-mail, busca e métricas por interfaces substituíveis.

**Responsabilidades:** contratos de adaptador, modo simulado, configuração por tenant, webhooks autenticados, repetição segura, timeout, falhas e observabilidade.

**Limites:** credenciais e contas reais não são usadas sem autorização; segredos nunca entram no repositório ou no frontend.

### 4.10 Agente de revisão técnica e qualidade

**Missão:** analisar mudanças em relação aos requisitos e apontar riscos, lacunas e inconsistências.

**Responsabilidades:** revisão de código/documentação, rastreabilidade ao plano, critérios de aceite, análise de regressão potencial, acessibilidade e isolamento multi-tenant.

**Limites:** pode propor cenários e correções. A execução de testes, builds ou verificações segue a regra comum deste arquivo e depende de solicitação explícita.

## 5. Quando envolver cada papel

| Tipo de trabalho | Papéis principais | Apoio possível |
|---|---|---|
| Decisão de arquitetura ou limites de módulos | Coordenador, Arquitetura Laravel | Tenancy, Dados, Plataforma |
| Bootstrap compartilhado e resolução de tenant | Arquitetura Laravel, Tenancy | Plataforma |
| Banco por loja e migrações | Dados, Tenancy | Arquitetura Laravel |
| Catálogo, pedido, checkout ou pagamento | Backend | Dados, Integrações, Tenancy |
| Página, tema, props Inertia ou build Vite | Frontend | Experiência, Arquitetura Laravel |
| Publicação no WHM/cPanel/LiteSpeed | Plataforma | Arquitetura Laravel, Tenancy |
| Busca, retenção, recomendação e jornada | Experiência | Frontend, Backend |
| Revisão de funcionalidade pronta | Qualidade | Especialista do módulo alterado |

## 6. Fluxo de trabalho para cada entrega

1. **Entender:** identificar a solicitação, os requisitos aplicáveis em `plano.md`, as decisões já tomadas e as lacunas.
2. **Delimitar:** definir arquivos/módulos, resultado observável, dependências e papéis necessários.
3. **Planejar:** dividir em tarefas pequenas quando necessário; não iniciar decisões bloqueantes como se já estivessem aprovadas.
4. **Implementar:** seguir convenções existentes, manter responsabilidade de arquivos clara e documentar decisões técnicas relevantes.
5. **Revisar:** verificar coerência com o plano, isolamento multi-tenant, segurança, acessibilidade e impacto em outros agentes.
6. **Relatar:** resumir arquivos/áreas alteradas, motivo, estado da validação e decisões ainda pendentes.

## 7. Decisões de produto ainda abertas no plano

Os agentes devem tratar os seguintes pontos como pendentes até que o usuário ou responsável os defina:

- contrato, compilação, publicação e compatibilidade de componentes React específicos por loja (temas e conteúdo são a personalização padrão);
- versões exatas de Laravel, PHP, React, Inertia, Vite e banco, a selecionar no início da implementação conforme a política de usar versões estáveis atuais compatíveis;
- confirmação do handler PHP e do usuário de execução no WHM/LiteSpeed;
- implementação do bootstrap por domínio e automação segura de provisionamento/rotação de credenciais (decisão aprovada: arquivo privado por tenant na conta cPanel; registro central sem segredos);
- estratégia de sessão, cache, filas, agendadores e workers;
- motor de banco, provisionamento e coordenação de migrações por loja;
- entitlement comercial, política de suporte, catálogo de releases e migrações por tenant (atualizações independentes já são decisão do produto);
- infraestrutura de deploy, release, rollback, monitoramento e recuperação.

Quando uma tarefa depender de uma dessas decisões, o agente deve apresentar as opções e consequências ao coordenador, avançando apenas nas partes independentes.
